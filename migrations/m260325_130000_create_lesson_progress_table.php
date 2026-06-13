<?php

use yii\db\Migration;

class m260325_130000_create_lesson_progress_table extends Migration
{
    public function safeUp()
    {
        $this->execute('DROP TABLE IF EXISTS {{%lesson_progress}}');

        $this->createTable('{{%lesson_progress}}', [
            'id' => $this->primaryKey()->unsigned(),
            'user_id' => $this->integer()->unsigned()->notNull(),
            'material_id' => $this->integer()->unsigned()->notNull(),
            'completed_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx_lesson_progress_unique', '{{%lesson_progress}}', ['user_id', 'material_id'], true);
        $this->addForeignKey('fk_lesson_progress_user', '{{%lesson_progress}}', 'user_id', '{{%users}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_lesson_progress_material', '{{%lesson_progress}}', 'material_id', '{{%training_materials}}', 'id', 'CASCADE', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropTable('{{%lesson_progress}}');
    }
}
