<?php

namespace app\modules\user\controllers;

use Yii;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;

//models
use app\modules\user\models\AuthItemChild;
use app\modules\user\models\AuthItem;
use app\modules\user\models\searchmodel\AuthItemSearch;

use app\modules\user\components\RootController;

/**
 * RoleController implements the CRUD actions for AuthItem model.
 */
class RoleController extends RootController {

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
     * Lists all AuthItem models.
     * @return mixed
     */
    public function actionIndex() {
        $searchModel = new AuthItemSearch();
        $searchModel->type = AuthItem::TYPE_ROLE;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single AuthItem model.
     * @param string $id
     * @return mixed
     */
    public function actionView($id) {
        return $this->render('view', [
                    'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new AuthItem model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate() {
        $model = new AuthItem();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->name]);
        } else {
            return $this->render('create', [
                        'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing AuthItem model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     */
    public function actionUpdate($id) {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->name]);
        } else {
            return $this->render('update', [
                        'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing AuthItem model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     */
    public function actionDelete($id) {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the AuthItem model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return AuthItem the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id) {
        if (($model = AuthItem::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionAssingchildrole($id) {
        $roles = AuthItem::find()->all();
        return $this->render('assingchildrole', ['roles' => $roles]);
    }

    public function actionAssingpermission($id) {
        $routes = AuthItem::getRoutes();
        $childRoles = Yii::$app->authManager->getChildRoles($id);
        $childRoles = isset($childRoles) ? ArrayHelper::map($childRoles, 'name', 'name') : [];

        $childPermission = Yii::$app->authManager->getPermissionsByRole($id);
        $childPermission = $childPermission ? ArrayHelper::map($childPermission, 'name', 'name') : [];

        return $this->render('assingpermission', ['role' => $id, 'routes' => $routes, 'childRoles' => $childRoles, 'childPermission' => $childPermission]);
    }

    public function actionCreatepermission($id) {
        if (Yii::$app->request->isPost) {
            $name = $_POST['name'];

            if (AuthItem::find()->where(['name' => $name])->exists() == FALSE) {
                $newPermission = new AuthItem();
                $newPermission->name = $name;
                $newPermission->type = AuthItem::TYPE_PERMISSION;
                $newPermission->save();
            }

            $newAddChild = new AuthItemChild();
            $newAddChild->parent = $id;
            $newAddChild->child = $name;
            $newAddChild->save();

            echo json_encode(['status' => 1, 'data' => ['name' => $name]]);
        } else {
            echo json_encode(['status' => 0]);
        }
    }

    public function actionDeletepermission($id) {
        if (Yii::$app->request->isPost) {
            
            $model = AuthItemChild::find()->where(['parent'=>$id,'child'=>$_POST["name"]])->one();
            $model->delete();
            
            echo json_encode(['status' => 1]);
        } else {
            echo json_encode(['status' => 0]);
        }
    }
}
