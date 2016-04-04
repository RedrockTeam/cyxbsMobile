<?php
namespace Home\Controller;
use Think\Controller;

class ArticleController extends Controller {
    public function index(){
        $article = D("hotarticles");
        $articles = $article->relation(true)->select();
        var_dump($articles);
    }

    public function searchArticle(){
        $num = I('post.num');
        $page = I('post.page');
    }

    public function praise(){
    	if(I('post.id') == null){
    		$info = array(
                "status" => 801,
                "info"   => "invalid parameter"
            );
    	}else{
    		$praise = M('articlepraise');
    		$condition = array(
    			"stunum"     => I('post.stuNum'),
    			"article_id" => I('post.id')
    		);
    		$goal = $condition->where($condition)->find();
    		if($goal){
    			$info = array(
	                "status" => 404,
	                "info"   => "已赞"
	            );
    		}else{
    			$praise->add($condition);
    		}
    	}
    	echo json_encode($info,true);
    }

    public function _empty() {
        $this->display('Empty/index');
    }
}