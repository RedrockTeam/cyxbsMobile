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

        $user_id = getUserIdInTable($stunum);

        $checkInLogModel = M("checkin_log");

        $isCheckToday = $checkInLogModel->where(array(
            "userid" => $user_id,
            "create_at" => array("exp", "BETWEEN '" . date("Y-m-d 00:00:00") . "' AND '" . date("Y-m-d 23:59:59") . "' "),
        ))->count();

        if ($isCheckToday >= 1)
            returnJson(403, "today had checked in");
        $checkInLogModel->data(array("userid" => $user_id, "create_at" => date("Y-m-d H:i:s")))->add();

        $i=0;
        for ($i = 0; $i < 7; $i++) {
            $num = $checkInLogModel->where(array(
                "userid" => $user_id,
                "create_at" => array("exp", "BETWEEN '" . date("Y-m-d 00:00:00", strtotime("-" . (string)$i . " day")) . "' AND '" . date("Y-m-d 23:59:59", strtotime("-" . (string)$i . " day")) . "' "),
            ))->count();
            if ($num <= 0)
                break;
        }
        $integral = array(10, 10, 20, 10, 30, 10, 40);
        $integralModel = M("integral_log");
        $integralModel
            ->data(array(
                "user_id" => $user_id,
                "event_type" => "check in",
                "num" => $integral[$i - 1],
                "created_at" => date("Y-m-d H:i:s"),
            ))
            ->add();
        $userModel = M("users");
        $userModel->where(array("id" => $user_id))->setInc("integral", $integral[$i - 1]);
        returnJson(200);
    }

    //获取签到状态
    public function getCheckInStatus()
    {
        if (!IS_POST) {
            returnJson(415);
        }
        $stunum = I("post.stunum");
        $idnum = I("post.idnum");
        if (!authUser($stunum, $idnum))
            returnJson(403, "the user is not himself");
        $user_id = getUserIdInTable($stunum);

        $checkInLogModel = M("checkin_log");

        $isCheckToday = $checkInLogModel->where(array(
            "userid" => $user_id,
            "create_at" => array("exp", "BETWEEN '" . date("Y-m-d 00:00:00") . "' AND '" . date("Y-m-d 23:59:59") . "' "),
        ))->count();
        if ($isCheckToday == 1)
            $data['checked'] = 1;
        else
            $data['checked'] = 0;

        for ($i = 0; $i < 7; $i++) {
            $num = $checkInLogModel->where(array(
                "userid" => $user_id,
                "create_at" => array("exp", "BETWEEN '" . date("Y-m-d 00:00:00", strtotime("-" . (string)$i . " day")) . "' AND '" . date("Y-m-d 23:59:59", strtotime("-" . (string)$i . " day")) . "' "),
            ))->count();
            if ($num <= 0)
                break;
        }

        $data['serialDays'] = $i;

        returnJson(200, "success", $data);
    }
}