<?php
namespace backend\controllers;

use Yii;
use yii\web\Controller;
use common\models\LoginForm;
use common\models\LDAPService;

//用户模型
use common\models\Users;
use common\models\Servers;
use vendor\twl\tools\utils\Output;
use common\models\AuthItemServers;
use common\models\Projects;
use johnitvn\rbacplus\models\AssignmentForm;
use yii\helpers\Url;
use backend\modules\projects\models\ProjectLog;

use yii\httpclient\Client;


/**
 * Site controller
 */
class MirrorController extends Controller
{
    public function actionIndex()
    {
        return $this->render("index");
    }

    public function actionBasic()
    {
        return $this->renderAjax("basic");
    }
}
