<?php

namespace Admin\Common;

class Datatable
{
	protected $dataTable;
	//需要返回的字段
	protected $displayField;
	//搜索的字段
	protected $searchField;
	//需要join的字段
	protected $joinField;

	protected $parameter;

	protected $data;

	protected $draw;

	protected $error;

	public function __construct($dataTable, $displayField = array())
	{
		if (!$this->setDataTableMessage($dataTable)) {
			$this->error = 'errpr initialize';
			return false;
		}
		//显示如果为空
		if (empty($displayField)) {
			foreach ($dataTable['columns'] as $key => $value) {
				if ($value['id']) {
					$displayField[] = $value['id'];
				}
			}
		}
		if (!$this->setDisplayField($displayField)) {
			$this->error = 'setDisplayField error';
			return false;
		}
		//搜索
		if (!$this->setSearchField($dataTable)) {
			$this->error = 'setSearchField error';
			return false;
		}
		
		
	}
}