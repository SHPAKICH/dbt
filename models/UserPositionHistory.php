<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * История должностей для расчёта зарплаты по дате смены.
 *
 * @property int $id
 * @property int $user_id
 * @property string $position
 * @property string $effective_from date Y-m-d
 * @property string $created_at
 */
class UserPositionHistory extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%user_position_history}}';
    }

    public function rules(): array
    {
        return [
            [['user_id', 'position', 'effective_from'], 'required'],
            [['user_id'], 'integer'],
            [['effective_from'], 'date', 'format' => 'php:Y-m-d'],
            [['position'], 'in', 'range' => ['manager', 'location_manager', 'senior_teamaker', 'teamaker', 'trainee']],
            [['user_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => 'id'],
        ];
    }

    /**
     * Должность пользователя на указанную дату (по истории; иначе текущая в User).
     */
    public static function getPositionOnDate(int $userId, string $date): string
    {
        $row = static::find()
            ->where(['user_id' => $userId])
            ->andWhere(['<=', 'effective_from', $date])
            ->orderBy(['effective_from' => SORT_DESC])
            ->limit(1)
            ->asArray()
            ->one();

        if ($row) {
            return $row['position'];
        }

        $user = User::findOne($userId);
        return $user ? $user->position : 'teamaker';
    }

    /**
     * Массив должностей для пар (user_id, date) — для расчёта зарплаты по дате смены.
     * @param array $pairs список [ ['user_id' => int, 'date' => 'Y-m-d'], ... ]
     * @return array [ 'userId_date' => position, ... ]
     */
    public static function getPositionMapForPairs(array $pairs): array
    {
        if (empty($pairs)) {
            return [];
        }

        $userIds = array_unique(array_column($pairs, 'user_id'));
        $maxDate = max(array_column($pairs, 'date'));

        $rows = static::find()
            ->where(['user_id' => $userIds])
            ->andWhere(['<=', 'effective_from', $maxDate])
            ->orderBy(['user_id' => SORT_ASC, 'effective_from' => SORT_DESC])
            ->asArray()
            ->all();

        $byUser = [];
        foreach ($rows as $r) {
            $uid = (int) $r['user_id'];
            if (!isset($byUser[$uid])) {
                $byUser[$uid] = [];
            }
            $byUser[$uid][] = ['effective_from' => $r['effective_from'], 'position' => $r['position']];
        }

        $users = User::find()->where(['id' => $userIds])->indexBy('id')->all();
        $result = [];
        foreach ($pairs as $p) {
            $uid = (int) $p['user_id'];
            $date = $p['date'];
            $key = $uid . '_' . $date;
            if (isset($result[$key])) {
                continue;
            }
            $list = $byUser[$uid] ?? null;
            $currentPosition = isset($users[$uid]) ? $users[$uid]->position : 'teamaker';
            $position = $currentPosition;
            if ($list !== null) {
                foreach ($list as $h) {
                    if ($h['effective_from'] <= $date) {
                        $position = $h['position'];
                        break;
                    }
                }
            }
            $result[$key] = $position;
        }

        return $result;
    }

    /**
     * Подпись должности по коду (для отчётов).
     */
    public static function getPositionLabelByCode(string $code): string
    {
        $labels = [
            'manager' => 'Управляющий',
            'location_manager' => 'Менеджер точки',
            'senior_teamaker' => 'Старший тимейкер',
            'teamaker' => 'Тимейкер',
            'trainee' => 'Стажёр',
        ];
        return $labels[$code] ?? $code;
    }
}
