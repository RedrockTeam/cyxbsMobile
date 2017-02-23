<?php

namespace Admin\Model;

use Think\Model\RelationModel;

class RoleModel extends RelationModel
{
	protected $_link = array(
		'Head' => array(
			'mapping_type'	=> self::HAS_MANY,
			'class_name'	=> 'Role',
			'parent_key'	=> 'p_id',
		),

		'Servant' => array(
			'mapping_type'	=> self::BELONGS_TO,
			'class_name'	=> 'Role',
			'parent_key'	=> 'p_id',
			'mapping_order'	=> 'id',
		),

		
		'Ability' => array(
			'mapping_type'	=> self::MANY_TO_MANY,
			'class_name'	=> 'permission',
			'foreign_key'	=> 'role_id',
			'relation_for' 	=> 'relation_foreign_key',
			'relation_table'=> 'cyxbsmobile_role_permission',
		),
	);
	/**
	 * 获得Role所有的关联的数据
	 * @param string $method 关系类型的名字
	 * @param int $id role的id值
	 * @return array  所有Role子集的信息
	 */
	public function relationInfo($method, $id = null)
	{	
		if (!empty($id)) {
			$this->find($id);
		}
		
		$method = ucfirst($method);
		if(!array_key_exists($method, $this->_link)) {
			E('error relate method');
			return false;
		}
		
		if (!isset($this->id)) {
			throw new \Exception("not find \$id value");
			return false;
		}
		$info = $this->relationGet($method);
		return $info;
	}
	
	/**
	 * 该角色所有的能力(包括下属的能力)(递归调用)
	 * @param  int $id admin的Id值
	 * @return array   所有能执行 permition 的信息
	 */
	public function ability($id = null)
	{	
		if (!empty($id)) {
			$this->find($id);
		}

		$abilities = $this->relationInfo('ability', $id);
		$servant = $this->relationInfo('servant', $id);
		if (!empty($servant)) {
			//递归获取所有的能力信息
			$ability = $this->ability($servant['id']);
			$abilities = array_merge($abilities, $ability);
			$abilities = array_unique($abilities, SORT_REGULAR);
		}
		return $abilities;
	}

	// /***/
	// public function servant($id = null) {
	// 	$servants = array();
		
	// 	if (!empty($id)) {
	// 		$this->find($id);
	// 	}

	// 	while()

	// } 
}