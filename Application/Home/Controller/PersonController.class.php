<?php
namespace Home\Controller;
use Think\Controller;

class PersonController extends BaseController {
    protected $changeState = array(
        'start' => array('before'=> -1, 'after'=>1),
        'close' => array('before'=> 1, 'after'=>-1),
        'delete'=> array('before'=>'1,-1', 'after' => 0),
        'recover' => array('before'=> 0, 'after' => 1),
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
            $this->returnJson(404);
        }

        $user = M('users')->where("stunum='%s'", $information['stuNum'])->find();
        if(!$user) {
            $this->returnJson(404, '', '没有完善信息,无法添加事务');
        }
        $user_id = $user['id'];
        $id = empty($information['id']) ? getMillisecond().sprintf("%04.0f",mt_rand(0000,9999)) : $information['id'];
        $current_time = date("Y-m-d H:i:s");
        $term = $this->getTerm();
        $data = array(
            "week" => $information['week'],
            'class'=> $information['class'],
            "time" => $information['time'],
            'day'  => $information['day'],
            'title' => $information['title'],
            'content' => $information['content'],
            'id' => $id,
            'updated_time' => $current_time,
            'user_id'      => $user_id,
            'term'         => $term,
        );
        $result = M('transaction')->add($data);
        if (!$result) {
            $this->returnJson(500, '','error');
        }
        $this->returnJson(200, array('id' => $id));
    }



    public function _empty($name)
    {   
        $pattern = "/([a-zA-Z_]+)Transaction/";
        if(preg_match($pattern, $name, $result)) {
            $state = $this->changeState[$result[1]];
            if (isset($state)) {
                $information = I('post.');
                call_user_func(array($this,'changeTransactionState'), $information, $state);
            }
        }
        header( "HTTP/1.1 404 Not Found" );       
        $this->display('Empty/index');
    }
     /**
     * 改变事务状态
     */
    protected function changeTransactionState($information, $state)
    {    
        $term = $information['term'];
        $term = empty($term) ?  $this->getTerm() : $term;
        if (!$transaction = $this->isTransactionOwner($information['id'], $information['stuNum'], $term)) {
             if(!$this->is_admin($information['stuNum'])) {
               $this->returnJson(403);
            }

            $transaction = M('transaction')->find($information['id']);
        }
         
        //state为0 为删除
        $time = date("Y-m-d H:i:s");
        $data = array(
            'id' => $information['id'],
            'state' => $state['after'],
            'updated_time' => $time
        );
        
        if (!is_array($state['before'])) {
            $state['before'] = explode(',', $state['before']);
        }
        if (!in_array($transaction['state'] ,$state['before'])) {
            $this->returnJson(404);
        } 
        if (M('transaction')->data($data)->save())
            $this->returnJson(200);
        else {
            $this->returnJson(500, '', 'error');
        }
        
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
            $this->returnJson(403);
        $field = array(
            'id',
            'class',
            'day', 
            'time',
            'title',
            'content',
            'state',            
            'week',          
        );
        $pos = array(
            'stunum' => $stuNum,   
            'state'=> array('neq', 0), 
            'term' => $term
        );
        $data = M('transaction')->where($pos)->field($field)->select();
        foreach ($data as &$value) {
            $value['week'] = explode(',', $value['week']);
        }
        $data = compact('term', 'stuNum', 'data');
        $this->returnJson(200, $data);
    }

    /**
     * 修改事务信息
     */
    
    public function editTransaction()
    {
        $information = I('post.');
        
        if (!$this->produceTransaction($information, true, $change)) {
            $this->returnJson(404);
        }
        //未修改
        if (empty($change)) {
            $this->returnJson(801);
        }
        //是否有权修改
        if (!$this->isTransactionOwner($information['id'], $information['stuNum'], $information['term'])) {
            if(!$this->is_admin($information['stuNum'])) {
               $this->returnJson(403);
            }
        }
        $change['id'] = $information['id'];
        $change['updated_time'] = date('Y-m-d H:i:s');
        if (M('transaction')->data($change)->save()) {
            $this->returnJson(200);
        } else {
            $this->returnJson(500, '', 'error');
        }
    }

    public function isTransactionOwner($id, $stunum, $term='')
    {
        if (empty($term))
            $term = $information['term'];
        $pos = array('id'=>$id);
        $transaction = M('transaction')->where($pos)->find();
        if (!$transaction) {
            return false;
        }
        $user = M('users')->where('stunum="%s"', $stunum)->find();
        if (!$user)
            $this->returnJson(403);
      return ($user['id'] == $transaction['user_id']) ? $transaction : false; 
        
    }

    //获取当前的学期
    protected function getTerm()
    {
        $year = date('Y');
        $month = date('m');
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
                
                case 'day':
                    if (is_numeric($value))
                        if ($value <= 6 || $value >= 0) {
                            break;
                        }
                    return false;
               
                case 'lesson':            
                    if (is_numeric($value))
                        if ($value <= 5 || $value >= 0) {
                            break;
                        }
                    return false;
                
                case 'week':            
                    if(!is_array($value)) {
                        $value = explode(',',$value);
                    }

                    foreach ($value as $week) {
                        if(!is_numeric($week) || $week <= 0 || $week > 21) 
                            return false;
                    }
                    
                    $value = implode(',', $value);
                  
                    break;

                case 'time':
                    if (!is_numeric($value)) 
                        return false;
                    break;

                case 'title':
                    $value = trim($value);
                    break;
                
                case 'content':
                    $value = trim($value);
                    break;
                
                default:
                    $inField = false;
               }
               
               if ($inField) {
                    if (!empty($value))
                        $parameter[$field] = $value; 
               }
       }
        if (isset($information['state'])) 
            unset($information['state']);  
       if (!$is_edit) {
            if (empty($information['title']) || empty($information['week']) || empty($information['day']) || empty($information['class']) || empty($information['time']))
                return false;
       } 
                
       return true;

    }
}