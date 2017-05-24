<?php 
namespace backend\modules\logs\models;

use Yii;
use vendor\twl\tools\utils\Output;

/**
* 
*/
class Version extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return "version_logs";
    }

    public function rules()
    {
        return [
            
        ];
    }

    public function attributeLabels()
    {
        return [
            'version_title' => '标题',
            'version_number' => '版本号',
            'version_log' => '日志内容',
            'author' => '上传人',
            'create_date' => '添加时间'
        ];
    }
}