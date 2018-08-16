<?php
/**
 * Created by PhpStorm.
 * User: uncomplex func
 * Date: 2018/5/27
 * Time: 19:04
 */

namespace QA\Controller;

use Think\Controller;

class UserController extends Controller
{
    //帮一帮
    public function help()
    {
        if (!IS_POST)
            returnJson(415);
        $type = (int)I("post.type") ?: 0;
        if ($type == 0)
            returnJson(801);

        $stunum = I("post.stunum");
        $idnum = I("post.idnum");
        $page = I("post.page") ?: 0;
        $size = I("post.size") ?: 6;
        if (!authUser($stunum, $idnum))
            returnJson(403, "it is not yourself");

        $userID = getUserIdInTable($stunum);
        $questionModel = M("questionlist");
        $answerModel = M("answerlist");

        $adoptedAnswers = $answerModel
            ->field(array("id", "question_id", "content", "updated_at"))
            ->where(array(
                "user_id" => $userID,
                "is_adopted" => 1,
                "state" => 1,
            ))
            ->page($page, $size)
            ->select();

        $notAdoptedAnswers = $answerModel
            ->field(array("id", "question_id", "content", "created_at"))
            ->where(array(
                "user_id" => $userID,
                "is_adopted" => 0,
                "state" => 1,
            ))
            ->page($page, $size)
            ->select();

        if ($adoptedAnswers == null) {
            $adoptedAnswers = array();
        } else {
            for ($i = 0; $i < count($adoptedAnswers); $i++) {
                $questionInfo = $questionModel
                    ->field(array("title", "disappear_at"))
                    ->where(array(
                        "id" => $adoptedAnswers[$i]['question_id'],
                        "state" => 1,
                    ))
                    ->find();
                $adoptedAnswers[$i]['question_title'] = json_decode($questionInfo['title']);
                $adoptedAnswers[$i]['content'] = json_decode($adoptedAnswers[$i]['content']);
                $adoptedAnswers[$i]['disappear_at'] = $questionInfo['disappear_at'];
                $adoptedAnswers[$i]['created_at'] = "";

            }
        }


        if ($notAdoptedAnswers == null) {
            $notAdoptedAnswers = array();
        } else {
            for ($i = 0; $i < count($notAdoptedAnswers); $i++) {
                $questionInfo = $questionModel
                    ->field(array("title", "disappear_at"))
                    ->where(array(
                        "id" => $notAdoptedAnswers[$i]['question_id'],
                        "state" => 1,
                    ))
                    ->find();
                $notAdoptedAnswers[$i]['question_title'] = json_decode($questionInfo['title']);
                $notAdoptedAnswers[$i]['content'] = json_decode($notAdoptedAnswers[$i]['content']);
                $notAdoptedAnswers[$i]['disappear_at'] = $questionInfo['disappear_at'];
                $notAdoptedAnswers[$i]["updated_at"] = "";
            }
        }
        if ($type == 1)
            $data = $adoptedAnswers;
        else
            $data = $notAdoptedAnswers;
        returnJson(200, "success", $data);
    }

    //问一问
    public function ask()
    {
        if (!IS_POST)
            returnJson(415);


        $stunum = I("post.stunum");
        $idnum = I("post.idnum");

        if (!authUser($stunum, $idnum))
            returnJson(403, "it is not yourself");

        $type = (int)I("post.type") ?: 0;
        if ($type == 0)
            returnJson(801);

        $questionModel = M("questionlist");
        $answerModel = M("answerlist");
        $userID = getUserIdInTable($stunum);

        $userQuestion = $questionModel
            ->field(array("id", "title", "disappear_at", "created_at"))
            ->where(array(
                "user_id" => $userID,
                "state" => 1,
            ))
            ->select();
        $solvedQuestions = array();
        $notSolvedQuestions = array();

        if ($userQuestion != null) {
            for ($i = 0; $i <= count($userQuestion); $i++) {
                $haveAdoptedAnswers = $answerModel
                    ->field(array("content", "updated_at", "created_at"))
                    ->where(array(
                        "question_id" => $userQuestion[$i]['id'],
                        "is_adopted" => 1,
                        "state" => 1,
                    ))
                    ->find();
                $userQuestion[$i]['title'] = json_decode($userQuestion[$i]['title']);

                if ($haveAdoptedAnswers == null) {
                    array_push($notSolvedQuestions, $userQuestion[$i]);
                } else {
                    $userQuestion[$i]['answer_content'] = json_decode($haveAdoptedAnswers['content']);
                    $userQuestion[$i]['updated_at'] = $haveAdoptedAnswers['updated_at'];
                    array_push($solvedQuestions, $userQuestion[$i]);
                    unset($userQuestion[$i]);
                }
            }
        }

        if ($type == 1) {
            for ($i = 0; $i < count($notSolvedQuestions); $i++) {
                $notSolvedQuestions[$i]["updated_at"] = "";
            }
            $data = $notSolvedQuestions;
        } else {
            $data = $solvedQuestions;
        }
        returnJson(200, 'success', $data);
    }

    //草稿箱列表
    public function getDraftList()
    {
        if (!IS_POST) {
            returnJson(415);
        }
        $stunum = I("post.stunum");
        $idnum = I("post.idnum");

        if (!authUser($stunum, $idnum))
            returnJson(403, "it is not yourself");

        $page = I("post.page") ?: 0;
        $size = I("post.size") ?: 6;
        $userId = getUserIdInTable($stunum);

        $draftModel = M("qa_draft");
        $draftList = $draftModel
            ->field(array("id", "content", "type", "created_at", "target_id"))
            ->where(array(
                "user_id" => $userId,
                "state" => 1,
            ))
            ->order('created_at desc')
            ->page($page, $size)
            ->select();

        for ($i = 0; $i < count($draftList); $i++) {
            switch ($draftList[$i]["type"]) {
                case "question":
                    $draftList[$i]['title_content'] = "";
                    break;
                case "answer":
                    $answerModel = M("answerlist");
                    $check = $answerModel->where(array(
                        "id" => $draftList[$i]['target_id'],
                        "state" => 1,
                    ))->getField("content");
                    $check = json_decode($check);
                    $draftList[$i]['title_content'] = $check;
                    break;
                case "remark":
                    $remarkModel = M("praise_remark");
                    $check = $remarkModel
                        ->where(array(
                            "id" => $draftList[$i]['target_id'],
                            "type" => 2,
                            "state" => 1,
                        ))
                        ->getField("content");
                    $check = json_decode($check);
                    $draftList[$i]['title_content'] = $check;
                    break;
            }
        }


        if ($draftList == null)
            returnJson(200, "success", array());
        else
            returnJson(200, "success", $draftList);
    }

    //更新草稿箱
    public function updateItemInDraft()
    {
        if (!IS_POST) {
            returnJson(415);
        }
        $stunum = I("post.stunum");
        $idnum = I("post.idnum");

        if (!authUser($stunum, $idnum))
            returnJson(403, "it is not yourself");

        $userId = getUserIdInTable($stunum);
        $content = I("post.content") ?: null;
        $id = (int)I("post.id") ?: 0;

        if ($content == null || $id == 0)
            returnJson(801);


        $draftModel = M("qa_draft");


        $data = $draftModel
            ->where(array(
                "user_id" => $userId,
                "state" => 1,
                "id" => $id
            ))
            ->setField(array(
                "content" => $content,
            ));

        if ($data == false)
            returnJson(500, "update error");
        else
            returnJson(200);

    }

    //删除草稿箱
    public function deleteItemInDraft()
    {
        if (!IS_POST) {
            returnJson(415);
        }
        $stunum = I("post.stunum");
        $idnum = I("post.idnum");

        if (!authUser($stunum, $idnum))
            returnJson(403, "it is not yourself");

        $id = (int)I("post.id") ?: 0;
        if ($id == 0)
            returnJson(404, "invalid item in draft");
        $userId = getUserIdInTable($stunum);

        $draftModel = M("qa_draft");

        $return = $draftModel
            ->where(array(
                "id" => $id,
                "user_id" => $userId,
                "state" => 1,
            ))
            ->setField(array(
                "state" => 0,
            ));

        if ($return != false)
            returnJson(200, "success");
        else
            returnJson(500, "delete error");
    }


    //草稿箱添加
    public function addItemInDraft()
    {
        if (!IS_POST) {
            returnJson(415);
        }
        $stunum = I("post.stunum");
        $idnum = I("post.idnum");

        if (!authUser($stunum, $idnum))
            returnJson(403, "it is not yourself");

        $userId = getUserIdInTable($stunum);
        $content = I("post.content");
        $type = I("post.type");
        if (empty($content) || empty($type))
            returnJson(801);

        $target_id = null;
        switch ($type) {
            case "answer":
                $target_id = I("post.target_id") ?: 0;
                if ($target_id == 0)
                    returnJson(801);
                $answerModel = M("answerlist");
                $check = $answerModel->where(array(
                    "id" => $target_id,
                    "state" => 1,
                ))->find();
                if ($check == null)
                    returnJson(403, "invalid answer");
                break;
            case "remark":
                $target_id = I("post.target_id") ?: 0;
                if ($target_id == 0)
                    returnJson(801);

                $remarkModel = M("praise_remark");
                $check = $remarkModel
                    ->where(array(
                        "id" => $target_id,
                        "type" => 2,
                        "state" => 1,
                    ))
                    ->find();
                if ($check == null)
                    returnJson(403, "invalid remark");
                break;
            case "question":
                $target_id = 0;
                $content = ($content);
                break;
            default:
                returnJson(403, "invalid parameter 'type' ");
                break;
        }

        $draftModel = M("qa_draft");

        $draftModel->create();
        $draftModel->user_id = $userId;
        $draftModel->content = $content;
        $draftModel->type = $type;
        $draftModel->target_id = (int)$target_id;
        $draftModel->created_at = date("Y-m-d H:i:s");
        $draftModel->state = 1;

        if ($draftModel->add())
            returnJson(200, "success");
    }


    public function integralRecords()
    {
        if (!IS_POST) {
            returnJson(415);
        }

        $stunum = I("post.stunum");
        $idnum = I("post.idnum");

        if (!authUser($stunum, $idnum))
            returnJson(403, "it is not yourself");

        $page = I("post.page") ?: 0;
        $size = I("post.size") ?: 6;


    }

    public function aboutMe()
    {
        if (!IS_POST)
            returnJson(415);
        $stunum = I("post.stunum");
        $idnum = I("post.idnum");

        if (!authUser($stunum, $idnum))
            returnJson(403, "it is not yourself");

        $page = I("post.page") ?: 0;
        $size = I("post.size") ?: 6;
        $userId = getUserIdInTable($stunum);

        $type = (int)I("post.type") ?: 0; //点赞1 评论2
        if ($type == 0)
            returnJson(801);

        $answerModel = M("answerlist");
        $remarkModel = M("praise_remark");
        $remarkModel->where(array())->select();

        $answerSet = $answerModel
            ->field(array("id", "question_id", "content"))
            ->where(array(
                "user_id" => $userId,
                "state" => 1,
            ))
            ->select();

        $photoModel = M("answer_photos");
        $idSet = array();
        for ($i = 0; $i < count($answerSet); $i++) {
            $answerSet[$i]['content'] = json_decode($answerSet[$i]['content']);
            $photo = $photoModel
                ->where(array(
                    "answer_id" => $answerSet[$i]['id'],
                    "state" => 1,
                ))->find();
            if ($photo == null)
                $answerSet[$i]["photo_src"] = "";
            else
                $answerSet[$i]["photo_src"] = DOMAIN . $photo['file_path'];
            array_push($idSet, $answerSet[$i]['id']);
        }

        if ($type == 2) {
            $remarkPraiseSet = $remarkModel
                ->field(array("target_id", "content", "user_id", "created_at", "type"))
                ->where(array(
                    "target_id" => array("in", $idSet),
                    "state" => 1,
                    "type" => $type,
                ))
                ->order("created_at desc")
                ->page($page, $size)
                ->select();
        } else if ($type == 3) {
            $remarkPraiseSet = $remarkModel
                ->field(array("target_id", "content", "user_id", "created_at", "type"))
                ->where(array(
                    "target_id" => array("in", $idSet),
                    "state" => 1,
                ))
                ->order("created_at desc")
                ->page($page, $size)
                ->select();
        } else {
            $remarkPraiseSet = $remarkModel
                ->field(array("target_id", "content", "user_id", "created_at", "type"))
                ->where(array(
                    "target_id" => array("in", $idSet),
                    "state" => 1,
                    "type" => $type,
                ))
                ->order("created_at desc")
                ->page($page, $size)
                ->select();
        }

        for ($i = 0; $i < count($remarkPraiseSet); $i++) {
            if ($type == 2)
                $remarkPraiseSet[$i]['content'] = json_decode($remarkPraiseSet[$i]['content']);
            elseif ($type == 3) {
                if ($remarkPraiseSet[$i]['type'] == 2)
                    $remarkPraiseSet[$i]['content'] = json_decode($remarkPraiseSet[$i]['content']);
                else
                    $remarkPraiseSet[$i]['content'] = "";

            } else
                $remarkPraiseSet[$i]['content'] = "";

            $id = $remarkPraiseSet[$i]['user_id'];
            $userInfo = getUserBasicInfoInTable($id);
            unset($remarkPraiseSet[$i]['user_id']);

            $remarkPraiseSet[$i]['nickname'] = $userInfo['nickname'];
            $remarkPraiseSet[$i]['photo_thumbnail_src'] = $userInfo['photo_thumbnail_src'];
            $answerInfo = array();
            for ($j = 0; $j < count($answerSet); $j++) {
                if ((int)$remarkPraiseSet[$i]["target_id"] == (int)$answerSet[$j]['id']) {
                    $answerInfo = $answerSet[$j];
                    break;
                }
            }
            $remarkPraiseSet[$i]['photo_src'] = $answerInfo["photo_src"];
            $remarkPraiseSet[$i]['question_id'] = $answerInfo["question_id"];
            $remarkPraiseSet[$i]['answer_content'] = $answerInfo["content"];
        }

        returnJson(200, "success", $remarkPraiseSet);
    }
}