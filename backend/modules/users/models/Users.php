<?php

namespace backend\modules\users\models;

use Yii;

/**
 * This is the model class for table "users".
 *
 * @property integer $id
 * @property string $username
 * @property string $password
 * @property string $role_id
 * @property string $auth_db
 * @property string $insert_date
 * @property integer $is_change_passwd
 * @property integer $authority
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 */
class Users extends \common\models\users
{
    public $old_password;
    public $new_password;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'users';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['username', 'authority'], 'required'],
            [['username'], 'unique'],
            [['role_id', 'is_change_passwd', 'authority', 'status', 'created_at', 'updated_at'], 'integer'],
            [['insert_date'], 'safe'],
            ['new_password', 'compare', 'compareAttribute' => 'password', 'message' => '两次新密码输入不一致'],
            ['old_password', 'validateOldPassword'],
            [[ 'password'], 'convert', 'on' => ['create', 'update']],
            ['authority', 'default', 'value' => 1, 'on' => 'create'],
            ['password', 'default', 'value' => md5('123456'), 'on' => 'create'],
            ['updated_at', 'default', 'value' => time(), 'on' => 'update'],
            [['username'], 'string', 'max' => 20],
            [['password'], 'string', 'max' => 70],
            [['auth_db'], 'string', 'max' => 255]
        ];
    }

    /**
     * 场景设置(non-PHPdoc)
     * add 新增  edit 修改  pwd 修改密码  login 登陆  set 修改资料
     * @see \yii\base\Model::scenarios()
     */
    public function scenarios(){
        return [
            'create' => ['username', 'password','chinesename','authority'],
            'update' => ['username', 'authority','chinesename','updated_at'],
            'pwd' => ['old_password','password','new_password'],
        ];
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => '工号',
            'chinesename' => '姓名',
            'password' => '新密码',
            'departments' => '部门',
            'old_password' => '旧密码',
            'new_password' => '重复新密码',
            'role_id' => 'role id',
            'auth_db' => 'Auth Db',
            'insert_date' => '插入时间',
            'is_change_passwd' => '是否修改过密码（0:未修改1:修改过）',
            'authority' => '登陆属性(0:后台直接登陆+平台跳转，1：需要运维平台跳转)',
            'status' => '状态',
            'created_at' => '添加时间',
            'updated_at' => '修改时间',
        ];
    }

    public function validateOldPassword($attribute, $params)
    {
        //print_r($this->password);exit;
        $user = self::findByUsername(Yii::$app->users->identity->username);
        if ($user->password !== md5($this->old_password)) {
            $this->addError($attribute, $this->old_password.'旧密码输入有误！');
        }else{
            $this->password = md5($this->new_password);
            $this->is_change_passwd = 1;
            //unset($this->new_password,$this->new_password2);
        }
    }

    public function convert()
    {
        $this->password = md5($this->password);
    }


    public function findSelf()
    {
        $user = self::findByUsername(Yii::$app->users->identity->username);
        return $user;
    }
}
