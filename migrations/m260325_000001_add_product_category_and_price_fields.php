<?php

use yii\db\Migration;

class m260325_000001_add_product_category_and_price_fields extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%products}}', 'category', $this->string(100)->null()->after('unit'));
        $this->addColumn('{{%products}}', 'price_per_unit', $this->decimal(10, 2)->null()->after('category'));
        $this->addColumn('{{%products}}', 'package_quantity', $this->string(100)->null()->after('price_per_unit'));
        $this->addColumn('{{%products}}', 'package_description', $this->string(255)->null()->after('package_quantity'));
        $this->addColumn('{{%products}}', 'sort_order', $this->integer()->notNull()->defaultValue(0)->after('package_description'));

        $this->createIndex('idx_products_category', '{{%products}}', 'category');
        $this->createIndex('idx_products_sort_order', '{{%products}}', ['category', 'sort_order']);
    }

    public function safeDown()
    {
        $this->dropIndex('idx_products_sort_order', '{{%products}}');
        $this->dropIndex('idx_products_category', '{{%products}}');

        $this->dropColumn('{{%products}}', 'sort_order');
        $this->dropColumn('{{%products}}', 'package_description');
        $this->dropColumn('{{%products}}', 'package_quantity');
        $this->dropColumn('{{%products}}', 'price_per_unit');
        $this->dropColumn('{{%products}}', 'category');
    }
}
