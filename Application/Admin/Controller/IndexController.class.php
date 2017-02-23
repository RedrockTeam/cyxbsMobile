<?php
namespace Admin\Controller;

use Think\Controller;

class IndexController extends Controller 
{
                              
    public function _initialize()
    {
        $user = session('admin');
        $controller = I('path.1');
        if (empty($user) && $controller != 'login') {
            $this->redirect('Index/login');
        }
    }
    public function index()
    {
        $this->display();
    }
    
    public function login()
    {
        //登录了的跳转到主页面
        $user = session('admin');
        if (!empty($user)) {
           $this->redirect('Index/index');
        }
        $this->display();
        
        
    }

    public function article()
    {
       
    }

    public function admin()
    {

    }

    public function user()
    {

    }
}

