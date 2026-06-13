<?php

use yii\db\Migration;

/**
 * Таблица предпочтительных графиков сотрудников (карта возможностей).
 */
class m240205_120300_create_user_availability_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%user_availability}}', [
            'id' => $this->primaryKey()->unsigned(),
            'user_id' => $this->integer()->unsigned()->notNull(),
            'weekday' => $this->tinyInteger()->notNull()->comment('1=Пн ... 7=Вс'),
            'time_start' => $this->time()->null(),
            'time_end' => $this->time()->null(),
        ]);

        $this->createIndex('idx_user_availability_user_weekday', '{{%user_availability}}', ['user_id', 'weekday'], true);

        $this->addForeignKey(
            'fk_user_availability_user',
            '{{%user_availability}}',
            'user_id',
            '{{%users}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_user_availability_user', '{{%user_availability}}');
        $this->dropTable('{{%user_availability}}');
    }
}



