<?php

namespace app\modules\user\controllers;

use Yii;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
//models
use app\modules\user\models\AuthItem;
use app\modules\user\models\AuthAssignment;
use app\modules\user\models\User;
use app\modules\user\models\searchmodel\UserSearch;
use app\modules\user\components\RootController;

/**
 * UserController implements the CRUD actions for User model.
 */
class AdminController extends RootController {

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all User models.
     * @return mixed
     */
    public function actionIndex() {
        $searchModel = new UserSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single User model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id) {
        return $this->render('view', [
                    'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new User model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate() {
        $model = new User();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            if (isset($_POST['roles']) && count($_POST['roles']) > 0) {
                foreach ($_POST['roles'] as $key => $value) {
                    $authAssignment = new AuthAssignment();
                    $authAssignment->item_name = $key;
                    $authAssignment->user_id = strval($model->id);
                    $authAssignment->save();
                }
            }
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            $roles = AuthItem::find()->where(['type' => AuthItem::TYPE_ROLE])->all();
            return $this->render('create', [
                        'model' => $model,
                        'roles' => $roles,
            ]);
        }
    }

    /**
     * Updates an existing User model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id) {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {

            if (preg_match('/^[a-f0-9]{32}$/', $model->password) == 0) {
                $model->password = md5($model->password);
                $model->save();
            }
            $existingRoles = $model->getRoles(true);

            $updatedRoles = [];
            if (isset($_POST['roles']) && count($_POST['roles']) > 0) {
                foreach ($_POST['roles'] as $key => $item) {
                    $updatedRoles[$key] = $key;
                }
            }

            if (is_array($existingRoles)) {
                $removedRoles = array_diff($existingRoles, $updatedRoles);
            }

            if (is_array($existingRoles)) {
                $newRoles = array_diff($updatedRoles, $existingRoles);
            }

            if (!empty($removedRoles)) {
                AuthAssignment::deleteAll(['and', 'user_id = :p1', ['in', 'item_name', $removedRoles]], [':p1' => $model->id]);
            }

            if (!empty($newRoles)) {
                foreach ($newRoles as $value) {
                    $authAssignment = new AuthAssignment();
                    $authAssignment->user_id = strval($model->id);
                    $authAssignment->item_name = strval($value);
                    $authAssignment->save();
                }
            }

            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            $roles = AuthItem::find()->where(['type' => AuthItem::TYPE_ROLE])->all();
            return $this->render('update', [
                        'model' => $model,
                        'roles' => $roles,
                        'userRoles' => $model->getRoles(true),
            ]);
        }
    }

    /**
     * Deletes an existing User model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id) {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id) {
        if (($model = User::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

}
