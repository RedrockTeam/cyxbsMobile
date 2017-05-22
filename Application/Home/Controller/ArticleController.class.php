<?php
namespace Home\Controller;

use Home\Common\Article;
use Think\Controller;

class ArticleController extends BaseController {
    protected    $newsList = array('jwzx','cyxw','xsjz','xwgg');
//    public function index(){
//        $article = D("hotarticles");
//        $articles = $article->relation(true)->select();
//    }

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
                    'cyxbsmobile_articles.id'      => $article_id,
                    'cyxbsmobile_articles.type_id' => $type_id,

                );
            $content = $article->where($condition_article)->join('cyxbsmobile_users ON cyxbsmobile_articles.user_id = cyxbsmobile_users.id')->field('cyxbsmobile_articles.id,cyxbsmobile_articles.photo_src,cyxbsmobile_articles.thumbnail_src,cyxbsmobile_articles.content,cyxbsmobile_articles.type_id,cyxbsmobile_articles.updated_time,cyxbsmobile_articles.created_time,cyxbsmobile_articles.like_num,cyxbsmobile_articles.remark_num,cyxbsmobile_users.photo_src as user_photo,cyxbsmobile_users.nickname')->select();
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
        if($stunum_other === null){
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
            cyxbsmobile_articleremarks.state > 0 AND
            cyxbsmobile_users.stunum != '$stunum' AND
            cyxbsmobile_articleremarks.article_id IN(
                SELECT id FROM cyxbsmobile_articles WHERE state > 0 and (user_id = '$user_id' OR answer_user_id = '$user_id')
        )  
        UNION
        SELECT 'praise' as type,'' as content,cyxbsmobile_articles.content as article_content,cyxbsmobile_articles.thumbnail_src as article_photo_src,cyxbsmobile_articlepraises.created_time,cyxbsmobile_articlepraises.article_id,cyxbsmobile_users.stunum,cyxbsmobile_users.nickname,cyxbsmobile_users.photo_src
        FROM (cyxbsmobile_articlepraises JOIN cyxbsmobile_users ON cyxbsmobile_articlepraises.stunum = cyxbsmobile_users.stunum )JOIN cyxbsmobile_articles
        ON cyxbsmobile_articlepraises.article_id = cyxbsmobile_articles.id
        WHERE 
            cyxbsmobile_users.stunum != '$stunum' AND
            cyxbsmobile_articlepraises.article_id IN(
                SELECT id FROM cyxbsmobile_articles WHERE user_id = '$user_id' AND state > 0
        ) 
            ORDER BY created_time DESC
            limit $start,$size
        ";
        $result = M('')->query($sql);
        if (I('version') >= 1) {
            returnJson(200, array('data'=>$result));
        } else {
            $info = array(
                'status' => '200',
                "info"   => "success",
                'data'   => $result
            );
            echo json_encode($info);
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
        // $condition = array(
        //     'articletype_id' => $type
        // );
        // ->order('updated_time DESC')->limit($start,$start+15)->field('user_id,title,id,photo_src,thumbnail_src,type_id,content,updated_time,created_time,like_num,remark_num')
        $content = $article->limit($start,$size)->order('id DESC')->select();

        $praise  = M('articlepraises');
        $result = array();
        foreach($content as $key => $value){
            $praise_condition = array(
                "articletype_id"  => $value['articletype_id'],
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

    /**
     * 添加文章
     */
    public  function addArticle() {
        $data = I('post.');

        if (!$data['type_id']) {
            returnJson(801, 'error articleType');
        }
        if (isset($data['keyword'])) {
            $topic = D('topics')->where(array('keyword'=>$data['keyword']))->find();
            if ($topic) {
                $data['topic_id'] = $topic['id'];
                unset($data['keyword']);
            }
        }
        $article = Article::setArticle($data, $data['stuNum']);
        if($article === false)    returnJson(801);
        if ($article->add())
            returnJson(200, '', array('state'=>200));
        else
            returnJson(404, $article->getError());

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
        $get = I('get.');
        $post = I('post.');
        $url = U('NewArticle/searchHotArticle', $get, true, true);
        $result = curlPost($url, $post);
        if ($result)
            returnJson(404);
        echo $result;
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

    public function praise()
    {
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

    /**
     * 恢复软删除的文章
     */
    public function recoverArticle()
    {
        $information = I('post.');
        $article = Article::setArticle($information, $information['stuNum']);

        if ($article === false)
            returnJson(404, 'error article');
        $result = $article->recover();
        if ($result)    returnJson(200);
        else returnJson(404, $article->getError());
    }

    /**
     * 删除文章
     * @return [type] [description]
     */
    public function deleteArticle()
    {
        $type_id 	= I('post.type_id');
        $article_id = I('post.article_id');
        $stuNum		= I('post.stuNum');
        $forceDelete = I('post.forceDelete');
        //确认参数完整
        if (empty($type_id) || empty($article_id)) {
            returnJson(801);
            exit;
        }
        $article = compact('type_id', 'article_id');

        $article =  Article::setArticle($article, $stuNum);

        if ($article === false) returnJson(404, "error article");

        $result = $article->delete($forceDelete);

        if($result)
            returnJson(200);
        else
            returnJson(404, $article->getError());
    }

    public function editArticle()
    {
        $information = I('post.');
        if (empty($information['type_id']) || empty($information['article_id'])) {
            returnJson(801);
        }
    }






}