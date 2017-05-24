<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/12/21
 * Time: 19:40
 */
namespace tests\controllers;

use yii\console\Controller;

class TestController extends Controller{
    public function actionIndex()
    {
        echo "this is console";exit;
    }
}