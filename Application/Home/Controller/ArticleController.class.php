<?php
namespace Home\Controller;
use Think\Controller;

class ArticleController extends BaseController {
    protected    $newsList = array('jwzx','cyxw','xsjz','xwgg');
    public function index(){
        $article = D("hotarticles");
        $articles = $article->relation(true)->select();
    }

    public function searchContent(){
        $article    = D('articles');
        $type_id = I('post.type_id');
        $stunum = I('post.stuNum');
        $article_id = I('post.article_id');
        if($stunum == null || $article_id == null || $type_id == null){
            $info = array(
                "status" => 801,
                "info"   => "invalid parameter"
            );
            echo json_encode($info);exit;
        }else{
            $condition_user = array(
                "stunum" => $stunum
            );
            $condition_article = array(
                    'id'      => $article_id,
                    'type_id' => $type_id,

                );
            $content = $article->where($condition_article)->field('id,photo_src,thumbnail_src,content,type_id,updated_time,created_time,like_num,remark_num')->select();
            $info = array(
                'status' => '200',
                "info"   => "success",
                'data'   => $content
            );
            echo json_encode($info);
        }
    }

    public function searchTrends(){
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
            $content = $article->where($condition_article)->order('updated_time DESC')->limit($start,$size)->field('id,photo_src,thumbnail_src,content,type_id,created_time,updated_time,created_time,like_num,remark_num')->select();
            $info = array(
                'status' => '200',
                "info"   => "success",
                'data'   => $content
            );
            echo json_encode($info);
        }
    }

    public function aboutMe(){
        $stunum = I('post.stuNum');
        $type = I('post.type_id');
        $page = I('post.page');
        $size = I('post.size');
        $page = empty($page) ? 0 : $page;
        $size = empty($size) ? 15 : $size;
        $start = $page*$size;
        $remark = D('articleremarks');
        $user = M('users');
        $article = D('articles');
        $user_id = $user->where("stunum = '$stunum'")->find();
        $user_id = $user_id['id'];
        $sql = " SELECT 'remark' as type,cyxbsmobile_articleremarks.content as content,cyxbsmobile_articles.content as article_content ,cyxbsmobile_articles.thumbnail_src as article_photo_src ,cyxbsmobile_articleremarks.created_time,cyxbsmobile_articleremarks.article_id,cyxbsmobile_users.stunum,cyxbsmobile_users.nickname,cyxbsmobile_users.photo_src
                FROM (cyxbsmobile_articleremarks JOIN cyxbsmobile_users ON cyxbsmobile_articleremarks.user_id = cyxbsmobile_users.id)JOIN cyxbsmobile_articles
        ON  cyxbsmobile_articleremarks.article_id = cyxbsmobile_articles.id
         WHERE 
            cyxbsmobile_users.stunum != '$stunum' AND
            cyxbsmobile_articleremarks.article_id IN(
                SELECT id FROM cyxbsmobile_articles WHERE user_id = '$user_id'
        ) UNION
        SELECT 'praise' as type,'' as content,cyxbsmobile_articles.content as article_content,cyxbsmobile_articles.thumbnail_src as article_photo_src,cyxbsmobile_articlepraises.created_time,cyxbsmobile_articlepraises.article_id,cyxbsmobile_users.stunum,cyxbsmobile_users.nickname,cyxbsmobile_users.photo_src
        FROM (cyxbsmobile_articlepraises JOIN cyxbsmobile_users ON cyxbsmobile_articlepraises.stunum = cyxbsmobile_users.stunum )JOIN cyxbsmobile_articles
        ON cyxbsmobile_articlepraises.article_id = cyxbsmobile_articles.id
        WHERE 
            cyxbsmobile_users.stunum != '$stunum' AND
            cyxbsmobile_articlepraises.article_id IN(
                SELECT id FROM cyxbsmobile_articles WHERE user_id = '$user_id'
        ) 
            ORDER BY created_time DESC
            limit $start,$size
        ";
        $result = M('')->query($sql);
        $info = array(
                'status' => '200',
                "info"   => "success",
                'data'   => $result
            );
        echo json_encode($info);
    }

    public function addArticle(){
        $data = I('post.');
        if($data['user_id']==null||$data['user_id']==$data['stuNum']||$data['type_id'] == null||$data['type_id'] < 5||$data['type_id'] == 6){
            $info = array(
                    'state' => 801,
                    'status' => 801,
                    'info'  => 'invalid parameter',
                );
            echo json_encode($info,true);
            exit;
        }
        $user = M('users');
        $user_condition = array(
            "stunum" =>$data['stuNum']
        );
        $user_id = $user->where($user_condition)->find();
        $article  = D('articles');
        $article_field = $article->getDbFields();
        foreach ($data as $key => $value) {
            if(!in_array($key, $article_field)){
                unset($data[$key]);
            }
        }
        $data['user_id'] = $user_id['id'];
        $data['created_time'] = date("Y-m-d H:i:s", time());
        $data['updated_time'] = date("Y-m-d H:i:s", time());
        $article_check = $article->add($data);
        $hotarticles = M('hotarticles');
        $content = $data;
        $content['articletype_id'] = $data['type_id'];
        $content['article_id'] = $article_check;
        $hotarticles->add($content);
        if($article_check){
            $info = array(
                    'state' => 200,
                    'status' => 200,
                    'info'  => 'success',
                );
            echo json_encode($info,true);
            exit;
        }else{
            $info = array(
                    'state' => 801,
                    'status' => 801,
                    'info'  => 'invalid parameter',
                );
            echo json_encode($info,true);
            exit;
        }
    }

    public function listNews(){
        $type = I('post.type_id');
        $page = I('post.page');
        $size = I('post.size');
        $page = empty($page) ? 0 : $page;
        $size = empty($size) ? 15 : $size;
        $start = $page*$size;

        $articleType = D('articletypes');
        $article     = D('news');
        $condition = array(
            'type_id' => $type
        );
        // ->order('updated_time DESC')->limit($start,$start+15)->field('user_id,title,id,photo_src,thumbnail_src,type_id,content,updated_time,created_time,like_num,remark_num')
        $content = $article->where($condition)->limit($start,$size)->order('id DESC')->select();

        $praise  = M('articlepraises');
        $result = array();
        foreach($content as $key => $value){
            $praise_condition = array(
                "articletype_id"  => $type,
                "article_id"      => $value['id'],
                "stunum"          => I('post.stuNum')
            );
            $praise_exist = $praise->where($praise_condition)->find();
            if($praise_exist){
                $value['is_my_like'] = true;
            }else{
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
            $praise_exist = $praise->where($praise_condition)->find();
            if($praise_exist){
                $value['is_my_like'] = true;
            }else{
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

    public function searchHotArticle(){
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
        if($page == 0){
            $notice = M('notices');
            $data_notice_condition = array(
                    "created_time" => array('GT',$now_date),
                    "state"        => 1,
                );
            $data_notice   = $notice->where($data_notice_condition)->select();
            $site = $_SERVER["SERVER_NAME"];
            foreach ($data_notice as $key => $value) {
                $praise_condition = array(
                    "articletype_id" => "6",
                    "article_id"      => $data_notice[$key]['id'],
                    "stunum"          => I('post.stuNum')
                );
                $praise_exist = $praise->where($praise_condition)->find();
                if($praise_exist){
                    $exist = true;
                }else{
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

            }elseif($data[$key]['articletype_id'] < 5){
                $article = M("news");
                $praise  = M('articlepraises');
                $praise_condition = array(
                    "articletype_id"  => $data[$key]['articletype_id'],
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

    public function postNotice(){
        $administrators = M('administrators');
        $users = M('users');
        $user_condition = array(
                "stunum" =>I('post.stuNum')
            );
        $user_id = $users->where($user_condition)->find();

        $admin_condition = array(
                "user_id" => $user_id['id']
            );
        $admin = $administrators->where($admin_condition)->find();
        if(I('post.keyword') == 'cyxbsmobile' && $admin){
            $notice = M('notices');
            $content = array(
                "user_id"      => $user_id['id'],
                "created_time" => date("Y-m-d H:i:s", time()),
                "updated_time" => date("Y-m-d H:i:s", time()),
                "content"      => I('post.content'),
                "title"        => I('post.title'),
                "photo_src"    => I('post.photo_src'),
                "thumbnail_src"=> I('post.thumbnail_src')
            );
            $notice->add($content);
            $info = array(
                    'state' => 200,
                    'status' => 200,
                );
            echo json_encode($info,true);exit;
        }else{
            $info = array(
                    'state' => 801,
                    'status' => 801,
                    'info'  => 'invalid parameter',
                    'data'  => array(),
                );
            echo json_encode($info,true);
            exit;
        }
    }

    public function deleteNotice(){
        $administrators = M('administrators');
        $users = M('users');
        $notice = M('notices');
        $user_condition = array(
                "stunum" => I('post.stuNum')
            );
        $user_id = $users->where($user_condition)->find();

        $admin_condition = array(
                "user_id" => $user_id['id']
            );
        $admin = $administrators->where($admin_condition)->find();
        $notice_condition = array(
                "id" => I('post.notice_id'),
                "state" => 1
            );
        $notice_exist = $notice->where($notice_condition)->find();
        if(I('post.keyword') == 'cyxbsmobile' && $admin && I('post.notice_id')&&$notice_exist){
            $notice_info = array(
                    "state" => 0
                );
            $notice->where($notice_condition)->data($notice_info)->save();
            $info = array(
                    'state' => 200,
                    'status' => 200,
                );
            echo json_encode($info,true);exit;
        }else{
            $info = array(
                    'state' => 801,
                    'status' => 801,
                    'info'  => 'invalid parameter',
                    'data'  => array(),
                );
            echo json_encode($info,true);
            exit;
        }
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


}