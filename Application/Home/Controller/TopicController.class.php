<?php
/**
 * Created by PhpStorm.
 * User: pumbf
 * Date: 2017/3/22
 * Time: 00:39
 */

namespace Home\Controller;


use Home\Common\Article;
use Home\Common\ForbidWord;
use Think\Controller;

class TopicController extends Controller
{
    /**
     * 添加话题
     */
    public function addTopic()
    {
        $information = I('post.');
        if (!$this->produceTopicInformation($information,$error)) {
            returnJson(404, $error);
        }

        if ($information['official'] == true) {
            //官方发起话题
            if (!is_admin($information['stuNum'])) {
                returnJson(403, '你还不是管理员哟');
            }
            $information['user_id'] = 0;            //以红岩网校工作站名义创建的话题
        } else {
            //个人发起话题
            $user = M('users')->where('stunum=\'%s\'', $information['stuNum'])->find();
            if(!$user) {
                returnJson(403, '你还不是掌邮的用户');
            }
            $information['user_id'] = $user['id'];
        }
        if (is_null($information['keyword']) || isset($information['id']) || is_null($information['user_id'])) {
            returnJson(404, 'error pram');
        }
        $default = array(
            'content' => '',
            'photo_src'=>'',
            'thumbnail_src'=>'',
            'like_num'  => 0,
            'remark_num'    => 0,
            'join_num' => 0,
            'article_num' => 0,
            'state' => 1,
            'created_time'  => date("Y-m-d H:i:s"),
            'updated_time'  => date("Y-m-d H:i:s")
        );
        $information = array_merge($default, $information);
        $result = M('topics')->add($information);
        if ($result) {
            returnJson(200);
        } else {
            returnJson(404);
        }

    }

    /**
     * 添加话题文章
     */
    public function addTopicArticle() {
        $information = I('post.');
        //验证是否登入的信息
        if (true !== $result = authUser($information['stuNum'], $information['idNum']))   echo json_encode($result);
        //默认类型
        $information['type_id'] = 7;

        if (isset($information['article_id']) || isset($information['id'])) {
            returnJson(404);
        }
        $article = Article::setArticle($information, $information['stuNum']);
        if ($article === false)
            returnJson(404);

        $result = $article->add();
        if ($result) {
            D('topics')->where('id=%d', $article->get('topic_id'))->setInc('article_num');
            returnJson(200);
        } else {
            returnJson(404, 'fatal add article ');
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
        $information['size'] = isset($information['size']) ? $information['size'] : 10;

        $displayField = array(
            "topics.id" => "topic_id",
            'content',
            'keyword',
            'photo_src',
            'thumbnail_src',
            'join_num',
            'like_num',
            'article_num',
            'user_id'
        );
        $pos = array(
            'created_time' => array('elt', date('Y-m-d H:i:s')),
            "state"   => 1,
        );

        if (isset($information['keyword']))
            $pos['keyword'] =  array('like', $information['keyword'].'%');


        //话题信息的查询
        $data = M('topics')
            ->alias('topics')
            ->where($pos)
            ->field($displayField)
            ->order('created_time')
            ->limit($information['page']*$information['size'], $information['size'])
            ->select();
        $userField = array('nickname', 'stunum'=>'user_id', 'photo_src'=>'user_photo');
        foreach ($data as $key => &$value) {
            $user = D('users')->field($userField)->find($value['user_id']);
            $value = array_merge($value, $user);
            $value['img']['img_small_src'] = $value['photo_src'];
            $value['img']['img_src'] = $value['thumbnail_src'];

            $value['is_my_join'] = $this->is_my_join($value['id'], $information['stuNum']);
            unset($value['photo_src']);
            unset($value['thumbnail_src']);
            $value['content'] = array("content" => $value['content']);
        }
        returnJson(200, '', compact('data'));
    }

    /**
     * @param $topic_id int 话题id
     * @param $user mixed 用户标示
     * @return bool
     */

    /**
     * 我参与过的topic
     */
    public function myJoinedTopic() {
        $post = I('post.');
        $get = I('get.');
        $information = array_merge($get, $post);
        $information['page'] = isset($information['page']) ? $information['page'] : 0;
        $information['size'] = isset($information['size']) ? $information['size'] : 10;
        if (authUser($information['stuNum'], $information['idNum'])) {
            returnJson(403, "你未登入");
        }
        $user = getUserInfo($information['stuNum']);
        $pos = array('user_id' => $user['id'], 'state' => 1, 'created_time' => array('elt', date('Y-m-d H:i:s')));
        $result = D('topicarticles')->field('topic_id')->where($pos)->group('topic_id')->select();
        if ($result === false)
            returnJson(404);

        $topic_ids = array();
        foreach ($result as $topic)
            $topic_ids = $topic['topic_id'];
        $condition = array('id'=>array('in', $topic_ids), 'state'=>1, 'created_time' => array('elt', date('Y-m-d H:i:s')));
        if (isset($information['keyword'])) {
            $condition['keyword'] = array("like", $information['key'].'%');
        }
        $displayField = array(
            "id" => "topic_id",
            'content',
            'keyword',
            'photo_src',
            'thumbnail_src',
            'join_num',
            'like_num',
            'article_num',
            'user_id'
        );
        $data = D('topics')
                    ->field($displayField)
                    ->where($condition)
                    ->limit($information['page']*$information['size'], $information['size'])
                    ->select();
        $userField = array('nickname', 'stunum'=>'user_id', 'photo_src'=>'user_photo');
        foreach ($data as $key => &$value) {
            $user = D('users')->field($userField)->find($value['user_id']);
            $value = array_merge($value, $user);
            $value['img']['img_small_src'] = $value['photo_src'];
            $value['img']['img_src'] = $value['thumbnail_src'];

            unset($value['photo_src']);
            unset($value['thumbnail_src']);
            $value['content'] = array("content" => $value['content']);
        }
        returnJson(200, '', compact('data'));
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
            $user_alias.'.photo_thumbnail_src'    => 'photo_thumbnail_src',
            'like_num',
            'remark_num',
        );
        $site = $_SERVER["SERVER_NAME"];
        //话题
        $topic = M('topics')->field(true, 'state')->find($information['topic_id']);
        //user_id为0时使用官方的身份
        if ($topic['user_id'] == 0) {
            $topic['nickname'] = "红岩网校工作站";
            $topic['photo_src'] = "http://".$site.'/cyxbsMobile/Public/HONGY.jpg';
        } else {
            $user = M('users')->find($topic['user_id']);
            $topic['nickname'] = $user['nickname'];
            $topic['photo_src'] = $user['photo_src'];
        }
        unset($topic['user_id']);
        if (!$topic) {
            returnJson(404, 'error topic\'s id');
        }
        $pos = array('topic_id' => $topic['id'], $article_alias.'.state'=> 1);
        $articles = M('topicarticles')
            ->alias($article_alias)
            ->join('__USERS__ '.$user_alias.' ON '.$user_alias.'.id='.$article_alias.'.user_id', "RIGHT")
            ->where($pos)
            ->field($displayField)
            ->order($article_alias.'.updated_time DESC')
            ->limit($information['page']*$information['size'], $information['size'])
            ->select();

        foreach ($articles as $key => $value) {
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

    /**
     * 文章详情
     */
    public function topicArticleContent() {
        $information = I('post.');
        if(false === $article = Article::setArticle($information, $information['stuNum']))
            returnJson(404, 'error article');
        $content = $article->getContent();

        $content['is_my_like'] = is_null($information['stuNum'])? false :$article->getPraise($information['stuNum']);
        returnJson(200, '', array('data'=>array($content)));

    }

    protected function is_my_join($topic_id, $user) {
        $user = getUserInfo($user);
        if ($user === false) {
            return false;
        }
        $pos = array('topic_id' => $topic_id, 'user_id' => $user['id']);
        $result = D('topicarticles')->where($pos)->find();
        return empty($result) ? false : true;
    }

    /**
     * 处理话题的数据
     * @param $information
     * @param string $error
     * @return bool
     */
    protected function produceTopicInformation(&$information, &$error='')
    {
        if(empty($information)) {
            return $information;
        }
        $forbidWord = new ForbidWord('topics');
        foreach ($information as $field => $value) {

            switch ($field) {

                case 'keyword':
                    if(empty($value) || !$forbidWord->check($value)) {
                        $error = $field."'s value is error";
                        return false;
                    }
                    $result = D('topics')->where('keyword=\'%s\'', $value)->find();
                    if ($result) {
                        $error = "keyword exist";
                        return false;
                    }
                    break;
                case 'content':
                    if(empty($value) || !$forbidWord->check($value, 'articles')) {
                        $error = $field."'s value is error";
                        return false;
                    }
                    break;

                case 'topic_id':
                    $information['id'] = $value;
                    unset($information[$field]);
                    break;
                case 'state':
                case 'updated_time':
                case 'created_time':
                    unset($information[$field]);
                    break;
            }
        }

        return true;
    }
    /**
     * 判断我是否对该文章点赞
     * @param  number  $article_id 文章的id值
     * @param  number  $type_id    文章类型
     * @param  string  $stunum     学号
     * @return boolean             是否喜欢
     */
    protected function is_my_like($article_id, $type_id, $stunum)
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

    protected function addTopicRead($topic_id) {
        return D('topics')->where('id=%d', $topic_id)->setInc('read_num');
    }

}