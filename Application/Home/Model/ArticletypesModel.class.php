<?php

namespace Home\Model;

use Think\Model;
use Think\Model\RelationModel;

class ArticletypesModel extends RelationModel{
    protected $_link = array(        
			'Hotarticles'=>array(    
			        'mapping_type'      => self::HAS_MANY,
			        'class_name'        => 'Hotarticles',  
			        'foreign_key'       => 'articletypes_id',              
			    ),        
			);

    public function addLog(){

    }

}