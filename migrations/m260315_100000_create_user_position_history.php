<?php

use yii\db\Migration;

/**
 * История смены должностей для учёта при расчёте зарплаты (повышение/понижение с даты).
 */
class m260315_100000_create_user_position_history extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%user_position_history}}', [
            'id' => $this->primaryKey()->unsigned(),
            'user_id' => $this->integer()->unsigned()->notNull()->comment('Пользователь'),
            'position' => $this->string(50)->notNull()->comment('Код должности начиная с effective_from'),
            'effective_from' => $this->date()->notNull()->comment('С какой даты действует эта должность'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx_uph_user_effective', '{{%user_position_history}}', ['user_id', 'effective_from']);
        $this->addForeignKey(
            'fk_uph_user',
            '{{%user_position_history}}',
            'user_id',
            '{{%users}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Начальная запись для существующих пользователей: текущая должность с даты создания
        $this->execute("
            INSERT INTO {{%user_position_history}} (user_id, position, effective_from)
            SELECT id, position, COALESCE(DATE(created_at), '2000-01-01')
            FROM {{%users}}
        ");
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_uph_user', '{{%user_position_history}}');
        $this->dropTable('{{%user_position_history}}');
    }
}
