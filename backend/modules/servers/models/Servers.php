<?php

namespace backend\modules\servers\models;

use Yii;

/**
 * This is the model class for table "servers".
 *
 * @property string $server_id
 * @property string $ip
 * @property string $mirror_ip
 * @property string $name
 * @property string $updated_date
 */
class Servers extends \common\models\Servers
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
            [['ip'], 'unique'],
            [['environment'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'server_id' => '服务器ID',
            'mirror_ip' => '镜像服务器IP',
            'ip' => 'IP地址',
            'name' => '描述',
            'updated_date' => '修改时间',
            'environment' => '服务器环境',
        ];
    }
}
