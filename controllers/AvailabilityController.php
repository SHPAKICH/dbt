<?php

namespace app\controllers;

use app\models\Location;
use app\models\User;
use app\models\UserAvailabilityByDate;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

class AvailabilityController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Карта возможностей: своя (редактируемая) или по точке/территории для менеджера и управляющего.
     * mode=my — всегда своя карта. Иначе для location_manager — все сотрудники точки, для manager — по точкам.
     */
    public function actionIndex($month = null, $year = null, $location_id = null, $mode = null)
    {
        $user = Yii::$app->user->identity;
        $now = new \DateTimeImmutable();
        if ($month === null || $year === null) {
            $month = (int)$now->format('n');
            $year = (int)$now->format('Y');
        } else {
            $month = (int)$month;
            $year = (int)$year;
        }

        $firstDay = new \DateTimeImmutable("{$year}-" . sprintf('%02d', $month) . "-01");
        $lastDay = (int)$firstDay->format('t');
        $daysOfMonth = [];
        $datesOfMonth = [];
        for ($d = 1; $d <= $lastDay; $d++) {
            $date = $firstDay->setDate($year, $month, $d);
            $daysOfMonth[$d] = (int)$date->format('N');
            $datesOfMonth[$d] = $date->format('Y-m-d');
        }
        $dateList = array_values($datesOfMonth);
        $weekdayNamesShort = [1 => 'Пн', 2 => 'Вт', 3 => 'Ср', 4 => 'Чт', 5 => 'Пт', 6 => 'Сб', 7 => 'Вс'];

        $isManager = $user->position === 'manager' && !$user->isAdmin();
        $isLocationManager = $user->position === 'location_manager';
        $isAdmin = $user->isAdmin();
        $showTeamView = ($isLocationManager || $isManager || $isAdmin) && $mode !== 'my';

        if ($showTeamView) {
            return $this->renderTeamView($user, $month, $year, $location_id, $lastDay, $daysOfMonth, $datesOfMonth, $dateList, $weekdayNamesShort);
        }

        $records = [];
        if ($dateList) {
            $rows = UserAvailabilityByDate::find()
                ->where(['user_id' => $user->id])
                ->andWhere(['date' => $dateList])
                ->all();
            foreach ($rows as $row) {
                $records[$row->date] = $row;
            }
        }

        return $this->render('index', [
            'records' => $records,
            'year' => $year,
            'month' => $month,
            'lastDay' => $lastDay,
            'daysOfMonth' => $daysOfMonth,
            'datesOfMonth' => $datesOfMonth,
            'weekdayNamesShort' => $weekdayNamesShort,
            'canSeeTeamView' => $isLocationManager || $isManager || $isAdmin,
        ]);
    }

    /**
     * Данные для командного вида: локации и по каждой — сотрудники и их доступность по датам.
     */
    private function renderTeamView($user, $month, $year, $locationIdParam, $lastDay, $daysOfMonth, $datesOfMonth, $dateList, $weekdayNamesShort)
    {
        $locations = [];
        $hasLocationParam = array_key_exists('location_id', Yii::$app->request->get());
        $locationId = ($locationIdParam !== null && $locationIdParam !== '') ? (int)$locationIdParam : null;

        if ($user->isAdmin()) {
            $locations = Location::find()->where(['is_active' => 1])->orderBy(['name' => SORT_ASC])->all();
            if ($locationId === null && !$hasLocationParam && $locations) {
                $locationId = (int)$locations[0]->id;
            }
        } elseif ($user->position === 'manager') {
            $ids = (new \yii\db\Query())->from('manager_locations')->where(['manager_id' => $user->id])->select('location_id')->column();
            $locations = Location::find()->where(['id' => $ids, 'is_active' => 1])->orderBy(['name' => SORT_ASC])->all();
            if ($locationId === null && !$hasLocationParam && $locations) {
                $locationId = (int)$locations[0]->id;
            }
        } else {
            $loc = Location::findOne($user->location_id);
            $locations = $loc ? [$loc] : [];
            $locationId = (int)$user->location_id;
        }

        $monthNames = [1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель', 5 => 'Май', 6 => 'Июнь',
            7 => 'Июль', 8 => 'Август', 9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'];

        $locIds = $locationId ? [$locationId] : array_map(function ($l) { return $l->id; }, $locations);
        $sections = $locIds ? $this->buildTeamSections($locIds, $dateList, $lastDay) : [];

        return $this->render('index-team', [
            'year' => $year,
            'month' => $month,
            'lastDay' => $lastDay,
            'daysOfMonth' => $daysOfMonth,
            'datesOfMonth' => $datesOfMonth,
            'weekdayNamesShort' => $weekdayNamesShort,
            'monthNames' => $monthNames,
            'locations' => $locations,
            'locationId' => $locationId,
            'sections' => $sections ?? [],
        ]);
    }

    private function buildTeamSections(array $locationIds, array $dateList, int $lastDay): array
    {
        $sections = [];
        foreach ($locationIds as $locId) {
            $location = Location::findOne($locId);
            if (!$location) {
                continue;
            }
            $employees = User::find()
                ->where(['location_id' => $locId, 'is_active' => 1])
                ->orderBy(['last_name' => SORT_ASC, 'first_name' => SORT_ASC])
                ->all();
            if (empty($employees)) {
                $sections[] = ['location' => $location, 'employees' => [], 'byUserByDate' => []];
                continue;
            }
            $userIds = array_map(function ($u) { return $u->id; }, $employees);
            $rows = UserAvailabilityByDate::find()
                ->where(['user_id' => $userIds])
                ->andWhere(['date' => $dateList])
                ->all();
            $byUserByDate = [];
            foreach ($rows as $r) {
                $byUserByDate[$r->user_id][$r->date] = $r;
            }
            $sections[] = ['location' => $location, 'employees' => $employees, 'byUserByDate' => $byUserByDate];
        }
        return $sections;
    }

    /**
     * AJAX: обновление ячейки по конкретной дате (Y-m-d).
     */
    public function actionUpdateCell()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = Yii::$app->user->identity;
        $date = trim((string)Yii::$app->request->post('date', ''));
        $value = trim((string)Yii::$app->request->post('value', ''));

        if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return ['success' => false, 'error' => 'Некорректная дата.'];
        }

        $record = UserAvailabilityByDate::findOne(['user_id' => $user->id, 'date' => $date]);

        if ($value === '' || $value === '0') {
            if ($record) {
                $record->delete();
            }
            return ['success' => true, 'cell' => ['text' => '']];
        }

        if (!preg_match('/^([01]?\d|2[0-3])(\.5)?-([01]?\d|2[0-3])(\.5)?$/', $value, $matches)) {
            return [
                'success' => false,
                'error' => 'Неверный формат. Используйте, например, "9-21" или "9.5-21.5" или "0".',
            ];
        }

        $start = (int)$matches[1] + (!empty($matches[2]) ? 0.5 : 0.0);
        $end = (int)$matches[3] + (!empty($matches[4]) ? 0.5 : 0.0);

        if ($start < 0 || $start > 23.5 || $end < 0 || $end > 23.5) {
            return ['success' => false, 'error' => 'Часы должны быть в диапазоне 0–23.5.'];
        }
        if ($start >= $end) {
            return ['success' => false, 'error' => 'Время начала должно быть меньше времени окончания.'];
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
            return ['success' => false, 'error' => 'Не удалось сохранить.'];
        }

        $startText = $startHourInt . ($startMinutes === 30 ? '.5' : '');
        $endText = $endHourInt . ($endMinutes === 30 ? '.5' : '');

        return [
            'success' => true,
            'cell' => ['text' => $startText . '-' . $endText],
        ];
    }
}



