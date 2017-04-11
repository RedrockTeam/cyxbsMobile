<?php
namespace Home\Controller;

use Think\Controller;

class NewArticleRemarkController extends Controller
{
	public function getRemark(){
        $page = I('post.page');
        $size = I('post.size');
        $remark_id = I('post.article_id');
        $type_id   = I('post.type_id');
        if($remark_id< 200 && $type_id == 5) {
            $type_id = 6;
        }
        if($remark_id == null||$type_id == null){
            $info = array(
                    'state' => 801,
                    'status' => 801,
                    'info'  => 'invalid parameter',
                    'data'  => array(),
                );
            echo json_encode($info,true);
            exit;
        }
        $remark = M('articleremarks');
        $condition = array(
            "cyxbsmobile_articleremarks.article_id" => $remark_id,
            "cyxbsmobile_articleremarks.articletypes_id" => $type_id,
            "state"     => 1
        );
        if($page != null ){
            $page = empty($page) ? 0 : $page;
            $size = empty($size) ? 15 : $size;
            $start = $page*$size;
            $result = $remark
                ->join('cyxbsmobile_users ON cyxbsmobile_articleremarks.user_id =cyxbsmobile_users.id')
                ->where("cyxbsmobile_articleremarks.article_id = '$remark_id' and cyxbsmobile_articleremarks.articletypes_id = '$type_id'")
                ->order('created_time DESC')
                ->field('stunum,nickname,username,photo_src,photo_thumbnail_src,cyxbsmobile_articleremarks.created_time,content,answer_user_id')
                ->limit($start,$size)
                ->select();
        }else{
            $result = $remark
                ->join('cyxbsmobile_users ON cyxbsmobile_articleremarks.user_id =cyxbsmobile_users.id')
                ->where("cyxbsmobile_articleremarks.article_id = '$remark_id' and cyxbsmobile_articleremarks.articletypes_id = '$type_id'")
                ->order('created_time DESC')
                ->field('stunum,nickname,username,photo_src,photo_thumbnail_src,cyxbsmobile_articleremarks.created_time,content,answer_user_id')
                ->select();
        }
       	$info = array(
                    'state' => 200,
                    'status' => 200,
                    'data'  => $result,
                );
        echo json_encode($info,true);
    }
}