<?php
namespace Home\Controller;
use Think\Controller;

class PraiseController extends BaseController {
    public function addone(){
        $praise_id = I('post.id');
        $articletypes_id = I('post.type_id');
        if($praise_id == null){
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
            $praise->add($condition);
            $condition_all = array(
                "article_id" => $praise_id,
                "articletypes_id" => I('post.type_id')
            );
            $hotarticle->where($condition_all)->setInc('hot_num');
            $num = $praise->where($condition_all)->find();
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