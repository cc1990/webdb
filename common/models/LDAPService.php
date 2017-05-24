<?php

namespace common\models;
use common\models\CurlService;

class LDAPService {
	protected $url;
	protected $CurlService;

	public function __construct() {
		$this->url = 'http://manager.qccr.com/';
		if(empty($this->CurlService))
			$this->CurlService = new CurlService();
	}

	/**
	 * 登陆接口
	 * @param $username
	 * @param $password
	 * @return bool|mixed
	 */
	public function login($username,$password) {
		//获取token
		$result = $this->getToken();
		if($result['result'] === false)
			return array('result'=>false,'message'=>'token获取失败');
		$postDate['token'] = $result['data'];
		$postDate['username'] = $username;
		$postDate['password'] = $password;
		$return = $this->CurlService->postData($this->url.'services/login.json',$postDate);
		$return = json_decode($return,true);
		if($return['success'] === false){
			return array('result'=>false,'message'=>$return['statusText']);
		}else{
			return array('result'=>true,'data'=>$return['data']);
		}
	}

	public function getDepts($empNos){
		$postDate['empNos'] = $empNos;
		$return = $this->CurlService->postData($this->url.'services/getDeptsByEmpNos.json',$postDate);
		$return = json_decode($return,true);
		return $return['data'];
	}

	/**
	 * 获取token
	 * @return bool
	 */
	private function getToken(){
		$return = $this->CurlService->getData($this->url.'services/getToken.json');
		$return = json_decode($return,true);
		if($return['success'] == 1){
			return array('result'=>true,'data'=>$return['data']);;
		}else{
			return array('result'=>false,'message'=>'token获取失败');
		}
	}


	private function getPrincipal($token){
		$postDate['token'] = $token;
		$return = $this->CurlService->postData($this->url.'services/getPrincipal.json',$postDate);
		$return = json_decode($return,true);
		return $return['data'];
	}


}
?>