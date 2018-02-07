<?php
/**
 * Created by PhpStorm.
 * User: uncomplex func
 * Date: 2018/2/6
 * Time: 15:58
 */

namespace QA\Controller;


use Think\Controller;

class QuestionController extends Controller
{
    public function index()
    {
        echo "hello world!";
    }

    public function _before_add()
    {
        if (!IS_POST)
            returnJson(801, "wrong way to request");
    }


    public function add()
    {
        $checkField = array(
            "stuNum",
            "idNum",
            "title",
            "description",
            "is_anonymous",
            "kind",
            "tags",
            "reward",
            "disappear_time",
        );
        if (!checkParameter($checkField)) {
            returnJson(801);
        }
        $date = new \DateTime();

        $stunum = I("post.stuNum");
        $idnum = I("post.idNum");

        if (!authUser($stunum, $idnum))
            returnJson(403, "stunum or idnum is wrong");

        $questionModel = M("questionlist");

        $question = new \stdClass();
        //请求字段
        if ($question->user_id = getUserIdInTable($stunum))
            returnJson(500);
        $question->title = I("post.title");
        $question->description = I("post.description");
        $question->is_anonymous = I("post.is_anonymous");
        $question->kind = I("post.kind");
        $question->tags = I("post.tags");

        //积分认证模块
        //记得写好这里
        //by uncomplex func
        //in 2018/2/8 1:10
        $question->reward = I("post.reward");

        $disappearTime = I("post.disappear_time");
        echo 1;
        if (date("Y-m-d H:i:s", strtotime($disappearTime)) == $disappearTime)
            $question->disappear_at = $disappearTime;
        else
            returnJson(801);
        //表中默认字段
        $question->state = 1;
        $question->created_at = $date->format("Y-m-d H:i:s");
        $question->updated_at = $question->created_at;
        $question->answer_num = 0;

        $questionModel->create($question);
        $questionModel->add();
        returnJson(200);
    }
}