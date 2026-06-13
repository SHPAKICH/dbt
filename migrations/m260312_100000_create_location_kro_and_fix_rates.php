<?php

use yii\db\Migration;

/**
 * Таблица КРО (процент прохождения проверки точки за месяц).
 * Обновление ставок: Стажёр 250, Менеджер точки 350.
 */
class m260312_100000_create_location_kro_and_fix_rates extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%location_kro}}', [
            'id' => $this->primaryKey()->unsigned(),
            'location_id' => $this->integer()->unsigned()->notNull(),
            'year' => $this->smallInteger()->notNull(),
            'month' => $this->tinyInteger()->notNull(),
            'pass_percent' => $this->decimal(5, 2)->notNull()->defaultValue(0)->comment('Процент прохождения проверки 0-100'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);
        $this->createIndex('uniq_location_kro_location_year_month', '{{%location_kro}}', ['location_id', 'year', 'month'], true);
        $this->addForeignKey(
            'fk_location_kro_location',
            '{{%location_kro}}',
            'location_id',
            '{{%locations}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Ставки по ТЗ: Стажёр 250, Тимейкер 250, Старший тимейкер 300, Менеджер 350
        $this->update('{{%position_rates}}', ['hourly_rate' => 250], ['code' => 'trainee']);
        $this->update('{{%position_rates}}', ['hourly_rate' => 350], ['code' => 'location_manager']);
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_location_kro_location', '{{%location_kro}}');
        $this->dropTable('{{%location_kro}}');
        $this->update('{{%position_rates}}', ['hourly_rate' => 200], ['code' => 'trainee']);
        $this->update('{{%position_rates}}', ['hourly_rate' => 300], ['code' => 'location_manager']);
    }
}
