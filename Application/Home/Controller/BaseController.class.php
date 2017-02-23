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
      
        $admin = session('admin.id');
        if (isset($admin)) {
            $admin = M('admin')->find($admin);
            if ($admin) {
                return;
            }
        }
     
        header("Content-type: application/json");
        // $this->article = D('articles');
        // $this->article_types = D('articletypes');
        // $this->article_remarks = D('articleremarks');
      
        $stuNum = I('post.stuNum');
        $idNum = I('post.idNum');
        if(empty($stuNum) || empty($idNum))
            returnJson(801);
        else{
            if (!$this->verify($stuNum, $idNum)) {
                returnJson(404, '错误信息');
            }
        }
    }
    public function index(){


    }
    public function verify($stuNum, $idNum)
    {   
        $idnum = S($stuNum);
       
        if (!empty($idnum))
            return $idNum == $idnum;
     
        $condition = array(
            "stuNum" => $stuNum,
            "idNum"  => $idNum
        );
        $needInfo = $this->curl_init($this->apiUrl,$condition);
        $needInfo = json_decode($needInfo,true);
        if($needInfo['status'] != 200){
            echo json_encode($needInfo);
            exit;
        }else{
            S($stuNum, $idNum);
        }
        return true;
    }
    protected function curl_init($url,$data){//初始化目标网站
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt ($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $output = curl_exec($ch);
        curl_close ($ch);
        return $output;
    }

    public function destroySession(){
        session(null);
        $this->redirect(CONTROLLER_NAME . 'Index/index');
    }


    /**
     * 判断是否为管理员
     * @param  string  $stunum 学号
     * @return boolean         是否为管理员
     */
    public function is_admin($stunum) 
    {
        if (empty($stunum)) {
            return false;
        }
        $stu = D('users')->where('stunum="%s"', $stunum)->find();
        if (empty($stu)) {
            return false;
        }
        $id = $stu['id'];
        $is_admin  = M('admin')->where(array('state'=>1,'stunum'=>$stunum))->find();
        if($is_admin) {
            return true;
        } else {
            $is_admin = M('administrators')->where('user_id='.$id)->find();
            if (is_admin) {
                return true;
            }
        }

        return false;
        
    }

    /**
     * 根据status返回对应的json语句
     * @param  int $status      http请求码
     * @param  array  $data   json里需要返回的数据
     * @param  string $info   重写info信息
     * @return [type]         [description]
     */
    public function returnJson($status, $data = array(), $info="") 
    {
        switch ($status) {
            case 404: 
                $report = array('status'=> 404, 'info'=>'请求参数错误');
                break;
            case 403:
                $report = array('status'=> 403, 'info'=>'Don\'t permit');
                break;
            case 801:
                $report = array('status'=> 801, 'info'=>'invalid parameter');
                break;
            case 200:
                $report = array('status'=> 200, 'info'=>'success');
                break;
            default:
                $report = array('status'=>$status, 'info'=>"");
        }

        if(!empty($info)) {
            $report['info'] = $info;
        }
        if(!empty($data)) {
            if(array_key_exists('info', $data) || array_key_exists('status', $data)) {
                return false;
            } else {
                $report = array_merge($report, $data);
            }
        }
        header('Content-type:application/json');
        $json = json_encode($report, JSON_NUMERIC_CHECK);
        echo $json;
        exit;
    }

    /**
     * 信息加密
     * @param  string $data 需要加密的信息
     * @param  string $salt 盐
     * @return string       加密后的字符串
     */
    protected function encrypt($data, $salt='')
    {

    }

    /**
     * 信息解密
     * @param  string $data 加密的信息
     * @param  string $salt 盐
     * @return string      解密的信息
     */
    protected function decrypt($data, $salt='')
    {
        return base64_decode($data);
    }
}