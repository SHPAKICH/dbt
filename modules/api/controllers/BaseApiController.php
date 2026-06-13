<?php

namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use yii\filters\Cors;
use yii\filters\ContentNegotiator;
use yii\filters\VerbFilter;
use yii\web\Response;

/**
 * Базовый контроллер REST API.
 * JSON-ответы, CORS для фронтенда, отключение CSRF для API.
 */
abstract class BaseApiController extends Controller
{
    public function behaviors(): array
    {
        $frontendUrl = Yii::$app->params['frontendUrl'] ?? 'http://localhost:5173';

        return [
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
            'cors' => [
                'class' => Cors::class,
                'cors' => [
                    'Origin' => [$frontendUrl, 'http://localhost:5173', 'http://127.0.0.1:5173'],
                    'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
                    'Access-Control-Request-Headers' => ['Authorization', 'Content-Type', 'X-Requested-With'],
                    'Access-Control-Allow-Credentials' => true,
                    'Access-Control-Max-Age' => 86400,
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => $this->verbs(),
            ],
        ];
    }

    public function beforeAction($action): bool
    {
        Yii::$app->request->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    protected function verbs(): array
    {
        return [];
    }

    protected function success(array $data = [], string $message = ''): array
    {
        $out = ['success' => true];
        if ($message !== '') {
            $out['message'] = $message;
        }
        return array_merge($out, $data);
    }

    protected function error(string $message, array $errors = [], int $code = 400): array
    {
        $out = ['success' => false, 'message' => $message];
        if (!empty($errors)) {
            $out['errors'] = $errors;
        }
        Yii::$app->response->statusCode = $code;
        return $out;
    }

    /**
     * Проверяет, не был ли POST-запрос обрезан из-за post_max_size.
     * Когда размер тела запроса превышает post_max_size, PHP обнуляет
     * и $_POST, и $_FILES, при этом CONTENT_LENGTH остаётся корректным.
     */
    protected function isPostTruncated(): bool
    {
        if (Yii::$app->request->getMethod() !== 'POST') {
            return false;
        }
        $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($contentLength <= 0) {
            return false;
        }
        return empty($_POST) && empty($_FILES);
    }

    /**
     * Возвращает ошибку, если запрос был обрезан. Null — если всё ОК.
     */
    protected function checkPostSize(): ?array
    {
        if ($this->isPostTruncated()) {
            $postMax = ini_get('post_max_size') ?: '8M';
            return $this->error(
                "Размер загружаемых данных превышает лимит сервера ({$postMax}). Попробуйте загрузить фото меньшего размера.",
                [],
                413
            );
        }
        return null;
    }

    /**
     * Проверяет массив UploadedFile на ошибки загрузки (UPLOAD_ERR_INI_SIZE и т.д.)
     * Возвращает текст ошибки или null.
     */
    protected function getFileUploadError(\yii\web\UploadedFile $file): ?string
    {
        if ($file->error === UPLOAD_ERR_OK) {
            return null;
        }
        if ($file->error === UPLOAD_ERR_INI_SIZE || $file->error === UPLOAD_ERR_FORM_SIZE) {
            $maxSize = ini_get('upload_max_filesize') ?: '2M';
            return "Файл «{$file->name}» слишком большой. Максимум: {$maxSize}. Попробуйте сжать фото или отправить в меньшем разрешении.";
        }
        if ($file->error === UPLOAD_ERR_PARTIAL) {
            return "Файл «{$file->name}» был загружен лишь частично. Проверьте интернет-соединение.";
        }
        if ($file->error === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return "Ошибка загрузки файла «{$file->name}» (код {$file->error}).";
    }
}
