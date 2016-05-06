<?php
namespace Home\Controller;
use Think\Controller;

class BaseController extends Controller {
    protected $idNum;
    protected $article;
    protected $article_types;
    protected $article_remarks;
    protected $apiUrl = "http://hongyan.cqupt.edu.cn/api/verify";
    public function _before_index(){    

    }

    function _initialize(){
        header("Content-type:JSON;");
        $this->article = D('articles');
        $this->article_types = D('articletypes');
        $this->article_remarks = D('articleremarks');
        if(I('post.stuNum')==null||I('post.idNum') == null){
            $info = array(
                "status" => 801,
                "info"   => "invalid parameter"
            );
            echo json_encode($info,true);
            exit;
        }else{
            $stunum = I('post.stuNum');
            $idNum  = I('post.idNum');
            $condition = array(
                    "stuNum" => $stunum,
                    "idNum"  => $idNum
                );
            $needInfo = $this->curl_init($this->apiUrl,$condition);
            $needInfo = json_decode($needInfo,true);
            if($needInfo['status'] != 200){
                echo json_encode($needInfo);
                exit;
            }
            if(S($stunum) == $idNum){

            }else{
                $stunum = I('post.stuNum');
                $idNum  = I('post.idNum');
                $condition = array(
                    "stuNum" => $stunum,
                    "idNum"  => $idNum
                );
                $needInfo = $this->curl_init($this->apiUrl,$condition);
                $needInfo = json_decode($needInfo,true);
                if($needInfo['status'] != 200){
                    echo json_encode($needInfo);
                    exit;
                }else{
                    S($stunum, $idNum);
                }
            }
        }
    }
    public function index(){


    }

    protected function curl_init($url,$data){//初始化目标网站
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt ($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $output = curl_exec($ch);
        curl_close ( $ch );
        return $output;
    }

    public function destroySession(){
        session(null);
        $this->redirect(CONTROLLER_NAME . 'Index/index');
    }
}