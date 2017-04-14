<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends Controller {
    // public function collect(){
    //     import('ORG.Util.phpQuery');
    //     $url = 'http://jwzx.cqupt.edu.cn/showFileContent.php?id=e4db5f094ea8813086c28c07125db69edfdfbd7d';
    //     header("Content-type: text/html; charset=gbk2312");
    //     $goal = \phpQuery::newDocumentFile($url);
    //     $test = pq(".contentShow")->children('*');
    //     $content = pq($test)->html();
    //     $replacement = "=\"\"";
    //     $content = $this->_replaceStyle($content,$replacement);
    //     echo $content;
        
    // }                                         

    public function index(){
        // header("Content-type: text/html; charset=utf-8");
        // set_time_limit(0);
        // $this->_articleList("http://jwzx.cqupt.edu.cn/pubFileList.php?dirId=0001&currentPageNo=");
        // $this->collect();
        // exit;
        //$this->_empty();
        //header('Location: http://hongyan.cqupt.edu.cn/app/');
        $this->display();
    }
    
    // private function _articleList($url,){
    //     for($i=1;$i<6;$i++){
    //         $need_url = $url,"$i";
    //         $pattern_href = '/<a href=\'(.*?)\' target=_blank/';
    //         $output = mb_convert_encoding($this->curl_init($need_url),"utf-8","gb2312");
    //         $need_href= $this->_patternGoal($pattern_href,$output);
    //     }
    // }

    // private function _replaceStyle($content,$replacement){
    //     $pattern = '/=\"(.*?)\"/';
    //     $content=preg_replace($pattern,$replacement,$content);
    //     return $content;
    // }

    public function _empty() {
        $this->display('Empty/index');
    }

    public function test() {
        $forbidword  = new \Home\Common\Forbidword;
        $wordList = array('shit', 'sb');
        print_r($forbidword->transForbidwordList($wordList));
        
    }

    public function exportAnalyse(){
        $information = I('post.');
        if (!authUser($information['stuNum'], $information['idNum']) || !is_admin($information['stuNum'])) {
            returnJson('403');
        }
        $college = array(
            array('通信与信息工程学院'),
            array('计算机科学与技术学院'),
            array('自动化学院'),
            array('先进制造工程学院'),
            array('光电工程学院', '国际半导体学院'),
            array('生物信息学院'),
            array('理学院'),
            array('经济管理学院'),
            array('网络空间安全与信息法学院'),
            array('传媒艺术学院'),
            array('外国语学院'),
            array('国际学院'),
            array('软件工程学院')
        );
        $Data = array();
        $id = 106;
        $url = "http://hongyan.cqupt.edu.cn/api/verify";
        while($id >93) {
            $pos = array('article_id' => $id, 'articletype_id' => 6, 'p.created_time'=> array('lt','2017-04-13'));
            $field = array('p.stunum' => 'stuNum', 'idnum'=>'idNum');
            $praisesUser = D('articlepraises')->alias('p')->join('__USERS__ ON __USERS__.stunum = p.stunum', 'left')->where($pos)->field($field)->select();
            $selfCollege = $college[106-$id];
            $data['college'] = $selfCollege[0];
            $data['totalPraise'] = count($praisesUser);
            $data['selfPraise'] = 0; $data['otherPraise'] = 0;

            foreach ($praisesUser as $value) {
                $college = S('studentCollege'.$value['stuNum']);
                if (!$college) {
                    $jsonMessage = curlPost($url, $value);
                    $message = json_decode($jsonMessage, true);
                    S('studentMessage'.$value['stuNum'], $message['college']);
                }
                in_array($college, $selfCollege) ? $data['selfPraise']++ : $data['otherPraise']++;
            }
            $Data[] = $data;
            $id--;
        }
        returnJson(200, '', array('data'=>$Data));
    }
}

