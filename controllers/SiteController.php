<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\ProfileForm;
use app\services\ProfileCardService;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout', 'profile', 'profile-card-select', 'profile-card-remove', 'profile-card-visibility'],
                'rules' => [
                    [
                        'actions' => ['logout', 'profile', 'profile-card-select', 'profile-card-remove', 'profile-card-visibility'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string|Response
     */
    public function actionIndex()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->redirect(['/site/profile']);
        }
        return $this->redirect(['/site/login']);
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        $this->layout = 'login';
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post())) {
            if ($model->login()) {
                return $this->redirect(['/site/profile']);
            } else {
                // Отладочная информация
                if (YII_DEBUG) {
                    Yii::error('Ошибка входа: ' . print_r($model->errors, true));
                    $user = $model->getUser();
                    if ($user) {
                        Yii::error('Пользователь найден: ' . $user->email);
                        Yii::error('Проверка пароля: ' . ($user->validatePassword($model->password) ? 'OK' : 'FAILED'));
                        Yii::error('Хеш пароля в БД: ' . $user->password_hash);
                    } else {
                        Yii::error('Пользователь не найден для: ' . $model->emailOrPhone);
                    }
                }
            }
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    /**
     * Displays profile page and handles profile update (including avatar upload).
     *
     * @return string|Response
     */
    public function actionProfile()
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/site/login']);
        }

        $user = Yii::$app->user->identity;
        $model = new ProfileForm($user);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Профиль обновлён.');
            return $this->refresh();
        }

        return $this->render('profile', [
            'user' => $user,
            'model' => $model,
        ]);
    }

    /**
     * Выбор карточки профиля.
     *
     * @param int $id ID шаблона
     * @return Response
     */
    public function actionProfileCardSelect(int $id): Response
    {
        /** @var \app\models\User $user */
        $user = Yii::$app->user->identity;
        $service = new ProfileCardService();
        if ($service->selectTemplate($user, $id)) {
            Yii::$app->session->setFlash('success', 'Карточка профиля выбрана.');
        } else {
            Yii::$app->session->setFlash('error', 'Не удалось выбрать карточку профиля.');
        }
        return $this->redirect(['profile']);
    }

    /**
     * Снять выбранную карточку.
     *
     * @return Response
     */
    public function actionProfileCardRemove(): Response
    {
        /** @var \app\models\User $user */
        $user = Yii::$app->user->identity;
        $service = new ProfileCardService();
        $service->removeSelectedTemplate($user);
        Yii::$app->session->setFlash('success', 'Карточка профиля снята.');
        return $this->redirect(['profile']);
    }

    /**
     * Изменить видимость карточки.
     *
     * @param int $id ID шаблона
     * @param int $visible 1/0
     * @return Response
     */
    public function actionProfileCardVisibility(int $id, int $visible): Response
    {
        /** @var \app\models\User $user */
        $user = Yii::$app->user->identity;
        $service = new ProfileCardService();
        $ok = $service->setVisibility($user, $id, $visible === 1);
        if ($ok) {
            Yii::$app->session->setFlash('success', $visible ? 'Карточка отображается.' : 'Карточка скрыта.');
        } else {
            Yii::$app->session->setFlash('error', 'Не удалось изменить видимость карточки.');
        }
        return $this->redirect(['profile']);
    }
}
