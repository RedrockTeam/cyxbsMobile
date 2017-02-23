<?php

namespace Home\Model;

use Think\Model;
use Think\Model\RelationModel;

class UsersModel extends RelationModel{
    protected $_link = array(        
			'Articles'=>array(    
			        'mapping_type'      => self::HAS_MANY,
			        'class_name'        => 'Articles',  
			        'foreign_key'       => 'user_id',              
			    ),       
			);

    public function addLog(){

    }

}