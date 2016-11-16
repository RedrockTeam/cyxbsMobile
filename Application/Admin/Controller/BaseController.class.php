<?php

namespace Admin\Controller;

use Think\Controller;

class BaseController extends Controller
{
	protected $exceptPermit = array(
		'Admin:login',
		);
	
	/**
	 * 当进入为空的情况
	 */
	public function _empty()
	{
        $this->display('Empty/index');
	}

	/**
	 * 类似中间件,对权限进行判断
	 */
	public function _initialize()
	{
		$action = ucfirst(I('path.0')).':'.I('path.1');
		if (!in_array($action, $this->exceptPermit)) {
			$info  = session('admin');

			if (!isset($info)) {
				header("HTTP/1.1 403 Forbidden");
				$this->redirect('Index/login', '', 3, '未登录');
				exit;		
			}
			$permit	= false;
			//验证权限
			if (!$permit = permit($info['id'], $action)) {
				//带参数的权限验证
				while (!$permit) {
					$id = M('permission')->where("name='%s'", $action)->find()['id'];
					if (empty($id)) {
						returnJson(404);
						
					}
					$permission	 = M('permission')->where('p_id=%d', $id)->find()['name'];
					if(empty($permission)) {
						break;
					}
					$data = per_decode($permission);
					//得到查询的参数
					foreach ($data['data'] as $key => &$value) {
						if (empty(I($key))) {
							returnJson(403);
							
						}
						$value = I($key);
					}
					$permission = per_encode($data['controller'], $data['action'], $data['data']);
					$permit = permit($info['id'], $permission);
				}
				if (!$permit) {
					returnJson(403);
					
				}
			}
		}
	}



}
