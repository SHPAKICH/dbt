<?php

namespace app\services;

use app\models\DailyReport;
use app\models\Location;
use app\models\LocationKro;
use app\models\PositionRate;
use app\models\ScheduleShift;
use app\models\User;
use app\models\UserPositionHistory;
use Yii;
use yii\base\Component;
use yii\db\Expression;

/**
 * Расчёт зарплаты: Ставка + КРО + Премия.
 * Часы из графика (schedule_shifts), выручка из дейли (daily_reports.to_revenue).
 * Зарплата за день на другой точке считается по данным этой точки.
 */
class PayrollService extends Component
{
    /** Порог выручки на человека для премии (руб). */
    public const REVENUE_PER_PERSON_THRESHOLD = 20000;

    /** Доля от превышения, идущая в премиальный фонд (5%). */
    public const BONUS_SHARE = 0.05;

    /**
     * Список точек для зарплаты (как в Daily: админ, управляющий, менеджер точки).
     */
    public function getPayrollLocations(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            return [];
        }
        $query = Location::find()->where(['is_active' => 1]);
        if ($user->isAdmin()) {
            return $query->orderBy(['name' => SORT_ASC])->all();
        }
        if ($user->position === 'manager') {
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
        } elseif (in_array($user->position, ['location_manager', 'senior_teamaker'], true) && $user->location_id) {
            $query->andWhere(['id' => $user->location_id]);
        } else {
            return [];
        }
        return $query->orderBy(['name' => SORT_ASC])->all();
    }

    /**
     * Надбавка КРО (руб/час) для точки за месяц.
     */
    public function getKroBonusForLocationMonth(int $locationId, int $year, int $month): float
    {
        $kro = LocationKro::getForLocationMonth($locationId, $year, $month);
        return $kro ? LocationKro::passPercentToBonus((float) $kro->pass_percent) : 0.0;
    }

    /**
     * Расчёт за период с разбивкой по дням и по точкам.
     * Для сотрудника (trainee/teamaker/senior_teamaker) возвращаются только его смены.
     *
     * @param string $from Y-m-d
     * @param string $to Y-m-d
     * @param int|null $locationId фильтр по точке (для админа/менеджера)
     * @param int|null $userId если задан — только этот пользователь (для «моя зарплата»)
     * @return array { byDays: [...], totalHours, totalAmount, summaryByUser: [...] }
     */
    public function calculate(string $from, string $to, ?int $locationId = null, ?int $userId = null): array
    {
        // Основной запрос: смены конкретного пользователя (или все, если userId не задан)
        $shiftsQuery = ScheduleShift::find()
            ->alias('s')
            ->select(['s.user_id', 's.location_id', 's.date', 's.hours'])
            ->where(['between', 's.date', $from, $to])
            ->andWhere(['>', 's.hours', 0]);

        if ($locationId !== null) {
            $shiftsQuery->andWhere(['s.location_id' => $locationId]);
        }
        if ($userId !== null) {
            $shiftsQuery->andWhere(['s.user_id' => $userId]);
        }

        $shifts = $shiftsQuery->asArray()->all();
        if (empty($shifts)) {
            return [
                'byDays' => [],
                'totalHours' => 0,
                'totalAmount' => 0,
                'summaryByUser' => [],
            ];
        }

        $locationIds = array_unique(array_column($shifts, 'location_id'));
        $userIds = array_unique(array_column($shifts, 'user_id'));

        $reportsByKey = $this->loadDailyReports($from, $to, $locationIds);
        $kroByKey = $this->loadKroForLocationsMonths($locationIds, $from, $to);
        $users = User::find()->where(['id' => $userIds])->indexBy('id')->all();
        $locations = Location::find()->where(['id' => $locationIds])->indexBy('id')->all();

        // Когда фильтр по пользователю задан, нужно знать реальное число людей
        // на каждой точке в каждый день — иначе порог премии считается для 1 человека.
        // Загружаем ВСЕ смены на те же (date, location_id) пары.
        $allShiftsByKey = [];   // key => [ uid => hours ]
        if ($userId !== null) {
            $datePairs = [];
            foreach ($shifts as $row) {
                $datePairs[$row['date'] . '_' . $row['location_id']] = [
                    'date'        => $row['date'],
                    'location_id' => $row['location_id'],
                ];
            }
            if (!empty($datePairs)) {
                // Строим OR-условие: (date = X AND location_id = Y) OR ...
                $orConditions = ['or'];
                foreach ($datePairs as $pair) {
                    $orConditions[] = ['and',
                        ['s2.date'        => $pair['date']],
                        ['s2.location_id' => $pair['location_id']],
                    ];
                }
                $allShifts = ScheduleShift::find()
                    ->alias('s2')
                    ->select(['s2.user_id', 's2.location_id', 's2.date', 's2.hours'])
                    ->where(['>', 's2.hours', 0])
                    ->andWhere($orConditions)
                    ->asArray()
                    ->all();

                foreach ($allShifts as $row) {
                    $key = $row['date'] . '_' . $row['location_id'];
                    $uid = (int) $row['user_id'];
                    $hrs = (float) $row['hours'];
                    if (!isset($allShiftsByKey[$key])) {
                        $allShiftsByKey[$key] = [];
                    }
                    $allShiftsByKey[$key][$uid] = ($allShiftsByKey[$key][$uid] ?? 0) + $hrs;
                }
            }
        }

        $byDays = [];
        $dayKeys = [];

        foreach ($shifts as $row) {
            $locId = (int) $row['location_id'];
            $date = $row['date'];
            $key = $date . '_' . $locId;
            if (!isset($dayKeys[$key])) {
                $dayKeys[$key] = true;
                $revenue = isset($reportsByKey[$key]) ? (float) $reportsByKey[$key]['to_revenue'] : 0;
                $byDays[$key] = [
                    'date' => $date,
                    'location_id' => $locId,
                    'location_name' => isset($locations[$locId]) ? $locations[$locId]->name : '',
                    'revenue' => $revenue,
                    'shifts' => [],
                    'total_hours' => 0,
                    'n_people' => 0,
                ];
            }
        }

        foreach ($shifts as $row) {
            $key = $row['date'] . '_' . $row['location_id'];
            $uid = (int) $row['user_id'];
            $hours = (float) $row['hours'];
            $byDays[$key]['shifts'][$uid] = ($byDays[$key]['shifts'][$uid] ?? 0) + $hours;
        }

        foreach (array_keys($byDays) as $key) {
            if ($userId !== null && isset($allShiftsByKey[$key])) {
                // Используем реальные данные по всей точке за день
                $byDays[$key]['total_hours'] = array_sum($allShiftsByKey[$key]);
                $byDays[$key]['n_people']    = count($allShiftsByKey[$key]);
            } else {
                $byDays[$key]['total_hours'] = array_sum($byDays[$key]['shifts']);
                $byDays[$key]['n_people']    = count($byDays[$key]['shifts']);
            }
        }

        $bonusPerHourByKey = [];
        foreach ($byDays as $key => &$day) {
            $revenue = $day['revenue'];
            $totalHours = $day['total_hours'];
            $n = $day['n_people'];
            $excess = $revenue - $n * self::REVENUE_PER_PERSON_THRESHOLD;
            if ($totalHours > 0 && $excess > 0) {
                $bonusPerHourByKey[$key] = ($excess * self::BONUS_SHARE) / $totalHours;
            } else {
                $bonusPerHourByKey[$key] = 0.0;
            }
        }
        unset($day);

        $yearMonthByDate = [];
        foreach (array_unique(array_column($shifts, 'date')) as $d) {
            $yearMonthByDate[$d] = [ (int) date('Y', strtotime($d)), (int) date('n', strtotime($d)) ];
        }

        // Должность на дату смены (повышение/понижение с учётом effective_from)
        $pairs = [];
        foreach ($byDays as $day) {
            $date = $day['date'];
            foreach (array_keys($day['shifts']) as $uid) {
                $pairs[] = ['user_id' => $uid, 'date' => $date];
            }
        }
        $positionMap = UserPositionHistory::getPositionMapForPairs($pairs);

        $resultByDays = [];
        $summaryByUser = [];

        foreach ($byDays as $key => $day) {
            $date = $day['date'];
            $locId = $day['location_id'];
            [$y, $m] = $yearMonthByDate[$date];
            $kroBonus = $kroByKey[$locId][$y][$m] ?? 0.0;
            $bonusPerHour = $bonusPerHourByKey[$key] ?? 0.0;

            $dayRows = [];
            foreach ($day['shifts'] as $uid => $hours) {
                $user = $users[$uid] ?? null;
                if (!$user) {
                    continue;
                }
                $positionKey = $uid . '_' . $date;
                $positionOnDate = $positionMap[$positionKey] ?? $user->position;
                $baseRate = (float) (PositionRate::getRateByCode($positionOnDate) ?? 0);
                $rate = $baseRate + $kroBonus;
                $salaryPart = round($rate * $hours, 2);
                $bonusPart = round($bonusPerHour * $hours, 2);
                $total = round($salaryPart + $bonusPart, 2);

                $dayRows[] = [
                    'user_id' => $uid,
                    'user_name' => $user->getFullName(),
                    'position_label' => UserPositionHistory::getPositionLabelByCode($positionOnDate),
                    'hours' => $hours,
                    'base_rate' => $baseRate,
                    'kro_add' => $kroBonus,
                    'rate' => $rate,
                    'salary_part' => $salaryPart,
                    'bonus_per_hour' => $bonusPerHour,
                    'bonus' => $bonusPart,
                    'total' => $total,
                ];

                if (!isset($summaryByUser[$uid])) {
                    $summaryByUser[$uid] = [
                        'user_id' => $uid,
                        'user_name' => $user->getFullName(),
                        'hours' => 0,
                        'amount' => 0,
                    ];
                }
                $summaryByUser[$uid]['hours'] += $hours;
                $summaryByUser[$uid]['amount'] += $total;
            }

            $resultByDays[] = [
                'date' => $date,
                'location_id' => $locId,
                'location_name' => $day['location_name'],
                'revenue' => $day['revenue'],
                'total_hours' => $day['total_hours'],
                'n_people' => $day['n_people'],
                'bonus_per_hour' => round($bonusPerHour, 2),
                'kro_add' => $kroBonus,
                'employees' => $dayRows,
            ];
        }

        usort($resultByDays, static function ($a, $b) {
            $c = strcmp($a['date'], $b['date']);
            return $c !== 0 ? $c : $a['location_id'] - $b['location_id'];
        });

        $totalHours = 0;
        $totalAmount = 0;
        foreach ($summaryByUser as $u) {
            $totalHours += $u['hours'];
            $totalAmount += $u['amount'];
        }
        foreach (array_keys($summaryByUser) as $uid) {
            $summaryByUser[$uid]['amount'] = round($summaryByUser[$uid]['amount'], 2);
        }

        // Для обратной совместимости с веб-экспортом: rows как список по пользователям
        $rows = [];
        foreach ($summaryByUser as $s) {
            $u = $users[$s['user_id']] ?? null;
            if (!$u) {
                continue;
            }
            $hrs = $s['hours'];
            $rows[] = [
                'user' => $u,
                'location' => null,
                'positionLabel' => $u->getPositionLabel(),
                'hours' => $hrs,
                'rate' => $hrs > 0 ? round($s['amount'] / $hrs, 2) : 0,
                'amount' => $s['amount'],
            ];
        }

        return [
            'byDays' => $resultByDays,
            'totalHours' => round($totalHours, 2),
            'totalAmount' => round($totalAmount, 2),
            'summaryByUser' => array_values($summaryByUser),
            'rows' => $rows,
        ];
    }

    /**
     * Расчёт «моя зарплата по дням» для текущего пользователя (для вкладки Уведомления).
     *
     * @return array { days: [ { date, location_name, hours, base_rate, kro_add, bonus_per_hour, bonus, total }, ... ], totalHours, totalAmount }
     */
    public function getMyDailyBreakdown(string $from, string $to): array
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            return ['days' => [], 'totalHours' => 0, 'totalAmount' => 0];
        }
        $result = $this->calculate($from, $to, null, (int) $user->id);
        $days = [];
        foreach ($result['byDays'] as $day) {
            foreach ($day['employees'] as $emp) {
                if ((int) $emp['user_id'] === (int) $user->id) {
                    $days[] = [
                        'date' => $day['date'],
                        'location_id' => $day['location_id'],
                        'location_name' => $day['location_name'],
                        'hours' => $emp['hours'],
                        'base_rate' => $emp['base_rate'],
                        'kro_add' => $emp['kro_add'],
                        'bonus_per_hour' => $emp['bonus_per_hour'],
                        'bonus' => $emp['bonus'],
                        'total' => $emp['total'],
                    ];
                    break;
                }
            }
        }
        return [
            'days' => $days,
            'totalHours' => $result['totalHours'],
            'totalAmount' => $result['totalAmount'],
        ];
    }

    private function loadDailyReports(string $from, string $to, array $locationIds): array
    {
        if (empty($locationIds)) {
            return [];
        }
        $rows = DailyReport::find()
            ->select(['location_id', 'report_date', 'to_revenue'])
            ->where(['between', 'report_date', $from, $to])
            ->andWhere(['location_id' => $locationIds])
            ->asArray()
            ->all();
        $indexed = [];
        foreach ($rows as $r) {
            $indexed[$r['report_date'] . '_' . $r['location_id']] = $r;
        }
        return $indexed;
    }

    /** @return array [ locationId => [ year => [ month => kroBonus ], ... ], ... ] */
    private function loadKroForLocationsMonths(array $locationIds, string $from, string $to): array
    {
        if (empty($locationIds)) {
            return [];
        }
        $fromY = (int) date('Y', strtotime($from));
        $fromM = (int) date('n', strtotime($from));
        $toY = (int) date('Y', strtotime($to));
        $toM = (int) date('n', strtotime($to));
        $list = LocationKro::find()
            ->where(['location_id' => $locationIds])
            ->andWhere(['>=', new Expression('(year * 12 + month)'), $fromY * 12 + $fromM])
            ->andWhere(['<=', new Expression('(year * 12 + month)'), $toY * 12 + $toM])
            ->asArray()
            ->all();
        $out = [];
        foreach ($list as $row) {
            $locId = (int) $row['location_id'];
            $y = (int) $row['year'];
            $m = (int) $row['month'];
            if (!isset($out[$locId])) {
                $out[$locId] = [];
            }
            if (!isset($out[$locId][$y])) {
                $out[$locId][$y] = [];
            }
            $out[$locId][$y][$m] = LocationKro::passPercentToBonus((float) $row['pass_percent']);
        }
        return $out;
    }
}
