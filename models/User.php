<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * User model
 *
 * @property integer $id
 * @property string $phone
 * @property string $email
 * @property string $password_hash
 * @property string $position
 * @property integer $location_id
 * @property string $avatar
 * @property string $first_name
 * @property string $last_name
 * @property string|null $birthday
 * @property string|null $telegram
 * @property int|null $telegram_chat_id
 * @property string|null $telegram_link_code
 * @property string|null $telegram_link_expires_at
 * @property string|null $password_reset_token_hash
 * @property string|null $password_reset_expires_at
 * @property string|null $certification_date
 * @property integer $is_active
 * @property integer $is_admin
 * @property integer $has_super_access
 * @property integer $is_main_admin
 * @property string $auth_key
 * @property string $created_at
 * @property string $updated_at
 */
class User extends ActiveRecord implements IdentityInterface
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'users';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['phone', 'email', 'password_hash', 'position'], 'required'],
            [['location_id', 'is_active', 'is_admin', 'has_super_access', 'is_main_admin'], 'integer'],
            [['phone'], 'string', 'max' => 20],
            [['email'], 'string', 'max' => 255],
            [['email'], 'email'],
            [['password_hash', 'avatar'], 'string', 'max' => 500],
            [['first_name', 'last_name'], 'string', 'max' => 100],
            [['birthday', 'certification_date'], 'date', 'format' => 'php:Y-m-d'],
            [['telegram'], 'string', 'max' => 100],
            [['telegram_chat_id'], 'integer'],
            [['telegram_link_code'], 'string', 'max' => 8],
            [['telegram_link_expires_at', 'password_reset_expires_at'], 'safe'],
            [['password_reset_token_hash'], 'string', 'max' => 64],
            [['auth_key'], 'string', 'max' => 32, 'skipOnEmpty' => true],
            [['position'], 'in', 'range' => ['manager', 'location_manager', 'senior_teamaker', 'teamaker', 'trainee']],
            [['phone'], 'unique'],
            [['email'], 'unique'],
            [['location_id'], 'exist', 'skipOnError' => true, 'targetClass' => Location::class, 'targetAttribute' => ['location_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->hasAttribute('is_main_admin') && (bool) $this->is_main_admin) {
                if ($this->hasAttribute('is_admin')) {
                    $this->is_admin = 1;
                }
                if ($this->hasAttribute('has_super_access')) {
                    $this->has_super_access = 0;
                }
            }

            // Проверяем, существует ли поле auth_key в таблице
            $tableSchema = static::getTableSchema();
            if ($tableSchema && isset($tableSchema->columns['auth_key'])) {
                if ($insert && (empty($this->auth_key) || $this->auth_key === null)) {
                    $this->generateAuthKey();
                }
            }
            return true;
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'phone' => 'Телефон',
            'email' => 'Email',
            'password_hash' => 'Пароль',
            'position' => 'Должность',
            'location_id' => 'Точка',
            'avatar' => 'Аватар',
            'first_name' => 'Имя',
            'last_name' => 'Фамилия',
            'birthday' => 'День рождения',
            'telegram' => 'Telegram',
            'certification_date' => 'Дата аттестации',
            'is_active' => 'Активен',
            'is_admin' => 'Администратор',
            'has_super_access' => 'Супер-доступ',
            'is_main_admin' => 'Главный администратор',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлен',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        $user = static::findOne($id);
        // Админ может входить даже если неактивен
        if ($user && ($user->is_active == 1 || $user->isAdmin())) {
            return $user;
        }
        return null;
    }

    /**
     * {@inheritdoc}
     * Для REST API: токен = auth_key пользователя.
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        if (empty($token)) {
            return null;
        }
        $user = static::findOne(['auth_key' => $token]);
        if ($user && ($user->is_active == 1 || $user->isAdmin())) {
            return $user;
        }
        return null;
    }



    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        // Проверяем, существует ли поле в таблице
        $tableSchema = static::getTableSchema();
        if ($tableSchema && isset($tableSchema->columns['auth_key'])) {
            return $this->auth_key ?? '';
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Gets user's full name
     *
     * @return string
     */
    public function getFullName()
    {
        $name = trim($this->first_name . ' ' . $this->last_name);
        return $name ?: $this->email;
    }

    /**
     * Gets position label
     *
     * @return string
     */
    public function getPositionLabel()
    {
        $positions = [
            'manager' => 'Управляющий',
            'location_manager' => 'Менеджер точки',
            'senior_teamaker' => 'Старший тимейкер',
            'teamaker' => 'Тимейкер',
            'trainee' => 'Стажер',
        ];
        return $positions[$this->position] ?? $this->position;
    }

    /**
     * Стаж (лет/месяцев с даты создания).
     */
    public function getTenureYears(): float
    {
        if (!$this->created_at) {
            return 0;
        }
        $from = new \DateTime($this->created_at);
        $to = new \DateTime('now');
        return round($to->diff($from)->days / 365.25, 1);
    }

    /**
     * Relation to Location
     */
    public function getLocation()
    {
        return $this->hasOne(Location::class, ['id' => 'location_id']);
    }

    /**
     * Супер-доступ к админ-панели (выдаётся главным администратором).
     */
    public function hasSuperAccess(): bool
    {
        $tableSchema = static::getTableSchema();
        if ($tableSchema && isset($tableSchema->columns['has_super_access'])) {
            return (bool) $this->has_super_access;
        }

        return false;
    }

    /**
     * Главный администратор — может выдавать супер-доступ другим пользователям.
     */
    public function isMainAdmin(): bool
    {
        $tableSchema = static::getTableSchema();
        if ($tableSchema && isset($tableSchema->columns['is_main_admin'])) {
            return (bool) $this->is_main_admin;
        }

        return false;
    }

    /**
     * Проверяет, является ли пользователь администратором
     *
     * @return bool
     */
    public function isAdmin()
    {
        if ($this->isMainAdmin()) {
            return true;
        }

        $tableSchema = static::getTableSchema();
        if ($tableSchema && isset($tableSchema->columns['is_admin'])) {
            return (bool) $this->is_admin || $this->hasSuperAccess();
        }

        return false;
    }

    /**
     * Территориальный управляющий без полного админ-доступа.
     */
    public function isTerritorialManagerOnly(): bool
    {
        if ($this->isMainAdmin()) {
            return false;
        }

        return $this->position === 'manager' && !$this->isAdmin();
    }

    /**
     * Находит пользователя по email или phone (включая админа)
     * Переопределяем для поддержки входа админа
     */
    public static function findByEmailOrPhone($emailOrPhone)
    {
        return static::find()
            ->where(['or', ['email' => $emailOrPhone], ['phone' => $emailOrPhone]])
            ->one(); // Убираем проверку is_active для админа
    }

    public function clearPasswordResetToken(): void
    {
        $this->password_reset_token_hash = null;
        $this->password_reset_expires_at = null;
    }

    public function clearTelegramLinkCode(): void
    {
        $this->telegram_link_code = null;
        $this->telegram_link_expires_at = null;
    }

    public function hasLinkedTelegram(): bool
    {
        return !empty($this->telegram_chat_id);
    }
}
