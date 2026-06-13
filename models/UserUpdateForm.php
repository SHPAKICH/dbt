<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * Форма для обновления данных пользователя администратором
 */
class UserUpdateForm extends Model
{
    public $id;
    public $phone;
    public $email;
    public $position;
    public $location_id;
    public $first_name;
    public $last_name;
    public $birthday;
    public $telegram;
    public $certification_date;
    public $is_active;
    public $new_password;
    public $confirm_password;

    private $_user;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['phone', 'email', 'position'], 'required'],
            [['location_id', 'is_active', 'id'], 'integer'],
            [['phone'], 'string', 'max' => 20],
            [['email'], 'string', 'max' => 255],
            [['email'], 'email'],
            [['first_name', 'last_name'], 'string', 'max' => 100],
            [['birthday', 'certification_date'], 'date', 'format' => 'php:Y-m-d'],
            [['telegram'], 'string', 'max' => 100],
            [['position'], 'in', 'range' => ['manager', 'location_manager', 'senior_teamaker', 'teamaker', 'trainee']],
            [
                ['phone'], 
                'unique', 
                'targetClass' => User::class, 
                'filter' => function($query) {
                    return $query->andWhere(['!=', 'id', $this->id]);
                }
            ],
            [
                ['email'], 
                'unique', 
                'targetClass' => User::class, 
                'filter' => function($query) {
                    return $query->andWhere(['!=', 'id', $this->id]);
                }
            ],
            [['new_password', 'confirm_password'], 'string', 'min' => 6],
            ['confirm_password', 'compare', 'compareAttribute' => 'new_password', 'message' => 'Пароли не совпадают'],
            [['location_id'], 'exist', 'skipOnError' => true, 'targetClass' => Location::class, 'targetAttribute' => ['location_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'phone' => 'Телефон',
            'email' => 'Email',
            'position' => 'Должность',
            'location_id' => 'Точка',
            'first_name' => 'Имя',
            'last_name' => 'Фамилия',
            'birthday' => 'День рождения',
            'telegram' => 'Telegram',
            'certification_date' => 'Дата аттестации',
            'is_active' => 'Активен',
            'new_password' => 'Новый пароль',
            'confirm_password' => 'Подтверждение пароля',
        ];
    }

    /**
     * Загружает данные пользователя в форму
     *
     * @param User $user
     */
    public function loadUser($user)
    {
        $this->id = $user->id;
        $this->phone = $user->phone;
        $this->email = $user->email;
        $this->position = $user->position;
        $this->location_id = $user->location_id;
        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name;
        $this->birthday = $user->hasAttribute('birthday') ? $user->birthday : null;
        $this->telegram = $user->hasAttribute('telegram') ? $user->telegram : null;
        $this->certification_date = $user->hasAttribute('certification_date') ? $user->certification_date : null;
        $this->is_active = $user->is_active;
        $this->_user = $user;
    }

    /**
     * Сохраняет изменения пользователя
     *
     * @return bool
     */
    public function save()
    {
        if (!$this->validate()) {
            return false;
        }

        $user = $this->_user;
        if (!$user) {
            $this->addError('id', 'Пользователь не найден');
            return false;
        }

        $user->phone = $this->phone;
        $user->email = $this->email;
        $user->position = $this->position;
        $user->location_id = $this->location_id;
        $user->first_name = $this->first_name;
        $user->last_name = $this->last_name;
        if ($user->hasAttribute('birthday')) {
            $user->birthday = $this->birthday ?: null;
        }
        if ($user->hasAttribute('telegram')) {
            $user->telegram = $this->telegram ?: null;
        }
        if ($user->hasAttribute('certification_date')) {
            $user->certification_date = $this->certification_date ?: null;
        }
        $user->is_active = $this->is_active;

        // Обновляем пароль, если указан новый
        if (!empty($this->new_password)) {
            $user->setPassword($this->new_password);
        }

        return $user->save();
    }

    /**
     * Получает пользователя
     *
     * @return User|null
     */
    public function getUser()
    {
        return $this->_user;
    }
}

