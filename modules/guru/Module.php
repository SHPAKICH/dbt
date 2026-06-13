<?php

namespace app\modules\guru;

use Yii;

/**
 * Модуль «Меню Гуру» — обучение и аттестация сотрудников.
 * Тесты, викторины, технологические карты, теория.
 */
class Module extends \yii\base\Module
{
    public $controllerNamespace = 'app\modules\guru\controllers';

    public function init()
    {
        parent::init();
        if (empty(Yii::$app->i18n->translations['guru'])) {
            Yii::$app->i18n->translations['guru'] = [
                'class' => \yii\i18n\PhpMessageSource::class,
                'sourceLanguage' => 'ru-RU',
                'basePath' => __DIR__ . '/messages',
            ];
        }
    }
}
