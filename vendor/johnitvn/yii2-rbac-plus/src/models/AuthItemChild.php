<?php

namespace johnitvn\rbacplus\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @author John Martin <john.itvn@gmail.com>
 * @since 1.0.0
 */
class AuthItemChild extends ActiveRecord {

    /**
     *
     * @var string 
     */
    public $name;

    /**
     * @var string classname of Rule
     */
    public $className;

    /**
     * @var \yii\rbac\Rule
     */
    private $item;

    /**
     * Description 保存数据
    */
    public function saveData($name,$permissions){
        //清空数据
        $this->deleteAll(['parent'=>$name]);
        if(empty($permissions)) return true;

        $data['parent'] = $name;
        foreach($permissions as $value){
            $this->isNewRecord = true;
            $this->parent = $name;
            $this->child = $value;
            $this->insert();
        }
    }
}
