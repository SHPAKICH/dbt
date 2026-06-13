<?php

namespace app\components;

use Yii;
use yii\web\ErrorHandler;
use yii\web\HttpException;
use yii\web\Response;

/**
 * Для запросов к /api при ошибке отдаём JSON вместо редиректа на site/error.
 */
class ApiErrorHandler extends ErrorHandler
{
    protected function renderException($exception): void
    {
        $isApi = false;
        if (Yii::$app->has('request')) {
            $path = Yii::$app->getRequest()->getPathInfo();
            $isApi = strpos($path, 'api') === 0;
        } elseif (!empty($_SERVER['REQUEST_URI'])) {
            $isApi = strpos($_SERVER['REQUEST_URI'], '/api') === 0;
        }

        if ($isApi && Yii::$app->has('response')) {
            $response = Yii::$app->getResponse();
            $response->format = Response::FORMAT_JSON;
            $message = 'Внутренняя ошибка сервера.';
            if ($exception instanceof HttpException && $exception->getMessage() !== '') {
                $message = $exception->getMessage();
            } elseif (YII_DEBUG && $exception->getMessage() !== '') {
                $message = $exception->getMessage();
            }
            $response->data = [
                'success' => false,
                'message' => $message,
                'name' => $this->getExceptionName($exception),
            ];
            if (YII_DEBUG) {
                $response->data['file'] = $exception->getFile();
                $response->data['line'] = $exception->getLine();
            }
            $response->statusCode = $exception->statusCode ?? 500;
            $response->send();
            return;
        }

        parent::renderException($exception);
    }
}
