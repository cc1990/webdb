<?php

namespace backend\modules\operat\controllers;

use yii;
use backend\controllers\SiteController;
use backend\modules\operat\Module\Select;

class DefaultController extends SiteController
{
    public function actionIndex()
    {
        echo 'aaa';exit;
        return $this->render('index');
    }
}
