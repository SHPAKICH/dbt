<?php

namespace app\modules\api\controllers;

use app\models\News;
use Yii;

class NewsController extends BaseApiController
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
            'index' => ['GET'],
            'view' => ['GET'],
        ];
    }

    /**
     * GET /api/v1/news
     * Список опубликованных новостей (превью: заголовок + короткий текст).
     */
    public function actionIndex(): array
    {
        $items = News::find()
            ->where(['is_published' => 1])
            ->orderBy(['published_at' => SORT_DESC, 'created_at' => SORT_DESC])
            ->limit(50)
            ->all();

        $list = [];
        foreach ($items as $n) {
            $textOnly = trim(strip_tags($n->body));
            $preview = $textOnly === '' ? '' : mb_substr($textOnly, 0, 200);
            if (mb_strlen($textOnly) > 200) {
                $preview .= '…';
            }
            $list[] = [
                'id' => (int)$n->id,
                'title' => $n->title,
                'preview' => $preview,
                'createdAt' => $n->created_at,
                'publishedAt' => $n->published_at,
            ];
        }

        return $this->success(['items' => $list]);
    }

    /**
     * GET /api/v1/news/:id
     * Одна новость целиком (для полного просмотра).
     */
    public function actionView(int $id): array
    {
        $n = News::findOne(['id' => $id, 'is_published' => 1]);
        if (!$n) {
            \Yii::$app->response->statusCode = 404;
            return $this->error('Новость не найдена', [], 404);
        }
        return $this->success([
            'id' => (int)$n->id,
            'title' => $n->title,
            'body' => $n->body,
            'createdAt' => $n->created_at,
            'publishedAt' => $n->published_at,
        ]);
    }
}

