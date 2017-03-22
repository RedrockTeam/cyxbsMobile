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
            if (!authUser($stuNum, $idNum)) {
                returnJson(404, '错误信息');
            }
        }
    }
    public function index(){


    }


    public function destroySession(){
        session(null);
        $this->redirect(CONTROLLER_NAME . 'Index/index');
    }


    /**
     * 判断是否为管理员
     * @param  string  $user 学号 | 用户id
     * @return boolean         是否为管理员
     */
    public function is_admin($user)
    {
        if (empty($user)) {
            return false;
        }
        $stu = D('users')->where('stunum="%s"', $user)->find();
        if (empty($stu)) {
            return false;
        }
        $id = $stu['id'];
        $is_admin  = M('admin')->where(array('state'=>1,'stunum'=>$user))->find();
        if($is_admin) {
            return true;
        } else {
            $is_admin = M('administrators')->where('user_id='.$id)->find();
            if ($is_admin) {
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