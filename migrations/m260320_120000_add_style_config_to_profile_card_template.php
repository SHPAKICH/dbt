<?php

use yii\db\Migration;

class m260320_120000_add_style_config_to_profile_card_template extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%profile_card_template}}', 'style_config', $this->text()->after('preview_image'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%profile_card_template}}', 'style_config');
    }
}
