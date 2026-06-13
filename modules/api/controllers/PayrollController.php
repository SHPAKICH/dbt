<?php

namespace app\modules\api\controllers;

use app\models\KroBonusBand;
use app\models\LocationKro;
use app\models\PositionRate;
use app\services\PayrollService;
use Yii;
use yii\web\Response;

/**
 * API зарплаты: локации, КРО, расчёт, мои расчёты по дням.
 *
 * GET  /api/v1/payroll/locations
 * GET  /api/v1/payroll/kro?location_id=&year=&month=
 * POST /api/v1/payroll/kro — body: { location_id, year, month, pass_percent }
 * GET  /api/v1/payroll/calculations?from=&to=&location_id=
 * GET  /api/v1/payroll/my-daily?from=&to=
 */
class PayrollController extends BaseApiController
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
            'kro' => ['GET', 'POST'],
            'calculations' => ['GET'],
            'my-daily' => ['GET'],
            'formula' => ['GET'],
        ];
    }

    private function getService(): PayrollService
    {
        return Yii::$container->get(PayrollService::class);
    }

    private function requirePayrollAccess(): void
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            throw new \yii\web\UnauthorizedHttpException('Не авторизован.');
        }
    }

    private function canManageKro(): bool
    {
        $user = Yii::$app->user->identity;
        return $user && ($user->isAdmin() || $user->position === 'manager' || $user->position === 'location_manager');
    }

    private function canAccessLocationForKro(int $locationId): bool
    {
        $user = Yii::$app->user->identity;
        if (!$user || $user->isAdmin()) {
            return true;
        }
        if ($user->position === 'manager') {
            $ids = (new \yii\db\Query())->from('manager_locations')->where(['manager_id' => $user->id])->select('location_id')->column();
            return in_array($locationId, $ids, false);
        }
        if ($user->position === 'location_manager' && $user->location_id) {
            return (int) $user->location_id === $locationId;
        }
        return false;
    }

    /**
     * GET /api/v1/payroll/locations
     */
    public function actionLocations(): array
    {
        $this->requirePayrollAccess();
        $service = $this->getService();
        $locations = $service->getPayrollLocations();
        $list = [];
        foreach ($locations as $loc) {
            $list[] = ['id' => (int) $loc->id, 'name' => $loc->name];
        }
        return $this->success(['locations' => $list]);
    }

    /**
     * GET /api/v1/payroll/kro?location_id=&year=&month=
     * POST /api/v1/payroll/kro — { location_id, year, month, pass_percent }
     */
    public function actionKro(): array
    {
        $this->requirePayrollAccess();
        if (Yii::$app->request->isGet) {
            $locationId = (int) (Yii::$app->request->get('location_id') ?? 0);
            $year = (int) (Yii::$app->request->get('year') ?? date('Y'));
            $month = (int) (Yii::$app->request->get('month') ?? date('n'));
            if (!$this->canManageKro() || !$this->canAccessLocationForKro($locationId)) {
                return $this->error('Нет доступа.', [], 403);
            }
            $kro = LocationKro::getForLocationMonth($locationId, $year, $month);
            return $this->success([
                'location_id' => $locationId,
                'year' => $year,
                'month' => $month,
                'pass_percent' => $kro ? (float) $kro->pass_percent : null,
            ]);
        }
        $body = Yii::$app->request->getBodyParams();
        if (empty($body) && Yii::$app->request->getRawBody()) {
            $decoded = json_decode(Yii::$app->request->getRawBody(), true);
            $body = is_array($decoded) ? $decoded : [];
        }
        if (!$this->canManageKro()) {
            return $this->error('Только аудитор/менеджер может вносить КРО.', [], 403);
        }
        $locationId = (int) ($body['location_id'] ?? 0);
        $year = (int) ($body['year'] ?? date('Y'));
        $month = (int) ($body['month'] ?? date('n'));
        $passPercent = isset($body['pass_percent']) ? (float) str_replace(',', '.', $body['pass_percent']) : null;
        if (!$locationId || !$this->canAccessLocationForKro($locationId)) {
            return $this->error('Нет доступа к точке.', [], 403);
        }
        if ($passPercent === null || $passPercent < 0 || $passPercent > 100) {
            return $this->error('Укажите процент прохождения от 0 до 100.', [], 422);
        }
        $kro = LocationKro::getForLocationMonth($locationId, $year, $month);
        if (!$kro) {
            $kro = new LocationKro();
            $kro->location_id = $locationId;
            $kro->year = $year;
            $kro->month = $month;
        }
        $kro->pass_percent = $passPercent;
        if (!$kro->save()) {
            return $this->error(implode(', ', $kro->getFirstErrors()), $kro->errors, 422);
        }
        return $this->success([
            'id' => $kro->id,
            'location_id' => $kro->location_id,
            'year' => $kro->year,
            'month' => $kro->month,
            'pass_percent' => (float) $kro->pass_percent,
        ]);
    }

    /**
     * GET /api/v1/payroll/calculations?from=&to=&location_id=
     */
    public function actionCalculations(): array
    {
        $this->requirePayrollAccess();
        $user = Yii::$app->user->identity;
        $from = (string) (Yii::$app->request->get('from') ?? date('Y-m-01'));
        $to = (string) (Yii::$app->request->get('to') ?? date('Y-m-t'));
        $locationId = Yii::$app->request->get('location_id');
        $locationId = $locationId !== null && $locationId !== '' ? (int) $locationId : null;

        $service = $this->getService();
        $isEmployee = $user && in_array($user->position, ['trainee', 'teamaker', 'senior_teamaker'], true);
        if ($isEmployee) {
            $result = $service->calculate($from, $to, null, (int) $user->id);
        } else {
            if ($locationId) {
                $locations = $service->getPayrollLocations();
                $allowed = false;
                foreach ($locations as $loc) {
                    if ((int) $loc->id === $locationId) {
                        $allowed = true;
                        break;
                    }
                }
                if (!$allowed) {
                    return $this->error('Нет доступа к этой точке.', [], 403);
                }
            }
            $result = $service->calculate($from, $to, $locationId, null);
        }
        return $this->success($result);
    }

    /**
     * GET /api/v1/payroll/formula — параметры и шкала для отображения формулы расчёта.
     */
    public function actionFormula(): array
    {
        $this->requirePayrollAccess();
        $rates = PositionRate::find()->orderBy(['name' => SORT_ASC])->all();
        $rateList = [];
        foreach ($rates as $rate) {
            $rateList[] = [
                'code' => $rate->code,
                'name' => $rate->name,
                'hourly_rate' => (float) $rate->hourly_rate,
            ];
        }

        return $this->success([
            'positionRates' => $rateList,
            'kroBands' => KroBonusBand::getBands(),
            'revenuePerPersonThreshold' => PayrollService::REVENUE_PER_PERSON_THRESHOLD,
            'bonusShare' => PayrollService::BONUS_SHARE,
        ]);
    }

    /**
     * GET /api/v1/payroll/my-daily?from=&to=
     */
    public function actionMyDaily(): array
    {
        $this->requirePayrollAccess();
        $from = (string) (Yii::$app->request->get('from') ?? date('Y-m-01'));
        $to = (string) (Yii::$app->request->get('to') ?? date('Y-m-t'));
        $service = $this->getService();
        $result = $service->getMyDailyBreakdown($from, $to);
        return $this->success($result);
    }
}
