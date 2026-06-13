<?php

use yii\db\Migration;

class m260315_200000_create_photo_report_photos_table extends Migration
{
    public function safeUp()
    {
        // DDL не откатывается в MySQL — дропаем если осталась от неудачного прогона
        $this->execute('DROP TABLE IF EXISTS `photo_report_photos`');

        $this->createTable('photo_report_photos', [
            'id' => $this->primaryKey()->unsigned(),
            'location_id' => $this->integer()->unsigned()->notNull(),
            'user_id' => $this->integer()->unsigned()->notNull(),
            'report_type' => "ENUM('closing','opening') NOT NULL",
            'item_key' => $this->string(100)->notNull(),
            'file_path' => $this->string(500)->notNull(),
            'file_name' => $this->string(255)->notNull(),
            'created_at' => $this->dateTime()->defaultExpression('NOW()'),
        ]);

        $this->addForeignKey(
            'fk_photo_report_location',
            'photo_report_photos',
            'location_id',
            'locations',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_photo_report_user',
            'photo_report_photos',
            'user_id',
            'users',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createIndex('idx_photo_report_location_type', 'photo_report_photos', ['location_id', 'report_type', 'item_key']);
        $this->createIndex('idx_photo_report_created', 'photo_report_photos', ['created_at']);
    }

    public function safeDown()
    {
        $this->dropTable('photo_report_photos');
    }
}
