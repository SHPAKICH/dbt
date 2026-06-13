<?php

use yii\db\Migration;

/**
 * Добавляет location_id в accounting_message (если таблица уже создана без этой колонки).
 */
class m260312_210000_add_location_id_to_accounting_message extends Migration
{
    public function safeUp()
    {
        $columns = $this->db->getTableSchema('{{%accounting_message}}');
        if ($columns === null) {
            // Таблица не существует — пропускаем, создаётся другой миграцией
            return;
        }
        if (isset($columns->columns['location_id'])) {
            // Колонка уже есть
            return;
        }

        $this->addColumn('{{%accounting_message}}', 'location_id', $this->integer()->unsigned()->notNull()->defaultValue(0)->after('user_id'));
        $this->createIndex('idx_accounting_message_location_id', '{{%accounting_message}}', 'location_id');
        $this->addForeignKey(
            'fk_accounting_message_location_id',
            '{{%accounting_message}}',
            'location_id',
            '{{%locations}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $columns = $this->db->getTableSchema('{{%accounting_message}}');
        if ($columns === null || !isset($columns->columns['location_id'])) {
            return;
        }
        $this->dropForeignKey('fk_accounting_message_location_id', '{{%accounting_message}}');
        $this->dropIndex('idx_accounting_message_location_id', '{{%accounting_message}}');
        $this->dropColumn('{{%accounting_message}}', 'location_id');
    }
}
