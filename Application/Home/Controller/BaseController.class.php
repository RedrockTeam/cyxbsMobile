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
        return is_admin($user);
        
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