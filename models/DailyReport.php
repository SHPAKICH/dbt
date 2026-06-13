<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * DailyReport model — Дейли (ежедневный отчёт по точке)
 *
 * Заполняемые: plan_daily (только тер.управ+), bar, delivery, self_pickup, bonuses,
 * checks_bar, checks_delivery, checks_self_pickup, manager_id.
 * Авто: to_revenue, delta_plan, orders_count, avg_check_*, worker_hours, productivity_*.
 *
 * @property int $id
 * @property int $location_id
 * @property string $report_date
 * @property float|null $plan_daily
 * @property float|null $bar
 * @property float|null $delivery
 * @property float|null $self_pickup
 * @property float|null $bonuses
 * @property int|null $checks_bar
 * @property int|null $checks_delivery
 * @property int|null $checks_self_pickup
 * @property float|null $to_revenue
 * @property float|null $delta_plan
 * @property int|null $orders_count
 * @property float|null $avg_check_bar
 * @property float|null $avg_check_delivery
 * @property float|null $avg_check_self_pickup
 * @property float|null $worker_hours
 * @property float|null $productivity_orders
 * @property float|null $productivity_money
 * @property int|null $manager_id
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Location $location
 * @property User|null $manager
 */
class DailyReport extends ActiveRecord
{
    public static function tableName()
    {
        return 'daily_reports';
    }

    public function rules()
    {
        return [
            [['location_id', 'report_date'], 'required'],
            [['location_id', 'manager_id', 'orders_count', 'checks_bar', 'checks_delivery', 'checks_self_pickup'], 'integer'],
            [['report_date'], 'date', 'format' => 'php:Y-m-d'],
            [['plan_daily', 'bar', 'delivery', 'self_pickup', 'bonuses',
                'to_revenue', 'delta_plan', 'avg_check_bar', 'avg_check_delivery', 'avg_check_self_pickup',
                'worker_hours', 'productivity_orders', 'productivity_money'], 'number'],
            [['location_id', 'report_date'], 'unique', 'targetAttribute' => ['location_id', 'report_date']],
            [['location_id'], 'exist', 'skipOnError' => true, 'targetClass' => Location::class, 'targetAttribute' => ['location_id' => 'id']],
            [['manager_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['manager_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'location_id' => 'Точка',
            'report_date' => 'Дата',
            'plan_daily' => 'План на день',
            'bar' => 'БАР',
            'delivery' => 'ДОСТАВКА',
            'self_pickup' => 'САМОВЫВОЗ',
            'bonuses' => 'БОНУСЫ',
            'checks_bar' => 'Чеки БАР',
            'checks_delivery' => 'Чеки ДОСТАВКА',
            'checks_self_pickup' => 'Чеки САМОВЫВОЗ',
            'to_revenue' => 'ТО',
            'delta_plan' => 'DELTA',
            'orders_count' => 'Заказов',
            'avg_check_bar' => 'Ср. чек БАР',
            'avg_check_delivery' => 'Ср. чек ДОСТАВКА',
            'avg_check_self_pickup' => 'Ср. чек САМОВЫВОЗ',
            'worker_hours' => 'Часы (из графика)',
            'productivity_orders' => 'Произв. (заказы)',
            'productivity_money' => 'Произв. (₽)',
            'manager_id' => 'Менеджер смены',
        ];
    }

    public function getLocation()
    {
        return $this->hasOne(Location::class, ['id' => 'location_id']);
    }

    public function getManager()
    {
        return $this->hasOne(User::class, ['id' => 'manager_id']);
    }

    public function getDayOfWeekShort(): string
    {
        $map = ['ВС', 'ПН', 'ВТ', 'СР', 'ЧТ', 'ПТ', 'СБ'];
        $w = (int)date('w', strtotime($this->report_date));
        return $map[$w] ?? '';
    }
}
