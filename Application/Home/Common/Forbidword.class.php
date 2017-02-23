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


	public function check(&$string, $IsIgnoreCase=false)
	{
		$len = $this->strlen($string);
		$has_forbid = false;

		if ($len===0) {
			return $string;
		}
		if($IsIgnoreCase) {
			for ($i; $i<$len; $i++) {
				$q = $i;
				while(is_array($p) || !empty($stack)) {
					while(is_array($p)) {
						if($string[$p]>='a' && $string[$p]<='z') {
							$stack['char'][] = strtoupper($stirng[$q]);
							$stack['pos'][] = $q;
						} elseif($string[$p]>='A' && $string[$p]<='Z') {
							$stack[] = strtolower($string[$q]);
							$stack['pos'][] = $q;
						}
						$q++; $p = $this->forbidwordList[$string[$q]];
					}

				}
			}
		} else {
			for($i; $i<$len; $i++) {
				//$q 为string的字符，$p对应该字符对应的数组
				$q = $i;
				$p = $this->forbidwordList[$string[$q]];
				$length = '';
				while(empty($p)) {
					if(isset($p['length'])) {
						$length = $p['length'];
					}
					$q++;
					$p = $this->forbidwordList[$string[$q]];
				}

				if (empty($length)) {
					continue;
				} else {
					//替换
					$has_forbid = true;
					while(--$length) {
						$string[$i] = $this->replace;
						$i++;
					}
				}
			}
		}

		return $has_forbid;                                   
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


	public function transForbidwordList($wordList)
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
				if (!isset($point[$char])) {
					$point[$char] = array();
				}
				$point = &$point[$char];
			}
			$point['length'] = $len;
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

	
	//字符串截取
	public function substr($str, $offset = 0, $length='', $charset= 'utf-8')
	{
	    if (empty($str)) {
	        return false;
	    }
	    if($length === '') {
	    	$length = $this->strlen($str);
	    }
	    return iconv_substr($str, $offset, $length, $charset);
	}
	//字符串长度
	public function strlen($str, $charset= 'utf-8')
	{
	    if (empty($str)) {
	        return 0;
	    }
	    return iconv_substr($str, $charset);
	}
	//字符串长度
	public function strpos($str, $needle, $offset=0, $charset='utf-8')
	{
		return iconv_strpos($str, $needle, $offset=0, $charset='utf-8');
	}

	public function strrpos($str, $needle, $charset='utf-8')
	{
		return iconv_strpos($str, $needle, $charset='utf-8');
	} 
}

