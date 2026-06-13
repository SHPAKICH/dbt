<?php

namespace app\modules\api\controllers;

use app\services\IikoClient;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

/**
 * Чеки (продажи) из iiko Server API — OLAP SALES.
 */
class IikoChecksController extends BaseApiController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
        ];

        return $behaviors;
    }

    protected function verbs(): array
    {
        return [
            'departments' => ['POST'],
            'list' => ['POST'],
            'detail' => ['POST'],
            'shift-summary' => ['POST'],
        ];
    }

    private function requireAccess(): void
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            throw new \yii\web\UnauthorizedHttpException('Не авторизован.');
        }
        if (!$user->isAdmin() && !in_array($user->position, ['location_manager', 'manager'], true)) {
            throw new ForbiddenHttpException('Нет доступа к разделу «Чеки».');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJsonBody(): array
    {
        $body = Yii::$app->request->getBodyParams();
        if (empty($body) && Yii::$app->request->getRawBody()) {
            $decoded = json_decode(Yii::$app->request->getRawBody(), true);
            if (is_array($decoded)) {
                $body = $decoded;
            }
        }

        return is_array($body) ? $body : [];
    }

    private function createClient(): IikoClient
    {
        $body = $this->parseJsonBody();
        $iiko = $body['iiko'] ?? null;
        if (!is_array($iiko)) {
            throw new BadRequestHttpException('Укажите настройки iiko (URL, логин, пароль) в разделе «Настройки».');
        }
        $baseUrl = trim((string) ($iiko['baseUrl'] ?? ''));
        $login = trim((string) ($iiko['login'] ?? ''));
        $password = (string) ($iiko['password'] ?? '');
        if ($baseUrl === '' || $login === '' || $password === '') {
            throw new BadRequestHttpException('Заполните URL iiko-сервера, логин и пароль в настройках.');
        }

        return new IikoClient($baseUrl, $login, $password);
    }

    /**
     * POST /api/v1/iiko-checks/departments — точки iiko.
     */
    public function actionDepartments(): array
    {
        $this->requireAccess();
        try {
            $client = $this->createClient();
            $departments = $client->getDepartments();
            $client->logout();

            return $this->success(['ok' => true, 'departments' => $departments]);
        } catch (\Throwable $e) {
            Yii::$app->response->statusCode = 502;
            return ['success' => false, 'ok' => false, 'message' => $e->getMessage(), 'departments' => []];
        }
    }

    /**
     * POST /api/v1/iiko-checks/list — список чеков за период.
     */
    public function actionList(): array
    {
        $this->requireAccess();
        $body = $this->parseJsonBody();
        $periodTo = $body['to'] ?? gmdate('Y-m-d');
        $periodFrom = $body['from'] ?? gmdate('Y-m-d', strtotime((string) $periodTo) - 7 * 86400);
        $departmentId = IikoClient::optionalString($body['departmentId'] ?? null);

        try {
            $client = $this->createClient();
            $result = $client->getSalesChecksList(
                (string) $periodFrom,
                (string) $periodTo,
                $departmentId
            );
            $client->logout();

            $checks = $result['checks'] ?? [];

            return $this->success([
                'ok' => true,
                'from' => $result['from'] ?? substr((string) $periodFrom, 0, 10),
                'to' => $result['to'] ?? substr((string) $periodTo, 0, 10),
                'checks' => $checks,
                'total' => count($checks),
                'totalSum' => (float) ($result['totalSum'] ?? 0),
                'warning' => $result['warning'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Yii::$app->response->statusCode = 502;
            return [
                'success' => false,
                'ok' => false,
                'message' => $e->getMessage(),
                'checks' => [],
            ];
        }
    }

    /**
     * POST /api/v1/iiko-checks/detail — позиции чека.
     */
    public function actionDetail(): array
    {
        $this->requireAccess();
        $body = $this->parseJsonBody();
        $orderId = IikoClient::scalarString($body['orderId'] ?? '');
        if ($orderId === '') {
            throw new BadRequestHttpException('Укажите orderId.');
        }
        $periodTo = $body['to'] ?? gmdate('Y-m-d');
        $periodFrom = $body['from'] ?? gmdate('Y-m-d', strtotime((string) $periodTo) - 7 * 86400);
        $departmentId = IikoClient::optionalString($body['departmentId'] ?? null);

        try {
            $client = $this->createClient();
            $items = $client->getSalesCheckItems(
                (string) $periodFrom,
                (string) $periodTo,
                $orderId,
                $departmentId
            );
            $client->logout();

            return $this->success([
                'ok' => true,
                'orderId' => $orderId,
                'items' => $items,
            ]);
        } catch (\Throwable $e) {
            Yii::$app->response->statusCode = 502;
            return ['success' => false, 'ok' => false, 'message' => $e->getMessage(), 'items' => []];
        }
    }

    /**
     * POST /api/v1/iiko-checks/shift-summary — сводка по смене (часы, оплата, типы).
     */
    public function actionShiftSummary(): array
    {
        $this->requireAccess();
        $body = $this->parseJsonBody();
        $periodTo = $body['to'] ?? gmdate('Y-m-d');
        $periodFrom = $body['from'] ?? gmdate('Y-m-d', strtotime((string) $periodTo) - 7 * 86400);
        $departmentId = IikoClient::optionalString($body['departmentId'] ?? null);

        try {
            $client = $this->createClient();
            $summary = $client->getSalesShiftSummary(
                (string) $periodFrom,
                (string) $periodTo,
                $departmentId
            );
            $client->logout();

            return $this->success(array_merge(['ok' => true], $summary));
        } catch (\Throwable $e) {
            Yii::$app->response->statusCode = 502;
            return [
                'success' => false,
                'ok' => false,
                'message' => $e->getMessage(),
                'summary' => ['totalSum' => 0, 'totalChecks' => 0, 'avgCheck' => 0],
                'byHour' => [],
                'byPaymentType' => [],
                'byOrderType' => [],
                'byDay' => [],
                'warning' => null,
            ];
        }
    }
}
