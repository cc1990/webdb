<?php
namespace vendor\twl\api\login;

use common\helpers\CurlExtend;

class Login
{

    private $token;

    private $curl;

    private $config;

    private $path;
    
    private $check = true;
    
    public $username;
    
    public $password;
    

    public function __construct()
    {
        $this->path = substr(__FILE__, 0, - 9);
        $this->curl = new CurlExtend();
        $this->loadConfig();
        
        $this->username = isset($_POST['username']) ? $_POST['username'] : (isset($_GET['username']) ? $_GET['username'] : '');
        $this->password = isset($_POST['password']) ? $_POST['password'] : (isset($_GET['password']) ? $_GET['password'] : '');
    }

    public function login()
    {
        $this->setToken();
        
        $param = array();
        $param['username'] = trim($this->username);
        $param['password'] = trim($this->password);
        $param['token'] = trim($this->token);
        
        $url = $this->config->url['login'];
        $result = $this->curl->submit($url, $param);
        
        $res = $this->checkresult($result);
        
        die(json_encode($res));
    }

    /**
     * 设置token
     */
    private function setToken()
    {
        if (isset($_SESSION['login_token']) && ! empty($_SESSION['login_token'])) {
            $this->token = $_SESSION['login_token'];
        } else {
            $this->getUrlToken();
        }
    }
    
    
    /**
     * curl获取token并赋值
     */
    private function getUrlToken(){
        $url = $this->config->url['getToken'];
        $result = $this->curl->fetch($url);
        
        $res = $this->checkresult($result);
        $_SESSION['login_token'] = $res['data'];
        $this->token = $_SESSION['login_token'];
    }
    
    /**
     * 验证curl结果
     * @param unknown $result
     * @return multitype:number unknown mixed |multitype:number NULL Ambigous <string, mixed>
     */
    private function checkresult($result){
        if(!is_array($result))$result = json_decode($result, true);
        
        if($result['success'] === true){
            return array('code'=>0, 'msg'=>$result['statusText'], 'data'=>$result['data']);
        }else{
            $res = array('code'=>-1, 'oldcode'=>$result['stateCode']['code'], 'msg'=>($result['statusText'] ? $result['statusText'] : '登录接口异常'));
            if($this->check == true && $res['oldcode'] == -106){  //令牌失效，重新获取
                unset($_SESSION['login_token']);
                $this->check = false;
                $this->login();
            }
            die(json_encode($res));
        }
    }

    /**
     * 自动加配置文件
     * @return boolean
     */
    private function loadConfig()
    {
        if (! file_exists($this->path . 'config.php'))
            return false;
        global $apiconfig;
        $apiconfig = new \stdClass();
        require_once $this->path . 'config.php';
        $this->config = $apiconfig;
    }
}