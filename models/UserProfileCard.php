<?php

namespace app\models;

use yii\db\ActiveRecord;

class UserProfileCard extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%user_profile_card}}';
    }

    public function getTemplate()
    {
        return $this->hasOne(ProfileCardTemplate::class, ['id' => 'template_id']);
    }
}

