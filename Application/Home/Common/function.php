<?php

/**
 * 获取毫秒时间戳
 * @return float 时间戳
 */
function getMillisecond() 
{
	list($t1, $t2) = explode(' ', microtime());     
	return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);  
} 