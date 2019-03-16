<?php
/**
 * Created by PhpStorm.
 * User: uncomplex func
 * Date: 2018/3/20
 * Time: 22:58
 */

namespace QA\Controller;


use QA\Common\JWT;
use Think\Controller;
use Think\Exception;
use Think\Upload;

class IntegralController extends Controller
{
    const KEY = "zscy";
    const ENCRYPT_WAY = "HS256";
    protected $fileConfig = array(
        "maxSize" => 4194304,
        'rootPath' => './Public/QA/Item/',
        "saveName" => "uniqid",
        "exts" => array('jpg', 'gif', 'png', 'jpeg'),
        "autoSub" => false,
        "subName" => array('date', "Ymd"),
    );
    private $filePath = "/Public/QA/Item/";

    public function _initialize()
    {
        if (!IS_POST)
            returnJson(415);
    }

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

    //增加积分
    protected static function add($addedUser, $num, $event, $time)
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

    //获取用户账户余额
    public function getDiscountBalance()
    {
        if (!IS_POST) {
            returnJson(415);
        }
        $stuNum = I("post.stuNum");
        $idNum = I("post.idNum");
        //认证方式
        //需要更换
        //测试时认证不变
        if (!authUser($stuNum, $idNum))
            returnJson(403, "discount verify failed");
        try {
            $userModel = M('users');
            $user_id = getUserIdInTable($stuNum);
            $balance = $userModel
                ->where(array(
                    "id" => $user_id
                ))
                ->getField("integral");
            returnJson(200, "success", (int)$balance);
        } catch (\Exception $e) {
            returnJson(500, "server error");
        }
    }

    //管理员 增加可兑换商品
    public function addItem()
    {
        $request = I("post.");
        //请求者身份校验
        if (!authUser($request['stuNum'], $request['idNum']))
            returnJson(403);
        $user_id = getUserIdInTable($request['stuNum']);
        //测试阶段不加管理员校验
//        $is_admin = is_admin($user_id);
//        if (!$is_admin)
//            returnJson(403, "you are not a admin");

        $file = new Upload($this->fileConfig);
        $photos = $file->upload();
        if (!$photos) {
            $this->error($file->getError());
        } else {
            $photoSrc = $this->filePath . $photos['itemPhoto']['savename'];
        }

        //是否有空参数
        $checkNull = checkParameter();
        if (!$checkNull)
            returnJson(801);
        try {
            $shopModel = M("integral_shop");
            $shopModel->create();
            $shopModel->name = $request['name'];
            $shopModel->value = $request['value'];
            $shopModel->num = $request['num'];
            $shopModel->photo_src = $photoSrc;
            $shopModel->created_at = date("Y-m-d H:i:s");
            $shopModel->adder = $user_id;
            $result = $shopModel->add();

            if ($result)
                returnJson(200);
        } catch (\Exception $e) {
            returnJson(500);
        }
    }

    //获取商品列表
    public function getItemList()
    {
        $request = I("post.");
        if (!authUser($request['stuNum'], $request['idNum']))
            returnJson(403);
        $shopModel = M('integral_shop');
        $result = $shopModel
            ->field(array(
                "name",
                "value",
                "num",
                "photo_src",
            ))
            ->where(array(
                "state" => 1
            ))
            ->select();
        for ($i = 0; $i < count($result); $i++) {
            $result[$i]['photo_src'] = DOMAIN . $result[$i]['photo_src'];
        }
        returnJson(200, "success", $result);
    }

    //签到
    public function checkIn()
    {
        if (!IS_POST) {
            returnJson(415);
        }
        $stunum = I("post.stunum");
        $idnum = I("post.idnum");
        if (!authUser($stunum, $idnum))
            returnJson(403, "the user is not himself");

        $userInfo = getUserInfo($stunum);
        $user_id = (int)$userInfo["id"];
        $userIntegral = (int)$userInfo["integral"];

        $checkInLogModel = M("checkin_log");

        $isCheckToday = $checkInLogModel->where(array(
            "userid" => $user_id,
            "create_at" => array("exp", "BETWEEN '" . date("Y-m-d 00:00:00") . "' AND '" . date("Y-m-d 23:59:59") . "' "),
        ))->count();

        if ($isCheckToday >= 1)
            returnJson(403, "today had checked in");
        else {
            try {
                $checkInLogModel
                    ->data(array("userid" => $user_id, "create_at" => date("Y-m-d H:i:s")))
                    ->add();
                //积分映射
                $integralMap = array(10, 10, 20, 10, 30, 10, 40);

                //连续签到日期
                $continuousSignInDayNum = getUserInfo($user_id)['check_in_days'];
                //前一天是否签到
                $isCheckYesterday = $checkInLogModel->where(array(
                    "userid" => $user_id,
                    "create_at" => array("exp", "BETWEEN '" . date("Y-m-d 00:00:00", strtotime("-1 day")) . "' AND '" . date("Y-m-d 23:59:59", strtotime("-1 day")) . "' "),
                ))->count();

                //如果昨天签到了 计算连续签到天数 大于七天时 积分按七天签到算
                $integral = 0;
                if ($isCheckYesterday == 1 && $continuousSignInDayNum >= 1) {
                    $continuousSignInDayNum += 1;
                    if ($continuousSignInDayNum >= 7)
                        $integral = $integralMap[6];
                    else
                        $integral = $integralMap[$continuousSignInDayNum];
                } else if ($isCheckToday != 1) {
                    $continuousSignInDayNum = 1;
                    $integral = $integralMap[0];
                }

                //积分变动记录表添加记录
                $integralModel = M("integral_log");
                $integralModel
                    ->data(array(
                        "user_id" => $user_id,
                        "event_type" => "check in",
                        "num" => $integral,
                        "created_at" => date("Y-m-d H:i:s"),
                    ))
                    ->add();

                //变更用户表 积分数额
                $userModel = M("users");
                $userModel
                    ->where(array("id" => $user_id))
                    ->setField(array("integral" => $userIntegral + $integral, "check_in_days" => $continuousSignInDayNum));
                returnJson(200);
            } catch (Exception $exception) {
                returnJson(500, "server error");
            }
        }
    }

    //获取签到状态
    //该方法弃用
}