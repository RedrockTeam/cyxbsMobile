<?php

namespace Admin\Model;

use Think\Model;

use Admin\Controller\DataController as Data;

class AdminModel extends Model
{
	/**
	 * 对密码字符串进行加密
	 * @param  string $password 密码原字符串
	 * @param  string $salt     加密盐值
	 * @return string           加密后的字符串
	 */
	protected function crcypt($password, $salt)
	{
		$password = hash_hmac('snefru256', $password, $salt);
		return $password;
	}

	/**
	 * 生成一个随机的salt
	 * @return string salt
	 */
	protected function makeSalt()
	{
		$salt = '';

		for ($i=0; $i<8; $i++) {
			$ascii = mt_rand(32, 126);
			$salt .= chr($ascii); 
		}
		return $salt;
	}

	/**
	 * 确认密码是否正确
	 * @param  string $password 密码
	 * @param  mixed $pos      学生必要信息
	 * @return bool          密码是否正确
	 */
	public function checkoutPwd($password, $pos='')
	{

		if (empty($pos)) {			
			if (empty($this->data)) {
				return false;
			}
		} elseif (is_array($pos)) {
			// $data = new Data;
			// $pos = $data->parameter($pos, 'admin');
			if (empty($pos))
				return false;
			$this->where($pos)->find();
		} elseif (is_string($pos)) {
			$this->find($pos);
		} else {
			return false;
		}

		$password = $this->crcypt($password, $this->salt);
		return $password == $this->password;
	}

	/**
	 * 改变用户的密码
	 * @return bool 操作是否成功
	 */
	public function changePassword($password, $newpassword)
	{	
		if (empty($this->data)) {
			E('this admin model is empty!');
		}
		
		if (!$this->checkoutPwd($password)) {
			return false;
		}
		$data['salt'] =  $this->makeSalt();;
		$data['password'] = $this->crcypt($newpassword, $data['salt']);
		$data['updated_time'] = date("Y-m-d H:i:s", time());
		$data['id'] = $this->id;
		return $result = $this->data($data)->save();

	}

	/**
	 * 向admin注册一个新的管理员
	 * @param  string $stunum  学号
	 * @param  int    $role_id 角色的id
	 * @return bool          是否创建成功0
	 */
	public function registerAdmin($user, $role_id)
	{

		if (empty($user) || empty($role_id)) {
			throw new \Exception('invaild parameter');
		}
		$result = $this->where("stunum='%s'", $user['stunum'])->find();
		
		if (!empty($result)) {
			return false;
		}
		$username = $user['username'];
		$stunum = $user['stunum'];
		
		//生成salt,和对各个参数的处理
		$salt = $this->makeSalt();
		$created_time = date("Y-m-d H:i:s", time());
		//初始密码为身份证后六位
		$password = $this->crcypt($user['idnum'], $salt);
		$auto = array('updated_time', 'time', 1, 'function');
		$user_id = $user['id'];
		$data = compact('password', 'salt', 'username', 'stunum', 'role_id', 'created_time', 'user_id');
		$is_register = $this->token(false)->add($data);
		
		return $is_register;
	}



}