<?php

use yii\db\Migration;

/**
 * Таблицы для редактируемой матрицы ресурсов/возможностей.
 */
class m240205_120200_create_resource_matrix_tables extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%resource_matrices}}', [
            'id' => $this->primaryKey()->unsigned(),
            'name' => $this->string(255)->notNull(),
            'code' => $this->string(100)->notNull()->unique(),
            'description' => $this->text()->null(),
        ]);

        $this->createTable('{{%resource_rows}}', [
            'id' => $this->primaryKey()->unsigned(),
            'matrix_id' => $this->integer()->unsigned()->notNull(),
            'label' => $this->string(255)->notNull(),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
        ]);
        $this->addForeignKey(
            'fk_resource_rows_matrix',
            '{{%resource_rows}}',
            'matrix_id',
            '{{%resource_matrices}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createTable('{{%resource_columns}}', [
            'id' => $this->primaryKey()->unsigned(),
            'matrix_id' => $this->integer()->unsigned()->notNull(),
            'label' => $this->string(255)->notNull(),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
        ]);
        $this->addForeignKey(
            'fk_resource_columns_matrix',
            '{{%resource_columns}}',
            'matrix_id',
            '{{%resource_matrices}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createTable('{{%resource_cells}}', [
            'id' => $this->primaryKey()->unsigned(),
            'row_id' => $this->integer()->unsigned()->notNull(),
            'column_id' => $this->integer()->unsigned()->notNull(),
            'value' => $this->text()->null(),
            'version' => $this->integer()->notNull()->defaultValue(1),
            'updated_by' => $this->integer()->unsigned()->null(),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->addForeignKey(
            'fk_resource_cells_row',
            '{{%resource_cells}}',
            'row_id',
            '{{%resource_rows}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_resource_cells_column',
            '{{%resource_cells}}',
            'column_id',
            '{{%resource_columns}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_resource_cells_user',
            '{{%resource_cells}}',
            'updated_by',
            '{{%users}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->createIndex(
            'uniq_resource_cell_row_column',
            '{{%resource_cells}}',
            ['row_id', 'column_id'],
            true
        );

        $this->createTable('{{%resource_cell_history}}', [
            'id' => $this->primaryKey()->unsigned(),
            'cell_id' => $this->integer()->unsigned()->notNull(),
            'old_value' => $this->text()->null(),
            'new_value' => $this->text()->null(),
            'version' => $this->integer()->notNull(),
            'changed_by' => $this->integer()->unsigned()->null(),
            'changed_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->addForeignKey(
            'fk_resource_cell_history_cell',
            '{{%resource_cell_history}}',
            'cell_id',
            '{{%resource_cells}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_resource_cell_history_user',
            '{{%resource_cell_history}}',
            'changed_by',
            '{{%users}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        // Создаём одну базовую матрицу по умолчанию
        $this->insert('{{%resource_matrices}}', [
            'name' => 'Матрица ресурсов',
            'code' => 'default',
            'description' => 'Таблица возможностей/ресурсов',
        ]);
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_resource_cell_history_user', '{{%resource_cell_history}}');
        $this->dropForeignKey('fk_resource_cell_history_cell', '{{%resource_cell_history}}');
        $this->dropTable('{{%resource_cell_history}}');

        $this->dropForeignKey('fk_resource_cells_user', '{{%resource_cells}}');
        $this->dropForeignKey('fk_resource_cells_column', '{{%resource_cells}}');
        $this->dropForeignKey('fk_resource_cells_row', '{{%resource_cells}}');
        $this->dropTable('{{%resource_cells}}');

        $this->dropForeignKey('fk_resource_columns_matrix', '{{%resource_columns}}');
        $this->dropTable('{{%resource_columns}}');

        $this->dropForeignKey('fk_resource_rows_matrix', '{{%resource_rows}}');
        $this->dropTable('{{%resource_rows}}');

        $this->dropTable('{{%resource_matrices}}');
    }
}



