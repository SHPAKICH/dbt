<?php

namespace app\controllers;

use app\models\DailyReport;
use app\models\Location;
use app\models\User;
use app\services\DailyExportService;
use app\services\DailyReportService;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

/**
 * Дейли — ежедневный отчёт по точке.
 * Роли: Админ — все точки; Управляющий — свои территории; Менеджер/Ст.тимейкер — своя точка.
 */
class DailyController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'update' => ['post'],
                ],
            ],
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => static function ($rule, $action) {
                            $user = Yii::$app->user->identity;
                            if (!$user) {
                                return false;
                            }
                            return $user->isAdmin()
                                || in_array($user->position, ['manager', 'location_manager', 'senior_teamaker'], true);
                        },
                    ],
                ],
            ],
        ];
    }

    /**
     * Таблица Дейли с возможностью заполнения.
     */
    public function actionIndex($location_id = null, $month = null)
    {
        /** @var DailyReportService $service */
        $service = Yii::$container->get(DailyReportService::class);

        $locations = $service->getAccessibleLocations();
        if (empty($locations)) {
            return $this->render('index', [
                'locations' => [],
                'locationId' => null,
                'location' => null,
                'reports' => [],
                'yearMonth' => date('Y-m'),
                'users' => [],
                'canEditPlan' => false,
            ]);
        }

        $yearMonth = $month ?? date('Y-m');
        $locationId = null;

        if ($location_id !== null) {
            $locationId = (int)$location_id;
            if (!$service->canAccessLocation($locationId)) {
                $locationId = (int)$locations[0]->id;
            }
        } else {
            $locationId = (int)$locations[0]->id;
        }

        $location = Location::findOne($locationId);
        if (!$location) {
            $locationId = (int)$locations[0]->id;
            $location = $locations[0];
        }

        $service->ensureMonthRows($locationId, $yearMonth);

        $from = $yearMonth . '-01';
        $to = date('Y-m-t', strtotime($from));

        $reports = $service->getReportsQuery($locationId, $from, $to)->all();

        foreach ($reports as $r) {
            $service->recalculate($r);
            $r->save(false);
        }

        $canEditPlan = $service->canEditPlan();

        $users = User::find()
            ->where(['is_active' => 1])
            ->andWhere(['position' => ['location_manager', 'senior_teamaker', 'manager']])
            ->orderBy(['last_name' => SORT_ASC, 'first_name' => SORT_ASC])
            ->all();

        return $this->render('index', [
            'locations' => $locations,
            'locationId' => $locationId,
            'location' => $location,
            'reports' => $reports,
            'yearMonth' => $yearMonth,
            'users' => $users,
            'canEditPlan' => $canEditPlan,
        ]);
    }

    /**
     * Сохранение строки Дейли (AJAX).
     */
    public function actionUpdate(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $id = (int)Yii::$app->request->post('id');
        if ($id <= 0) {
            return $this->asJson(['ok' => false, 'error' => 'Не указан ID']);
        }

        /** @var DailyReportService $service */
        $service = Yii::$container->get(DailyReportService::class);

        $model = DailyReport::findOne($id);
        if (!$model) {
            return $this->asJson(['ok' => false, 'error' => 'Запись не найдена']);
        }

        if (!$service->canAccessLocation($model->location_id)) {
            return $this->asJson(['ok' => false, 'error' => 'Нет доступа']);
        }

        $canEditPlan = $service->canEditPlan();

        $editableByShift = ['bar', 'delivery', 'self_pickup', 'bonuses', 'checks_bar', 'checks_delivery', 'checks_self_pickup', 'manager_id'];
        $editableAttrs = $editableByShift;
        if ($canEditPlan) {
            $editableAttrs[] = 'plan_daily';
        }

        $floatAttrs = ['plan_daily', 'bar', 'delivery', 'self_pickup', 'bonuses'];
        $intAttrs = ['checks_bar', 'checks_delivery', 'checks_self_pickup'];

        foreach ($editableAttrs as $attr) {
            $val = Yii::$app->request->post($attr);
            if ($attr === 'manager_id') {
                $model->$attr = $val !== '' && $val !== null ? (int)$val : null;
            } elseif (in_array($attr, $floatAttrs, true)) {
                $model->$attr = $val !== '' && $val !== null ? (float)str_replace(',', '.', $val) : null;
            } elseif (in_array($attr, $intAttrs, true)) {
                $model->$attr = $val !== '' && $val !== null ? (int)$val : null;
            }
        }

        $service->recalculate($model);

        if ($model->save()) {
            return $this->asJson([
                'ok' => true,
                'data' => [
                    'to_revenue' => $model->to_revenue,
                    'delta_plan' => $model->delta_plan,
                    'orders_count' => $model->orders_count,
                    'avg_check_bar' => $model->avg_check_bar,
                    'avg_check_delivery' => $model->avg_check_delivery,
                    'avg_check_self_pickup' => $model->avg_check_self_pickup,
                    'worker_hours' => $model->worker_hours,
                    'productivity_orders' => $model->productivity_orders,
                    'productivity_money' => $model->productivity_money,
                ],
            ]);
        }

        return $this->asJson(['ok' => false, 'error' => implode(', ', $model->getFirstErrors())]);
    }

    /**
     * Экспорт в XLSX для бухгалтерии.
     */
    public function actionExport($location_id, $month = null): Response
    {
        /** @var DailyReportService $service */
        $service = Yii::$container->get(DailyReportService::class);

        $locationId = (int)$location_id;
        if (!$service->canAccessLocation($locationId)) {
            throw new \yii\web\ForbiddenHttpException('Нет доступа к этой точке');
        }

        $location = Location::findOne($locationId);
        if (!$location) {
            throw new \yii\web\NotFoundHttpException('Точка не найдена');
        }

        $yearMonth = $month ?? date('Y-m');
        $from = $yearMonth . '-01';
        $to = date('Y-m-t', strtotime($from));

        $reports = $service->getReportsQuery($locationId, $from, $to)->all();
        foreach ($reports as $r) {
            $service->recalculate($r);
        }

        /** @var DailyExportService $exporter */
        $exporter = Yii::$container->get(DailyExportService::class);

        return $exporter->export($reports, $location->name, $yearMonth);
    }
}
