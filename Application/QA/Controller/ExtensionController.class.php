<?php
/**
 * Created by PhpStorm.
 * User: mingmeng
 * Date: 2019/3/17
 * Time: 19:28
 */

namespace QA\Controller;


use Think\Controller;

class ExtensionController extends Controller
{
    public function emptyRoom()
    {
        $buildNum = I("get.buildNum");
        $week = I("get.week");
        $sectionNum = I("get.sectionNum");
        $weekdayNum = I("get.weekdayNum");

        var_dump($weekdayNum);

        $sectionMapper = array(
            ""
        );
    }
}