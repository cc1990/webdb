<?php
namespace backend\server;

use Yii;


/**
 * Description ssh2相关操作
 * Author   Cc
 * Time 2017-1-12
 */
class SshServer
{
    private $host;
    private $port = 22;
    private $username;
    private $password;
    private $log_content;
    private $conn;
    private $path;

    /**
     * Description 构造函数，初始化数据,创建连接
    */
    function __construct($host = null,$username = null,$password = null,$port = "22")
    {
        $this->path = Yii::$app->params['backup']['log_path'];
        $this->host = $host ? $host : Yii::$app->params["backup"]["host"];
        $this->username = $username ? $username : Yii::$app->params["backup"]["username"];
        $this->password = $password ? $password : Yii::$app->params["backup"]["password"];
        $this->port = $port;
        if(!function_exists("ssh2_connect"))   throw new \Exception("ssh2扩展不存在,请安装扩展");
        ini_set("default_socket_timeout", 1);
        if(!($this->conn = ssh2_connect($host,$port))) throw new \Exception("服务器{$this->host}连接失败！");
        ssh2_auth_password($this->conn,$this->username,$this->password);
    }

    /**
     * Description 获取文件内容
     * Param $file str 文件路径
     * Return 返回文件内容
    */
    function getLogContent($file){
        $sftp = ssh2_sftp($this->conn);
        $this->log_content = file_get_contents("ssh2.sftp://{$sftp}{$file}");
        return $this->log_content;
    }

    /**
     * Description 获取目录下的所有文件
    */
    function getFiles($path = false)
    {
        $path = $path ? $path : $this->path;
        $stream = ssh2_exec($this->conn,"ls {$path};");
        stream_set_blocking($stream, true);
        $filesStr = stream_get_contents($stream);
        $filesArr = array_filter(explode("\n",$filesStr));
        fclose($stream);
        return $filesArr;
    }

    /**
     * Description 获取服务器私有信息
    */
    function __get($name)
    {
        return $this->$name;
    }
}