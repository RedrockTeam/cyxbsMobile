<?php
/**
 * Created by PhpStorm.
 * User: uncomplex func
 * Date: 2018/4/5
 * Time: 9:20
 */

namespace QA\Common;


class Integral
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

    public static function add($addedUser, $num, $event, $time)
    {
        $userModel = M("users");
        $integralModel = M('integral_log');
        try {
            $integralModel->create();
            $integralModel->user_id = $addedUser;
            $integralModel->num = $num;
            $integralModel->event_type = $event;
            $integralModel->created_at = $time;
            $integralModel->add();
        } catch (\Exception $exception) {
            return false;
        }
        try {
            $userModel->where(array(
                "id" => $addedUser,
                "state" => 1,
            ))->setInc("integral", $num);
        } catch (\Exception $exception) {
            return false;
        }
        return true;
    }

}