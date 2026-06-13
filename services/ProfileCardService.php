<?php

namespace app\services;

use app\models\User;
use app\models\ProfileCardTemplate;
use app\models\UserProfileCard;

class ProfileCardService
{
    /**
     * Пока просто возвращаем все активные шаблоны.
     */
    public function getAvailableTemplates(User $user): array
    {
        return ProfileCardTemplate::find()
            ->where(['is_active' => 1])
            ->all();
    }

    /**
     * Текущая выбранная карточка пользователя.
     */
    public function getSelectedTemplate(User $user): ?ProfileCardTemplate
    {
        $userCard = UserProfileCard::find()
            ->where(['user_id' => $user->id, 'is_selected' => 1])
            ->with('template')
            ->one();

        return $userCard && $userCard->template ? $userCard->template : null;
    }

    /**
     * Для нескольких пользователей вернуть код выбранной карточки: [user_id => ['code' => ..., 'cssClass' => ...]]
     */
    public function getSelectedTemplateCodesForUserIds(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }
        $userIds = array_map('intval', $userIds);
        $rows = UserProfileCard::find()
            ->where(['user_id' => $userIds, 'is_selected' => 1])
            ->with('template')
            ->all();
        $result = [];
        foreach ($rows as $uc) {
            if ($uc->template && $uc->template->is_active) {
                $result[$uc->user_id] = [
                    'code' => $uc->template->code ?? '',
                    'cssClass' => $uc->template->css_class ?? 'profile-card-' . ($uc->template->code ?? 'default'),
                    'styleConfig' => $uc->template->getStyleConfigArray(),
                ];
            }
        }
        return $result;
    }

    /**
     * Выбрать шаблон карточки для пользователя.
     */
    public function selectTemplate(User $user, int $templateId): bool
    {
        $template = ProfileCardTemplate::findOne([
            'id' => $templateId,
            'is_active' => 1,
        ]);

        if (!$template) {
            return false;
        }

        UserProfileCard::updateAll(['is_selected' => 0], ['user_id' => $user->id]);

        $userCard = UserProfileCard::findOne([
            'user_id' => $user->id,
            'template_id' => $template->id,
        ]);

        if (!$userCard) {
            $userCard = new UserProfileCard();
            $userCard->user_id = $user->id;
            $userCard->template_id = $template->id;
            $userCard->created_at = time();
            $userCard->unlocked_at = time();
            $userCard->is_visible = 1;
        }

        $userCard->is_selected = 1;
        return $userCard->save(false);
    }

    /**
     * Снять выбранную карточку (ничего не выбрано).
     */
    public function removeSelectedTemplate(User $user): void
    {
        UserProfileCard::updateAll(['is_selected' => 0], ['user_id' => $user->id]);
    }

    /**
     * Скрыть / показать карточку для пользователя.
     */
    public function setVisibility(User $user, int $templateId, bool $isVisible): bool
    {
        $template = ProfileCardTemplate::findOne($templateId);
        if (!$template) {
            return false;
        }

        $userCard = UserProfileCard::findOne([
            'user_id' => $user->id,
            'template_id' => $template->id,
        ]);

        if (!$userCard) {
            $userCard = new UserProfileCard();
            $userCard->user_id = $user->id;
            $userCard->template_id = $template->id;
            $userCard->created_at = time();
            $userCard->unlocked_at = time();
        }

        $userCard->is_visible = $isVisible ? 1 : 0;
        return $userCard->save(false);
    }
}

