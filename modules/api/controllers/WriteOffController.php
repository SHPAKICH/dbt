<?php

namespace app\modules\api\controllers;

use app\services\WriteOffService;
use Yii;

/**
 * Списание продуктов (документация).
 *
 * GET  /api/v1/write-off/locations
 * GET  /api/v1/write-off/entries?location_id=&date=
 * POST /api/v1/write-off/save
 */
class WriteOffController extends BaseApiController
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
            'locations' => ['GET'],
            'entries' => ['GET'],
            'save' => ['POST'],
        ];
    }

    private function getService(): WriteOffService
    {
        return Yii::$container->get(WriteOffService::class);
    }

    private function requireAccess(): void
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            throw new \yii\web\UnauthorizedHttpException('Не авторизован.');
        }
        if (!$user->isAdmin() && !in_array($user->position, ['manager', 'location_manager', 'senior_teamaker'], true)) {
            throw new \yii\web\ForbiddenHttpException('Нет доступа к списанию.');
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

    public function actionLocations(): array
    {
        $this->requireAccess();
        $service = $this->getService();
        $list = [];
        foreach ($service->getAccessibleLocations() as $loc) {
            $list[] = ['id' => (int) $loc->id, 'name' => $loc->name];
        }

        return $this->success(['locations' => $list]);
    }

    public function actionEntries(): array
    {
        $this->requireAccess();
        $service = $this->getService();
        $locationId = (int) (Yii::$app->request->get('location_id') ?? 0);
        $date = (string) (Yii::$app->request->get('date') ?? date('Y-m-d'));
        if (!$locationId || !$service->canAccessLocation($locationId)) {
            return $this->success([
                'items' => [],
                'totals' => $service->buildTotals([]),
                'grandTotal' => 0.0,
            ]);
        }

        $items = $service->getEntries($locationId, $date);

        return $this->success([
            'items' => $items,
            'totals' => $service->buildTotals($items),
            'grandTotal' => $service->sumAmounts($items),
        ]);
    }

    public function actionSave(): array
    {
        $this->requireAccess();
        $body = $this->parseJsonBody();
        $locationId = (int) ($body['locationId'] ?? $body['location_id'] ?? 0);
        $date = (string) ($body['date'] ?? $body['entryDate'] ?? '');
        $items = $body['items'] ?? [];
        if (!is_array($items)) {
            $items = [];
        }

        try {
            $data = $this->getService()->saveEntries($locationId, $date, $items);
        } catch (\yii\web\ForbiddenHttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), [], 422);
        }

        return $this->success($data, 'Списание сохранено.');
    }
}
