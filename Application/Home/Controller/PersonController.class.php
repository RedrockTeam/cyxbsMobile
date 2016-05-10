<?php
namespace Home\Controller;
use Think\Controller;

class PersonController extends BaseController {
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

        $bank_array = array('redrock','管理员','红岩','红岩网校工作站','重邮','重庆邮电大学','cqupt');

        $test_nickname = trim($all_info['nickname']);

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
            $user = M('user');
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
}