<?php

namespace Admin\Controller;

use Think\Controller;

class UserController extends Controller
{
        private $operate = array(
                'delete'  => 'userStatus',
                'recover' => 'userStatus',
                'lock'    => 'userStatus',
                'unlock'  => 'userStatus'
        );
        protected $_status = array(
                'recover' => array('before_state'=>0,'after_state'=>1),
                'unlock' => array('before_state'=>2,'after_state'=>1),
                'lock' => array('before_state'=>1,'after_state'=>2),
                'delete' => array('before_state'=>array(1,2),'after_state'=>0),
        );

        protected  $_state = array(
                'delete' => 0,
                'normal' => 1,
                'lock'   => 2
        );

        public function _initialize()
        {
                //初始化
                $this->_status = array(
                        'recover' => array('before_state'=>$this->_state['delete'],'after_state'=>$this->_state['normal']),
                        'unlock' => array('before_state'=>$this->_state['lock'],'after_state'=>$this->_state['normal']),
                        'lock' => array('before_state'=>$this->_state['normal'],'after_state'=>$this->_state['lock']),
                        'delete' => array('before_state'=>array($this->_state['                       normal'], $this->_state['lock']),'after_state'=>$this->_state['delete']),
                );
        }

        /**
         * 分配任务
         */
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


        protected function userStatus($operate, $data)
        {
                $table = 'users';

                $Data = new DataController();

                if (empty($data) || empty($data)) {
                        return false;
                }

                $operate = strtolower($operate);
                //操作对state的改变

                //操作不存在报错
                if (empty($this->_status[$operate])) {
                        return false;
                } else {
                        extract($this->_status[$operate]);
                }

                if (!is_array($before_state)) {
                        $before_state = explode(',', $before_state);
                }


                $data = $Data->parameter($data, $table);

                $user = M($table)->where($data)->find();

                if(!$user) {
                        return false;
                }

                if (in_array($user['state'], $before_state)) {
                        $user['state'] = $after_state;
                        //修改成功
                        $result = M($table)->save($user);
                        if ($result) {
                                return true;
                        }
                }

                return true;
        }


}
