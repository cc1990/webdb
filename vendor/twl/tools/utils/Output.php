<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace vendor\twl\tools\utils;

/**
 * @author 庄路
 */

class Output extends \Exception{
    
    /**
     * 成功
     */
    const SUCCESS       = 1;

    /**
     * 失败
     */
    const ERROR         = 0;


    public static function error($msg = null,$type = 1)
    {
        if($type == 1){
            exit(json_encode(array('code' => self::ERROR ,'msg' => $msg)));
        }elseif($type == 2){
            if (is_array($msg)) {
                $str = '';
                foreach ($msg as $k => $v) {
                    $str .= (is_array($v)) ? $v[0] : $v;
                    $str .= ' ';
                }
            } else {
                $str = $msg;
                \Yii::$app->session->setFlash('error', $str);
            }
        }else{
            exit($msg);
        }
    }

    public static function success($msg = null,$content='')
    {
        echo json_encode(array('code' => self::SUCCESS ,'msg' => $msg,'content' => $content));
    }
}
