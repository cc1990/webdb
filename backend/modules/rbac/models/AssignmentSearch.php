<?php

namespace johnitvn\rbacplus\models;

use Yii;
use yii\data\ActiveDataProvider;
use johnitvn\rbacplus\Module;
use common\models\AuthAssignment;

/**
 * @author John Martin <john.itvn@gmail.com>
 * @since 1.0.0
 * 
 */
class AssignmentSearch extends \yii\base\Model {

    /**
     * @var Module $rbacModule
     */
    protected $rbacModule;

    /**
     *
     * @var mixed $id
     */
    public $id;

    public $username;

    public $chinesename;

    public $departments;

    public $roles;
    /**
     *
     * @var string $login
     */
    public $login;

    /**
     * @inheritdoc
     */
    public function init() {
        parent::init();
        $this->rbacModule = Yii::$app->getModule('rbac');
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id', 'login','username','chinesename','departments','roles'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => Yii::t('rbac', 'ID'),
            'login' => $this->rbacModule->userModelLoginFieldLabel,
            'username' => Yii::t('rbac', 'username'),
            'chinesename' => Yii::t('rbac', 'chinesename'),
            'departments' => Yii::t('rbac', 'departments'),
            'roles' => $this->rbacModule->userModelLoginFieldLabel,
        ];
    }

    /**
     * Create data provider for Assignment model.    
     */
    public function search() {
        $query = call_user_func($this->rbacModule->userModelClassName . "::find");
        //var_dump($query);exit;
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
        $params = Yii::$app->request->getQueryParams();
        //var_dump($params);exit;
        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }
        //$query->andFilterWhere([$this->userModule->userModelIdField => $this->id]);
        $query->andFilterWhere([$this->rbacModule->userModelIdField => $this->id]);
        //var_dump($this->login);exit;
        $query->andFilterWhere(['like', $this->rbacModule->userModelLoginField, $this->username]);
        //var_dump(['like', $this->rbacModule->userModelChinesenameField, $this->chinesename]);exit;
        $query->andFilterWhere(['like', $this->rbacModule->userModelChinesenameField, $this->chinesename]);
        //$query->andFilterWhere(['like', $this->rbacModule->userModelRolesField, $this->roles]);
        //echo $this->roles;exit;
        $query->andFilterWhere(['like', $this->rbacModule->userModelDepartmentsField, $this->departments]);
        //$query->andFilterWhere(['in', 'id', AuthAssignment::findIdsByItemName($this->roles)]);
        $query->andFilterWhere(['in', 'id', $this->findIdsByLikeItemName($this->roles)]);
        return $dataProvider;
    }

    /**
     * 根据角色名称模糊搜索
     * @param  [type] $itemName [description]
     * @return [type]           [description]
     */
    public function findIdsByLikeItemName($itemName) {
        if(empty($itemName))
            return '';
        $return = array();
        $list = AuthAssignment::find()->where(['like', 'item_name', $itemName ])->asArray()->all();
        if (!empty($list)) {
            foreach($list as $val){
                $return[] = $val['user_id'];
            }
            return $return;
        }else{
            return 0;
        }

    }

}
