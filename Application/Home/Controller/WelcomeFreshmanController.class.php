<?php

namespace Home\Controller;

use Think\Controller;

class WelcomeFreshmanController extends Controller 
{	
	//各个部分对映的type id
	protected $type = array(
		'qsqk' => 0,
		'rcsh' => 1,
		'zbms' => 2,
		'zbmj' => 3,
		'yccy' => 4,
		'yxxz' => 5,
		'yxjs' => 6,
		'mzcy' => 7,
	);

	/**
	 * 查询宿舍情况
	 */
	public function dormitoryIntroduction() {
		
		$sql = 'type='.$this->type['qsqk'];
		$field = array(
			'id',
			'introduction',
		);
		$json =  $this->selectMessage($sql, $field);
		echo $json;
	}

	/**
	 * 日常生活
	 */
	public function daylyLife() {
		$sql = 'type='.$this->type['rcsh'];
		$field = array(
			'id',
			'name',
			'message' => 'address',
		);
		$json = $this->selectMessage($sql, $field);
		echo $json;
	}
	/**
	 * 周边美食
	 */
	public function surroundingFood() {
		$sql = 'type='.$this->type['zbms'];
		$field = array(
			'id',
			'name',
			'message' => 'address',
			'introduction',
		);
		$json = $this->selectMessage($sql, $field);
		echo $json;
	}
	/**
	 * 周边风景
	 */
	public function surroundingView() {
		$sql = 'type='.$this->type['zbmj'];
		$field = array(
			'id',
			'name',
			'message' => 'tourRoute',
			'introduction',
		);
		$json = $this->selectMessage($sql, $field);
		echo $json;
	}
	/**
	 * 原创重邮
	 */
	public function cquptOriginal() {
		$sql = 'type='.$this->type['yccy'];
		$field = array(
			'id',
			'name',
			'message' => 'time',
			'introduction',
			'external_message' => 'video_url',
		);
		$json = $this->selectMessage($sql, $field);
		echo $json;
	}
	/**
	 * 优秀教师
	 */
	public function outstandingTeacher() {
		$sql = 'type='.$this->type['yxjs'];
		$field = array(
			'id',
			'name',
			'message' => 'college'
		);
		$json = $this->selectMessage($sql, $field);
		echo $json;
	}
	/**
	 * 优秀学子
	 */
	public function outstandingStudent() {
		$sql = 'type='.$this->type['yxxz'];
		$field = array(
			'id',
			'name',
			'message' => 'description',
			'introduction',
		);
		$json = $this->selectMessage($sql, $field);
		echo $json;
	}

	public function cquptView() {
		$sql = 'type='.$this->type['mzcy'];
		$field = array(
			'id',
			'name',	
		);
		$json = $this->selectMessage($sql, $field);
		echo $json;

	}

	/**
	 * 数据库查询
	 * @param  string $sql    指定地址
	 * @param  string $field  指定返回的标签
	 * @return array          搜索的结果
	 */
	protected function selectMessage($sql, $field) {
		$page = I('page');
		$size = I('size');
		$page = empty($page)? 0 : $page;
		$size = empty($size)? 5 : $size;
		$firstRow = $page*$size;
		$welcome = D('Welcomefreshman');
		$total = $welcome->field('count(*)')
						 ->where($sql)
						 ->select();
		$total = $total[0]['count(*)'];
		$limit = $firstRow.','.$size;
		$data = $welcome->relation(true)
						->where($sql)
						->field($field)
						->limit($limit)
						->order('id')
						->select();
		$info = array(
			'status' 	=> '200',
			'info'		=> 'success',
			'total'		=> $total,
			'data'		=> $data
		);
		return json_encode($info);
	}

	

}