<?php

use yii\db\Migration;

class m260316_000000_add_report_date_to_photo_report_photos extends Migration
{
    public function safeUp()
    {
        $this->addColumn('photo_report_photos', 'report_date', $this->date()->null()->after('item_key'));

        // Заполняем дату отчета для уже существующих записей
        $this->execute('UPDATE `photo_report_photos` SET `report_date` = DATE(`created_at`) WHERE `report_date` IS NULL');

        $this->alterColumn('photo_report_photos', 'report_date', $this->date()->notNull());
        $this->createIndex(
            'idx_photo_report_location_type_item_date',
            'photo_report_photos',
            ['location_id', 'report_type', 'item_key', 'report_date']
        );
        $this->createIndex(
            'idx_photo_report_date',
            'photo_report_photos',
            ['report_date']
        );
    }

    public function safeDown()
    {
        $this->dropIndex('idx_photo_report_date', 'photo_report_photos');
        $this->dropIndex('idx_photo_report_location_type_item_date', 'photo_report_photos');
        $this->dropColumn('photo_report_photos', 'report_date');
    }
}
