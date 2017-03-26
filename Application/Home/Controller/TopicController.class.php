<?php
/**
 * Created by PhpStorm.
 * User: pumbf
 * Date: 2017/3/22
 * Time: 00:39
 */

namespace Home\Controller;


use Home\Common\Article;
use Home\Common\Forbidword;
use Think\Controller;

class TopicController extends Controller
{
    /**
     * 添加话题
     */
    public function addTopic()
    {
        $information = I('post.');
        if (true !== authUser(I('post.stuNum'), I('post.idNum'))) {
            returnJson('403', '未登录不允许');
        }


        if (!$this->produceTopicInformation($information,$error)) {
            returnJson(404, $error);
        }

        if ($information['official'] == 'true') {
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
            returnJson(801);
        }
        $user = !empty($information['official']) ? 0 : $information['stuNum'];
        $article = Article::setArticle($information,  $user);
        if ($article === false)
            returnJson(404);

        $result = $article->add();
        if ($result) {
            D('topics')->where('id=%d', $article->get('topic_id'))->setInc('article_num');
            returnJson(200, '', array('state'=>200));
        } else {
            returnJson(404, $article->getError());
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

        if (!empty($information['searchKeyword']))
            $pos['keyword'] =  array('like', $information['searchKeyword'].'%');
        else
            $information['searchKeyword'] = '';

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
            $user = $value['user_id'] === 0 ? array(
                'nickname' => "红岩网校工作站",
                'photo_src' => "http://" . $_SERVER["SERVER_NAME"] . '/cyxbsMobile/Public/HONGY.jpg',
                'user_id' => '0'
            ) : D('users')->field($userField)->find($value['user_id']);
            $value = array_merge($value, $user);
            $value['img']['img_small_src'] = $value['photo_src'];
            $value['img']['img_src'] = $value['thumbnail_src'];

            $value['is_my_join'] = is_my_join($value['topic_id'], $information['stuNum']);
            unset($value['photo_src']);
            unset($value['thumbnail_src']);
            $value['content'] = array("content" => $value['content']);
        }
        returnJson(200, '', array('searchKeyword' => $information['searchKeyword'] ,'data'=>$data));
    }



    /**
     * 我参与过的topic
     */
    public function myJoinedTopic() {
        $post = I('post.');
        $get = I('get.');
        $information = $get + $post;

        $information['page'] = isset($information['page']) ? $information['page'] : 0;
        $information['size'] = isset($information['size']) ? $information['size'] : 10;
        //idNum 和 stuNum 必须post 传值
        if (true !== authUser(I('post.stuNum'), I('post.idNum'))) {
            returnJson(403, "你未登入");
        }
        $user = getUserInfo($information['stuNum']);
        $topicIds = array();
        //获取该用户通过回答回复参与过话题
        $remarkPos = array(
            'user_id' => $user['id'],
            'state' => 1,
            'articletypes_id' => 7,
            'created_time' => array('elt', date('Y-m-d H:i:s')),
        );
        $articleIds = D('articleremarks')->where($remarkPos)->group('article_id')->getField('article_id', true);
        if (!empty($articleIds)) {
            foreach($articleIds as $articleId)
                $topicIds[] = M('topicarticles')->where(array('id'=>$articleId))->getField('topic_id');
        }

        //反转去重
        $topicIds = array_flip($topicIds);
        //获取该用户通过回答写文章参与过话题
        $articlePos = array(
            'user_id' => $user['id'],
            'state' => 1,
            'created_time' => array('elt', date('Y-m-d H:i:s')),
        );
        $articleTopicIds = D('topicarticles')->where($articlePos)->group('topic_id')->getField('topic_id', true);

        if (empty($articleTopicIds))
            $articleTopicIds = array();
         else
            $articleTopicIds = array_flip($articleTopicIds);
        //数组合并
        $topicIds = $articleTopicIds + $topicIds;

        $topicIds = array_flip($topicIds);
        //没找到对应的话题返回空
        if(empty($topicIds))    returnJson(200, '', array('data' => array()));

        $topicIds = implode(',', $topicIds);

        $condition = array(
            'state'=>1,
            'created_time' => array('elt', date('Y-m-d H:i:s')),
            'id'=>array('in', $topicIds),
        );

        if (isset($information['keyword'])) {
            $condition['keyword'] = array("like", $information['keyword'].'%');
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
            $user = $value['user_id'] === 0 ? array(
                'nickname' => "红岩网校工作站",
                'photo_src' => "http://" . $_SERVER["SERVER_NAME"] . '/cyxbsMobile/Public/HONGY.jpg',
                'user_id' => '0'
            ) : D('users')->field($userField)->find($value['user_id']);
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
            $article_alias.'.id' => 'article_id',
            'type_id',
            $article_alias.'.photo_src'     => 'article_photo_src',
            $article_alias.'.thumbnail_src' => 'article_thumbnail_src',
            'title',
            'content',
            'nickname',
            'stunum'          => 'user_id',
            $user_alias.'.photo_src'        => 'user_photo_src',
            $user_alias.'.photo_thumbnail_src'    => 'user_thumbnail_src',
            'like_num',
            'remark_num',
        );
        $site = $_SERVER["SERVER_NAME"];
        //话题
        if (isset($information['topic_id'])) {
            $pos = array('state'=>1, 'id' => $information['topic_id']);
            $topic = M('topics')->field(true, 'state')->where($pos)->find();
        }
        else if(isset($information['keyword'])) {
            $pos = array('state'=>1, 'keyword' => $information['keyword']);
            $topic = M('topics')->field(true, 'state')->where($pos)->find();
        }
        else
            returnJson(404, 'can\'t find this topic');

        if (!$topic)    returnJson(404, 'can\'t find this topic');

        $topic["topic_id"] = $topic['id'];
        unset($topic['id']);
        //user_id为0时使用官方的身份
        if ($topic['user_id'] == 0) {
            $topic['nickname'] = "红岩网校工作站";
            $topic['photo_src'] = "http://".$site.'/cyxbsMobile/Public/HONGY.jpg';
        } else {
            $user = M('users')->find($topic['user_id']);
            $topic['nickname'] = $user['nickname'];
            $topic['user_photo_src'] = $user['photo_src'];
            $topic['user_thumbnail_src'] = $user['photo_thumbnail_src'];
        }
        unset($topic['user_id']);

        $pos = array('topic_id' => $topic['topic_id'], $article_alias.'.state'=> 1);
        //搜索文章
        if (!empty($information['searchTitle']))
            $pos['title'] = array('like', $information['searchTitle'].'%');
        else
            $information['searchTitle'] = '';

        $articles = M('topicarticles')
            ->alias($article_alias)
            ->join('__USERS__ '.$user_alias.' ON '.$user_alias.'.id='.$article_alias.'.user_id', "LEFT")
            ->where($pos)
            ->field($displayField)
            ->order($article_alias.'.updated_time DESC')
            ->limit($information['page']*$information['size'], $information['size'])
            ->select();

        foreach ($articles as $key => $value) {
            if($value['user_id'] === 0) {
                $value['nickname'] = "红岩网校工作站";
                $value['photo_src'] = "http://".$site.'/cyxbsMobile/Public/HONGY.jpg';
                $value['thumbnail_src'] = "http://".$site.'/cyxbsMobile/Public/HONGY.jpg';
            }
            if (!isset($information['stuNum'])) {
                $value['is_my_like)'] = false;
            }
            else {
                $value['is_my_like)'] = $this->is_my_like($value['id'], $value['type_id'], $information['stuNum']);
            }
        }
        $topic['is_my_join'] = empty($information['stuNum']) ? false : is_my_join($topic['id'], $information['stuNum']);
        $topic['articles'] = $articles;

        returnJson(200, '',array('searchTitle' => $information['searchTitle'], 'data' => $topic));




    }

    /**
     * 文章详情
     */
    public function topicArticleContent() {
        $information = I('post.');
        //topic_id
        $information['type_id'] = 7;
        if(false === $article = Article::setArticle($information, $information['stuNum']))
            returnJson(404, 'error article');
        $content = $article->getContent();
        if (!$content)  returnJson(404, $article->getError());
        $content['is_my_like'] = is_null($information['stuNum'])? false :$article->getPraise($information['stuNum']);
        returnJson(200, '', array('data'=>array($content)));

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
        $forbidWord = new Forbidword('topics');
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
    protected function is_my_like($article_id, $type_id, $stuNum)
    {
        if(empty($stuNum)) {
            return false;
        }
        $praise_condition = array(
            'article_id'    => $article_id,
            'articletypes_id'=> $type_id,
            'stunum'        => $stuNum
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