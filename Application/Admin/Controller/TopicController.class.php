<?php
/**
 * Created by PhpStorm.
 * User: pumbf
 * Date: 2017/4/5
 * Time: 23:29
 */

namespace Admin\Controller;
use Home\Common\Forbidword;
use Think\Controller;

class TopicController extends Controller
{
    private $_state = array(
        'delete' => 0,
        'normal' => 1,
        'lock'   => 2
    );
    protected $_status = array();
    private $operate = array(
        'delete'  => 'delete',
        'recover' => 'topicState',
        'lock'    => 'topicState',
        'unlock'  => 'topicState'
    );

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

    protected function topicState($operate, $data) {

        if (empty($operate) || empty($data)) {
            return false;
        }
        $table ='topics';
        $id = $data['id'];

        if (empty($id) || empty($table)) {
            return false;
        }

        $operate = strtolower($operate);

        //操作不存在,返回false
        if (empty($this->_status[$operate])) {
            return false;
        } else {
            $before_state = $this->_status[$operate]['before_state'];
            $after_state = $this->_status[$operate]['after_state'];
            if (!is_array($before_state)) {
                $before_state = explode(',', $before_state);
            }
        }

        $topic = M($table)->where($data)->find();

        if(!$topic) {
            return false;
        }

        if (in_array($topic['state'], $before_state)) {
            $topic['state'] = $after_state;
            //修改成功
            $result = M($table)->save($topic);
            if ($result) {
                return true;
            }
        }

        return false;
    }

    protected function delete($operate, $data){
        $topic = D('topics')->find($data['id']);
        if (!$topic)    return false;
        if (!in_array($topic['state'], $this->_status[$operate]['before_state']))
            return false;
        $topic['state'] = $this->_status[$operate]['after_state'];

        $result = D('topics')->save($topic);
        return $result ? true : false;
    }

    public function addTopic()
    {
        $information = I('post.');
        if (!session('admin')) {
            returnJson('403', '未登录不允许');
        }


        if (!$this->produceTopicInformation($information,$error)) {
            returnJson(404, $error);
        }

        if ($information['official'] == 'true') {
            //官方发起话题
            $information['official'] = 1;            //以红岩网校工作站名义创建的话题
        } else {
            $information['join_num'] = 1;
        }
            //个人发起话题
        $information['user_id'] = session('admin.user_id');

        if (empty($information['keyword']) || isset($information['id']) || is_null($information['user_id'])) {
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
}