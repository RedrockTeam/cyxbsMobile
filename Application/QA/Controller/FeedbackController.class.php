<?php
/**
 * Created by PhpStorm.
 * User: uncomplex func
 * Date: 2018/11/15
 * Time: 21:04
 */

namespace QA\Controller;

use Think\Controller;

class FeedbackController extends Controller
{
    public function index()
    {
        echo "hello";
    }

    /**
     * @description use to check the users stunum&idnum
     * @param String stunum
     * @idnum String idnum
     * @return void
     */
    public function _initialize()
    {
        if (!IS_POST)
            returnJson(405);
        $stunum = I("post.stunum");
        $idnum = I("post.idnum");
        if (!authUser($stunum, $idnum))
            returnJson(403);
    }

    /**
     * @description 增加举报记录
     * @author yangruixin
     * @param String $stunum
     * @param String $idnum 上面两项参数放在了initialize方法里面
     * @param String $type 举报类型
     * @param String $content 举报内容
     * @param int $question_id 举报对象
     * @return String json
     */
    public function addReport()
    {
        //待验证参数
        $stunum = I("post.stunum");
        //传入输入参数
        $type = I("post.type");
        $content = I("post.content");
        $question_id = I("post.question_id");

        //参数验证
        if (!is_numeric($question_id) || empty($type) || empty($content))
            returnJson(801);
        $userId = getUserIdInTable($stunum);

        //确认该问题是一个有效问题 or return 403
        $questionModel = M("questionlist");
        if ($questionModel
                ->where(array(
                    "id" => $question_id,
                    "state" => 1,
                ))
                ->count() != 1
        )
            returnJson(403, "invalid question");

        //确认是否已经举报过该问题
        $reportModel = M(REPORT_TABLE);
        if ($reportModel
                ->where(array(
                    "question_id" => $question_id,
                    "user_id" => $userId,
                    "state" => 1,
                ))
                ->count() == 1)
            returnJson(403, "you had reported this question!");

        $reportModel->create();
        $reportModel->user_id = $userId;
        $reportModel->type = $type;
        $reportModel->content = $content;
        $reportModel->question_id = $question_id;
        $reportModel->state = 1;
        $key = $reportModel->add();
        if (!empty($key) && is_numeric($key))
            returnJson(200);
        else
            returnJson(500);
    }

    /**
     * @author yangruixin
     * @description
     * @param int $page 页码
     * @param int $size 一页的数量
     * @todo 增加管理员功能
     */
    public function reportList()
    {
        $page = I("post.page") ?: 1;
        $size = I("post.size") ?: 6;

        $reportModel = M(REPORT_TABLE);
        $field = array("user_id", "type", "content", "question_id");
        $result = $reportModel
            ->field($field)
            ->where(array(
                "state" => 1
            ))
            ->page($page, $size)
            ->select();
        if ($result != null)
            returnJson(200, "success", $result);
        else
            returnJson(404, "no data");
    }

    /**
     * @author yangruixin
     * @description 该方法为举报处理，处理完之后将举报记录删除
     * @method POST
     * @param int $stunum
     * @param int $idnum
     * @param int $id 举报id
     * @todo 管理员认证方法 并且需要确定处理方式
     */
    public function process()
    {

    }
}