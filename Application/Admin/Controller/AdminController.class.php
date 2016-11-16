<?php

namespace Admin\Controller;

use Think\Controller;

use Admin\Common\ClientInfo;

use Admin\Common\JWT;

class AdminController extends Controller
{
        protected $banInfo = array(
                'salt', 'password'
        );

        protected $operate = array(
                'delete'        => 'adminStatus',
                'unlock'        => 'adminStatus',
                'recover'       => 'adminStatus',
                'lock'          => 'adminStatus'
        );

        protected  $_state = array(
                'delete' => 0,
                'normal' => 1,
                'lock'   => 2
        );

        protected $_status = array();

        public function _initialize()
        {
                //初始化
                $this->_status = array(
                        'recover' => array('before_state'=>$this->_state['delete'],'after_state'=>$this->_state['normal']),
                        'unlock' => array('before_state'=>$this->_state['lock'],'after_state'=>$this->_state['normal']),
                        'lock' => array('before_state'=>$this->_state['normal'],'after_state'=>$this->_state['lock']),
                        'delete' => array('before_state'=>array($this->_state['normal'], $this->_state['lock']),'after_state'=>$this->_state['delete']),
                );
        }

        /**
         * 像admin注册一个新的管理员
         * @param  string $stunum  学号
         * @param  int    $role_id 角色的id
         * @return bool          是否创建成功0
         */
        protected function registerAdmin($stunum, $role_id)
        {

                if (empty($stunum) || empty($role_id)) {
                        throw new \Exception('invaild parameter');
                }
                $user = M('users')->where("stunum='%s'",$stunum)->find();

                //如果找不到该用户

                if (!$user) {
                        returnJson(404, 'Don\'t register in cyxbs');

                }

                $is_register = D('admin')->registerAdmin($user, $role_id);
                return $is_register;
        }



        /**
         * 初始化admin(兼容原来的管理员组)
         * @param  string $stunum 学号
         * @return bool         是否创建成功
         */
        protected function init($stunum)
        {
                $admin = M('admin')->find();
                if (empty($admin)) {
                        $role = D('role')->where("name='%s'", 'root')->find();
                        $result = $this->registerAdmin($stunum, $role['id']);
                } else {
                        $role = D('role')->where("name='%s'", 'backup')->find();
                        $result = $this->registerAdmin($stunum, $role['id']);
                }
                return $result;
        }

        /**
         * 对登录的接口
         */
        public function login()
        {
                //表单验证
                if (!M()->autoCheckToken(I('post.'))) {
                        $this->error('非法操作', U('Index/login'), 3);
                }
                $informaetion = I('post.');
                $stunum = $informaetion['stuNum'];
                $password = $informaetion['password'];

                //存活时间
                $expireTime = mktime(0,0,0)+86400-time();
                $times = S('adminLogin:'.$stunum);
                if (!isset($times)) {
                        S('adminLogin:'.$stunum, 1, $expireTime);
                } else {
                        if ($times > 5) {
                                $admin = M('admin')->where("stunum='%s'",$stunum)->find();
                                if (!empty($admin) && $admin['state'] == 1) {
                                        $admin['state'] = 2;
                                        M('admin')->data($admin)->save();
                                }
                                $this->error('该用户已被锁定', U('Index/login'), 3);
                        } else {
                                $times += 1;
                                S('adminLogin:'.$stunum, $times, $expireTime);
                        }
                }

                if (empty($stunum) || empty($password)) {
                        $this->error('用户或密码为空', U('Index/login'), 3);
                }

                $admin = M('admin')->where("stunum='%s'", $stunum)->find();

                if (empty($admin)) {
                        $user = M('users')->where("stunum='%s'", $stunum)->find();
                        //未找到该用户
                        if (empty($user)) {
                                $this->error('你不是掌邮的用户', U('Index/login'), 3);
                        }
                        //未初始化的管理员的密码为其身份证后6位
                        if ($password == $user['idnum']) {

                                //查询最初管理员的表是否有该用户
                                $exist =  M('administrators')->where("user_id='%d'", $user['id'])->find();
                                if ($exist) {
                                        //将管理员导入新的表
                                        if ($this->init($stunum)) {
                                                $admin = M('admin')->where("stunum='%s'", $user['stunum'])->find();
                                                if (empty($admin)) {
                                                        $this->error('服务器错误',      U('Index/login'), 3);
                                                } else {
                                                        //注入信息
                                                        $this->immitInfo($admin);
                                                }
                                        } else {
                                                $this->error('服务器错误', U('Index/login'), 3);
                                        }
                                } else {
                                        $this->error('你无权操作', U('Index/login'), 3);
                                }
                        } else {
                                $this->error('密码错误', U('Index/login'), 3);
                        }
                } else {
                        if ($admin['state'] == 1) {
                                if (D('admin')->checkoutPwd($password, array('stunum'=> $stunum))) {

                                        //将一些需要的信息注入到session
                                        $this->immitInfo($admin);
                                } else {
                                        $this->error('密码错误', U('Index/login'), 3);
                                }
                        } else {
                                $messages = array('0' => '你已经不是管理员了！', '2'=> "你的账号被锁定了~");
                                $this->error($messages[$admin['state']], U('Index/login'), 3);
                        }

                }
        }

        protected function pwdstrength($password)
        {
                $modes = 0;
            if (strlen($password) < 8) return $modes;
            if (preg_match("/[1-9]/", $password)) $modes++; //数字
            if (preg_match("/[a-z]/", $password)) $modes++; //小写
            if (preg_match("/[A-Z]/", $password)) $modes++; //大写
            if (preg_match("/\W/", $password)) $modes++; //特殊字符
             return $modes;
        }

        /**
         * 修改密码的接口
         */
        public function changePassword()
        {
                //表单验证
                // if (!M()->autoCheckToken(I('post.'))) {
                //      returnJson(403);

                // }
                $information = I('post.');
                $id = session('admin.id');

                $admin = D('admin');

                if (!$admin->find($id)) {
                        returnJson(404);
                }

                $password = $information['newpassword'];
                $confirmpassword = $information['confirmpassword'];

                if ($this->pwdstrength($password) < 2) {
                        returnJson(404, '密码不符合规范');
                }



                if($password != $confirmpassword) {
                        returnJson(404, 'confirmpassword != password');

                }

                $result = $admin->changePassword($information['oldpassword'], $password);

                if ($result) {
                        returnJson(200);
                } else {
                        returnJson(404);
                }

        }

        /**
         * 处理登录后对信息的处理
         * @param  array $data 身份信息
         */
        protected function immitInfo($info)
        {
                $data['admin_id'] = $info['id'];
                $data['login_time'] = date("Y-m-d H:i:s", time());
                //获取登录ip
                $data['login_ip'] = get_client_ip();

                //获取浏览器信息
                $browser = ClientInfo::getBrowser();
                $data['browser'] = empty($browser) ? '未知' : $browser[name];

                //获取系统信息
                $platform = ClientInfo::getOs();
                $data['platform'] = empty($platform) ? '未知' : $platform;
                M('loginlist')->add($data);
                filter($info, $this->banInfo, true);

                session('admin', $info);
                //清理错误登录信息
                S('adminLogin:'.$stunum, null);

                $this->success('登录成功',U('Index/index'), 3);
        }

        /**
         *  管理员的状态
         */
        public function adminStatus($operate, $data)
        {

                if (empty($data) || empty($operate)) {
                        return false;
                }
                $table = 'admin';

                //操作不存在报错
                if (empty($this->_status[$operate])) {
                        returnJson(404);
                } else {
                        extract($this->_status[$operate]);
                }

                $Data = new DataController;

                $before_state = !is_array($before_state) ? explode(',', $before_state) : $before_state;

                $data = $Data->parameter($data, $table);

                $admin = M($table)->where($data)->find();


                if(!$admin) {
                        return false;
                }

                if ($operate == 'unlock') {
                        S('adminLogin:'.$admin['stunum'], null);
                }

                if (in_array($admin['state'], $before_state)) {
                        $admin['state'] = $after_state;
                        //修改成功
                        $result = M($table)->save($admin);
                        if ($result) {
                                return true;
                        }
                }

                return false;
        }


        /**
         * 改变角色
         */
        public function changeRole()
        {
                /*
                $post = array("
                        "__hash__" => $token,
                        "before_role_id" => $before_id,
                        "after_role_name" => $name,
                        "after_role_id" => $after_id,
                        stuNum  => $stunum
                ")
                 */
                $post = I('post.');
                // var_dump($post);exit;
                //实例化
                extract($post);
                //当取消管理员
                if ($after_role_id === '-1') {
                        if ($after_role_name !== '用户') {
                                returnJson(404);

                        }
                } else {
                        //错误的role_id
                        $role = M('role')->where("id=%d", $after_role_id)->find();
                        if (empty($role)) {
                                returnJson(404);

                        }

                        if ($after_role_name !== $role['display_name']) {
                                //错误参数
                                returnJson(404);

                        }
                }

                $admin = M('admin')->where("stunum='%s'", $stuNum)->find();
                //是否原来注册过
                if (empty($admin)) {
                        if ($before_role_id     != -1) {
                                returnJson(404);

                        } else {
                                $is_register = $this->registerAdmin($stuNum, $after_role_id);
                                if ($is_register) {
                                        returnJson(200);
                                } else {
                                        returnJson(500);
                                }

                        }
                } else {
                        if ($before_role_id == -1) {
                                if ($admin['state'] != 0) {
                                        returnJson(404);

                                } else {
                                        $admin['state'] = 1;
                                        $admin['role_id'] = $after_role_id;
                                }
                        } else {
                                if ($before_role_id != $admin['role_id']) {
                                        returnJson(404);

                                } else {
                                        if ($after_role_id == -1) {
                                                $admin['state'] = 0;
                                        } else {
                                                $admin['role_id'] = $after_role_id;
                                        }
                                }
                        }
                }
                $admin['updated_time'] = date("Y-m-d H:i:s", time());
                $result = M('admin')->save($admin);
                if ($result) {
                        returnJson(200);
                } else {
                        returnJson(500);
                }
        }

        /**
         * 重置密码
         */
        public function resetPassword()
        {
                $informaetion = I('post.');
                $stunum = I('post.stunum');
                $admin = D('admin');


                if ($admin->where("stunum='%s'", $stunum)->find()) {
                        returnJson(404);

                }

                $user = M('users')->where("stunum='%s'", $stunum)->find();
                $result = D('admin')->changePassword($user['idnum'], array("stunum"=>$stunum));

                if ($result) {
                        returnJson(200);
                } else {
                        returnJson(500);
                }

        }

        /**
         * 对admin的操作
         * @return json 是否修改成功
         */
        public function operate()
        {
                $operate = I('post.operate');
                $data = I('post.data');
                if (!isset($operate) || !isset($data)) {
                        returnJson(801);
                }

                $action = empty($this->operate[$operate])
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
         * 登出
         */
        public function logout()
        {
                session('admin', null);
                $this->redirect('Index/login');
        }

}