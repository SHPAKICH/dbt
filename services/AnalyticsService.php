<?php

namespace app\services;

use app\models\DailyReport;
use app\models\Location;
use Yii;
use yii\base\Component;

/**
 * Аналитика точек для карты: список точек и детальная статистика.
 * Данные по выручке и чекам — из iiko (модули «Чеки» и «Отчёты по продажам»).
 */
class AnalyticsService extends Component
{
    /** Центр карты по умолчанию (Санкт-Петербург). */
    private const DEFAULT_LAT = 59.9343;
    private const DEFAULT_LNG = 30.3351;

    /** @var array<int, array{id: string, label: string, keyword: string}> */
    private const PRODUCT_GROUPS = [
        ['id' => 'waffles', 'label' => 'Вафли', 'keyword' => 'вафл'],
        ['id' => 'vibes', 'label' => 'Вайбы', 'keyword' => 'вайб'],
        ['id' => 'classic', 'label' => 'Классика', 'keyword' => 'классик'],
        ['id' => 'breezes', 'label' => 'Бризы', 'keyword' => 'бриз'],
        ['id' => 'ice-tea', 'label' => 'Айс ти', 'keyword' => 'айс ти'],
    ];

    /**
     * @return Location[]
     */
    public function getAccessibleLocations(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            return [];
        }

        $query = Location::find()->orderBy(['name' => SORT_ASC]);
        if ($user->isAdmin()) {
            return $query->all();
        }
        if ($user->position === 'manager') {
            $ids = (new \yii\db\Query())
                ->from('manager_locations')
                ->where(['manager_id' => $user->id])
                ->select('location_id')
                ->column();
            if ($ids) {
                $query->andWhere(['id' => $ids]);
            } else {
                $query->andWhere('0=1');
            }

            return $query->all();
        }
        if ($user->position === 'location_manager' && $user->location_id) {
            $query->andWhere(['id' => (int) $user->location_id]);

            return $query->all();
        }

        return [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPoints(): array
    {
        $points = [];
        foreach ($this->getAccessibleLocations() as $loc) {
            [$lat, $lng] = $this->resolveCoordinates($loc);
            $points[] = [
                'id' => (int) $loc->id,
                'name' => $loc->name,
                'lat' => $lat,
                'lng' => $lng,
                'status' => (int) $loc->is_active === 1 ? 'active' : 'inactive',
                'description' => $loc->address ? (string) $loc->address : 'Точка сети',
            ];
        }

        return $points;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPointDetail(int $locationId): ?array
    {
        $allowed = false;
        foreach ($this->getAccessibleLocations() as $loc) {
            if ((int) $loc->id === $locationId) {
                $allowed = true;
                $location = $loc;
                break;
            }
        }
        if (!$allowed || !isset($location)) {
            return null;
        }

        $today = date('Y-m-d');
        $weekFrom = date('Y-m-d', strtotime('-6 days'));
        $monthFrom = date('Y-m-d', strtotime('-29 days'));

        $reports = DailyReport::find()
            ->where(['location_id' => $locationId])
            ->andWhere(['>=', 'report_date', $monthFrom])
            ->andWhere(['<=', 'report_date', $today])
            ->orderBy(['report_date' => SORT_ASC])
            ->all();

        $opsDay = 0;
        $opsWeek = 0;
        $opsMonth = 0;
        $totalRevenue = 0.0;
        $totalOrders = 0;
        $totalPlan = 0.0;
        $lastUpdatedAt = $location->updated_at;

        $activityChart = [];
        $revenueChart = [];
        $byDate = [];

        foreach ($reports as $report) {
            $date = $report->report_date;
            $orders = (int) ($report->orders_count ?? 0);
            $revenue = (float) ($report->to_revenue ?? 0);
            $plan = (float) ($report->plan_daily ?? 0);

            $byDate[$date] = ['orders' => $orders, 'revenue' => $revenue];
            if ($report->updated_at && (!$lastUpdatedAt || $report->updated_at > $lastUpdatedAt)) {
                $lastUpdatedAt = $report->updated_at;
            }

            $opsMonth += $orders;
            $totalRevenue += $revenue;
            $totalOrders += $orders;
            $totalPlan += $plan;

            if ($date >= $weekFrom) {
                $opsWeek += $orders;
            }
            if ($date === $today) {
                $opsDay += $orders;
            }
        }

        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $row = $byDate[$date] ?? ['orders' => 0, 'revenue' => 0.0];
            $activityChart[] = [
                'date' => $date,
                'value' => (int) $row['orders'],
            ];
            $revenueChart[] = [
                'date' => $date,
                'value' => round((float) $row['revenue'], 2),
            ];
        }

        $avgCheck = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0.0;
        $conversion = $totalPlan > 0 ? round($totalRevenue / $totalPlan * 100, 1) : null;

        [$lat, $lng] = $this->resolveCoordinates($location);

        return [
            'id' => (int) $location->id,
            'name' => $location->name,
            'lat' => $lat,
            'lng' => $lng,
            'status' => (int) $location->is_active === 1 ? 'active' : 'inactive',
            'description' => $location->address ? (string) $location->address : 'Точка сети',
            'address' => $location->address ?: '—',
            'dataSource' => 'daily_report',
            'period' => ['from' => $monthFrom, 'to' => $today],
            'lastUpdatedAt' => $lastUpdatedAt,
            'operationsDay' => $opsDay,
            'operationsWeek' => $opsWeek,
            'operationsMonth' => $opsMonth,
            'totalRevenue' => round($totalRevenue, 2),
            'avgCheck' => $avgCheck,
            'conversion' => $conversion,
            'activityChart' => $activityChart,
            'revenueChart' => $revenueChart,
            'checksByHour' => [],
            'byPaymentType' => [],
            'byOrderType' => [],
            'topProducts' => [],
            'productGroups' => [],
            'warning' => null,
        ];
    }

    /**
     * Лидерборд точек по выручке за период (данные iiko OLAP SALES).
     *
     * @return array<string, mixed>
     */
    public function getLeaderboard(IikoClient $client, string $from, string $to): array
    {
        [$from, $to] = $this->normalizePeriod($from, $to);
        $departments = $client->getDepartments();
        $depById = [];
        foreach ($departments as $dep) {
            $depById[(string) ($dep['id'] ?? '')] = (string) ($dep['name'] ?? '');
        }

        $rows = [];
        $totalRevenue = 0.0;
        $totalChecks = 0;
        $warnings = [];

        foreach ($this->getAccessibleLocations() as $location) {
            $departmentId = IikoLocationMatcher::pickDepartmentForLocation(
                $departments,
                (string) $location->name,
                $this->resolveLocationIikoName($location)
            );
            $entry = [
                'locationId' => (int) $location->id,
                'name' => $location->name,
                'status' => (int) $location->is_active === 1 ? 'active' : 'inactive',
                'departmentId' => $departmentId,
                'departmentName' => $departmentId !== null ? ($depById[$departmentId] ?? null) : null,
                'matched' => $departmentId !== null,
                'revenue' => 0.0,
                'checks' => 0,
                'avgCheck' => 0.0,
                'error' => null,
            ];

            if ($departmentId === null) {
                $entry['error'] = 'Не найдено подразделение iiko';
                $rows[] = $entry;
                continue;
            }

            try {
                $shift = $client->getSalesShiftSummary($from, $to, $departmentId);
                $summary = $shift['summary'] ?? [];
                $entry['revenue'] = round((float) ($summary['totalSum'] ?? 0), 2);
                $entry['checks'] = (int) ($summary['totalChecks'] ?? 0);
                $entry['avgCheck'] = round((float) ($summary['avgCheck'] ?? 0), 2);
                if (!empty($shift['warning'])) {
                    $warnings[] = "{$location->name}: {$shift['warning']}";
                }
            } catch (\Throwable $e) {
                $entry['error'] = $e->getMessage();
            }

            $rows[] = $entry;
            $totalRevenue += $entry['revenue'];
            $totalChecks += $entry['checks'];
        }

        usort($rows, static function ($a, $b) {
            return ($b['revenue'] ?? 0) <=> ($a['revenue'] ?? 0);
        });

        $leaderboard = [];
        $rank = 1;
        foreach ($rows as $row) {
            $row['rank'] = $rank++;
            $leaderboard[] = $row;
        }

        return [
            'period' => ['from' => $from, 'to' => $to],
            'leaderboard' => $leaderboard,
            'totals' => [
                'revenue' => round($totalRevenue, 2),
                'checks' => $totalChecks,
                'avgCheck' => $totalChecks > 0 ? round($totalRevenue / $totalChecks, 2) : 0.0,
                'locations' => count($leaderboard),
            ],
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    /**
     * Детальная аналитика точки из iiko за период.
     *
     * @return array<string, mixed>|null
     */
    public function getPointDetailFromIiko(IikoClient $client, int $locationId, string $from, string $to): ?array
    {
        $location = null;
        foreach ($this->getAccessibleLocations() as $loc) {
            if ((int) $loc->id === $locationId) {
                $location = $loc;
                break;
            }
        }
        if ($location === null) {
            return null;
        }

        [$from, $to] = $this->normalizePeriod($from, $to);
        $departments = $client->getDepartments();
        $departmentId = IikoLocationMatcher::pickDepartmentForLocation(
            $departments,
            (string) $location->name,
            $this->resolveLocationIikoName($location)
        );
        $departmentName = null;
        foreach ($departments as $dep) {
            if ((string) ($dep['id'] ?? '') === (string) $departmentId) {
                $departmentName = (string) ($dep['name'] ?? '');
                break;
            }
        }

        [$lat, $lng] = $this->resolveCoordinates($location);

        if ($departmentId === null) {
            return [
                'id' => (int) $location->id,
                'name' => $location->name,
                'lat' => $lat,
                'lng' => $lng,
                'status' => (int) $location->is_active === 1 ? 'active' : 'inactive',
                'description' => $location->address ? (string) $location->address : 'Точка сети',
                'address' => $location->address ?: '—',
                'dataSource' => 'iiko',
                'period' => ['from' => $from, 'to' => $to],
                'departmentId' => null,
                'departmentName' => null,
                'lastUpdatedAt' => date('c'),
                'operationsDay' => 0,
                'operationsWeek' => 0,
                'operationsMonth' => 0,
                'totalRevenue' => 0.0,
                'avgCheck' => 0.0,
                'conversion' => null,
                'activityChart' => [],
                'revenueChart' => [],
                'checksByHour' => [],
                'byPaymentType' => [],
                'byOrderType' => [],
                'topProducts' => [],
                'productGroups' => [],
                'warning' => 'Не удалось сопоставить точку с подразделением iiko.',
            ];
        }

        $shift = $client->getSalesShiftSummary($from, $to, $departmentId);
        $productsReport = $client->getSalesProductsReport($from, $to, $departmentId);

        $byDay = is_array($shift['byDay'] ?? null) ? $shift['byDay'] : [];
        $activityChart = [];
        $revenueChart = [];
        foreach ($byDay as $row) {
            $label = (string) ($row['label'] ?? '');
            $date = $this->normalizeChartDate($label);
            if ($date === null) {
                continue;
            }
            $activityChart[] = ['date' => $date, 'value' => (int) ($row['checks'] ?? 0)];
            $revenueChart[] = ['date' => $date, 'value' => round((float) ($row['sum'] ?? 0), 2)];
        }

        $today = date('Y-m-d');
        $weekFrom = date('Y-m-d', strtotime('-6 days'));
        $opsDay = 0;
        $opsWeek = 0;
        $opsMonth = 0;
        foreach ($byDay as $row) {
            $date = $this->normalizeChartDate((string) ($row['label'] ?? ''));
            if ($date === null) {
                continue;
            }
            $checks = (int) ($row['checks'] ?? 0);
            $opsMonth += $checks;
            if ($date >= $weekFrom) {
                $opsWeek += $checks;
            }
            if ($date === $today) {
                $opsDay += $checks;
            }
        }

        $summary = is_array($shift['summary'] ?? null) ? $shift['summary'] : [];
        $products = is_array($productsReport['products'] ?? null) ? $productsReport['products'] : [];
        $topProducts = array_slice($products, 0, 12);

        $warnings = array_filter([
            $shift['warning'] ?? null,
            $productsReport['warning'] ?? null,
        ]);

        return [
            'id' => (int) $location->id,
            'name' => $location->name,
            'lat' => $lat,
            'lng' => $lng,
            'status' => (int) $location->is_active === 1 ? 'active' : 'inactive',
            'description' => $location->address ? (string) $location->address : 'Точка сети',
            'address' => $location->address ?: '—',
            'dataSource' => 'iiko',
            'period' => ['from' => $from, 'to' => $to],
            'departmentId' => $departmentId,
            'departmentName' => $departmentName,
            'lastUpdatedAt' => date('c'),
            'operationsDay' => $opsDay,
            'operationsWeek' => $opsWeek,
            'operationsMonth' => $opsMonth,
            'totalRevenue' => round((float) ($summary['totalSum'] ?? 0), 2),
            'avgCheck' => round((float) ($summary['avgCheck'] ?? 0), 2),
            'conversion' => null,
            'activityChart' => $activityChart,
            'revenueChart' => $revenueChart,
            'checksByHour' => is_array($shift['byHour'] ?? null) ? $shift['byHour'] : [],
            'byPaymentType' => is_array($shift['byPaymentType'] ?? null) ? $shift['byPaymentType'] : [],
            'byOrderType' => is_array($shift['byOrderType'] ?? null) ? $shift['byOrderType'] : [],
            'topProducts' => $topProducts,
            'productGroups' => $this->buildProductGroups($products),
            'productsSummary' => $productsReport['summary'] ?? null,
            'warning' => $warnings !== [] ? implode(' ', $warnings) : null,
        ];
    }

    /**
     * @param array<int, array{name: string, sum: float, qty: float}> $products
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildProductGroups(array $products): array
    {
        $groups = [];
        foreach (self::PRODUCT_GROUPS as $group) {
            $sum = 0.0;
            $qty = 0.0;
            $keyword = mb_strtolower($group['keyword'], 'UTF-8');
            foreach ($products as $product) {
                $name = mb_strtolower((string) ($product['name'] ?? ''), 'UTF-8');
                if ($name !== '' && str_contains($name, $keyword)) {
                    $sum += (float) ($product['sum'] ?? 0);
                    $qty += (float) ($product['qty'] ?? 0);
                }
            }
            $groups[] = [
                'id' => $group['id'],
                'label' => $group['label'],
                'sum' => round($sum, 2),
                'qty' => round($qty, 3),
            ];
        }

        return $groups;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function normalizePeriod(string $from, string $to): array
    {
        $from = substr(trim($from), 0, 10);
        $to = substr(trim($to), 0, 10);
        if ($from === '' || $to === '') {
            $to = date('Y-m-d');
            $from = date('Y-m-d', strtotime('-29 days'));
        }
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    private function resolveLocationIikoName(Location $location): ?string
    {
        if (!$location->hasAttribute('iiko_name')) {
            return null;
        }
        $name = trim((string) $location->iiko_name);

        return $name !== '' ? $name : null;
    }

    private function normalizeChartDate(string $label): ?string
    {
        $label = trim($label);
        if ($label === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $label, $m)) {
            return substr($m[0], 0, 10);
        }
        if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})/', $label, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        $ts = strtotime($label);

        return $ts !== false ? date('Y-m-d', $ts) : null;
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function resolveCoordinates(Location $location): array
    {
        if ($location->hasAttribute('lat') && $location->hasAttribute('lng')
            && $location->lat !== null && $location->lng !== null
            && (float) $location->lat !== 0.0 && (float) $location->lng !== 0.0) {
            return [(float) $location->lat, (float) $location->lng];
        }

        return $this->fallbackCoordinates((int) $location->id);
    }

    /**
     * Равномерное распределение точек вокруг центра, если координаты не заданы.
     *
     * @return array{0: float, 1: float}
     */
    private function fallbackCoordinates(int $locationId): array
    {
        $angle = deg2rad(($locationId * 47) % 360);
        $ring = (int) floor(($locationId - 1) / 8);
        $radius = 0.012 + $ring * 0.009;

        $lat = self::DEFAULT_LAT + sin($angle) * $radius;
        $lng = self::DEFAULT_LNG + cos($angle) * $radius * 1.8;

        return [round($lat, 6), round($lng, 6)];
    }
}
