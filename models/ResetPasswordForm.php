<?php

namespace app\models;

use yii\base\Model;

class ResetPasswordForm extends Model
{
    public $token = '';
    public $password = '';
    public $passwordConfirm = '';

    public function rules(): array
    {
        return [
            [['token', 'password', 'passwordConfirm'], 'required'],
            [['token'], 'string', 'max' => 64],
            [['password'], 'string', 'min' => 6, 'max' => 72],
            [['passwordConfirm'], 'compare', 'compareAttribute' => 'password', 'message' => 'Пароли не совпадают'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'password' => 'Новый пароль',
            'passwordConfirm' => 'Подтверждение пароля',
        ];
    }
}
