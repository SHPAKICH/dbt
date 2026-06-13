<?php

namespace app\services;

/**
 * Клиент iiko Server API (Resto API).
 * Авторизация: GET /resto/api/auth?login=&pass=sha1(password)
 */
class IikoClient
{
    private string $baseUrl;
    private string $login;
    private string $password;
    private ?string $token = null;

    /** @var array<string, array>|null */
    private ?array $olapTransactionsColumns = null;

    /** @var array<string, array>|null */
    private ?array $olapSalesColumns = null;

    public function __construct(string $baseUrl, string $login, string $password)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->login = $login;
        $this->password = $password;
    }

    /**
     * iiko XML/JSON часто отдаёт вложенные структуры вместо строки (id в массиве и т.п.).
     *
     * @param mixed $value
     */
    public static function scalarString($value, string $default = ''): string
    {
        if ($value === null || $value === '') {
            return $default;
        }
        if (is_string($value) || is_int($value) || is_float($value) || is_bool($value)) {
            return (string) $value;
        }
        if (is_array($value)) {
            foreach (['id', 'uuid', 'guid', 'code', '#text', '@', 'text', 'value', 'name'] as $key) {
                if (array_key_exists($key, $value)) {
                    $inner = self::scalarString($value[$key], '');
                    if ($inner !== '') {
                        return $inner;
                    }
                }
            }
            if (isset($value['@attributes']) && is_array($value['@attributes'])) {
                $fromAttr = self::scalarString($value['@attributes'], '');
                if ($fromAttr !== '') {
                    return $fromAttr;
                }
            }
            foreach ($value as $v) {
                if (is_scalar($v)) {
                    return (string) $v;
                }
                if (is_array($v)) {
                    $nested = self::scalarString($v, '');
                    if ($nested !== '') {
                        return $nested;
                    }
                }
            }
            return $default;
        }
        if (is_object($value)) {
            if ($value instanceof \SimpleXMLElement) {
                return trim((string) $value);
            }
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }
            return $default;
        }
        return $default;
    }

    /**
     * @param mixed $value
     */
    public static function scalarOrNull($value): ?string
    {
        $s = self::scalarString($value, '');
        return $s === '' ? null : $s;
    }

    /** @alias scalarOrNull */
    public static function optionalString($value): ?string
    {
        return self::scalarOrNull($value);
    }

    public function login(): string
    {
        $passHash = sha1($this->password);
        // Токен приходит как text/plain — не запрашивать только JSON/XML (иначе HTTP 406)
        $res = $this->httpGet('/resto/api/auth', [
            'login' => $this->login,
            'pass' => $passHash,
        ], '*/*');
        if ($res['status'] !== 200) {
            $hint = $res['status'] === 406
                ? ' (сервер не принял заголовок Accept — проверьте URL и доступность iiko)'
                : '';
            throw new \RuntimeException(
                'iiko auth failed: HTTP ' . $res['status'] . $hint . '. ' . mb_substr(trim($res['body']), 0, 200)
            );
        }
        $raw = trim(preg_replace('/^\xEF\xBB\xBF/', '', $res['body']));
        $token = $raw;
        if (strncmp($raw, '<', 1) === 0) {
            $xml = @simplexml_load_string($raw);
            if ($xml !== false) {
                $token = self::scalarString($xml->key ?? $xml->token ?? $xml->string ?? $raw, $raw);
            }
        } elseif (strncmp($raw, '{', 1) === 0 || strncmp($raw, '[', 1) === 0) {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $token = self::scalarString(
                    $json['key'] ?? $json['token'] ?? $json['access_token'] ?? $raw,
                    $raw
                );
            }
        }
        if ($token === '' || strlen($token) > 200) {
            throw new \RuntimeException('iiko auth: неожиданный ответ: ' . mb_substr($raw, 0, 200));
        }
        $this->token = $token;
        return $token;
    }

    public function logout(): void
    {
        if ($this->token === null) {
            return;
        }
        try {
            $this->httpGet('/resto/api/logout', ['key' => $this->token], '*/*');
        } catch (\Throwable $e) {
            // ignore
        } finally {
            $this->token = null;
        }
    }

    public function ping(): array
    {
        $this->ensureToken();
        try {
            $res = $this->httpGet('/resto/api/version', ['key' => $this->token], '*/*');
            $text = trim(strip_tags($res['body']));
            return ['ok' => true, 'version' => $text];
        } catch (\Throwable $e) {
            return ['ok' => true];
        }
    }

    /**
     * @return array<int, array{id: string, name: string, code?: string}>
     */
    public function getStores(): array
    {
        $parsed = $this->request('/resto/api/corporation/stores', [], 'xml');
        return $this->mapCorporateList($parsed, 'store');
    }

    /**
     * @return array<int, array{productId: string, productName?: string, storeId: string, amount: float, unit?: string}>
     */
    public function getBalance(array $opts = []): array
    {
        $ts = $opts['timestamp'] ?? date('Y-m-d\TH:i:s');
        $params = ['timestamp' => $ts];
        if (!empty($opts['department'])) {
            $params['department'] = $opts['department'];
        }
        if (!empty($opts['store'])) {
            $params['store'] = $opts['store'];
        }
        $data = $this->request('/resto/api/v2/reports/balance/stores', $params, 'auto');
        $list = is_array($data) && isset($data[0]) ? $data : $this->flattenCorporateList($data);
        $rows = [];
        foreach ($list as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rows[] = [
                'productId' => self::scalarString($row['product'] ?? $row['productId'] ?? ''),
                'productName' => self::optionalString($row['productName'] ?? null),
                'productNum' => self::optionalString($row['productNum'] ?? null),
                'productCode' => self::optionalString($row['productCode'] ?? null),
                'storeId' => self::scalarString($row['store'] ?? $row['storeId'] ?? ''),
                'storeName' => self::optionalString($row['storeName'] ?? null),
                'amount' => (float) self::scalarString($row['amount'] ?? 0, '0'),
                'unit' => self::optionalString($row['unit'] ?? null),
            ];
        }
        return $rows;
    }

    /**
     * @return array<int, array{id: string, name: string, code?: string, mainUnit?: string}>
     */
    public function getProducts(): array
    {
        try {
            $data = $this->request('/resto/api/v2/entities/products/list', [], 'auto');
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (
                strpos($msg, 'HTTP 403') === false
                && strpos($msg, 'HTTP 401') === false
                && stripos($msg, 'Permission denied') === false
            ) {
                throw $e;
            }
            $data = $this->request('/resto/api/products', ['includeDeleted' => 'false'], 'xml');
        }
        $list = is_array($data) && isset($data[0]) ? $data : $this->flattenCorporateList($data);
        $out = [];
        foreach ($list as $p) {
            if (!is_array($p)) {
                continue;
            }
            $id = self::scalarString($p['id'] ?? $p['uuid'] ?? $p['guid'] ?? '');
            if ($id === '') {
                continue;
            }
            $name = self::scalarString(
                $p['name'] ?? $p['fullName'] ?? $p['code'] ?? $p['num'] ?? $id,
                $id
            );
            $out[] = [
                'id' => $id,
                'name' => $name,
                'code' => self::optionalString($p['code'] ?? null),
                'num' => self::optionalString($p['num'] ?? null),
                'mainUnit' => self::optionalString($p['mainUnit'] ?? $p['mainUnitName'] ?? null),
                'unitCost' => $this->extractProductUnitCost($p),
            ];
        }
        return $out;
    }

    /**
     * Себестоимость / цена единицы из карточки продукта iiko (если доступна в API).
     *
     * @param array<string, mixed> $product
     */
    private function extractProductUnitCost(array $product): ?float
    {
        $keys = [
            'costOne',
            'estimatedPurchasePrice',
            'cost',
            'defaultSalePrice',
            'price',
        ];
        foreach ($keys as $key) {
            if (!isset($product[$key]) || $product[$key] === '' || $product[$key] === null) {
                continue;
            }
            $value = (float) self::scalarString($product[$key], '0');
            if ($value > 0) {
                return round($value, 4);
            }
        }

        return null;
    }

    /**
     * Торговые предприятия (точки) из iiko.
     *
     * @return array<int, array{id: string, name: string, code?: string}>
     */
    public function getDepartments(): array
    {
        $paths = [
            '/resto/api/corporation/departments',
            '/resto/api/corporation/groups',
        ];
        $lastError = null;
        foreach ($paths as $path) {
            try {
                $parsed = $this->request($path, [], 'xml');

                return $this->mapCorporateList($parsed, 'department');
            } catch (\Throwable $e) {
                $lastError = $e;
            }
        }
        if ($lastError !== null) {
            throw $lastError;
        }

        return [];
    }

    /**
     * Список чеков (заказов) за период — OLAP SALES.
     *
     * @return array{from: string, to: string, checks: array<int, array<string, mixed>>, warning: ?string}
     */
    public function getSalesChecksList(string $from, string $to, ?string $departmentId = null): array
    {
        $from = substr($from, 0, 10);
        $to = substr($to, 0, 10);
        $rows = $this->requestOlapSalesChecks($from, $to, $departmentId, null);
        $checks = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $check = $this->normalizeSalesCheckRow($row);
            if ($check === null) {
                continue;
            }
            $key = ($check['orderId'] ?? '') . '::' . ($check['orderNum'] ?? '') . '::' . ($check['date'] ?? '');
            $checks[$key] = $check;
        }
        $list = array_values($checks);
        usort($list, static function ($a, $b) {
            return strcmp($b['date'] ?? '', $a['date'] ?? '');
        });

        $schema = $this->resolveOlapSalesSchema(false);
        $totalSum = $this->fetchSalesRevenueTotal($from, $to, $departmentId, $schema);

        return [
            'from' => $from,
            'to' => $to,
            'checks' => $list,
            'totalSum' => $totalSum,
            'warning' => $this->buildSalesSumFieldWarning($schema),
        ];
    }

    /**
     * Позиции чека.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSalesCheckItems(
        string $from,
        string $to,
        string $orderId,
        ?string $departmentId = null
    ): array {
        $from = substr($from, 0, 10);
        $to = substr($to, 0, 10);
        $orderId = trim($orderId);
        if ($orderId === '') {
            return [];
        }
        $rows = $this->requestOlapSalesChecks($from, $to, $departmentId, $orderId);
        $items = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $item = $this->normalizeSalesCheckItemRow($row);
            if ($item === null) {
                continue;
            }
            $items[] = $item;
        }
        usort($items, static function ($a, $b) {
            return ($b['sum'] ?? 0) <=> ($a['sum'] ?? 0);
        });

        return $items;
    }

    /**
     * Сводка по смене / периоду: выручка по часам, типам оплаты, типам заказов, дням.
     *
     * @return array{
     *   from: string,
     *   to: string,
     *   summary: array{totalSum: float, totalChecks: int, avgCheck: float},
     *   byHour: array<int, array{label: string, hour: int|null, sum: float, checks: int}>,
     *   byPaymentType: array<int, array{label: string, sum: float, checks: int, share: float}>,
     *   byOrderType: array<int, array{label: string, sum: float, checks: int, share: float}>,
     *   byDay: array<int, array{label: string, sum: float, checks: int}>
     * }
     */
    public function getSalesShiftSummary(string $from, string $to, ?string $departmentId = null): array
    {
        $from = substr($from, 0, 10);
        $to = substr($to, 0, 10);
        $salesColumns = $this->fetchOlapSalesColumns();
        $schema = $this->resolveOlapSalesSchema(false);
        $sumField = $schema['sumField'];
        $checksField = $schema['orderAggField'] ?? 'UniqOrderId';

        $hourField = $this->pickOlapGroupField($salesColumns, [
            'HourOpen',
            'OpenHour',
            'HourClose',
            'DateTime.HourOpen',
        ]) ?? 'HourOpen';

        $filters = $this->buildSalesOlapFilters($from, $to, $departmentId, $salesColumns, $schema, null);

        $byHourRows = $this->runOlapSalesWithRetry(
            [$hourField],
            array_values(array_filter([$sumField, $checksField])),
            $filters,
            $salesColumns,
            $schema['dateField'],
            $from,
            $to,
            $schema
        );
        $byHour = $this->normalizeShiftBreakdownRows($byHourRows, $hourField, $sumField, $checksField, 'hour');

        $byPaymentType = [];
        if ($schema['payTypeField'] !== null && $schema['payTypeField'] !== '') {
            $rows = $this->runOlapSalesWithRetry(
                [$schema['payTypeField']],
                array_values(array_filter([$sumField, $checksField])),
                $filters,
                $salesColumns,
                $schema['dateField'],
                $from,
                $to,
                $schema
            );
            $byPaymentType = $this->normalizeShiftBreakdownRows($rows, $schema['payTypeField'], $sumField, $checksField, 'label');
        }

        $byOrderType = [];
        if ($schema['orderTypeField'] !== null && $schema['orderTypeField'] !== '') {
            $rows = $this->runOlapSalesWithRetry(
                [$schema['orderTypeField']],
                array_values(array_filter([$sumField, $checksField])),
                $filters,
                $salesColumns,
                $schema['dateField'],
                $from,
                $to,
                $schema
            );
            $byOrderType = $this->normalizeShiftBreakdownRows($rows, $schema['orderTypeField'], $sumField, $checksField, 'label');
        }

        $byDayRows = $this->runOlapSalesWithRetry(
            [$schema['dateField']],
            array_values(array_filter([$sumField, $checksField])),
            $filters,
            $salesColumns,
            $schema['dateField'],
            $from,
            $to,
            $schema
        );
        $byDay = $this->normalizeShiftBreakdownRows($byDayRows, $schema['dateField'], $sumField, $checksField, 'day');

        $totalSum = $this->fetchSalesRevenueTotal($from, $to, $departmentId, $schema);
        $totalChecks = 0;
        foreach ($byHour as $row) {
            $totalChecks += (int) ($row['checks'] ?? 0);
        }
        if ($totalChecks <= 0 && $byPaymentType !== []) {
            foreach ($byPaymentType as $row) {
                $totalChecks += (int) ($row['checks'] ?? 0);
            }
        }

        $this->applySharePercent($byPaymentType, $totalSum);
        $this->applySharePercent($byOrderType, $totalSum);

        $avgCheck = $totalChecks > 0 ? round($totalSum / $totalChecks, 2) : 0.0;

        return [
            'from' => $from,
            'to' => $to,
            'summary' => [
                'totalSum' => $totalSum,
                'totalChecks' => $totalChecks,
                'avgCheck' => $avgCheck,
            ],
            'byHour' => $byHour,
            'byPaymentType' => $byPaymentType,
            'byOrderType' => $byOrderType,
            'byDay' => $byDay,
            'warning' => $this->buildSalesSumFieldWarning($schema),
        ];
    }

    /**
     * Отчёт по продажам блюд за период (OLAP SALES, группировка по блюду).
     *
     * @param array<int, string>|null $selectedNames имена блюд для блока «выбранная группа» (сопоставление без учёта регистра)
     *
     * @return array{
     *   from: string,
     *   to: string,
     *   summary: array{totalSum: float, totalQty: float, productCount: int},
     *   products: array<int, array{name: string, sum: float, qty: float, sharePercent: float, avgPrice: float}>,
     *   selected: ?array<string, mixed>
     * }
     */
    public function getSalesProductsReport(
        string $from,
        string $to,
        ?string $departmentId = null,
        ?array $selectedNames = null,
        ?array $productKeywords = null
    ): array {
        $from = substr($from, 0, 10);
        $to = substr($to, 0, 10);
        $salesColumns = $this->fetchOlapSalesColumns();
        $schema = $this->resolveOlapSalesSchema(false);
        $filters = $this->buildSalesOlapFilters($from, $to, $departmentId, $salesColumns, $schema, null);

        $rows = $this->runOlapSalesWithRetry(
            [$schema['productField']],
            array_values(array_filter([$schema['sumField'], $schema['amountField']])),
            $filters,
            $salesColumns,
            $schema['dateField'],
            $from,
            $to,
            $schema
        );

        $products = [];
        $totalSum = 0.0;
        $totalQty = 0.0;
        $productField = $schema['productField'];
        $sumField = $schema['sumField'];
        $amountField = $schema['amountField'];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = self::scalarString($row[$productField] ?? $row['Product.Name'] ?? $row['DishName'] ?? '', '');
            if ($name === '') {
                continue;
            }
            $sum = abs((float) self::scalarString($row[$sumField] ?? $row['DishSumInt'] ?? '0', '0'));
            $qty = abs((float) self::scalarString($row[$amountField] ?? $row['DishAmountInt'] ?? '0', '0'));
            if (isset($products[$name])) {
                $products[$name]['sum'] += $sum;
                $products[$name]['qty'] += $qty;
            } else {
                $products[$name] = ['name' => $name, 'sum' => $sum, 'qty' => $qty];
            }
            $totalSum += $sum;
            $totalQty += $qty;
        }

        $list = array_values($products);
        foreach ($list as &$item) {
            $item['sum'] = round((float) $item['sum'], 2);
            $item['qty'] = round((float) $item['qty'], 3);
            $item['sharePercent'] = $totalSum > 0
                ? round($item['sum'] / $totalSum * 100, 1)
                : 0.0;
            $item['avgPrice'] = $item['qty'] > 0
                ? round($item['sum'] / $item['qty'], 2)
                : 0.0;
        }
        unset($item);

        usort($list, static function ($a, $b) {
            return ($b['sum'] ?? 0) <=> ($a['sum'] ?? 0);
        });

        if (($selectedNames === null || $selectedNames === []) && $productKeywords !== null && $productKeywords !== []) {
            $selectedNames = $this->matchProductNamesByKeywords($list, $productKeywords);
        }

        $selected = null;
        $normalizedSelected = $this->normalizeProductNameKeys($selectedNames);
        if ($normalizedSelected !== []) {
            $selected = $this->buildSelectedProductsAnalytics($list, $totalSum, $totalQty, $normalizedSelected);
        }

        return [
            'from' => $from,
            'to' => $to,
            'summary' => [
                'totalSum' => round($totalSum, 2),
                'totalQty' => round($totalQty, 3),
                'productCount' => count($list),
            ],
            'products' => $list,
            'selected' => $selected,
        ];
    }

    /**
     * @param array<int, array{name: string}> $products
     * @param array<int, string> $keywords
     *
     * @return array<int, string>
     */
    private function matchProductNamesByKeywords(array $products, array $keywords): array
    {
        $normalizedKeywords = [];
        foreach ($keywords as $keyword) {
            $kw = mb_strtolower(trim((string) $keyword));
            if ($kw !== '') {
                $normalizedKeywords[] = $kw;
            }
        }
        if ($normalizedKeywords === []) {
            return [];
        }

        $matched = [];
        foreach ($products as $product) {
            $name = (string) ($product['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $nameLower = mb_strtolower($name);
            foreach ($normalizedKeywords as $kw) {
                if (mb_strpos($nameLower, $kw) !== false) {
                    $matched[$name] = true;
                    break;
                }
            }
        }

        return array_keys($matched);
    }

    /**
     * @param array<int, string>|null $names
     *
     * @return array<string, true>
     */
    private function normalizeProductNameKeys(?array $names): array
    {
        if ($names === null || $names === []) {
            return [];
        }
        $out = [];
        foreach ($names as $name) {
            $key = mb_strtolower(trim((string) $name));
            if ($key !== '') {
                $out[$key] = true;
            }
        }

        return $out;
    }

    /**
     * @param array<int, array{name: string, sum: float, qty: float, sharePercent: float, avgPrice: float}> $products
     * @param array<string, true> $selectedKeys
     *
     * @return array<string, mixed>
     */
    private function buildSelectedProductsAnalytics(
        array $products,
        float $totalSum,
        float $totalQty,
        array $selectedKeys
    ): array {
        $items = [];
        $selSum = 0.0;
        $selQty = 0.0;
        foreach ($products as $p) {
            $key = mb_strtolower(trim((string) ($p['name'] ?? '')));
            if ($key === '' || !isset($selectedKeys[$key])) {
                continue;
            }
            $items[] = $p;
            $selSum += (float) ($p['sum'] ?? 0);
            $selQty += (float) ($p['qty'] ?? 0);
        }

        $matchedKeys = [];
        foreach ($items as $it) {
            $matchedKeys[mb_strtolower(trim((string) ($it['name'] ?? '')))] = true;
        }
        $notFound = [];
        foreach (array_keys($selectedKeys) as $key) {
            if (!isset($matchedKeys[$key])) {
                $notFound[] = $key;
            }
        }

        $restSum = max(0, round($totalSum - $selSum, 2));

        return [
            'itemCount' => count($items),
            'sum' => round($selSum, 2),
            'qty' => round($selQty, 3),
            'sharePercent' => $totalSum > 0 ? round($selSum / $totalSum * 100, 1) : 0.0,
            'qtySharePercent' => $totalQty > 0 ? round($selQty / $totalQty * 100, 1) : 0.0,
            'avgPrice' => $selQty > 0 ? round($selSum / $selQty, 2) : 0.0,
            'restSum' => $restSum,
            'restSharePercent' => $totalSum > 0 ? round($restSum / $totalSum * 100, 1) : 0.0,
            'items' => $items,
            'notFoundCount' => count($notFound),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSalesOlapFilters(
        string $from,
        string $to,
        ?string $departmentId,
        array $salesColumns,
        array $schema,
        ?string $orderIdFilter
    ): array {
        $dateField = $schema['dateField'];
        $filters = [
            $dateField => $this->olapDateRangeFilter($dateField, $from, $to, $salesColumns),
        ];
        if ($departmentId !== null && $departmentId !== '') {
            $filters[$schema['departmentIdField']] = $this->olapIncludeValuesFilter(
                $schema['departmentIdField'],
                [$departmentId],
                $salesColumns
            );
        }
        if ($orderIdFilter !== null && $orderIdFilter !== '') {
            $filters[$schema['orderFilterField']] = $this->olapIncludeValuesFilter(
                $schema['orderFilterField'],
                [$orderIdFilter],
                $salesColumns
            );
        }

        return $filters;
    }

    /**
     * @param string[] $groupBy
     * @param string[] $aggregate
     * @param array<string, mixed> $filters
     * @param array<string, array> $salesColumns
     * @param array<string, mixed> $schema
     *
     * @return array<int, array<string, mixed>>
     */
    private function runOlapSalesWithRetry(
        array $groupBy,
        array $aggregate,
        array $filters,
        array $salesColumns,
        string $dateField,
        string $from,
        string $to,
        array $schema
    ): array {
        $groupBy = array_values(array_unique(array_filter($groupBy)));
        $aggregate = array_values(array_unique(array_filter($aggregate)));

        $lastError = 'не удалось выполнить OLAP SALES';
        $attempts = 0;
        $dateFilterAttempts = 0;
        while ($attempts < 12) {
            $attempts++;
            try {
                return $this->executeOlapSalesRequest($groupBy, $aggregate, $filters);
            } catch (\RuntimeException $e) {
                $lastError = $e->getMessage();
                if ($this->isOlapClassCastError($lastError) && $attempts <= 2) {
                    continue;
                }
                if ($this->isOlapDatePeriodRejected($lastError) && $dateFilterAttempts < 2) {
                    $dateFilterAttempts++;
                    $filters[$dateField] = $this->olapDateRangeFilterAlternate(
                        $dateField,
                        $from,
                        $to,
                        $salesColumns,
                        $dateFilterAttempts
                    );
                    continue;
                }
                $unknown = $this->extractUnknownOlapField($lastError);
                if ($unknown === null) {
                    throw $e;
                }
                $groupBy = array_values(array_filter($groupBy, static fn ($f) => $f !== $unknown));
                $aggregate = array_values(array_filter($aggregate, static fn ($f) => $f !== $unknown));
                if ($groupBy === [] && $aggregate === []) {
                    throw $e;
                }
            }
        }
        throw new \RuntimeException('iiko OLAP SALES: ' . $lastError);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeShiftBreakdownRows(
        array $rows,
        string $labelField,
        string $sumField,
        ?string $checksField,
        string $sortMode
    ): array {
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $label = self::scalarString($row[$labelField] ?? '', '');
            if ($label === '') {
                continue;
            }
            $sum = abs((float) self::scalarString(
                $row[$sumField] ?? $row['DishDiscountSumInt'] ?? $row['DishSumInt'] ?? '0',
                '0'
            ));
            $checks = 0;
            if ($checksField !== null && $checksField !== '') {
                $checks = (int) round((float) self::scalarString($row[$checksField] ?? '0', '0'));
            }
            $hour = null;
            if ($sortMode === 'hour') {
                if (preg_match('/(\d{1,2})/', $label, $m)) {
                    $hour = (int) $m[1];
                    if ($hour >= 0 && $hour <= 23) {
                        $label = str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':00';
                    }
                }
            } elseif ($sortMode === 'day') {
                $label = substr($label, 0, 10);
            }
            $out[] = [
                'label' => $label,
                'hour' => $hour,
                'sum' => round($sum, 2),
                'checks' => $checks,
            ];
        }

        if ($sortMode === 'hour') {
            usort($out, static function ($a, $b) {
                return ($a['hour'] ?? 99) <=> ($b['hour'] ?? 99);
            });
        } elseif ($sortMode === 'day') {
            usort($out, static function ($a, $b) {
                return strcmp($a['label'] ?? '', $b['label'] ?? '');
            });
        } else {
            usort($out, static function ($a, $b) {
                return ($b['sum'] ?? 0) <=> ($a['sum'] ?? 0);
            });
        }

        return $out;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function applySharePercent(array &$rows, float $totalSum): void
    {
        foreach ($rows as &$row) {
            $sum = (float) ($row['sum'] ?? 0);
            $row['share'] = $totalSum > 0 ? round($sum / $totalSum * 100, 1) : 0.0;
        }
        unset($row);
    }

    /**
     * Движение по товару из документов iiko (как отчёт «Движение товара» в Chain).
     *
     * @return array{from: string, to: string, groups: array<int, array>}
     */
    public function getProductDocumentHistory(
        string $storeId,
        string $productId,
        string $from,
        string $to,
        ?string $productNum = null,
        ?string $productCode = null
    ): array {
        $from = substr($from, 0, 10);
        $to = substr($to, 0, 10);
        $productNum = $productNum !== null && $productNum !== '' ? trim($productNum) : null;
        $productCode = $productCode !== null && $productCode !== '' ? trim($productCode) : null;

        $groupDefs = $this->productDocumentGroupDefs();
        $bucket = [];
        foreach ($groupDefs as $def) {
            $bucket[$def['id']] = [];
        }

        $warnings = [];
        try {
            $storeRows = $this->fetchStoreOperationsReport($storeId, $from, $to);
            foreach ($storeRows as $row) {
                if (!$this->storeReportRowMatchesProduct($row, $productId, $productNum, $productCode)) {
                    continue;
                }
                $groupId = $this->classifyStoreDocumentGroup(
                    self::scalarString($row['documentType'] ?? $row['type'] ?? $row['operationType'] ?? '')
                );
                if (!isset($bucket[$groupId])) {
                    $groupId = 'other';
                }
                $entry = $this->buildEntryFromStoreReportRow($row, $groupId);
                if ($entry !== null) {
                    $bucket[$groupId][] = $entry;
                }
            }
        } catch (\Throwable $e) {
            $warnings[] = 'Отчёт по складским операциям: ' . mb_substr($e->getMessage(), 0, 200);
        }

        $exportFallback = [
            'saleAct' => ['outgoingInvoice'],
            'incomingInvoice' => ['incomingInvoice'],
        ];
        foreach ($exportFallback as $groupId => $types) {
            if (!empty($bucket[$groupId])) {
                continue;
            }
            $def = null;
            foreach ($groupDefs as $g) {
                if ($g['id'] === $groupId) {
                    $def = $g;
                    break;
                }
            }
            if ($def === null) {
                continue;
            }
            $typeErrors = [];
            foreach ($types as $type) {
                try {
                    $docs = $this->fetchDocumentsExport($type, $from, $to);
                } catch (\Throwable $e) {
                    $typeErrors[] = mb_substr($e->getMessage(), 0, 120);
                    continue;
                }
                foreach ($docs as $doc) {
                    if (!$this->documentMatchesStore($doc, $type, $storeId)) {
                        continue;
                    }
                    foreach ($doc['items'] as $it) {
                        if (!is_array($it) || !$this->itemMatchesProduct($it, $productId, $productNum, $productCode)) {
                            continue;
                        }
                        $entry = $this->buildDocumentEntry($doc, $it, $type, $def['defaultDir']);
                        if ($entry !== null) {
                            $bucket[$groupId][] = $entry;
                        }
                    }
                }
            }
            if (empty($bucket[$groupId]) && $typeErrors !== []) {
                $warnings[] = $def['title'] . ' — ' . implode('; ', $typeErrors);
            }
        }

        $groups = [];
        foreach ($groupDefs as $def) {
            $entries = $bucket[$def['id']] ?? [];
            usort($entries, static function ($a, $b) {
                return strcmp($b['date'] ?? '', $a['date'] ?? '');
            });
            $totalOut = 0.0;
            $totalIn = 0.0;
            foreach ($entries as $e) {
                if (($e['direction'] ?? '') === 'out') {
                    $totalOut += (float) ($e['amount'] ?? 0);
                } elseif (($e['direction'] ?? '') === 'in') {
                    $totalIn += (float) ($e['amount'] ?? 0);
                }
            }
            $groups[] = [
                'id' => $def['id'],
                'title' => $def['title'],
                'entries' => $entries,
                'totalOut' => round($totalOut, 4),
                'totalIn' => round($totalIn, 4),
            ];
        }

        return ['from' => $from, 'to' => $to, 'groups' => $groups, 'warnings' => $warnings];
    }

    /**
     * Расход (реализация + списание) и приход (приходные накладные) по товарам за период — из отчёта складских операций.
     *
     * @return array<string, array{out: float, in: float}>
     */
    public function getStoreProductPeriodMovement(string $storeId, string $from, string $to): array
    {
        $from = substr($from, 0, 10);
        $to = substr($to, 0, 10);
        $map = [];
        $storeRows = $this->fetchStoreOperationsReport($storeId, $from, $to);
        foreach ($storeRows as $row) {
            $pid = self::scalarString($row['productId'] ?? '');
            if ($pid === '') {
                continue;
            }
            $group = $this->classifyStoreDocumentGroup(
                self::scalarString($row['documentType'] ?? $row['type'] ?? $row['operationType'] ?? '')
            );
            if ($group !== 'saleAct' && $group !== 'writeoff' && $group !== 'incomingInvoice') {
                continue;
            }
            $delta = $this->storeReportRowMovementDelta($row, $group);
            if ($delta === null) {
                continue;
            }
            if (!isset($map[$pid])) {
                $map[$pid] = ['out' => 0.0, 'in' => 0.0];
            }
            if ($delta['out'] > 0) {
                $map[$pid]['out'] += $delta['out'];
            }
            if ($delta['in'] > 0) {
                $map[$pid]['in'] += $delta['in'];
            }
        }
        foreach ($map as $pid => $v) {
            $map[$pid] = [
                'out' => round($v['out'], 4),
                'in' => round($v['in'], 4),
            ];
        }

        return $map;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array{out: float, in: float}|null
     */
    private function storeReportRowMovementDelta(array $row, string $groupId): ?array
    {
        $amountIn = abs((float) self::scalarString($row['amountIn'] ?? '0', '0'));
        $amountOut = abs((float) self::scalarString($row['amountOut'] ?? '0', '0'));
        $amount = abs((float) self::scalarString($row['amount'] ?? '0', '0'));
        $out = 0.0;
        $in = 0.0;
        if ($groupId === 'incomingInvoice') {
            if ($amountIn > 0) {
                $in = $amountIn;
            } elseif ($amount > 0 && $amountOut <= 0) {
                $in = $amount;
            }
        } else {
            if ($amountOut > 0) {
                $out = $amountOut;
            } elseif ($amount > 0 && $amountIn <= 0) {
                $out = $amount;
            }
        }
        if ($out <= 0 && $in <= 0) {
            return null;
        }

        return ['out' => $out, 'in' => $in];
    }

    /**
     * @return array<int, array{id: string, title: string, defaultDir: string}>
     */
    private function productDocumentGroupDefs(): array
    {
        return [
            ['id' => 'saleAct', 'title' => 'Акты реализации', 'defaultDir' => 'out'],
            ['id' => 'writeoff', 'title' => 'Списания', 'defaultDir' => 'out'],
            ['id' => 'inventory', 'title' => 'Инвентаризация', 'defaultDir' => 'both'],
            ['id' => 'incomingInvoice', 'title' => 'Приход', 'defaultDir' => 'in'],
        ];
    }

    private function formatDateDmY(string $isoDate): string
    {
        $d = substr(trim($isoDate), 0, 10);
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $d, $m)) {
            return $d;
        }

        return $m[3] . '.' . $m[2] . '.' . $m[1];
    }

    /**
     * Отчёт «Складские операции» — основной источник движения, как в iikoChain.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchStoreOperationsReport(string $storeId, string $from, string $to): array
    {
        $paths = [
            '/resto/api/reports/storeOperations',
            '/resto/api/reports/store_operation',
        ];
        $params = [
            'dateFrom' => $this->formatDateDmY($from),
            'dateTo' => $this->formatDateDmY($to),
            'stores' => $storeId,
            'productDetalization' => 'true',
            'showCostCorrections' => 'false',
        ];
        $lastError = null;
        foreach ($paths as $path) {
            try {
                $parsed = $this->request($path, $params, 'xml');

                return $this->parseStoreOperationsXml($parsed);
            } catch (\Throwable $e) {
                $lastError = $e;
            }
        }
        if ($lastError !== null) {
            throw $lastError;
        }

        return [];
    }

    /**
     * @param mixed $parsed
     *
     * @return array<int, array<string, mixed>>
     */
    private function parseStoreOperationsXml($parsed): array
    {
        $rows = [];
        $this->walkForStoreReportRows($parsed, $rows);

        return $rows;
    }

    /**
     * @param mixed $node
     * @param array<int, array<string, mixed>> $rows
     */
    private function walkForStoreReportRows($node, array &$rows, int $depth = 0): void
    {
        if ($depth > 30 || !is_array($node)) {
            return;
        }
        if ($this->looksLikeStoreReportRow($node)) {
            $rows[] = $this->normalizeStoreReportRow($node);
        }
        if (isset($node[0]) && is_array($node[0])) {
            foreach ($node as $child) {
                if (is_array($child)) {
                    $this->walkForStoreReportRows($child, $rows, $depth + 1);
                }
            }

            return;
        }
        foreach ($node as $child) {
            if (is_array($child)) {
                $this->walkForStoreReportRows($child, $rows, $depth + 1);
            }
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private function looksLikeStoreReportRow(array $row): bool
    {
        $hasProduct = isset($row['productId']) || isset($row['product']) || isset($row['nomenclatureId'])
            || isset($row['productDto']);
        $hasDoc = isset($row['documentNumber']) || isset($row['documentNum']) || isset($row['documentType'])
            || isset($row['type']) || isset($row['document']) || isset($row['documentId']);

        return $hasProduct && $hasDoc;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function normalizeStoreReportRow(array $row): array
    {
        $product = $row['product'] ?? $row['productDto'] ?? null;
        $productId = self::extractItemProductId(is_array($product) ? $product : ['productId' => $product]);
        if ($productId === '' && isset($row['productId'])) {
            $productId = self::scalarString($row['productId']);
        }

        return [
            'productId' => $productId,
            'documentNumber' => self::optionalString(
                $row['documentNumber'] ?? $row['documentNum'] ?? $row['number'] ?? null
            ),
            'documentType' => self::optionalString(
                $row['documentType'] ?? $row['type'] ?? $row['operationType'] ?? $row['documentTypeName'] ?? null
            ),
            'date' => self::optionalString(
                $row['date'] ?? $row['dateIncoming'] ?? $row['documentDate'] ?? $row['operationDate'] ?? null
            ),
            'correspondence' => self::optionalString(
                $row['correspondence'] ?? $row['counteragent'] ?? $row['counterparty'] ?? $row['comment'] ?? null
            ),
            'amount' => $row['amount'] ?? $row['quantity'] ?? $row['primaryAmount'] ?? null,
            'amountIn' => $row['amountIn'] ?? $row['incoming'] ?? $row['income'] ?? $row['debit'] ?? null,
            'amountOut' => $row['amountOut'] ?? $row['outgoing'] ?? $row['outcome'] ?? $row['credit'] ?? null,
            'sum' => $row['sum'] ?? $row['documentSum'] ?? $row['totalSum'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function storeReportRowMatchesProduct(
        array $row,
        string $productId,
        ?string $productNum,
        ?string $productCode
    ): bool {
        return $this->itemMatchesProduct(
            [
                'productId' => $row['productId'] ?? '',
                'productNum' => $row['productNum'] ?? null,
                'productCode' => $row['productCode'] ?? null,
            ],
            $productId,
            $productNum,
            $productCode
        );
    }

    private function classifyStoreDocumentGroup(string $typeName): string
    {
        $t = mb_strtolower(trim($typeName));
        if ($t === '') {
            return 'other';
        }
        if (preg_match('/реализац|sale|outgoing|продаж/u', $t)) {
            return 'saleAct';
        }
        if (preg_match('/списан|writeoff|waste|брак/u', $t)) {
            return 'writeoff';
        }
        if (preg_match('/инвентар|inventory|пересчет|stocktaking/u', $t)) {
            return 'inventory';
        }
        if (preg_match('/приход|incoming|поступ|приходн/u', $t)) {
            return 'incomingInvoice';
        }

        return 'other';
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>|null
     */
    private function buildEntryFromStoreReportRow(array $row, string $groupId): ?array
    {
        $amountIn = abs((float) self::scalarString($row['amountIn'] ?? '0', '0'));
        $amountOut = abs((float) self::scalarString($row['amountOut'] ?? '0', '0'));
        $amount = abs((float) self::scalarString($row['amount'] ?? '0', '0'));
        $direction = 'out';
        if ($amountIn > 0 && $amountOut <= 0) {
            $amount = $amountIn;
            $direction = 'in';
        } elseif ($amountOut > 0 && $amountIn <= 0) {
            $amount = $amountOut;
            $direction = 'out';
        } elseif ($amount <= 0) {
            return null;
        } elseif ($groupId === 'incomingInvoice') {
            $direction = 'in';
        } elseif ($groupId === 'inventory') {
            $direction = $amountIn >= $amountOut ? 'in' : 'out';
            if ($amountIn > 0) {
                $amount = $amountIn;
            } elseif ($amountOut > 0) {
                $amount = $amountOut;
            }
        }

        $typeLabel = self::scalarString($row['documentType'] ?? '', 'Документ');
        $number = self::optionalString($row['documentNumber'] ?? null);
        $label = $this->documentTypeTitle($groupId !== 'other' ? $groupId : $typeLabel);
        if ($number !== null) {
            $label .= ' №' . $number;
        }

        return [
            'documentId' => null,
            'documentNumber' => $number,
            'documentLabel' => $label,
            'date' => self::optionalString($row['date'] ?? null),
            'documentType' => $groupId,
            'correspondence' => self::optionalString($row['correspondence'] ?? null),
            'amount' => $amount,
            'sum' => isset($row['sum']) ? (float) self::scalarString($row['sum'], '0') : null,
            'direction' => $direction,
        ];
    }

    /**
     * @param array<string, mixed> $it
     */
    private function itemMatchesProduct(
        array $it,
        string $productId,
        ?string $productNum,
        ?string $productCode
    ): bool {
        $pid = self::extractItemProductId($it);
        if ($productId !== '' && $pid !== '' && $pid === $productId) {
            return true;
        }
        $num = self::optionalString($it['productNum'] ?? $it['num'] ?? $it['article'] ?? null);
        $code = self::optionalString($it['productCode'] ?? $it['code'] ?? $it['sku'] ?? null);
        if ($productNum !== null && $num !== null && strcasecmp($productNum, $num) === 0) {
            return true;
        }
        if ($productCode !== null && $code !== null && strcasecmp($productCode, $code) === 0) {
            return true;
        }

        return false;
    }

    /**
     * @param array<string, mixed> $it
     */
    private function extractItemProductId(array $it): string
    {
        $raw = $it['productId'] ?? $it['product'] ?? $it['nomenclatureId'] ?? null;
        if (is_array($raw)) {
            return self::scalarString($raw['id'] ?? $raw['uuid'] ?? $raw['guid'] ?? '');
        }

        return self::scalarString($raw);
    }

    /**
     * @param array<string, mixed> $doc
     */
    private function documentTypeTitle(string $type): string
    {
        $map = [
            'saleAct' => 'Акт реализации',
            'writeoff' => 'Акт списания',
            'incomingInvoice' => 'Приходная накладная',
            'inventory' => 'Инвентаризация',
            'inventoryAct' => 'Инвентаризация',
            'stockTaking' => 'Инвентаризация',
            'incomingInventory' => 'Инвентаризация',
            'inventoryDocument' => 'Инвентаризация',
            'internalTransfer' => 'Перемещение',
        ];

        return $map[$type] ?? $type;
    }

    /**
     * @param array<string, mixed> $doc
     */
    private function documentMatchesStore(array $doc, string $type, string $storeId): bool
    {
        if ($type === 'internalTransfer') {
            $fromStore = self::scalarString(
                $doc['fromStoreId'] ?? $doc['storeFromId'] ?? $doc['storeFrom'] ?? $doc['sourceStoreId'] ?? ''
            );
            $toStore = self::scalarString(
                $doc['toStoreId'] ?? $doc['storeToId'] ?? $doc['storeTo'] ?? $doc['targetStoreId'] ?? ''
            );
            return $fromStore === $storeId || $toStore === $storeId;
        }
        $docStore = self::scalarString($doc['storeId'] ?? $doc['store'] ?? $doc['defaultStoreId'] ?? '');
        if ($docStore !== '' && $docStore !== $storeId) {
            return false;
        }
        if ($docStore === '' && !empty($doc['items']) && is_array($doc['items'])) {
            foreach ($doc['items'] as $it) {
                if (!is_array($it)) {
                    continue;
                }
                $itStore = self::scalarString($it['storeId'] ?? $it['store'] ?? '');
                if ($itStore === '' || $itStore === $storeId) {
                    return true;
                }
            }
            return false;
        }

        return true;
    }

    /**
     * @param array<string, mixed> $doc
     * @param array<string, mixed> $it
     * @return array<string, mixed>|null
     */
    private function buildDocumentEntry(array $doc, array $it, string $type, string $defaultDir): ?array
    {
        $rawAmount = (float) self::scalarString($it['amount'] ?? $it['factAmount'] ?? 0, '0');
        if ($rawAmount == 0.0) {
            return null;
        }
        if ($defaultDir === 'both') {
            $direction = $rawAmount >= 0 ? 'in' : 'out';
        } else {
            $direction = $defaultDir;
        }
        $amount = abs($rawAmount);
        $correspondence = self::optionalString(
            $doc['correspondence']
                ?? $doc['counteragent']
                ?? $doc['supplier']
                ?? $doc['comment']
                ?? $it['comment']
                ?? $it['correspondence']
                ?? null
        );
        $number = self::optionalString($doc['documentNumber'] ?? $doc['number'] ?? null);
        $label = $this->documentTypeTitle($type);
        if ($number !== null) {
            $label .= ' №' . $number;
        }
        return [
            'documentId' => self::optionalString($doc['id'] ?? null),
            'documentNumber' => $number,
            'documentLabel' => $label,
            'date' => self::optionalString($doc['dateIncoming'] ?? $doc['date'] ?? null),
            'documentType' => $type,
            'correspondence' => $correspondence,
            'amount' => $amount,
            'sum' => isset($it['sum']) ? (float) self::scalarString($it['sum'], '0') : null,
            'direction' => $direction,
        ];
    }

    /**
     * Расход/приход за период: OLAP → документы (если нет прав на OLAP) → пусто (далее дельта остатков).
     *
     * @return array{source: ?string, map: array<string, array{out: float, in: float}>, error: ?string}
     */
    public function resolveProductMovement(string $storeId, string $from, string $to, ?string $departmentId = null): array
    {
        $olapError = null;
        try {
            $map = $this->getProductMovementOlap($storeId, $from, $to, $departmentId);
            if ($map !== []) {
                return ['source' => 'olap', 'map' => $map, 'error' => null];
            }
        } catch (\RuntimeException $e) {
            $olapError = $this->formatMovementError($e);
            if (!$this->isOlapAccessDenied($e)) {
                return ['source' => null, 'map' => [], 'error' => $olapError];
            }
        }

        if ($storeId === '') {
            return [
                'source' => null,
                'map' => [],
                'error' => $olapError ?? 'Для расчёта движения без OLAP выберите склад.',
            ];
        }

        try {
            $map = $this->getProductMovementFromDocuments($storeId, $from, $to);
            $hint = $olapError ? $olapError . ' Использован расчёт по документам (акты, накладные).' : null;
            return ['source' => 'documents', 'map' => $map, 'error' => $hint];
        } catch (\Throwable $e) {
            $docErr = $this->formatMovementError($e);
            return [
                'source' => null,
                'map' => [],
                'error' => trim(($olapError ?? '') . ' ' . $docErr),
            ];
        }
    }

    /**
     * @return array<string, array{out: float, in: float}>
     */
    public function getProductMovement(string $storeId, string $from, string $to, ?string $departmentId = null): array
    {
        return $this->resolveProductMovement($storeId, $from, $to, $departmentId)['map'];
    }

    /**
     * @return array<string, array{out: float, in: float}>
     */
    private function getProductMovementOlap(string $storeId, string $from, string $to, ?string $departmentId = null): array
    {
        $schema = $this->resolveOlapMovementSchema();
        $dateCandidates = array_values(array_unique(array_filter(array_merge(
            [$schema['dateField']],
            [
                'OpenDate.Typed',
                'DateTime.OpenDate',
                'OperDay',
                'AccountingDay',
                'DateTime.AccountingDay',
                'OpenDate',
                'DateTime.Date',
                'Date',
            ]
        ))));

        $lastError = 'не удалось выполнить OLAP-запрос';
        foreach ($dateCandidates as $dateField) {
            try {
                return $this->requestOlapProductMovement(
                    $dateField,
                    $schema['productField'],
                    $schema['outField'],
                    $schema['inField'],
                    $schema['storeField'],
                    $storeId,
                    $from,
                    $to,
                    $departmentId
                );
            } catch (\RuntimeException $e) {
                $lastError = $e->getMessage();
                if ($this->isOlapAccessDenied($e)) {
                    throw $e;
                }
                $unknown = $this->extractUnknownOlapField($lastError);
                if ($unknown !== null && $unknown === $dateField) {
                    continue;
                }
                if ($unknown !== null && in_array($unknown, [$schema['productField'], $schema['outField'], $schema['inField'], 'Store.Id', 'Department.Id'], true)) {
                    throw $e;
                }
                if (strpos($lastError, 'Unknown OLAP field') !== false) {
                    continue;
                }
                throw $e;
            }
        }

        throw new \RuntimeException('iiko OLAP: ' . $lastError);
    }

    /**
     * Движение по экспорту документов (без права на OLAP по проводкам).
     *
     * @return array<string, array{out: float, in: float}>
     */
    private function getProductMovementFromDocuments(string $storeId, string $from, string $to): array
    {
        $out = $this->getStoreOutflow($storeId, $from, $to);
        $in = $this->getStoreInflow($storeId, $from, $to);
        $allIds = array_unique(array_merge(array_keys($out), array_keys($in)));
        $map = [];
        foreach ($allIds as $productId) {
            $o = $out[$productId] ?? 0.0;
            $i = $in[$productId] ?? 0.0;
            if ($o === 0.0 && $i === 0.0) {
                continue;
            }
            $map[$productId] = ['out' => $o, 'in' => $i];
        }
        return $map;
    }

    /**
     * @return array<string, float>
     */
    private function getStoreOutflow(string $storeId, string $from, string $to): array
    {
        $types = ['saleAct', 'writeoff', 'internalTransfer'];
        $result = [];
        foreach ($types as $type) {
            try {
                $docs = $this->fetchDocumentsExport($type, $from, $to);
            } catch (\Throwable $e) {
                continue;
            }
            foreach ($docs as $doc) {
                if ($type === 'internalTransfer') {
                    $fromStore = self::scalarString(
                        $doc['fromStoreId'] ?? $doc['storeFromId'] ?? $doc['storeFrom'] ?? $doc['sourceStoreId'] ?? ''
                    );
                    if ($fromStore === '' || $fromStore !== $storeId) {
                        continue;
                    }
                } else {
                    $docStore = self::scalarString($doc['storeId'] ?? $doc['store'] ?? $doc['defaultStoreId'] ?? '');
                    if ($docStore !== '' && $docStore !== $storeId) {
                        continue;
                    }
                }
                foreach ($doc['items'] as $it) {
                    $productId = self::scalarString($it['productId'] ?? $it['product'] ?? '');
                    if ($productId === '') {
                        continue;
                    }
                    $itStore = self::scalarString($it['storeId'] ?? $it['store'] ?? '');
                    if ($itStore !== '' && $itStore !== $storeId) {
                        continue;
                    }
                    $amount = abs((float) self::scalarString($it['amount'] ?? $it['factAmount'] ?? 0, '0'));
                    if ($amount === 0.0) {
                        continue;
                    }
                    $result[$productId] = ($result[$productId] ?? 0) + $amount;
                }
            }
        }
        return $result;
    }

    /**
     * @return array<string, float>
     */
    private function getStoreInflow(string $storeId, string $from, string $to): array
    {
        $types = ['incomingInvoice', 'internalTransfer'];
        $result = [];
        foreach ($types as $type) {
            try {
                $docs = $this->fetchDocumentsExport($type, $from, $to);
            } catch (\Throwable $e) {
                continue;
            }
            foreach ($docs as $doc) {
                if ($type === 'internalTransfer') {
                    $toStore = self::scalarString(
                        $doc['toStoreId'] ?? $doc['storeToId'] ?? $doc['storeTo'] ?? $doc['targetStoreId'] ?? $doc['defaultStoreId'] ?? ''
                    );
                    if ($toStore === '' || $toStore !== $storeId) {
                        continue;
                    }
                } else {
                    $docStore = self::scalarString($doc['storeId'] ?? $doc['store'] ?? $doc['defaultStoreId'] ?? '');
                    if ($docStore !== '' && $docStore !== $storeId) {
                        continue;
                    }
                }
                foreach ($doc['items'] as $it) {
                    $productId = self::scalarString($it['productId'] ?? $it['product'] ?? '');
                    if ($productId === '') {
                        continue;
                    }
                    $itStore = self::scalarString($it['storeId'] ?? $it['store'] ?? '');
                    if ($itStore !== '' && $itStore !== $storeId) {
                        continue;
                    }
                    $amount = abs((float) self::scalarString($it['amount'] ?? $it['factAmount'] ?? 0, '0'));
                    if ($amount === 0.0) {
                        continue;
                    }
                    $result[$productId] = ($result[$productId] ?? 0) + $amount;
                }
            }
        }
        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchDocumentsExport(string $type, string $from, string $to): array
    {
        $fromIso = substr($from, 0, 10);
        $toIso = substr($to, 0, 10);
        $paths = [
            '/resto/api/documents/export/' . $type,
        ];
        $paramSets = [
            ['from' => $fromIso, 'to' => $toIso],
            ['dateFrom' => $fromIso, 'dateTo' => $toIso],
        ];
        $lastError = null;
        foreach ($paths as $path) {
            foreach ($paramSets as $params) {
                try {
                    $parsed = $this->request($path, $params, 'xml');

                    return $this->flattenDocuments($parsed);
                } catch (\Throwable $e) {
                    $lastError = $e;
                }
            }
        }
        if ($lastError !== null) {
            throw $lastError;
        }

        return [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function flattenDocuments($parsed): array
    {
        if (!is_array($parsed)) {
            return [];
        }
        $docs = $this->collectDocumentNodes($parsed);
        $out = [];
        foreach ($docs as $d) {
            if (!is_array($d)) {
                continue;
            }
            $itemsNode = $d['items'] ?? $d['itemList'] ?? $d['itemsList'] ?? $d['itemDtoes'] ?? null;
            $items = $this->flattenDocumentItems($itemsNode);
            $out[] = [
                'id' => $d['id'] ?? null,
                'documentNumber' => $d['documentNumber'] ?? $d['number'] ?? null,
                'dateIncoming' => $d['dateIncoming'] ?? $d['date'] ?? null,
                'comment' => $d['comment'] ?? null,
                'correspondence' => $d['correspondence'] ?? null,
                'storeId' => $d['storeId'] ?? $d['store'] ?? $d['defaultStoreId'] ?? null,
                'fromStoreId' => $d['fromStoreId'] ?? null,
                'storeFromId' => $d['storeFromId'] ?? null,
                'storeFrom' => $d['storeFrom'] ?? null,
                'sourceStoreId' => $d['sourceStoreId'] ?? null,
                'toStoreId' => $d['toStoreId'] ?? null,
                'storeToId' => $d['storeToId'] ?? null,
                'storeTo' => $d['storeTo'] ?? null,
                'targetStoreId' => $d['targetStoreId'] ?? null,
                'defaultStoreId' => $d['defaultStoreId'] ?? null,
                'items' => array_map(static function ($it) {
                    if (!is_array($it)) {
                        return ['productId' => null, 'amount' => 0];
                    }
                    $product = $it['product'] ?? null;
                    $productId = is_array($product)
                        ? ($product['id'] ?? $product['uuid'] ?? null)
                        : $product;
                    $productName = null;
                    $productNum = null;
                    $productCode = null;
                    if (is_array($product)) {
                        $productName = self::optionalString($product['name'] ?? null);
                        $productNum = self::optionalString($product['num'] ?? $product['article'] ?? null);
                        $productCode = self::optionalString($product['code'] ?? null);
                    }
                    return [
                        'productId' => $productId ?? $it['productId'] ?? null,
                        'productName' => $productName ?? self::optionalString($it['productName'] ?? null),
                        'productNum' => $productNum ?? self::optionalString($it['productNum'] ?? $it['num'] ?? null),
                        'productCode' => $productCode ?? self::optionalString($it['productCode'] ?? $it['code'] ?? null),
                        'amount' => $it['amount'] ?? $it['factAmount'] ?? 0,
                        'factAmount' => $it['factAmount'] ?? null,
                        'storeId' => $it['storeId'] ?? $it['store'] ?? null,
                        'store' => $it['store'] ?? null,
                    ];
                }, $items),
            ];
        }
        return $out;
    }

    /**
     * @return array<int, array>
     */
    private function collectDocumentNodes($node): array
    {
        if (!is_array($node)) {
            return [];
        }
        $candidates = [];
        $queue = [$node];
        for ($depth = 0; $queue !== [] && $depth < 40; $depth++) {
            $next = [];
            foreach ($queue as $cur) {
                if (!is_array($cur)) {
                    continue;
                }
                if (isset($cur[0]) && is_array($cur[0])) {
                    foreach ($cur as $el) {
                        if (is_array($el) && $this->looksLikeDocument($el)) {
                            $candidates[] = $el;
                        } elseif (is_array($el)) {
                            $next[] = $el;
                        }
                    }
                    continue;
                }
                if ($this->looksLikeDocument($cur)) {
                    $candidates[] = $cur;
                    continue;
                }
                foreach ($cur as $v) {
                    if (is_array($v)) {
                        $next[] = $v;
                    }
                }
            }
            $queue = $next;
        }
        return $candidates;
    }

    private function looksLikeDocument(array $o): bool
    {
        $hasDoc = isset($o['documentNumber']) || isset($o['dateIncoming']) || isset($o['number']);
        $hasItems = isset($o['items']) || isset($o['itemList']) || isset($o['itemsList']) || isset($o['itemDtoes']);
        return $hasDoc || $hasItems;
    }

    /**
     * @return array<int, array>
     */
    private function flattenDocumentItems($node): array
    {
        if ($node === null) {
            return [];
        }
        if (is_array($node) && isset($node[0])) {
            return array_values(array_filter($node, 'is_array'));
        }
        if (!is_array($node)) {
            return [];
        }
        $tags = [
            'item', 'itemDto', 'writeoffDocumentItemDto', 'saleActItemDto',
            'internalTransferItemDto', 'incomingInvoiceItemDto',
            'inventoryItemDto', 'inventoryActItemDto', 'stockTakingItemDto',
        ];
        foreach ($tags as $k) {
            if (!isset($node[$k])) {
                continue;
            }
            $v = $node[$k];
            if (is_array($v) && isset($v[0])) {
                return array_values(array_filter($v, 'is_array'));
            }
            if (is_array($v)) {
                return [$v];
            }
        }
        if (isset($node['productId']) || isset($node['product']) || isset($node['amount'])) {
            return [$node];
        }
        return [];
    }

    private function isOlapAccessDenied(\Throwable $e): bool
    {
        $msg = $e->getMessage();
        if (preg_match('/HTTP\s*(403|409)\b/i', $msg)) {
            return true;
        }
        if (stripos($msg, 'Доступ запрещен') !== false || stripos($msg, 'Доступ запрещён') !== false) {
            return true;
        }
        if (stripos($msg, 'OLAP') !== false && stripos($msg, 'проводк') !== false) {
            return true;
        }
        if (stripos($msg, 'Permission denied') !== false) {
            return true;
        }
        return false;
    }

    private function formatMovementError(\Throwable $e): string
    {
        $msg = trim($e->getMessage());
        if ($this->isOlapAccessDenied($e)) {
            return 'OLAP недоступен: нет права «Просматривать OLAP-отчёт по проводкам». '
                . 'В iikoChain откройте настройки пользователя API и включите это право '
                . '(или используйте учётную запись с доступом к OLAP по проводкам).';
        }
        if (stripos($msg, 'iiko OLAP') === 0) {
            return $msg;
        }
        return 'iiko: ' . mb_substr($msg, 0, 400);
    }

    /**
     * @return array<string, array>
     */
    private function fetchOlapTransactionsColumns(): array
    {
        if ($this->olapTransactionsColumns !== null) {
            return $this->olapTransactionsColumns;
        }
        $this->olapTransactionsColumns = [];
        try {
            $token = $this->ensureToken();
            $res = $this->httpGet('/resto/api/v2/reports/olap/columns', [
                'key' => $token,
                'reportType' => 'TRANSACTIONS',
            ], 'application/json');
            if ($res['status'] < 200 || $res['status'] >= 300) {
                return $this->olapTransactionsColumns;
            }
            $parsed = json_decode(trim($res['body']), true);
            if (!is_array($parsed)) {
                return $this->olapTransactionsColumns;
            }
            foreach ($parsed as $fieldName => $meta) {
                if (!is_string($fieldName) || !is_array($meta)) {
                    continue;
                }
                $this->olapTransactionsColumns[$fieldName] = $meta;
            }
        } catch (\Throwable $e) {
            // схема подберётся по списку кандидатов
        }
        return $this->olapTransactionsColumns;
    }

    /**
     * Поле для groupByRowFields (только groupingAllowed).
     *
     * @param array<string, array> $columns
     * @param string[] $candidates
     */
    private function pickOlapGroupField(array $columns, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if ($columns === []) {
                return $candidate;
            }
            if (!isset($columns[$candidate])) {
                continue;
            }
            if ($columns[$candidate]['groupingAllowed'] ?? false) {
                return $candidate;
            }
        }
        if ($columns !== []) {
            foreach ($columns as $fieldName => $meta) {
                if (!($meta['groupingAllowed'] ?? false)) {
                    continue;
                }
                foreach ($candidates as $needle) {
                    if (stripos($fieldName, str_replace('.', '', $needle)) !== false
                        || stripos($fieldName, $needle) !== false) {
                        return $fieldName;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Поле для aggregateFields.
     *
     * @param array<string, array> $columns
     * @param string[] $candidates
     */
    private function pickOlapAggregateField(array $columns, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if ($columns === []) {
                return $candidate;
            }
            if (!isset($columns[$candidate])) {
                continue;
            }
            if ($columns[$candidate]['aggregationAllowed'] ?? false) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Поле для фильтра IncludeValues.
     *
     * @param array<string, array> $columns
     * @param string[] $candidates
     */
    private function pickOlapFilterField(array $columns, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if ($columns === []) {
                return $candidate;
            }
            if (!isset($columns[$candidate])) {
                continue;
            }
            if ($columns[$candidate]['filteringAllowed'] ?? false) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param array<string, array> $columns
     * @param string[] $candidates
     */
    private function pickOlapField(array $columns, array $candidates, ?string $typeHint = null): ?string
    {
        $names = $columns !== [] ? array_keys($columns) : $candidates;
        foreach ($candidates as $candidate) {
            if (!in_array($candidate, $names, true)) {
                continue;
            }
            if ($columns === []) {
                return $candidate;
            }
            $meta = $columns[$candidate] ?? [];
            if ($typeHint !== null) {
                $type = strtoupper((string) ($meta['type'] ?? ''));
                if ($type !== '' && $type !== strtoupper($typeHint) && $type !== 'DATETIME' && $typeHint === 'DATE') {
                    continue;
                }
            }
            if (($meta['filteringAllowed'] ?? true) || ($meta['aggregationAllowed'] ?? true) || ($meta['groupingAllowed'] ?? true)) {
                return $candidate;
            }
        }
        if ($typeHint !== null && $columns !== []) {
            foreach ($columns as $fieldName => $meta) {
                $type = strtoupper((string) ($meta['type'] ?? ''));
                if ($type === strtoupper($typeHint) || ($typeHint === 'DATE' && $type === 'DATETIME')) {
                    if ($meta['filteringAllowed'] ?? false) {
                        return $fieldName;
                    }
                }
            }
        }
        return null;
    }

    /**
     * @return array{dateField: string, productField: string, outField: string, inField: string, storeField: string}
     */
    private function resolveOlapMovementSchema(): array
    {
        $columns = $this->fetchOlapTransactionsColumns();

        $dateField = $this->pickOlapField($columns, [
            'OpenDate.Typed',
            'DateTime.OpenDate',
            'OperDay',
            'AccountingDay',
            'DateTime.AccountingDay',
            'OpenDate',
            'DateTime.Date',
            'Date',
        ], 'DATE') ?? 'OpenDate.Typed';

        $productField = $this->pickOlapField($columns, [
            'Product.Id',
            'ProductId',
            'Product',
            'Nomenclature.Id',
        ]) ?? 'Product.Id';

        $outField = $this->pickOlapField($columns, [
            'Amount.Out',
            'Sum.Out',
            'AmountOutgoing',
            'OutgoingAmount',
            'ExpenseAmount',
            'Amount.Expense',
        ]);
        $inField = $this->pickOlapField($columns, [
            'Amount.In',
            'Sum.In',
            'AmountIncoming',
            'IncomingAmount',
            'IncomeAmount',
            'Amount.Income',
        ]);

        if ($outField === null || $inField === null) {
            foreach (array_keys($columns) as $fieldName) {
                $meta = $columns[$fieldName];
                if (!($meta['aggregationAllowed'] ?? false)) {
                    continue;
                }
                if ($outField === null && (stripos($fieldName, 'Out') !== false || stripos($fieldName, 'Expense') !== false || stripos($fieldName, 'Расход') !== false)) {
                    $outField = $fieldName;
                }
                if ($inField === null && (stripos($fieldName, 'In') !== false || stripos($fieldName, 'Income') !== false || stripos($fieldName, 'Приход') !== false)) {
                    if (stripos($fieldName, 'Incoming') !== false || stripos($fieldName, 'In') !== false) {
                        $inField = $fieldName;
                    }
                }
            }
        }

        if ($outField === null) {
            $outField = 'Amount.Out';
        }
        if ($inField === null) {
            $inField = 'Amount.In';
        }

        $storeField = $this->pickOlapField($columns, ['Store.Id', 'StoreId', 'Store']) ?? 'Store.Id';

        return [
            'dateField' => $dateField,
            'productField' => $productField,
            'outField' => $outField,
            'inField' => $inField,
            'storeField' => $storeField,
        ];
    }

    /**
     * @param array<string, array>|null $columns метаданные OLAP (тип поля DATE / DATETIME).
     */
    private function olapDateRangeFilter(string $field, string $from, string $to, ?array $columns = null): array
    {
        $fromDay = substr(trim($from), 0, 10);
        $toDay = substr(trim($to), 0, 10);
        if ($fromDay === '' || $toDay === '') {
            $toDay = $fromDay !== '' ? $fromDay : gmdate('Y-m-d');
            $fromDay = $fromDay !== '' ? $fromDay : $toDay;
        }
        if ($fromDay > $toDay) {
            $tmp = $fromDay;
            $fromDay = $toDay;
            $toDay = $tmp;
        }

        $meta = ($columns !== null && isset($columns[$field]) && is_array($columns[$field]))
            ? $columns[$field]
            : null;
        if ($this->fieldWantsOlapDateTime($field, $meta)) {
            return [
                'filterType' => 'DateRange',
                'periodType' => 'CUSTOM',
                'from' => $this->toOlapDateTime($fromDay, false),
                'to' => $this->toOlapDateTime($toDay, true),
            ];
        }

        // Поля типа DATE (OpenDate.Typed и т.п.) — только yyyy-MM-dd, без времени; to — исключающая граница.
        $toExclusive = date('Y-m-d', strtotime($toDay . ' +1 day'));

        return [
            'filterType' => 'DateRange',
            'periodType' => 'CUSTOM',
            'from' => $fromDay,
            'to' => $toExclusive,
        ];
    }

    /**
     * @param array<string, mixed>|null $columnMeta
     */
    private function fieldWantsOlapDateTime(string $field, ?array $columnMeta = null): bool
    {
        if ($columnMeta !== null) {
            $type = strtoupper((string) ($columnMeta['type'] ?? ''));
            if ($type === 'DATE') {
                return false;
            }
            if ($type === 'DATETIME' || $type === 'DATE_TIME') {
                return true;
            }
        }
        if (stripos($field, 'DateTime') !== false) {
            return true;
        }
        if (stripos($field, 'Typed') !== false || stripos($field, 'OperDay') !== false
            || stripos($field, 'AccountingDay') !== false || stripos($field, 'OpenDate') !== false) {
            return false;
        }

        return false;
    }

    private function toOlapDateTime(string $date, bool $endOfDay): string
    {
        $d = substr($date, 0, 10);
        if ($endOfDay) {
            return $d . 'T23:59:59.999';
        }
        return $d . 'T00:00:00.000';
    }

    /**
     * @return array<string, array{out: float, in: float}>
     */
    private function requestOlapProductMovement(
        string $dateField,
        string $productField,
        string $outField,
        string $inField,
        string $storeField,
        string $storeId,
        string $from,
        string $to,
        ?string $departmentId
    ): array {
        $filters = [
            $dateField => $this->olapDateRangeFilter($dateField, $from, $to),
        ];
        if ($storeId !== '') {
            $filters[$storeField] = [
                'filterType' => 'IncludeValues',
                'values' => [$storeId],
            ];
        }
        if ($departmentId) {
            $filters['Department.Id'] = [
                'filterType' => 'IncludeValues',
                'values' => [$departmentId],
            ];
        }

        $body = json_encode([
            'reportType' => 'TRANSACTIONS',
            'buildSummary' => false,
            'groupByRowFields' => [$productField],
            'groupByColFields' => [],
            'aggregateFields' => [$outField, $inField],
            'filters' => $filters,
        ], JSON_UNESCAPED_UNICODE);

        $token = $this->ensureToken();
        $res = $this->httpPost('/resto/api/v2/reports/olap', ['key' => $token], $body);
        if ($res['status'] === 401 || $res['status'] === 403) {
            $this->token = null;
            $token = $this->ensureToken();
            $res = $this->httpPost('/resto/api/v2/reports/olap', ['key' => $token], $body);
        }
        if ($res['status'] < 200 || $res['status'] >= 300) {
            throw new \RuntimeException('iiko OLAP -> HTTP ' . $res['status'] . ': ' . mb_substr($res['body'], 0, 300));
        }

        $parsed = json_decode(trim($res['body']), true);
        $rows = [];
        if (is_array($parsed['data'] ?? null)) {
            $rows = $parsed['data'];
        } elseif (is_array($parsed)) {
            $rows = $parsed;
        }

        $map = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $productId = self::scalarString(
                $row[$productField] ?? $row['Product.Id'] ?? $row['productId'] ?? ''
            );
            if ($productId === '') {
                continue;
            }
            $out = abs((float) ($row[$outField] ?? $row['Amount.Out'] ?? $row['amountOut'] ?? 0));
            $in = abs((float) ($row[$inField] ?? $row['Amount.In'] ?? $row['amountIn'] ?? 0));
            if ($out === 0.0 && $in === 0.0) {
                continue;
            }
            $map[$productId] = ['out' => $out, 'in' => $in];
        }
        return $map;
    }

    private function extractUnknownOlapField(string $message): ?string
    {
        if (preg_match("/Unknown OLAP field '([^']+)'/i", $message, $m)) {
            return $m[1];
        }
        if (preg_match("/Grouping is not allowed for field '([^']+)'/i", $message, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * @return array<string, array>
     */
    private function fetchOlapSalesColumns(): array
    {
        if ($this->olapSalesColumns !== null) {
            return $this->olapSalesColumns;
        }
        $this->olapSalesColumns = [];
        try {
            $token = $this->ensureToken();
            $res = $this->httpGet('/resto/api/v2/reports/olap/columns', [
                'key' => $token,
                'reportType' => 'SALES',
            ], 'application/json');
            if ($res['status'] >= 200 && $res['status'] < 300) {
                $parsed = json_decode(trim($res['body']), true);
                if (is_array($parsed)) {
                    foreach ($parsed as $fieldName => $meta) {
                        if (is_string($fieldName) && is_array($meta)) {
                            $this->olapSalesColumns[$fieldName] = $meta;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // подбор по кандидатам
        }

        return $this->olapSalesColumns;
    }

    /**
     * @return array{
     *   dateField: string,
     *   orderGroupField: string,
     *   orderFilterField: string,
     *   orderAggField: ?string,
     *   departmentIdField: string,
     *   departmentNameField: string,
     *   sumField: string,
     *   amountField: string,
     *   productField: string,
     *   payTypeField: ?string,
     *   orderTypeField: ?string
     * }
     */
    private function resolveOlapSalesSchema(bool $detailMode): array
    {
        $columns = $this->fetchOlapSalesColumns();

        $dateField = $this->pickOlapGroupField($columns, [
            'OpenDate.Typed',
            'DateTime.OpenDate',
            'OpenDate',
            'OperDay',
            'AccountingDay',
        ]) ?? $this->pickOlapField($columns, [
            'OpenDate.Typed',
            'OpenDate',
        ], 'DATE') ?? 'OpenDate.Typed';

        $orderGroupField = $this->pickOlapGroupField($columns, [
            'OrderNum',
            'Order.Number',
            'OrderNumber',
            'OpenTime',
            'DateTime.OpenTime',
            'SessionNum',
        ]) ?? 'OrderNum';

        $orderFilterField = $this->pickOlapFilterField($columns, [
            'OrderNum',
            'Order.Number',
            'OrderNumber',
            'OpenTime',
            'DateTime.OpenTime',
        ]) ?? $orderGroupField;

        $orderAggField = $this->pickOlapAggregateField($columns, [
            'UniqOrderId',
            'OrderNum',
        ]);

        $departmentIdField = $this->pickOlapFilterField($columns, [
            'Department.Id',
            'DepartmentId',
        ]) ?? $this->pickOlapGroupField($columns, ['Department.Id', 'DepartmentId']) ?? 'Department.Id';

        $departmentNameField = $this->pickOlapGroupField($columns, [
            'Department',
            'Department.Name',
        ]) ?? 'Department';

        $sumField = $this->pickOlapSalesRevenueField($columns);

        $amountField = $this->pickOlapAggregateField($columns, [
            'DishAmountInt',
            'ProductAmount',
            'Amount',
        ]) ?? 'DishAmountInt';

        $productField = $this->pickOlapGroupField($columns, [
            'Product.Name',
            'DishName',
            'Product',
            'Dish',
        ]) ?? 'Product.Name';

        $payTypeField = $this->pickOlapGroupField($columns, [
            'PayTypes',
            'PaymentType',
            'PayType',
        ]);

        $orderTypeField = $this->pickOlapGroupField($columns, [
            'OrderType',
            'OrderServiceType',
        ]);

        return [
            'dateField' => $dateField,
            'orderGroupField' => $orderGroupField,
            'orderFilterField' => $orderFilterField,
            'orderAggField' => $orderAggField,
            'departmentIdField' => $departmentIdField,
            'departmentNameField' => $departmentNameField,
            'sumField' => $sumField,
            'amountField' => $amountField,
            'productField' => $productField,
            'payTypeField' => $payTypeField,
            'orderTypeField' => $orderTypeField,
            'sumFieldIsNetRevenue' => $this->isNetRevenueSumField($sumField),
        ];
    }

    /**
     * Поле выручки как в iikoChain: со скидкой, а не «сумма без скидки».
     *
     * @param array<string, array> $columns
     */
    private function pickOlapSalesRevenueField(array $columns): string
    {
        return $this->pickOlapAggregateField($columns, [
            'DishDiscountSumInt',
            'OrderSum',
            'OrderDiscountSum',
            'Sum',
            'DishSumInt',
        ]) ?? 'DishDiscountSumInt';
    }

    private function isNetRevenueSumField(string $field): bool
    {
        return in_array($field, [
            'DishDiscountSumInt',
            'OrderSum',
            'OrderDiscountSum',
            'Sum',
        ], true);
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function buildSalesSumFieldWarning(array $schema): ?string
    {
        $field = (string) ($schema['sumField'] ?? '');
        if ($field === 'DishSumInt') {
            return 'Выручка считается по полю «сумма без скидки» (DishSumInt). '
                . 'В iikoChain обычно показана выручка со скидкой — итог у вас может быть выше, чем в Chain.';
        }
        if (!($schema['sumFieldIsNetRevenue'] ?? false)) {
            return 'Используется поле «' . $field . '». Сверьте с настройками OLAP в iiko, если сумма не совпадает с Chain.';
        }

        return null;
    }

    /**
     * Итоговая выручка за период одним OLAP-запросом (без суммирования списка чеков).
     *
     * @param array<string, mixed> $schema
     */
    private function fetchSalesRevenueTotal(
        string $from,
        string $to,
        ?string $departmentId,
        ?array $schema = null
    ): float {
        $schema ??= $this->resolveOlapSalesSchema(false);
        $salesColumns = $this->fetchOlapSalesColumns();
        $filters = $this->buildSalesOlapFilters($from, $to, $departmentId, $salesColumns, $schema, null);
        $sumField = $schema['sumField'];

        $rows = $this->runOlapSalesWithRetry(
            [$schema['dateField']],
            [$sumField],
            $filters,
            $salesColumns,
            $schema['dateField'],
            $from,
            $to,
            $schema
        );

        $total = 0.0;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $total += abs((float) self::scalarString($row[$sumField] ?? $row['DishDiscountSumInt'] ?? $row['DishSumInt'] ?? '0', '0'));
        }

        return round($total, 2);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function requestOlapSalesChecks(
        string $from,
        string $to,
        ?string $departmentId,
        ?string $orderIdFilter
    ): array {
        $salesColumns = $this->fetchOlapSalesColumns();
        $schema = $this->resolveOlapSalesSchema($orderIdFilter !== null && $orderIdFilter !== '');
        $filters = $this->buildSalesOlapFilters($from, $to, $departmentId, $salesColumns, $schema, $orderIdFilter);

        if ($orderIdFilter !== null && $orderIdFilter !== '') {
            $groupBy = array_values(array_unique(array_filter([
                $schema['productField'],
            ])));
            $aggregate = array_values(array_unique(array_filter([
                $schema['sumField'],
                $schema['amountField'] !== $schema['sumField'] ? $schema['amountField'] : null,
            ])));
        } else {
            $groupBy = array_values(array_unique(array_filter([
                $schema['orderGroupField'],
                $schema['dateField'],
                $schema['departmentNameField'],
            ])));
            $aggregate = array_values(array_unique(array_filter([
                $schema['sumField'],
                $schema['orderAggField'],
            ])));
        }

        return $this->runOlapSalesWithRetry(
            $groupBy,
            $aggregate,
            $filters,
            $salesColumns,
            $schema['dateField'],
            $from,
            $to,
            $schema
        );
    }

    private function isOlapDatePeriodRejected(string $message): bool
    {
        return stripos($message, 'OLAP-запрос отклонен') !== false
            || stripos($message, 'HTTP 409') !== false
            || stripos($message, 'DATE') !== false && stripos($message, 'период') !== false;
    }

    private function isOlapClassCastError(string $message): bool
    {
        return stripos($message, 'ClassCastException') !== false
            || stripos($message, 'cannot be cast to java.lang.Integer') !== false
            || stripos($message, 'HTTP 500') !== false;
    }

    /**
     * @param array<int, mixed> $values
     *
     * @return array<int, mixed>
     */
    private function coerceOlapFilterValues(string $field, array $values, ?array $columns, bool $forceInteger = false): array
    {
        $type = '';
        if ($columns !== null && isset($columns[$field]) && is_array($columns[$field])) {
            $type = strtoupper((string) ($columns[$field]['type'] ?? ''));
        } elseif (preg_match('/OrderNum|Order\.Number|UniqOrderId/i', $field)) {
            $type = 'INTEGER';
        }
        $asInteger = $forceInteger || $type === 'INTEGER' || $type === 'INT' || $type === 'LONG';

        $result = [];
        foreach ($values as $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $s = trim((string) $value);
            if ($asInteger && $s !== '' && preg_match('/^-?\d+$/', $s)) {
                $result[] = (int) $s;
                continue;
            }
            $result[] = $value;
        }

        return $result;
    }

    /**
     * @param array<int, mixed> $values
     *
     * @return array{filterType: string, values: array<int, mixed>}
     */
    private function olapIncludeValuesFilter(
        string $field,
        array $values,
        ?array $columns = null,
        bool $forceInteger = false
    ): array {
        return [
            'filterType' => 'IncludeValues',
            'values' => $this->coerceOlapFilterValues($field, $values, $columns, $forceInteger),
        ];
    }

    /**
     * Запасные варианты периода, если iiko отклонил основной фильтр дат.
     *
     * @param array<string, array>|null $columns
     */
    private function olapDateRangeFilterAlternate(
        string $field,
        string $from,
        string $to,
        ?array $columns,
        int $variant
    ): array {
        $fromDay = substr(trim($from), 0, 10);
        $toDay = substr(trim($to), 0, 10);
        if ($fromDay > $toDay) {
            $tmp = $fromDay;
            $fromDay = $toDay;
            $toDay = $tmp;
        }
        if ($variant === 1) {
            $toBound = $fromDay === $toDay
                ? date('Y-m-d', strtotime($toDay . ' +1 day'))
                : date('Y-m-d', strtotime($toDay . ' +1 day'));

            return [
                'filterType' => 'DateRange',
                'periodType' => 'CUSTOM',
                'from' => $fromDay,
                'to' => $toBound,
            ];
        }

        return [
            'filterType' => 'DateRange',
            'periodType' => 'CUSTOM',
            'from' => $this->toOlapDateTime($fromDay, false),
            'to' => $this->toOlapDateTime($toDay, true),
        ];
    }

    /**
     * @param string[] $groupBy
     * @param string[] $aggregate
     * @param array<string, mixed> $filters
     *
     * @return array<int, array<string, mixed>>
     */
    private function executeOlapSalesRequest(array $groupBy, array $aggregate, array $filters): array
    {
        $body = json_encode([
            'reportType' => 'SALES',
            'buildSummary' => false,
            'groupByRowFields' => array_values($groupBy),
            'groupByColFields' => [],
            'aggregateFields' => array_values($aggregate),
            'filters' => $filters,
        ], JSON_UNESCAPED_UNICODE);

        $token = $this->ensureToken();
        $res = $this->httpPost('/resto/api/v2/reports/olap', ['key' => $token], $body);
        if ($res['status'] === 401 || $res['status'] === 403) {
            $this->token = null;
            $token = $this->ensureToken();
            $res = $this->httpPost('/resto/api/v2/reports/olap', ['key' => $token], $body);
        }
        if ($res['status'] < 200 || $res['status'] >= 300) {
            throw new \RuntimeException('iiko OLAP SALES -> HTTP ' . $res['status'] . ': ' . mb_substr($res['body'], 0, 300));
        }

        return $this->parseOlapDataRows($res['body']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseOlapDataRows(string $body): array
    {
        $parsed = json_decode(trim($body), true);
        if (!is_array($parsed)) {
            return [];
        }
        if (is_array($parsed['data'] ?? null)) {
            return $parsed['data'];
        }
        if (array_is_list($parsed)) {
            return $parsed;
        }

        return is_array($parsed['rows'] ?? null) ? $parsed['rows'] : [];
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>|null
     */
    private function normalizeSalesCheckRow(array $row): ?array
    {
        $schema = $this->resolveOlapSalesSchema(false);
        $orderNum = self::optionalString(
            $row[$schema['orderGroupField']] ?? $row['OrderNum'] ?? null
        );
        $orderId = $orderNum ?? '';
        if ($orderId === '' && $schema['orderAggField'] !== null) {
            $aggVal = self::scalarString($row[$schema['orderAggField']] ?? '', '');
            if ($aggVal !== '') {
                $orderId = $aggVal;
            }
        }
        $sum = (float) self::scalarString(
            $row[$schema['sumField']] ?? $row['DishDiscountSumInt'] ?? $row['DishSumInt'] ?? '0',
            '0'
        );
        if ($orderId === '' && $sum === 0.0) {
            return null;
        }
        $dateRaw = self::scalarString(
            $row[$schema['dateField']] ?? $row['OpenDate.Typed'] ?? $row['OpenDate'] ?? ''
        );
        $itemsCount = (float) self::scalarString(
            $row[$schema['amountField']] ?? $row['DishAmountInt'] ?? '0',
            '0'
        );

        return [
            'orderId' => $orderId,
            'orderNum' => $orderNum ?? ($orderId !== '' ? $orderId : null),
            'date' => $this->normalizeCheckDate($dateRaw),
            'departmentId' => self::optionalString(
                $row[$schema['departmentIdField']] ?? $row['Department.Id'] ?? null
            ),
            'departmentName' => self::optionalString(
                $row[$schema['departmentNameField']] ?? $row['Department'] ?? null
            ),
            'sum' => round(abs($sum), 2),
            'itemsCount' => $itemsCount > 0 ? round($itemsCount, 3) : null,
            'payType' => $schema['payTypeField'] !== null
                ? self::optionalString($row[$schema['payTypeField']] ?? null)
                : null,
            'orderType' => $schema['orderTypeField'] !== null
                ? self::optionalString($row[$schema['orderTypeField']] ?? null)
                : null,
        ];
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>|null
     */
    private function normalizeSalesCheckItemRow(array $row): ?array
    {
        $schema = $this->resolveOlapSalesSchema(true);
        $name = self::scalarString(
            $row[$schema['productField']] ?? $row['Product.Name'] ?? $row['DishName'] ?? '',
            'Позиция'
        );
        $amount = abs((float) self::scalarString(
            $row[$schema['amountField']] ?? $row['DishAmountInt'] ?? '0',
            '0'
        ));
        $sum = abs((float) self::scalarString(
            $row[$schema['sumField']] ?? $row['DishDiscountSumInt'] ?? $row['DishSumInt'] ?? '0',
            '0'
        ));
        if ($amount === 0.0 && $sum === 0.0) {
            return null;
        }

        return [
            'name' => $name,
            'amount' => $amount > 0 ? round($amount, 3) : null,
            'sum' => round($sum, 2),
            'unitPrice' => $amount > 0 ? round($sum / $amount, 2) : null,
        ];
    }

    private function normalizeCheckDate(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $raw, $m)) {
            return strlen($raw) > 10 ? $raw : $m[1];
        }
        if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})/', $raw, $m)) {
            return $m[3] . '-' . $m[2] . '-' . $m[1] . (strlen($raw) > 10 ? substr($raw, 10) : '');
        }

        return $raw;
    }

    private function ensureToken(): string
    {
        if ($this->token !== null) {
            return $this->token;
        }
        return $this->login();
    }

    /**
     * @param array<string, string|int> $params
     */
    private function request(string $path, array $params = [], string $expect = 'auto')
    {
        $attempt = $this->parseResponse($path, $params, $expect);
        if ($expect === 'auto' && $this->looksEmptyArray($attempt['value'])) {
            $alt = $attempt['mode'] === 'json' ? 'xml' : 'json';
            try {
                $retry = $this->parseResponse($path, $params, $alt);
                if (!$this->looksEmptyArray($retry['value'])) {
                    return $retry['value'];
                }
            } catch (\Throwable $e) {
                // keep first
            }
        }
        return $attempt['value'];
    }

    /**
     * @param array<string, string|int> $params
     * @return array{value: mixed, mode: string}
     */
    private function parseResponse(string $path, array $params, string $acceptMode): array
    {
        $token = $this->ensureToken();
        $accept = $acceptMode === 'xml'
            ? 'application/xml'
            : ($acceptMode === 'json' ? 'application/json' : 'application/json, application/xml;q=0.9');
        $params['key'] = $token;
        $res = $this->httpGet($path, $params, $accept);
        if ($res['status'] === 401 || $res['status'] === 403) {
            $this->token = null;
            $token = $this->ensureToken();
            $params['key'] = $token;
            $res = $this->httpGet($path, $params, $accept);
        }
        if ($res['status'] < 200 || $res['status'] >= 300) {
            throw new \RuntimeException(
                "iiko {$path} -> HTTP {$res['status']}: " . mb_substr($res['body'], 0, 300)
            );
        }
        $text = trim(preg_replace('/^\xEF\xBB\xBF/', '', $res['body']));
        $contentType = strtolower($res['contentType'] ?? '');
        $mode = $acceptMode;
        if ($acceptMode === 'auto') {
            $first = $text[0] ?? '';
            if ($first === '{' || $first === '[') {
                $mode = 'json';
            } elseif ($first === '<') {
                $mode = 'xml';
            } else {
                $mode = strpos($contentType, 'xml') !== false ? 'xml' : 'json';
            }
        }
        if ($mode === 'json') {
            $value = json_decode($text, true);
            if (!is_array($value) && $value !== null) {
                throw new \RuntimeException("iiko {$path}: не удалось разобрать JSON");
            }
            return ['value' => $value, 'mode' => 'json'];
        }
        return ['value' => $this->xmlToArray($text), 'mode' => 'xml'];
    }

    private function xmlToArray(string $xml): array
    {
        $sx = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($sx === false) {
            throw new \RuntimeException('Не удалось разобрать XML от iiko');
        }
        return json_decode(json_encode($sx), true) ?? [];
    }

    /**
     * @return array<int, array{id: string, name: string, code?: string}>
     */
    private function mapCorporateList($parsed, string $type): array
    {
        $list = $this->flattenCorporateList($parsed);
        $out = [];
        foreach ($list as $s) {
            if (!is_array($s)) {
                continue;
            }
            $id = self::scalarString($s['id'] ?? $s['uuid'] ?? $s['guid'] ?? $s['code'] ?? '');
            if ($id === '') {
                continue;
            }
            $out[] = [
                'id' => $id,
                'name' => self::scalarString($s['name'] ?? $s['code'] ?? $id, $id),
                'code' => self::optionalString($s['code'] ?? null),
            ];
        }
        return $out;
    }

    private function flattenCorporateList($parsed): array
    {
        if ($parsed === null) {
            return [];
        }
        if (is_array($parsed) && isset($parsed[0])) {
            return array_values(array_filter($parsed, 'is_array'));
        }
        if (!is_array($parsed)) {
            return [];
        }
        $keys = ['corporateItemDtoes', 'stores', 'departments', 'items', 'list', 'products', 'data', 'result'];
        foreach ($keys as $k) {
            if (isset($parsed[$k]) && is_array($parsed[$k])) {
                $v = $parsed[$k];
                if (isset($v[0])) {
                    return array_values(array_filter($v, 'is_array'));
                }
                if (is_array($v)) {
                    return [$v];
                }
            }
        }
        foreach ($parsed as $v) {
            if (is_array($v) && isset($v[0]) && is_array($v[0])) {
                $first = $v[0];
                if (isset($first['id']) || isset($first['uuid']) || isset($first['guid']) || isset($first['code'])) {
                    return array_values(array_filter($v, 'is_array'));
                }
            }
        }
        return [];
    }

    private function looksEmptyArray($v): bool
    {
        if (!is_array($v) || $v === []) {
            return false;
        }
        if (!isset($v[0])) {
            return false;
        }
        $empty = 0;
        foreach ($v as $x) {
            if ($x === null || $x === [] || (is_array($x) && $x === [])) {
                $empty++;
            }
        }
        return count($v) > 0 && $empty / count($v) > 0.5;
    }

    /**
     * @param array<string, string|int|null> $params
     */
    private function httpGet(string $path, array $params = [], string $accept = 'application/json, application/xml;q=0.9'): array
    {
        $url = $this->baseUrl . $path;
        if ($params !== []) {
            $url .= '?' . http_build_query(array_filter($params, static fn ($v) => $v !== null && $v !== ''));
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => [
                'Accept: ' . $accept,
                'User-Agent: dbt-hub-iiko/1.0',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($body === false) {
            throw new \RuntimeException('cURL: ' . $err);
        }
        return ['status' => $status, 'body' => (string) $body, 'contentType' => $contentType];
    }

    private function httpPost(string $path, array $query, string $jsonBody): array
    {
        $url = $this->baseUrl . $path . '?' . http_build_query($query);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: dbt-hub-iiko/1.0',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($body === false) {
            throw new \RuntimeException('cURL: ' . $err);
        }
        return ['status' => $status, 'body' => (string) $body];
    }
}
