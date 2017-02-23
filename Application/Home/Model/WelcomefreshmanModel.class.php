<?php

namespace Home\Model;

use Think\Model;
use Think\Model\RelationModel;

class WelcomefreshmanModel extends RelationModel
{
	protected $_link = array (
				'photo' => array(
								'mapping_type' 	=> self::HAS_MANY,
								'class_name'	=> 'Welcomefreshmanphoto',
								'foreign_key'	=> 'article_id',
								'mapping_fields'=> array(
									'id','photo_src','photo_thumbnail_src',
									),
							),
		);
}