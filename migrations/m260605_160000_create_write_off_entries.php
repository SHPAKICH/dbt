<?php

use yii\db\Migration;

/**
 * Списание продуктов по категориям (документация).
 */
class m260605_160000_create_write_off_entries extends Migration
{
    public function safeUp()
    {
        // Повторный запуск после частичного сбоя (таблица без FK)
        $this->execute('DROP TABLE IF EXISTS {{%write_off_entries}}');

        $this->createTable('{{%write_off_entries}}', [
            'id' => $this->primaryKey()->unsigned(),
            'location_id' => $this->integer()->unsigned()->notNull(),
            'entry_date' => $this->date()->notNull(),
            'category' => $this->string(32)->notNull()->comment('spoilage|hall_service|marketing|rework|tasting'),
            'product_id' => $this->string(64)->null()->comment('ID продукта в iiko'),
            'product_name' => $this->string(500)->notNull(),
            'weight' => $this->decimal(12, 4)->notNull()->defaultValue(0),
            'unit' => $this->string(32)->null(),
            'unit_cost' => $this->decimal(12, 4)->notNull()->defaultValue(0),
            'amount' => $this->decimal(12, 2)->notNull()->defaultValue(0),
            'created_by' => $this->integer()->unsigned()->null(),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx_write_off_loc_date', '{{%write_off_entries}}', ['location_id', 'entry_date']);
        $this->createIndex('idx_write_off_category', '{{%write_off_entries}}', ['category']);
        $this->addForeignKey(
            'fk_write_off_location',
            '{{%write_off_entries}}',
            'location_id',
            '{{%locations}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_write_off_user',
            '{{%write_off_entries}}',
            'created_by',
            '{{%users}}',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_write_off_user', '{{%write_off_entries}}');
        $this->dropForeignKey('fk_write_off_location', '{{%write_off_entries}}');
        $this->dropTable('{{%write_off_entries}}');
    }
}
