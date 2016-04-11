<?php

namespace Home\Model;

use Think\Model;
use Think\Model\RelationModel;

class ArticlPraisesModel extends RelationModel{
    protected $_link = array(        
			'Articles'=>array(    
			        'mapping_type'      => self::BELONGS_TO,
			        'class_name'        => 'articles',  
			        'foreign_key'       => 'article_id',              
			    ),        
			);

    public function addLog(){

    }

    public function searchContent($typeid,$articleid){

    }

}