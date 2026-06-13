<?php

namespace app\modules\api\controllers;

use app\models\Location;
use app\models\User;
use app\models\UserAvailabilityByDate;
use Yii;

class AvailabilityController extends BaseApiController
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
            'my' => ['GET'],
            'update-cell' => ['POST'],
            'team' => ['GET'],
        ];
    }

    /**
     * GET /api/v1/availability/my?month=3&year=2025 — моя карта по месяцу.
     */
    public function actionMy(): array
    {
        $user = Yii::$app->user->identity;
        $month = (int)Yii::$app->request->get('month') ?: (int)date('n');
        $year = (int)Yii::$app->request->get('year') ?: (int)date('Y');

        $firstDay = sprintf('%04d-%02d-01', $year, $month);
        $lastDay = (int)date('t', strtotime($firstDay));
        $dates = [];
        for ($d = 1; $d <= $lastDay; $d++) {
            $dates[] = sprintf('%04d-%02d-%02d', $year, $month, $d);
        }

        $records = UserAvailabilityByDate::find()
            ->where(['user_id' => $user->id])
            ->andWhere(['date' => $dates])
            ->indexBy('date')
            ->all();

        $byDate = [];
        foreach ($dates as $date) {
            $r = $records[$date] ?? null;
            if ($r && $r->time_start && $r->time_end) {
                $sH = (int)substr($r->time_start, 0, 2);
                $sM = substr($r->time_start, 3, 2);
                $eH = (int)substr($r->time_end, 0, 2);
                $eM = substr($r->time_end, 3, 2);
                $sText = $sH . ($sM === '30' ? '.5' : '');
                $eText = $eH . ($eM === '30' ? '.5' : '');
                $byDate[$date] = [
                    'timeStart' => $r->time_start,
                    'timeEnd' => $r->time_end,
                    'text' => $sText . '-' . $eText,
                ];
            } else {
                $byDate[$date] = null;
            }
        }

        $weekdayNames = [1 => 'Пн', 2 => 'Вт', 3 => 'Ср', 4 => 'Чт', 5 => 'Пт', 6 => 'Сб', 7 => 'Вс'];
        $daysOfMonth = [];
        for ($d = 1; $d <= $lastDay; $d++) {
            $daysOfMonth[$d] = (int)date('N', strtotime(sprintf('%04d-%02d-%02d', $year, $month, $d)));
        }

        return $this->success([
            'year' => $year,
            'month' => $month,
            'lastDay' => $lastDay,
            'dates' => $dates,
            'byDate' => $byDate,
            'daysOfMonth' => $daysOfMonth,
            'weekdayNames' => $weekdayNames,
        ]);
    }

    /**
     * POST /api/v1/availability/update-cell — обновить ячейку (дата, значение "9-21" или "").
     */
    public function actionUpdateCell(): array
    {
        $user = Yii::$app->user->identity;
        $body = Yii::$app->request->getBodyParams();
        if (empty($body)) {
            $raw = Yii::$app->request->getRawBody();
            $body = is_array($decoded = json_decode($raw, true)) ? $decoded : [];
        }
        $date = trim((string)($body['date'] ?? ''));
        $value = trim((string)($body['value'] ?? $body['text'] ?? ''));

        if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $this->error('Некорректная дата.', [], 422);
        }

        $record = UserAvailabilityByDate::findOne(['user_id' => $user->id, 'date' => $date]);

        if ($value === '' || $value === '0') {
            if ($record) {
                $record->delete();
            }
            return $this->success(['cell' => ['text' => '']]);
        }

        if (!preg_match('/^([01]?\d|2[0-3])(\.5)?-([01]?\d|2[0-3])(\.5)?$/', $value, $matches)) {
            return $this->error('Неверный формат. Используйте, например, "9-21" или "9.5-21.5" или "0".', [], 422);
        }

        $start = (int)$matches[1] + (!empty($matches[2]) ? 0.5 : 0.0);
        $end = (int)$matches[3] + (!empty($matches[4]) ? 0.5 : 0.0);
        if ($start >= $end) {
            return $this->error('Время начала должно быть меньше времени окончания.', [], 422);
        }

        $startHourInt = (int)floor($start);
        $startMinutes = ($start - $startHourInt) >= 0.5 ? 30 : 0;
        $endHourInt = (int)floor($end);
        $endMinutes = ($end - $endHourInt) >= 0.5 ? 30 : 0;

        if (!$record) {
            $record = new UserAvailabilityByDate();
            $record->user_id = $user->id;
            $record->date = $date;
        }
        $record->time_start = sprintf('%02d:%02d', $startHourInt, $startMinutes);
        $record->time_end = sprintf('%02d:%02d', $endHourInt, $endMinutes);
        if (!$record->save()) {
            return $this->error('Не удалось сохранить.', [], 500);
        }

        $startText = $startHourInt . ($startMinutes === 30 ? '.5' : '');
        $endText = $endHourInt . ($endMinutes === 30 ? '.5' : '');
        return $this->success(['cell' => ['text' => $startText . '-' . $endText]]);
    }

    /**
     * GET /api/v1/availability/team?month=&year=&location_id= — командный вид (для менеджеров).
     */
    public function actionTeam(): array
    {
        $user = Yii::$app->user->identity;
        $month = (int)Yii::$app->request->get('month') ?: (int)date('n');
        $year = (int)Yii::$app->request->get('year') ?: (int)date('Y');
        $queryParams = Yii::$app->request->getQueryParams();
        $hasLocationParam = array_key_exists('location_id', $queryParams);
        if ($hasLocationParam) {
            $rawLoc = $queryParams['location_id'];
            $locationId = ($rawLoc !== '' && $rawLoc !== null) ? (int)$rawLoc : null;
        } else {
            $locationId = null;
        }

        $locations = [];
        if ($user->isAdmin()) {
            $locations = Location::find()->where(['is_active' => 1])->orderBy(['name' => SORT_ASC])->all();
            if ($locationId === null && !$hasLocationParam && $locations) {
                $locationId = (int)$locations[0]->id;
            }
        } elseif ($user->position === 'manager') {
            $ids = (new \yii\db\Query())->from('manager_locations')->where(['manager_id' => $user->id])->select('location_id')->column();
            $locations = Location::find()->where(['id' => $ids, 'is_active' => 1])->orderBy(['name' => SORT_ASC])->all();
            if ($locationId !== null && !in_array($locationId, array_map('intval', $ids), true)) {
                return $this->error('Нет доступа к выбранной точке.', [], 403);
            }
            if ($locationId === null && !$hasLocationParam && $locations) {
                $locationId = (int)$locations[0]->id;
            }
        } else {
            $loc = Location::findOne($user->location_id);
            $locations = $loc ? [$loc] : [];
            $locationId = (int)$user->location_id;
        }

        if ($locationId !== null && !$user->isAdmin() && $user->position !== 'manager' && (int)$user->location_id !== $locationId) {
            return $this->error('Нет доступа к выбранной точке.', [], 403);
        }

        $firstDay = sprintf('%04d-%02d-01', $year, $month);
        $lastDay = (int)date('t', strtotime($firstDay));
        $dateList = [];
        $daysOfMonth = [];
        for ($d = 1; $d <= $lastDay; $d++) {
            $dateList[] = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $daysOfMonth[$d] = (int)date('N', strtotime(sprintf('%04d-%02d-%02d', $year, $month, $d)));
        }

        $locIds = $locationId ? [$locationId] : array_map(function ($l) { return $l->id; }, $locations);
        $sections = [];
        foreach ($locIds as $locId) {
            $location = Location::findOne($locId);
            if (!$location) continue;
            $employees = User::find()
                ->where(['location_id' => $locId, 'is_active' => 1])
                ->orderBy(['last_name' => SORT_ASC, 'first_name' => SORT_ASC])
                ->all();
            $userIds = array_map(function ($u) { return $u->id; }, $employees);
            $rows = UserAvailabilityByDate::find()
                ->where(['user_id' => $userIds])
                ->andWhere(['date' => $dateList])
                ->all();
            $byUserByDate = [];
            foreach ($rows as $r) {
                $byUserByDate[(string)$r->user_id][$r->date] = $r->time_start && $r->time_end
                    ? substr($r->time_start, 0, 5) . '–' . substr($r->time_end, 0, 5)
                    : '';
            }
            $sections[] = [
                'location' => ['id' => (int)$location->id, 'name' => $location->name],
                'employees' => array_map(function ($u) {
                    return [
                        'id' => (int)$u->id,
                        'fullName' => $u->getFullName(),
                        'positionLabel' => $u->getPositionLabel(),
                    ];
                }, $employees),
                'byUserByDate' => $byUserByDate,
            ];
        }

        $locList = array_map(function ($l) {
            return ['id' => (int)$l->id, 'name' => $l->name];
        }, $locations);

        $weekdayNames = [1 => 'Пн', 2 => 'Вт', 3 => 'Ср', 4 => 'Чт', 5 => 'Пт', 6 => 'Сб', 7 => 'Вс'];

        return $this->success([
            'year' => $year,
            'month' => $month,
            'lastDay' => $lastDay,
            'dates' => $dateList,
            'daysOfMonth' => $daysOfMonth,
            'weekdayNames' => $weekdayNames,
            'locations' => $locList,
            'locationId' => $locationId,
            'allLocations' => $locationId === null && count($locIds) > 1,
            'sections' => $sections,
        ]);
    }
}
