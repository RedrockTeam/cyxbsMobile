<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends Controller {
    public function collect(){
        import('ORG.Util.phpQuery');
        $url = 'http://jwzx.cqupt.edu.cn/showFileContent.php?id=e4db5f094ea8813086c28c07125db69edfdfbd7d';
        header("Content-type: text/html; charset=gbk2312");
        $goal = \phpQuery::newDocumentFile($url);
        $test = pq(".contentShow")->children('*');
        $content = pq($test)->html();
        $replacement = "=\"\"";
        $content = $this->_replaceStyle($content,$replacement);
        echo $content;
        
    }                                         

    public function index(){
        header("Content-type: text/html; charset=utf-8");
        set_time_limit(0);
        $this->_articleList("http://jwzx.cqupt.edu.cn/pubFileList.php?dirId=0001&currentPageNo=");
        $this->collect();
        exit;
    }
    
    private function _articleList($url,){
        for($i=1;$i<6;$i++){
            $need_url = $url,"$i";
            $pattern_href = '/<a href=\'(.*?)\' target=_blank/';
            $output = mb_convert_encoding($this->curl_init($need_url),"utf-8","gb2312");
            $need_href= $this->_patternGoal($pattern_href,$output);
        }
    }

    private function _replaceStyle($content,$replacement){
        $pattern = '/=\"(.*?)\"/';
        $content=preg_replace($pattern,$replacement,$content);
        return $content;
    }

    public function _empty() {
        $this->display('Empty/index');
    }
}

