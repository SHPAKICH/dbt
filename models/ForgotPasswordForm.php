<?php

namespace app\models;

use Yii;
use yii\base\Model;

class ForgotPasswordForm extends Model
{
    public $emailOrPhone = '';
    /** @var string email|telegram|auto */
    public $channel = 'auto';

    public function rules(): array
    {
        return [
            [['emailOrPhone'], 'required'],
            [['emailOrPhone'], 'string', 'max' => 255],
            [['emailOrPhone'], 'trim'],
            [['channel'], 'in', 'range' => ['email', 'telegram', 'auto']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'emailOrPhone' => 'Email или телефон',
            'channel' => 'Способ восстановления',
        ];
    }

    public function findUser(): ?User
    {
        return User::findByEmailOrPhone(trim($this->emailOrPhone));
    }

    public function isRateLimited(): bool
    {
        $key = 'pwd_reset_' . md5(Yii::$app->request->userIP . '|' . trim($this->emailOrPhone));
        $cache = Yii::$app->cache;
        $attempts = (int) $cache->get($key);
        if ($attempts >= 5) {
            return true;
        }
        $cache->set($key, $attempts + 1, 3600);
        return false;
    }
}
