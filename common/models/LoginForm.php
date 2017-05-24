<?php
namespace common\models;

use Yii;
use yii\base\Model;
use common\models\LoginRecords;
/**
 * Login form
 */
class LoginForm extends Model
{
    public $username;
    public $password;
    public $rememberMe = true;
    public $sso = false;
    private $_user;


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['username', 'password'], 'required'],
            // rememberMe must be a boolean value
            ['rememberMe', 'boolean'],
            // password is validated by validatePassword()
            ['password', 'validatePassword'],
        ];
    }


    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if ($this->sso == false &&(!$user || !$user->validatePassword($this->password))) {
                $this->addError($attribute, '账户或密码有误.');
            }
        }
    }

    /**
     * Logs in a user using the provided username and password.
     *
     * @return boolean whether the user is logged in successfully
     */
    public function login()
    {
        $User = $this->getUser();
        if(!empty($User) && $User->authority != 0 && $this->sso === false){
            $this->addError( 'username','该账户未被授权从此页面登录，请从集中运维管理平台跳转');
            return false;
        }

        if ($this->validate()) {
            LoginRecords::addRecord($User->id,(int)$this->sso);
            return Yii::$app->users->login($User, 3600 * 24 * 30);
        } else {
            return false;
        }
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    protected function getUser()
    {
        if ($this->_user === null) {
            $this->_user = Users::findByUsername($this->username);
        }

        return $this->_user;
    }
}
