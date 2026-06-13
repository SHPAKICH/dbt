<?php

use yii\db\Migration;

/**
 * Таблица новостей для информирования пользователей.
 */
class m260305_230000_create_news_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%news}}', [
            'id' => $this->primaryKey()->unsigned(),
            'title' => $this->string(255)->notNull(),
            'body' => $this->text()->notNull(),
            'author_id' => $this->integer()->unsigned()->null(),
            'is_published' => $this->smallInteger()->notNull()->defaultValue(1),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'published_at' => $this->timestamp()->null(),
        ]);
        $this->createIndex('idx_news_published_at', '{{%news}}', ['is_published', 'published_at']);
        $this->createIndex('idx_news_created_at', '{{%news}}', ['created_at']);
        $this->addForeignKey(
            'fk_news_author',
            '{{%news}}',
            'author_id',
            '{{%users}}',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_news_author', '{{%news}}');
        $this->dropTable('{{%news}}');
    }
}

