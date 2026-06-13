<?php

use yii\db\Migration;

/**
 * Координаты точек для карты аналитики.
 */
class m260605_120000_add_location_coordinates extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%locations}}', 'lat', $this->decimal(10, 7)->null()->comment('Широта'));
        $this->addColumn('{{%locations}}', 'lng', $this->decimal(10, 7)->null()->comment('Долгота'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%locations}}', 'lng');
        $this->dropColumn('{{%locations}}', 'lat');
    }
}
