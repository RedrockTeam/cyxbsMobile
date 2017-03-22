<?php

namespace Home\Common;

use Think\Exception;

class ForbidWord
{

	protected $error;

	protected $forbidWordList;			//敏感词列表

	protected $replace = '*';			//替换敏感词的字符

    protected $type;

	public  $table_type = array(
		'articles'		=> 5,
		'notices'		=> 6,
	  	'topics'		=> 3,
	  	'username'		=> 1,
	  	'introduction'	=> 2,
		);
    public function  __construct($type) {
        //设置默认type
        if ($this->setType($type) === false)     throw new Exception("error type:".$type);

        $forbidWordList = S('forbidWordList');

        if ($forbidWordList === false) $this->update();

        else $this->forbidWordList = $forbidWordList;

    }

    /**
     * 检查
     * @param $string
     * @param bool $IsIgnoreCase
     * @return bool
     */
	public function check($string, $type = null)
	{
	    if (isset($type)) {
            if(isset($this->table_type[$type])) $type = $this->getTypeId($type);
            else $type = $this->type;
        } else {
            $type = $this->type;
        }

		$len = $this->strlen($string);

		if ($len===0)   return $string;

		foreach ($this->forbidWordList[$type] as $word) {
		    if ($len < $word['len']) continue;

		    $result = $this->strpos($string, $word['value']);
            if ($result !== false)
                return false;
        }

        return true;
	}

    /**
     * 返回type对应的id值
     * @param $type
     * @return bool|mixed
     */
    protected  function getTypeId($type) {
        if(isset($this->table_type[$type]))
            $type = $this->table_type[$type];
        elseif(!in_array($type ,$this->table_type))
            return false;
        return $type;
    }

    /**
     * 设置默认字符串类型
     * @param $type
     * @return bool
     */
    public function setType($type) {
	    $type_id = $this->getTypeId($type);
        if ($type_id === false) {
            $this->error = 'error type:'.$type;
            return false;
        }
        $this->type = $type_id;
        return true;
    }


    /**
     * @param $string   string 待处理字符串
     * @param $type_id  int    处理类型
     * @return mixed
     */
    public function produce($string, $type = null) {
        if (isset($type)) {
            if(isset($this->table_type[$type])) $type = $this->getTypeId($type);
            else $type = $this->type;
        } else {
            $type = $this->type;
        }
        $len = $this->strlen($string);
        if ($len===0) {
            return $string;
        }

        foreach ($this->forbidWordList[$type] as $word) {

            $replace = str_repeat($this->replace, $word['len']);
            $string = str_replace($word['value'], $replace, $string);

        }
        return $string;
    }

    /**
     * 更新违法词库
     * @return bool
     */
    public function update() {
        $field = array('value', 'type_id');
        $wordList = D('word_range')
                    ->alias('word_range')
                    ->field($field)
                    ->join('__FORBIDWORDS__ word ON word.id=word_range.w_id and word.state=1')
                    ->where("word_range.state=%d", 1)
                    ->select();
        $forbidList = array();

        if ($wordList === false) {
            $this->error = D()->getError();
            return false;
        }
        foreach ($wordList as $word) {
            $len = $this->strlen($word);
            $forbidList[$word['type_id']][] = array('value' => $word['value'], 'len'=>$len);
        }
        S('forbidWordList',$wordList);
        $this->forbidWordList = $wordList;

        return true;
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
	protected function substr($str, $offset = 0, $length='', $charset= 'UTF-8')
	{
	    if (empty($str)) {
	        return false;
	    }
	    if($length === '') {
	    	$length = $this->strlen($str);
	    }
	    return mb_substr($str, $offset, $length, $charset);
	}
	//字符串长度
    protected function strlen($str, $charset= 'UTF-8')
	{
	    if (empty($str)) {
	        return 0;
	    }
	    return mb_strlen($str, $charset);
	}
	//字符串长度
    protected function strpos($str, $needle, $offset=0, $charset='UTF-8')
	{
		return mb_strpos($str, $needle, $offset, $charset);
	}

    protected function strrpos($str, $needle, $offset=0, $charset='UTF-8')
	{
		return mb_strrpos($str, $needle, $offset,$charset);
	}

    protected function strtolower($str, $charset='UTF-8') {
	    return mb_strtolower($str, $charset);
    }


}

