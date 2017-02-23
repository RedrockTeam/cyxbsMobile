<?php

namespace Home\Model;

use Think\Model;
use Think\Model\RelationModel;

class HotarticlesModel extends RelationModel{
    protected $_link = array(        
			'Articletypes'=>array(    
			        'mapping_type'      => self::BELONGS_TO,
			        'class_name'        => 'articletypes',  
			        'foreign_key'       => 'articletype_id',              
			    ),        
			);

    public function addLog(){

    }

    public function searchContent($typeid,$articleid){

    }

}