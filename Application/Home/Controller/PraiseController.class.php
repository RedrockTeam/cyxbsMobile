<?php
namespace Home\Controller;
use Think\Controller;

class PraiseController extends Controller {
    public function addone(){
        $praise_id = I('post.article_id');
        $articletypes_id = I('post.type_id');
        if($praise_id == null || $articletypes_id == null){
            returnJson(801, '', array('state' => 801));
        }

        $user = M('users');
        $condition_user = array(
            "stunum" => I('post.stuNum'),
        );
        $user_exist = $user->where($condition_user)->find();
        if(!$user_exist){
            returnJson(801, '',array('data'=>array(), 'state'=>801));
        }
        $praise = M('articlepraises');

        $condition = array(
            "article_id" => $praise_id,
            "stunum"     => I('post.stuNum'),
            'articletype_id'=>$articletypes_id
        );

        $result = $praise->where($condition)->find();
        if($result){
            returnJson(404, 'praised',array('data'=>array(), 'state'=>404));
        }
        $articleTable = getArticleTable($articletypes_id);

        $condition = array(
            "id"  => $praise_id,
        );
        D($articleTable)->where($condition)->setInc('like_num');
        if ($articletypes_id != 6) {
            $hotarticle = M('hotarticles');
            $condition['articletype_id'] = $articletypes_id;
            $hotarticle->where($condition)->setInc('like_num');
        }
        if ($articletypes_id == 7) {
            $topic_id = D($articleTable)->where($condition)->getField('topic_id');
            D('topics')->where(array('id'=>$topic_id))->setInc('like_num');
        }
        $content = array(
            "article_id" => $praise_id,
            "stunum"     => I('post.stuNum'),
            "created_time" => date("Y-m-d H:i:s", time()),
            "update_time"  => date("Y-m-d H:i:s", time()),
            "articletype_id"    => $articletypes_id
        );
        $condition_all = array(
            "article_id" => $praise_id,
            "articletype_id" => I('post.type_id')
        );
        $praise->add($content);
        $num = $praise->where($condition_all)->count();


        returnJson(200, '', array('like_num'=>$num,'state'=>200));

    }
    
    public function cancel(){
        $praise_id = I('post.article_id');
        $articletypes_id = I('post.type_id');
        if($praise_id == null || $articletypes_id == nul){
            returnJson(801, '', array('state' => 801));
        }
        $praise = M('articlepraises');
        $condition = array(
            "article_id" => $praise_id,
            "stunum"     => I('post.stuNum'),
            "articletype_id" => $articletypes_id
        );
        $result = $praise->where($condition)->find();
        if(!$result){
            returnJson(404, 'praised',array('data'=>array(), 'state'=>404));
        }
        $articleTable = getArticleTable($articletypes_id);

        $condition = array(
            "id"  => $praise_id,
        );
        D($articleTable)->where($condition)->setDec('like_num');
        if ($articletypes_id != 6) {
            $hotarticle = M('hotarticles');
            $condition['articletype_id'] = $articletypes_id;
            $hotarticle->where($condition)->setDec('like_num');
        }
        if ($articletypes_id == 7) {
            $topic_id = D($articleTable)->where($condition)->getField('topic_id');
            D('topics')->where(array('id'=>$topic_id))->setDec('like_num');
        }
        $content = array(
            "article_id" => $praise_id,
            "stunum"     => I('post.stuNum'),
            "articletype_id"    => $articletypes_id
        );
        $condition_all = array(
            "article_id" => $praise_id,
            "articletype_id" => I('post.type_id')
        );
        $praise->where($content)->delete();
        $num = $praise->where($condition_all)->count();

        returnJson(200, '', array('like_num'=>$num,'state'=>200));
    }
}