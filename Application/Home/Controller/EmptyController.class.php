<?php
/**
 * Created by PhpStorm.
 * User: Haku
 * Date: 15/8/10
 * Time: 15:31
 */

namespace Home\Controller;
use Think\Controller;
use Think\Exception;

class EmptyController extends Controller {
    public function index(){
    	header( "HTTP/1.1 404 Not Found" );
        $this->display('Empty/index');
    }

    public function _empty() {
    	header( "HTTP/1.1 404 Not Found" );       
    	$this->display('Empty/index');
    }
}