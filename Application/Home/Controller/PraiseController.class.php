<?php
namespace Home\Controller;
use Think\Controller;

class PraiseController extends Controller {
    public function addone(){
        $praise_id = I('post.article_id');
        $articletypes_id = I('post.type_id');
        if($praise_id == null || $articletypes_id == null){
            
            $info = array(
                    'state' => 801,
                    'status' => 801,
                    'info'  => 'invalid parameter',
                    'data'  => array(),
                );
            echo json_encode($info,true);
            exit;
        }

        $user = M('users');
        $condition_user = array(
            "stunum" => I('post.stuNum'),
        );
        $user_exist = $user->where($condition_user)->find();
        if(!$user_exist){
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
            "stunum"     => I('post.stuNum'),
            'articletype_id'=>$articletypes_id
        );

        $result = $praise->where($condition)->find();
        if($result){
            $info = array(
                    'state' => 404,
                    'status' => 404,
                    'info'  => 'praised',
                    'data'  => array(),
                );
        }else{
            $hotarticle = M('hotarticles');
            if($articletypes_id == 6){
                $notice = M('notices');
                $condition = array(
                    "id"  => $praise_id,
                );
                $notice->where($condition)->setInc('like_num');
                $content = array(
                    "article_id" => $praise_id,
                    "stunum"     => I('post.stuNum'),
                    "created_time" => date("Y-m-d H:i:s", time()),
                    "updated_time"  => date("Y-m-d H:i:s", time()),
                    "articletype_id"    => $articletypes_id
                );
                $condition_all = array(
                    "article_id" => $praise_id,
                    "articletype_id" => I('post.type_id')
                );
                $praise->add($content);
                $num = $praise->where($condition_all)->count();
                $info = array(
                        'state' => 200,
                        'status' => 820001,
                        'like_num'  => $num,
                    );
                echo json_encode($info,true);
                exit;
            }elseif($articletypes_id > 4){
                $article = M('articles');
                $condition = array(
                    "id"  => $praise_id,
                );
                $article->where($condition)->setInc('like_num');
            }else{
                $news = M('news');
                $condition = array(
                    "id"  => $praise_id,
                );
                $news->where($condition)->setInc('like_num');
            }
            $content = array(
                "article_id" => $praise_id,
                "stunum"     => I('post.stuNum'),
                "created_time" => date("Y-m-d H:i:s", time()),
                "updated_time"  => date("Y-m-d H:i:s", time()),
                "articletype_id"    => $articletypes_id
            );
            $praise->add($content);
            $condition_all = array(
                "article_id" => $praise_id,
                "articletype_id" => I('post.type_id')
            );
            $article = M('articles');
            $condition_article = array(
                    "id"  => $praise_id,
                );
            $a = $hotarticle->where($condition_all)->find();
            $hotarticle->where($condition_all)->setInc('like_num');
            $num = $praise->where($condition_all)->count();
            $info = array(
                    'state' => 200,
                    'status' => 200,
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
                    'status' => 801,
                    'info'  => 'invalid parameter',
                    'data'  => array(),
                );
            echo json_encode($info,true);
            exit;
        }
        $praise = M('articlepraises');
        $condition = array(
            "article_id" => $praise_id,
            "stunum"     => I('post.stuNum'),
            "articletype_id" => $articletypes_id
        );
        $result = $praise->where($condition)->find();
        if(!$result){
            $info = array(
                    'state' => 404,
                    'status' => 404,
                    'info'  => 'praised',
                    'data'  => array(),
                );
        }else{
            $hotarticle = M('hotarticles');
            if($articletypes_id == 6){
                $notices = M('notices');
                $condition = array(
                    "id"  => $praise_id,
                );
                $condition_praise = array(
                        "articletype_id" => I('post.type_id'),
                        "stunum"         => I('post.stuNum'),
                        "article_id"     => $condition['id']
                    );
                $praise->where($condition_praise)->delete(); 
                $article->where($condition)->setDec('like_num');

                $condition_all = array(
                    "article_id" => $praise_id,
                    "articletype_id" => I('post.type_id')
                );
                $num = $praise->where($condition_all)->count();
                $info = array(
                    'state' => 200,
                    'status' => 200,
                    'like_num'  => $num,
                );
                echo json_encode($info,true);exit;
            }elseif($articletypes_id > 4 && $articletypes_id != 6){
                $article = M('articles');
                $condition = array(
                    "id"  => $praise_id,
                );
                $article->where($condition)->setDec('like_num');
            }
            $condition_all = array(
                "article_id" => $praise_id,
                "articletype_id" => I('post.type_id')
            );
            $article = M('articles');
            $condition_article = array(
                    "id"  => $praise_id,
                );
            $condition_praise['articletype_id'] = I('post.type_id');
            $condition_praise['stunum'] = I('post.stuNum');
            $condition_praise['article_id'] = $condition['id'];
            $praise->where($condition_praise)->delete(); 
            $b = $hotarticle->where($condition_all)->setDec('like_num');
            $num = $praise->where($condition_all)->count();
            $info = array(
                    'state' => 200,
                    'status' => 200,
                    'like_num'  => $num,
                );

        }
        echo json_encode($info,true);
    }

    public function _empty() {
        $this->display('Empty/index');
    }
}