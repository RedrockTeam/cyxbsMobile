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
function returnJson($status, $info="", $data = null)
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
    if($data !== null) {
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
    if (is_null($stu)) {
        return null;
    }

    if (mystrlen($stu) == 10) {
        $user = D('users')->where("stunum='%s'", $stu)->find();
        if ($user)      return $user;
    }
//    $user = (int)$stu === 0 ? array(
//        'nickname' => "红岩网校工作站",
//        'photo_src' => "http://" . $_SERVER["SERVER_NAME"] . '/cyxbsMobile/Public/HONGY.jpg',
//        'photo_thumbnail_src' => "http://" . $_SERVER["SERVER_NAME"] . '/cyxbsMobile/Public/HONGY.jpg',
//        'stunum' => 0,
//        'id'    => 0,
//    ) : D("users")->find($stu);
    $user = D("users")->find($stu);
    return $user;
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

/**
 * @param $url string 请求地址
 * @param $data array post 请求参数
 * @return mixed
 */
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

/**
 * 是否加入这个话题
 * @param $topic_id  int 话题标号
 * @param $user     int|string 用户标示
 * @return bool
 */
function is_my_join($topic_id, $user) {
    $user = getUserInfo($user);
    if ($user === false) {
        return false;
    }
    $pos = D('topics')->where(array('id' => $topic_id, 'user_id' => $user['id'], 'official'=>array('NEQ',1)))->find();
    if ($pos)   return true;
    $pos = array('topic_id' => $topic_id, 'user_id' => $user['id'], 'state' => 1);
    $result = D('topicarticles')->where($pos)->find();
    if($result) return true;

    $article_ids = D('topicarticles')->field('id')->where(array('topic_id' => $topic_id, 'state'=>1,'official'=>array('NEQ',1)))->select(false);

    $pos['articletypes_id'] = 7;
    //in 子句
    $pos['article_id'] = array('exp', 'IN('.$article_ids.')');

    $result = D('articleremarks')->where($pos)->count();
    return $result ? true : false;

}

/**
 * @param $type_id  int 文章类型
 * @return bool|string
 */
function getArticleTable($type_id) {
    $table = '';
    switch ($type_id) {
        case 1:
        case 2:
        case 3:
        case 4:
            $table = 'news';
            break;
        case 5:
            $table = 'articles';
            break;
        case 6:
            $table = 'notices';
            break;
        case 7:
            $table = 'topicarticles';
            break;
    }
    return empty($table) ? false : $table;
}

/**
 * 参与一个话题
 * @param $topicId   int    话题
 * @param $stuNum   string  学号
 * @return bool     是否参与成功
 */
function addJoinTopicIds($topicId, $stuNum) {
    if (empty($topicId) || empty($stuNum))  return false;
    $topicIds = getJoinTopicIds($stuNum);
    if (false !== $key=array_search($topicId, $topicIds))
        unset($topicIds[$key]);
    else {
        D('topics')->where('id=%d',$topicId)->setInc('join_num');
    }
    array_unshift($topicIds, $topicId);
    setTopicIds($stuNum, $topicIds);
    return true;
}

/**
 * 退出一个话题
 * @param $topicId  int    话题
 * @param $stuNum   string  学号
 * @return bool 是否添加成功
 */
function subscribeJoinTopicIds($topicId, $stuNum) {
    if (!is_my_join($topicId, $stuNum)) {
        $result = D('topics')->where('id=%d', $topicId)->setDec('join_num');
        if (!$result)   return false;
        $topicIds = getJoinTopicIds($stuNum);
        $key = array_search($topicId, $stuNum);
        if ($key === false)     return false;
        unset($topicIds[$key]);
        setTopicIds($stuNum, $topicIds);
    } else {
        setTopicIds($stuNum, '');
    }
    return true;
}

/**
 * @param $stuNum   string  学号
 * @param $topicIds int     话题号
 * 参加话题的缓存
 */
function setTopicIds($stuNum, $topicIds) {
    S('ZSCY-JoinedTopic-'.$stuNum, $topicIds);
}

/**
 * @param $stuNum   string 学号
 * @return array|bool|mixed|null    null 没有参与 false传入参数有问题 array 参与的话题列表
 */
function getJoinTopicIds($stuNum) {
    if (empty($stuNum)) return false;
    $topicIds = S('ZSCY-JoinedTopic-'.$stuNum);
    //缓存了的
    if (!empty($topicIds)) {
        return $topicIds;
    }

    $user = getUserInfo($stuNum);
    if (!$user)    return false;
    //获取该用户通过回答回复参与过话题
    $remarkPos = array(
        'user_id' => $user['id'],
        'state' => 1,
        'articletypes_id' => 7,
        'created_time' => array('elt', date('Y-m-d H:i:s')),
    );
    $topicArticleRemarks = D('articleremarks')->field('max(created_time) as created_time, article_id')->where($remarkPos)->group('article_id')->select();

    $remarkTopicIds = array();

    if (!empty($topicArticleRemarks)) {
        foreach($topicArticleRemarks as $remark)
            $remarkTopicIds[$remark['created_time']] = M('topicarticles')->where(array('id'=>$remark['article_id'], 'state'=>1))->getField('topic_id');
    }
    unset($topicArticleRemarks);

    //获取该用户通过回答写文章参与过话题
    $pos = array(
        'user_id' => $user['id'],
        'state' => 1,
        'created_time' => array('elt', date('Y-m-d H:i:s')),
        'official' => array('NEQ', 1),
    );
    $topicArticles = D('topicarticles')->field('max(created_time) as created_time, topic_id')->where($pos)->group('topic_id')->select();
    $articleTopicIds = array();
    if (!empty($topicArticles))
        foreach ($topicArticles as $article) {
            $articleTopicIds[$article['created_time']] = $articleTopicIds[$article['topic_id']];
        }
    unset($topicArticles);
    //获取该用户发起的话题 参与过话题
    $topics = D('topics')->field('created_time, id')->where($pos)->select();

    $topicIds = array();
    if (!empty($topics))
        foreach ($topics as $topic)
            $topicIds[$topic['created_time']] = $topic['id'];
    //数组合并
    $topicIds = array_merge($remarkTopicIds, $topicIds, $articleTopicIds);

    unset($remarkTopicIds);
    unset($articleTopicIds);

    //没找到对应的话题返回空
    if(empty($topicIds))    return null;
    //排序
    ksort($topicIds, SORT_LOCALE_STRING);
    //反转去重
    $topicIds = array_flip($topicIds);
    $topicIds = array_flip($topicIds);
    //倒序排列
    krsort($topicIds, SORT_LOCALE_STRING);
    $topicIds = array_values($topicIds);
    //缓存
    S('JoinedTopic-'.$stuNum, $topicIds);
    return $topicIds;
}