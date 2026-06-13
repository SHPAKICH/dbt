<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Реакция пользователя на сообщение бухгалтерского чата.
 *
 * @property int $id
 * @property int $message_id
 * @property int $user_id
 * @property string $emoji
 * @property string $created_at
 *
 * @property AccountingMessage $message
 * @property User $user
 */
class AccountingMessageReaction extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%accounting_message_reaction}}';
    }

    public function rules(): array
    {
        return [
            [['message_id', 'user_id', 'emoji'], 'required'],
            [['message_id', 'user_id'], 'integer'],
            [['created_at'], 'safe'],
            [['emoji'], 'string', 'max' => 32],
        ];
    }

    public function getMessage()
    {
        return $this->hasOne(AccountingMessage::class, ['id' => 'message_id']);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}

