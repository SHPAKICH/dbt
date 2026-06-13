<?php

use yii\db\Migration;

/**
 * Настраиваемые надбавки КРО (руб/час) по порогам прохождения проверки.
 */
class m260605_100000_create_kro_bonus_bands extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%kro_bonus_bands}}', [
            'id' => $this->primaryKey()->unsigned(),
            'min_percent' => $this->decimal(5, 2)->notNull()->comment('Минимальный % прохождения'),
            'hourly_bonus' => $this->decimal(10, 2)->notNull()->defaultValue(0)->comment('Надбавка, руб/час'),
            'sort_order' => $this->smallInteger()->notNull()->defaultValue(0)->comment('Порядок проверки (больше — выше)'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        $this->batchInsert('{{%kro_bonus_bands}}', ['min_percent', 'hourly_bonus', 'sort_order'], [
            [99, 50, 50],
            [95, 40, 40],
            [91, 30, 30],
            [88, 20, 20],
            [85, 10, 10],
            [0, 0, 0],
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%kro_bonus_bands}}');
    }
}
