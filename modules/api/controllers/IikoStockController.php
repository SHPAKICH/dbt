<?php

namespace app\modules\api\controllers;

use app\services\IikoClient;
use app\services\IikoCloudClient;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

/**
 * Прокси к iiko: Server API (остатки) и iikoCloud API (движение документов).
 * Учётные данные передаются с клиента (localStorage).
 */
class IikoStockController extends BaseApiController
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
            'test' => ['POST'],
            'stores' => ['POST'],
            'balance' => ['POST'],
            'product-documents' => ['POST'],
            'cloud-test' => ['POST'],
            'cloud-organizations' => ['POST'],
            'cloud-stores' => ['POST'],
            'documents-list' => ['POST'],
            'document-by-id' => ['POST'],
        ];
    }

    private function requireAccess(): void
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            throw new \yii\web\UnauthorizedHttpException('Не авторизован.');
        }
        if (!$user->isAdmin() && !in_array($user->position, ['location_manager', 'manager'], true)) {
            throw new ForbiddenHttpException('Нет доступа к остаткам на складе.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function parseRequestBody(): array
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
        $body = $this->parseRequestBody();
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

    private function createCloudClient(): IikoCloudClient
    {
        $body = $this->parseRequestBody();
        $cloud = $body['iikoCloud'] ?? $body['iiko'] ?? null;
        if (!is_array($cloud)) {
            throw new BadRequestHttpException('Укажите настройки iikoCloud (API login) в разделе «Настройки».');
        }
        $apiLogin = trim((string) ($cloud['apiLogin'] ?? ''));
        if ($apiLogin === '') {
            throw new BadRequestHttpException('Заполните API login (ключ iikoCloud) в настройках.');
        }
        $baseUrl = trim((string) ($cloud['apiBaseUrl'] ?? IikoCloudClient::DEFAULT_BASE_URL));

        return new IikoCloudClient($apiLogin, $baseUrl !== '' ? $baseUrl : null);
    }

    /**
     * @return string[]
     */
    private function resolveOrganizationIds(array $body): array
    {
        $cloud = is_array($body['iikoCloud'] ?? null) ? $body['iikoCloud'] : [];
        $orgId = IikoClient::scalarString($body['organizationId'] ?? $cloud['organizationId'] ?? '');
        if ($orgId !== '') {
            return [$orgId];
        }
        if (isset($body['organizationIds']) && is_array($body['organizationIds'])) {
            $ids = [];
            foreach ($body['organizationIds'] as $id) {
                $s = IikoClient::scalarString($id);
                if ($s !== '') {
                    $ids[] = $s;
                }
            }
            if ($ids !== []) {
                return $ids;
            }
        }

        throw new BadRequestHttpException('Укажите organizationId в настройках или в запросе.');
    }

    /**
     * POST /api/v1/iiko-stock/cloud-test
     */
    public function actionCloudTest(): array
    {
        $this->requireAccess();
        try {
            $client = $this->createCloudClient();
            $client->authenticate();
            $orgs = $client->getOrganizations();

            return $this->success([
                'ok' => true,
                'organizations' => $orgs,
            ]);
        } catch (\Throwable $e) {
            Yii::$app->response->statusCode = 502;

            return ['success' => false, 'ok' => false, 'message' => $e->getMessage(), 'organizations' => []];
        }
    }

    /**
     * POST /api/v1/iiko-stock/cloud-organizations
     */
    public function actionCloudOrganizations(): array
    {
        $this->requireAccess();
        try {
            $client = $this->createCloudClient();
            $client->authenticate();
            $orgs = $client->getOrganizations();

            return $this->success(['ok' => true, 'organizations' => $orgs]);
        } catch (\Throwable $e) {
            Yii::$app->response->statusCode = 502;

            return ['success' => false, 'ok' => false, 'message' => $e->getMessage(), 'organizations' => []];
        }
    }

    /**
     * POST /api/v1/iiko-stock/cloud-stores
     */
    public function actionCloudStores(): array
    {
        $this->requireAccess();
        $body = $this->parseJsonBody();
        try {
            $client = $this->createCloudClient();
            $client->authenticate();
            $orgIds = $this->resolveOrganizationIds($body);
            $stores = $client->getStores($orgIds);

            return $this->success(['ok' => true, 'stores' => $stores]);
        } catch (\Throwable $e) {
            Yii::$app->response->statusCode = 502;

            return ['success' => false, 'ok' => false, 'message' => $e->getMessage(), 'stores' => []];
        }
    }

    /**
     * POST /api/v1/iiko-stock/documents-list — единый список документов движения.
     */
    public function actionDocumentsList(): array
    {
        $this->requireAccess();
        $body = $this->parseJsonBody();

        $fromDate = IikoClient::scalarString($body['fromDate'] ?? $body['from'] ?? '');
        $toDate = IikoClient::scalarString($body['toDate'] ?? $body['to'] ?? '');
        if ($fromDate === '' || $toDate === '') {
            $toDate = gmdate('Y-m-d');
            $fromDate = gmdate('Y-m-d', strtotime($toDate) - 30 * 86400);
        }

        $uiTypes = isset($body['documentTypes']) && is_array($body['documentTypes'])
            ? $body['documentTypes']
            : null;
        $uiStatuses = isset($body['statuses']) && is_array($body['statuses'])
            ? $body['statuses']
            : null;

        try {
            $client = $this->createCloudClient();
            $client->authenticate();
            $orgIds = $this->resolveOrganizationIds($body);
            $result = $client->listDocuments([
                'organizationIds' => $orgIds,
                'fromDate' => $fromDate,
                'toDate' => $toDate,
                'documentTypes' => IikoCloudClient::expandDocumentTypes($uiTypes),
                'statuses' => IikoCloudClient::expandStatuses($uiStatuses),
                'storeId' => IikoClient::optionalString($body['storeId'] ?? null),
                'page' => (int) ($body['page'] ?? 0),
                'pageSize' => (int) ($body['pageSize'] ?? 50),
            ]);

            return $this->success(array_merge(['ok' => true], $result));
        } catch (\Throwable $e) {
            Yii::$app->response->statusCode = 502;

            return [
                'success' => false,
                'ok' => false,
                'message' => $e->getMessage(),
                'documents' => [],
                'hasMore' => false,
            ];
        }
    }

    /**
     * POST /api/v1/iiko-stock/document-by-id — состав документа.
     */
    public function actionDocumentById(): array
    {
        $this->requireAccess();
        $body = $this->parseJsonBody();

        $documentId = IikoClient::scalarString($body['documentId'] ?? $body['id'] ?? '');
        $documentType = IikoClient::scalarString($body['documentType'] ?? '');
        if ($documentId === '' || $documentType === '') {
            throw new BadRequestHttpException('Укажите documentId и documentType.');
        }

        try {
            $client = $this->createCloudClient();
            $client->authenticate();
            $orgIds = $this->resolveOrganizationIds($body);
            $doc = $client->getDocumentById($orgIds[0], $documentId, $documentType);

            return $this->success(['ok' => true, 'document' => $doc]);
        } catch (\Throwable $e) {
            Yii::$app->response->statusCode = 502;

            return ['success' => false, 'ok' => false, 'message' => $e->getMessage(), 'document' => null];
        }
    }

    /**
     * POST /api/v1/iiko-stock/test
     */
    public function actionTest(): array
    {
        $this->requireAccess();
        try {
            $client = $this->createClient();
            $client->login();
            $ping = $client->ping();
            $token = $client->login();
            $preview = mb_substr(IikoClient::scalarString($token), 0, 8) . '…';
            $client->logout();
            return $this->success([
                'ok' => true,
                'tokenPreview' => $preview,
                'version' => isset($ping['version']) ? IikoClient::scalarString($ping['version']) : null,
            ]);
        } catch (\Throwable $e) {
            Yii::$app->response->statusCode = 502;
            return ['success' => false, 'ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * POST /api/v1/iiko-stock/stores
     */
    public function actionStores(): array
    {
        $this->requireAccess();
        try {
            $client = $this->createClient();
            $stores = $client->getStores();
            $client->logout();
            return $this->success(['ok' => true, 'stores' => $stores]);
        } catch (\Throwable $e) {
            Yii::$app->response->statusCode = 502;
            return ['success' => false, 'ok' => false, 'message' => $e->getMessage(), 'stores' => []];
        }
    }

    /**
     * POST /api/v1/iiko-stock/balance — остатки и расход/приход за период (реализация+списание / приход).
     */
    public function actionBalance(): array
    {
        $this->requireAccess();
        $body = $this->parseJsonBody();

        $storeId = isset($body['storeId']) ? (string) $body['storeId'] : '';
        $reservesInput = is_array($body['reserves'] ?? null) ? $body['reserves'] : [];
        $periodTo = $body['to'] ?? gmdate('Y-m-d');
        $periodFrom = $body['from'] ?? gmdate('Y-m-d', strtotime((string) $periodTo) - 90 * 86400);
        $tsNow = gmdate('Y-m-d\TH:i:s');

        try {
            $client = $this->createClient();
            $balance = $client->getBalance([
                'store' => $storeId ?: null,
                'timestamp' => $tsNow,
            ]);
            $products = [];
            try {
                $products = $client->getProducts();
            } catch (\Throwable $e) {
                // имена опциональны
            }

            $movementMap = [];
            $movementWarning = null;
            if ($storeId !== '') {
                try {
                    $movementMap = $client->getStoreProductPeriodMovement(
                        $storeId,
                        (string) $periodFrom,
                        (string) $periodTo
                    );
                } catch (\Throwable $e) {
                    $movementWarning = mb_substr($e->getMessage(), 0, 240);
                }
            }

            $client->logout();

            $productMap = [];
            foreach ($products as $p) {
                $productMap[$p['id']] = $p;
            }
            $reserveMap = [];
            foreach ($reservesInput as $r) {
                if (!is_array($r)) {
                    continue;
                }
                $sid = IikoClient::scalarString($r['storeId'] ?? '');
                $pid = IikoClient::scalarString($r['productId'] ?? '');
                if ($sid === '' || $pid === '') {
                    continue;
                }
                $reserveMap[$sid . '::' . $pid] = $r;
            }

            $seen = [];
            $rows = [];
            foreach ($balance as $b) {
                $bStoreId = IikoClient::scalarString($b['storeId'] ?? '');
                $bProductId = IikoClient::scalarString($b['productId'] ?? '');
                if ($storeId !== '' && $bStoreId !== $storeId) {
                    continue;
                }
                $key = $bStoreId . '::' . $bProductId;
                $seen[$key] = true;
                $product = $productMap[$bProductId] ?? null;
                $reserve = $reserveMap[$key] ?? null;
                $reserveValue = (float) ($reserve['reserve'] ?? 0);
                $multiplicity = isset($reserve['multiplicity']) ? (float) $reserve['multiplicity'] : null;
                $shortage = max(0, $reserveValue - $b['amount']);
                $toOrder = $this->roundToStep($shortage, $multiplicity);
                $bNorm = array_merge($b, ['storeId' => $bStoreId, 'productId' => $bProductId]);
                $mov = $movementMap[$bProductId] ?? ['out' => 0.0, 'in' => 0.0];
                $rows[] = $this->buildRow(
                    $bNorm,
                    $product,
                    $reserve,
                    $reserveValue,
                    $multiplicity,
                    $shortage,
                    $toOrder,
                    (float) ($mov['out'] ?? 0),
                    (float) ($mov['in'] ?? 0)
                );
            }

            foreach ($reservesInput as $r) {
                if (!is_array($r)) {
                    continue;
                }
                $sid = IikoClient::scalarString($r['storeId'] ?? '');
                $pid = IikoClient::scalarString($r['productId'] ?? '');
                if ($storeId !== '' && $sid !== $storeId) {
                    continue;
                }
                $key = $sid . '::' . $pid;
                if (isset($seen[$key])) {
                    continue;
                }
                $product = $productMap[$pid] ?? null;
                $reserveValue = (float) ($r['reserve'] ?? 0);
                $multiplicity = isset($r['multiplicity']) ? (float) $r['multiplicity'] : null;
                $shortage = $reserveValue;
                $toOrder = $this->roundToStep($shortage, $multiplicity);
                $mov = $movementMap[$pid] ?? ['out' => 0.0, 'in' => 0.0];
                $rows[] = [
                    'productId' => $pid,
                    'productName' => IikoClient::scalarString(
                        $r['productName'] ?? $product['name'] ?? null,
                        '(товар ' . mb_substr($pid, 0, 8) . ')'
                    ),
                    'productCode' => IikoClient::optionalString($r['productCode'] ?? $product['code'] ?? null),
                    'productNum' => IikoClient::optionalString($r['productNum'] ?? $product['num'] ?? null),
                    'unit' => IikoClient::optionalString($r['unit'] ?? $product['mainUnit'] ?? null),
                    'storeId' => $sid,
                    'storeName' => $r['storeName'] ?? null,
                    'amount' => 0,
                    'periodOut' => (float) ($mov['out'] ?? 0),
                    'periodIn' => (float) ($mov['in'] ?? 0),
                    'reserve' => $reserveValue,
                    'multiplicity' => $multiplicity,
                    'shortage' => $shortage,
                    'toOrder' => $toOrder,
                    'status' => $this->classify(0, $reserveValue),
                    'notes' => $r['notes'] ?? null,
                ];
            }

            usort($rows, function ($a, $b) {
                return strcmp(
                    IikoClient::scalarString($a['productName'] ?? ''),
                    IikoClient::scalarString($b['productName'] ?? '')
                );
            });

            return $this->success([
                'ok' => true,
                'generatedAt' => gmdate('c'),
                'storeId' => $storeId !== '' ? $storeId : null,
                'from' => substr((string) $periodFrom, 0, 10),
                'to' => substr((string) $periodTo, 0, 10),
                'movementWarning' => $movementWarning,
                'total' => count($rows),
                'rows' => $rows,
            ]);
        } catch (\Throwable $e) {
            Yii::$app->response->statusCode = 502;
            return [
                'success' => false,
                'ok' => false,
                'message' => $e->getMessage(),
                'rows' => [],
            ];
        }
    }

    /**
     * POST /api/v1/iiko-stock/product-documents — движение по выбранному товару.
     */
    public function actionProductDocuments(): array
    {
        $this->requireAccess();
        $body = $this->parseJsonBody();

        $storeId = IikoClient::scalarString($body['storeId'] ?? '');
        $productId = IikoClient::scalarString($body['productId'] ?? '');
        if ($storeId === '' || $productId === '') {
            throw new BadRequestHttpException('Укажите storeId и productId.');
        }

        $periodTo = $body['to'] ?? gmdate('Y-m-d');
        $periodFrom = $body['from'] ?? gmdate('Y-m-d', strtotime((string) $periodTo) - 90 * 86400);
        $productNum = IikoClient::optionalString($body['productNum'] ?? null);
        $productCode = IikoClient::optionalString($body['productCode'] ?? null);

        try {
            $client = $this->createClient();
            $history = $client->getProductDocumentHistory(
                $storeId,
                $productId,
                (string) $periodFrom,
                (string) $periodTo,
                $productNum,
                $productCode
            );
            $client->logout();

            return $this->success(array_merge(['ok' => true], $history));
        } catch (\Throwable $e) {
            Yii::$app->response->statusCode = 502;
            return ['success' => false, 'ok' => false, 'message' => $e->getMessage(), 'groups' => []];
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

    private function buildRow(
        array $b,
        ?array $product,
        ?array $reserve,
        float $reserveValue,
        ?float $multiplicity,
        float $shortage,
        float $toOrder,
        float $periodOut = 0.0,
        float $periodIn = 0.0
    ): array {
        $productId = IikoClient::scalarString($b['productId'] ?? '');
        return [
            'productId' => $productId,
            'productName' => IikoClient::scalarString(
                $b['productName'] ?? $product['name'] ?? null,
                '(товар ' . mb_substr($productId, 0, 8) . ')'
            ),
            'productCode' => IikoClient::optionalString($b['productCode'] ?? $product['code'] ?? null),
            'productNum' => IikoClient::optionalString($b['productNum'] ?? $product['num'] ?? null),
            'unit' => IikoClient::optionalString($b['unit'] ?? $reserve['unit'] ?? $product['mainUnit'] ?? null),
            'storeId' => IikoClient::scalarString($b['storeId'] ?? ''),
            'storeName' => IikoClient::optionalString($b['storeName'] ?? null),
            'amount' => $b['amount'],
            'periodOut' => round($periodOut, 4),
            'periodIn' => round($periodIn, 4),
            'reserve' => $reserveValue,
            'multiplicity' => $multiplicity,
            'shortage' => $shortage,
            'toOrder' => $toOrder,
            'status' => $this->classify($b['amount'], $reserveValue),
            'notes' => isset($reserve['notes']) ? IikoClient::scalarString($reserve['notes']) : null,
        ];
    }

    private function classify(float $amount, float $reserve): string
    {
        if ($reserve <= 0) {
            return 'no-reserve';
        }
        if ($amount <= 0) {
            return 'critical';
        }
        $ratio = $amount / $reserve;
        if ($ratio < 0.5) {
            return 'critical';
        }
        if ($ratio < 1) {
            return 'low';
        }
        return 'ok';
    }

    private function roundToStep(float $value, ?float $step): float
    {
        if ($step === null || $step <= 0) {
            return $value;
        }
        return (float) (ceil($value / $step) * $step);
    }
}
