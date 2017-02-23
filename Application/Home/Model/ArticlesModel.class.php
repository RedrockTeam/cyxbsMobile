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
			'ArticleRemarks'=>array(    
			        'mapping_type'      => self::HAS_MANY,
			        'class_name'        => 'Articleremarks',  
			        'foreign_key'       => 'article_id',              
			    ),   
			'ArticlePraises'=>array(    
			        'mapping_type'      => self::HAS_MANY,
			        'class_name'        => 'Articlepraises',  
			        'foreign_key'       => 'article_id',              
			    ),           
			);

    public function addLog(){

    }

}