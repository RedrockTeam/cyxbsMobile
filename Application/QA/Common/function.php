<?php
/**
 * Created by PhpStorm.
 * User: uncomplex func
 * Date: 2018/2/3
 * Time: 15:36
 */

//const DOMAIN = "https://wx.idsbllp.cn/springtest/cyxbsMobile";
const DOMAIN = "https://hongyan.cqupt.edu.cn/app";
const REPORT_TABLE = "reports";
/**
 * 根据status返回对应的json语句
 * @param  int $status http请求码
 * @param  string $info 重写info信息
 * @param  array $data
 * @return void $data json里需要返回的数据
 */
function returnJson($status, $info = "", $data = array())
{
    // print_r(debug_backtrace());exit;
    switch ($status) {
        case 500:
            $report = array('status' => 500, 'info' => 'server error');
            break;
        case 404:
            $report = array('status' => 404, 'info' => 'error parameter');
            break;
        case 403:
            $report = array('status' => 403, 'info' => 'Don\'t permit');
            break;
        case 801:
            $report = array('status' => 801, 'info' => 'invalid parameter');
            break;
        case 200:
            $report = array('status' => 200, 'info' => 'success', "data" => array());
            break;
        case 415:
            $report = array("status" => 405, "info" => "invalid http method");
            break;
        case 405:
            $report = array("status" => 405, "info" => "invalid http method");
            break;
        case 'datatable':
            $report = array('draw' => intval($data['draw']), 'recordsFiltered' => intval($data['recordsFiltered']), 'recordsTotal' => intval($data['recordsTotal']), 'data' => $data['data']);
            unset($data);
            break;
        default:
            $report = array('status' => $status);
    }


    if (!empty($info)) {
        $report['info'] = $info;
    }
    if (!empty($data)) {
        $report['data'] = $data;
    }
    //加密序列化处理
    //$json = message_encrypt($report);
    header("Content-Type:application/json");
    http_response_code($status);
    echo json_encode($report);
    exit;
}


/**
 * 检查一组参数中是否有空参数
 * @param  array $parameters 参数数组
 * @return  boolean $result 检查结果
 */
function checkParameter($parameters = array())
{
    $test = I("post.");
    foreach ($parameters as $value) {
        if (empty($test[$value]) && !is_numeric($test[$value])) {
            return false;
        }
    }
    return true;
}


/**
 * 从用户表中获取userid
 * @param  int $stunum 学号
 * @return  mixed int|boolean 用户在user表的主键id
 */
function getUserIdInTable($stunum)
{
    $queryField = array(
        'stunum' => $stunum,
    );
    $UsersModel = M('users');
    $result = $UsersModel->where($queryField)->field('id')->find();
    if (!empty($result))
        return $result['id'];
    else
        return false;
}

/**
 * 通过用户id获取基本信息
 * @param  int $user_id 用户id
 * @return  mixed $result 包含头像 昵称 性别
 */
function getUserBasicInfoInTable($user_id)
{
    $userModel = M('users');
    $result = $userModel
        ->field("nickname,photo_thumbnail_src,gender")
        ->where(array(
            "id" => $user_id,
        ))
        ->find();
    if (!empty($result))
        return $result;
    else
        return false;
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
        "idNum" => $idNum
    );
    $url = "http://hongyan.cqupt.edu.cn/api/verify";
    $needInfo = curlPost($url, $condition);
    $needInfo = json_decode($needInfo, true);
    if ($needInfo['status'] != 200) {
        return false;
    } else {
        S($stuNum, $idNum);
    }
    return true;
}


