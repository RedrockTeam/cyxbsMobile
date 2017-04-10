<?php

namespace Home\Common;

//use Admin\Controller\DataController;
//use Home\Controller\BaseController;
//use Home\Controller\ArticleController;
use Think\Exception;
//use Think\Log\Driver\File as Log;

class Article
{
    //文章
    private $article;
    //写进来的
    protected $tmp;
    //对象里存有的属性
    private $fields;
    //文章对应的回复
    private $article_remarks;

    private $article_praise;
    //文章存放的数据表
    private $table;
    //当前操作者详细信息
    private $operator;
    //文章作者
    private $author;
    //文章 类型ID
    private $type_id;
    //操作产生的错误
    protected $error = array();
    //记录
    protected $log;
    //违规字检查
    protected $forbidWord;


    /**
     * EditController constructor.
     * @param $article  array 文章信息
     * @param $table string 文章存放数据表
     * @param $user array 用户信息
     * @throws Exception
     */
    protected function  __construct($article, $table, $user)
    {
        $this->table = $table;
        $this->fields = D($table)->getDbFields();
        $this->operator = $user;
        $this->type_id = isset($article['type_id']) ? $article['type_id'] : $article['article_type_id'];
        $this->forbidWord = new Forbidword('articles');

        if (empty($article['id']) && isset($article['article_id'])) {
            $article['id'] = $article['article_id'];
            unset($article['article_id']);
        }
        if (empty($article['id'])) {
            $this->tmp = $this->produceArticleInformation($article);
        } else {
            $this->article = D($table)->find($article['id']);
            if ($this->article == false) {
                throw new Exception("error article");
            } else {
                //作者信息
                if ($this->type_id == 6 || (in_array("official", $this->fields) && $this->get('official') == 1)) {
                    $this->author = array(
                        'photo_src' => "http://".$_SERVER["SERVER_NAME"].'/cyxbsMobile/Public/HONGY.jpg',
                        'nickname'  => '红岩网校工作站',
                        'id'        => 0,
                    );
                } elseif (in_array('user_id', $this->fields))
                    $this->author = getUserInfo($this->article['user_id']);
            }

        }
    }

    /**
     * @param $article
     * @param $stu
     * @return bool|Article
     */
    public  static function setArticle($article, $stu=null) {
        $table_type = array(
            1 		=> 'news',
            2		=> 'news',
            3 		=> 'news',
            4 		=> 'news',
            5 		=> 'articles',
            6		=> 'notices',
            7		=> 'topicarticles',
        );
        //没有文章类型 返回false
        if(empty($article)) {
            return false;
        }
        //当以数组的形式传入
        if (is_array($article)) {
            //type_id 和 article_id 确定文章
            $type_id = isset($article['type_id']) ? $article['type_id'] : $article['article_type_id'];
            $table = $table_type[$type_id];
            //错误类型返回false
            if (empty($type_id) || empty($table)) {
                return false;
            }

        } else {
            return false;
        }

        $stu = getUserInfo($stu);

        if ($stu === false)     $stu=null;
        try {
            $obj =  new self($article, $table, $stu);
        } catch(Exception $e) {
            return false;
        }
        return $obj;

    }
    //获得文章内容
    public function __get($name)
    {
        $this->get($name);
    }
    //获取内容
    /**
     * @param $name array|string
     * @param bool $origin 是否原来的
     * @return array|mixed|string
     * @throws Exception
     */
    public function get($name, $origin = false) {

        if (is_array($name)) {
            $data = array();
            foreach ($name as $key => $value) {
                if (is_numeric($key))
                    $key = $value;
                try {
                    $val = $this->get($key);
                } catch (Exception $e) {
                    continue;
                }
                $data[$value] = $val;
            }
            return $data;
        }
        //适配 article.title 这种情况
        if (strpos($name, '.') !== false) {
            $names = explode('.', $name);

            try {
                $value = $this->get(array_shift($names));
            } catch (\Exception $e) {
                return null;
            }
            while(!empty($names)) {
                $name = array_shift($names);
                $value = $value[$name];
                if(is_null($value)) {
                    return null;
                }
            }
            return $value;
        }
        $name = strtolower($name);

        if (in_array($name, array('author', 'article', 'tmp', 'operator')))
            return $this->$name;

        if($name === 'user')
            return $this->operator;

        if (in_array($name, $this->fields))

            return is_null($this->tmp) || is_null($this->tmp[$name]) || $origin ? $this->article[$name] : $this->tmp[$name];


        throw new Exception('error article fields');

    }

    /**
     * 对文章内容进行操作
     * @param $name
     * @param $value
     * @throws Exception
     */
    public function set($name, $value=null) {
        if ($this->hasPower()) {
            if (is_array($name) && $value === null) {
                $name = $this->produceArticleInformation($name);
                if ($name === false)
                    throw new Exception('error data');
                $this->tmp = $name;
            } elseif (is_string($name)) {
                if (false === $value = $this->produceArticleInformation($name, $value))
                    throw new Exception('error data');
                $this->tmp[$name] = $value;
            } else {
                throw new Exception('not permit');
            }
        } else {
            throw new Exception('not permit');
        }
    }

    public function __set($name, $value)
    {
       $this->set($name, $value);
    }

    /**
	 * 删除文章
	 * @return [type] [description]
	 */
	public function delete($forceDelete = false)
	{
		if (!$this->is_exist()) {
            $this->error[] =  'don\'t exist';
            return false;
        }

		//是否有权力删除文章
		if ($this->hasPower()) {

            //根据类型和参数是否直接删除
            if($forceDelete || !in_array('state', $this->fields))
                $result = $this->forthDelete();
            else
                $result =  $this->softDelete();
            if(!$result) {
                $this->error[] = 'delete error';
                return false;
            }
            if (in_array('type_id', $this->fields)) {
                $data = $this->article;
                $topic = D('topics')->find($data['topic_id']);
                $pos = array('articletypes_id' => $this->type_id, 'article_id' => $data['id'], 'state'=>1);
                $remarkUsers = D('articleremarks')->where($pos)->group('user_id')->getField('user_id', true);
                $remark_num = D('articleremarks')->where($pos)->data('state=-1')->save();
                $topic['remark_num'] -= $remark_num;
                $topic['article_num']--;
                $result = D('topics')->save($topic);
                foreach ($remarkUsers as $user) {
                    subscribeJoinTopicIds($data['topic_id'], $user);
                }
                subscribeJoinTopicIds($data['topic_id'], $this->author['stunum']);
            }

		} else {	
			$this->error[] =  'don\'t permit';
            return false;
		}

        return true;
	}

//	protected  function changelog($info) {
//        if (isset($this->log))  $this->log = new Log;
//        $this->log->write($info);
//    }

    /**
     * 软删除
     * @return bool
     */
    protected function softDelete() {

        $data = $this->article;
        $data['state'] = 0;
        if (isset($data['updated_time'])) $data['updated_time'] = date('Y-m-d H:i:s');
        $result = D($this->table)->save($data);
        if ($result) {
            $this->article = $data;
        }
        return $result ? true : false;
    }

    /**
     * 恢复软删除删的文章
     * @return bool
     */
    public function  recover() {
        if(!$this->hasPower()) {
            $this->error[] = "softDelete don't permit";
            return false;
        }
        //判断是否是软删除s
        if (!isset($this->article) || $this->article['state'] != 0) {
            $this->error[] = "this article can't recover";
            return false;
        }
        $data = $this->article;
        $data['state'] = 1;
        //如果有updated_time 更新时间
        if (in_array('updated_time', $this->fields)) $data['updated_time'] = date('Y-m-d H:i:s');
        $result = D($this->table)->save($data);
        if ($result) {
            $this->article = $result;
            if (isset($data['topic_id'])) {
                $topic = D('topics')->find($data['topic_id']);
                $pos = array('articletypes_id' => $this->type_id, 'article_id' => $data['id'], 'state'=>-1);
                $remarkUsers = D('articleremarks')->where($pos)->group('user_id')->getField('user_id', true);
                $remark_num = D('articleremarks')->where($pos)->data('state=1')->save();
                $topic['remark_num'] += $remark_num;
                $topic['article_num']++;
                $result = D('topics')->save($topic);
                foreach ($remarkUsers as $user) {
                    addJoinTopicIds($data['topic_id'], $user);
                }
                addJoinTopicIds($data['topic_id'], $this->author['stunum']);
            }
        }
        return $result ? true : false;
    }

	/**
	 * 进行文章的删除工作
	 * @param  [type] $article_id 文章id
	 * @param  [type] $type_id    文章类别
	 * @return [bool]             是否操作成功
	 */
	protected function forthDelete()
	{

		$article = M($this->table);
		$praise = M('articlepraises');
		$remark = M('articleremarks');

        $type_id = $this->type_id;
        $article_id = $this->article['id'];
		//应用事务进行删除
		M()->startTrans();
		$remark_exist = $remark->where(array(
			'article_id'		=> $article_id,
			'articletypes_id'	=> $type_id,
			))->select();
		if (empty($remark_exist)){
			$remark_result = true;
		} else {
			$remark_result = $remark->where(array(
				'article_id'	 => $article_id,
				'articletypes_id'=> $type_id,))
				->delete();
		}
		$praise_exist = $praise->where(array(
			'article_id'			=> $article_id,
			'articletype_id'		=> $type_id,
			))->select();
		if (empty($praise_exist)) {
			$praise_result = true;
		} else {
			$praise_result = $praise->where(array(
			'article_id'	=> $article_id,
			'articletype_id'=> $type_id,
			))->delete();
		}
		//不为通知则删除hotarticle里的内容
		if ($type_id != 6) {
			$hotarticles = M('hotarticles');
			$hotarticles_result = $hotarticles->where(array(
				'article_id' 	=> $article_id,
				'articletype_id' => $type_id,
				))->delete();
		} else {
			$hotarticles_result = true;
		}

		$article_result = $article->where('id=%d',$article_id)->delete();

		$result = $remark_result && $praise_result && $article_result && $hotarticles_result;
		if ($result) {
			M()->commit();
		} else {
			M()->rollback();
		}
		$this->tmp = $article;
        $this->article = array();
		return $result;
	}

    /**
     * 判断是否是管理员
     * @return bool
     */
    protected  function  is_admin() {
        if (isset($this->operator['is_admin']))
            return $this->operator['is_admin'];

        $user_id = $this->operator['id'];
        $admin = D('admin')->where(array('user_id'=>$user_id))->find();
        if (empty($admin))
            return $this->operator['is_admin'] =  D('administrators')->where(array('user_id'=>$user_id)) ? true : false;

        return $this->operator['is_admin'] = true;
    }

    /**
     * 存在的 进行更新， 不存在的 添加
     * @param array $data   更改数据
     * @return bool 是否更改成功
     */
    public function save($data = array()) {
        if (!$this->hasPower()) {
            $this->error[] = 'action save don\'t permit';
            return false;
        }
        if (isset($data))
            $this->set($data);
        $data = $this->tmp;

        //文章不存在时 添加文章
        if (is_null($this->article)) {
            $this->add();
        }
        $change = '';

        foreach ($this->tmp as $field => $value)
            $change .= $field.$this->article[$field].'=>'.$value.' ';

        $data['id'] = $this->article['id'];
        if (in_array('updated_time', $this->fields)) $data['updated_time'] = date('Y-m-d H:i:s');
        //$data['user_id'] = $this->operator['id'];
        $result = D($this->table)->save($data);

//        $sql = M()->getLastSql();

        if ($result) {
            $this->tmp = array();
            $this->article = $result;
            return true;
        }

        return false;

    }

    /**
     * @param array $data
     * @return bool
     */
    public function add($data = array()) {
        if (!$this->hasPower()) {
            $this->error[] = 'action save don\'t permit';
            return false;
        }
        if ($this->is_exist()) {
            $this->error[] = 'article have exist';
            return false;
        }
        if (!empty($data)) {
            $this->set($data);
        }
        $data = $this->tmp;

        if (in_array('photo_src', $this->fields) || in_array('thumbnail_src', $this->fields)) {
            $data['photo_src'] = isset($data['photo_src']) ? $data['photo_src'] : '';
            $data['thumbnail_src'] = isset($data['thumbnail_src']) ? $data['thumbnail_src'] : '';
        }
        if (in_array('updated_time', $this->fields)) $data['updated_time'] = date('Y-m-d H:i:s');
        if (in_array('created_time', $this->fields)) $data['created_time'] = date('Y-m-d H:i:s');

        $data['user_id'] = $this->operator['id'];

        if (in_array('topic_id', $this->fields)) {
            if (is_null($data['topic_id'])) {
                $this->error[] = "invalid topic_id";
                return false;
            }
            $topic = D('topics')->where('id=%d',$this->get('topic_id'))->setInc('article_num');
            if (!$topic) {
                $this->error[] = "error topic_id";
                return false;
            }

            if ($this->tmp['official'] != 1) {
                addJoinTopicIds($this->get('topic_id'), $this->operator['stunum']);
            }
        }
        $result = D($this->table)->add($data);

        if ($result) {
            $this->article = D($this->table)->find($result);
            $this->tmp = array();
            // 提供测试
            if ($this->type_id != 6 && $this->type_id != 7) {
                return $this->addHotArticles();
            }
            return true;
        }
        $this->error[] = 'add error';
        return false;
    }

    /**
     * @return bool
     */
    public function  addHotArticles() {

        $data = array(
            'article_id'        => $this->article['id'],
            'articletype_id'    => $this->type_id,
            'created_time'      => date("Y-m-d H:i:s"),
            'updated_time'      => date("Y-m-d H:i:s"),
            'remark_num'        => 0,
            'like_num'         => 0,
            'self_remark_num'   => 0,
        );
        $result = D('hotarticles')->add($data);
        if (!$result) {
            $this->error[] = M()->getError();
            return false;
        }
        return true;
    }


    /**
     * 文章是否存在
     * @return bool
     */
    public  function is_exist() {
        if (empty($this->article))    return false;
        if ($this->article['state'] != 1)  return false;
        if (isset($this->article['topic_id'])) {
            $topic = D('topics')->where('state=1')->find($this->article['topic_id']);
            if (!$topic)    return false;
        }
        return true;
    }

    /**
     * 判断是否有修改文章的权限
     * @return boolean          是否有权力
     */
    protected function hasPower()
    {

        if (is_null($this->operator))
            return false;
        if (isset($this->operator['hasPower']))
            return $this->operator['hasPower'];

        $type_id = $this->type_id;
        if (empty($this->article))
            return $this->operator['hasPower'] = true;

        //是否有权限
        if ($type_id == 6 || $type_id <= 4 || $this->article['user_id'] == 0) {
            return $this->operator['hasPower'] = $this->is_admin();
        } else {
            if($this->operator['id'] != $this->article['user_id'])
                if (!$this->is_admin())
                    return $this->operator['hasPower'] = false;

            return $this->operator['hasPower'] = true;
        }
    }

    /**
     * @return bool|mixed
     */
    public function getRemarks() {

        if (!$this->is_exist()) {
            $this->error[] = "can't find article`";
            return false;
        }

        if (isset($this->article_remarks))  return $this->article_remarks;

        $pos = array(
            'state' => 1,
            "articletypes_id" => $this->type_id,
            "article_id" => $this->article['id']
        );
        $remarks = D('articleremarks')->where($pos)->select();

        if ($remarks) {
            $this->article_remarks = $remarks;
            return $remarks;
        }
        return false;
    }

    /**
     * @param mixed $stu    //用户标志
     * @return bool|int
     */
    public function getPraise($stu = false) {
        if (!$this->is_exist()) {
            $this->error[] = "can't find article`";
            return false;
        }
        if ($stu === false) {
            if (isset($this->article_praise))
                return $this->article_praise;
            else {
                $pos = array(
                    'article_id' => $this->article['id'],
                    'articletype_id' => $this->type_id,
                    );
                $praise = D('articlepraises')->where($pos)->count();
                if ($praise !== false) {
                    $this->article_praise = $praise;
                    return $praise;
                }
                return false;
            }
        } else {
            $user = getUserInfo($stu);
            $pos = array(
                'article_id' => $this->article['id'],
                'stunum'       => $user['stunum'],
                'articletype_id' => $this->type_id,
            );
            $result = D('articlepraises')->where($pos)->find();
            return $result  ? true : false;
        }
    }

    /**
     * 返回错误 内容
     * @return mixed
     */
    public function getError() {
         $error = $this->error;
        return end($error);
    }

    /**
     * @param $field string 字段
     * @param null $value mixed 字段对应的值
     * @return array|bool|mixed|null
     */

    protected function produceArticleInformation($field, $value = null)
    {
        if(empty($field)) {
            $this->error[] = "empty field";
            return false;
        }
        //如果传入数组使用递归调用
        if (is_array($field)) {
            foreach ($field as $key => &$value) {
                $value = $this->produceArticleInformation($key, $value);
                if ($value === false) {
                    unset($field[$key]);
                }
            }
            return empty($field) ? false : $field;
        }

        $is_add = !$this->is_exist();

        if (!in_array($field, $this->fields)) {
            return false;
        }
        //对各个字段进行处理
        switch ($field) {

            case 'title':
            case 'content':
                if(empty($value)) {
                    $this->error[] = $field."'s value is error";
                    return false;
                }
                $value = $this->forbidWord->produce($value);
                break;

            case 'article_id':
                if (!is_numeric($value) && !$is_add) {
                    $this->error[] = $field."'s value is error";
                    return false;
                }
                break;
            case 'topic_id':
                if (!is_numeric($value) && !$is_add) {
                    $this->error[] = $field."'s value is error";
                    return false;
                }
                break;
            case 'type_id':
            case 'articletype_id':
                if ($is_add)    break;
            case 'id':
            case 'state':
                return false;
                break;
            case 'official':

                if ($value == 'true') {
                    if(!$this->is_admin()) {
                        return false;
                    } else {
                        $value = 1;
                    }
                }
                break;

        }
        return $value;
    }

    /**
     * 是否完成添加阅读次数
     * @return bool
     */
    public function addReadNum() {
        if (!$this->is_exist()) {
            $this->error[] = "can't find article`";
            return false;
        }
        $data = $this->article;
        if (is_null($data['read_num']))
            return true;
        $data['read_num']++;
        $result = D($this->table)->save($data);
        if ($result) {
            $this->article = $data;
            return true;
        }
        return false;
    }
    /*
     * 返回文章详情
     * @return array;
     */
    public function getContent() {
        if (!$this->is_exist()) {
            $this->error[] = "can't find article`";
            return false;
        }
        $this->addReadNum();
        $field = $this->getArticleContentField();
        $content = $this->get($field);
        if (isset($this->article['user_id'])) {
            $author = $this->author;
            $content['nickname'] = $author['nickname'];
            $content['user_photo'] = $author['photo_src'];
        }
        return $content;
    }

    /**
     * 需要显示的字段
     * @return array
     */
    protected function getArticleContentField() {
        $articleField = array(
                'id',
                'content',
                'title',
                'remark_num',
                'like_num',
                'photo_src',
                'thumbnail_src',
                'type_id',
                'articletype_id' => 'type_id',
                'updated_time',
                'created_time',
        );
        if ($this->type_id == 7)
            $articleField[] = 'topic_id';
            $articleField[] = 'keyword';
        if ($this->type_id < 5) {
            $articleField[] = 'date';
            $articleField[] = 'read';
        }
        return $articleField;
    }

    /**
     * @param $content string 回复内容
     * @param $answerToUser  int|string 待回复的人
     * @return bool
     */
    public function addRemark($content, $answerToUser = 0) {
        if (!$this->is_exist()) {
            $this->error[] = "can't find article`";
            return false;
        }
        if (!$this->addRemarkNum())   return false;
        if (!$answerToUser) $answerUserId = 0;
        else {
            $answerUser = getUserInfo($answerToUser);
            $answerUserId = $answerUser['id'];
        }
        $data = array(
            "content" => $content,
            "created_time" => date("Y-m-d H:i:s", time()),
            "user_id" => $this->operator['id'],
            "article_id" => $this->article['id'],
            "articletypes_id" => $this->type_id,
            "answer_user_id" => $answerUserId,
        );
        $result = D('articleremarks')->add($data);
        if ($result) return true;
        $this->error[] = "remark can't add";
        return false;
    }

    /**
     * 添加remark的数量
     * @return bool
     */
    protected function addRemarkNum() {

        if (isset($this->article['remark_num'])) {
            $data = $this->article;
            $data['remark_num']++;
            $data['updated_time'] = date('Y-m-d H:i:s');
            $result = D($this->table)->save($data);
            if (!$result){
                $this->error[] = "article remark_num add error";
                return false;
            }
        }
        $pos = array(
            'article_id' => $this->article['id'],
            'articletype_id' => $this->type_id,
            'state' => 1,
        );
        $hotArticle = D('hotarticles')->where($pos)->find();
        if ($hotArticle) {
            $hotArticle['remark_num']++;
            $hotArticle['updated_time'] = date('Y-m-d H:i:s');
            if ($this->operator['id'] == $this->author['id'])
                $hotArticle['self_remark_num']++;

            $result = D('hotarticles')->save($hotArticle);
            if (!$result) {
                $this->error[] = "fatal add Hot Article remark_num ";
                return false;
            }
        }
        if (isset($this->article['topic_id'])) {
            $result = D('topics')->where('id=%d', $this->article['topic_id'])->find();
            $result['remark_num']++;
            $result['updated_time'] = date('Y-m-d H:i:s');

            addJoinTopicIds($this->article['topic_id'], $this->operator['stunum']);
            $result = D('topics')->save($result);
            if (!$result) {
                $this->error[] = "fatal add topics join_num";
                return false;
            }
        }
        return true;
    }

    /**
     * 返回文章类型
     * @param $type_id int 文章类型
     * @param $is_brief  boolean 是否简写
     * @return mixed
     */
    public static function getType($type_id, $is_brief = false) {
        $article_type = array(
            1     => '重邮新闻',
            2     => '教务在线',
            3     => '学术讲座',
            4     => '校务公告',
            5     => '哔哔叨叨',
            6     => '公告',
            7     => '话题文章',
        );
        $article_type_brief = array(
            1 => 'cyxw',
            2 => 'jwzx',
            3 => 'xsjz',
            4 => 'xwgg',
            5 => 'bbdd',
            6 => 'notice',
            7 => 'topic',
        );
        return $is_brief ? $article_type_brief[$type_id] : $article_type[$type_id];
    }

    public function articleType($is_brief=false) {
        return self::getType($this->type_id, $is_brief);
    }


}