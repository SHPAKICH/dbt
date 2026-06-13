<?php

use yii\db\Migration;

class m260319_120000_create_accounting_message_reaction_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%accounting_message_reaction}}', [
            'id' => $this->primaryKey()->unsigned(),
            'message_id' => $this->integer()->unsigned()->notNull(),
            'user_id' => $this->integer()->unsigned()->notNull(),
            'emoji' => $this->string(32)->notNull(),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex(
            'idx_accounting_message_reaction_message_user_unique',
            '{{%accounting_message_reaction}}',
            ['message_id', 'user_id'],
            true
        );
        $this->createIndex(
            'idx_accounting_message_reaction_message_id',
            '{{%accounting_message_reaction}}',
            'message_id'
        );
        $this->createIndex(
            'idx_accounting_message_reaction_user_id',
            '{{%accounting_message_reaction}}',
            'user_id'
        );

        $this->addForeignKey(
            'fk_accounting_message_reaction_message_id',
            '{{%accounting_message_reaction}}',
            'message_id',
            '{{%accounting_message}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_accounting_message_reaction_user_id',
            '{{%accounting_message_reaction}}',
            'user_id',
            '{{%users}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_accounting_message_reaction_user_id', '{{%accounting_message_reaction}}');
        $this->dropForeignKey('fk_accounting_message_reaction_message_id', '{{%accounting_message_reaction}}');
        $this->dropTable('{{%accounting_message_reaction}}');
    }
}

