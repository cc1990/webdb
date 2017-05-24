<?php
namespace common\helpers;

use yii\httpclient\Client;

class CurlExtend extends Client{
    
    
    public function fetch($url){
        try{
            return $this->createRequest()->setMethod('get')->setUrl($url)->send()->getContent();
        }catch (\Exception $e){
            return array('code'=>-100,'msg'=>'连接失败');
        }
    }
    
    public function submit($url,$param=''){
        try{
            $request = $this->createRequest()->setMethod('post')->setUrl($url);
            if(is_array($param)){
                $re = $request->setData($param);
            }else{
                $re = $request->setContent($param);
            }
            return $re->send()->getContent();
        }catch (\Exception $e){
            return array('code'=>-100,'msg'=>'连接失败');
        }
    }
    
}