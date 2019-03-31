<?php
/**
 * Created by PhpStorm.
 * User: mingmeng
 * Date: 2019/3/17
 * Time: 19:28
 */

namespace QA\Controller;

use QA\Common\Classroom;
use Think\Controller;

class ExtensionController extends Controller
{
    public function emptyRoom()
    {
        if (!extension_loaded('redis')) {
            E(L('_NOT_SUPPORT_') . ':redis');
        }

        $buildNum = I("get.buildNum");
        $week = I("get.week");
        $sectionNum = I("get.sectionNum");
        $weekdayNum = I("get.weekdayNum");

        if (is_null($buildNum) || is_null($week) || is_null($sectionNum) || is_null($weekdayNum))
            returnJson(801);

        $sectionMapper = array(
            array("1", "2"),
            array("3", "4"),
            array("5", "6"),
            array("7", "8"),
            array("9", "10"),
            array("11", "12")
        );

        $key = array();
        if (strpos($sectionNum, ",")) {
            $key = array($week . "_" . $weekdayNum . "_" . $sectionMapper[(int)$sectionNum][0], $week . "_" . $weekdayNum . "_" . $sectionMapper[(int)$sectionNum][1]);
        } else {
            $tmpkey = explode(",", $sectionNum);
            for ($i = 0; $i < count($tmpkey); $i++) {
                array_push($key, $key[$i] = $week . "_" . $weekdayNum . "_" . $sectionMapper[(int)$tmpkey][0]);
                array_push($key, $key[$i] = $week . "_" . $weekdayNum . "_" . $sectionMapper[(int)$tmpkey][1]);
            }
        }

        if (empty($key))
            returnJson(403);

        $busyRoom = array();
        $redis = new \Redis();
        $redis->connect(C('REDIS_HOST') ?: '127.0.0.1');
        $redis->select(3);
        for ($i = 0; $i < count($key); $i++) {
            $busyRoom = array_merge($busyRoom, $redis->sMembers($key[$i]));
            $busyRoom = array_unique($busyRoom);
        }

        $result = array_diff(Classroom::$ALL, $busyRoom);
        sort($result);

        $data = array();
        for ($i = 0; $i < count($result); $i++) {
            if ($result[$i][0] == $buildNum)
                array_push($data, $result[$i]);
        }
//        header("Content-Type:application/json");

        return array(
            "status" => 200,
            "info" => "success",
            "version" => "1.0.0",
            "term" => "20182",
            "weekdayNum" => "{$weekdayNum}",
            "buildNum" => "{$buildNum}",
            "data" => $data,
        );
    }
}