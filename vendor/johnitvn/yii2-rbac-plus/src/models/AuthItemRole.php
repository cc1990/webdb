<?php

namespace johnitvn\rbacplus\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @author John Martin <john.itvn@gmail.com>
 * @since 1.0.0
 */
class AuthItemRole extends ActiveRecord {
    public $name;
    public $type;
    public $description;
    public $rule_name;
    public $created_at;
    public $updated_at;

    public static function tableName()
    {
        return "backup_logs";
    }

    /**
     * Description 保存数据
     */
    public function saveData($post){
        $this->isNewRecord = true;
        $this->name = $post['name'];
        $this->type = 1;
        $this->description = $post['description'];
        $this->rule_name = empty($post['rulename']) ? null : $post['rulename'];
        $this->created_at = $this->updated_at = time();
        $this->insert();
    }

}
