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

