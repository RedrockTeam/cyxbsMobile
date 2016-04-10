<?php
namespace Home\Controller;
use Think\Controller;

class ArticleRemarkController extends BaseController {
    public function getRemark(){
        $remark_id = I('post.id');
        if($remark_id == null){
            $info = array(
                    'state' => 801,
                    'info'  => 'invalid parameter',
                    'data'  => array(),
                );
            echo json_encode($info,true);
            exit;
        }
        $remark = M('articleremarks');
        $condition = array(
            "id" => $remark_id,
        );
        $result = $remark
        		->join('cyxbsmobile_users ON cyxbsmobile_articleremarks.user_id =cyxbsmobile_users.id')
        		->where("cyxbsmobile_articleremarks.user_id = '$remark_id'")
        		->field('stunum,nickname,username,photo_src,photo_thumbnail_src,create_time,content')
        		->select();
       	$info = array(
                    'state' => 200,
                    'info'  => 'invalid parameter',
                    'data'  => $result,
                );
        echo json_encode($info,true);
    }

    public postRemarks(){

    }

    public function _empty() {
        $this->display('Empty/index');
    }
}