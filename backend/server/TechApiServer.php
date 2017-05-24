<?php 
namespace backend\server;

use yii\httpclient\Client;

class TechApiServer
{
    const TECHAPI = "http://techapib.qccr.com/upstream/auth"; 
    const APP_NAME = 'dbtools';
    const APP_MD5 = "fbb16f020c2ef2d3d846b061a92bcf89";  //应用md5值

    /**
     * 请求techapi上的接口
     * @param  [type] $tech_api_url [description]
     * @param  [type] $environment  [description]
     * @param  [type] $data         [description]
     * @return [type]               [description]
     */
    public function requestTechApi( $tech_api_url, $environment, $data )
    {
        $techapi_timestamp = time();
        $techapi_signature = md5(self::APP_MD5 . $techapi_timestamp); 
        $url = $tech_api_url.'?techapi_name='.self::APP_NAME.'&techapi_env='.$environment.'&techapi_timestamp='.$techapi_timestamp.'&techapi_signature='.$techapi_signature;

        if( is_array( $data ) ){
            $request = $this->http_post($url, $data);
        }else{
            $url .= "&".$data;
            $request = $this->http_get( $url );
        }
        return $reqeust;
    }

    /**
     * 校验请求来源合法性
     * @param  [type] $techapi_signature [签名]
     * @param  [type] $techapi_timestamp [时间戳]
     * @return [boole]                    [是否合法]
     */
    public function checkTechapiSignature( $techapi_signature, $techapi_timestamp)
    {
        $parameter = self::APP_MD5 . $techapi_timestamp;        // 根据producer md5和时间戳拼接成字符串
        $localSignature = md5($parameter);       // 根据字符串计算得md5值

        if( $techapi_signature == $localSignature ){
            $url = self::TECHAPI . "?signature=" . $techapi_signature;
            $request = $this->http_get( $url );
            if( $request == ''){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
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
}