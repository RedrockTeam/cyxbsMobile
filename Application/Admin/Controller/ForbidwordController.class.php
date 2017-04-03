<?php

namespace Admin\Controller;

use Think\Controller;

class ForbidwordController extends Controller
{
        protected $_error;

        private $operate = array(
                'delete'  => 'forbidwordState',
                'recover' => 'forbidwordState',
                'active'  => 'forbidwordState',
                'inactive'=> 'forbidwordState',
                'add'     => 'add',
                'edit'    => 'edit',
        );

        //type_id => type eg 1 => 'cyxw'
        protected  $forbidword_type = array();

        protected $_state = array(
                        'delete' => 0,
                        'normal' => 1,
                        'inactive'       => 2
                );

        protected $_status = array();

        public function _initialize()
        {
                $this->forbidword_type =  ArticleController::getType();
                $this->forbidword_type['-1'] = 'nickname';
                //初始化
                $this->_status =  array(
                        'recover' => array('before_state'=>$this->_state['delete'],'after_state'=>$this->_state['normal']),
                        'active' => array('before_state'=>$this->_state['inactive'],'after_state'=>$this->_state['normal']),
                        'inactive' => array('before_state'=>$this->_state['normal'],'after_state'=>$this->_state['inactive']),
                        'delete' => array('before_state'=>array($this->_state['normal'], $this->_state['inactive']),'after_state'=>$this->_state['delete']),
                );

        }

        //获取$forbidword_type
        public function getType()
        {
                return $this->forbidword_type;
        }

        /**
         * 修改forbidword状态
         * @return [type] [description]
         */
        public function operate()
        {
                $operate = I('post.operate');
                $data = I('post.data');
                if (!isset($operate) || !isset($data)) {
                        returnJson(801);
                }

                $action =       empty($this->operate[$operate])
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
         * 增加一个关键词
         * @return [type] [description]
         */
        protected function createForbidword($forbidword)
        {
                if (empty($forbidword)) {
                        $this->_error = 'create forbidword error';
                        return false;
                }
                $word['value'] = $forbidword;
                $word['created_time'] = date('Y-m-d H:i:s');
                $word['updated_time'] = date('Y-m-d H:i:s');
                //创建字段
                $id = M('forbidwords')->data($word)->add();
                return $id;
        }

        /**
         * 修改，增加违规字的范围
         * @param string $value 违规字内容
         * @param mixed $range  违规字约束的范围
         * @return bool                 是否成功
         */
        protected function changeForbidwordRange($value, $range)
        {
                if (empty($value)) {
                        $this->_error = "forbidword is null!";
                        return false;
                }

                if(!is_array($range)) {
                        //用 ','进行分割
                        $range = explode(',', $range);
                }

                $forbidword = M('forbidwords')->where("value='%s'", $value)->find();
                if (!$forbidword) {
                        $w_id = $this->createForbidword($value);
                        if (!$w_id) {
                                $this->_error = 'create error';
                                return false;
                        }
                } else {
                        if ($forbidword['state'] == $this->_state['delete']) {
                                if (empty($range)) {
                                        $this->_error = "Forbideword ".$value." don't exists";
                                        return false;
                                } else {
                                        if (!$this->forbidwordState('recover', $forbidword)) {
                                                $this->_error = "find a error during recover forbidword";
                                                return false;
                                        }
                                }
                        }
                        $w_id = $forbidword['id'];
                }
                //删除错误的范围
                foreach ($range as $key => &$type) {
                        if (is_numeric($type)) {
                                if (isset($this->forbidword_type[$type]))
                                        continue;
                        }
                        if (false === $type = array_search($type, $this->forbidword_type)) {
                                unset($key);
                        }
                }

                //此关键词修改前的区域
                $pos = array("w_id" => $w_id, 'state' => $this->_state['normal']);
                $forbids = M('word_range')->where($pos)->select();
                //修改后不存在的区域进行删除
                foreach ($forbids as $forbid) {
                        //改变后仍存在的区域不处理
                        if(false !== $key = array_search($forbid['type_id'], $range)){
                                unset($range[$key]);
                                continue;
                        }
                        //如果不存在，则删除
                        if(!$this->forbidWordRangeState('delete', $forbid)) {
                                $this->_error = 'forbidword range delete error';
                                return false;
                        }
                }
                //修改前不存在的进行添加
                foreach ($range as $key => $type) {
                        if (!is_numeric($type)) {
                                continue;
                        }

                        $pos = array('w_id'=>$w_id, 'type_id'=>$type);
                        $forbid = M('word_range')->where($pos)->find();
                        //如果为原来被删除的
                        if ($forbid) {
                                if (!$this->forbidWordRangeState('recover', $forbid)) {
                                        return false;
                                }
                        }
                        //如果为不存在的
                        $forbid = $pos;
                        $forbid['created_time'] = date('Y-m-d H:i:s');
                        if (!M('word_range')->add($forbid)) {
                                $this->_error = "forbid create error";
                                return false;
                        }
                }
                return true;
        }

        /**
         * 暴露的接口,接收请求参数
         * @return json
         */
        public function forbidword()
        {
                $information = I('post.');
                $forbidword = $information['forbidword'];
                $range = $information['range'];

                if (!isset($range)) {
                        $result = $this->getForbidwordRange($forbidword);
                } else {
                        $result = $this->changeForbidwordRange($forbidword, $range);
                }

                if ($result) {
                        returnJson(200);
                } else {
                        returnJson(404, $this->_error);
                }
        }

        /**
         * 获取违规字应用范围
         * @param  [type] $value [description]
         * @return [type]        [description]
         */
        protected function getForbidwordRange($data)
        {
                if (empty($data)) {
                        $this->_error = 'Missing forbidword\'s Parameters';
                        return false;
                }
                if (!is_array) {
                        $data = array("value" => $data);
                }
                $forbidword = M("forbidwords")
                                ->where("state=%d",$this->_state['normal'])
                                ->where($data)
                                ->find();

                if (!$forbidword) {
                        $this->_error = "Can't find which forbidword's value is ".$data['value'];
                        return false;
                }
                //获取所有应用范围
                $range = M("word_range")
                            ->where("w_id=%d", $forbidword['id'])
                            ->where("state=%d", $this->_state['normal'])
                            ->fields('type_id')
                            ->select();

                if ($range === false) {
                        $this->_error = 'select is error';
                        return false;
                }

                $range = '';
                if (!empty($range)) {
                        foreach ($range as $value) {
                                $range = $range.$value['type_id'].',';
                        }
                        $range = rtrim($range,',');
                }

                return $range;
        }

        /**
         * 修改违规字的是否应用
         * @param  string $operate 修改的状态
         * @param  array $data    违规字的属性
         * @return bool          修改是否成功
         */
        protected function forbidwordState($operate, $data)
        {
                $table = 'forbidwords';
                // if ($operate === 'delete') {
                //      if (!$this->changeForbidwordRange($data, array())) {
                //              return false;
                //      }
                // }
                return $this->changeState($operate, $data, $table);
        }

        /**
         * 改变状态参数
         * @param  string $operate 操作名
         * @param  array  $data    需修改的属性
         * @param  string $table   修改元素所在的表
         * @return bool          是否修改成功
         */
        protected function changeState($operate, $data, $table)
        {
                if (empty($operate) || empty($data)) {
                        $this->_error = 'empty parameter';
                        return false;
                }

                $Data = new DataController();

                //操作不存在,返回false
                if (empty($this->_status[$operate])) {
                        $this->_error = 'error operate';
                        return false;
                } else {
                        //$before_state 和 $after_state
                        extract($this->_status[$operate]);
                        $before_state = $this->_status[$operate]['before_state'];
                        $after_state = $this->_status[$operate]['after_state'];
                        if (!is_array($before_state)) {
                                $before_state = explode(',', $before_state);
                        }
                }

                $data = $Data->parameter($data, $table);

                $data = M($table)->where($data)->find();

                if (!$data) {
                        $this->_error = 'Dismatch '.$table;
                        return false;
                }

                if (in_array($data['state'], $before_state)) {
                        $data['state'] = $after_state;
                        $data['updated_time'] = date('Y-m-d H:i:s');
                        //修改成功
                        $result = M($table)->save($data);
                        if ($result) {
                                return true;
                        }
                }
                $this->_error = "Can\'t change the $table state!";
                return false;
        }

        /**
         * change the forbid state
         * @param  string $operate operate in $this->operate
         * @param  array  $data    $forbidword info
         * @return bool         是否修改成功
         */
        protected function forbidWordRangeState($operate, $data)
        {
                $table = 'word_range';
                return $this->changeState($operate, $data, $table);
        }


        /**
         * select2 获取选项
         * @return json
         */
        public function range()
        {
                //搜索的值
                $information = I('q');
                $data = array();
                $preg = '/.*'.$information.'.*/';
                foreach($this->forbidword_type as $key => $value) {
                        //搜索，用正则进行匹配
                        if(empty($information) || preg_match($preg, $value)) {
                                $data[] = array('id' => $key, 'text'=> $value);
                        }
                }
                returnJson(200, '', $data);
        }
}