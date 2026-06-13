<?php

namespace app\services;

use app\models\Location;
use app\models\ScheduleShift;
use app\models\ShiftInvite;
use app\models\User;
use app\models\UserAvailabilityByDate;
use app\services\ProfileCardService;
use app\services\TelegramNotificationService;
use Yii;
use yii\base\Component;
use yii\base\Exception;

/**
 * Сервис работы с графиком смен.
 */
class ScheduleService extends Component
{
    private function assertCanAccessLocation(int $locationId, bool $requireEditor = false): void
    {
        $currentUser = Yii::$app->user->identity;
        if (!$currentUser) {
            throw new Exception('Требуется авторизация.');
        }

        if ($currentUser->isAdmin()) {
            return;
        }

        if ($currentUser->position === 'manager') {
            $allowedLocationIds = array_map('intval', (new \yii\db\Query())
                ->from('manager_locations')
                ->where(['manager_id' => $currentUser->id])
                ->select('location_id')
                ->column());

            if (!in_array($locationId, $allowedLocationIds, true)) {
                throw new Exception('Нет прав на доступ к этой точке.');
            }
            return;
        }

        if ($currentUser->position === 'location_manager') {
            if ((int)$currentUser->location_id !== $locationId) {
                throw new Exception('Нет прав на доступ к этой точке.');
            }
            return;
        }

        if ($requireEditor) {
            throw new Exception('Нет прав на редактирование графика.');
        }

        if ((int)$currentUser->location_id !== $locationId) {
            throw new Exception('Нет прав на доступ к этой точке.');
        }
    }

    /**
     * Обновить ячейку графика (один сотрудник / дата / точка).
     *
     * @throws Exception
     */
    public function updateCell(int $userId, int $locationId, string $date, ?string $value): ScheduleShift
    {
        $user = User::findOne($userId);
        $location = Location::findOne($locationId);

        if (!$user || !$location) {
            throw new Exception('Некорректные данные сотрудника или точки.');
        }

        $this->assertCanAccessLocation($locationId, true);

        $shift = ScheduleShift::findOne([
            'user_id' => $userId,
            'location_id' => $locationId,
            'date' => $date,
        ]);

        if ($value === null || $value === '' || $value === '0') {
            // Выходной / отсутствие смены
            if ($shift === null) {
                $shift = new ScheduleShift([
                    'user_id' => $userId,
                    'location_id' => $locationId,
                    'date' => $date,
                ]);
            }
            $shift->time_start = null;
            $shift->time_end = null;
            $shift->is_day_off = 1;
            $shift->recalculate();
            if (!$shift->save()) {
                throw new Exception('Не удалось сохранить смену: ' . json_encode($shift->getFirstErrors(), JSON_UNESCAPED_UNICODE));
            }

            return $shift;
        }

        // Ожидаемый формат: "HH-HH", "HH.5-HH", "HH-HH.5" (например "10-22", "9-21.5")
        if (!preg_match('/^([01]?\d|2[0-3])(\.5)?-([01]?\d|2[0-3])(\.5)?$/', $value, $matches)) {
            throw new Exception('Неверный формат времени. Используйте, например, "10-22" или "9-21.5" или "0" для выходного.');
        }

        $startHour = (int)$matches[1] + (!empty($matches[2]) ? 0.5 : 0.0);
        $endHour = (int)$matches[3] + (!empty($matches[4]) ? 0.5 : 0.0);

        if ($startHour < 0 || $startHour > 23.5 || $endHour < 0 || $endHour > 23.5) {
            throw new Exception('Часы должны быть в диапазоне 0–23.5.');
        }
        if ($startHour >= $endHour) {
            throw new Exception('Время начала должно быть меньше времени окончания.');
        }

        if ($shift === null) {
            $shift = new ScheduleShift([
                'user_id' => $userId,
                'location_id' => $locationId,
                'date' => $date,
            ]);
        }

        $shift->is_day_off = 0;

        $startHourInt = (int)floor($startHour);
        $startMinutes = ($startHour - $startHourInt) >= 0.5 ? 30 : 0;
        $endHourInt = (int)floor($endHour);
        $endMinutes = ($endHour - $endHourInt) >= 0.5 ? 30 : 0;

        $shift->time_start = sprintf('%02d:%02d', $startHourInt, $startMinutes);
        $shift->time_end = sprintf('%02d:%02d', $endHourInt, $endMinutes);
        $shift->recalculate();

        if (!$shift->save()) {
            throw new Exception('Не удалось сохранить смену: ' . json_encode($shift->getFirstErrors(), JSON_UNESCAPED_UNICODE));
        }

        return $shift;
    }

    /**
     * Получить данные графика по точке и периоду.
     *
     * @return array [dates, users, shifts, totalsByUser, totalsByDate]
     */
    public function getScheduleGrid(int $locationId, string $fromDate, string $toDate): array
    {
        $this->assertCanAccessLocation($locationId);

        $location = Location::findOne($locationId);
        if (!$location) {
            throw new Exception('Точка не найдена.');
        }

        // Список дат
        $dates = [];
        $current = strtotime($fromDate);
        $end = strtotime($toDate);
        while ($current <= $end) {
            $dates[] = date('Y-m-d', $current);
            $current = strtotime('+1 day', $current);
        }

        // Сотрудники точки (все, у кого location_id = точка, плюс те, кто имеет смены на этой точке)
        // Коренные сотрудники точки (location_id = эта точка)
        $coreUsers = User::find()
            ->where(['location_id' => $locationId])
            ->andWhere(['is_active' => 1])
            ->orderBy(['last_name' => SORT_ASC, 'first_name' => SORT_ASC])
            ->indexBy('id')
            ->all();

        $shifts = ScheduleShift::find()
            ->where([
                'location_id' => $locationId,
            ])
            ->andWhere(['between', 'date', $fromDate, $toDate])
            ->all();

        $shiftMap = [];
        $users = $coreUsers; // для обратной совместимости — все, у кого есть смены или коренные
        foreach ($shifts as $shift) {
            $shiftMap[$shift->user_id][$shift->date] = $shift;
            if (!isset($users[$shift->user_id])) {
                $users[$shift->user_id] = $shift->user;
            }
        }

        // Разделение: коренные (свои) и усиление (есть смена на этой точке, но user.location_id != locationId)
        $coreUserIds = array_keys($coreUsers);
        $reinforcementUsers = [];
        foreach ($shiftMap as $uid => $byDate) {
            $user = $users[$uid] ?? null;
            if (!$user) continue;
            $isCore = in_array((int)$uid, array_map('intval', $coreUserIds), true);
            if (!$isCore) {
                $reinforcementUsers[$uid] = $user;
            }
        }

        // Карта возможностей: только по конкретным датам (user_availability_by_date)
        $availabilityMap = [];
        if ($users) {
            $userIds = array_keys($users);
            $byDate = UserAvailabilityByDate::find()
                ->where(['user_id' => $userIds])
                ->andWhere(['between', 'date', $fromDate, $toDate])
                ->all();
            foreach ($byDate as $item) {
                $availabilityMap[$item->user_id][$item->date] = $item;
            }
            foreach ($dates as $date) {
                foreach ($userIds as $userId) {
                    if (!isset($availabilityMap[$userId][$date])) {
                        $availabilityMap[$userId][$date] = null;
                    }
                }
            }
        }

        // Подсчёт итогов
        $totalsByUser = [];
        $totalsByDate = [];
        foreach ($users as $userId => $user) {
            $totalsByUser[$userId] = 0;
        }
        foreach ($dates as $date) {
            $totalsByDate[$date] = 0;
        }

        foreach ($shiftMap as $userId => $byDate) {
            foreach ($byDate as $date => $shift) {
                $totalsByUser[$userId] += (float)$shift->hours;
                $totalsByDate[$date] += (float)$shift->hours;
            }
        }

        // users — только коренные сотрудники точки для первой таблицы
        $usersForGrid = [];
        foreach ($users as $uid => $u) {
            if (isset($coreUsers[$uid])) {
                $usersForGrid[$uid] = $u;
            }
        }
        $this->sortUsersMapByPositionAndName($usersForGrid);
        $this->sortUsersMapByPositionAndName($reinforcementUsers);

        // Коды карточек профиля для отображения в графике
        $allUserIds = array_unique(array_merge(array_keys($usersForGrid), array_keys($reinforcementUsers)));
        $cardService = new ProfileCardService();
        $cardCodeByUserId = $cardService->getSelectedTemplateCodesForUserIds($allUserIds);

        return [
            'location' => $location,
            'dates' => $dates,
            'users' => $usersForGrid,
            'usersReinforcement' => $reinforcementUsers,
            'cardCodeByUserId' => $cardCodeByUserId,
            'shifts' => $shiftMap,
            'availability' => $availabilityMap,
            'totalsByUser' => $totalsByUser,
            'totalsByDate' => $totalsByDate,
        ];
    }

    /**
     * Автогенерация смен по карте возможностей (по датам).
     * Заполняет только пустые ячейки: для каждой даты берётся запись из user_availability_by_date.
     *
     * @return int количество созданных/обновлённых смен
     * @throws Exception
     */
    public function generateFromAvailability(int $locationId, string $fromDate, string $toDate): int
    {
        $currentUser = Yii::$app->user->identity;
        if (!$currentUser) {
            throw new Exception('Требуется авторизация.');
        }
        if (!$currentUser->isAdmin()) {
            if ($currentUser->position === 'manager') {
                $allowed = (new \yii\db\Query())->from('manager_locations')
                    ->where(['manager_id' => $currentUser->id])->select('location_id')->column();
                if (!in_array($locationId, $allowed ?? [], true)) {
                    throw new Exception('Нет прав на эту точку.');
                }
            } elseif ($currentUser->position === 'location_manager') {
                if ((int)$currentUser->location_id !== $locationId) {
                    throw new Exception('Нет прав на эту точку.');
                }
            } else {
                throw new Exception('Нет прав на автогенерацию графика.');
            }
        }

        $location = Location::findOne($locationId);
        if (!$location) {
            throw new Exception('Точка не найдена.');
        }

        $users = User::find()
            ->where(['location_id' => $locationId, 'is_active' => 1])
            ->indexBy('id')
            ->all();
        if (empty($users)) {
            return 0;
        }

        $userIds = array_keys($users);
        $byDateList = UserAvailabilityByDate::find()
            ->where(['user_id' => $userIds])
            ->andWhere(['between', 'date', $fromDate, $toDate])
            ->andWhere(['not', ['time_start' => null]])
            ->andWhere(['not', ['time_end' => null]])
            ->all();
        $byDateMap = [];
        foreach ($byDateList as $a) {
            $byDateMap[$a->user_id][$a->date] = $a;
        }

        $count = 0;
        $current = strtotime($fromDate);
        $end = strtotime($toDate);
        while ($current <= $end) {
            $date = date('Y-m-d', $current);
            foreach ($users as $userId => $user) {
                $avail = $byDateMap[$userId][$date] ?? null;
                if (!$avail || !$avail->time_start || !$avail->time_end) {
                    continue;
                }
                $existing = ScheduleShift::findOne([
                    'user_id' => $userId,
                    'location_id' => $locationId,
                    'date' => $date,
                ]);
                if ($existing) {
                    continue; // не перезаписываем существующие смены
                }
                $shift = new ScheduleShift([
                    'user_id' => $userId,
                    'location_id' => $locationId,
                    'date' => $date,
                    'time_start' => $avail->time_start,
                    'time_end' => $avail->time_end,
                    'is_day_off' => 0,
                ]);
                $shift->recalculate();
                if ($shift->save()) {
                    $count++;
                }
            }
            $current = strtotime('+1 day', $current);
        }

        return $count;
    }

    /**
     * Найти кандидатов для усиления на точке по дате:
     * - есть желаемая смена в user_availability_by_date на эту дату
     * - сотрудник активен
     * - нет уже проставленной смены (на любой точке) в этот день
     * - домашняя точка сотрудника отличается от locationId (по умолчанию)
     *
     * @return array<int, array<string,mixed>>
     * @throws Exception
     */
    public function findHelpers(int $locationId, string $date): array
    {
        $currentUser = Yii::$app->user->identity;
        if (!$currentUser) {
            throw new Exception('Требуется авторизация.');
        }
        if (!$currentUser->isAdmin()) {
            if ($currentUser->position === 'manager') {
                $allowed = (new \yii\db\Query())->from('manager_locations')
                    ->where(['manager_id' => $currentUser->id])->select('location_id')->column();
                if (!in_array($locationId, $allowed ?? [], true)) {
                    throw new Exception('Нет прав на эту точку.');
                }
            } elseif ($currentUser->position === 'location_manager') {
                if ((int)$currentUser->location_id !== $locationId) {
                    throw new Exception('Нет прав на эту точку.');
                }
            } else {
                throw new Exception('Нет прав на подбор сотрудников для графика.');
            }
        }

        $location = Location::findOne($locationId);
        if (!$location) {
            throw new Exception('Точка не найдена.');
        }
        $ts = strtotime($date);
        if ($ts === false) {
            throw new Exception('Некорректная дата.');
        }

        // Пользователи, у которых уже есть смена в этот день (на любой точке)
        $busyUserIds = (new \yii\db\Query())
            ->from(ScheduleShift::tableName())
            ->where(['date' => $date])
            ->andWhere(['is_day_off' => 0])
            ->select('user_id')
            ->distinct()
            ->column();

        // Доступности по дате
        $availabilities = UserAvailabilityByDate::find()
            ->where(['date' => $date])
            ->andWhere(['not', ['time_start' => null]])
            ->andWhere(['not', ['time_end' => null]])
            ->all();

        if (empty($availabilities)) {
            return [];
        }

        $byUser = [];
        $userIds = [];
        foreach ($availabilities as $a) {
            $byUser[$a->user_id] = $a;
            $userIds[] = $a->user_id;
        }
        $userIds = array_values(array_unique($userIds));

        if (empty($userIds)) {
            return [];
        }

        // Исключаем тех, кому уже отправлен pending-инвайт на эту точку и дату
        $pendingInviteUserIds = (new \yii\db\Query())
            ->from(ShiftInvite::tableName())
            ->where([
                'location_id' => $locationId,
                'date' => $date,
                'status' => ShiftInvite::STATUS_PENDING,
            ])
            ->select('user_id')
            ->column();

        $users = User::find()
            ->where(['id' => $userIds, 'is_active' => 1])
            ->andWhere(['<>', 'location_id', $locationId])
            ->orderBy(['last_name' => SORT_ASC, 'first_name' => SORT_ASC])
            ->all();

        $result = [];
        foreach ($users as $user) {
            if (in_array($user->id, $pendingInviteUserIds, true)) {
                continue;
            }
            if (in_array($user->id, $busyUserIds ?? [], true)) {
                continue;
            }
            $avail = $byUser[$user->id] ?? null;
            if (!$avail) {
                continue;
            }
            $timeStart = $avail->time_start;
            $timeEnd = $avail->time_end;
            $availabilityText = '';
            if ($timeStart && $timeEnd) {
                $availabilityText = substr($timeStart, 0, 5) . '–' . substr($timeEnd, 0, 5);
            }

            $result[] = [
                'id' => (int)$user->id,
                'fullName' => $user->getFullName(),
                'positionLabel' => $user->getPositionLabel(),
                'homeLocationId' => $user->location_id ? (int)$user->location_id : null,
                'homeLocationName' => $user->location ? $user->location->name : null,
                'timeStart' => $timeStart,
                'timeEnd' => $timeEnd,
                'availability' => $availabilityText,
            ];
        }

        return $result;
    }

    /**
     * Отправить инвайт на смену (усиление). Сотрудник примет или откажет во вкладке Уведомления.
     *
     * @param int $userId кого приглашаем
     * @param int $locationId точка
     * @param string $date дата
     * @param string $value время "10-22" или "0"
     * @throws Exception
     */
    public function createInvite(int $userId, int $locationId, string $date, string $value): ShiftInvite
    {
        $currentUser = Yii::$app->user->identity;
        if (!$currentUser) {
            throw new Exception('Требуется авторизация.');
        }
        if (!$currentUser->isAdmin()) {
            if ($currentUser->position === 'manager') {
                $allowed = (new \yii\db\Query())->from('manager_locations')
                    ->where(['manager_id' => $currentUser->id])->select('location_id')->column();
                if (!in_array($locationId, $allowed ?? [], true)) {
                    throw new Exception('Нет прав на эту точку.');
                }
            } elseif ($currentUser->position === 'location_manager') {
                if ((int)$currentUser->location_id !== $locationId) {
                    throw new Exception('Нет прав на эту точку.');
                }
            } else {
                throw new Exception('Нет прав на отправку инвайтов.');
            }
        }

        $user = User::findOne($userId);
        $location = Location::findOne($locationId);
        if (!$user || !$location) {
            throw new Exception('Некорректные данные сотрудника или точки.');
        }
        if ((int)$user->location_id === $locationId) {
            throw new Exception('Сотрудник уже привязан к этой точке. Используйте обычную постановку смены.');
        }

        $existing = ShiftInvite::findOne([
            'user_id' => $userId,
            'location_id' => $locationId,
            'date' => $date,
            'status' => ShiftInvite::STATUS_PENDING,
        ]);
        if ($existing) {
            throw new Exception('Инвайт на эту дату уже отправлен этому сотруднику.');
        }

        $timeStart = null;
        $timeEnd = null;
        if ($value !== '' && $value !== '0' && preg_match('/^([01]?\d|2[0-3])(\.5)?-([01]?\d|2[0-3])(\.5)?$/', $value, $m)) {
            $sh = (int)$m[1] + (!empty($m[2]) ? 0.5 : 0);
            $eh = (int)$m[3] + (!empty($m[4]) ? 0.5 : 0);
            $timeStart = sprintf('%02d:%02d', (int)floor($sh), ($sh - floor($sh)) >= 0.5 ? 30 : 0);
            $timeEnd = sprintf('%02d:%02d', (int)floor($eh), ($eh - floor($eh)) >= 0.5 ? 30 : 0);
        }

        $invite = new ShiftInvite([
            'user_id' => $userId,
            'location_id' => $locationId,
            'date' => $date,
            'time_start' => $timeStart,
            'time_end' => $timeEnd,
            'invited_by' => $currentUser->id,
            'status' => ShiftInvite::STATUS_PENDING,
        ]);
        if (!$invite->save()) {
            throw new Exception('Не удалось сохранить инвайт: ' . json_encode($invite->getFirstErrors(), JSON_UNESCAPED_UNICODE));
        }
        (new TelegramNotificationService())->onShiftInviteCreated($invite);
        return $invite;
    }

    /**
     * Принять инвайт: создать смену и пометить инвайт принятым.
     *
     * @param int $inviteId
     * @return ScheduleShift
     * @throws Exception
     */
    public function acceptInvite(int $inviteId): ScheduleShift
    {
        $invite = ShiftInvite::findOne($inviteId);
        if (!$invite || $invite->status !== ShiftInvite::STATUS_PENDING) {
            throw new Exception('Инвайт не найден или уже обработан.');
        }
        $currentUser = Yii::$app->user->identity;
        if (!$currentUser || (int)$currentUser->id !== (int)$invite->user_id) {
            throw new Exception('Принять инвайт может только приглашённый сотрудник.');
        }

        $value = ($invite->time_start && $invite->time_end)
            ? (substr($invite->time_start, 0, 5) . '-' . substr($invite->time_end, 0, 5))
            : '10-22';
        $shift = $this->createShiftFromInvite($invite, $value);
        $invite->status = ShiftInvite::STATUS_ACCEPTED;
        $invite->save(false);
        (new TelegramNotificationService())->onShiftInviteAccepted($invite);
        return $shift;
    }

    /**
     * Создать смену по принятому инвайту (без проверки прав менеджера).
     */
    private function createShiftFromInvite(ShiftInvite $invite, string $value): ScheduleShift
    {
        if (!preg_match('/^([01]?\d|2[0-3])(\.5)?-([01]?\d|2[0-3])(\.5)?$/', $value, $matches)) {
            $value = '10-22';
            preg_match('/^([01]?\d|2[0-3])(\.5)?-([01]?\d|2[0-3])(\.5)?$/', $value, $matches);
        }
        $startHour = (int)$matches[1] + (!empty($matches[2]) ? 0.5 : 0.0);
        $endHour = (int)$matches[3] + (!empty($matches[4]) ? 0.5 : 0.0);
        $startHourInt = (int)floor($startHour);
        $startMinutes = ($startHour - $startHourInt) >= 0.5 ? 30 : 0;
        $endHourInt = (int)floor($endHour);
        $endMinutes = ($endHour - $endHourInt) >= 0.5 ? 30 : 0;
        $timeStart = sprintf('%02d:%02d', $startHourInt, $startMinutes);
        $timeEnd = sprintf('%02d:%02d', $endHourInt, $endMinutes);

        $shift = ScheduleShift::findOne([
            'user_id' => $invite->user_id,
            'location_id' => $invite->location_id,
            'date' => $invite->date,
        ]);
        if ($shift === null) {
            $shift = new ScheduleShift([
                'user_id' => $invite->user_id,
                'location_id' => $invite->location_id,
                'date' => $invite->date,
            ]);
        }
        $shift->is_day_off = 0;
        $shift->time_start = $timeStart;
        $shift->time_end = $timeEnd;
        $shift->recalculate();
        if (!$shift->save()) {
            throw new Exception('Не удалось сохранить смену: ' . json_encode($shift->getFirstErrors(), JSON_UNESCAPED_UNICODE));
        }
        return $shift;
    }

    /**
     * Отказаться от инвайта.
     *
     * @param int $inviteId
     * @throws Exception
     */
    public function declineInvite(int $inviteId): void
    {
        $invite = ShiftInvite::findOne($inviteId);
        if (!$invite || $invite->status !== ShiftInvite::STATUS_PENDING) {
            throw new Exception('Инвайт не найден или уже обработан.');
        }
        $currentUser = Yii::$app->user->identity;
        if (!$currentUser || (int)$currentUser->id !== (int)$invite->user_id) {
            throw new Exception('Отказать от инвайта может только приглашённый сотрудник.');
        }
        $invite->status = ShiftInvite::STATUS_DECLINED;
        $invite->save(false);
        (new TelegramNotificationService())->onShiftInviteDeclined($invite);
    }

    /**
     * Порядок должностей в графике: управляющий → менеджер точки → старший тимейкер → тимейкер → стажёр.
     *
     * @param array<int|string, User> $usersById
     */
    private function sortUsersMapByPositionAndName(array &$usersById): void
    {
        if ($usersById === []) {
            return;
        }
        uksort($usersById, function ($aid, $bid) use ($usersById) {
            $ua = $usersById[$aid];
            $ub = $usersById[$bid];
            $wa = $this->positionRank($ua->position ?? null);
            $wb = $this->positionRank($ub->position ?? null);
            if ($wa !== $wb) {
                return $wa <=> $wb;
            }
            $ln = strcmp((string)$ua->last_name, (string)$ub->last_name);
            if ($ln !== 0) {
                return $ln;
            }
            return strcmp((string)$ua->first_name, (string)$ub->first_name);
        });
    }

    private function positionRank(?string $position): int
    {
        static $order = [
            'manager' => 0,
            'location_manager' => 1,
            'senior_teamaker' => 2,
            'teamaker' => 3,
            'trainee' => 4,
        ];
        return $order[$position] ?? 99;
    }
}


