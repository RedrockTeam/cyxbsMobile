<?php

/**
 * 获取毫秒时间戳
 * @return float 时间戳
 */
function getMillisecond() 
{
	list($t1, $t2) = explode(' ', microtime());     
	return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);  
}

/**
 * 根据status返回对应的json语句
 * @param  int|array $status http请求码
 * @param  string|array $info 重写info信息
 * @param  array $data json里需要返回的数据
 * @throws \Think\Exception
 */
function returnJson($status, $info="", $data = null)
{
    // print_r(debug_backtrace());exit;
    if (is_array($status)) {
        $report = checkJson($status);
        header('Content-type:application/json');
        $json = json_encode($report);
        echo $json;
        exit;
    }
    if (is_numeric($status)) {
        switch ($status) {
            case 404:
                $report = array('status' => 404, 'info' => '请求参数错误', 'state' => 404);
                break;
            case 403:
                $report = array('status' => 403, 'info' => 'Don\'t permit', 'state' => 403);
                break;
            case 801:
                $report = array('status' => 801, 'info' => 'invalid parameter', 'state' => 801);
                break;
            case 200:
                $report = array('status' => 200, 'info' => 'success', 'state' => 200);
                break;
            default:
                $report = array('status' => $status, 'info' => "", 'state' => $status);
        }
    } else {
        throw new \Think\Exception('错误参数');
    }
    if (is_array($info)) {
        $report = array_merge($report, $info);
        returnJson($report);
    }
    if(!empty($info) && is_string($info)) {
        $report['info'] = $info;
    }
    if($data !== null) {
        if (!is_array($data))
            $data = array('data' => $data);
        $report = array_merge($report, $data);
        returnJson($report);
    }
}

function checkJson($data) {
    foreach ($data as $key => &$value) {
        if (is_array($value))
            $value = checkJson($value);
        elseif (is_numeric($value)) {
            $fields = array('nickname', 'title', 'content', 'keyword', 'name', 'message', 'address', 'classnum', 'stunum','user_id', 'stuNum');
            if (!in_array($key, $fields, true)) {
                $value =  $value==(int)$value ? (int)$value : (double)$value;
            }
        }
    }
    return $data;
}

 /**
 * 信息加密
 * @param  string $data 需要加密的信息
 * @param  string $salt 盐
 * @return string       加密后的字符串
 */
function encrypt($data, $salt='')
{
    return base64_encode($data);
}

/**
 * 信息解密
 * @param  string $data 加密的信息
 * @param  string $salt 盐
 * @return string      解密的信息
 */
function decrypt($data, $salt='')
{
    return base64_decode($data);
}

//时间转换
function timeFormate($startTime='', $format="Y-m-d H:i:s")
{
    if (empty($time)) {
        return date($format);
    }
    if (is_numeric($startTime)) {
        if (strlen($startTime) > 10) {
            $startTime = substr($startTime, 0, 10);
        }
        $startTime = '@'.$startTime;
    }
    $startTime = new DateTime($startTime);
    $time =  $startTime->format($format);
    return $time;
}

 /**
 * 判断是否为管理员
 * @param  string  $user   身份标示
 * @return boolean         是否为管理员
 */
function is_admin($user)
{
    if (empty($user))
        return !session('admin') ? false : true;

    $user = getUserInfo($user);
    $user_id = $user['id'];


    $is_admin  = M('admin')->where(array('state'=>1,'user_id'=>$user_id))->find();
    if(!$is_admin) {
        $is_admin = M('administrators')->where('user_id='.$user_id)->find();
        if (!$is_admin) {
            return false;
        }
    }
    return true;

    
}

function forbidwordCheck($value, $field)
{
    return $value;
}




/**
 * 验证身份信息
 * @param $stuNum   string 学号
 * @param $idNum    string 身份证号
 * @return bool|mixed
 */
function authUser($stuNum, $idNum)
{
    if (empty($stuNum) || empty($idNum)) return false;

    $idnum = S($stuNum);

    if (!empty($idnum))
        return $idNum == $idnum;

    $condition = array(
        "stuNum" => $stuNum,
        "idNum"  => $idNum
    );
    $url = "http://hongyan.cqupt.edu.cn/api/verify";
    $needInfo = curlPost($url,$condition);
    $needInfo = json_decode($needInfo,true);
    if($needInfo['status'] != 200){
        return json_encode($needInfo);
    }else{
        S($stuNum, $idNum);
    }
    return true;
}


