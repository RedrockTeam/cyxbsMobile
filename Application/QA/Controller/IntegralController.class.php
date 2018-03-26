<?php
/**
 * Created by PhpStorm.
 * User: uncomplex func
 * Date: 2018/3/20
 * Time: 22:58
 */

namespace QA\Controller;


use Think\Controller;
use QA\Common\JWT;

class IntegralController extends Controller
{
    public function index()
    {
        var_dump(JWT::$leeway);
    }
}