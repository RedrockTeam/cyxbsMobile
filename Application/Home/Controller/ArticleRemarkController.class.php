<?php
namespace Home\Controller;
use Think\Controller;

class ArticleRemarkController extends BaseController {
    public function getRemark(){
        $remark_id = I('post.article_id');
        $type_id   = I('post.type_id');
        if($remark_id == null||$type_id == null){
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
            "article_id" => $remark_id,
        );
        $result = $remark
        		->join('cyxbsmobile_users ON cyxbsmobile_articleremarks.user_id =cyxbsmobile_users.id')
        		->where("cyxbsmobile_articleremarks.article_id = '$remark_id' and cyxbsmobile_articleremarks.articletypes_id = '$type_id'")
                ->order('created_time DESC')
        		->field('stunum,nickname,username,photo_src,photo_thumbnail_src,cyxbsmobile_articleremarks.created_time,content')
        		->select();
       	$info = array(
                    'state' => 200,
                    'data'  => $result,
                );
        echo json_encode($info,true);
    }

    public function postRemarks(){
        $content = I('post.content');
        $article_id = I('post.article_id');
        $type_id    = I('post.type_id');
        if($content == null || $type_id == null || $article_id == null){
            $info = array(
                    'state' => 801,
                    'info'  => 'invalid parameter',
                    'data'  => array(),
                );
            echo json_encode($info,true);
            exit;
        }else{
            $remark = M('articleremarks');
            $user = M('users');
            $condition = array(
                    "stunum"  => I('post.stuNum')
                );
            $user_id = $user->where($condition)->field('id')->find();
            $content = array(
                "content"         => $content,
                "created_time"    =>  date("Y-m-d H:i:s", time()),
                "user_id"         =>  $user_id['id'],
                "article_id"      => $article_id,
                "articletypes_id" => $type_id,
            );
            $remark->add($content);
            $info = array(
                    'state' => 200,
                );
            echo json_encode($info,true);
        }
    }

    public function _empty() {
        $this->display('Empty/index');
    }
}