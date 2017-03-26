<?php

namespace Home\Controller;

use Home\Common\Article;
use Think\Controller;
use Think\Exception;

class NewArticleController extends Controller
{
	protected    $newsList = array('jwzx','cyxw','xsjz','xwgg');

    public function index(){
        $article = D("hotarticles");
        $articles = $article->relation(true)->select();
    }

    /**
     * 热门动态
     */
    public function searchHotArticle() {
		
        $hotArticle = D("hotarticles");
        $page = I('post.page');
        $size = I('post.size');
        $page = empty($page) ? 0 : $page;
        $size = empty($size) ? 15 : $size;
        $start = $page*$size;
        $stuNum = I('stuNum');
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
                $stuNum = I('post.stuNum');
                $exist = $this->is_my_like($value['article_id'], 6, $stuNum);
                $now_info = array(
                    'status' => 200,
                    'page'   => $page,
                    'data'   =>array(
                                'id'        => $value['id'],
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
        $data = $hotArticle
                        ->where("created_time > '$now_date'")
                        ->order('((remark_num-self_remark_num)*2+like_num) DESC,updated_time DESC')
                        ->limit($start,$size)
                        ->select();
        foreach ($data as $key => $value) {
            $article = array(
                'article_id'=>$value['article_id'],
                'type_id'   => $value['articletype_id']
            );
            $article = Article::setArticle($article);
            //不存在的字段throw exception
            if($article->is_exist() === false)  continue;
            try {
                $time = $article->get('date');
            }catch (Exception $e) {
                $time = $article->get('created_time');
            }
            $photo_src = $article->get("photo_src");
            $small_src = $article->get("thumbnail_src");
            $author = $article->get('author');
            //兼容格式
            $now_info = array(
                'status' => 200,
                'page'   => $page,
                'data'   =>array(
                            'id'        => $value['id'],
                            'type'      => $article->articleType(true),
                            'type_id'   => $value['articletype_id'],
                            'article_id'=> $value['article_id'],
                            'user_id'   => $author ? $author['id'] : '',
                            'nickname' => $author ? $author['nickname'] : '',
                            'user_head' => $author ? $author['photo_src'] : '',
                            'time'      => $time,
                            'content'   => array('content' => $article->get('content')),
                            'img'       => array(
                                            'img_small_src' => $small_src ? $small_src : '',
                                            'img_src' => $photo_src ? $photo_src : '',
                                        ),
                            'like_num'  => $value['like_num'],
                            'remark_num'=> $value['remark_num'],
                            "is_my_Like"=> $stuNum ? $article->getPraise($stuNum) : false,
                        ),
            );
            array_push($info,$now_info);
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
//        $articleType = D('articletypes');
        $article     = D('articles');
        $condition = array(
            'type_id' => $type,
            'cyxbsmobile_articles.state'   => array('neq', 0),
        );
        // ->order('updated_time DESC')->limit($start,$start+15)->field('user_id,title,id,photo_src,thumbnail_src,type_id,content,updated_time,created_time,like_num,remark_num')
        $content = $article
                    ->where($condition)
                    ->join('cyxbsmobile_users ON cyxbsmobile_articles.user_id = cyxbsmobile_users.id')
                    ->field('cyxbsmobile_articles.title,cyxbsmobile_articles.id,cyxbsmobile_articles.photo_src as article_photo_src,cyxbsmobile_articles.thumbnail_src as article_thumbnail_src,cyxbsmobile_articles.type_id,cyxbsmobile_articles.content,cyxbsmobile_articles.updated_time,cyxbsmobile_articles.created_time,like_num,remark_num,cyxbsmobile_users.stunum,cyxbsmobile_users.nickname,cyxbsmobile_users.photo_src,cyxbsmobile_users.photo_thumbnail_src  ')
                    ->limit($start,$size)
                    ->order('updated_time DESC')
                    ->select();

        $result = array();
        foreach($content as $key => $value){
            $stuNum = I('post.stuNum');
            $value['is_my_like'] = $this->is_my_like($value['id'], $value['type_id'], $stuNum);
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
//         $condition = array(
//             'articletype_id' => $type
//         );
        // ->order('updated_time DESC')->limit($start,$start+15)->field('user_id,title,id,photo_src,thumbnail_src,type_id,content,updated_time,created_time,like_num,remark_num')
        $content = $article->limit($start,$size)->order('id DESC')->select();

        $result = array();
        foreach($content as $key => $value) {
            $stuNum = I('post.stuNum');
            $value['is_my_like'] = $this->is_my_like($value['id'], $value['articletype_id'], $stuNum);
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
        $page = empty($page) ? 0  : $page;
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
                    'user_id' =>$user['id'],
                    'state'   => 1,

                );
            $contents = $article->where($condition_article)->order('updated_time DESC')->limit($start,$size)->field('id,photo_src,thumbnail_src,content,type_id,created_time,updated_time,created_time,like_num,remark_num')->select();
            //判断自己是否点过赞
            $mynum = I('post.stuNum');
            $praise = M('articlepraises');
            foreach ($contents as &$content) {
                $content['is_my_like'] = $this->is_my_like($content['id'], $content['type_id'], $mynum);
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
        $information = I('post.');

        if(false === $article = Article::setArticle($information, $information['stuNum']))
            returnJson(404, 'error article');
        $content = $article->getContent();

        $content['is_my_like'] = is_null($information['stuNum'])? false :$article->getPraise($information['stuNum']);
        returnJson(200, '', array('data'=>array($content)));
    }

    /**
     * 判断我是否对该文章点赞
     * @param  number  $article_id 文章的id值
     * @param  number  $type_id    文章类型
     * @param  string  $stunum     学号
     * @return boolean             是否喜欢
     */
    public function is_my_like($article_id, $type_id, $stunum)
    {
        if(empty($stunum)) {
            return false;
        }
        $praise_condition = array(
            'article_id'    => $article_id,
            'articletype_id'=> $type_id,
            'stunum'        => $stunum
            );
        $praise = M('articlepraises');
        $praise_exist = $praise->where($praise_condition)->find();
        if ($praise_exist) {
            return true;
        } else {
            return false;
        }
    }




}