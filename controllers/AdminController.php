<?php

namespace app\controllers;

use Yii;
use app\services\DatabaseBackupService;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use app\models\User;
use app\models\Location;
use app\models\UserUpdateForm;
use app\models\UserCreateForm;

class AdminController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'matchCallback' => function ($rule, $action) {
                            if (Yii::$app->user->isGuest) {
                                return false;
                            }
                            $user = Yii::$app->user->identity;
                            // Админ имеет доступ ко всему
                            if ($user->isAdmin()) {
                                return true;
                            }
                            // Управляющий имеет доступ к списку пользователей и созданию
                            if ($user->position === 'manager') {
                                $allowedActions = ['users', 'create-user'];
                                return in_array($action->id, $allowedActions);
                            }
                            return false;
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'backup-database' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Главная страница админ-панели
     *
     * @return string
     */
    public function actionIndex()
    {
        $usersCount = User::find()->count();
        $locationsCount = Location::find()->count();
        $activeUsersCount = User::find()->where(['is_active' => 1])->count();
        $backupService = new DatabaseBackupService();

        return $this->render('index', [
            'usersCount' => $usersCount,
            'locationsCount' => $locationsCount,
            'activeUsersCount' => $activeUsersCount,
            'recentBackups' => $backupService->getRecentBackups(),
            'backupDirectory' => $backupService->getBackupDirectory(),
            'backupRetentionDays' => $backupService->getRetentionDays(),
            'backupScheduleLabel' => $backupService->getScheduleLabel(),
        ]);
    }

    /**
     * Создать дамп базы данных и отдать файл на скачивание.
     *
     * @return \yii\web\Response
     */
    public function actionBackupDatabase()
    {
        if (Yii::$app->user->isGuest || !Yii::$app->user->identity->isAdmin()) {
            throw new ForbiddenHttpException('Резервное копирование доступно только администраторам.');
        }

        try {
            $result = (new DatabaseBackupService())->createBackup();
            return Yii::$app->response->sendFile($result['path'], $result['filename']);
        } catch (\Throwable $e) {
            Yii::error($e, __METHOD__);
            Yii::$app->session->setFlash('error', 'Не удалось создать резервную копию базы данных: ' . $e->getMessage());
            return $this->redirect(['index']);
        }
    }

    /**
     * Список пользователей
     *
     * @return string
     */
    public function actionUsers()
    {
        $currentUser = Yii::$app->user->identity;
        $query = User::find()->with('location');
        
        $managerLocations = [];
        $isManager = $currentUser->position === 'manager' && !$currentUser->isAdmin();
        
        // Если управляющий (не админ), показываем только пользователей его точек
        if ($isManager) {
            // Получаем точки управляющего из таблицы manager_locations
            $managerLocations = (new \yii\db\Query())
                ->from('manager_locations')
                ->where(['manager_id' => $currentUser->id])
                ->select('location_id')
                ->column();
            
            if (!empty($managerLocations)) {
                // Показываем всех пользователей, привязанных к точкам управляющего
                $query->where(['location_id' => $managerLocations]);
            } else {
                // Если у управляющего нет точек, показываем пустой список
                $query->where('1=0');
            }
        }
        
        $users = $query->orderBy(['created_at' => SORT_DESC])->all();

        return $this->render('users', [
            'users' => $users,
            'isManager' => $isManager,
            'managerLocations' => $managerLocations,
        ]);
    }

    /**
     * Создание нового пользователя
     *
     * @return string|\yii\web\Response
     */
    public function actionCreateUser()
    {
        $model = new UserCreateForm();
        $currentUser = Yii::$app->user->identity;

        if ($model->load(Yii::$app->request->post())) {
            // Валидация для управляющего
            if ($currentUser->position === 'manager' && !$currentUser->isAdmin()) {
                $allowedPositions = ['location_manager', 'senior_teamaker', 'teamaker', 'trainee'];
                if (!in_array($model->position, $allowedPositions)) {
                    $model->addError('position', 'Управляющий может создавать пользователей только до уровня "Менеджер точки".');
                }
            }

            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Пользователь успешно создан.');
                return $this->redirect(['users']);
            }
        }

        return $this->render('create-user', [
            'model' => $model,
        ]);
    }

    /**
     * Редактирование пользователя
     *
     * @param integer $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionUpdateUser($id)
    {
        $user = User::findOne($id);
        if (!$user) {
            throw new NotFoundHttpException('Пользователь не найден.');
        }

        $model = new UserUpdateForm();
        $model->loadUser($user);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Пользователь успешно обновлен.');
            return $this->redirect(['users']);
        }

        return $this->render('update-user', [
            'model' => $model,
            'user' => $user,
        ]);
    }

    /**
     * Удаление пользователя
     *
     * @param integer $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionDeleteUser($id)
    {
        $user = User::findOne($id);
        if (!$user) {
            throw new NotFoundHttpException('Пользователь не найден.');
        }

        // Нельзя удалить самого себя
        if ($user->id == Yii::$app->user->id) {
            Yii::$app->session->setFlash('error', 'Нельзя удалить самого себя.');
            return $this->redirect(['users']);
        }

        // Нельзя удалить другого админа
        if ($user->isAdmin() && $user->id != Yii::$app->user->id) {
            Yii::$app->session->setFlash('error', 'Нельзя удалить другого администратора.');
            return $this->redirect(['users']);
        }

        $user->delete();
        Yii::$app->session->setFlash('success', 'Пользователь удален.');
        return $this->redirect(['users']);
    }

    /**
     * Список точек
     *
     * @return string
     */
    public function actionLocations()
    {
        $locations = Location::find()
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        return $this->render('locations', [
            'locations' => $locations,
        ]);
    }

    /**
     * Редактирование точки
     *
     * @param integer $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionUpdateLocation($id)
    {
        $location = Location::findOne($id);
        if (!$location) {
            throw new NotFoundHttpException('Точка не найдена.');
        }

        if ($location->load(Yii::$app->request->post()) && $location->save()) {
            Yii::$app->session->setFlash('success', 'Точка успешно обновлена.');
            return $this->redirect(['locations']);
        }

        return $this->render('update-location', [
            'location' => $location,
        ]);
    }

    /**
     * Создание новой точки
     *
     * @return string|\yii\web\Response
     */
    public function actionCreateLocation()
    {
        $location = new Location();

        if ($location->load(Yii::$app->request->post()) && $location->save()) {
            Yii::$app->session->setFlash('success', 'Точка успешно создана.');
            return $this->redirect(['locations']);
        }

        return $this->render('create-location', [
            'location' => $location,
        ]);
    }

    /**
     * Удаление точки
     *
     * @param integer $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionDeleteLocation($id)
    {
        $location = Location::findOne($id);
        if (!$location) {
            throw new NotFoundHttpException('Точка не найдена.');
        }

        $location->delete();
        Yii::$app->session->setFlash('success', 'Точка удалена.');
        return $this->redirect(['locations']);
    }
}

