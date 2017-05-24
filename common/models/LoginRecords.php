<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "login_records".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $ip
 * @property integer $type
 * @property integer $login_time
 */
class LoginRecords extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'login_records';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'type', 'login_time'], 'integer'],
            [['ip'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'ip' => 'Ip',
            'type' => 'Type',
            'login_time' => 'Login Time',
        ];
    }

    /**
     * @inheritdoc
     */
    public static function addRecord($user_id,$type=0) {
        $LoginRecord = new LoginRecords();
        $LoginRecord->user_id = $user_id;
        $LoginRecord->ip = $_SERVER["REMOTE_ADDR"];
        $LoginRecord->type = $type;
        $LoginRecord->login_time = time();
        $result = $LoginRecord->save();
//        var_dump($LoginRecord->errors);exit;
//        var_dump($result);exit;
    }
}
