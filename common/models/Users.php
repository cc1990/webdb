<?php

namespace common\models;

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
class Users extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{

//    public $id;
//    public $username;
//    public $password;
//    public $status;
//    public $role_id;
//    public $auth_db;
//    public $insert_date;
//    public $is_change_passwd;
//    public $authority;
    public $authKey;
//    public $accessToken;
//    public $chinesename;

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
            [['username', 'password', 'created_at', 'updated_at','authority'], 'required'],
            [['role_id', 'is_change_passwd', 'authority', 'status', 'created_at', 'updated_at'], 'integer'],
            [['insert_date'], 'safe'],
            [['username'], 'string', 'max' => 20],
            [['chinesename'], 'string', 'max' => 20],
            [['password'], 'string', 'max' => 70],
            [['auth_db'], 'string', 'max' => 255]
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
            'password' => '密码',
            'role_id' => 'Role ID',
            'auth_db' => 'Auth Db',
            'insert_date' => 'Insert Date',
            'is_change_passwd' => 'Is Change Passwd',
            'authority' => 'Authority',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id) {
        $user = self::findById($id);
        if ($user) {
//            var_dump($user);
//            echo 1;
//            var_dump(new static($user));exit;
            return ($user);
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null) {
        $user = self::find()->where(array('accessToken' => $token))->one();
        if ($user) {
            return new static($user);
        }
        return null;
    }

    /**
     * Finds user by username
     *
     * @param  string      $username
     * @return static|null
     */
    public static function findByUsername($username) {
        $user = Users::find()->where(array('username' => $username))->one();
        //var_dump($user);exit;
        if ($user) {
            return new static($user);
        }

        return null;
    }

    public static function findById($id) {
        $user = Users::find()->where(array('id' => $id))->asArray()->one();
        if ($user) {
            return new static($user);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey() {
        return $this->authKey;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey) {
        return $this->authKey === $authKey;
    }

    /**
     * Validates password
     *
     * @param  string  $password password to validate
     * @return boolean if password provided is valid for current user
     * 在创建用户的时候，也需要对密码进行操作
     */
    public function validatePassword($password) {
        //方法一:使用自带的加密方式
//echo $this->password;
        //var_dump($this->password === md5($password));exit;
        return $this->password === md5($password);

        //方法二：通过YII自带的验证方式来验证hash是否正确
        //return Yii::$app->getSecurity()->validatePassword($password, $this->password);
    }

    public function resetPassword($id){
        $user = Users::find()->where(['id' => $id])->one();
        if($user){
            $user->password = md5('123456');
            $result = $user->save();
            if($result === true)
                return true;
            else
                return false;
        }else{
            return false;
        }
    }
}
