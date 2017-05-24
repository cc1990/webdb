<?php 
namespace backend\controllers;

use Yii;
use yii\web\Controller;

use yii\httpclient\Client;

class BaseController extends Controller
{
    
    /**
     * 链接数据库
     * @param  [String] $server_ip [数据库IP]
     * @param  [String] $db_name   [数据库名]
     * @return [type]            [description]
     */
    public function connectDb( $server_ip, $db_name ){
        //组合数据库配置
        $connect_config['dsn'] = "mysql:host=$server_ip;dbname=$db_name";
        $connect_config['username'] = Yii::$app->params['MARKET_USER'];
        $connect_config['password'] = Yii::$app->params['MARKET_PASSWD'];
        $connect_config['charset'] = Yii::$app->params['MARKET_CHARSET'];
        //数据库连接对象
        $executeConnection = new \yii\db\Connection((Object)$connect_config);
        return $executeConnection;
    }

    /**
     * GET请求
     * @param  [type] $url [description]
     * @return [type]      [description]
     */
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

    /**
     * POST请求
     * @param  [type] $url  [description]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
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