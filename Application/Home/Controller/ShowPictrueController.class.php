<?php

namespace Home\Controller;

use Think\Controller;

class ShowPictureController extends Controller
{	
	private $version = '201612';

	//上传
	public function upload()
	{
		
		if (!$this->verifyRole()) {
			returnJson(403);
		}
		$info = I('post.');
		$start_time = timeFormate($info['startTime']);

		$creared_time = timeFormate();
		
		$stunm = empty($info['stuNum']) ? session('admin.stunum') : $info['stuNum'];
		$photo_src = $info['photo_src'];
		//显示区域
		$display_column = $info['column'];
		if (empty($display_columns) || empty($photosrc)) {
			returnJson(404);
		}
		$target_url = $info['target_url'];
		$user = M('users')->where('stnum=\'%S\'', $stunm)->find();
		if (empty($user)) {
			returnJson(404);
		}
		$user_id = $user['id'];
		$annotation = $info['annotation'];
		$data = compact('start_time', 'creared_time', 'stunm', 'photo_src', 'display_column', 'user_id', 'target_url', 'annotation');
		$result = M('displaypicture')->add($data);
		if ($result) {
			returnJson(200);
		} else {
			returnJson(404);
		}
	}

	//显示
	public function showPictrue()
	{
		$column = I('column');
		
		if (empty($column)) {
			returnJson(801);
		}

		if (false !== $display = S('displayColumn:'.$column)) {
			$current_time = timeFormate();
			$pos = array(
				'state' => 1,
				'start_time' => array('EGT', $current_time),
				'display_column' => $column,
			);
			$field = array('target_url', 'photo_src');
			$display = M('displaypicture')->where($pos)->field($field)->order('start_time desc')->find();
			if (!$display) {
				returnJson(404);
			}
			S('displayColumn:'.$column, $display, 1200);
		}

		returnJson(200, '', $display);
	}

	//重置缓存
	public function refresh()
	{
		if (!$this->verifyRole()) {
			returnJson(403);
		}
		//S('displayColumn:'.$column, null);
		returnJson(200);
	}

	protected function verifyRole()
	{
		$stuNum = I('post.stuNum');
		$baseConfirm = new BaseController;
		return is_admin($stuNum);
	}
	/**
	 * 上传记录
	 */
	public function  uploadList()
	{
		if (!$this->verifyRole()) {
			returnJson(403);
		}

		$info = I('post.');
		
		$page = empty($info['page']) ? 0 : $info['page'];
		$size = empty($info['size']) ? 10: $info['size'];
		$pos = array('state'=> 1);
		
		if (!empty($info['column'])) {
			$pos['display_column'] = $info['column'];
		}

		if (!empty($info['stuNum'])) {
			$user = M('users')->where('stunm=\'%s\'', $info['stuNum'])->find();
			if (!$user) {
				returnJson(404, 'error stunum');
			}
			$pos['user_id'] = $user['id'];
		}

		$field = array(
				'displaypicture.id' => 'id', 
				'display_column' => 'column',
				'stunum' => 'uploaderStunum',
				'username' => 'uploaderName',
				"displaypicture.photo_src",
				'displaypicture.start_time',
				'target_url',
				'displaypicture.created_time',
				'annotation',
				);
		//查询
		$data = M('displaypicture')
					->alias('displaypicture')
					->join('join __USERS__ ON __USERS__.id = displaypicture.user_id')
					->where($pos)
					->field($field)
					->order('displaypicture.creared_time desc')
					->limit($page*$size, $size)
					->select();
		returnJson(200, '', array('version' => $this->version,'data'=> $data));
	}

	//修改
	protected function editDb($object, $change, $primarykeyName = 'id')
	{
		if (empty($object))
			return false;
		
		elseif (is_array($object)) {	
			if (!in_array($primarykeyName, $object)) {
				return false;
			}
			$pk = $object[$primarykeyName];
		} else {
			$pk = $object;
		}
		$tableColumns = M('displaypictrue')->getDbFields();
		foreach ($change as $key => $value) {
			if (!in_array($key, $tableColumns)) {
				return false;
			}
		}
		$change[$primarykeyName] = $pk;
		$change['created_time'] =  timeFormate();
		$result = M('displaypictrue')->save($change);
		return $result ? true : false;
	}

	public function delete()
	{
		if (!$this->verifyRole()) {
			returnJson(403);
		}
		$id = I('post.id');
		if ($this->editDb($id, array('state' => 0))) {
			returnJson(200);
		} else {
			returnJson(404);
		}
	}

	public function edit()
	{
		if (!$this->verifyRole()) {
			returnJson(403);
		}
		$info = I('post.');
		$stunum = empty($info['stuNum']) ? session('admin.stunum') : $info['stuNum'];
		$user = M('users')->where('stunum=\'%s\'', $stunum)->find();
		if (!$user) {
			returnJson(404, 'error stunum');
		}
		
		unset($info['stuNum']);
		unset($info['idNum']);
		$info['user_id'] = $user['id'];
		if ($this->editDb($info['id'], $info))
			returnJson(200);
		else
			returnJson(404);
	}

}