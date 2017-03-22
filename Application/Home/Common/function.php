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
 * @param  int $status      http请求码
 * @param  array  $data   json里需要返回的数据
 * @param  string $info   重写info信息
 * @return [type]         [description]
 */
function returnJson($status, $info="", $data = array()) 
{   
    // print_r(debug_backtrace());exit;
    switch ($status) {
        case 404: 
            $report = array('status'=> 404, 'info'=>'请求参数错误');
            break;
        case 403:
            $report = array('status'=> 403, 'info'=>'Don\'t permit');
            break;
        case 801:
            $report = array('status'=> 801, 'info'=>'invalid parameter');
            break;
        case 200:
            $report = array('status'=> 200, 'info'=>'success');
            break;
        default:
            $report = array('status'=>$status, 'info'=>"");
    }

    if(!empty($info)) {
        $report['info'] = $info;
    }
    if(!empty($data)) {
        if(array_key_exists('info', $data) || array_key_exists('status', $data)) {
            return false;
        } else {
            $report = array_merge($report, $data);
        }
    }
    header('Content-type:application/json');
    $json = json_encode($report, JSON_NUMERIC_CHECK);
    echo $json;
    exit;
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
 * @param  string  $stunum 学号
 * @return boolean         是否为管理员
 */
function is_admin($stunum) 
{
    if (empty($stunum)) {
        if (!session('admin')) {
            return false;
        }
        $stunum = session('admin.stunum');
    }
    $stu = D('users')->where('stunum="%s"', $stunum)->find();
    if (empty($stu)) {
        return false;
    }
    $id = $stu['id'];
    $is_admin  = M('admin')->where(array('state'=>1,'stunum'=>$stunum))->find();
    if($is_admin) {
        return true;
    } else {
        $is_admin = M('administrators')->where('user_id='.$id)->find();
        if ($is_admin) {
            return true;
        }
    }

    return false;
    
}

function forbidwordCheck($value, $field)
{
    return $value;
}

/**
 * @param $str  string 字符串
 * @return int
 */
function mystrlen($str) {
    return mb_strlen($str, 'UTF-8');
}

/**
 * @param $stu   string   学号或者user_id
 * @return mixed    用户信息
 */
function getUserInfo($stu) {
    if (empty($stu)) {
        return null;
    }

    if (mystrlen($stu) == 10) {
        $user = D('users')->where("stunum='%s'", $stu)->find();
        if ($user)      return $user;
    }
    $user = D("users")->find($stu);
    return $user;
}


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
    $needInfo = $this->curl_init($url,$condition);
    $needInfo = json_decode($needInfo,true);
    if($needInfo['status'] != 200){
        return json_decode($needInfo,true);
    }else{
        S($stuNum, $idNum);
    }
    return true;
}
function curlPost($url,$data){//初始化目标网站
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt ($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    $output = curl_exec($ch);
    curl_close ($ch);
    return $output;
}
