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
        $all_info['stunum'] = $all_info['stuNum'];
        $all_info['idnum'] = $all_info['idNum'];
        $all_info['updated_time'] = date;
        unset($all_info['stuNum']);
        unset($all_info['idNum']);
        $all_info = array_filter($all_info);
        $user  = M('users');
        $user_condition = array(
                "stunum" => I('post.stuNum')
            );
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