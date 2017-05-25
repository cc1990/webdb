<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2017/1/12
 * Time: 15:27
 */

namespace backend\server;

use Yii;
use backend\modules\backup\models\Logs;
use yii\httpclient\Client;


/**
 * Description ssh2相关操作
 * Author   Cc
 * Time 2017-1-12
 */
class LogServer
{
    private $sshServer;
    private $serverIp;

    public function __construct(SshServer $sshServer)
    {
        $this->sshServer = $sshServer;
        $this->serverIp = $sshServer->host;
    }

    /**
     * Description 执行解析过程
    */
    public function run()
    {
        $files = $this->getNeedFiles();
        $this->parseLogFile($files);
    }

    /**
     * Description 解析日志文件
    */
    public function parseLogFile($files)
    {
        foreach($files as $file){
            $type = $this->getBackupType($file);
            if(empty($type))    continue;
            if(method_exists($this,$type."Parse")){
                $data = call_user_func([$this,$type."Parse"],$file);
                $this->save($data);
            }else{
                continue;
            }
        }
    }

    /**
     * Description 全量备份日志解析
    */
    public function fullParse($file){
        $content = $this->sshServer->getLogContent($this->sshServer->path.$file);

        $result = [];
        $keyword = Yii::$app->params['backup']['keyword']['full'];
        foreach(explode("\n",$content) as $line){
            if(empty($line))   continue;
            foreach($keyword as $key=>$search){
                if(strpos($line,$search) !== false) {
                    $result[$key][] = $line;
                }
            }
        }
        return $this->getInfo($result,$file,'full');
    }

    /**
     * Description 增量备份日志解析
    */
    public function incrParse($file){
        $content = $this->sshServer->getLogContent($this->sshServer->path.$file);

        $result = [];
        $keyword = Yii::$app->params['backup']['keyword']['incr'];
        foreach(explode("\n",$content) as $line){
            if(empty($line))   continue;
            foreach($keyword as $key=>$search){
                if(strpos($line,$search) !== false) {
                    $result[$key][] = $line;
                }
            }
        }
        return $this->getInfo($result,$file,'incr');
    }

    /**
     * Description binlog日志解析
    */
    public function binlogParse($file){
        $content = $this->sshServer->getLogContent($this->sshServer->path.$file);

        $result = [];
        $keyword = Yii::$app->params['backup']['keyword']['binlog'];
        foreach(explode("\n",$content) as $line){
            if(empty($line))   continue;
            foreach($keyword as $key=>$search){
                if(strpos($line,$search) !== false) {
                    $result[$key][] = $line;
                }
            }
        }
        return $this->getInfo($result,$file,'binlog');
    }

    /**
     * Description dump日志解析
    */
    public function dumpParse($file){
        $content = $this->sshServer->getLogContent($this->sshServer->path.$file);

        $result = [];
        $keyword = Yii::$app->params['backup']['keyword']['dump'];
        foreach(explode("\n",$content) as $line){
            if(empty($line))   continue;
            foreach($keyword as $key=>$search){
                if(strpos($line,$search) !== false) {
                    $result[$key][] = $line;
                }
            }
        }
        return $this->getInfo($result,$file,'dump');
    }

    /**
     * Description 获取相应数据
    */
    public function getInfo($result,$file,$type)
    {
        $data['status'] = 1;
        $data['server_ip'] = $this->serverIp;
        $data['script_path'] = $this->sshServer->path;
        $data['script_file'] = $file;
        $data['type'] = $type;
        if(!empty($result['start'])){   //获取开始时间
            $data['start_time'] = strtotime(substr($result['start'][0],1,19));
        }
        if(!empty($result['size'])){    //获取数据大小
            preg_match("/after compress the size is (.*)./i",$result['size'][0],$sizeMatch);
            $data['filesize'] = $sizeMatch[1];
        }
        if(!empty($result['archive_server'])){
            $archiveHost = [];
            foreach($result['archive_server'] as $item){
                preg_match('/remote host ([0-9.]*):/i',$item,$serverMatch);
                $archiveHost[] = $serverMatch[1];
            }
            $data['archive_ip'] = implode(",",array_unique($archiveHost));
        }
        if(!empty($result['success'])){
            $data['end_time'] = strtotime(substr($result['success'][0],8,19));
            $data['status'] = 2;
        }
        if(!empty($result['failed'])){
            preg_match('/\[(.*)\]/i',$result['failed'][0],$failedMatch);
            $data['end_time'] = $failedMatch[1];
            $data['status'] = 3;
        }
        return $data;
    }


    /**
     * Description 数据入库
    */
    public function save($data){
        $logsModel = new Logs();
        $logInfo = $logsModel->findOne(['server_ip'=>$data['server_ip'],'script_path'=>$data['script_path'],'script_file'=>$data['script_file']]);
        if(!empty($logInfo))    $logsModel = $logsModel->findOne($logInfo['id']);
        foreach ($data as $key=>$value){
            $logsModel->$key = $value;
        }
        $mount = isset(Yii::$app->params['backup']['getSpaceInterface']['disk'][$data['server_ip']]) ? Yii::$app->params['backup']['getSpaceInterface']['disk'][$data['server_ip']] : ['data','data'];
        if(!empty($data['server_ip'])){
                $endpoint_source = explode(".", $data['server_ip']);
                $param[0]['endpoint'] = $endpoint_source[2] . "_" . $endpoint_source[3];
                $param[0]['counter'] = Yii::$app->params['backup']['getSpaceInterface']['params']['counter.free'].$mount[0];
                $backupRemainSpace = $this->formatFileSize($this->http_post(Yii::$app->params['backup']['getSpaceInterface']['url'], json_encode($param)));
                $logsModel->backup_remain_space = $backupRemainSpace;
        }
        if(!empty($data['archive_ip'])){
            $archive_ip_arr = explode(",",$data['archive_ip']);
            $archiveRemainSpace = $archiveAllSpace = [];
            foreach($archive_ip_arr as $value){
                $endpoint_source = explode(".",$value);
                $param[0]['endpoint'] = $endpoint_source[2]."_".$endpoint_source[3];
                $param[0]['counter'] = Yii::$app->params['backup']['getSpaceInterface']['params']['counter.free'].$mount[1];
                $archiveRemainSpace[] = $this->formatFileSize($this->http_post(Yii::$app->params['backup']['getSpaceInterface']['url'],json_encode($param)));
                $param[0]['counter'] = Yii::$app->params['backup']['getSpaceInterface']['params']['counter.total'].$mount[1];
                $archiveAllSpace[] = $this->formatFileSize($this->http_post(Yii::$app->params['backup']['getSpaceInterface']['url'],json_encode($param)));
            }
            $logsModel->archive_remain_space = implode(",",$archiveRemainSpace);
            $logsModel->archive_all_space = implode(",",$archiveAllSpace);
        }
        $logsModel->save();
    }

    /**
     * Description 判断是什么类型的备份日志
    */
    public function getBackupType($file)
    {
        $typeLog = explode("_",$file);
        return $typeLog[count($typeLog) - 3];
    }

    /**
     * Description 规避已备份完成，数据库已存在的文件
     * Return 需要解析的文件
    */
    public function getNeedFiles()
    {
        $logsModel = new Logs();
        $where['server_ip'] = $this->serverIp;
        $where['script_path'] = $this->sshServer->path;
        $where['status'] = [2,3];
        $logsList = $logsModel->find()->where($where)->asArray()->all();
        $filterFiles = [];
        foreach ($logsList as $item) {
            $filterFiles[] = $item['script_file'];
        }
        $files = $this->sshServer->getFiles();
        foreach($files as $key=>$value){
            if(in_array($value,$filterFiles))   unset($files[$key]);
        }
        return $files;
    }

    /**
     * Description post接口获取
    */
    public function http_post( $url, $data )
    {
        $client = new Client();
        $request = $client->createRequest()
            ->setMethod('post')
            ->setHeaders(['content-type' => 'application/json'])
            ->setUrl( $url );
        if ( is_array($data) ) {
            $request->setData( json_encode($data) );
        } else {
            $request->setContent( $data );
        }
        $result = $request->send()->getContent();
        return json_decode($result,true);
    }

    /**
     * Description 数据大小转成可读大小
    */
    function formatFileSize($space)
    {
        $fileSize = isset($space[0]['value']['value']) ? $space[0]['value']['value'] : 0;
        $unit = array(' Bytes', ' KB', ' MB', ' GB', ' TB', ' PB', ' EB', ' ZB', ' YB');
        $i = 0;
        $inv = 1 / 1024;

        while($fileSize >= 1024 && $i < 8) {
            $fileSize *= $inv;
            ++$i;
        }
        $fileSizeTmp = sprintf("%.2f", $fileSize);

        return ($fileSizeTmp - (int)$fileSizeTmp ? $fileSizeTmp : $fileSize) . $unit[$i];
    }
}