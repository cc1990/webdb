<?php 
namespace backend\modules\logs\models;

use Yii;
use yii\base\Model;

/**
* ddl大数据查看规则配置
*/
class DdlConfigs extends \yii\db\ActiveRecord
{
    /**
     * @description 定义表格名称
    */
    public static function tableName(){
        return 'ddl_configs';
    }

    /**
     * @description 获取规则列表
    */
    public function getRule()
    {
        $rule_list = $this->find()->where(['status' => 1])->asArray()->all();
        $rule = [];
        foreach($rule_list as $value){
            $rule[$value['database']][] = $value['table'];
        }
        return $rule;
    }
}
?>