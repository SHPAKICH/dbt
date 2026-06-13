<?php

use yii\db\Migration;

/**
 * Добавляет тестовую карточку "Золотая" для геймификации профиля.
 */
class m260311_200000_add_gold_profile_card_template extends Migration
{
    public function safeUp()
    {
        $exists = $this->db->createCommand(
            'SELECT 1 FROM {{%profile_card_template}} WHERE [[code]] = :code',
            [':code' => 'gold']
        )->queryScalar();
        if ($exists) {
            return;
        }
        $time = time();
        $this->insert('{{%profile_card_template}}', [
            'name' => 'Золотая',
            'code' => 'gold',
            'description' => 'Золотая карточка — тестовый стиль для сайдбара и графика',
            'preview_image' => null,
            'css_class' => 'profile-card-gold',
            'is_active' => 1,
            'created_at' => $time,
            'updated_at' => $time,
        ]);
    }

    public function safeDown()
    {
        $this->delete('{{%profile_card_template}}', ['code' => 'gold']);
    }
}
