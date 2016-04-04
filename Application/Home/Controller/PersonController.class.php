<?php
namespace Home\Controller;
use Think\Controller;

class PersonController extends Controller {
    public function search(){
        $user = M('user');
        $condition = array(
            "stunum" => I('post.stuNum')
        );
        $info = $user->where($condition)->field('stunum,intrduction,username,nickname,photosrc,update_time')->find();

    }


    public function setNickname(){
        if(empty(I('post.username'))){
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