<?php

namespace app\models;

use yii\db\ActiveRecord;

class ProfileCardTemplate extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%profile_card_template}}';
    }

    public function rules()
    {
        return [
            [['name', 'code'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['code'], 'string', 'max' => 100],
            [['code'], 'unique'],
            [['description'], 'string'],
            [['style_config'], 'safe'],
            [['preview_image'], 'string', 'max' => 255],
            [['css_class'], 'string', 'max' => 100],
            [['is_active'], 'boolean'],
            [['created_at', 'updated_at'], 'integer'],
        ];
    }

    public function getStyleConfigArray(): array
    {
        try {
            $val = $this->style_config;
        } catch (\Throwable $e) {
            return [];
        }
        if (empty($val)) {
            return [];
        }
        $data = json_decode($val, true);
        return is_array($data) ? $data : [];
    }

    public function setStyleConfigFromArray(array $config): void
    {
        $this->style_config = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function beforeSave($insert)
    {
        if ($insert && !$this->created_at) {
            $this->created_at = time();
        }
        $this->updated_at = time();
        return parent::beforeSave($insert);
    }
}
