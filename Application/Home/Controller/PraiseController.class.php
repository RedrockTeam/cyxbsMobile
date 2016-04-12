<?php
namespace Home\Controller;
use Think\Controller;

class PraiseController extends BaseController {
    public function addone(){
        $praise_id = I('post.article_id');
        $articletypes_id = I('post.type_id');
        if($praise_id == null || $articletypes_id == nul){
            $info = array(
                    'state' => 801,
                    'info'  => 'invalid parameter',
                    'data'  => array(),
                );
            echo json_encode($info,true);
            exit;
        }
        $praise = M('articlepraises');
        $condition = array(
            "article_id" => $praise_id,
            "stunum"     => I('post.stuNum')
        );
        $result = $praise->where($condition)->find();
        if($result){
            $info = array(
                    'state' => 404,
                    'info'  => 'praised',
                    'data'  => array(),
                );
        }else{
            $hotarticle = M('hotarticles');
            if($praise_id > 5){
                $article = M('articles');
                $condition = array(
                    "id"  => $praise_id,
                );
                $article->where($condition)->setInc('like_num');
            }
            $content = array(
                "article_id" => $praise_id,
                "stunum"     => I('post.stuNum'),
                "created_time" => date("Y-m-d H:i:s", time()),
                "update_time"  => date("Y-m-d H:i:s", time()),
                "articletype_id"    => $articletypes_id
            );
            $praise->add($content);
            $condition_all = array(
                "article_id" => $praise_id,
                "articletypes_id" => I('post.type_id')
            );
            $article = M('articles');
            $condition_article = array(
                    "id"  => $praise_id,
                );
            $article->where($condition_article)->setInc('like_num');
            $hotarticle->where($condition_all)->setInc('hot_num');
            $num = $praise->where($condition_all)->find();
            $info = array(
                    'state' => 200,
                    'like_num'  => $num,
                );
        }
        echo json_encode($info,true);
    }
    
    public function cancel(){
        $praise_id = I('post.article_id');
        $articletypes_id = I('post.type_id');
        if($praise_id == null || $articletypes_id == nul){
            $info = array(
                    'state' => 801,
                    'info'  => 'invalid parameter',
                    'data'  => array(),
                );
            echo json_encode($info,true);
            exit;
        }
        $praise = M('articlepraises');
        $condition = array(
            "article_id" => $praise_id,
            "stunum"     => I('post.stuNum')
        );
        $result = $praise->where($condition)->find();
        if(!$result){
            $info = array(
                    'state' => 404,
                    'info'  => 'praised',
                    'data'  => array(),
                );
        }else{
            $hotarticle = M('hotarticles');
            if($praise_id > 5){
                $article = M('articles');
                $condition = array(
                    "id"  => $praise_id,
                );
                $article->where($condition)->setDec('like_num');
            }
            $condition_all = array(
                "article_id" => $praise_id,
                "articletypes_id" => I('post.type_id')
            );
            $article = M('articles');
            $condition_article = array(
                    "id"  => $praise_id,
                );
            $article->where($condition_article)->setDec('like_num');
            $praise->where($condition)->delete();
            $hotarticle->where($condition_all)->setDec('hot_num');
            $num = $praise->where($condition_all)->count();
            $info = array(
                    'state' => 200,
                    'like_num'  => $num,
                );
        }
        echo json_encode($info,true);
    }

    public function _empty() {
        $this->display('Empty/index');
    }
}