<?php

namespace app\services;

use app\models\Location;
use app\models\WriteOffEntry;
use Yii;
use yii\base\Component;

class WriteOffService extends Component
{
    /**
     * @return Location[]
     */
    public function getAccessibleLocations(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            return [];
        }

        $query = Location::find()->where(['is_active' => 1]);

        if ($user->isAdmin()) {
            return $query->orderBy(['name' => SORT_ASC])->all();
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
        } elseif (in_array($user->position, ['location_manager', 'senior_teamaker'], true) && $user->location_id) {
            $query->andWhere(['id' => $user->location_id]);
        } else {
            return [];
        }

        return $query->orderBy(['name' => SORT_ASC])->all();
    }

    public function canAccessLocation(int $locationId): bool
    {
        foreach ($this->getAccessibleLocations() as $loc) {
            if ((int) $loc->id === $locationId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getEntries(int $locationId, string $date): array
    {
        if (!$this->canAccessLocation($locationId)) {
            return [];
        }

        $date = substr(trim($date), 0, 10);
        $rows = WriteOffEntry::find()
            ->where(['location_id' => $locationId, 'entry_date' => $date])
            ->orderBy(['category' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        $list = [];
        foreach ($rows as $row) {
            $list[] = $this->serializeEntry($row);
        }

        return $list;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     *
     * @return array{items: array<int, array<string, mixed>>, totals: array<string, float>, grandTotal: float}
     */
    public function saveEntries(int $locationId, string $date, array $items): array
    {
        if (!$this->canAccessLocation($locationId)) {
            throw new \yii\web\ForbiddenHttpException('Нет доступа к точке.');
        }

        $date = substr(trim($date), 0, 10);
        if ($date === '') {
            throw new \InvalidArgumentException('Укажите дату.');
        }

        $userId = Yii::$app->user->identity ? (int) Yii::$app->user->identity->id : null;

        $tx = Yii::$app->db->beginTransaction();
        try {
            WriteOffEntry::deleteAll(['location_id' => $locationId, 'entry_date' => $date]);

            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $name = trim((string) ($item['productName'] ?? $item['product_name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $category = (string) ($item['category'] ?? '');
                if (!isset(WriteOffEntry::CATEGORY_LABELS[$category])) {
                    continue;
                }

                $entry = new WriteOffEntry();
                $entry->location_id = $locationId;
                $entry->entry_date = $date;
                $entry->category = $category;
                $entry->product_id = trim((string) ($item['productId'] ?? $item['product_id'] ?? '')) ?: null;
                $entry->product_name = $name;
                $entry->weight = (float) ($item['weight'] ?? 0);
                $entry->unit = trim((string) ($item['unit'] ?? '')) ?: null;
                $entry->unit_cost = (float) ($item['unitCost'] ?? $item['unit_cost'] ?? 0);
                $entry->created_by = $userId;
                if (!$entry->validate()) {
                    throw new \InvalidArgumentException(implode(' ', $entry->getFirstErrors()) ?: 'Ошибка валидации.');
                }
                $entry->save(false);
            }

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }

        $entries = $this->getEntries($locationId, $date);

        return [
            'items' => $entries,
            'totals' => $this->buildTotals($entries),
            'grandTotal' => $this->sumAmounts($entries),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     *
     * @return array<string, float>
     */
    public function buildTotals(array $entries): array
    {
        $totals = [];
        foreach (array_keys(WriteOffEntry::CATEGORY_LABELS) as $category) {
            $totals[$category] = 0.0;
        }
        foreach ($entries as $entry) {
            $cat = (string) ($entry['category'] ?? '');
            if (!isset($totals[$cat])) {
                continue;
            }
            $totals[$cat] += (float) ($entry['amount'] ?? 0);
        }
        foreach ($totals as $k => $v) {
            $totals[$k] = round($v, 2);
        }

        return $totals;
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     */
    public function sumAmounts(array $entries): float
    {
        $sum = 0.0;
        foreach ($entries as $entry) {
            $sum += (float) ($entry['amount'] ?? 0);
        }

        return round($sum, 2);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeEntry(WriteOffEntry $row): array
    {
        return [
            'id' => (int) $row->id,
            'locationId' => (int) $row->location_id,
            'date' => $row->entry_date,
            'category' => $row->category,
            'categoryLabel' => WriteOffEntry::CATEGORY_LABELS[$row->category] ?? $row->category,
            'productId' => $row->product_id,
            'productName' => $row->product_name,
            'weight' => (float) $row->weight,
            'unit' => $row->unit,
            'unitCost' => (float) $row->unit_cost,
            'amount' => (float) $row->amount,
        ];
    }
}
