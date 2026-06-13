<?php

use yii\db\Migration;

/**
 * Сброс пароля и привязка Telegram для уведомлений.
 */
class m260602_100000_add_password_reset_and_telegram_link extends Migration
{
    public function safeUp()
    {
        $table = $this->db->getSchema()->getTableSchema('{{%users}}');
        if (!$table) {
            return;
        }

        if (!isset($table->columns['telegram_chat_id'])) {
            $this->addColumn('{{%users}}', 'telegram_chat_id', $this->bigInteger()->null()->comment('Telegram chat_id для уведомлений'));
        }
        if (!isset($table->columns['telegram_link_code'])) {
            $this->addColumn('{{%users}}', 'telegram_link_code', $this->string(8)->null()->comment('Код привязки Telegram'));
        }
        if (!isset($table->columns['telegram_link_expires_at'])) {
            $this->addColumn('{{%users}}', 'telegram_link_expires_at', $this->dateTime()->null()->comment('Срок действия кода привязки'));
        }
        if (!isset($table->columns['password_reset_token_hash'])) {
            $this->addColumn('{{%users}}', 'password_reset_token_hash', $this->string(64)->null()->comment('Хеш токена сброса пароля'));
        }
        if (!isset($table->columns['password_reset_expires_at'])) {
            $this->addColumn('{{%users}}', 'password_reset_expires_at', $this->dateTime()->null()->comment('Срок действия токена сброса'));
        }
    }

    public function safeDown()
    {
        $table = $this->db->getSchema()->getTableSchema('{{%users}}');
        if (!$table) {
            return;
        }

        foreach (['password_reset_expires_at', 'password_reset_token_hash', 'telegram_link_expires_at', 'telegram_link_code', 'telegram_chat_id'] as $col) {
            if (isset($table->columns[$col])) {
                $this->dropColumn('{{%users}}', $col);
            }
        }
    }
}
