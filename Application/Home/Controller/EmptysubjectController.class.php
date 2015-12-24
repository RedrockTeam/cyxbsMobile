<?php
namespace Home\Controller;
use Think\Controller;

class EmptysubjectController extends Controller {

    public function index(){
        $this->display('Emptysubject/index');    
    }

    public function _empty() {
        $this->display('Empty/index');
    }
}