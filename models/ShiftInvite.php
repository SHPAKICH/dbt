<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Инвайт на смену (усиление). Менеджер отправляет → сотрудник принимает/отказывает.
 *
 * @property int $id
 * @property int $user_id
 * @property int $location_id
 * @property string $date
 * @property string|null $time_start
 * @property string|null $time_end
 * @property int $invited_by
 * @property string $status pending|accepted|declined
 * @property string $created_at
 * @property string $updated_at
 *
 * @property User $user
 * @property User $invitedByUser
 * @property Location $location
 */
class ShiftInvite extends ActiveRecord
{
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_DECLINED = 'declined';

    public static function tableName()
    {
        return 'shift_invites';
    }

    public function rules()
    {
        return [
            [['user_id', 'location_id', 'date', 'invited_by'], 'required'],
            [['user_id', 'location_id', 'invited_by'], 'integer'],
            [['date'], 'date', 'format' => 'php:Y-m-d'],
            [['time_start', 'time_end'], 'match', 'pattern' => '/^\d{2}:\d{2}(:\d{2})?$/'],
            [['status'], 'in', 'range' => [self::STATUS_PENDING, self::STATUS_ACCEPTED, self::STATUS_DECLINED]],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            [['location_id'], 'exist', 'skipOnError' => true, 'targetClass' => Location::class, 'targetAttribute' => ['location_id' => 'id']],
            [['invited_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['invited_by' => 'id']],
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getInvitedByUser()
    {
        return $this->hasOne(User::class, ['id' => 'invited_by']);
    }

    public function getLocation()
    {
        return $this->hasOne(Location::class, ['id' => 'location_id']);
    }
}
