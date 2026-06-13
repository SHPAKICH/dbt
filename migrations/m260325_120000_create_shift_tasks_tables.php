<?php

use yii\db\Migration;

class m260325_120000_create_shift_tasks_tables extends Migration
{
    public function safeUp()
    {
        $this->execute('DROP TABLE IF EXISTS `shift_task_photos`');
        $this->execute('DROP TABLE IF EXISTS `shift_task_completions`');
        $this->execute('DROP TABLE IF EXISTS `shift_tasks`');

        $this->createTable('shift_tasks', [
            'id' => $this->primaryKey()->unsigned(),
            'location_id' => $this->integer()->unsigned()->notNull(),
            'created_by' => $this->integer()->unsigned()->notNull(),
            'title' => $this->string(500)->notNull(),
            'requires_photo' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'shift_date' => $this->date()->notNull(),
            'created_at' => $this->dateTime()->defaultExpression('NOW()'),
        ]);

        $this->addForeignKey('fk_shift_task_location', 'shift_tasks', 'location_id', 'locations', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_shift_task_creator', 'shift_tasks', 'created_by', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->createIndex('idx_shift_task_location_date', 'shift_tasks', ['location_id', 'shift_date']);

        $this->createTable('shift_task_completions', [
            'id' => $this->primaryKey()->unsigned(),
            'task_id' => $this->integer()->unsigned()->notNull(),
            'completed_by' => $this->integer()->unsigned()->notNull(),
            'comment' => $this->text()->null(),
            'completed_at' => $this->dateTime()->defaultExpression('NOW()'),
        ]);

        $this->addForeignKey('fk_stc_task', 'shift_task_completions', 'task_id', 'shift_tasks', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_stc_user', 'shift_task_completions', 'completed_by', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->createIndex('idx_stc_task', 'shift_task_completions', ['task_id']);

        $this->createTable('shift_task_photos', [
            'id' => $this->primaryKey()->unsigned(),
            'completion_id' => $this->integer()->unsigned()->notNull(),
            'file_path' => $this->string(500)->notNull(),
            'file_name' => $this->string(255)->notNull(),
            'created_at' => $this->dateTime()->defaultExpression('NOW()'),
        ]);

        $this->addForeignKey('fk_stp_completion', 'shift_task_photos', 'completion_id', 'shift_task_completions', 'id', 'CASCADE', 'CASCADE');
        $this->createIndex('idx_stp_created', 'shift_task_photos', ['created_at']);
    }

    public function safeDown()
    {
        $this->dropTable('shift_task_photos');
        $this->dropTable('shift_task_completions');
        $this->dropTable('shift_tasks');
    }
}
