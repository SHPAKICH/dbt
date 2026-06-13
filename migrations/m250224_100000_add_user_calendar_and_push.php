<?php

use yii\db\Migration;

/**
 * Добавляет поля для календаря сотрудников (др, телеграм, аттестация) и таблицу подписок на push.
 */
class m250224_100000_add_user_calendar_and_push extends Migration
{
    public function safeUp()
    {
        $table = $this->db->getSchema()->getTableSchema('{{%users}}');
        if ($table && !isset($table->columns['birthday'])) {
            $this->addColumn('{{%users}}', 'birthday', $this->date()->null()->comment('День рождения'));
        }
        if ($table && !isset($table->columns['telegram'])) {
            $this->addColumn('{{%users}}', 'telegram', $this->string(100)->null()->comment('Telegram'));
        }
        if ($table && !isset($table->columns['certification_date'])) {
            $this->addColumn('{{%users}}', 'certification_date', $this->date()->null()->comment('Дата аттестации'));
        }

        $this->createTable('{{%push_subscriptions}}', [
            'id' => $this->primaryKey()->unsigned(),
            'user_id' => $this->integer()->unsigned()->notNull(),
            'endpoint' => $this->text()->notNull(),
            'p256dh' => $this->string(255)->notNull(),
            'auth' => $this->string(255)->notNull(),
            'user_agent' => $this->string(500)->null(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->createIndex('idx_push_subscriptions_user_id', '{{%push_subscriptions}}', ['user_id']);
        $this->addForeignKey(
            'fk_push_subscriptions_user',
            '{{%push_subscriptions}}',
            'user_id',
            '{{%users}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_push_subscriptions_user', '{{%push_subscriptions}}');
        $this->dropTable('{{%push_subscriptions}}');

        $table = $this->db->getSchema()->getTableSchema('{{%users}}');
        if ($table && isset($table->columns['birthday'])) {
            $this->dropColumn('{{%users}}', 'birthday');
        }
        if ($table && isset($table->columns['telegram'])) {
            $this->dropColumn('{{%users}}', 'telegram');
        }
        if ($table && isset($table->columns['certification_date'])) {
            $this->dropColumn('{{%users}}', 'certification_date');
        }
    }
}
