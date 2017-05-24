<?php 
namespace backend\modules\operat\models;

use Yii;
use yii\base\Model;

class Select extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'operat_select_config';
    }

    public function rules()
    {
        return [
            [['dev', 'dev_trunk', 'test', 'test_trunk', 'pro', 'pre', 'white_list_num'], 'required'],
            [['dev', 'dev_trunk', 'test', 'test_trunk', 'pro', 'pre'], 'number'],
            [['white_list'], 'string'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'dev'=> '开发',
            'test'=> '测试',
            'dev_trunk'=> '研发主干',
            'test_trunk'=> '测试主干',
            'pro'=> '线上',
            'pre'=> '预发布',
        ];
    }
}
