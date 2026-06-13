<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\web\UploadedFile;

/**
 * Форма редактирования профиля (имя, фамилия, аватар).
 */
class ProfileForm extends Model
{
    public $first_name;
    public $last_name;
    /** @var UploadedFile|null */
    public $avatarFile;

    /**
     * @var User
     */
    private $_user;

    public function __construct(User $user, $config = [])
    {
        $this->_user = $user;
        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name;
        parent::__construct($config);
    }

    public function rules()
    {
        return [
            [['first_name', 'last_name'], 'string', 'max' => 100],
            [['avatarFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg, gif, webp', 'maxSize' => 3 * 1024 * 1024, 'checkExtensionByMimeType' => true],
        ];
    }

    public function attributeLabels()
    {
        return [
            'first_name' => 'Имя',
            'last_name' => 'Фамилия',
            'avatarFile' => 'Аватар',
        ];
    }

    public function getUser(): User
    {
        return $this->_user;
    }

    /**
     * Сохранить изменения профиля и загрузить аватар при наличии.
     */
    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $user = $this->_user;
        $user->first_name = $this->first_name;
        $user->last_name = $this->last_name;

        // API передаёт avatarFile через getInstanceByName('avatarFile'); веб-форма — через getInstance($this, 'avatarFile')
        $avatarFile = $this->avatarFile ?: UploadedFile::getInstance($this, 'avatarFile');
        if ($avatarFile) {
            $dir = Yii::getAlias('@webroot/uploads/avatars');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $name = $user->id . '_' . time() . '.' . strtolower($avatarFile->extension);
            $path = $dir . '/' . $name;

            if ($avatarFile->saveAs($path)) {
                try {
                    \yii\imagine\Image::thumbnail($path, 400, 400)->save($path, ['quality' => 90]);
                } catch (\Throwable $e) {
                    Yii::warning('Avatar resize failed: ' . $e->getMessage());
                }

                if ($user->avatar && is_file(Yii::getAlias('@webroot') . $user->avatar)) {
                    @unlink(Yii::getAlias('@webroot') . $user->avatar);
                }

                $user->avatar = '/uploads/avatars/' . $name;
            }
        }

        return $user->save(false);
    }
}
