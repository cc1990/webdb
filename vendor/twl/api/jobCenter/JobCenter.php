<?php 
namespace vendor\twl\api\jobCenter;

use yii\httpclient\Client;

/**
* author Cc
*/
class JobCenter
{
    
    private $config; //配置信息

    private $path; //文件目录

    public function __construct()
    {
        $this->path = dirname(__FILE__);
        $this->loadConfig();
    }

    /**
     * 发送文本类型的钉钉消息
     * @param  [type] $touser  [员工工号（5位，不足的话在前面添加0），多个员工工号用 '|' 分隔]
     * @param  [type] $todept  [部门ID（ID请咨询自动化平台组），多个部门ID用 '|' 分隔]
     * @param  [type] $content [内容]
     * @return [type]          [description]
     */
    public function sendTextDingTalk( $touser, $todept, $content )
    {
        $url = $this->config['dingtalk'];

        //员工工号和部门ID至少填写一个
        if( empty( $touser ) && empty( $todept ) ){
            $result['error'] = $this->config['userOrDept_error'];
        }
        if( empty( trim( $content ) ) ){
            $result['error'] = $this->config['content_error'];
        }

        $data['timestamp'] = time();
        $data['signature'] = $this->signature();
        $data['touser'] = $touser;
        $data['todept'] = $todept;
        $data['msgtype'] = 'text';
        $data['content'] = $content;

        $result = $this->http_request( $url, $data );
        return $result;
    }

    /**
     * HTTP请求
     * @param  [type] $url  [description]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    private function http_request( $url, $data )
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
     * 计算签名
     * @return [type] [description]
     */
    private function signature()
    {
        $md5_key = $this->config['md5_key'];
        $timestamp = time();
        return md5($timestamp . $md5_key);
    }

    /**
     * 加载配置文件
     * @return [type] [description]
     */
    private function loadConfig()
    {
        $config_file = $this->path . "/config.php";
        if( !file_exists( $config_file ) ){
            return false;
        }
        $this->config = require_once( $config_file );
    }
}