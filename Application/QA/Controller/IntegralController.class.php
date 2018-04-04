<?php
/**
 * Created by PhpStorm.
 * User: uncomplex func
 * Date: 2018/3/20
 * Time: 22:58
 */

namespace QA\Controller;


use Org\Util\String;
use Think\Controller;
use QA\Common\JWT;

class IntegralController extends Controller
{
    const KEY = "zscy";
    const ENCRYPT_WAY = "HS256";

    public function index()
    {
        $jwtString = getallheaders();
        $jwtString = $jwtString['Authorization'];
        header("Access-Control-Allow-Origin: *");
        $info = JWT::decode($jwtString, $this::KEY);
        $payload = array(
            "iss" => "redrock.team",
            "iat" => time(),
            "exp" => time() + 3600,
            "sub" => $this::KEY . "clint",
        );
    }

    protected static function add( $addedUser,  $num,  $event,  $time)
    {
        try {
            $userModel = M("users");
            $integralModel = M('integral_log');
            $integralModel->create();
            $integralModel->user_id = $addedUser;
            $integralModel->num = $num;
            $integralModel->event_type = $event;
            $integralModel->created_at = $time;
            $integralModel->add();
        }catch (\Exception $exception){
            returnJson(500);
        }
        $userModel->where(array(
            "id"=>$addedUser,
            "state"=>1,
        ))->setInc("integral",$num);
    }

    public function test()
    {
        self::add(2628,10,"answer",date("Y-m-d H:i:s"));
    }

}