<?php

use yii\db\Migration;

/**
 * Таблицы для модуля заказов поставок.
 */
class m240205_120100_create_supply_tables extends Migration
{
    public function safeUp()
    {
        // Справочник товаров
        $this->createTable('{{%products}}', [
            'id' => $this->primaryKey()->unsigned(),
            'name' => $this->string(255)->notNull()->comment('Название товара'),
            'sku' => $this->string(100)->null()->comment('Артикул'),
            'unit' => $this->string(50)->notNull()->defaultValue('шт')->comment('Единица измерения'),
            'is_active' => $this->boolean()->notNull()->defaultValue(1),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Заказ поставки
        $this->createTable('{{%supply_orders}}', [
            'id' => $this->primaryKey()->unsigned(),
            'location_id' => $this->integer()->unsigned()->notNull(),
            'created_by' => $this->integer()->unsigned()->notNull(),
            'status' => $this->string(20)->notNull()->defaultValue('new'),
            'comment' => $this->text()->null(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->addForeignKey(
            'fk_supply_orders_location',
            '{{%supply_orders}}',
            'location_id',
            '{{%locations}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_supply_orders_user',
            '{{%supply_orders}}',
            'created_by',
            '{{%users}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Позиции заказа
        $this->createTable('{{%supply_order_items}}', [
            'id' => $this->primaryKey()->unsigned(),
            'order_id' => $this->integer()->unsigned()->notNull(),
            'product_id' => $this->integer()->unsigned()->notNull(),
            'quantity' => $this->decimal(10, 3)->notNull()->defaultValue(0),
        ]);

        $this->addForeignKey(
            'fk_supply_order_items_order',
            '{{%supply_order_items}}',
            'order_id',
            '{{%supply_orders}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_supply_order_items_product',
            '{{%supply_order_items}}',
            'product_id',
            '{{%products}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_supply_order_items_product', '{{%supply_order_items}}');
        $this->dropForeignKey('fk_supply_order_items_order', '{{%supply_order_items}}');
        $this->dropTable('{{%supply_order_items}}');

        $this->dropForeignKey('fk_supply_orders_user', '{{%supply_orders}}');
        $this->dropForeignKey('fk_supply_orders_location', '{{%supply_orders}}');
        $this->dropTable('{{%supply_orders}}');

        $this->dropTable('{{%products}}');
    }
}



