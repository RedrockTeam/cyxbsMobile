<?php

namespace Home\Controller;

use Think\Controller;

class NewArticleController extends Controller
{
	protected    $newsList = array('jwzx','cyxw','xsjz','xwgg');

    public function index(){
        $article = D("hotarticles");
        $articles = $article->relation(true)->select();
    }
	public function searchHotArticle() {
		
        $hotArticle = D("hotarticles");
		$article = D("articles");
        $praise  = M('articlepraises');
        $user = D('users');
        $page = I('post.page');
        $size = I('post.size');
        $page = empty($page) ? 0 : $page;
        $size = empty($size) ? 15 : $size;
        $start = $page*$size;
        $info = array();
        $now_date = date("Y-m-d H:i:s",mktime(0,0,0,date("m"),date("d")-7,date("Y")));
        
        if ($page == 0) {
            $notice = M('notices');
            $data_notice_condition = array(
                    "created_time" => array('GT',$now_date),
                    "state"        => 1,
                );
            $data_notice   = $notice->where($data_notice_condition)->order('created_time')->select();
            $site = $_SERVER["SERVER_NAME"];
            foreach ($data_notice as $key => $value) {
                $praise_condition = array(
                    "articletype_id" => "6",
                    "article_id"      => $data_notice[$key]['id'],
                    "stunum"          => I('post.stuNum')
                );
                $stuNum = I('post.stuNum');
                if(!empty($stuNum)){
                	$praise_exist = $praise->where($praise_condition)->find();
                	if($praise_exist){
                    	$exist = true;
                	}else{
                    	$exist = false;
                	}
                } else {
                	$exist = false;
                }
                $now_info = array(
                    'status' => 200,
                    'page'   => $page,
                    'data'   =>array(
                                'id'        => $data_notice[$key]['id'],
                                'type'      => "notice",
                                'type_id'   => "6",
                                'article_id'=> $value['id'],
                                'user_id'   => "0",
                                'nick_name' => "红岩网校工作站",
                                'user_head' => "http://".$site.'/cyxbsMobile/Public/HONGY.jpg',
                                'time'      => $value['created_time'],
                                'content'   => array(
                                                    "content" =>$value['content'],
                                                ),
                                'img'       => array(
                                                'img_small_src' => $value['thumbnail_src'],
                                                'img_src' => $value['photo_src'],
                                            ),
                                'like_num'  => $value['like_num'],
                                'remark_num'=> $value['remark_num'],
                                "is_my_Like"=> $exist,
                            ),
                );
               array_push($info,$now_info);
            }
        }
        $info = array_reverse($info);
        $data = $hotArticle->where("created_time > '$now_date'")->order('(remark_num*2+like_num) DESC,updated_time DESC')->limit($start,$size)->relation(true)->select();
        foreach ($data as $key => $value) {
            $condiion_articles = array(
                "id" => $data[$key]['article_id'],
                );
            if($data[$key]['Articletypes']['typename'] == null){

            } elseif ($data[$key]['articletype_id'] < 5) {
                $article = M("news");
                $praise  = M('articlepraises');
                $praise_condition = array(
                    "articletype_id"  => $data[$key]['articletype_id'],
                    "article_id"      => $data[$key]['article_id'],
                    "stunum"          => I('post.stuNum')
                );
                $stuNum = I('post.stuNum');
                if(!empty($stuNum)){
	                $praise_exist = $praise->where($praise_condition)->find();
	                if($praise_exist){
	                    $exist = true;
	                }else{
	                    $exist = false;
	                }
	             } else {
	             	$exist = false;
	             } 
                $articles = $article->where($condiion_articles)->find();
                $now_info = array(
                    'status' => 200,
                    'page'   => $page,
                    'data'   =>array(
                                'id'        => $data[$key]['id'],
                                'type'      => $data[$key]['Articletypes']['typename'],
                                'type_id'   => $data[$key]['articletype_id'],
                                'article_id'   => $data[$key]['article_id'],
                                'user_id'   => "",
                                'user_name' => "",
                                'user_head' => "",
                                'time'      => $articles['date'],
                                'content'   => $articles,
                                'img'       => array(
                                                'img_small_src' => "",
                                                'img_src' => "",
                                            ),
                                'like_num'  => $value['like_num'],
                                'remark_num'=> $value['remark_num'],
                                "is_my_Like"=> $exist,
                            ),
                );
                array_push($info,$now_info);
            } else {
                $article = D('articles');
                $praise  = M('articlepraises');
                $articlePhoto  = M('articlephoto');
                $articles = $article->where($condiion_articles)->relation(true)->find();
                $praise_condition = array(
                    "articletypes_id" => $data[$key]['articletypes_id'],
                    "article_id"      => $data[$key]['article_id'],
                    "stunum"          => I('post.stuNum')
                );
                
                $stuNum = I('post.stuNum');
                if(!empty($stuNum)){
	                $praise_exist = $praise->where($praise_condition)->find();
	                if($praise_exist){
	                    $exist = true;
	                }else{
	                    $exist = false;
	                }
	            } else {
	            	$exist = false;
	            }
                $photo_content = $articlePhoto->where($photo_condition)->select();
                $now_info = array(
                    'status' => 200,
                    'page'   => $page,
                    'data'   =>array(
                                'id'        => $data[$key]['id'],
                                'type'      => $data[$key]['Articletypes']['typename'],
                                'type_id'   => $data[$key]['articletype_id'],
                                'article_id'=>$data[$key]['article_id'],
                                'user_id'   => $articles['Users']['stunum'],
                                'nick_name' => $articles['Users']['nickname'],
                                'user_head' => $articles['Users']['photo_src'],
                                'time'      => $articles['created_time'],
                                'content'   => array(
                                                    "content" => $articles['content'],
                                                ),
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
                    'status' => 801,
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
        // ->order('updated_time DESC')->limit($start,$start+15)->field('user_id,title,id,photo_src,thumbnail_src,type_id,content,updated_time,created_time,like_num,remark_num')
        $content = $article->where($condition)->join('cyxbsmobile_users ON cyxbsmobile_articles.user_id = cyxbsmobile_users.id')->field('cyxbsmobile_articles.title,cyxbsmobile_articles.id,cyxbsmobile_articles.photo_src as article_photo_src,cyxbsmobile_articles.thumbnail_src as article_thumbnail_src,cyxbsmobile_articles.type_id,cyxbsmobile_articles.content,cyxbsmobile_articles.updated_time,cyxbsmobile_articles.created_time,like_num,remark_num,cyxbsmobile_users.stunum,cyxbsmobile_users.nickname,cyxbsmobile_users.photo_src,cyxbsmobile_users.photo_thumbnail_src  ')->limit($start,$size)->order('updated_time DESC')->select();
        $praise  = M('articlepraises');
        $result = array();
        foreach($content as $key => $value){
            $praise_condition = array(
                "articletype_id" => $value['type_id'],
                "article_id"      => $value['id'],
                "stunum"          => I('post.stuNum')
            );
            $stuNum = I('post.stuNum');
            if(!empty($stuNum)){
	            $praise_exist = $praise->where($praise_condition)->find();
	            if($praise_exist){
	                $value['is_my_like'] = true;
	            }else{
	                $value['is_my_like'] = false;
	            }
	       } else {
	       	$value['is_my_like'] = false;
	       }    
            array_push($result,$value);
        }


        $info = array(
                'status' => '200',
                "page"   => $page,
                'data'   => $result
        );
        echo json_encode($info);
    }

	 public function listNews() {
        $type = I('post.type_id');
        $page = I('post.page');
        $size = I('post.size');
        $page = empty($page) ? 0 : $page;
        $size = empty($size) ? 15 : $size;
        $start = $page*$size;

        $articleType = D('articletypes');
        $article     = D('news');
        // $condition = array(
        //     'articletype_id' => $type
        // );
        // ->order('updated_time DESC')->limit($start,$start+15)->field('user_id,title,id,photo_src,thumbnail_src,type_id,content,updated_time,created_time,like_num,remark_num')
        $content = $article->where($condition)->limit($start,$size)->order('id DESC')->select();

        $praise  = M('articlepraises');
        $result = array();
        foreach($content as $key => $value) {
            $praise_condition = array(
                "articletype_id"  => $value['articletype_id'],
                "article_id"      => $value['id'],
                "stunum"          => I('post.stuNum')
            );
            $stuNum = I('post.stuNum');
            if(!empty($stuNum)){
            	$praise_exist = $praise->where($praise_condition)->find();
            	if($praise_exist){
                	$value['is_my_like'] = true;
            	}else{
                	$value['is_my_like'] = false;
            	}             
            } else {
            	$value['is_my_like'] = false;
            }
            array_push($result,$value);
        }
         $info = array(
                'status' => '200',
                "page"   => $page,
                'data'   => $result
        );
        echo json_encode($info);
    }

    public function searchTrends() {
        $stunum_other = I('post.stunum_other');
        $type = I('post.type_id');
        $page = I('post.page');
        $size = I('post.size');
        $page = empty($page) ? 0 : $page;
        $size = empty($size) ? 15 : $size;
        $start = $page*$size;
        if($stunum_other == null){
            $stunum = I('post.stuNum');
        }else{
            $stunum = $stunum_other;
        }
        if($stunum == null){
            $info = array(
                "status" => 801,
                "info"   => "invalid parameter"
            );
            echo json_encode($info);exit;
        }else{
            $condition_user = array(
                "stunum" => $stunum
            );
            $user = M('users');
            $user = $user->where($condition_user)->find();
            $article = D('articles');
            $condition_article = array(
                    'user_id' =>$user['id']
                );
            $contents = $article->where($condition_article)->order('updated_time DESC')->limit($start,$size)->field('id,photo_src,thumbnail_src,content,type_id,created_time,updated_time,created_time,like_num,remark_num')->select();
            //判断自己是否点过赞
            $mynum = I('post.stuNum');
            $praise = M('articlepraises');
            foreach ($contents as &$content) {
                if (empty($mynum)) {
                    $content['is_my_like'] = false;
                } else {
                    $position = array(
                        'article_id'=> $content['id'],
                        'type_id'   => $content['type_id'],
                        'stunum'    => $mynum,
                    );
                    $praise_exist = $praise->where($position)->find();
                    if($praise_exist) {
                        $content['is_my_like'] = true;
                    } else {
                        $content['is_my_like'] = false;
                    }
                }
            }
            $info = array(
                'status' => '200',
                "info"   => "success",
                'data'   => $contents
            );
            echo json_encode($info);
        }
    }

    public function searchContent() {
        $type_id = I('post.type_id');
        $stunum = I('post.stuNum');
        $article_id = I('post.article_id');
        if( $article_id == null || $type_id == null){
            $info = array(
                "status" => 801,
                "info"   => "invalid parameter"
            );
            echo json_encode($info);exit;
        }else{
            $condition_user = array(
                "stunum" => $stunum
            );
            if($type_id >= 5) {
                $article    = D('articles');
                $condition_article = array(
                        'cyxbsmobile_articles.id'      => $article_id,
                        'cyxbsmobile_articles.type_id' => $type_id,

                    );
                $content = $article->where($condition_article)->join('cyxbsmobile_users ON cyxbsmobile_articles.user_id = cyxbsmobile_users.id')->field('cyxbsmobile_articles.id,cyxbsmobile_articles.photo_src,cyxbsmobile_articles.thumbnail_src,cyxbsmobile_articles.content,cyxbsmobile_articles.type_id,cyxbsmobile_articles.updated_time,cyxbsmobile_articles.created_time,cyxbsmobile_articles.like_num,cyxbsmobile_articles.remark_num,cyxbsmobile_users.photo_src as user_photo,cyxbsmobile_users.nickname')->select();
            } elseif ($type_id > 0 && $type_id <5) {                  //新闻内容
                $news = D('news');
                $condition_news = array(
                    'id'               => $article_id,
                    'articletype_id'  => $type_id,
                    );
                $field = array(
                    'title',
                    'id',
                    'articletype_id'   => 'type_id',
                    'content',
                    'date',
                    'like_num',
                    'remark_num',
                    'read',
                    );
                $content = $news->where($condition_news)->field($field)->select();
            } else {
                 $info = array(
                "status" => 801,
                "info"   => "invalid parameter"
                 );
            echo json_encode($info);exit;
            }

            foreach ($content as $key => &$value) {
           
                if($value){
                    $mark = empty($stunum);
                    if (!$mark) {
                        $praise_condition = array(
                            'article_id'    => $article_id,
                            'type_id'       => $type_id,
                            'stunum'        => $stunum
                            );
                        $praise = M('articlepraises');
                        $praise_exist = $praise->where($praise_condition)->find();
                        if ($praise_exist) {
                            $value['is_my_like'] = true;
                        } else {
                            $value['is_my_like'] = false;
                        }
                    } else {
                        $value['is_my_like'] = false;
                    }
                }
            }
            $info = array(
                'status' => '200',
                "info"   => "success",
                'data'   => $content
            );
            echo json_encode($info);
        }
    }

}