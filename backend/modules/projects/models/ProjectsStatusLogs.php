<?php 
namespace backend\modules\projects\models;

use backend\modules\projects\models\Projects;

class ProjectsStatusLogs extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'projects_status_logs';
    }

    public function rules()
    {
        return [
            [['pro_id', 'environment', 'update_time'], 'safe']
        ];
    }

    public function getProjects()
    {
        return $this->hasOne( Projects::className(), ['pro_id' => 'pro_id'] )->select(['name']);
    }
}