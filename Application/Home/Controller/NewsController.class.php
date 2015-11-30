<?php
namespace Home\Controller;
use Think\Controller;
use Org\Net\Http;
/*
 *written by 丛广林
 *date:2015/11/
 *qq:304546210
 *phone:18883990798
 *news_api:教务在线jwzx，重邮新闻cyxw，学术讲座xsjz，校务公告xwgg
 */
class NewsController extends Controller {
    private $site = '';
    private $_Jwzx = array();
    private $_Cyxw = array();
    private $_Xsjz = array();
    private $_Xwgg = array();
    public function index(){
        $this->curl_init();
        $this->newsUpdate();
    }
    /*
     *魔术方法，调用缓存新闻内容
     *'jwzx'=>'教务在线','cyxw'=>'重邮新闻','xsjz'=>'学术讲座',
     'xwgg'=>'校务公告','404'=>'缓存为空'
     */
    public function __call($name,$agrs){//缓存调用位置
        $page = empty(I('post.page'))?15:I('post.page');
        $size = empty(I('post.size'))?15:I('post.size');
        
    }
    /*
     *newsUpdate
     *更新新闻缓存
     *调用clear方法清除下载文件
     *num为搜索新闻数
     */
    public function newsUpdate(){//设置需要网站，刷新入口
        $this->_curl_set_jwzx("http://jwzx.cqupt.edu.cn/pubFileList.php?dirId=0001&currentPageNo=");
        $this->_curl_set_cyxy("http://xwzx.cqupt.edu.cn/xwzx/news_type.php?id=1&page=");
        $this->_curl_set_xsjz("http://202.202.32.35/getPublicPage.do?ffmodel=notic&&nc_mode=news&page=1&rows=");
        $this->_curl_set_xwgg("http://202.202.32.35/getPublicPage.do?ffmodel=notic&&nc_mode=notic&page=1&rows=");
    }
    /*
     *searchFolder
     *下载附件
     *输出附件位置
     */
    public function searchFolder(){
        import("ORG.Net.Http");
        $id = I('get.goalID');
        $url = "http://jwzx.cqupt.edu.cn/fileAttach.php?id=".$id;
        $url = "http://jwzx.cqupt.edu.cn/fileAttach.php?id=10756";
        $http = new Http();
        $html = file_get_contents($url);  
        $last = $http_response_header[7]; 
        $last = explode('.', $last);
        $site = $_SERVER["SERVER_NAME"];
        $setPosition ="./Public/jwzxnews/".$id.'.'.$last[1];
        $http->curlDownload($url,$setPosition);
        $folder_name = explode('/',$_SERVER["SCRIPT_NAME"]);
        $setPosition ="http://".$site.'/'.$folder_name[1]."/Public/jwzxnews/".$id.'.'.$last[1];
        echo $setPosition;
    }
    /*
     *clear
     *清空附件
     */
    private function clear(){
        $dh=opendir("./Public/jwzxnews");
        while ($file=readdir($dh)) {
            if($file!="." && $file!="..") {
                $fullpath="./Public/jwzxnews"."/".$file;
                if(!is_dir($fullpath)) {
                    unlink($fullpath);
                } else {
                        deldir($fullpath);
                }
            }
        }
 
        closedir($dh);
    }
    /*
     *curl
     *curl初始化及设置
     */
    private function curl_init($url){//初始化目标网站
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        $output = curl_exec($ch);
        return $output;
    }
    /*
     *_curl_set_jwzx
     *教务在线新闻更新
     *原为gb2312格式转成utf-8
     *pattern_(title题目|time发布时间|href文章uri)正则匹配样式
     *output 为目标页内容
     *need目标匹配内容
     *for内为文章内容匹配
     *S('jwzx')为内容缓存
     */
    private function _curl_set_jwzx($url){
        for($e=1;$e<16;$e++){
            $need_url = $url.$e;
            $pattern_href = '/a href=\'showfilecontent.php\?id=(.*?)\'/';
            $output = $this->curl_init($need_url);
            $output = mb_convert_encoding($output,"utf-8","gb2312");

            $need_href = $this->_patternGoal($pattern_href,$output);
            $goal_num = count($need_href[1]);
            for($i = 0; $i < $goal_num; $i++){
                $now_site = 'http://jwzx.cqupt.edu.cn/showfilecontent.php?id='.$need_href[1][$i];
                $now_news['articleid'] =$need_href[1][$i];
                $ready_site = $this->curl_init($now_site);
                $ready_site = mb_convert_encoding($ready_site,"utf-8","gb2312");
                $ready_site = $this->_patternGoal('/<!-- 下面是body部分 -->([\s\S]*?)<!-- body over -->/',$ready_site);
                $now_pattern_head = '/mso-font-kerning:0pt">([\s\S]*?)(<\/span>|<span|<a name|<b>|<o:p>)/';
                $head = $this->_patternGoal($now_pattern_head,$ready_site[1][0]);
                $need_head = trim(implode('',$head[1]));
                $now_news['head'] = substr($need_head,0,200); 
                $ready_site=implode('',$ready_site[0]); 
                /*作者、时间、阅读量*/
                $now_pattern_head_time = "/<CENTER><h4 style=\"font-size:13pt\">(.*?)<\/h4><\/CENTER><hr size=\"1\"><CENTER>发布时间:(.*?)                   发布人:(.*?)阅读人数:(.*?)</"; 
                $title_time = $this->_patternGoal($now_pattern_head_time,$ready_site);  
                $now_news['title'] = $title_time[1][0];
                $now_news['date'] = trim($title_time[2][0]);    
                $now_news['read'] = trim($title_time[4][0]);    
                $now_pattern_href = '/href=\'fileAttach.php\?id=/';
                $ready_site = preg_replace($now_pattern_href,"href='http://".$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"]."/searchfolder?goalID=",$ready_site);
                $now_pattern_href = '/href=\'(.*?)\'/';
                /*附件地址及名称*/
                $need_annex = $this->_patternGoal($now_pattern_href,$ready_site);
                $now_pattern_hrefname = "/blank>(.*?)<\/a>/";
                $hrefname = $this->_patternGoal($now_pattern_hrefname,$ready_site);
                $now_news['name'] = implode("|",$hrefname[1]);
                $content_pattern = "/<\/CENTER><BR><BR>([\s\S]*?)<hr size=1>/";
                $ready_site = $this->_patternGoal($content_pattern,$ready_site);
                /*匹配contents*/
                $now_pattern_src = "/src=\"/";
                $ready_site = preg_replace($now_pattern_src,"src='http://jwzx.cqupt.edu.cn/",$ready_site[1]);            
                $now_pattern_style = "/style=\"([\s\S]*?)\"/";
                $ready_site = preg_replace($now_pattern_style,"style=''",$ready_site[0]);
                $now_news['content'] = $ready_site;
                $now_news['address'] =implode("|",$need_annex[1]);
                array_push($this->_Jwzx,$now_news);
            }
            //var_dump($this->_Jwzx);exit;
        }
        array_shift($this->_Jwzx);
        $this->setSql('jwzx',$this->_Jwzx);
    }
    /*
     *_curl_set_cyxw
     *教务在线新闻更新
     *原为gb2312格式转成utf-8
     *pattern_(title题目|time发布时间|href文章uri)正则匹配样式
     *output 为目标页内容
     *need目标匹配内容
     *for内为文章内容匹配
     *S('cyxw')为内容缓存
     */
    private function _curl_set_cyxy($url){
        import('ORG.Util.phpQuery');
        for($e=1;$e<7;$e++){
            $output = $this->curl_init($url.$e);
            $output = mb_convert_encoding($output,"utf-8","gb2312");
            $pattern  = '/<a href="news.php\?id=(.*?)" title="(.*?)">/';
            $ready_href = $this->_patternGoal($pattern,$output);
            $goal_num = count($ready_href[1]);
            for($i = 0;$i<$goal_num;$i++){
                $now_site = "http://xwzx.cqupt.edu.cn/xwzx/news.php?id=".$ready_href[1][$i];
                $ready_site = $this->curl_init($now_site);
                $ready_site = mb_convert_encoding($ready_site,"utf-8","gb2312");
                $title_pattern = "/ line-height:30px;\">(.*?)<\/td>/";
                $need_title = $this->_patternGoal($title_pattern,$ready_site);
                $time_pattern = "/style=\"line-height:30px;\">日期：(.*?)&nbsp;&nbsp;&nbsp;&nbsp;供稿单位：(.*?)&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;点击率：(.*?)次<\/td>/";
                $need_date_read = $this->_patternGoal($time_pattern,$ready_site);
                $content_pattern = '/news_content\">([\s\S]*?)<\/tr/';
                $need_content = $this->_patternGoal($content_pattern,$ready_site);
                $need_content = preg_replace('/src="/','src="http://xwzx.cqupt.edu.cn', $need_content[1]);
                // $goal = \phpQuery::newDocumentFile($now_site);
                // $now_news_cyxw['head'] = substr(pq("#news_content")->text(),0,200);
                $now_news_cyxw['articleid'] = $ready_href[1][$i];
                $now_news_cyxw['title'] = $need_title[1][0];
                $now_news_cyxw['date'] = $need_date_read[1][0];
                $now_news_cyxw['read'] = $need_date_read[3][0];
                $now_news_cyxw['content'] = $need_content[0];
                array_push($this->_Cyxw,$now_news_cyxw);
            }
        }
        $this->setSql('cyxw',$this->_Cyxw);exit;
    }

    private function _curl_set_xsjz($url,$num){
        $output = $this->curl_init($url);
        $output = json_decode($output,true);
        $output = $output["rows"];
        for($i = 0;$i<$num;$i++){
            $ready_site =$this->curl_init("http://202.202.32.35/getPublicNotic.do?id=".$output[$i]['id']);
            $title_pattern = '/<h1 align=\'center\' style=\'font-size:19px;color: darkgreen\'>(.*?)<\/h1><h4 align=\'center\' style=\'font-weight:normal\'>发布部门:(.*?)&nbsp;&nbsp;发布人:(.*?)&nbsp;&nbsp;发布时间:(.*?)<\/h4>/';
            $need_title = $this->_patternGoal($title_pattern,$ready_site);
            $this->_Xsjz[$i]['title'] = $need_title[1];
            $this->_Xsjz[$i]['date'] = $need_title[4];
            $this->_Xsjz[$i]['content'] = $ready_site;
        }
    }

    private function _curl_set_xwgg($url,$num){
        $output = $this->curl_init($url);
        $output = json_decode($output,true);
        $output = $output["rows"];
        for($i = 0;$i<$num;$i++){
            $ready_site =$this->curl_init("http://202.202.32.35/getPublicNotic.do?id=".$output[$i]['id']);
            $title_pattern = '/<h1 align=\'center\' style=\'font-size:19px;color: darkgreen\'>(.*?)<\/h1><h4 align=\'center\' style=\'font-weight:normal\'>发布部门:(.*?)&nbsp;&nbsp;发布人:(.*?)&nbsp;&nbsp;发布时间:(.*?)<\/h4>/';
            $need_title = $this->_patternGoal($title_pattern,$ready_site);
            $this->_Xwgg[$i]['title'] = $need_title[1];
            $this->_Xwgg[$i]['date'] = $need_title[4];
            $this->_Xwgg[$i]['content'] = $ready_site;
        }
    }
    /*
     *setSql
     *若存数据库调用接口
     *清空数据库
     *truncate，所以用原生
     */
    private function setSql($goalsql,$content){//刷新数据库
        $news = M($goalsql);
        $sql = "truncate table cyxbsmobile_".$goalsql;
        $new=M();
        $new->execute($sql);
        $news->addall($content);
    }

    private function _patternGoal($pattern,$string){//匹配函数
        preg_match_all($pattern,$string,$goalarray);
        return $goalarray;
    }

    public function _empty() {
        $this->display('Empty/index');
    }
}