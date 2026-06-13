<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $user_id
 * @property string $endpoint
 * @property string $p256dh
 * @property string $auth
 * @property string|null $user_agent
 * @property string $created_at
 * @property User $user
 */
class PushSubscription extends ActiveRecord
{
    public static function tableName()
    {
        return 'push_subscriptions';
    }

    public function rules()
    {
        return [
            [['user_id', 'endpoint', 'p256dh', 'auth'], 'required'],
            [['user_id'], 'integer'],
            [['endpoint'], 'string'],
            [['p256dh', 'auth'], 'string', 'max' => 255],
            [['user_agent'], 'string', 'max' => 500],
            [['user_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
