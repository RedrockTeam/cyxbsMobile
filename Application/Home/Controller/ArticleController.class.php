<?php
namespace Home\Controller;
use Think\Controller;

class ArticleController extends Controller {
    protected    $newsList = array('jwzx','cyxw','xsjz','xwgg');
    public function index(){
        $article = D("hotarticles");
        $articles = $article->relation(true)->select();
        var_dump($articles);
    }

    public function searchArticle(){
        $hotArticle = D("hotarticles");
        $article = D("articles");
        $user = D('users');
        $page = I('post.page');
        $size = I('post.size');
        $page = empty($page) ? 0 : $page;
        $size = empty($size) ? 15 : $size;
        $start = $page*15;
        $info = array();
        $data = $hotArticle->order('hot_num DESC')->limit($start,$start+15)->relation(true)->select();
        foreach ($data as $key => $value) {
            $condiion_articles = array(
                "id" => $data[$key]['article_id'],
                );
            if($data[$key]['articletypes_id'] < 5){
                $article = M($data[$key]['Articletypes']['typename']);
                $articles = $article->where($condiion_articles)->find();
                $now_info = array(
                    'status' => 200,
                    'page'   => $page,
                    'data'   =>array(
                                'type'      => $data[$key]['Articletypes']['typename'],
                                'id'        => $data[$key]['articletypes_id'],
                                'user_id'   => '',
                                'user_name' =>'',
                                'user_head' =>'',
                                'time'      => $articles['date'],
                                'content'   => $articles,
                                'img'       => '',

                            ),
                );
                array_push($info,$now_info);
            }else{
                $article = D('articles');
                $articles = $article->where($condiion_articles)->relation(true)->find();
                $now_info = array(
                    'status' => 200,
                    'page'   => $page,
                    'data'   =>array(
                                'type'      => $data[$key]['Articletypes']['typename'],
                                'id'        => $data[$key]['articletypes_id'],
                                'user_id'   => $articles['Users']['stunum'],
                                'user_name' => $articles['Users']['username'],
                                'user_head' => '',
                                'time'      => $articles['created_time'],
                                'content'   => $articles['content'],
                                'img'       => '',

                            ),
                );
                array_push($info,$now_info);
            }
        }
        var_dump($info);
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