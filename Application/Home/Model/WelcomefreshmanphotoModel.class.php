<?php

namespace Home\Model;

use Think\Model;
use Think\Model\RelationModel;

class WelcomefreshmanphotoModel extends RelationModel
{
	protected $_link = array(
			'WElcomeFreshman' => array(
				'mapping_type'	=> self::BELONGS_TO,
				'class_name'	=> 'Welcomefreshman',
				'foreign_key'	=> 'article_id',
				),
		);
}