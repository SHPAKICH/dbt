<?php

use yii\db\Migration;

/**
 * Явное название точки в iiko для сопоставления с подразделениями/складами.
 */
class m260605_150000_add_location_iiko_name extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%locations}}',
            'iiko_name',
            $this->string(255)->null()->comment('Название точки в iiko')
        );
    }

    public function safeDown()
    {
        $this->dropColumn('{{%locations}}', 'iiko_name');
    }
}
