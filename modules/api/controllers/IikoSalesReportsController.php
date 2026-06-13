<?php

namespace app\modules\api\controllers;

use app\services\IikoClient;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

/**
 * Отчёты по продажам блюд из iiko Server API — OLAP SALES.
 */
class IikoSalesReportsController extends BaseApiController
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
            'report' => ['POST'],
            'nomenclature' => ['POST'],
        ];
    }

    private function requireAccess(): void
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            throw new \yii\web\UnauthorizedHttpException('Не авторизован.');
        }
        if (!$user->isAdmin() && !in_array($user->position, ['location_manager', 'manager'], true)) {
            throw new ForbiddenHttpException('Нет доступа к разделу «Отчёты по продажам».');
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
     * @return array<int, string>
     */
    private function parseProductNames(array $body): array
    {
        $raw = $body['productNames'] ?? $body['products'] ?? [];
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $name) {
            $s = trim((string) $name);
            if ($s !== '') {
                $out[] = $s;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * @return array<int, string>
     */
    private function parseProductKeywords(array $body): array
    {
        $raw = $body['productKeywords'] ?? $body['keywords'] ?? [];
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $keyword) {
            $s = trim((string) $keyword);
            if ($s !== '') {
                $out[] = $s;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * POST /api/v1/iiko-sales-reports/departments
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
     * POST /api/v1/iiko-sales-reports/nomenclature — справочник блюд (для поиска).
     */
    public function actionNomenclature(): array
    {
        $this->requireAccess();
        $body = $this->parseJsonBody();
        $query = mb_strtolower(trim((string) ($body['query'] ?? '')));

        try {
            $client = $this->createClient();
            $products = $client->getProducts();
            $client->logout();

            if ($query !== '') {
                $products = array_values(array_filter($products, static function ($p) use ($query) {
                    $name = mb_strtolower((string) ($p['name'] ?? ''));
                    $code = mb_strtolower((string) ($p['code'] ?? ''));
                    $num = mb_strtolower((string) ($p['num'] ?? ''));

                    return str_contains($name, $query)
                        || ($code !== '' && str_contains($code, $query))
                        || ($num !== '' && str_contains($num, $query));
                }));
            }

            $limit = 200;
            if (count($products) > $limit) {
                $products = array_slice($products, 0, $limit);
            }

            return $this->success(['ok' => true, 'products' => $products, 'total' => count($products)]);
        } catch (\Throwable $e) {
            Yii::$app->response->statusCode = 502;

            return ['success' => false, 'ok' => false, 'message' => $e->getMessage(), 'products' => []];
        }
    }

    /**
     * POST /api/v1/iiko-sales-reports/report — продажи по блюдам и аналитика выбранной группы.
     */
    public function actionReport(): array
    {
        $this->requireAccess();
        $body = $this->parseJsonBody();
        $periodTo = $body['to'] ?? gmdate('Y-m-d');
        $periodFrom = $body['from'] ?? gmdate('Y-m-d', strtotime((string) $periodTo) - 7 * 86400);
        $departmentId = IikoClient::optionalString($body['departmentId'] ?? null);
        $productNames = $this->parseProductNames($body);
        $productKeywords = $this->parseProductKeywords($body);

        try {
            $client = $this->createClient();
            $report = $client->getSalesProductsReport(
                (string) $periodFrom,
                (string) $periodTo,
                $departmentId,
                $productNames !== [] ? $productNames : null,
                $productKeywords !== [] ? $productKeywords : null
            );
            $client->logout();

            $matchedNames = [];
            if ($productKeywords !== [] && isset($report['selected']['items'])) {
                foreach ($report['selected']['items'] as $item) {
                    if (!empty($item['name'])) {
                        $matchedNames[] = $item['name'];
                    }
                }
            }

            return $this->success(array_merge(['ok' => true], $report, [
                'matchedProductNames' => $matchedNames,
            ]));
        } catch (\Throwable $e) {
            Yii::$app->response->statusCode = 502;

            return [
                'success' => false,
                'ok' => false,
                'message' => $e->getMessage(),
                'summary' => ['totalSum' => 0, 'totalQty' => 0, 'productCount' => 0],
                'products' => [],
                'selected' => null,
            ];
        }
    }
}
