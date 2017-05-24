<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "ins_user".
 *
 * @property integer $user_id
 * @property string $phone
 * @property string $email
 * @property integer $create_time
 * @property integer $update_time
 * @property integer $create_person
 * @property integer $update_person
 */
class User extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{
    public $id;
    public $username;
    public $password;
    public $status;
    public $role_id;
    public $auth_db;
    public $insert_date;
    public $is_change_passwd;
    public $authority;
    public $authKey;
    public $accessToken;
    public $chinesename;
    //public $created_at;
    //public $updated_at;

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
        $user = Users::find()->where(array('accessToken' => $token))->one();
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
        return $this->password === md5($password);

        //方法二：通过YII自带的验证方式来验证hash是否正确
        //return Yii::$app->getSecurity()->validatePassword($password, $this->password);
    }
}
