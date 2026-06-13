<?php

namespace app\services;

use app\models\DailyReport;
use app\models\Location;
use app\models\ScheduleShift;
use app\models\User;
use Yii;
use yii\base\Component;
use yii\db\ActiveQuery;

/**
 * Сервис для работы с Дейли.
 * Автогенерация строк на месяц, фильтрация по ролям.
 */
class DailyReportService extends Component
{
    /**
     * Получить список точек, доступных текущему пользователю.
     * Админ — все. Управляющий — свои (manager_locations). Менеджер/Ст.тимейкер — своя точка.
     *
     * @return Location[]
     */
    public function getAccessibleLocations(): array
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
     * План на день может ставить только территориальный управляющий (manager) или админ.
     */
    public function canEditPlan(): bool
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            return false;
        }
        return $user->isAdmin() || $user->position === 'manager';
    }

    /**
     * Проверить доступ к точке по ролям.
     *
     * @param int $locationId
     * @return bool
     */
    public function canAccessLocation(int $locationId): bool
    {
        $locations = $this->getAccessibleLocations();
        foreach ($locations as $loc) {
            if ((int)$loc->id === $locationId) {
                return true;
            }
        }
        return false;
    }

    /**
     * Query для дейли с фильтром по ролям.
     *
     * @param int|null $locationId
     * @param string $from Y-m-d
     * @param string $to Y-m-d
     * @return ActiveQuery
     */
    public function getReportsQuery(?int $locationId, string $from, string $to): ActiveQuery
    {
        $query = DailyReport::find()
            ->with(['location', 'manager'])
            ->where(['between', 'report_date', $from, $to])
            ->orderBy(['report_date' => SORT_ASC]);

        if ($locationId !== null) {
            if (!$this->canAccessLocation($locationId)) {
                $query->andWhere('0=1');
            } else {
                $query->andWhere(['location_id' => $locationId]);
            }
        } else {
            $locations = $this->getAccessibleLocations();
            $ids = array_column($locations, 'id');
            if (empty($ids)) {
                $query->andWhere('0=1');
            } else {
                $query->andWhere(['location_id' => $ids]);
            }
        }

        return $query;
    }

    /**
     * Обеспечить наличие записей на каждый день месяца для точки.
     *
     * @param int $locationId
     * @param string $yearMonth Y-m
     */
    public function ensureMonthRows(int $locationId, string $yearMonth): void
    {
        if (!$this->canAccessLocation($locationId)) {
            return;
        }

        $from = $yearMonth . '-01';
        $to = date('Y-m-t', strtotime($from));

        $existing = (new \yii\db\Query())
            ->from('daily_reports')
            ->where(['location_id' => $locationId])
            ->andWhere(['between', 'report_date', $from, $to])
            ->select('report_date')
            ->column();

        $date = new \DateTime($from);
        $end = new \DateTime($to);

        while ($date <= $end) {
            $d = $date->format('Y-m-d');
            if (!in_array($d, $existing, true)) {
                $r = new DailyReport();
                $r->location_id = $locationId;
                $r->report_date = $d;
                $r->save(false);
            }
            $date->modify('+1 day');
        }
    }

    /**
     * Часы сотрудников за день по точке (из графика).
     *
     * @param int $locationId
     * @param string $date Y-m-d
     * @return float
     */
    public function getWorkerHoursForDay(int $locationId, string $date): float
    {
        $sum = ScheduleShift::find()
            ->where(['location_id' => $locationId, 'date' => $date])
            ->sum('hours');
        return (float)($sum ?? 0);
    }

    /**
     * Пересчитать все автополя у DailyReport.
     * ТО = БАР+ДОСТАВКА+САМОВЫВОЗ+БОНУСЫ
     * DELTA = ТО - План
     * Заказов = Чеки БАР + Чеки ДОСТАВКА + Чеки САМОВЫВОЗ
     * Ср. чек = выручка / чеки (если чеки > 0)
     * Часы — из графика
     * Произв. заказы = Заказов / часы
     * Произв. деньги = ТО / часы / 30
     *
     * @param DailyReport $r
     */
    public function recalculate(DailyReport $r): void
    {
        $bar = (float)($r->bar ?? 0);
        $delivery = (float)($r->delivery ?? 0);
        $selfPickup = (float)($r->self_pickup ?? 0);
        $bonuses = (float)($r->bonuses ?? 0);
        $planDaily = $r->plan_daily !== null ? (float)$r->plan_daily : null;
        $checksBar = (int)($r->checks_bar ?? 0);
        $checksDelivery = (int)($r->checks_delivery ?? 0);
        $checksSelfPickup = (int)($r->checks_self_pickup ?? 0);

        $r->to_revenue = $bar + $delivery + $selfPickup + $bonuses;
        $r->delta_plan = $planDaily !== null ? $r->to_revenue - $planDaily : null;
        $r->orders_count = $checksBar + $checksDelivery + $checksSelfPickup;

        $r->avg_check_bar = $checksBar > 0 ? round($bar / $checksBar, 2) : null;
        $r->avg_check_delivery = $checksDelivery > 0 ? round($delivery / $checksDelivery, 2) : null;
        $r->avg_check_self_pickup = $checksSelfPickup > 0 ? round($selfPickup / $checksSelfPickup, 2) : null;

        $r->worker_hours = $this->getWorkerHoursForDay((int)$r->location_id, $r->report_date);
        $r->productivity_orders = $r->worker_hours > 0 && $r->orders_count > 0
            ? round($r->orders_count / $r->worker_hours, 2) : null;
        $r->productivity_money = $r->worker_hours > 0 && $r->to_revenue > 0
            ? round($r->to_revenue / $r->worker_hours / 30, 2) : null;
    }
}
