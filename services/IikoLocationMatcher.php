<?php

namespace app\services;

/**
 * Сопоставление точек сети с подразделениями iiko (аналог frontend iikoStoreMatch.js).
 */
class IikoLocationMatcher
{
    public static function normalizeMatchText(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');
        $value = str_replace('ё', 'е', $value);
        $value = preg_replace('/[^a-zа-я0-9\s]/u', ' ', $value) ?? '';
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        return trim($value);
    }

    /**
     * @param array<int, array{id: string, name?: string}> $departments
     * @param string $locationName название точки в нашей системе
     * @param string|null $iikoName явное название в iiko (приоритетнее locationName)
     */
    public static function pickDepartmentForLocation(
        array $departments,
        string $locationName,
        ?string $iikoName = null
    ): ?string {
        if ($departments === []) {
            return null;
        }

        $search = trim($iikoName ?? '') !== '' ? trim($iikoName) : trim($locationName);
        if ($search === '') {
            return null;
        }

        $searchNorm = self::normalizeMatchText($search);

        foreach ($departments as $dep) {
            $name = (string) ($dep['name'] ?? '');
            if ($name !== '' && self::normalizeMatchText($name) === $searchNorm) {
                return (string) ($dep['id'] ?? '');
            }
        }

        $bestId = null;
        $bestScore = 0;

        foreach ($departments as $dep) {
            $name = self::normalizeMatchText((string) ($dep['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $score = 0;
            if ($name === $searchNorm) {
                $score += 5000;
            }
            if (str_contains($name, $searchNorm)) {
                $score += 1000 + mb_strlen($searchNorm, 'UTF-8') * 10;
            }
            if (str_contains($searchNorm, $name)) {
                $score += 800 + mb_strlen($name, 'UTF-8') * 8;
            }
            $score += self::tokenOverlapScore($searchNorm, $name);
            if (mb_strlen($searchNorm, 'UTF-8') >= 3) {
                $searchCompact = str_replace(' ', '', $searchNorm);
                $nameCompact = str_replace(' ', '', $name);
                if ($searchCompact !== '' && str_contains($nameCompact, $searchCompact)) {
                    $score += 200 + mb_strlen($searchCompact, 'UTF-8') * 5;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestId = (string) ($dep['id'] ?? '');
            }
        }

        return $bestScore >= 30 && $bestId !== '' ? $bestId : null;
    }

    /**
     * @param array<int, array{id: string, name?: string}> $stores
     */
    public static function pickStoreForLocation(
        array $stores,
        string $locationName,
        ?string $iikoName = null
    ): ?string {
        if ($stores === []) {
            return null;
        }

        $search = trim($iikoName ?? '') !== '' ? trim($iikoName) : trim($locationName);
        if ($search === '') {
            return null;
        }

        $warehouseStores = array_values(array_filter(
            $stores,
            static fn ($s) => str_contains(self::normalizeMatchText((string) ($s['name'] ?? '')), 'склад')
        ));
        $candidates = $warehouseStores !== [] ? $warehouseStores : $stores;

        return self::pickDepartmentForLocation($candidates, $locationName, $iikoName);
    }

    private static function tokenOverlapScore(string $a, string $b): int
    {
        $aw = array_values(array_filter(explode(' ', $a), static fn ($w) => mb_strlen($w, 'UTF-8') >= 2));
        $bw = array_flip(array_values(array_filter(explode(' ', $b), static fn ($w) => mb_strlen($w, 'UTF-8') >= 2)));
        if ($aw === [] || $bw === []) {
            return 0;
        }

        $hit = 0;
        foreach ($aw as $w) {
            if (isset($bw[$w])) {
                $hit += 10 + mb_strlen($w, 'UTF-8');
            }
        }

        return $hit;
    }
}
