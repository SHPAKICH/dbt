<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Файл, прикреплённый к сообщению бухгалтерского чата.
 *
 * @property int $id
 * @property int $message_id
 * @property string $path
 * @property string $name
 * @property string|null $mime_type
 *
 * @property AccountingMessage $message
 */
class AccountingMessageFile extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%accounting_message_file}}';
    }

    public function rules(): array
    {
        return [
            [['message_id', 'path', 'name'], 'required'],
            [['message_id'], 'integer'],
            [['path', 'name', 'mime_type'], 'string', 'max' => 255],
        ];
    }

    public function getMessage()
    {
        return $this->hasOne(AccountingMessage::class, ['id' => 'message_id']);
    }
}

