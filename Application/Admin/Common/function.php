<?php

/**
 * 找Admin 对应的role_id值
 * @param  int 			$adminId 	
 * @return int|bool     不存在返回false,反之为其的roleid
 */
function verifyRole($adminId)
{
	$admin = M('admin');
	$data = $admin->find($adminId);
	//存在admin返回role的id值,不存在返回false
	if ($data) {
		return $data['role_id'];
	}
	return false;
}

/**
 * 判断是否有权限
 * @param  	int	  	$id   管理员id
 * @param  string 	$work 所执行的操作
 * @return bool       是否有权利
 */
function permit($id, $work)
{
	$role_id = verifyRole($id);

	//不存在返回false
	if(!$role_id) {
		return false;
	}
	//操作的信息
	$permission = M('permission')->where("name='%s'",$work)->find();
	//操作集
	$permissions = S('permissions');
	if (empty($permissions)) {
		$permissions = M('permission')->field('name')->select();
	}
	if (!in_array($permission['name'], $permissions)) { 
	 	
	 	$permissions = M('permission')->getField('name', true);
	 	S('permissions', $permissions);
	 	//判断是否在缓存的命令集中
	 	if(!in_array($permission['name'], $permissions)) {
	 		return false;
	 	}
	}
	S('abilities:'.$role_id, null);
	//获取所有能力
	if(S('abilities:'.$role_id) !== false) {
		$abilities = S('abilities:'.$role_id);
	} else {
		$abilities = D('Role')->ability($role_id);
		S('abilities:'.$role_id, $abilities);
	}
	//查询是否又该权力
	while (true) {
		//拥有该项能力返回true
		if (in_array($permission,$abilities)) {
			return true;
		}

		//如果父级id为零则结束向上查找父级id
		if($permission['p_id'] == 0) {
			break;
		}
		
		$permission = M('permission')->find($permission['p_id']);

	}
	return false;
}

/**
 * 根据status返回对应的json语句
 * @param  int $status      http请求码
 * @param  string $info   重写info信息
 * @param  array  $data   json里需要返回的数据
 */
function returnJson($status,  $info="", $data = array()) 
{
 	// print_r(debug_backtrace());exit;
    switch ($status) {
    	case 500:
    		$report = array('status'=>500, 'info'=>'服务器错误');
    		break;
        case 404: 
            $report = array('status'=>404, 'info'=>'error parameter');
            break;
        case 403:
            $report = array('status'=>403, 'info'=>'Don\'t permit');
            break;
        case 801:
            $report = array('status'=>801, 'info'=>'invalid parameter');
            break;
        case 200:
            $report = array('status'=>200, 'info'=>'success');
            break;
        case 'datatable':
        	$report = array('draw'=>intval($data['draw']), 'recordsFiltered' => intval($data['recordsFiltered']), 'recordsTotal' => intval($data['recordsTotal']), 'data' => $data['data']);
        	unset($data);
        	break;
        default:
            $report = array('status'=>$status);
    } 

    if(!empty($info)) {
        $report['info'] = $info;
    }
    if(!empty($data)) {
        $report['data'] = $data;
    }
    //加密序列化处理
    $json = message_encrypt($report);
    echo $json;
    exit;
}


/**
 * 对一些关键字进行处理
 * @param  array  &$data     需要过滤的数据
 * @param  array  $bindData 静止使用的关键词
 * @param  boolean $is_key   如果开启,去除$data中不允许存在的
 * 键值
 * @return bool            是否进行过滤
 */
function filter(&$data, $bindData, $is_key=false)
{
	$is_filter = false;
	if ($is_key) {	
		foreach ($bindData as $value) {
			if (array_key_exists($value, $data)) {
				unset($data[$value]);
				$is_filter = true;
			}
		}
	} else {
		foreach ($bindData as $bind) {
			foreach ($data as $key => $value) {
				//查询内容里是否包含违规内容
				if (strpos($value, $bind) !== false ) {
					$is_filter = true;
					unset($data[$key]);
				}
			}
		}
	}
	return $is_filter;
}

/**
 * 将分析储存在permission的信息
 * @param  string $permission 存储在数据库的信息 
 * eg $action?$key=$value&$key=$value
 * @return array             string中的参数
 */
function per_decode($permission) 
{
	$data = array();
	$array = explode('?', $permission);

	//$action = controller:action
	$action = explode(':', $array[0]);
	$data['controller'] = $action[0];
	$data['action'] = $action[1];
	
	//参数集合 key=value&key=value
	$array = explode('&', $array[1]);
	$data['data'] = array();
	foreach ($array as $value) {
		$arr = explode('=', $value);
		$data['data'][$arr[0]] = $arr[1];
	}
	return $data;
}

/**
 * 将相应信息转换成一定格式
 * @param  string $controller 控制器名称
 * @param  string $action 		操作名称
 * @param  array  $parameter  参数及 key=>value
 * @return string             储存的格式 controller:action?parm1=value1&parm2=value2
 */
function per_encode($controller, $action, $parameter)
{
	if(empty($action) || empty($controller)) {
		return false;
	}
	
	if(empty($parameter)) {
		return $controller.':'.$action;
	}
	$permission = $controller.':'.$action.'?';
	//上参数
	foreach ($parameter as $key => $value) {
		$permission = $permission.$key.'='.$value.'&';
	}

	$permission = rtrim($permission, '&');
	return $permission;
}

/**
 * 传输加密
 * @param  mix $data 需要加密的信息
 * @return string         加密后的信息
 */
function message_encrypt($data) 
{
	// var_dump($data);
	$data = json_encode($data);
	$data = base64_encode($data);
	return  $data;
}

/**
 * 传输解密
 * @param  string $string 需要加密的信息
 * @return mix         解密后的信息
 */
function message_decrypt($string)
{
	$string = base64_decode($string);
	$data  = json_decode($string, true);
	return $data;
}



/**
 *
 */
function is_alive() {
    $admin = session('admin');
    return !empty($admin);
}


