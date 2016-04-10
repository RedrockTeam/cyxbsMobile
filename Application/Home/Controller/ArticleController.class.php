<?php
namespace Home\Controller;
use Think\Controller;

class ArticleController extends Controller {
    protected    $newsList = array('jwzx','cyxw','xsjz','xwgg');
    public function index(){
        $article = D("hotarticles");
        $articles = $article->relation(true)->select();
    }

    public function listArticle(){
        $type = I('post.type_id');
        $page = I('post.page');
        $size = I('post.size');
        $page = empty($page) ? 0 : $page;
        $size = empty($size) ? 15 : $size;
        $start = $page*$size;
        if($type == null){
            $info = array(
                    'state' => 801,
                    'info'  => 'invalid parameter',
                    'data'  => array(),
                );
            echo json_encode($info,true);
            exit;
        }
        $articleType = D('articletypes');
        $article     = D('articles');
        $condition = array(
            'type_id' => $type
        );
        $content = $article->where($condition)->order('updated_time DESC')->limit($start,$start+15)->field('id,photo_src,thumbnail_src,content,updated_time,created_time,like_num,remark_num')->select();

        $praise  = M('articlepraises');
        $praise_condition = array(
            "articletypes_id" => $content['type_id'],
            "article_id"      => $content['id'],
            "stunum"          => I('post.stuNum')
        );
        $praise_exist = $praise->where($praise_condition)->find();
        if($praise_exist){
            $content['is_my_like'] = true;
        }else{
            $content['is_my_like'] = false;
        }


        $info = array(
                'status' => '200',
                "page"   => $page,
                'data'   => $content
        );
        echo json_encode($info);
    }

    public function searchHotArticle(){
        $hotArticle = D("hotarticles");
        $article = D("articles");
        $user = D('users');
        $page = I('post.page');
        $size = I('post.size');
        $page = empty($page) ? 0 : $page;
        $size = empty($size) ? 15 : $size;
        $start = $page*$size;
        $info = array();
        $data = $hotArticle->order('like_num DESC')->limit($start,$start+15)->relation(true)->select();
        foreach ($data as $key => $value) {
            $condiion_articles = array(
                "id" => $data[$key]['article_id'],
                );
            if($data[$key]['articletype_id'] < 5){
                $article = M($data[$key]['Articletypes']['typename']);
                $praise  = M('articlepraises');
                $praise_condition = array(
                    "articletypes_id" => $data[$key]['articletypes_id'],
                    "article_id"      => $data[$key]['article_id'],
                    "stunum"          => I('post.stuNum')
                );
                $praise_exist = $praise->where($praise_condition)->find();
                if($praise_exist){
                    $exist = true;
                }else{
                    $exist = false;
                }
                $articles = $article->where($condiion_articles)->find();
                $now_info = array(
                    'status' => 200,
                    'page'   => $page,
                    'data'   =>array(
                                'type'      => $data[$key]['Articletypes']['typename'],
                                'id'        => $data[$key]['articletype_id'],
                                'user_id'   => '',
                                'user_name' =>'',
                                'user_head' =>'',
                                'time'      => $articles['date'],
                                'content'   => $articles,
                                'img'       => array(
                                                'img_small_src' => $articles['thumbnail_src'],
                                                'img_src' => $articles['photo_src'],
                                            ),
                                'like_num'  => $value['like_num'],
                                'remark_num'=> $value['remark_num'],
                                "is_my_Like"=> $exist,
                            ),
                );
                array_push($info,$now_info);
            }else{
                $article = D('articles');
                $praise  = M('articlepraises');
                $articlePhoto  = M('articlephoto');
                $articles = $article->where($condiion_articles)->relation(true)->find();
                $praise_condition = array(
                    "articletypes_id" => $data[$key]['articletypes_id'],
                    "article_id"      => $data[$key]['article_id'],
                    "stunum"          => I('post.stuNum')
                );
                $praise_exist = $praise->where($praise_condition)->find();
                if($praise_exist){
                    $exist = true;
                }else{
                    $exist = false;
                }
                $photo_content = $articlePhoto->where($photo_condition)->select();
                $now_info = array(
                    'status' => 200,
                    'page'   => $page,
                    'data'   =>array(
                                'type'      => $data[$key]['Articletypes']['typename'],
                                'id'        => $data[$key]['articletype_id'],
                                'user_id'   => $articles['Users']['stunum'],
                                'user_name' => $articles['Users']['username'],
                                'user_head' => '',
                                'time'      => $articles['created_time'],
                                'content'   => $articles['content'],
                                'img'       => array(
                                                'img_small_src' => $articles['thumbnail_src'],
                                                'img_src' => $articles['photo_src'],
                                            ),
                                'like_num'  => $value['like_num'],
                                'remark_num'=> $value['remark_num'],
                                "is_my_Like"=> $exist,
                            ),
                );
                array_push($info,$now_info);
            }
        }
        echo json_encode($info);
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