<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends Controller {
    private $site = '';
    private $_Jwzx;
    private $_Cyxw;
    private $_Xsjz;
    private $_Xwgg;
    public function index(){
        $this->_empty();
    }
 
    public function _empty() {
        $this->display('Empty/index');
    }
}

