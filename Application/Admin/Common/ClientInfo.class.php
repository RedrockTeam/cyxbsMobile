<?php

namespace Admin\Common;

class ClientInfo 
{
	//返回系统信息
	public static function getOs ($user_agent=null)
	{
		$userAgent 	= strtolower($user_agent ? : $_SERVER['HTTP_USER_AGENT']);

    	$os    		=   "";
    	$os_array   =   array(
    		'/windows nt 10.0/i' 	=> 	'Windows 10',
			'/windows nt 6.2/i'     =>  'Windows 8',
			'/windows nt 6.1/i'     =>  'Windows 7',
			'/windows nt 6.0/i'     =>  'Windows Vista',
			'/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
			'/windows nt 5.1/i'     =>  'Windows XP',
			'/windows xp/i'         =>  'Windows XP',
			'/windows nt 5.0/i'     =>  'Windows 2000',
			'/windows me/i'         =>  'Windows ME',
			'/win98/i'              =>  'Windows 98',
			'/win95/i'              =>  'Windows 95',
			'/win16/i'              =>  'Windows 3.11',
			'/macintosh|mac os x/i' =>  'Mac OS X',
			'/mac_powerpc/i'        =>  'Mac OS 9',
			'/linux/i'              =>  'Linux',
			'/ubuntu/i'             =>  'Ubuntu',
			'/iphone/i'             =>  'iPhone',
			'/ipod/i'               =>  'iPod',
			'/ipad/i'               =>  'iPad',
			'/android/i'            =>  'Android',
			'/blackberry/i'         =>  'BlackBerry',
			'/webos/i'              =>  'Mobile'
     	);

	    foreach ($os_array as $regex => $value) { 
	        if ( preg_match($regex, $userAgent) ) {
	           $os = $value;
	        }
	    }   

	    return $os;
	}

	//返回浏览器信息
	public function getBrowser($user_agent=null) 
	{
		$userAgent 	= strtolower($user_agent ? : $_SERVER['HTTP_USER_AGENT']);

		$browser 	= array();
 		$browsers 	= array(
				"firefox"	=>	"Firefox",
				"msie"		=>	"Internet Explorer",
				"edge" 		=> 	"Edge",
				"opera"		=>	"Opera",
				"chrome"	=>	"Chrome",
				"safari"	=>	"Safari",
				"mozilla"	=>	"Mozilla"
            ); 
      
        foreach($browsers as $browserkey => $browsername) 
        { 
            if (preg_match("#($browserkey)[/ ]?([0-9.]*)#", $userAgent, $match)) 
            { 
                $browser['name'] = $browsername; 
                $browser['version'] = $match[2]; 
                break ; 
            } 
        } 

	    return $browser;
	}

    //判断是否为搜索引擎访问
    public static function isRobot ($user_agent=null) 
    {
		$userAgent = strtolower($user_agent ? : $_SERVER['HTTP_USER_AGENT']);
		$spiders = array( 
			'Googlebot', 		// Google 
			'Baiduspider', 		// 百度 
			'Yahoo! Slurp', 	// 雅虎 
			'YodaoBot', 		// 有道 
			'MSNBot', 			// Bing
			'Bingbot', 			// Bing
			"Sogou Spider", 	// 搜狗
			"360spider", 		// 360 
			"HaoSouSpider", 	// 360
			"Sosospider", 		// 搜搜
			"YoudaoBot", 		// 有道
			"Yisouspider" 		// 神马搜索
			// 更多爬虫关键字 
		); 
  		foreach ($spiders as $spider) { 
			$spider = strtolower($spider); 
		    if (strpos($userAgent, $spider) !== false) { 
				return $spider; 
		    } 
		} 
		return false; 
	}

	//返回所在地
    public static function getAddress($ip=null)
    {
        $ip = $ip ? : STATIC::getIp();
        $ipadd = file_get_contents("http://int.dpool.sina.com.cn/iplookup/iplookup.php?ip=".$ip);//根据>新浪api接口获取
        if($ipadd){
            $charset = iconv("gbk", "utf-8", $ipadd);
            preg_match_all("/[\x{4e00}-\x{9fa5}]+/u", $charset, $ipadds);
            return $ipadds[0];
        }else{
            return null;
        }
    }

    public static function getIp()
    {
        if(!empty($_SERVER["HTTP_CLIENT_IP"])){
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }
        if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){ //获取代理ip
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        }
        if(isset($ip)){
            $ips = array_unshift($ips, $ip);
        }

        $count = isset($ips) ? count($ips) : 0;
        for($i=0; $i<$count; $i++){
            if(!preg_match("/^(10|172\.16|192\.168)\./i",$ips[$i])){//排除局域网ip
                $ip = $ips[$i];
            	break;
            }
        }
 
        $tip = empty($_SERVER['REMOTE_ADDR']) ? $ip : $_SERVER['REMOTE_ADDR'];
        if($tip=="127.0.0.1"){
            return "";
        }else{
        	return $tip;
        }
    }
}