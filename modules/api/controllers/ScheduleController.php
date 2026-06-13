<?php

namespace app\modules\api\controllers;

use app\models\Location;
use app\models\ShiftInvite;
use app\services\ScheduleService;
use Yii;

class ScheduleController extends BaseApiController
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
            'grid' => ['GET'],
            'update-cell' => ['POST'],
            'helpers' => ['GET'],
            'send-invite' => ['POST'],
            'my-invites' => ['GET'],
            'accept-invite' => ['POST'],
            'decline-invite' => ['POST'],
        ];
    }

    /**
     * GET /api/v1/schedule/locations — список точек для графика.
     */
    public function actionLocations(): array
    {
        $user = Yii::$app->user->identity;
        $query = Location::find()->where(['is_active' => 1]);

        if ($user->position === 'manager' && !$user->isAdmin()) {
            $ids = (new \yii\db\Query())
                ->from('manager_locations')
                ->where(['manager_id' => $user->id])
                ->select('location_id')
                ->column();
            if ($ids) {
                $query->andWhere(['id' => $ids]);
            } else {
                $query->andWhere('0=1');
            }
        } elseif ($user->position === 'location_manager' && $user->location_id && !$user->isAdmin()) {
            $query->andWhere(['id' => $user->location_id]);
        }

        $locations = $query->orderBy(['name' => SORT_ASC])->all();
        $list = [];
        $locationIds = array_column($locations, 'id');
        $defaultLocationId = null;
        if ($user->location_id && in_array((int)$user->location_id, array_map('intval', $locationIds), true)) {
            $defaultLocationId = (int)$user->location_id;
        }
        foreach ($locations as $loc) {
            $list[] = ['id' => (int)$loc->id, 'name' => $loc->name, 'color' => $loc->color ?? '#2b2b2b'];
        }
        $canEditSchedule = $user->isAdmin() || $user->position === 'manager' || $user->position === 'location_manager';
        return $this->success([
            'locations' => $list,
            'defaultLocationId' => $defaultLocationId,
            'canEditSchedule' => $canEditSchedule,
        ]);
    }

    /**
     * GET /api/v1/schedule/grid?location_id=1&period=week|month&date=2025-03-05
     */
    public function actionGrid(): array
    {
        $locationId = (int)Yii::$app->request->get('location_id');
        $period = Yii::$app->request->get('period', 'week');
        $date = Yii::$app->request->get('date') ?: date('Y-m-d');

        if ($period === 'month') {
            $from = date('Y-m-01', strtotime($date));
            $to = date('Y-m-t', strtotime($date));
        } else {
            $ts = strtotime($date);
            $w = (int)date('N', $ts);
            $from = date('Y-m-d', strtotime('-' . ($w - 1) . ' days', $ts));
            $to = date('Y-m-d', strtotime('+' . (7 - $w) . ' days', $ts));
        }

        /** @var ScheduleService $service */
        $service = Yii::$container->get(ScheduleService::class);
        $grid = $service->getScheduleGrid($locationId, $from, $to);

        $cardByUserId = $grid['cardCodeByUserId'] ?? [];
        $users = [];
        foreach ($grid['users'] as $userId => $user) {
            $card = $cardByUserId[$userId] ?? null;
            $users[$userId] = [
                'id' => (int)$user->id,
                'fullName' => $user->getFullName(),
                'positionLabel' => $user->getPositionLabel(),
                'cardCode' => $card['code'] ?? null,
                'cardCssClass' => $card['cssClass'] ?? null,
                'cardStyleConfig' => $card['styleConfig'] ?? null,
            ];
        }
        $usersReinforcement = [];
        foreach ($grid['usersReinforcement'] ?? [] as $userId => $user) {
            $card = $cardByUserId[$userId] ?? null;
            $usersReinforcement[$userId] = [
                'id' => (int)$user->id,
                'fullName' => $user->getFullName(),
                'positionLabel' => $user->getPositionLabel(),
                'cardCode' => $card['code'] ?? null,
                'cardCssClass' => $card['cssClass'] ?? null,
                'cardStyleConfig' => $card['styleConfig'] ?? null,
            ];
        }

        $shifts = [];
        foreach ($grid['shifts'] as $userId => $byDate) {
            $uid = (string)$userId;
            $shifts[$uid] = [];
            foreach ($byDate as $d => $shift) {
                $cellText = $this->formatCellText($shift);
                $timeStart = $shift->time_start ? trim((string)$shift->time_start) : null;
                $timeEnd = $shift->time_end ? trim((string)$shift->time_end) : null;
                $displayText = $cellText;
                if ($cellText === '0' && $timeStart && $timeEnd) {
                    $displayText = substr($timeStart, 0, 5) . '–' . substr($timeEnd, 0, 5);
                }
                $shifts[$uid][$d] = [
                    'timeStart' => $timeStart,
                    'timeEnd' => $timeEnd,
                    'hours' => (float)$shift->hours,
                    'isDayOff' => (bool)$shift->is_day_off,
                    'isNight' => (bool)$shift->is_night,
                    'isOvertime' => (bool)$shift->is_overtime,
                    'cellText' => $cellText,
                    'displayText' => $displayText,
                ];
            }
        }

        $availability = [];
        foreach ($grid['availability'] as $userId => $byDate) {
            $availability[$userId] = [];
            foreach ($byDate as $d => $av) {
                if ($av && $av->time_start && $av->time_end) {
                    $availability[$userId][$d] = $av->time_start . '-' . $av->time_end;
                } else {
                    $availability[$userId][$d] = null;
                }
            }
        }

        // Порядок строк в таблице: JSON объекты с числовыми ключами в JS перебираются по возрастанию id,
        // поэтому явно отдаём массив id в нужном порядке с сервера.
        $userOrder = array_map('intval', array_keys($users));
        $usersReinforcementOrder = array_map('intval', array_keys($usersReinforcement));

        return $this->success([
            'location' => [
                'id' => (int)$grid['location']->id,
                'name' => $grid['location']->name,
            ],
            'dates' => $grid['dates'],
            'from' => $from,
            'to' => $to,
            'users' => $users,
            'userOrder' => $userOrder,
            'usersReinforcement' => $usersReinforcement,
            'usersReinforcementOrder' => $usersReinforcementOrder,
            'shifts' => $shifts,
            'availability' => $availability,
            'totalsByUser' => $grid['totalsByUser'],
            'totalsByDate' => $grid['totalsByDate'],
        ]);
    }

    /**
     * POST /api/v1/schedule/update-cell
     * Body: { "userId", "locationId", "date", "value" } — value: "10-22" или "0" для выходного.
     */
    public function actionUpdateCell(): array
    {
        $body = Yii::$app->request->getBodyParams();
        if (empty($body)) {
            $raw = Yii::$app->request->getRawBody();
            $body = is_array($decoded = json_decode($raw, true)) ? $decoded : [];
        }
        $userId = (int)($body['userId'] ?? $body['user_id'] ?? 0);
        $locationId = (int)($body['locationId'] ?? $body['location_id'] ?? 0);
        $date = (string)($body['date'] ?? '');
        $value = isset($body['value']) ? (string)$body['value'] : null;

        if (!$userId || !$locationId || !$date) {
            return $this->error('Укажите userId, locationId и date', [], 422);
        }

        /** @var ScheduleService $service */
        $service = Yii::$container->get(ScheduleService::class);
        try {
            $shift = $service->updateCell($userId, $locationId, $date, $value === '' ? null : $value);
            $cellText = $this->formatCellText($shift);
            return $this->success([
                'cellText' => $cellText,
                'hours' => (float)$shift->hours,
            ]);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), [], 400);
        }
    }

    /**
     * POST /api/v1/schedule/send-invite
     * Body: { "userId", "locationId", "date", "value" } — отправить инвайт на смену (усиление).
     */
    public function actionSendInvite(): array
    {
        $body = Yii::$app->request->getBodyParams();
        if (empty($body)) {
            $raw = Yii::$app->request->getRawBody();
            $body = is_array($decoded = json_decode($raw, true)) ? $decoded : [];
        }
        $userId = (int)($body['userId'] ?? $body['user_id'] ?? 0);
        $locationId = (int)($body['locationId'] ?? $body['location_id'] ?? 0);
        $date = (string)($body['date'] ?? '');
        $value = isset($body['value']) ? (string)$body['value'] : '10-22';

        if (!$userId || !$locationId || !$date) {
            return $this->error('Укажите userId, locationId и date', [], 422);
        }

        /** @var ScheduleService $service */
        $service = Yii::$container->get(ScheduleService::class);
        try {
            $invite = $service->createInvite($userId, $locationId, $date, $value);
            return $this->success([
                'id' => (int)$invite->id,
                'message' => 'Инвайт отправлен. Сотрудник увидит его во вкладке «Уведомления».',
            ]);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), [], 400);
        }
    }

    /**
     * GET /api/v1/schedule/my-invites — мои инвайты на смены (pending) для вкладки Уведомления.
     */
    public function actionMyInvites(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            return $this->error('Требуется авторизация', [], 401);
        }
        $invites = ShiftInvite::find()
            ->where(['user_id' => $user->id, 'status' => ShiftInvite::STATUS_PENDING])
            ->with(['location', 'invitedByUser'])
            ->orderBy(['date' => SORT_ASC, 'created_at' => SORT_DESC])
            ->all();
        $items = [];
        foreach ($invites as $inv) {
            $items[] = [
                'id' => (int)$inv->id,
                'locationId' => (int)$inv->location_id,
                'locationName' => $inv->location ? $inv->location->name : '',
                'date' => $inv->date,
                'timeStart' => $inv->time_start ? substr($inv->time_start, 0, 5) : null,
                'timeEnd' => $inv->time_end ? substr($inv->time_end, 0, 5) : null,
                'invitedBy' => $inv->invitedByUser ? $inv->invitedByUser->getFullName() : '',
                'createdAt' => $inv->created_at,
            ];
        }
        return $this->success(['items' => $items]);
    }

    /**
     * POST /api/v1/schedule/accept-invite — принять инвайт (body: { "id": inviteId }).
     */
    public function actionAcceptInvite(): array
    {
        $body = Yii::$app->request->getBodyParams();
        if (empty($body)) {
            $raw = Yii::$app->request->getRawBody();
            $body = is_array($decoded = json_decode($raw, true)) ? $decoded : [];
        }
        $id = (int)($body['id'] ?? $body['inviteId'] ?? 0);
        if (!$id) {
            return $this->error('Укажите id инвайта', [], 422);
        }
        /** @var ScheduleService $service */
        $service = Yii::$container->get(ScheduleService::class);
        try {
            $shift = $service->acceptInvite($id);
            return $this->success([
                'message' => 'Смена добавлена в график.',
                'cellText' => $this->formatCellText($shift),
            ]);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), [], 400);
        }
    }

    /**
     * POST /api/v1/schedule/decline-invite — отказаться от инвайта (body: { "id": inviteId }).
     */
    public function actionDeclineInvite(): array
    {
        $body = Yii::$app->request->getBodyParams();
        if (empty($body)) {
            $raw = Yii::$app->request->getRawBody();
            $body = is_array($decoded = json_decode($raw, true)) ? $decoded : [];
        }
        $id = (int)($body['id'] ?? $body['inviteId'] ?? 0);
        if (!$id) {
            return $this->error('Укажите id инвайта', [], 422);
        }
        /** @var ScheduleService $service */
        $service = Yii::$container->get(ScheduleService::class);
        try {
            $service->declineInvite($id);
            return $this->success(['message' => 'Инвайт отклонён.']);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), [], 400);
        }
    }

    /**
     * GET /api/v1/schedule/helpers?location_id=1&date=2026-03-05
     * Кандидаты для усиления на точке в выбранный день.
     */
    public function actionHelpers(): array
    {
        $locationId = (int)Yii::$app->request->get('location_id');
        $date = (string)Yii::$app->request->get('date', '');

        if (!$locationId || !$date) {
            return $this->error('Укажите location_id и date.', [], 422);
        }

        /** @var ScheduleService $service */
        $service = Yii::$container->get(ScheduleService::class);
        try {
            $items = $service->findHelpers($locationId, $date);
            return $this->success(['items' => $items]);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), [], 400);
        }
    }

    private function formatCellText($shift): string
    {
        if ($shift->is_day_off || !$shift->time_start || !$shift->time_end) {
            return '0';
        }
        $s = substr($shift->time_start, 0, 5);
        $e = substr($shift->time_end, 0, 5);
        return $s . '–' . $e;
    }
}
