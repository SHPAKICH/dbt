<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * Форма для создания нового пользователя
 */
class UserCreateForm extends Model
{
    public $phone;
    public $email;
    public $password;
    public $confirm_password;
    public $position;
    public $location_id;
    public $first_name;
    public $last_name;
    public $is_active = 1;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        $rules = [
            [['phone', 'email', 'password', 'confirm_password', 'position'], 'required'],
            [['location_id', 'is_active'], 'integer'],
            [['phone'], 'string', 'max' => 20],
            [['email'], 'string', 'max' => 255],
            [['email'], 'email'],
            [['password', 'confirm_password'], 'string', 'min' => 6],
            [['first_name', 'last_name'], 'string', 'max' => 100],
            [['position'], 'in', 'range' => ['manager', 'location_manager', 'senior_teamaker', 'teamaker', 'trainee']],
            [['position'], 'validatePositionForManager'],
            [['phone'], 'unique', 'targetClass' => User::class],
            [['email'], 'unique', 'targetClass' => User::class],
            ['confirm_password', 'compare', 'compareAttribute' => 'password', 'message' => 'Пароли не совпадают'],
            [['location_id'], 'exist', 'skipOnError' => true, 'targetClass' => Location::class, 'targetAttribute' => ['location_id' => 'id']],
        ];
        
        // Для управляющего location_id обязателен
        $currentUser = Yii::$app->user->identity ?? null;
        if ($currentUser && $currentUser->position === 'manager' && !$currentUser->isAdmin()) {
            $rules[] = [['location_id'], 'required', 'message' => 'Необходимо выбрать точку'];
        }
        
        return $rules;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'phone' => 'Телефон',
            'email' => 'Email',
            'password' => 'Пароль',
            'confirm_password' => 'Подтверждение пароля',
            'position' => 'Должность',
            'location_id' => 'Точка',
            'first_name' => 'Имя',
            'last_name' => 'Фамилия',
            'is_active' => 'Активен',
        ];
    }

    /**
     * Сохраняет нового пользователя
     *
     * @return User|false
     */
    public function save()
    {
        if (!$this->validate()) {
            return false;
        }

        $user = new User();
        $user->phone = $this->phone;
        $user->email = $this->email;
        $user->setPassword($this->password);
        $user->position = $this->position;
        $user->location_id = $this->location_id;
        $user->first_name = $this->first_name;
        $user->last_name = $this->last_name;
        $user->is_active = $this->is_active;

        if ($user->save()) {
            return $user;
        }

        // Копируем ошибки из модели User
        foreach ($user->errors as $field => $errors) {
            foreach ($errors as $error) {
                $this->addError($field, $error);
            }
        }

        return false;
    }

    /**
     * Валидация должности для управляющего
     * Управляющий может создавать только до уровня "Менеджер точки"
     */
    public function validatePositionForManager($attribute, $params)
    {
        if (!$this->hasErrors($attribute)) {
            $currentUser = Yii::$app->user->identity;
            
            // Если текущий пользователь - управляющий (не админ)
            if ($currentUser && $currentUser->position === 'manager' && !$currentUser->isAdmin()) {
                $allowedPositions = ['location_manager', 'senior_teamaker', 'teamaker', 'trainee'];
                if (!in_array($this->position, $allowedPositions)) {
                    $this->addError($attribute, 'Управляющий может создавать пользователей только до уровня "Менеджер точки".');
                }
            }
        }
    }
}

