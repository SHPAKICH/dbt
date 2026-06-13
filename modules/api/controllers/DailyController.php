<?php

namespace app\modules\api\controllers;

use app\models\DailyReport;
use app\models\User;
use app\services\DailyExportService;
use app\services\DailyReportService;
use app\services\TelegramNotificationService;
use Yii;
use yii\web\Response;

class DailyController extends BaseApiController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
        ];
        return $behaviors;
    }

    protected function verbs(): array
    {
        return [
            'locations' => ['GET'],
            'reports' => ['GET'],
            'update' => ['POST'],
            'export' => ['GET'],
            'users' => ['GET'],
        ];
    }

    private function getService(): DailyReportService
    {
        return Yii::$container->get(DailyReportService::class);
    }

    private function requireDailyAccess(): void
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            Yii::$app->response->statusCode = 401;
            throw new \yii\web\UnauthorizedHttpException('Не авторизован.');
        }
        if (!$user->isAdmin() && !in_array($user->position, ['manager', 'location_manager', 'senior_teamaker'], true)) {
            Yii::$app->response->statusCode = 403;
            throw new \yii\web\ForbiddenHttpException('Нет доступа к Дейли.');
        }
    }

    /**
     * GET /api/v1/daily/locations
     */
    public function actionLocations(): array
    {
        $this->requireDailyAccess();
        $service = $this->getService();
        $locations = $service->getAccessibleLocations();
        $list = [];
        foreach ($locations as $loc) {
            $list[] = ['id' => (int) $loc->id, 'name' => $loc->name];
        }
        return $this->success(['locations' => $list]);
    }

    /**
     * GET /api/v1/daily/users — для выпадающего списка «Менеджер смены».
     */
    public function actionUsers(): array
    {
        $this->requireDailyAccess();
        $users = User::find()
            ->where(['is_active' => 1])
            ->andWhere(['position' => ['location_manager', 'senior_teamaker', 'manager']])
            ->orderBy(['last_name' => SORT_ASC, 'first_name' => SORT_ASC])
            ->all();
        $list = [];
        foreach ($users as $u) {
            $list[] = ['id' => (int) $u->id, 'fullName' => $u->getFullName()];
        }
        return $this->success(['users' => $list]);
    }

    /**
     * GET /api/v1/daily/reports?location_id=&month=Y-m
     */
    public function actionReports(): array
    {
        $this->requireDailyAccess();
        $service = $this->getService();
        $locationId = (int) (Yii::$app->request->get('location_id') ?? 0);
        $month = (string) (Yii::$app->request->get('month') ?? date('Y-m'));
        if (!$locationId || !$service->canAccessLocation($locationId)) {
            return $this->success(['reports' => [], 'canEditPlan' => $service->canEditPlan()]);
        }
        $service->ensureMonthRows($locationId, $month);
        $from = $month . '-01';
        $to = date('Y-m-t', strtotime($from));
        $reports = $service->getReportsQuery($locationId, $from, $to)->all();
        foreach ($reports as $r) {
            $service->recalculate($r);
            $r->save(false);
        }
        $list = [];
        foreach ($reports as $r) {
            $list[] = [
                'id' => (int) $r->id,
                'reportDate' => $r->report_date,
                'dayOfWeek' => $r->getDayOfWeekShort(),
                'planDaily' => $r->plan_daily !== null ? (float) $r->plan_daily : null,
                'bar' => $r->bar !== null ? (float) $r->bar : null,
                'delivery' => $r->delivery !== null ? (float) $r->delivery : null,
                'selfPickup' => $r->self_pickup !== null ? (float) $r->self_pickup : null,
                'bonuses' => $r->bonuses !== null ? (float) $r->bonuses : null,
                'checksBar' => $r->checks_bar !== null ? (int) $r->checks_bar : null,
                'checksDelivery' => $r->checks_delivery !== null ? (int) $r->checks_delivery : null,
                'checksSelfPickup' => $r->checks_self_pickup !== null ? (int) $r->checks_self_pickup : null,
                'toRevenue' => $r->to_revenue !== null ? (float) $r->to_revenue : null,
                'deltaPlan' => $r->delta_plan !== null ? (float) $r->delta_plan : null,
                'ordersCount' => $r->orders_count !== null ? (int) $r->orders_count : null,
                'avgCheckBar' => $r->avg_check_bar !== null ? (float) $r->avg_check_bar : null,
                'avgCheckDelivery' => $r->avg_check_delivery !== null ? (float) $r->avg_check_delivery : null,
                'avgCheckSelfPickup' => $r->avg_check_self_pickup !== null ? (float) $r->avg_check_self_pickup : null,
                'workerHours' => $r->worker_hours !== null ? (float) $r->worker_hours : null,
                'productivityOrders' => $r->productivity_orders !== null ? (float) $r->productivity_orders : null,
                'productivityMoney' => $r->productivity_money !== null ? (float) $r->productivity_money : null,
                'managerId' => $r->manager_id ? (int) $r->manager_id : null,
                'managerName' => $r->manager ? $r->manager->getFullName() : null,
            ];
        }
        return $this->success([
            'reports' => $list,
            'canEditPlan' => $service->canEditPlan(),
        ]);
    }

    /**
     * POST /api/v1/daily/update — сохранить одну строку Дейли.
     */
    public function actionUpdate(): array
    {
        $this->requireDailyAccess();
        $body = Yii::$app->request->getBodyParams();
        if (empty($body) && Yii::$app->request->getRawBody()) {
            $decoded = json_decode(Yii::$app->request->getRawBody(), true);
            $body = is_array($decoded) ? $decoded : [];
        }
        $id = (int) ($body['id'] ?? 0);
        if ($id <= 0) {
            return $this->error('Не указан ID.', [], 422);
        }
        $service = $this->getService();
        $model = DailyReport::findOne($id);
        if (!$model) {
            return $this->error('Запись не найдена.', [], 404);
        }
        if (!$service->canAccessLocation($model->location_id)) {
            return $this->error('Нет доступа.', [], 403);
        }
        $oldAttributes = $model->getAttributes();
        $canEditPlan = $service->canEditPlan();
        $editableByShift = ['bar', 'delivery', 'self_pickup', 'bonuses', 'checks_bar', 'checks_delivery', 'checks_self_pickup', 'manager_id'];
        $editableAttrs = $editableByShift;
        if ($canEditPlan) {
            $editableAttrs[] = 'plan_daily';
        }
        $floatAttrs = ['plan_daily', 'bar', 'delivery', 'self_pickup', 'bonuses'];
        $intAttrs = ['checks_bar', 'checks_delivery', 'checks_self_pickup'];
        // Ключи в теле запроса от фронта — camelCase; атрибуты модели — snake_case
        $bodyKeyByAttr = [
            'plan_daily' => 'planDaily',
            'checks_bar' => 'checksBar',
            'checks_delivery' => 'checksDelivery',
            'checks_self_pickup' => 'checksSelfPickup',
            'manager_id' => 'managerId',
            'self_pickup' => 'selfPickup',
        ];
        foreach ($editableAttrs as $attr) {
            $bodyKey = $bodyKeyByAttr[$attr] ?? $attr;
            $val = $body[$bodyKey] ?? $body[$attr] ?? null;
            if ($attr === 'manager_id') {
                $model->$attr = ($val !== '' && $val !== null && $val !== 0 && $val !== '0') ? (int) $val : null;
            } elseif (in_array($attr, $floatAttrs, true)) {
                $model->$attr = $val !== '' && $val !== null ? (float) str_replace(',', '.', $val) : null;
            } elseif (in_array($attr, $intAttrs, true)) {
                $model->$attr = $val !== '' && $val !== null ? (int) $val : null;
            }
        }
        $service->recalculate($model);
        if (!$model->save()) {
            return $this->error(implode(', ', $model->getFirstErrors()), $model->errors, 422);
        }
        (new TelegramNotificationService())->onDailyReportUpdated($model, $oldAttributes);
        return $this->success([
            'planDaily' => $model->plan_daily !== null ? (float) $model->plan_daily : null,
            'bar' => $model->bar !== null ? (float) $model->bar : null,
            'delivery' => $model->delivery !== null ? (float) $model->delivery : null,
            'selfPickup' => $model->self_pickup !== null ? (float) $model->self_pickup : null,
            'bonuses' => $model->bonuses !== null ? (float) $model->bonuses : null,
            'checksBar' => $model->checks_bar !== null ? (int) $model->checks_bar : null,
            'checksDelivery' => $model->checks_delivery !== null ? (int) $model->checks_delivery : null,
            'checksSelfPickup' => $model->checks_self_pickup !== null ? (int) $model->checks_self_pickup : null,
            'managerId' => $model->manager_id ? (int) $model->manager_id : null,
            'toRevenue' => $model->to_revenue,
            'deltaPlan' => $model->delta_plan,
            'ordersCount' => $model->orders_count,
            'avgCheckBar' => $model->avg_check_bar,
            'avgCheckDelivery' => $model->avg_check_delivery,
            'avgCheckSelfPickup' => $model->avg_check_self_pickup,
            'workerHours' => $model->worker_hours,
            'productivityOrders' => $model->productivity_orders,
            'productivityMoney' => $model->productivity_money,
        ]);
    }

    /**
     * GET /api/v1/daily/export?location_id=&month= — скачать XLSX.
     */
    public function actionExport()
    {
        $this->requireDailyAccess();
        $service = $this->getService();
        $locationId = (int) (Yii::$app->request->get('location_id') ?? 0);
        $month = (string) (Yii::$app->request->get('month') ?? date('Y-m'));
        if (!$service->canAccessLocation($locationId)) {
            throw new \yii\web\ForbiddenHttpException('Нет доступа к этой точке.');
        }
        $location = \app\models\Location::findOne($locationId);
        if (!$location) {
            throw new \yii\web\NotFoundHttpException('Точка не найдена.');
        }
        $from = $month . '-01';
        $to = date('Y-m-t', strtotime($from));
        $reports = $service->getReportsQuery($locationId, $from, $to)->all();
        foreach ($reports as $r) {
            $service->recalculate($r);
        }
        try {
            /** @var DailyExportService $exporter */
            $exporter = Yii::$container->get(DailyExportService::class);
            $response = $exporter->export($reports, $location->name, $month);
            Yii::$app->response->format = $response->format;
            Yii::$app->response->headers->removeAll();
            foreach ($response->headers as $name => $values) {
                foreach ((array) $values as $value) {
                    Yii::$app->response->headers->add($name, $value);
                }
            }
            Yii::$app->response->stream = $response->stream;
            Yii::$app->response->send();
            exit(0);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'PhpSpreadsheet') !== false || strpos($msg, 'Spreadsheet') !== false) {
                $msg = 'Библиотека PhpSpreadsheet не установлена. Выполните в корне проекта: composer install';
            }
            return $this->error($msg, [], 503);
        }
    }
}
