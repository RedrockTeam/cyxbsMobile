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
                $stuNum = I('post.stuNum');
                $exist = $this->is_my_like($value['article_id'], 6, $stuNum);
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
        $data = $hotArticle->where("created_time > '$now_date'")->order('((remark_num-self_remark_num)*2+like_num) DESC,updated_time DESC')->limit($start,$size)->relation(true)->select();
        foreach ($data as $key => $value) {
            $condiion_articles = array(
                "id" => $data[$key]['article_id'],
                );
            if($data[$key]['Articletypes']['typename'] == null){

            } elseif ($data[$key]['articletype_id'] < 5) {
                $article = M("news");
                $praise  = M('articlepraises');
                $stuNum = I('post.stuNum');
                $exist = $this->is_my_like($value['article_id'], $value['articletype_id'], $stuNum);
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
                $value['is_my_like'] = $this->is_my_like($value['id'], $value['articletype_id'], $stuNum);
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
            'type_id' => $type,
            'cyxbsmobile_articles.state'   => array('neq', 0),
        );
        // ->order('updated_time DESC')->limit($start,$start+15)->field('user_id,title,id,photo_src,thumbnail_src,type_id,content,updated_time,created_time,like_num,remark_num')
        $content = $article->where($condition)->join('cyxbsmobile_users ON cyxbsmobile_articles.user_id = cyxbsmobile_users.id')->field('cyxbsmobile_articles.title,cyxbsmobile_articles.id,cyxbsmobile_articles.photo_src as article_photo_src,cyxbsmobile_articles.thumbnail_src as article_thumbnail_src,cyxbsmobile_articles.type_id,cyxbsmobile_articles.content,cyxbsmobile_articles.updated_time,cyxbsmobile_articles.created_time,like_num,remark_num,cyxbsmobile_users.stunum,cyxbsmobile_users.nickname,cyxbsmobile_users.photo_src,cyxbsmobile_users.photo_thumbnail_src  ')->limit($start,$size)->order('updated_time DESC')->select();
        $praise  = M('articlepraises');
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
        // $condition = array(
        //     'articletype_id' => $type
        // );
        // ->order('updated_time DESC')->limit($start,$start+15)->field('user_id,title,id,photo_src,thumbnail_src,type_id,content,updated_time,created_time,like_num,remark_num')
        $content = $article->where($condition)->limit($start,$size)->order('id DESC')->select();

        $praise  = M('articlepraises');
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
                    'state'   => array('neq', 0),

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
        $type_id = I('post.type_id');
        $stunum = I('post.stuNum');
        $article_id = I('post.article_id');

        if ( $article_id == null || $type_id == null) {
            $info = array(
                "status" => 801,
                "info"   => "invalid parameter"
            );
            echo json_encode($info);exit;
        } else {
            $condition_user = array(
                "stunum" => $stunum
            );
            if($type_id >= 5) {
                $articletable = EditController::$table_type[$type_id];
                
                if (empty($articletable)) {
                    returnJson(404, 'error type_id');
                }

                $article    = D($articletable);
                //条件
                $condition_article = array(
                        $articletable.'.id'      => $article_id,
                        $articletable.'.type_id' => $type_id,
                        $articletable.'.state'   => array('neq', 0),
                    );
                //显示的参数
                $displayField = array(
                    $articletable.'.id',
                    'content',
                    'nickname',
                    'remark_num',
                    'like_num',
                    $articletable.'.photo_src',
                    $articletable.'.thumbnail_src',
                    $articletable.'.type_id',
                    $articletable.'.updated_time',
                    $articletable.'.created_time',
                    $user.'.photo_src' => 'user_photo'
                );
                $content = $article
                            ->alias($articletable)
                            ->where($condition_article)
                            ->join('cyxbsmobile_users users ON '.$articletable.'.user_id = users.id')
                            ->field($displayField)
                            ->select();
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
                 returnJson(801);
             }

            foreach ($content as $key => &$value) {
                $value['is_my_like'] = $this->is_my_like($article_id, $type_id, $stunum);
            }
            $info = array(
                'status' => '200',
                "info"   => "success",
                'data'   => $content
            );
            echo json_encode($info);
        }
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

    /**
     * 话题范围
     * @return [type] [description]
     */
    public function  topicList()
    {
        $post = I('post.');
        $get = I('get.');
        $information = array_merge($get, $post);
        $information['page'] = isset($information['page']) ? $information['page'] : 0;
        $information['size'] = isset($information['size']) ? $information['size'] : 3;
        
        $displayField = array(
            "topics.id" => "topic_id",
            "stunum" => 'user_id',
            "knickname",
            "title",
            'content',
            'keyword',
            'topics.photo_src',
            'topics.thumbnail_src',
            'join_num',
            'like_num',
            'article_num',
        );
        $pos = array(
            "keyword" => array('like', $information['key'].'%'),
            'created_time' => array('ELT', date('Y-m-d H:i:s')),
            "state"   => 1,
            );
        //话题信息的查询
        $data = M('topics')
                    ->alias('topics')
                    ->join('__USERS__ ON topics.user_id=__USERS__.id', 'LEFT')
                    ->where($pos)
                    ->filed($displayField)
                    ->order('created_time')
                    ->limit($information['page']*$information['size'], $information['size'])
                    ->select();
        
        foreach ($data as $key => &$value) {
            $value['img']['img_small_src'] = $value['photo_src'];
            $value['img']['img_src'] = $value['thumbnail_src'];
            
            $value['is_my_like)'] = $this->is_my_like($value['id'], $value['type_id'], $information['stuNum']);
            
            unset($value['photo_src']);
            unset($value['thumbnail_src']);
            $value['content'] = array("content" => $value['content']);
        }
        returnJson(200, '', compact($data, $vertion));
    }

    /**
     * 话题文章列表
     */
    public function listTopicArticle()
    {
        //兼容post和get请求
        $post = I('post.');
        $get = I('get.');
        $information = array_merge($post, $get);
        $information['page'] = isset($information['page']) ? $information['page'] : 0;
        $information['size'] = isset($information['size']) ? $information['size'] : 10;
        
        $article_alias = 'article';
        $user_alias = 'user';
        
        $displayField = array(
            $article_alias.'.id',
            'type_id',
            $article_alias.'.photo_src'     => 'article_photo_src',
            $article_alias.'.thumbnail_src' => 'article_thumbnail_src',
            'content',
            'nickname',
            'stunum',
            $user_alias.'.photo_src'        => 'photo_src',
            $user_alias.'.thumbnail_src'    => 'photo_thumbnail_src',
            'like_num',
            'remark_num',
        );
        //话题
        $topic = M('topics')->field(true, 'state')->find($information['topic_id']);
        //user_id为0时使用官方的身份
        if ($topic['user_id'] == 0) {
            $topic['nickname'] = "红岩网校工作站";
            $topic['photo_src'] = "http://".$site.'/cyxbsMobile/Public/HONGY.jpg';
        } else {
            $author = M('users')->find($topic['user_id']);
            $topic['nickname'] = $user['nickname'];
            $topic['photo_src'] = $user['photo_src'];
        }
        unset($topic['user_id']);
        if (!$topic) {
            returnJson(404, 'error topic\'s id');
        }
        $pos = array('topic_id' => $topic['id'], 'state'=> 1);
        $articles = M('topicarticles')
                    ->alias($article_alias)
                    ->join('__USERS__ '.$user_alias.' ON '.$user_alias.'.id='.$article_alias.'.user_id', "RIGHT")
                    ->where($pos)
                    ->field($displayField)
                    ->order('updated_time DESC')
                    ->limit($information['page']*$information['size'], $information['size'])
                    ->select();

        $praise = M('articlepraises');
        
        foreach ($data as $key => $value) {
            if($value['user_id'] == 0) {
                $value['nickname'] = "红岩网校工作站";
                $value['photo_src'] = "http://".$site.'/cyxbsMobile/Public/HONGY.jpg';
                $value['thumbnail_src'] = "http://".$site.'/cyxbsMobile/Public/HONGY.jpg';
            }
            $value['is_my_like)'] = $this->is_my_like($value['id'], $value['type_id'], $information['stuNum']);
        }

        $topic['articles'] = $articles;
        returnJson(200, '',array('data' => $topic));
    }


}