<?php

use yii\db\Migration;

class m260312_200000_create_accounting_chat_tables extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%accounting_message}}', [
            'id' => $this->primaryKey()->unsigned(),
            'user_id' => $this->integer()->unsigned()->notNull(),
            'location_id' => $this->integer()->unsigned()->notNull(),
            'text' => $this->text(),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx_accounting_message_user_id', '{{%accounting_message}}', 'user_id');
        $this->createIndex('idx_accounting_message_location_id', '{{%accounting_message}}', 'location_id');
        $this->addForeignKey(
            'fk_accounting_message_user_id',
            '{{%accounting_message}}',
            'user_id',
            '{{%users}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_accounting_message_location_id',
            '{{%accounting_message}}',
            'location_id',
            '{{%locations}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createTable('{{%accounting_message_file}}', [
            'id' => $this->primaryKey()->unsigned(),
            'message_id' => $this->integer()->unsigned()->notNull(),
            'path' => $this->string(255)->notNull(),
            'name' => $this->string(255)->notNull(),
            'mime_type' => $this->string(255)->null(),
        ]);

        $this->createIndex('idx_accounting_message_file_message_id', '{{%accounting_message_file}}', 'message_id');
        $this->addForeignKey(
            'fk_accounting_message_file_message_id',
            '{{%accounting_message_file}}',
            'message_id',
            '{{%accounting_message}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_accounting_message_file_message_id', '{{%accounting_message_file}}');
        $this->dropTable('{{%accounting_message_file}}');

        $this->dropForeignKey('fk_accounting_message_location_id', '{{%accounting_message}}');
        $this->dropForeignKey('fk_accounting_message_user_id', '{{%accounting_message}}');
        $this->dropTable('{{%accounting_message}}');
    }
}

