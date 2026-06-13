<?php

use yii\db\Migration;

/**
 * Таблица инвайтов на смену (усиление).
 * Менеджер отправляет инвайт → сотрудник принимает или отказывает во вкладке Уведомления.
 */
class m260311_120000_create_shift_invites_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%shift_invites}}', [
            'id' => $this->primaryKey()->unsigned(),
            'user_id' => $this->integer()->unsigned()->notNull()->comment('Кого приглашают'),
            'location_id' => $this->integer()->unsigned()->notNull(),
            'date' => $this->date()->notNull(),
            'time_start' => $this->time()->null()->comment('Предложенное начало'),
            'time_end' => $this->time()->null()->comment('Предложенное окончание'),
            'invited_by' => $this->integer()->unsigned()->notNull()->comment('Кто отправил (менеджер)'),
            'status' => $this->string(20)->notNull()->defaultValue('pending')->comment('pending|accepted|declined'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx_shift_invites_user_status', '{{%shift_invites}}', ['user_id', 'status']);
        $this->createIndex('idx_shift_invites_location_date', '{{%shift_invites}}', ['location_id', 'date']);

        $this->addForeignKey(
            'fk_shift_invites_user',
            '{{%shift_invites}}',
            'user_id',
            '{{%users}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_shift_invites_location',
            '{{%shift_invites}}',
            'location_id',
            '{{%locations}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_shift_invites_invited_by',
            '{{%shift_invites}}',
            'invited_by',
            '{{%users}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_shift_invites_invited_by', '{{%shift_invites}}');
        $this->dropForeignKey('fk_shift_invites_location', '{{%shift_invites}}');
        $this->dropForeignKey('fk_shift_invites_user', '{{%shift_invites}}');
        $this->dropTable('{{%shift_invites}}');
    }
}
