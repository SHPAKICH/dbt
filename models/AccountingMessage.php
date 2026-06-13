<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Сообщение в чате «Бухгалтерия».
 *
 * @property int $id
 * @property int $user_id
 * @property int $location_id
 * @property string|null $text
 * @property string $created_at
 *
 * @property User $user
 * @property \app\models\Location $location
 * @property AccountingMessageFile[] $files
 * @property AccountingMessageReaction[] $reactions
 */
class AccountingMessage extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%accounting_message}}';
    }

    public function rules(): array
    {
        return [
            [['user_id', 'location_id'], 'required'],
            [['user_id', 'location_id'], 'integer'],
            [['text'], 'string'],
            [['created_at'], 'safe'],
        ];
    }

    public function beforeSave($insert): bool
    {
        if ($insert && !$this->created_at) {
            $this->created_at = date('Y-m-d H:i:s');
        }
        return parent::beforeSave($insert);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getLocation()
    {
        return $this->hasOne(\app\models\Location::class, ['id' => 'location_id']);
    }

    public function getFiles()
    {
        return $this->hasMany(AccountingMessageFile::class, ['message_id' => 'id']);
    }

    public function getReactions()
    {
        return $this->hasMany(AccountingMessageReaction::class, ['message_id' => 'id']);
    }
}

