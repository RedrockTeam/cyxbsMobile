<?php
namespace Home\Controller;
use Think\Controller;

class PersonController extends BaseController {
    protected $changeState = array(
        'start' => array('before'=> -1, 'after'=>1),
        'close' => array('before'=> 1, 'after'=>-1),
        'delete'=> array('before'=>'1,-1', 'after' => 0, 'function'=>'deleteTransactionTime'),
        'recover' => array('before'=> 0, 'after' => 1, 'function'=> 'recoverTransactionTime'),
    );

    public function search(){
        $user = M('users');
        $stunum_other = I('post.stunum_other');
        if($stunum_other == null){
            $stunum = I('post.stuNum');
        }else{
            $stunum = $stunum_other;
        }
        if($stunum == null){
            $info = array(
                "status" => 801,
                "info"   => "invalid parameter"
            );
            echo json_encode($info);exit;
        }else{
            $condition = array(
                "stunum" => $stunum
            );
            $data = $user->where($condition)->field('id,stunum,introduction,username,nickname,gender,photo_thumbnail_src,photo_src,updated_time,phone,qq')->find();
            $info = array(
                'status' => '200',
                "info"   => "success",
                'data'   => $data
            );
            echo json_encode($info);
        }

    }

    public function setInfo(){
        $all_info  = I('post.');

        $bank_array = array('redrock','管理员','红岩','红岩网校工作站','重邮','重庆邮电大学','cqupt','大学','邮电');

        $test_nickname = trim($all_info['nickname']);
        $test_nickname = str_replace(' ', '', $test_nickname);
        $test_nickname = strtolower($test_nickname);

        foreach($bank_array as $key => $value){
            if(strpos($test_nickname,$value) === false){

            }else{
                $check_exist = true;
                break;
            }
        }
        if($check_exist){
            $info = array(
                    "status" => 801,
                    "info"   => "failed"
                );
            echo json_encode($info);exit;
        }
        $all_info['stunum'] = $all_info['stuNum'];
        $all_info['idnum'] = $all_info['idNum'];
        $all_info['updated_time'] = date("Y-m-d H:i:s", time());
        unset($all_info['stuNum']);
        unset($all_info['idNum']);
        $all_info = array_filter($all_info);
        $user  = M('users');
        $user_condition = array(
                "stunum" => I('post.stuNum')
            );
        $stunum = I('post.stuNum');
        $idNum  = I('post.idNum');
        $search_condition = array(
            "stuNum" => $stunum,
            "idNum"  => $idNum
        );
        $needInfo = $this->curl_init($this->apiUrl,$search_condition);
        $needInfo = json_decode($needInfo,true);
        $all_info['username'] = $needInfo['data']['name'];
        $all_info['gender'] = trim($needInfo['data']['gender']);
        $checkExist = $user->where($user_condition)->find();
        if($checkExist != NULL){
            $goal = $user->where($user_condition)->data($all_info)->save();
        }else{
            $goal = $user->add($all_info);
        }  
        $info = array(
            'status' => '200',
            "info"   => "success",
        );
        echo json_encode($info);
    }

    public function setNickname(){
        if(I('post.username')==null){
            $info = array(
                "status" => 801,
                "info"   => "invalid parameter"
            );
        }else{
            $user = M('users');
            $condition = array(
                "stunum" => I('post.stuNum')
            );
            $content = array(
                "nickname" => I('post.username')
            );
            $goal = $user->where($condition)->find();
            if($goal){
                $info = array(
                    "status" => 200,
                );
            }else{
                $info = array(
                    "status" => 801,
                    "info"   => "invalid parameter"
                );
            }
        }
        echo json_encode($info,true);
    }

    /**
     * 创建事务
     */
    public function addTransaction()
    {
        $information = I('post.');

        if (!$this->produceTransaction($information)) {
            returnJson(404);
        }

        $user = M('users')->where("stunum='%s'", $information['stuNum'])->find();
        if(!$user) {
            returnJson(404,'没有完善信息,无法添加事务');
        }
        $user_id = $user['id'];
        if(!$this->derepeat($user_id, $information, $information['date'], $error)) {
            returnJson(404, $error);
        }

        $id = empty($information['id']) ? getMillisecond().sprintf("%04.0f",mt_rand(0000,9999)) : $information['id'];
        $current_time = date("Y-m-d H:i:s");
        $term = $this->getTerm();
        $data = array(
            "time" => $information['time'],
            'title' => $information['title'],
            'content' => $information['content'],
            'id' => $id,
            'updated_time' => $current_time,
            'user_id'      => $user_id,
            'term'         => $term,
        );
        $result = M('transaction')->add($data);
        if (!$result) {
           returnJson(500,'error');
        }

        $transaction_time = M('transaction_time');
        
        foreach($information['date'] as $date) {
            $data = $date;
            $data['transaction_id'] = $id;
            $data['updated_time'] = $current_time;
         
            if (!$transaction_time->add($data)) {
                returnJson(500, 'error');
            }
        }

        returnJson(200, '', array('id' => $id));
    }


    //类似魔术方法改变事务状态
    public function _empty($name)
    {   
        $pattern = "/([a-zA-Z_]+)Transaction/";
        if(preg_match($pattern, $name, $result)) {
            $operate = $this->changeState[$result[1]];
            if (isset($operate)) {
                $information = I('post.');
                call_user_func(array($this,'changeTransactionState'), $information, $operate);
            }
        }
        header( "HTTP/1.1 404 Not Found" );       
        $this->display('Empty/index');
    }
     /**
     * 改变事务状态
     */
    protected function changeTransactionState($information, $operate)
    {    
        $term = $information['term'];
        $term = empty($term) ?  $this->getTerm() : $term;

        if (!is_array($operate['before'])) {
            $operate['before'] = explode(',', $operate['before']);
        }
        //是否对已删除的事项进行操作
        $operateForDeleted = in_array(0, $operate['before']);
       
        if (!$transaction = $this->isTransactionOwner($information['id'], $information['stuNum'], $operateForDeleted)) {
             if(!$this->is_admin($information['stuNum'])) {
               returnJson(403);
            }

            $transaction = M('transaction')->find($information['id']);
        }

        if (isset($operate['function'])) {
            $result = call_user_func(array($this, $operate['function']), $information);
            if (!$result) {
                returnJson(500);
            }
        }
         
        //state为0 为删除
        $time = date("Y-m-d H:i:s");
        $data = array(
            'id' => $information['id'],
            'state' => $operate['after'],
            'updated_time' => $time
        );
    
        if (!in_array($transaction['state'] ,$operate['before'])) {
            returnJson(404);
        } 
        if (M('transaction')->data($data)->save())
            returnJson(200);
        else {
            returnJson(500, 'error');
        }
        
    }
    //当事项被删除时，改变其时间的状态为-1
    protected function deleteTransactionTime($information)
    {
        $pos = array('transaction_id' => $information['id'], 'state'=>1);
        $data['updated_time'] = date('Y-m-d H:i:s');
        $data['state'] = -1;
        $result = M('transaction_time')->where($pos)->data($data)->save();
        return $result;
    }
    //当事项被恢复时，改变其时间的状态为1
    protected function recoverTransactionTime($information)
    {
        $pos = array('transaction_id' => $information['id'], 'state'=>-1);
        $data['updated_time'] = date('Y-m-d H:i:s');
        $data['state'] = 1;
        $result = M('transaction_time')->where($pos)->data($data)->save();
        return $result;
    }


    /**
     * 获取事务信息
     */
    public function getTransaction()
    {
        $stuNum = I('stuNum');
        $term = I('term');
        $term = empty($term) ?  $this->getTerm() : $term; 
        $user = M('users')->where('stunum="%s"', $stuNum)->find();
        if (!$user) 
            returnJson(403);
        $field = array(
            'id',
            'time',
            'title',
            'content',       
        );
        $pos = array(
            'user_id' => $user['id'],   
            'cyxbsmobile_transaction.state'=> array('neq', 0), 
            'term' => $term
        );
        $data = M('transaction')
                            ->where($pos)
                            ->field($field)
                            ->order('updated_time')
                            ->select();
        foreach ($data as &$transaction) {
            $transaction['date'] = M('transaction_time')
                                ->where(array('transaction_id'=>$transaction['id']))
                                ->field('class, day, week')
                                ->select();
            foreach($transaction['date'] as &$value) {
                $value['week'] = explode(',', $value['week']);
            }
            
        }
        $data = compact('term', 'stuNum', 'data');
        returnJson(200, '', $data);
    }

    /**
     * 修改事务信息
     */
    
    public function editTransaction()
    {
        $information = I('post.');
        
        if (!$this->produceTransaction($information, true, $change)) {
            returnJson(404);
        }
        //未修改
        if (empty($change)) {
            returnJson(801);
        }
        //是否有权修改
        if (!$this->isTransactionOwner($information['id'], $information['stuNum'])) {
            if(!$this->is_admin($information['stuNum'])) {
               returnJson(403);
            }
        }

        $change['id'] = $information['id'];
        $change['updated_time'] = date('Y-m-d H:i:s');
        if (isset($change['date'])) {
            if (!$this->editTransactionTime($change['id'], $change['date']))
                returnJson(404);
        }
        if (M('transaction')->data($change)->save()) {
            returnJson(200);
        } else {
            returnJson(500, 'error');
        }
    }
    /**
     * 修改事项的时间
     * @param  bigint $id   事项的id
     * @param  array $date 事项的时间
     * @return bool       修改是否成功
     */
    protected function editTransactionTime($id, $dates)
    {
        $pos = array("transaction_id"=>$id);
        $before_dates = M('transaction_time')->where($pos)->select();
        $before_count = count($before_dates);
        $after_count = count($dates);
        $max = $before_count > $after_count ? $before_count : $after_count;
        $result = true;
        for($i=0; $i<$before_count; $i++) {
            if ($i >= $after_count) {
                $before_dates[$i]['state'] = 0;
                $result = M('transaction_time')->data($before_dates[$i])->save();
            } elseif ($i >= $before_count) {
                $date = $dates[$i];
                $date['transaction_id'] = $id;
                $date['updated_time'] = date('Y-m-d H:i:s');
                $result = M('transaction_time')->data($date)->add();
            } else {
                $date = $dates[$i];
                $date['id'] = $before_dates[$i]['id'];
                $date['updated_time'] = date('Y-m-d H:i:s');
                $result = M('transaction_time')->data($date)->save();
            }
            if (!$result) {
                return false;
            }
        }
        return true;
    }

    /**
     * 判断是否为事项的拥有者
     * @param  bigint  $id     事项id
     * @param  int     $stunum 学号
     * @param  bool  $operateForDeleted   是否对已删除的进行操作
     * @return boolean         true为有权利
     */
    public function isTransactionOwner($id, $stunum, $operateForDeleted = false)
    {
    
        $pos = array('id'=>$id);

        if (!$operateForDeleted) {
            $pos['state'] = array('neq', 0);
        }
        $transaction = M('transaction')->where($pos)->find();
        if (!$transaction) {
            return false;
        }
        $user = M('users')->where('stunum="%s"', $stunum)->find();
        if (!$user)
            returnJson(403);
      return ($user['id'] == $transaction['user_id']) ? $transaction : false; 
        
    }

    /**
     * 防止完全相同的事项出现
     * @param int $user_id            用户的id值
     * @param array $transactionMessage 事项的基本参数
     * @param array &$transactionDate   事项的时间
     * @param string &$error             错误信息
     * @return boolean            true代表生成正确的时间
     */
    protected function derepeat($user_id, $transactionMessage, &$transactionDate, &$error='')
    {
        if (!is_numeric($user_id) || empty($transactionMessage) || empty($transactionDate)) {
            $error = 'Missing Parameters';
            return false;
        }

        $term = empty($transactionMessage['term']) ? $this->getTerm() : $transactionMessage['term'];
        //查找对应信息相同的有哪些
        $data = array(
            'user_id' => $user_id,
            'title'   => $transactionMessage['title'],
            'content' => $transactionMessage['content'],
            'time'    => $transactionMessage['time'],
            'term'    => $term,
            'state'   => array('neq', 0),
        );
       
        $transactions = M('transaction')->where($data)->field('id')->select();
        
        if (!$transactions) {
            return true;
        } else {
            $transaction_ids = array();
            foreach($transactions as $transaction)
                $transaction_ids[] = $transaction['id'];
        }
        foreach($transactionDate as $key => $date) {
            $data = array('date'=>$date);

            //验证格式正确
            if (!$this->produceTransaction($data, true)) {
                $error .= $key.'parameter is error, ';
                unset($transactionDate[$key]);
                continue;
            } 
            $data = $data['date'];
            $data['state'] = array('neq', 0);
            //查找重复的
            foreach ($transaction_ids as $transaction_id) {
               $data['transaction_id'] = $transaction_id;
               $result = M('transaction_time')->where($data)->find();
               //已存在时间的，去掉该时间
               if($result) {
                    unset($transactionDate[$key]);
                    break;
               }
            }
        }
        if(empty($transactionDate)) {
            $error = "all parameter is exist";
            return false;
        }
        return true;
    }


    /**
     * /获取当前的学期
     * @param  int   $timestamp = time() 
     * @return bool|int 返回学期标示或false           
     */
    protected function getTerm($timestamp)
    {
        if (empty($timestamp)) {
            $timestamp = time();
        } elseif (is_numeric($timestamp)) {
            if(strlen($timestamp) > 10) {
                $timestamp = substr($timestamp, 0, 10);
            } elseif (strlen($timestamp) <= 10) {
                return false;
            }
        } else {
            return false;
        }
        $year = date('Y', $timestamp);
        $month = date('m', $timestamp);
        if ($month < 3) {
            $term = ($year-1).$year.'1';
        } else if($month >= 9) {
            $term = $year.($year+1).'1';
        } else {
            $term = ($year-1).$year.'2';
        }
        return $term;
    }

    /**
     * 判断传入参数是否合法，并对一些信息进行处理
     * @param  array &$information 传入信息
     * @param  bool  $is_edit    是否为修改
     * @param array $parameter 将符合的参数存放到该变量里
     * @return bool               是否合法
     */
    protected function produceTransaction(&$information, $is_edit = false, &$parameter=array())
    {
        if ($is_edit && empty($information['id'])) {
            return false;
        }
        foreach ($information as $field => &$value) {
            $inField = true;
            //选择类型 
            switch($field) {
                case 'date' :
                    if (!is_array($value)) {
                        $value =  $_POST[$field];
                        $value = json_decode($value, true);
                    }
                    $stack = array();
                    foreach($value as $key => &$date) {
                        //判断星期
                        if (is_numeric($date['day'])) {
                            if ($date['day'] > 6 || $date['day'] < 0) {
                                return false;
                            }
                        }
                        else    return false;

                        //判断课程        
                        if (is_numeric($date['class'])) {
                            if ($date['class'] > 5 || $date['class'] < 0) {
                                 return false;
                            }
                        }
                        else    return false;
                        
                        //判断week
                        if(!is_array($date['week'])) {
                            $date['week'] = explode(',',$date['week']);
                        }
                        //反转去重
                        $date['week'] = array_flip($date['week']);
                        $date['week'] = array_flip($date['week']);
                        sort($date['week']); 
                        foreach ($date['week'] as $week) {
                            if(!is_numeric($week) || $week <= 0 || $week > 21) 
                                return false;
                        }

                        $date['week'] = implode(',', $date['week']);
                        //三元组去重复
                        if(!empty($stack[$date['day']][$date['class']])) {
                            unset($value[$key]);
                            continue;
                        }
                       $stack[$date['day']][$date['class']] = $date['week'];
                    }  
                  break;

                case 'time':
                    if (empty($value)) {
                        $value=NULL;
                    } elseif (!is_numeric($value)) 
                        return false;
                    break;

                case 'title':
                case 'content':
                    $value = trim($value);
                    break;
                
                case 'id':
                    $inField = false;
                    $len = strlen($value);
                    if ($len !== 17 || !is_numeric($value)) {
                        return false;
                    }
                    break;
                
                default:
                    $inField = false;
               }
               
               if ($inField) {
                    $parameter[$field] = $value; 
               }
        }
        if (isset($information['state'])) 
            unset($information['state']);
       if (!$is_edit) {
            if (is_null($information['content'])) {
                $information['content'] = '';
            } 
            if (empty($information['title']) || empty($information['date']))
                return false;
       }
       return true;

    }
}