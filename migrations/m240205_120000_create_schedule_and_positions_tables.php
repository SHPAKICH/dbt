<?php

use yii\db\Migration;

/**
 * Миграции для:
 * - ставок по должностям (position_rates)
 * - таблицы смен (schedule_shifts)
 * - цвета для точек (locations.color)
 */
class m240205_120000_create_schedule_and_positions_tables extends Migration
{
    public function safeUp()
    {
        // Цвет точки
        $this->addColumn('{{%locations}}', 'color', $this->string(7)->defaultValue('#2b2b2b')->comment('Цвет точки в графике'));

        // Справочник ставок по должностям
        $this->createTable('{{%position_rates}}', [
            'id' => $this->primaryKey()->unsigned(),
            'code' => $this->string(50)->notNull()->unique()->comment('Код должности (enum из users.position)'),
            'name' => $this->string(255)->notNull()->comment('Название должности'),
            'hourly_rate' => $this->decimal(10, 2)->notNull()->defaultValue(0)->comment('Ставка, руб/час'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Предзаполнение по текущему enum
        $this->batchInsert('{{%position_rates}}', ['code', 'name', 'hourly_rate'], [
            ['manager', 'Управляющий', 350],
            ['location_manager', 'Менеджер точки', 300],
            ['senior_teamaker', 'Старший тимейкер', 300],
            ['teamaker', 'Тимейкер', 250],
            ['trainee', 'Стажёр', 200],
        ]);

        // Таблица смен
        $this->createTable('{{%schedule_shifts}}', [
            'id' => $this->primaryKey()->unsigned(),
            'user_id' => $this->integer()->unsigned()->notNull(),
            'location_id' => $this->integer()->unsigned()->notNull(),
            'date' => $this->date()->notNull(),
            'time_start' => $this->time()->null(),
            'time_end' => $this->time()->null(),
            'hours' => $this->decimal(5, 2)->notNull()->defaultValue(0),
            'is_night' => $this->boolean()->notNull()->defaultValue(0),
            'is_overtime' => $this->boolean()->notNull()->defaultValue(0),
            'is_day_off' => $this->boolean()->notNull()->defaultValue(0),
            'comment' => $this->string(255)->null(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx_schedule_shifts_location_date', '{{%schedule_shifts}}', ['location_id', 'date']);
        $this->createIndex('idx_schedule_shifts_user_date', '{{%schedule_shifts}}', ['user_id', 'date']);
        $this->createIndex('idx_schedule_shifts_flags', '{{%schedule_shifts}}', ['is_night', 'is_overtime', 'is_day_off']);
        $this->createIndex('uniq_schedule_shifts_user_location_date', '{{%schedule_shifts}}', ['user_id', 'location_id', 'date'], true);

        $this->addForeignKey(
            'fk_schedule_shifts_user',
            '{{%schedule_shifts}}',
            'user_id',
            '{{%users}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_schedule_shifts_location',
            '{{%schedule_shifts}}',
            'location_id',
            '{{%locations}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_schedule_shifts_location', '{{%schedule_shifts}}');
        $this->dropForeignKey('fk_schedule_shifts_user', '{{%schedule_shifts}}');
        $this->dropTable('{{%schedule_shifts}}');

        $this->dropTable('{{%position_rates}}');

        $this->dropColumn('{{%locations}}', 'color');
    }
}



