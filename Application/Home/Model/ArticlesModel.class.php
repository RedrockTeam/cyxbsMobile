<?php

namespace Home\Model;

use Think\Model;
use Think\Model\RelationModel;

class ArticlesModel extends RelationModel{
    private $_log;
    protected $_link = array(        
			'Users'=>array(    
			        'mapping_type'      => self::BELONGS_TO,
			        'class_name'        => 'Users',  
			        'foreign_key'       => 'user_id',              
			    ),       
			);

    public function addLog(){

    }

}