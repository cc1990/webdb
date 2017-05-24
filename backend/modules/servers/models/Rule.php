<?php

namespace backend\modules\servers\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "servers".
 *
 * @property string $server_id
 * @property string $ip
 * @property string $mirror_ip
 * @property string $name
 * @property string $updated_date
 */
class Rule extends ActiveRecord
{
    public $primaryKey = 'id';
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'servers_user_rule';
    }
}
