<?php 
namespace backend\modules\index\models;

use Yii;
use yii\db\ActiveRecord;

/**
* 
*/
class ServerDbs extends ActiveRecord
{
    public static function tableName()
    {
        return 'auth_item_servers_dbs';
    }
}