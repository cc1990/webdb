<?php
namespace frontend\controllers;

use Yii;
use yii\web\Controller;

class ApiController extends Controller
{
    
    public $enableCsrfValidation = false;
    public $service_name;
    public $service_action;
    public $class_namespace;
    
    const NAMESPACE_PATH = 'vendor\twl\api\\';
    const ACTION_PREFIX = 'ACTION';
    
    public function init(){
        parent::init();
        //控制器
        $this->service_name = empty(\Yii::$app->request->post('service')) ? \Yii::$app->request->get('service') : \Yii::$app->request->post('service');
        $this->service_name = ucfirst(strtolower($this->service_name));
        //方法
        $this->service_action = empty(\Yii::$app->request->post('action')) ? \Yii::$app->request->get('action') : \Yii::$app->request->post('action');
        $this->service_action = ucfirst(strtolower($this->service_action));
    }

    /**
     * 入口
     * @return string
     */
    public function actionIndex(){

        if(!$this->isClass()){
           $result = array('code'=>-1,'msg'=>'类不存在');
           return json_encode($result);
        }
        
        //引入对应的类
        $obj = new $this->class_namespace($this->service_name,$this->module);
        
        if(!$this->isAction()){
            $result = array('code'=>-2,'msg'=>'方法不存在');
            return json_encode($result);
        }
        
        //调用方法
        $action = $this->service_action;
        $obj->$action();
    }
    
    /**
     * 判断控制器是否存在
     * @return boolean
     */
    private function isClass() {
        $this->class_namespace = self::NAMESPACE_PATH.strtolower($this->service_name).'\\'.$this->service_name;
        return class_exists($this->class_namespace);
    }
    
    /**
     * 判断控制器中方法是否存在
     * @return boolean
     */
    private function isAction() {
        return method_exists($this->class_namespace,$this->service_action);
    }

}