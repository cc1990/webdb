<?php

namespace backend\modules\projects\models;

use Yii;

/**
 * This is the model class for table "projects".
 *
 * @property string $pro_id
 * @property string $name
 * @property string $updated_date
 * @property integer $status
 * @property string $remarks
 */
class Projects extends \common\models\Projects
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'projects';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['updated_date'], 'safe'],
            [['status'], 'integer'],
            [['name'], 'string', 'max' => 50],
            [['remarks'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'pro_id' => 'Pro ID',
            'name' => 'Name',
            'updated_date' => 'Updated Date',
            'status' => 'Status',
            'remarks' => 'Remarks',
        ];
    }
}
