<?php

namespace Home\Common;

class Forbidword 
{

	protected $error;

	protected $forbidwordList;			//敏感词列表

	protected $replace = '*';			//替换敏感词的字符

	public static $table_type = array(
		'articles'		=> 5,
		'notices'		=> 6,
		'topicarticles' => 7,
	  	'topics'		=> 3,
	  	'username'		=> 1,
	  	'introduction'	=> 2,
		);


	public function check($string, $IsIgnoreCase=false)
	{
		$len = strlen($string);
		
		if ($len===0) {
			return $string;
		}

		for($i; $i<$len; $i++) {
			//$q 为string的字符，$p对应该字符对应的数组
			$q = $i;
			$p = $this->forbidwordList[$string[$q]];

			while(is_array($p)) {
				$q++;
				$p = $this->forbidwordList[$string[$q]];
			}

			if (empty($p)) {
				continue;
			} else {
				//替换
				while($i <= $q) {
					$string[$i] = $this->replace;
					$i++;
				}
			}
		}                                      
	}

	//根据字段设置敏感词列表
	public function setForbidwordListByType($type)
	{	
		$wordList = S('cyxbsForbidword:'.$type);
		//是否缓存了
		if ($wordList === false) {
			
			$type_id = $this->table_type[$type];
			if (empty($type_id)) {
				$this->error = "error type, can't match this type";
				return false;
			}
			$pos = array('type_id' => $type_id, 'word_range.state' => 1);
			$wordList = M('word_range')
						->alias('word_range')
						->join('__FORBIDWORD___ forbidword on forbidword.id=word_range.w_id and forbidword.state=1')
						->where($pos)
						->field('value')
						->select();
			//敏感字符串数组
			foreach($wordList as &$value)
				$value = $value['value'];
			$wordList =  $this->transForbidwordList($wordList);
			S('cyxbsForbidword:'.$type, $wordList, 28800);
		}
		$this->forbidwordList = $wordList;
		return true; 
	}


	protected function transForbidwordList($wordList)
	{
		if (empty($wordList)) {
			return $wordList;
		}

		if (is_string($wordList)) {
			$wordList = explode(',', $wordList);
		}
		$forbidwordList = array();
		
		foreach ($wordList as $word) {
			$point = &$forbidwordList;
			$len = 0;
			while($word) {
				$char = substr($word, 0, 1);
				$word = substr($word, 1);
				$len++;
				$point[$char] = array();
				$point = &$point[$char];
			}
			$point = '&'.$len;
		}

		return $forbidwordList;
		
	}

	public function setForbidwordList($wordList)
	{
		$wordList = $this->transForbidwordList($wordList);
		
		if (!empty($wordList)) {
			$this->forbidwordList = $wordList;
			return true;
		}

		return false;

	}

	/**
	 * 设置将敏感字 替换的 字符
	 * @param char $word 字符
	 * @return boolean 是否成功	 
	 */
	public function setReplaceWord($word)
	{
		if (empty($word) || $this->check($word)) {
			$this->replace = $word;
			$this->error = '';
			return true;
		}
		if (empty($this->error))
			$this->error = 'replace word can\'t be '.$word.'.';
		return false;
	}

	/**
	 * 上个错误信息
	 * @return string 错误新息
	 */
	public function getLastError()
	{
		return $this->error;
	} 
}
