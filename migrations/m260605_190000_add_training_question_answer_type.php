<?php

use yii\db\Migration;

/**
 * Тип ответа на вопрос теста: один или несколько правильных вариантов.
 */
class m260605_190000_add_training_question_answer_type extends Migration
{
    public function safeUp()
    {
        $table = $this->db->schema->getTableSchema('{{%training_questions}}');
        if (!$table) {
            return;
        }

        if (!isset($table->columns['answer_type'])) {
            $this->addColumn('{{%training_questions}}', 'answer_type', $this->string(20)->notNull()->defaultValue('single')->after('options'));
        }

        if (isset($table->columns['correct_answer'])) {
            $this->alterColumn('{{%training_questions}}', 'correct_answer', $this->string(500)->null());
        }
    }

    public function safeDown()
    {
        $table = $this->db->schema->getTableSchema('{{%training_questions}}');
        if (!$table) {
            return;
        }

        if (isset($table->columns['answer_type'])) {
            $this->dropColumn('{{%training_questions}}', 'answer_type');
        }
    }
}
