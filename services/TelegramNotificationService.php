<?php

namespace app\services;

use app\models\DailyReport;
use app\models\Location;
use app\models\ManagerLocation;
use app\models\News;
use app\models\PhotoReportPhoto;
use app\models\ShiftInvite;
use app\models\ShiftTask;
use app\models\TechCard;
use app\models\TrainingMaterial;
use app\models\TrainingTest;
use app\models\User;
use Yii;

/**
 * Исходящие уведомления сотрудникам в Telegram (привязанный telegram_chat_id).
 */
class TelegramNotificationService
{
    private TelegramBotService $bot;

    public function __construct(?TelegramBotService $bot = null)
    {
        $this->bot = $bot ?? new TelegramBotService();
    }

    public function isEnabled(): bool
    {
        return $this->bot->isConfigured();
    }

    /**
     * @return array{sent:int, skipped:int, failed:int}
     */
    public function sendToUserId(int $userId, string $text): array
    {
        $user = User::findOne(['id' => $userId, 'is_active' => 1]);
        if (!$user || !$user->hasLinkedTelegram()) {
            return ['sent' => 0, 'skipped' => 1, 'failed' => 0];
        }
        return $this->sendToChatId((int) $user->telegram_chat_id, $text)
            ? ['sent' => 1, 'skipped' => 0, 'failed' => 0]
            : ['sent' => 0, 'skipped' => 0, 'failed' => 1];
    }

    /**
     * @param int[] $userIds
     * @return array{sent:int, skipped:int, failed:int}
     */
    public function sendToUserIds(array $userIds, string $text): array
    {
        $stats = ['sent' => 0, 'skipped' => 0, 'failed' => 0];
        foreach (array_unique(array_map('intval', $userIds)) as $userId) {
            if ($userId <= 0) {
                continue;
            }
            $row = $this->sendToUserId($userId, $text);
            $stats['sent'] += $row['sent'];
            $stats['skipped'] += $row['skipped'];
            $stats['failed'] += $row['failed'];
        }
        return $stats;
    }

    /**
     * @return array{sent:int, skipped:int, failed:int}
     */
    public function broadcastToAllLinked(string $text): array
    {
        $userIds = User::find()
            ->select('id')
            ->where(['is_active' => 1])
            ->andWhere(['not', ['telegram_chat_id' => null]])
            ->andWhere(['>', 'telegram_chat_id', 0])
            ->column();
        return $this->sendToUserIds($userIds, $text);
    }

    /**
     * Тер.управляющие точки + управляющий точки.
     *
     * @return array{sent:int, skipped:int, failed:int}
     */
    public function notifyLocationManagers(int $locationId, string $text): array
    {
        $managerIds = ManagerLocation::find()
            ->select('manager_id')
            ->where(['location_id' => $locationId])
            ->column();

        $locationManagerId = User::find()
            ->select('id')
            ->where([
                'is_active' => 1,
                'location_id' => $locationId,
                'position' => 'location_manager',
            ])
            ->scalar();

        if ($locationManagerId) {
            $managerIds[] = (int) $locationManagerId;
        }

        return $this->sendToUserIds($managerIds, $text);
    }

    /**
     * Активные сотрудники, привязанные к точке.
     *
     * @return array{sent:int, skipped:int, failed:int}
     */
    public function notifyLocationStaff(int $locationId, string $text): array
    {
        $userIds = User::find()
            ->select('id')
            ->where(['is_active' => 1, 'location_id' => $locationId])
            ->column();
        return $this->sendToUserIds($userIds, $text);
    }

    public function onNewsPublished(News $news): void
    {
        $this->safe(function () use ($news) {
            $preview = $this->plainPreview((string) $news->body);
            $text = "📢 Новость DBT Hub\n\n"
                . $news->title . "\n";
            if ($preview !== '') {
                $text .= "\n" . $preview . "\n";
            }
            $text .= "\n" . $this->appLink('/news/' . (int) $news->id);
            $this->broadcastToAllLinked($text);
        });
    }

    public function onGuruTestPublished(TrainingTest $test, bool $isNew): void
    {
        $this->safe(function () use ($test, $isNew) {
            if (!(int) $test->is_active) {
                return;
            }
            $prefix = $isNew ? 'Новый тест' : 'Опубликован тест';
            $text = "📚 DBT.INFO — {$prefix}\n\n"
                . $test->title;
            if (trim((string) $test->description) !== '') {
                $text .= "\n\n" . $this->plainPreview((string) $test->description, 300);
            }
            $text .= "\n\n" . $this->appLink('/learning/test/' . (int) $test->id);
            $this->broadcastToAllLinked($text);
        });
    }

    public function onGuruLessonPublished(TrainingMaterial $lesson, bool $isNew): void
    {
        $this->safe(function () use ($lesson, $isNew) {
            if (!(int) $lesson->is_active) {
                return;
            }
            $prefix = $isNew ? 'Новый урок' : 'Опубликован урок';
            $text = "📚 DBT.INFO — {$prefix}\n\n"
                . $lesson->title
                . "\n\n" . $this->appLink('/learning/lesson/' . (int) $lesson->id);
            $this->broadcastToAllLinked($text);
        });
    }

    public function onGuruCardPublished(TechCard $card, bool $isNew): void
    {
        $this->safe(function () use ($card, $isNew) {
            if (!(int) $card->is_active) {
                return;
            }
            $prefix = $isNew ? 'Новая карточка' : 'Опубликована карточка';
            $text = "📚 DBT.INFO — {$prefix}\n\n"
                . $card->name
                . "\n\n" . $this->appLink('/learning/card/' . (int) $card->id);
            $this->broadcastToAllLinked($text);
        });
    }

    /**
     * @param array<string,mixed> $oldAttributes
     */
    public function onDailyReportUpdated(DailyReport $report, array $oldAttributes): void
    {
        $this->safe(function () use ($report, $oldAttributes) {
            if (!$this->isShiftClosed($report) || $this->isShiftClosedFromAttributes($oldAttributes)) {
                return;
            }

            $location = $report->location ?? Location::findOne($report->location_id);
            $locationName = $location ? $location->name : 'Точка #' . $report->location_id;
            $date = $this->formatDate((string) $report->report_date);
            $managerName = $report->manager ? $report->manager->getFullName() : '—';

            $text = "📊 Дейли — смена закрыта\n\n"
                . "Точка: {$locationName}\n"
                . "Дата: {$date}\n"
                . "Менеджер смены: {$managerName}\n"
                . "ТО: " . $this->formatMoney($report->to_revenue) . "\n"
                . "Заказов: " . (int) ($report->orders_count ?? 0) . "\n";

            if ($report->delta_plan !== null) {
                $sign = $report->delta_plan >= 0 ? '+' : '';
                $text .= "Delta к плану: {$sign}" . $this->formatMoney($report->delta_plan) . "\n";
            }

            $text .= "\n" . $this->appLink('/daily');

            $cacheKey = 'tg_daily_closed:' . (int) $report->location_id . ':' . $report->report_date;
            if (Yii::$app->cache->get($cacheKey)) {
                return;
            }
            Yii::$app->cache->set($cacheKey, 1, 86400);

            $this->notifyLocationManagers((int) $report->location_id, $text);
        });
    }

    public function onPhotoReportMaybeComplete(int $locationId, string $reportType, string $reportDate, int $totalItems): void
    {
        $this->safe(function () use ($locationId, $reportType, $reportDate, $totalItems) {
            if ($totalItems <= 0) {
                return;
            }

            $cutoff = date('Y-m-d H:i:s', strtotime('-48 hours'));
            $filledCount = (int) PhotoReportPhoto::find()
                ->select('item_key')
                ->distinct()
                ->where([
                    'location_id' => $locationId,
                    'report_type' => $reportType,
                    'report_date' => $reportDate,
                ])
                ->andWhere(['>=', 'created_at', $cutoff])
                ->count();

            if ($filledCount < $totalItems) {
                return;
            }

            $cacheKey = "tg_photo_report:{$locationId}:{$reportType}:{$reportDate}";
            if (Yii::$app->cache->get($cacheKey)) {
                return;
            }
            Yii::$app->cache->set($cacheKey, 1, 172800);

            $location = Location::findOne($locationId);
            $locationName = $location ? $location->name : 'Точка #' . $locationId;
            $typeLabel = $reportType === 'closing' ? 'закрытия' : 'открытия';
            $date = $this->formatDate($reportDate);

            $text = "📷 Фотоотчёт {$typeLabel} завершён\n\n"
                . "Точка: {$locationName}\n"
                . "Дата: {$date}\n"
                . "Пунктов: {$filledCount}/{$totalItems}\n\n"
                . $this->appLink('/tech-service');

            $this->notifyLocationManagers($locationId, $text);
        });
    }

    public function onShiftInviteCreated(ShiftInvite $invite): void
    {
        $this->safe(function () use ($invite) {
            $invite->refresh();
            $location = $invite->location ?? Location::findOne($invite->location_id);
            $inviter = $invite->invitedByUser ?? User::findOne($invite->invited_by);
            $time = $this->formatShiftTime($invite->time_start, $invite->time_end);

            $text = "📅 Приглашение на смену\n\n"
                . 'Точка: ' . ($location ? $location->name : '—') . "\n"
                . 'Дата: ' . $this->formatDate((string) $invite->date) . "\n"
                . "Время: {$time}\n"
                . 'Пригласил: ' . ($inviter ? $inviter->getFullName() : '—') . "\n\n"
                . $this->appLink('/schedule');

            $this->sendToUserId((int) $invite->user_id, $text);
        });
    }

    public function onShiftInviteAccepted(ShiftInvite $invite): void
    {
        $this->safe(function () use ($invite) {
            $employee = $invite->user ?? User::findOne($invite->user_id);
            $location = $invite->location ?? Location::findOne($invite->location_id);

            $text = "✅ Инвайт принят\n\n"
                . ($employee ? $employee->getFullName() : 'Сотрудник')
                . ' принял смену на '
                . $this->formatDate((string) $invite->date)
                . ' (' . ($location ? $location->name : '—') . ')';

            $this->sendToUserId((int) $invite->invited_by, $text);
        });
    }

    public function onShiftInviteDeclined(ShiftInvite $invite): void
    {
        $this->safe(function () use ($invite) {
            $employee = $invite->user ?? User::findOne($invite->user_id);
            $location = $invite->location ?? Location::findOne($invite->location_id);

            $text = "❌ Инвайт отклонён\n\n"
                . ($employee ? $employee->getFullName() : 'Сотрудник')
                . ' отказался от смены '
                . $this->formatDate((string) $invite->date)
                . ' (' . ($location ? $location->name : '—') . ')';

            $this->sendToUserId((int) $invite->invited_by, $text);
        });
    }

    public function onShiftTaskCreated(ShiftTask $task): void
    {
        $this->safe(function () use ($task) {
            $location = $task->location ?? Location::findOne($task->location_id);
            $creator = $task->creator ?? User::findOne($task->created_by);

            $text = "✅ Новая задача на смену\n\n"
                . 'Точка: ' . ($location ? $location->name : '—') . "\n"
                . 'Дата: ' . $this->formatDate((string) $task->shift_date) . "\n"
                . 'Задача: ' . $task->title . "\n";
            if ($task->requires_photo) {
                $text .= "Требуется фотоотчёт\n";
            }
            if ($creator) {
                $text .= 'От: ' . $creator->getFullName() . "\n";
            }
            $text .= "\n" . $this->appLink('/shift-tasks');

            $this->notifyLocationStaff((int) $task->location_id, $text);
        });
    }

    public function onShiftTaskCompleted(ShiftTask $task, User $completedBy): void
    {
        $this->safe(function () use ($task, $completedBy) {
            $location = $task->location ?? Location::findOne($task->location_id);

            $text = "✔️ Задача выполнена\n\n"
                . 'Точка: ' . ($location ? $location->name : '—') . "\n"
                . 'Дата: ' . $this->formatDate((string) $task->shift_date) . "\n"
                . 'Задача: ' . $task->title . "\n"
                . 'Выполнил: ' . $completedBy->getFullName();

            $this->sendToUserId((int) $task->created_by, $text);
        });
    }

    private function sendToChatId(int $chatId, string $text): bool
    {
        if ($chatId <= 0 || !$this->isEnabled()) {
            return false;
        }
        return $this->bot->sendMessage($chatId, $text);
    }

    private function safe(callable $callback): void
    {
        if (!$this->isEnabled()) {
            return;
        }
        try {
            $callback();
        } catch (\Throwable $e) {
            Yii::warning('Telegram notification failed: ' . $e->getMessage(), __METHOD__);
        }
    }

    private function appLink(string $path): string
    {
        $base = rtrim((string) (Yii::$app->params['frontendUrl'] ?? ''), '/');
        if ($base === '') {
            $base = 'https://dbthub.ru';
        }
        return 'Открыть в DBT Hub: ' . $base . $path;
    }

    private function plainPreview(string $html, int $maxLen = 200): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($html)) ?? '');
        if ($text === '') {
            return '';
        }
        if (mb_strlen($text) > $maxLen) {
            return mb_substr($text, 0, $maxLen - 1) . '…';
        }
        return $text;
    }

    private function formatDate(string $date): string
    {
        $ts = strtotime($date);
        return $ts ? date('d.m.Y', $ts) : $date;
    }

    private function formatMoney($value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        return number_format((float) $value, 0, '.', ' ') . ' ₽';
    }

    private function formatShiftTime(?string $start, ?string $end): string
    {
        if ($start && $end) {
            return substr($start, 0, 5) . '–' . substr($end, 0, 5);
        }
        return '10:00–22:00';
    }

    /**
     * @param array<string,mixed> $attrs
     */
    private function isShiftClosedFromAttributes(array $attrs): bool
    {
        return ($attrs['bar'] ?? null) !== null
            && ($attrs['manager_id'] ?? null) !== null
            && ($attrs['to_revenue'] ?? null) !== null;
    }

    private function isShiftClosed(DailyReport $report): bool
    {
        return $report->bar !== null
            && $report->manager_id !== null
            && $report->to_revenue !== null;
    }
}
