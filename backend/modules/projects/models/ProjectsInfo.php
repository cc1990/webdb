<?php 
namespace backend\modules\projects\models;

use Yii;
use backend\modules\projects\models\Projects;
use yii\base\Model;

/**
* 
*/
class ProjectsInfo extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return "projects_info";
    }

    public function rules()
    {
        return [
            [['pro_id'], 'required'],
            [['pro_name', 'server_ip', 'test_trunck_date', 'pre_date', 'pro_date',  'remark', 'level'], 'safe']
        ];
    }

    public function attributeLabels()
    {
        return [
            'pro_name' => '项目名称',
            'server_ip' => '开发/测试 库',
            'test_trunck_date' => '测试主干',
            'pre_date' => '预发环境',
            'pro_date' => '线上环境',
            'is_create_history' => '是否创建历史信息',
            'remark' => '备注'
        ];
    }

    /**
     * 获取项目信息
     * @return [type] [description]
     */
    public function getProjects()
    {
        return $this->hasOne(Projects::className(), ['pro_id' => 'pro_id']);
    }
}