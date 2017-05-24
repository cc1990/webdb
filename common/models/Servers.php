<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "servers".
 *
 * @property string $server_id
 * @property string $ip
 * @property string $name
 * @property string $updated_date
 */
class Servers extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'servers';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['updated_date'], 'safe'],
            [['ip'], 'string', 'max' => 15],
            [['name'], 'string', 'max' => 50],
//            [['ip'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'server_id' => 'Server ID',
            'ip' => 'Ip',
            'mirror_ip' => '镜像Ip',
            'name' => 'Name',
            'updated_date' => 'Updated Date',
        ];
    }

    public function getServers() {
        $Servers = Servers::find()->where(['status' => 1])->orderBy('server_id')->all();
        if ($Servers) {
            return $Servers;
        }
        return null;
    }

    /**
     * 获取单个服务器指定属性
     * @param $server_id
     * @param $prop_name
     * @return array|null|\yii\db\ActiveRecord[]
     */
    public function getServer($server_id,$prop_name='') {
        $Server = Servers::find()->where(['status' => 1,'server_id' => $server_id])->select($prop_name)->asArray()->one();
//        echo $server_id;
//        var_dump($Server);exit;

        if ($Server) {
            return $Server;
        }
        return null;
    }


    /**
     * 通过IP获取单个服务器指定属性
     * @param $server_id
     * @param $prop_name
     * @return array|null|\yii\db\ActiveRecord[]
     */
    public function getServerByIp($ip,$prop_name='') {
        $Server = Servers::find()->where(['status' => 1,'ip' => $ip])->select($prop_name)->asArray()->one();
//        echo $server_id;
//        var_dump($Server);exit;

        if ($Server) {
            return $Server;
        }
        return null;
    }
}
