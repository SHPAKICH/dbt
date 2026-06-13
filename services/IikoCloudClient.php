<?php

namespace app\services;

/**
 * Клиент iikoCloud API (Transport): Bearer из POST /api/1/access_token.
 */
class IikoCloudClient
{
    public const DEFAULT_BASE_URL = 'https://api-ru.iiko.services';

    /** @var array<string, string> UI-ключ → системные типы документов */
    public const DOCUMENT_TYPE_GROUPS = [
        'writeoff' => ['Writeoff', 'WasteDocument'],
        'sales' => ['SalesDocument', 'OutcomingInvoice'],
        'inventory' => ['InventoryDocument'],
        'incoming' => ['IncomingInvoice'],
    ];

    private string $baseUrl;
    private string $apiLogin;
    private ?string $token = null;

    public function __construct(string $apiLogin, ?string $baseUrl = null)
    {
        $this->apiLogin = trim($apiLogin);
        $this->baseUrl = rtrim($baseUrl !== null && $baseUrl !== '' ? $baseUrl : self::DEFAULT_BASE_URL, '/');
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function authenticate(): string
    {
        $data = $this->request('/api/1/access_token', [
            'apiLogin' => $this->apiLogin,
        ], false);

        $token = IikoClient::scalarString($data['token'] ?? '');
        if ($token === '') {
            throw new \RuntimeException('iikoCloud: пустой token в ответе access_token');
        }
        $this->token = $token;

        return $token;
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    public function getOrganizations(): array
    {
        $data = $this->request('/api/1/organizations', [
            'organizationIds' => null,
            'returnAdditionalInfo' => false,
            'includeDisabled' => false,
        ]);

        $out = [];
        foreach ($data['organizations'] ?? [] as $org) {
            if (!is_array($org)) {
                continue;
            }
            $id = IikoClient::scalarString($org['id'] ?? '');
            if ($id === '') {
                continue;
            }
            $out[] = [
                'id' => $id,
                'name' => IikoClient::scalarString($org['name'] ?? '', $id),
            ];
        }

        return $out;
    }

    /**
     * Список складских документов (пагинация, фильтры).
     *
     * @param array{
     *   organizationIds: string[],
     *   fromDate: string,
     *   toDate: string,
     *   documentTypes?: string[],
     *   statuses?: string[],
     *   storeId?: string,
     *   page?: int,
     *   pageSize?: int
     * } $filters
     *
     * @return array{documents: array<int, array>, hasMore: bool, page: int, pageSize: int, rawTotal: ?int}
     */
    public function listDocuments(array $filters): array
    {
        $page = max(0, (int) ($filters['page'] ?? 0));
        $pageSize = min(500, max(1, (int) ($filters['pageSize'] ?? 50)));

        $body = [
            'organizationIds' => array_values($filters['organizationIds'] ?? []),
            'dateFrom' => $this->formatCloudDateTime($filters['fromDate'] ?? '', true),
            'dateTo' => $this->formatCloudDateTime($filters['toDate'] ?? '', false),
            'page' => $page,
            'pageSize' => $pageSize,
        ];

        if (!empty($filters['documentTypes'])) {
            $body['documentTypes'] = array_values($filters['documentTypes']);
        }
        if (!empty($filters['statuses'])) {
            $body['statuses'] = array_values($filters['statuses']);
        }
        if (!empty($filters['storeId'])) {
            $body['storeIds'] = [IikoClient::scalarString($filters['storeId'])];
        }

        $data = $this->requestDocumentsList($body);
        $items = $this->extractDocumentList($data);
        $normalized = [];
        foreach ($items as $row) {
            if (!is_array($row)) {
                continue;
            }
            $doc = $this->normalizeDocumentSummary($row);
            if ($doc['id'] !== '') {
                $normalized[] = $doc;
            }
        }

        $total = null;
        if (isset($data['totalCount']) && is_numeric($data['totalCount'])) {
            $total = (int) $data['totalCount'];
        } elseif (isset($data['total']) && is_numeric($data['total'])) {
            $total = (int) $data['total'];
        }

        $hasMore = count($normalized) >= $pageSize;
        if ($total !== null) {
            $hasMore = ($page + 1) * $pageSize < $total;
        }

        return [
            'documents' => $normalized,
            'hasMore' => $hasMore,
            'page' => $page,
            'pageSize' => $pageSize,
            'rawTotal' => $total,
        ];
    }

    /**
     * @return array{id: string, number: string, date: string, documentType: string, status: string, comment: ?string, sum: float, storeId: ?string, storeFromId: ?string, storeToId: ?string, storeFromName: ?string, storeToName: ?string, items: array}
     */
    public function getDocumentById(string $organizationId, string $documentId, string $documentType): array
    {
        $data = $this->request('/api/1/storage/documents/v2/get_by_id', [
            'organizationId' => $organizationId,
            'id' => $documentId,
            'documentId' => $documentId,
            'documentType' => $documentType,
        ]);

        $doc = $data['document'] ?? $data;
        if (!is_array($doc)) {
            throw new \RuntimeException('iikoCloud: документ не найден в ответе get_by_id');
        }

        return $this->normalizeDocumentDetail($doc, $documentType);
    }

    /**
     * @return array<int, array{id: string, name: string, code: ?string}>
     */
    public function getStores(array $organizationIds): array
    {
        $paths = [
            '/api/1/entities/stores/list',
            '/api/1/storage/stores/list',
            '/api/1/stores/list',
        ];
        $lastError = null;
        foreach ($paths as $path) {
            try {
                $data = $this->request($path, [
                    'organizationIds' => $organizationIds,
                ]);
                $stores = $this->extractStoresList($data);
                if ($stores !== []) {
                    return $stores;
                }
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        if ($lastError !== null) {
            throw new \RuntimeException(
                'iikoCloud: не удалось загрузить склады. ' . mb_substr($lastError, 0, 200)
            );
        }

        return [];
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array<string, mixed>
     */
    private function requestDocumentsList(array $body): array
    {
        $paths = [
            '/api/1/storage/documents/v2/list',
            '/api/1/storage/documents/v2/search',
        ];
        $lastError = null;
        foreach ($paths as $path) {
            try {
                return $this->request($path, $body);
            } catch (\Throwable $e) {
                $lastError = $e;
            }
        }
        if ($lastError !== null) {
            throw $lastError;
        }
        throw new \RuntimeException('iikoCloud: не найден эндпоинт списка документов');
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<int, mixed>
     */
    private function extractDocumentList(array $data): array
    {
        foreach (['documents', 'items', 'data', 'result'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                return $data[$key];
            }
        }
        if ($this->looksLikeDocument($data)) {
            return [$data];
        }

        return [];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<int, array{id: string, name: string, code: ?string}>
     */
    private function extractStoresList(array $data): array
    {
        $rows = $data['stores'] ?? $data['items'] ?? $data['data'] ?? [];
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = IikoClient::scalarString($row['id'] ?? $row['storeId'] ?? '');
            if ($id === '') {
                continue;
            }
            $out[] = [
                'id' => $id,
                'name' => IikoClient::scalarString($row['name'] ?? '', $id),
                'code' => IikoClient::optionalString($row['code'] ?? null),
            ];
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function normalizeDocumentSummary(array $row): array
    {
        $type = $this->resolveDocumentType($row);
        $storeFromId = IikoClient::optionalString(
            $row['storeFromId'] ?? $row['sourceStoreId'] ?? $row['fromStoreId'] ?? null
        );
        $storeToId = IikoClient::optionalString(
            $row['storeToId'] ?? $row['targetStoreId'] ?? $row['toStoreId'] ?? null
        );
        $storeId = IikoClient::optionalString(
            $row['storeId'] ?? $storeFromId ?? $storeToId ?? null
        );

        return [
            'id' => IikoClient::scalarString($row['id'] ?? $row['documentId'] ?? ''),
            'number' => IikoClient::scalarString($row['number'] ?? $row['documentNumber'] ?? ''),
            'date' => $this->normalizeIsoDate($row['date'] ?? $row['dateIncoming'] ?? $row['documentDate'] ?? null),
            'documentType' => $type,
            'status' => $this->normalizeStatus($row['status'] ?? $row['state'] ?? null),
            'comment' => IikoClient::optionalString($row['comment'] ?? $row['description'] ?? null),
            'sum' => $this->toFloat($row['sum'] ?? $row['totalSum'] ?? $row['amount'] ?? 0),
            'storeId' => $storeId,
            'storeFromId' => $storeFromId,
            'storeToId' => $storeToId,
            'storeFromName' => IikoClient::optionalString(
                $row['storeFromName'] ?? $row['sourceStoreName'] ?? $row['fromStoreName'] ?? null
            ),
            'storeToName' => IikoClient::optionalString(
                $row['storeToName'] ?? $row['targetStoreName'] ?? $row['toStoreName'] ?? null
            ),
            'items' => [],
        ];
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function normalizeDocumentDetail(array $row, string $fallbackType): array
    {
        $summary = $this->normalizeDocumentSummary($row);
        if ($summary['documentType'] === '') {
            $summary['documentType'] = $fallbackType;
        }
        $summary['items'] = $this->normalizeDocumentItems($row);

        return $summary;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<int, array{productId: string, productName: ?string, amount: float, price: ?float, sum: ?float}>
     */
    private function normalizeDocumentItems(array $row): array
    {
        $raw = $row['items'] ?? $row['lines'] ?? $row['documentItems'] ?? [];
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }
            $productId = IikoClient::scalarString(
                $item['productId'] ?? $item['product'] ?? $item['nomenclatureId'] ?? ''
            );
            if ($productId === '') {
                continue;
            }
            $amount = $this->toFloat($item['amount'] ?? $item['quantity'] ?? $item['qty'] ?? 0);
            $price = isset($item['price']) || isset($item['cost']) || isset($item['unitPrice'])
                ? $this->toFloat($item['price'] ?? $item['cost'] ?? $item['unitPrice'] ?? 0)
                : null;
            $sum = isset($item['sum']) || isset($item['total']) || isset($item['lineSum'])
                ? $this->toFloat($item['sum'] ?? $item['total'] ?? $item['lineSum'] ?? 0)
                : ($price !== null ? round($amount * $price, 2) : null);

            $out[] = [
                'productId' => $productId,
                'productName' => IikoClient::optionalString(
                    $item['productName'] ?? $item['name'] ?? $item['product'] ?? null
                ),
                'amount' => $amount,
                'price' => $price,
                'sum' => $sum,
            ];
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function resolveDocumentType(array $row): string
    {
        $type = IikoClient::scalarString(
            $row['documentType'] ?? $row['type'] ?? $row['documentTypeName'] ?? ''
        );
        if ($type !== '') {
            return $type;
        }
        foreach (self::DOCUMENT_TYPE_GROUPS as $types) {
            foreach ($types as $candidate) {
                if (!empty($row[$candidate]) || !empty($row[lcfirst($candidate)])) {
                    return $candidate;
                }
            }
        }

        return '';
    }

    /**
     * @param mixed $status
     */
    private function normalizeStatus($status): string
    {
        $raw = IikoClient::scalarString($status);
        if ($raw === '') {
            return 'unknown';
        }
        $lower = mb_strtolower($raw);
        if (in_array($lower, ['processed', 'posted', 'approved', 'проведен', 'проведён', 'closed'], true)) {
            return 'posted';
        }
        if (in_array($lower, ['draft', 'new', 'черновик', 'created'], true)) {
            return 'draft';
        }
        if (in_array($lower, ['deleted', 'removed', 'удален', 'удалён', 'cancelled'], true)) {
            return 'deleted';
        }

        return $raw;
    }

    /**
     * @param mixed $value
     */
    private function normalizeIsoDate($value): string
    {
        $s = IikoClient::scalarString($value);
        if ($s === '') {
            return '';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $s)) {
            try {
                $dt = new \DateTimeImmutable($s);

                return $dt->format('c');
            } catch (\Throwable $e) {
                return $s;
            }
        }

        return $s;
    }

    private function formatCloudDateTime(string $date, bool $startOfDay): string
    {
        $date = substr(trim($date), 0, 10);
        if ($date === '') {
            $date = gmdate('Y-m-d');
        }
        $time = $startOfDay ? '00:00:00.000' : '23:59:59.999';

        return $date . ' ' . $time;
    }

    /**
     * @param mixed $value
     */
    private function toFloat($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        $s = IikoClient::scalarString($value, '0');
        $s = str_replace(',', '.', $s);

        return (float) $s;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function looksLikeDocument(array $row): bool
    {
        return isset($row['id']) || isset($row['documentId']) || isset($row['documentType']) || isset($row['type']);
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array<string, mixed>
     */
    private function request(string $path, array $body, bool $withAuth = true): array
    {
        if ($withAuth && $this->token === null) {
            $this->authenticate();
        }

        $url = $this->baseUrl . $path;
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: dbt-hub-iiko-cloud/1.0',
        ];
        if ($withAuth && $this->token !== null) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        $res = $this->httpPost($url, json_encode($body, JSON_UNESCAPED_UNICODE), $headers);
        if ($res['status'] === 401 && $withAuth) {
            $this->token = null;
            $this->authenticate();
            $headers = [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: dbt-hub-iiko-cloud/1.0',
                'Authorization: Bearer ' . $this->token,
            ];
            $res = $this->httpPost($url, json_encode($body, JSON_UNESCAPED_UNICODE), $headers);
        }

        if ($res['status'] < 200 || $res['status'] >= 300) {
            throw new \RuntimeException(
                'iikoCloud ' . $path . ' → HTTP ' . $res['status'] . ': ' . mb_substr(trim($res['body']), 0, 400)
            );
        }

        $decoded = json_decode($res['body'], true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('iikoCloud ' . $path . ': не удалось разобрать JSON');
        }

        return $decoded;
    }

    /**
     * @param array<int, string> $headers
     *
     * @return array{status: int, body: string}
     */
    private function httpPost(string $url, string $body, array $headers): array
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_CONNECTTIMEOUT => 30,
            ]);
            $responseBody = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($responseBody === false) {
                $err = curl_error($ch);
                curl_close($ch);
                throw new \RuntimeException('iikoCloud HTTP: ' . $err);
            }
            curl_close($ch);

            return ['status' => $status, 'body' => (string) $responseBody];
        }

        $headerLines = implode("\r\n", $headers);
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => $headerLines . "\r\n",
                'content' => $body,
                'timeout' => 120,
                'ignore_errors' => true,
            ],
        ]);
        $responseBody = @file_get_contents($url, false, $ctx);
        $status = 0;
        if (isset($http_response_header[0]) && preg_match('/\d{3}/', $http_response_header[0], $m)) {
            $status = (int) $m[0];
        }

        return ['status' => $status, 'body' => $responseBody !== false ? (string) $responseBody : ''];
    }

    /**
     * @param string[]|null $uiTypes
     *
     * @return string[]
     */
    public static function expandDocumentTypes(?array $uiTypes): array
    {
        if ($uiTypes === null || $uiTypes === []) {
            $all = [];
            foreach (self::DOCUMENT_TYPE_GROUPS as $types) {
                foreach ($types as $t) {
                    $all[] = $t;
                }
            }

            return array_values(array_unique($all));
        }
        $out = [];
        foreach ($uiTypes as $key) {
            $k = (string) $key;
            if (isset(self::DOCUMENT_TYPE_GROUPS[$k])) {
                foreach (self::DOCUMENT_TYPE_GROUPS[$k] as $t) {
                    $out[] = $t;
                }
            } else {
                $out[] = $k;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * @param string[]|null $uiStatuses
     *
     * @return string[]
     */
    public static function expandStatuses(?array $uiStatuses): array
    {
        if ($uiStatuses === null || $uiStatuses === []) {
            return [];
        }
        $map = [
            'posted' => ['PROCESSED', 'POSTED', 'APPROVED', 'Processed', 'Posted'],
            'draft' => ['DRAFT', 'NEW', 'Draft', 'New'],
            'deleted' => ['DELETED', 'REMOVED', 'Deleted', 'Removed'],
        ];
        $out = [];
        foreach ($uiStatuses as $s) {
            $key = (string) $s;
            if (isset($map[$key])) {
                foreach ($map[$key] as $api) {
                    $out[] = $api;
                }
            } else {
                $out[] = $key;
            }
        }

        return array_values(array_unique($out));
    }
}
