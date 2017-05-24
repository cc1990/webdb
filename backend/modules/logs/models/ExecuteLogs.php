<?php 
namespace backend\modules\logs\models;

use common\models\Users;
use Yii;

/**
* 脚本日志模型
*/
class ExecuteLogs extends \yii\db\ActiveRecord
{
    /**
     * @description 定义表格名称
    */
    public static function tableName(){
        return 'execute_logs';
    }

    /**
     * @description 获取操作日志列表
     * @param $where array 检索条件
     * @param $rule array 检索规则
     * @param $limit int 每页展示数
     * @param $page int 起始页
    */
    public function getList($where,$rule,$limit = 20,$page = 1){
        $sqlstr = 'select l.`host`,l.`database`,u.username,l.`script`,l.project_name,l.created_date from execute_logs l left join users u on l.user_id = u.id ';
        $sqlstr .= $this->_getWhere($where,$rule);
        $sqlstr .= " limit ".($page-1)*$limit.",{$limit}";
        $ddl_list = $this->getDb()->createCommand($sqlstr)->queryAll();
        return $ddl_list;
    }

    public function getusers()
    {
        return $this->hasOne(Users::className(),['id' => 'user_id']);
    }

    /**
     * @description 获取操作日志数目
     * @param $where array 检索条件
     * @param $rule array 检索规则
     * @param $limit int 每页展示数
     * @param $page int 起始页
    */
    public function getCount($where,$rule){
        $sqlstr = 'select count(*) from execute_logs l left join users u on l.user_id = u.id ';
        $sqlstr .= $this->_getWhere($where,$rule);
        $count = $this->getDb()->createCommand($sqlstr)->queryAll();
        return $count;
    }

    /**
     * @description 获取where语句
    */
    private function _getWhere($where,$rule)
    {
        $sqlstr = "where ";
        foreach ($where as $key=>$value) {
            if($key == "script") {
                $sqlstr .= "`{$key}` like '%{$value}%' and ";
            }else{
                $sqlstr .= "`{$key}` = '{$value}' and ";
            }
        }
        if(!empty($rule)) {
            $sqlstr .= "(";
            foreach ($rule as $key => $value) {
                $regexp = '';
                foreach($value as $value2){
                    $regexp .= " {$value2}| `{$value2}`|";
                }
                $sqlstr .= "(`database` = '{$key}' and `script` regexp '" . substr($regexp,0,-1) . "') or ";
            }
            $sqlstr = substr($sqlstr,0,-3).")";
            return $sqlstr;
        }
        return substr($sqlstr,0,-4);
    }
}
?>