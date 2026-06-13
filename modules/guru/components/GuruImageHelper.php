<?php

namespace app\modules\guru\components;

use Yii;
use yii\web\UploadedFile;

/**
 * Хелпер загрузки изображений для модуля Guru.
 * Файлы сохраняются в web/uploads/guru/{type}/
 */
class GuruImageHelper
{
    public const TYPE_TEST = 'tests';
    public const TYPE_QUESTION = 'questions';
    public const TYPE_CARD = 'cards';
    public const TYPE_LESSON = 'lessons';

    private static function getBasePath(): string
    {
        $path = Yii::getAlias('@webroot/uploads/guru');
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        return $path;
    }

    /**
     * Сохранить загруженный файл и вернуть относительный путь (для хранения в БД).
     * @param UploadedFile|null $file
     * @param string $type self::TYPE_TEST, TYPE_QUESTION, TYPE_CARD
     * @return string|null путь вида "tests/abc123.jpg" или null
     */
    public static function save(UploadedFile $file = null, string $type = self::TYPE_TEST): ?string
    {
        if (!$file || $file->error !== UPLOAD_ERR_OK) {
            return null;
        }
        $ext = strtolower($file->extension);
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return null;
        }
        $dir = self::getBasePath() . DIRECTORY_SEPARATOR . $type;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = Yii::$app->security->generateRandomString(12) . '.' . $ext;
        $fullPath = $dir . DIRECTORY_SEPARATOR . $name;
        if ($file->saveAs($fullPath)) {
            return $type . '/' . $name;
        }
        return null;
    }

    /**
     * Получить URL изображения для вывода в img src.
     * @param string|null $path путь из БД (tests/xxx.jpg) или старый полный URL
     * @return string|null URL или null
     */
    public static function getUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }
        // если уже полный URL (старые записи) — возвращаем как есть
        if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
            return $path;
        }
        // если путь уже абсолютный внутри сайта (/uploads/...), просто добавляем @web
        if (strpos($path, '/') === 0) {
            return Yii::getAlias('@web') . $path;
        }
        return Yii::getAlias('@web/uploads/guru/' . $path);
    }

    /**
     * Удалить файл по сохранённому пути.
     */
    public static function delete(?string $path): void
    {
        if (empty($path)) {
            return;
        }
        $fullPath = Yii::getAlias('@webroot/uploads/guru/' . $path);
        if (is_file($fullPath)) {
            unlink($fullPath);
        }
    }
}
