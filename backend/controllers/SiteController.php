<?php
namespace backend\controllers;

use Yii;
use yii\data\Pagination;
use yii\web\Controller;
use common\models\LoginForm;
use common\models\LDAPService;

//用户模型
use common\models\Users;
use common\models\Servers;
use vendor\twl\tools\utils\Output;
use common\models\AuthItemServers;
use common\models\Projects;
use common\models\SystemLogs;
use johnitvn\rbacplus\models\AssignmentForm;
use yii\helpers\Url;
use backend\modules\projects\models\ProjectLog;

use yii\httpclient\Client;
use yii\widgets\LinkPager;


/**
 * Site controller
 */
class SiteController extends Controller
{
    public $enableCsrfValidation = false;

    public $servers;

    public function beforeAction($action){
        header("Content-type:text/html;charset=utf-8");

        if ($action->id != 'login' && $action->id != 'sso' && $action->id != 'logout') {
            $this->is_login();
        }

        //每日更新一次项目状态信息；
        $this->upgradeProjectStatus();

        if(!empty(Yii::$app->users->identity)){

            $auth = Yii::$app->authManager;
            $roles = $auth->getRolesByUser(Yii::$app->users->identity->id);

            //$permission = $this->module->id.'_'.Yii::$app->controller->id.'_'.$action->id;
            $permission = $this->module->id.'_'.Yii::$app->controller->id.'_'.$action->id;

            //获取公共操作权限
            $public_permission = Yii::$app->params['public_permission'];

            if (!in_array( $permission, $public_permission )) { //如果当前操作不在公共操作权限范围内的时候，则判断但前用户是否有访问权限
                if($this->module->id != 'tools_backend' && !\Yii::$app->users->can($permission) ){
                    $this->busi_error('当前账户无访问权限',2);
                }
            }

            //echo $this->module->id.'_'.Yii::$app->controller->id.'_'.$action->id;exit;
            //权限验证
            //if($this->module->id != 'tools_backend' && $permission != 'index_default_index'  && $permission != 'users_default_updatepassword'  && !\Yii::$app->users->can($permission)){
                //exit($permission.'无访问权限');
               // $this->busi_error('当前账户无访问权限',2);
            //}
            //获取服务器权限
            $this->servers = AuthItemServers::findServers($roles);
            //检测密码是否已修改
            //var_dump(Yii::$app->users->identity);exit;
            /*if($permission != 'users_default_updatepassword'  && Yii::$app->users->identity->is_change_passwd == 0){
                return $this->redirect(['/users/default/updatepassword']);
            }*/

        }
        return parent::beforeAction($action);
    }

//    /**
//     * @inheritdoc
//     */
//    public function behaviors()
//    {
//        return [
//            'access' => [
//                'class' => AccessControl::className(),
//                'rules' => [
//                    [
//                        'actions' => ['login', 'error','sso'],
//                        'allow' => true,
//                    ],
//                    [
//                        'actions' => ['logout', 'index', 'execute', 'export', 'assignment', 'audit','doaudit', 'auditlist', 'doauditlist'],
//                        'allow' => true,
//                        'roles' => ['@'],
//                    ],
//                ],
//            ],
//            'verbs' => [
//                'class' => VerbFilter::className(),
//                'actions' => [
//                    'logout' => ['post'],
//                ],
//            ],
//        ];
//    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * 检测manager中心是否登录
     * @return boolean         [description]
     */
    public function is_login(){
        
        @$AUTH_JSESSIONID_COOKIE = $_COOKIE['AUTH_JSESSIONID_COOKIE'];
        if( !isset( $AUTH_JSESSIONID_COOKIE ) || empty( $AUTH_JSESSIONID_COOKIE ) ){
            header('Location: /site/login');exit;
        }

        //获取用户登录信息
        $url = "http://manager.qccr.com/services/getPrincipal.json?token=" . $AUTH_JSESSIONID_COOKIE;
        $request = json_decode($this->http_get( $url ));
        //var_dump($request);exit;
        if( $request->success && $request->failed == false ){
            $data = $request->data;
            $username = $data->username;

            //如果当前系统没有登录
            if( \Yii::$app->users->isGuest ){
                $this->createSession( $username, "123456" );
            }else if( !\Yii::$app->users->isGuest && Yii::$app->users->identity->username != $username ){
                $this->createSession( $username, "123456" );
            }
        }else{
            header('Location: /site/login');exit;
        }
    }


    /**
     * 登录
     * @return string|\yii\web\Response
     */
    public function actionLogin()
    {
        if (!\Yii::$app->users->isGuest) {
            //return $this->goHome();
        }
        $post = Yii::$app->request->post();
        if ( $post ){
            $result = $this->getToken();
            if( isset($result['error']) ){
                Output::error($result['error']);
            }

            $username = $post['username'];
            $password = $post['password'];
            //$rememberMe = $post['rememberMe'];
            if( empty($username) || empty($password) ){
                Output::error("用户名或密码必须填写！");
            }

            $userinfo['username'] = $username;
            $userinfo['password'] = $password;
            $userinfo['token'] = $result['data'];

            $login_info = $this->getUserLogin( $userinfo );
            if( isset($login_info['error']) ){
                Output::error($login_info['error']);
            }

            $sessionId = $login_info['data']->sessionId;
            setcookie("AUTH_JSESSIONID_COOKIE", $sessionId,time()+3600*12,'/','qccr.com');
            
            $username = $login_info['data']->username;

            //获取用户对象，如不存在则自动创建
            $User = Users::find()->where(['username' => $username])->one();
            if(empty($User)){
                $User = new Users();
                $User->username = $username;
                $User->password = md5($password);
                $User->role_id = 0;
                $User->created_at = time();
            }
            $User->authority = 0;
            $User->updated_at =time();
            $User->is_change_passwd = 1;
            $User->chinesename =$login_info['data']->employeeName;

            $resultIn = $User->save();
            if($resultIn !== true){
                Output::error('用户信息保存失败');
            }

            $cookies = Yii::$app->response->cookies;

            // 在要发送的响应中添加一个新的cookie
            $model = new LoginForm();
            $model->username = $username;
            $model->password = $password;
            $model->sso = 1;
            if ($model->login()) {
                SystemLogs::create('login', '登录系统');

                $info['url'] = Url::to(['index/default']);
                Output::success('用户登录成功', $info);
            } else {
                Output::error('用户登录失败');
            }
        }else{
            return $this->render('login');
        }
        /*$model = new LoginForm();
        //var_dump(Yii::$app->request->post());exit;
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goHome();
        } else {
            return $this->render('login', [
                'model' => $model,
            ]);
        }*/
    }

    public function createSession( $username, $password){
        //自动登陆
        $model = new LoginForm();
        $model->username = $username;
        $model->password = $password;
        $model->sso = 1;
        if ($model->login()) {
            SystemLogs::create('login', '登录系统');

            return true;
        } else {
            return false;
        }
    }

    //用户登录接口
    public function getUserLogin( $data ){
        $url = "http://manager.qccr.com/services/login.json";
        $request = json_decode($this->http_post( $url, $data ));
        
        if( $request->failed || $request->success != true ){
            $result['error'] = $request->statusText;
        }else{
            $result['data'] = $request->data;
        }
        return $result;
    }

    /**
     * 获取登录令牌
     * @return [type] [description]
     */
    public function getToken(){
        $url = "http://manager.qccr.com/services/getToken.json";
        $request = json_decode($this->http_get( $url ));
        
        if( $request->failed || $request->success != true ){
            $result['error'] = "获取登录令牌失败！";
        }else{
            $result['data'] = $request->data;
        }
        return $result;
    }


    public function http_get( $url )
    {
        $client = new Client();
        $result = $client->createRequest()
                ->setMethod('get')
                ->setUrl($url)
                ->setHeaders(['content-type' => 'application/json'])
                ->send()->getContent();

        return $result;
    }

    public function http_post( $url, $data )
    {
        $client = new Client();
        $request = $client->createRequest()
                ->setMethod('post')
                ->setHeaders(['content-type' => 'application/json'])
                ->setUrl( $url );
        if ( is_array($data) ) {
            $request->setData( $data );
        } else {
            $request->setContent( $data );
        }
        $result = $request->send()->getContent();
        return $result;
    }

    /**
     * 登出
     * @return \yii\web\Response
     */
    public function actionLogout()
    {
        Yii::$app->users->logout();
        @$AUTH_JSESSIONID_COOKIE = $_COOKIE['AUTH_JSESSIONID_COOKIE'];
        if( !isset( $AUTH_JSESSIONID_COOKIE ) || empty( $AUTH_JSESSIONID_COOKIE ) ){
            $this->redirect(['/site/login']);
            return false;
        }
        $url = "http://manager.qccr.com/services/logout.json?token=" . $AUTH_JSESSIONID_COOKIE;
        $request = json_decode($this->http_get( $url ));
        if( $request -> success && $request -> failed == false ){
            Output::success("退出成功");
        }else{
            Output::error($request->statusText);
        }
    }



    /**
     * 运维平台快速跳转
     */
    public function actionSso(){

        //获取签名数据
        $params['username'] = isset($_GET['username'])?trim($_GET['username']):null;
        $params['timestamp'] = isset($_GET['timestamp'])?trim($_GET['timestamp']):null;
        $signature = isset($_GET['signature'])?$_GET['signature']:null;
        //获取其他参数
        $chinesename = isset($_GET['chinesename'])?$_GET['chinesename']:'';
        //URL转码，防止中文变成乱码
        $chinesename = urldecode( $chinesename );
        $host = isset($_GET['host'])?$_GET['host']:null;
        $redirecturl = isset($_GET['redirecturl'])?$_GET['redirecturl']:null;
        $projectid = isset($_GET['projectid'])?$_GET['projectid']:null;
        $projectname = isset($_GET['projectname'])?$_GET['projectname']:null;

        //判断参数是否完整
        if(empty($params['username'])
            || empty($params['timestamp'])
            || empty($host)
            || empty($redirecturl)
            || empty($signature)
            || empty($projectid)
            || empty($projectname)
        ) {
            //exit('参数有错');
            return $this->busi_error('参数有错');
        }

        //判断签名是否过期
        if($params['timestamp'] + 60 < time()){
            return $this->busi_error('签名已过期');
           //exit('签名已过期');
        }

        //判断签名是否合法
        if($signature != $this->_ssoSign($params)) {
            return $this->busi_error('签名验证失败');
        }
        //进行默认选择缓存
        if(!empty($host))
            \Yii::$app->session->set('default_host', $host);

        if(!empty($projectid))
            \Yii::$app->session->set('default_pro_id', $projectid);

        //获取项目对象，如不存在则自动创建
        $Project = Projects::findOne(['pro_id' => $projectid]);
        if(empty($Project)){
            $Project = new Projects();
            $Project->pro_id = $projectid;
        }
        $Project->name = $projectname;
        $resultIn = $Project->save();
        if($resultIn !== true){
            //exit('项目信息保存失败');
            return $this->busi_error('项目信息保存失败');
        }

        //获取server对象，如不存在则自动创建
        $Server = new Servers();
        $serverInfo = $Server->getServerByIp($host);

        if(empty($serverInfo)){
            $Server->ip = $host;
            $Server->status = 1;
            $Server->save();
            $serverInfo = $Server->getServerByIp($host);
        }
        \Yii::$app->session->set('default_host_id', $serverInfo['server_id']);

        //获取用户对象，如不存在则自动创建
        $User = Users::find()->where(['username' => $params['username']])->one();
        if(empty($User)){
            $User = new Users();
            $User->username = $params['username'];
            $User->password = md5('123456');
            $User->role_id = 0;
            $User->created_at = time();
        }
        $User->authority = 0;
        $User->updated_at =time();
        $User->chinesename =$chinesename;

        $resultIn = $User->save();
        if($resultIn !== true){
            //exit('用户信息保存失败');
            return $this->busi_error('用户信息保存失败');
        }
        //自动登陆
        $model = new LoginForm();
        $model->username = $params['username'];
        $model->password = '888';
        $model->sso = 1;
        if ($model->login()) {
            if(!empty($redirecturl))
                return $this->redirect($redirecturl);
            else
                return $this->goHome();
        } else {
            return $this->render('login', [
                'model' => $model,
            ]);
        }

    }

    /**
     * 批量赋予权限
     */
//    public function actionAuthin(){
//        ini_set('max_execution_time', '0');
//        //所有用户工号和数据库对应关系
//        $user_db = array(
//            'basic_data' => '00100,00219,00573,00602,01047,01812,01842,01987,02075,02104,00102,00179,00343,00378,00528,01279,01299,02037,02180,00405,01236,01806,01827,02142,02189',
//            'qccr' => '00127,00449,00648,01112,01120,01656,01726,01741,01909,02074,02129,02132,02155,02172,02187,02225,00205,00678,00856,01014,01122,01204,01742,02004,02121,02145,00022,01174,01175,01237,01567,01766,01854,01855,01857,01933,00208,00359,00382,00392,00457,00604,00605,00611,00621,00758,00789,00829,00956,00971,01048,01055,01535,01614,01651,01653,01708,01709,01710,01723,01729,01820,01834,01845,01860,01877,01959,01961,01964,01989,01992,02008,02034,02035,02050,02073,02101,02103,02115,02117,02119,02120,02124,02125,02127,02141,02148,02149,02174,02177,02178,02181,02182,02183,02186,02188,02193,02207,02221,02222',
//            'market' => '00648,00655,01090,01173,01773,01843,01963,02123,02128,02179',
//            'crm' => '00629,00649,01031,01213,01585,01951,02038,02116,02184',
//            'oms' => '00339,00865,01033,01091,01245,02007,02126',
//            'insurance' => '01652,01713,01715,01730,01754,01762,01792,01886',
//            'news' => '01652,01713,01715,01730,01754,01762,01792,01886',
//            'item' => '00258,00259,00329,00480,01059,01121,01172,01179,02208,02223',
//            'auth' => '01121',
//            'ordercenter' => '00205,00678,00856,01014,01122,01204,01742,02004,02121,02145',
//            'kefu' => '00174,00188,00790,01962,02071,02175',
//            'appdl' => '00174,00188,00790,01962,02071,02175',
//            'membercenter' => '01029,01791,02070',
//            'growth' => '01029,01791,02070',
//            'paycenter' => '01225,02122',
//            '管理员' => '01782,02122,00186,00377,00518,00752,01475,01565,01659,01752,01781',
//        );
//
//        //获取用户对象，如不存在则自动创建
//        $Users = Users::find()->select('id,username')->where('id > 121')->asArray()->all();
//        $a = 1;
//
//        $time = time();
//        foreach($Users as $val){
//            unset($array);
//            $formModel = new AssignmentForm($val['id']);
//            $array['AssignmentForm']['userId'] = $val['id'];
//            foreach($user_db as $key=>$val2){
//                if(strpos($val2,$val['username']) !== false){
//                    echo $a.':'.$key.$val['id'].'<br>';
//                    $array['AssignmentForm']['roles'][] = $key;
//                    $a++;
//                }
//                $formModel->load($array);
//                $formModel->save();
//            }
//        }
//
//    }

    /**
     * 批量赋予权限
     */
    public function actionUpdateDepartments(){
        $LDAP = new LDAPService();

        $users = Users::find()->where(['>', 'username', 0])->andWhere(['=', 'departments' , ''])->asArray()->select(array('id','username'))->all();
        $empNos = '';
        $new_users = array();
        foreach($users as $val){
            if($val['username'] > 0){
                $empNos .= ','.$val['username'];
                $new_users[$val['username']] = $val['id'];
            }

        }
        if(!empty($empNos))
           $datas = $LDAP->getDepts($empNos);
        if(!empty($datas)){
            $echo  = '';
            foreach($datas as $val){
                if(!empty($val['deptName']) && $new_users[$val['employeeNo']]){
                    $user = Users::findOne($new_users[$val['employeeNo']]);
                    $user->departments = $val['deptName'];
                    $echo .= $val['employeeNo'].' :更新部门信息: ';
//                    var_dump($user);exit;
                    $result = $user->save();
                    if(true === $result){
                        $echo .= $val['deptName'].' 成功！<br/>';
                    }else{
                        $echo .= $val['deptName'].' 失败！<br/>';
                    }
                }
            }
        }
        if(empty($echo))
            echo '没有员工部门信息更新';
        else
            echo $echo;
    }


    /**
     * 错误页面
     * @return string
     */
    public function actionBError()
    {
        $action['name'] = '业务错误';
        $action['message'] = $_REQUEST['message'];
        return $this->render('@app/views/site/b-error',$action);
    }

    protected function busi_error($message,$type = 1)
    {
        if ( Yii::$app->request->isAjax ) {
            Output::error($message);
        } else {
            if($type == 1){
                $action['name'] = '业务错误';
                $action['message'] = $message;
                return $this->render('@app/views/site/b-error',$action);
            }else{
                 $this->redirect(array('/site/b-error','message'=>$message));
            }
        }
    }


    /**
     * 运维平台跳转签名验证
     * @param $params 签名数据
     * @return string
     */
    private function _ssoSign($params)
    {
        asort($params);
        $source = '';
        foreach ($params as $key => $value) {
            $source .= $value;
        }
        return md5($source . Yii::$app->params['MD5_KEY']);
    }

    /**
     * 检测是否有指定server和db的操作权限
     * @param $server_id
     * @param $db_name
     */
    protected function check_server_permission($server_ip,$db_name,$tb_name = null)
    {
        if(!isset($this->servers['privilege'][$server_ip][$db_name])){
            Output::error("没有数据库{$db_name}操作权限，请联系管理员");
        }elseif(!empty($tb_name)){
            if(!isset($this->servers['privilege'][$server_ip][$db_name]) || !in_array($tb_name,$this->servers['privilege'][$server_ip][$db_name])){
                Output::error("没有数据库{$db_name}下{$tb_name}表的操作权限,请联系管理员");
            }
        }
        return true;
    }

    /**
     * 首页
     * @return [type] [description]
     */
    public function goHome()
    {
        $this->redirect(array('/index/default/index'));
    }

    /**
     * 每天更新一次项目的状态
     * @return [type] [description]
     */
    public function upgradeProjectStatus()
    {
        $nowday = date("Y-m-d");
        $sql_select = "select datetime";
        $projectLog = new ProjectLog();
        @$project_log = $projectLog::find()->where(['update_date' => $nowday])->one();
        if( !empty($project_log) ){//如果已有当天的日志
            return ;
        }

        //获取未上线的项目列表
        $url = "http://cryw.qccr.com/releasemanage/ajax/get_project_list/";
        $request = $this->get( $url );
        $contents = json_decode( $request );
        if( empty( $result = $contents->result ) ) //如果返回的结果为空
            return ;

        foreach ($result as $key => $value) {
            $projectName_array[] = $value->project_name;
        }

        $projectName_array[] = 'pre_emergency_only';
        
        Projects::updateAll(['status'=> 1], ['in', 'name', $projectName_array]);//将未上线的项目状态改成1
        $update_num = Projects::updateAll(['status'=> 0], ['not in', 'name', $projectName_array]);//将不在未上线的项目列表中并且项目名称不为pre_emergency_only的项目状态改成0
        
        $projects = new Projects();
        //获取已有的项目列表
        $project_list = Projects::find()->select(['pro_id', 'name'])->asArray()->all();
        foreach ($project_list as $key => $value) {
            $project_list_array[] = $value['pro_id'];
        }
        
        //将新的项目添加到项目列表中
        $i = 0;
        foreach ($result as $key => $value) {
            $pro_id = $value->id;
            if ( !in_array( $pro_id, $project_list_array ) ) {
                $projects->pro_id = $pro_id;
                $projects->title = $value->desc;
                $projects->name = $value->project_name;
                $projects->updated_date = date("Y-m-d H:i:s");
                $projects->status = 1;
                @$projects->insert();
                $i++;
            }else{
                $title = $value->desc;
                if( !empty( $title ) ){
                    $model = Projects::findOne( $pro_id );
                    $model->title = $title;
                    $model->save();
                }
            }

            $status = $value->status;
        }
        
        //添加项目更新日志
        $projectLog->update_date = $nowday;
        $projectLog->add_num = $i;
        $projectLog->update_num = $update_num;
        $projectLog->save();
    }

    public function get( $url )
    {
        $client = new Client();
        $result = $client->createRequest()
                ->setMethod('get')
                ->setUrl($url)
                ->setHeaders(['content-type' => 'application/json'])
                ->send()->getContent();

        return $result;
    }

    /**
     * @description 分页类
     * @param
     * @return pageHtml 分页html
     */
    protected function _getPageHtml($totalCount,$page,$pageSize,$pageParam = 'page')
    {
        $pageClass = new Pagination(['totalCount'=>$totalCount,'pageSize' => $pageSize]);
        $pageClass->pageParam = $pageParam;

        $firstPageLabel = $page > 1?false:'首页';
        $prevPageLabel = $firstPageLabel?false:'上一页';
        $lastPageLabel = (int)$totalCount/$pageSize > (int)$page?false:'尾页';
        $nextPageLabel = $lastPageLabel?false:'下一页';
        $pageHtml = LinkPager::widget([
            'pagination' => $pageClass,
            'firstPageLabel' =>$firstPageLabel,
            'nextPageLabel' => $nextPageLabel,
            'prevPageLabel' => $prevPageLabel,
            'lastPageLabel' => $lastPageLabel
        ]);
        return $pageHtml;
    }
}
