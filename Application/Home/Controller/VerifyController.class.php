<?php

namespace Home\Controller;

use Think\Controller;

class VerifyController extends BaseController
{
	//重载方法
	public function _initialize()
	{}

	/**
	 * 进行登录验证
	 */
	public function verifyLogin()
	{
		$stuNum = I('post.stuNum');
		$idNum = I('post.idNum');
		if (empty($idNum) || empty($stuNum)) {
			$this->returnJson(801);
			exit;
		}
		if ($idNum != is_numeric($idNum)) {
			if (false === $idNum = $this->decrypt($idNum)) {
				$this->returnJson(404);
				exit;
			}
		}
		$data = compact('stuNum', 'idNum');
		$output = $this->curl_init($this->apiUrl, $data);
		echo $output;
	}
}