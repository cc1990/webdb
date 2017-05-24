<?php 
namespace common\models;

use Yii;
use yii\base\Model;

/**
* 
*/
class SystemLogs extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'system_logs';
    }

    public function rules()
    {
        return [
            [['username', 'action', 'log', 'create_time'], 'safe']
        ];
    }

    /**
     * 添加系统日志
     * @param  [String] $action [操作]
     * @param  [String] $log    [说明]
     * @return [type]         [description]
     */
    public static function create( $action, $log ){
        $model = new SystemLogs();
        $model->username = Yii::$app->users->identity->username;
        $model->action = $action;
        $model->log = $log;
        $model->create_time = date("Y-m-d H:i:s");

        $model->insert();
    }
}