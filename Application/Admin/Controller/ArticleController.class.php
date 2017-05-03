<?php

namespace Admin\Controller;

use Think\Controller;
use Home\Common\Article;


class ArticleController extends Controller
{
        private $operate = array(
                'delete'  => 'delete',
                'recover' => 'articleState',
                'lock'    => 'articleState',
                'unlock'  => 'articleState'
        );

        protected static $article_table = array(
                1               => 'news',
                2               => 'news',
                3               => 'news',
                4               => 'news',
                5               => 'articles',
                6               => 'notices',
                7               => 'topicarticles',
        );

        protected static $article_type = array(
                1               => '重邮新闻',
                2               => '教务在线',
                3               => '学术讲座',
                4               => '校务公告',
                5               => '哔哔叨叨',
                6               => '公告',
                7               => '话题文章',
        );

        private $_state = array(
                'delete' => 0,
                'normal' => 1,
                'lock'   => 2
        );
        protected $_status = array();

        public function _initialize()
        {
            if (!is_alive()) {
                returnJson(403);
            }
            //初始化
            $this->_status = array(
                    'recover' => array('before_state'=>$this->_state['delete'],'after_state'=>$this->_state['normal']),
                    'unlock' => array('before_state'=>$this->_state['lock'],'after_state'=>$this->_state['normal']),
                    'lock' => array('before_state'=>$this->_state['normal'],'after_state'=>$this->_state['lock']),
                    'delete' => array('before_state'=>array($this->_state['normal'], $this->_state['lock']),'after_state'=>$this->_state['delete']),
            );
        }
        
        public static function getType()
        {
                return self::$article_type;
        }

        public static function getTable() {
            return self::$article_table;
        }


        public function operate()
        {
                $operate = I('post.operate');
                $data = I('post.data');
                if (!isset($operate) || !isset($data)) {
                        returnJson(801);
                }

                $action =   empty($this->operate[$operate])
                                                ? $operate : $this->operate[$operate];

                if (!method_exists($this, $action)) {
                        returnJson(403);
                }

                foreach ($data as $key => &$value) {
                        $result = call_user_func(array($this, $action), $operate, $value);
                        if (!is_null($result)) {
                                $value['result'] = $result;
                        }
                }
                returnJson(200, '', $data);
        }

        /**
         * 修改数据库里的属性
         * @param  string $operate 操作名
         * @param  array $data    数据
         * @return bool          是否成功
         */
        public function articleState($operate, $data)
        {
                if (empty($operate) || empty($data)) {
                        return false;
                }
                $table = self::$article_table[$data['type_id']];
                $id = $data['id'];

                if (empty($id) || empty($table)) {
                        return false;
                }

              if ($operate == 'recover') {
                    $article = Article::setArticle($data, session('admin.user_id'));
                    return $article->recover();
              }

                $operate = strtolower($operate);

                //操作不存在,返回false
                if (empty($this->_status[$operate])) {
                        return false;
                } else {
                        extract($this->_status[$operate]);
                        if (!is_array($before_state)) {
                                $before_state = explode(',', $before_state);
                        }
                }


                $article = M($table)->where($data)->find();

                if(!$article) {
                        return false;
                }

                if (in_array($article['state'], $before_state)) {
                        $article['state'] = $after_state;
                        //修改成功
                        $result = M($table)->save($article);
                        if ($result) {
                                return true;
                        }
                }

                return false;
        }
        /**
         * 删除文章
         * @param  string $operate 需要执行的操作
         * @param  array $data    对象的属性
         * @return bool          执行是否成功
         */
        protected function delete($operate, $data)
        {

//                if(empty($data['id']) || empty($data['type_id'])) {
//                        return false;
//                }
//                //表
//                $table = self::$article_table[$data['type_id']];
//
//                if (empty($table)) {
//                        return false;
//                }
//
//                $article = M($table)->where($data)->find();
//
//                if (empty($article)) {
//                        return false;
//                }
//
//                //表里是否有state字段
//                if(isset($article['state'])) {
//                        if ($article['state'] != $this->_status['delete']['after_state']) {
//                                return $this->articleState('delete', $data);
//                        }
//                }
                //硬删除
                $article = Article::setArticle($data, session('admin.user_id'));
//                return $edit->delete($data['id'], $data['type_id']);
                return $article->delete();
        }

        public function addArticle() {
            $information = I('post.');
            if (isset($data['keyword'])) {
                $topic = D('topics')->where(array('keyword'=>$data['keyword']))->find();
                if ($topic) {
                    $data['topic_id'] = $topic['id'];
                    unset($data['keyword']);
                }
            }
            if ($information['official'] == 'true')    $information['official'] = 1;
            $information['user_id'] = session('admin.user_id');
            $article = Article::setArticle($information, $information['user_id']);
            if (!$article) returnJson(801);
            $article->add() ? returnJson(200) : returnJson(404);
        }

        public function getWriteType()
        {
            $data = array();
            foreach (self::$article_type as $key => $value) {
                if ($key > 4) {
                    $data[] = array('id'=>$key, 'text' => $value);
                }       
            }
            returnJson(200, '', $data);
        }

        public function getWriteTemplet()
        {
//            $templet = array(
//                    'title' => array('type' => 'text', 'text'=>'标题', 'name'=>'title', 'placeholder'=> '请输入标题'),
//                    'content' => array('type' => 'text', 'text' => '内容', 'name'=> 'content', 'placeholder'=> '详细内容..'),
//                    'photo' => array('type' => 'file', 'text' => '上传图片', 'name'=>'photo_src'),
//                    'keyword' => array('type' => 'text', 'text'=>'话题', 'name' => 'keyword', 'placeholder' => '话题..')
//            );

            $display = array(

                '5' =>    array(
                            'title',
                            'content',
                            'photo',
                            'official',
                        ),
                '6' =>  array(
                            'title',
                            'content',
                            'photo',

                        ),
                '7' => array(
                            'title',
                            'keyword',
                            'content',
                            'photo',
                            'official',
                        ),
            );

            $type_id = I('type_id');
            $data = $display[$type_id];
            if (empty($data)) {
                returnJson(404);
            }
           returnJson(200, '', $data);
        }

}
