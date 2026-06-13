<?php

namespace app\modules\api\controllers;

use app\services\AnalyticsService;
use app\services\IikoClient;
use Yii;
use yii\web\BadRequestHttpException;

/**
 * API аналитики точек на карте.
 *
 * GET  /api/v1/analytics/points
 * GET  /api/v1/analytics/points/{id}
 * POST /api/v1/analytics/points/{id} — детали из iiko
 * POST /api/v1/analytics/leaderboard — лидерборд по выручке
 */
class AnalyticsController extends BaseApiController
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
            'points' => ['GET'],
            'point' => ['GET', 'POST'],
            'leaderboard' => ['POST'],
        ];
    }

    private function getService(): AnalyticsService
    {
        return Yii::$container->get(AnalyticsService::class);
    }

    private function requireAnalyticsAccess(): void
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            throw new \yii\web\UnauthorizedHttpException('Не авторизован.');
        }
        if (!$user->isAdmin() && !in_array($user->position, ['manager', 'location_manager'], true)) {
            throw new \yii\web\ForbiddenHttpException('Нет доступа к аналитике.');
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

    private function createIikoClient(array $body): IikoClient
    {
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
     * @return array{0: string, 1: string}
     */
    private function parsePeriod(array $body): array
    {
        $from = trim((string) ($body['from'] ?? $body['periodFrom'] ?? ''));
        $to = trim((string) ($body['to'] ?? $body['periodTo'] ?? ''));

        return [$from, $to];
    }

    /**
     * GET /api/v1/analytics/points
     */
    public function actionPoints(): array
    {
        $this->requireAnalyticsAccess();
        $service = $this->getService();

        return $this->success(['points' => $service->getPoints()]);
    }

    /**
     * GET /api/v1/analytics/points/{id}
     * POST /api/v1/analytics/points/{id} — данные из iiko за период
     */
    public function actionPoint(int $id): array
    {
        $this->requireAnalyticsAccess();
        $service = $this->getService();

        if (Yii::$app->request->isPost) {
            $body = $this->parseJsonBody();
            [$from, $to] = $this->parsePeriod($body);
            try {
                $client = $this->createIikoClient($body);
                $detail = $service->getPointDetailFromIiko($client, $id, $from, $to);
            } catch (BadRequestHttpException $e) {
                throw $e;
            } catch (\Throwable $e) {
                return $this->error($e->getMessage(), [], 502);
            }
        } else {
            $detail = $service->getPointDetail($id);
        }

        if ($detail === null) {
            return $this->error('Точка не найдена или нет доступа.', [], 404);
        }

        return $this->success(['point' => $detail]);
    }

    /**
     * POST /api/v1/analytics/leaderboard
     */
    public function actionLeaderboard(): array
    {
        $this->requireAnalyticsAccess();
        $body = $this->parseJsonBody();
        [$from, $to] = $this->parsePeriod($body);
        $service = $this->getService();

        try {
            $client = $this->createIikoClient($body);
            $data = $service->getLeaderboard($client, $from, $to);
        } catch (BadRequestHttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), [], 502);
        }

        return $this->success($data);
    }
}
