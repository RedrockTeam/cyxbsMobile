<?php
/**
 * Created by PhpStorm.
 * User: uncomplex func
 * Date: 2018/2/8
 * Time: 17:52
 */

namespace QA\Controller;


use Think\Controller;

class AnswerController extends Controller
{
    //回答
    public function add()
    {
        if (!IS_POST)
            returnJson(415);
        if (!authUser(I("post.stuNum"), I("post.idNum")))
            returnJson(403);

        $question_id = I("post.question_id");
        $checkParameter = array(
            "question_id",
            "content",
        );

        if (!checkParameter($checkParameter))
            returnJson(801);

        $questionModel = M("questionlist");
        $answerModel = M('answerlist');
        $date = new \DateTime();

        $user_id = $questionModel
            ->field(array(
                "user_id"
            ))
            ->where(array(
                "id" => $question_id,
                "state" => 1,
            ))
            ->find();

        if ($user_id == null)
            returnJson(801, 'invalid question');


        $user_id = $user_id['user_id'];
        if ($user_id == getUserIdInTable(I("post.stuNum")))
            returnJson(403, "yourself or invalid question");

        $user_id = getUserIdInTable(I("post.stuNum"));

        $existence = $answerModel
            ->where(
                array(
                    "user_id" => $user_id,
                    "question_id" => $question_id,
                    "state" => 1,
                )
            )
            ->find();

        if (!empty($existence))
            returnJson(403, "the answer is already existing in the list");

        $answerModel->create();
        $answerModel->user_id = $user_id;
        $answerModel->question_id = $question_id;
        $answerModel->content = json_encode(I("post.content"));
        $answerModel->praise_num = 0;
        $answerModel->comment_num = 0;
        $answerModel->is_adopted = 0;
        $answerModel->created_at = $date->format("Y-m-d H:i:s");
        $answerModel->updated_at = $answerModel->created_at;
        $answerModel->state = 1;

        if ($answerModel->add()) {
            $questionModel
                ->where(array(
                    "id" => $question_id,
                    "state" => 1,
                ))
                ->setInc("answer_num", 1);

            returnJson(200);
        } else
            returnJson(500);
    }

    //回答列表
    public function getAnswerlist()
    {
        if (!IS_POST) {
            returnJson(415);
        }

        if (!authUser(I("post.stuNum"), I("idNum")))
            returnJson(403);

        $page = I("post.page") ?: 1;
        $size = I("post.size") ?: 6;
        $question_id = I("post.question_id");
        if (empty($question_id))
            returnJson(801);

        $answerModel = M("answerlist");
        $data = $answerModel
            ->field(array(
                "id",
                "user_id",
                "content",
                "created_at",
                "praise_num",
                "comment_num",
                "is_adopted",
            ))
            ->page($page, $size)
            ->where(array(
                "question_id" => $question_id,
                "state" => 1,
            ))
            ->order(array())
            ->select();


        for ($i = 0; $i < count($data); $i++) {
            $userinfo = getUserBasicInfoInTable($data[$i]['user_id']);
            $data[$i]['photo_url'] = array(
                "https://farm4.staticflickr.com/3703/33922601146_fb9867b205_k.jpg",
                "https://farm4.staticflickr.com/3703/33922601146_fb9867b205_k.jpg",
            );
            $data[$i]['content'] = json_decode($data[$i]['content']);
            $data[$i]['photo_thumbnail_src'] = $userinfo['photo_thumbnail_src'];
            $data[$i]['nickname'] = $userinfo['nickname'];
            $data[$i]['gender'] = $userinfo['gender'];
        }
        returnJson(200, "success", $data);
    }

    //采纳
    public function adopt()
    {
        if (!IS_POST)
            returnJson(415);
        if (!authUser(I("post.stuNum"), I("post.idNum")))
            returnJson(403);

        $question_id = I("post.question_id");
        $answer_id = I("post.answer_id");

        if (empty($question_id) || empty($answer_id))
            returnJson(801);

        $user_id = getUserIdInTable(I("post.stuNum"));

        $questionModel = M("questionlist");
        $answerModel = M("answerlist");

        $check_user = $questionModel
            ->where(array(
                "id" => $question_id,
                "user_id" => $user_id,
                "state" => 1,
            ))
            ->getField("user_id");
        if (empty($check_user))
            returnJson(403, "invalid question or user power is not enough");


        $checkAdopted = $answerModel
            ->where(array(
                "id" => $answer_id,
                "state" => 1,
                "is_adopted" => 1,
            ))
            ->getField("id");
        $checkOnly = $answerModel
            ->where(array(
                "question_id" => $question_id,
                "state" => 1,
                "is_adopted" => 1,
            ))
            ->getField("id");
        if (!empty($checkAdopted) || !empty($checkOnly))
            returnJson(801, "invalid question to answer");


        $answerModel
            ->where(array(
                "id" => $answer_id,
                "state" => 1,
            ))
            ->setField("is_adopted", 1);

        //积分处理
        //在积分模块之后需要补充

        returnJson(200);
    }

    //点赞
    public function praise()
    {
        if (!IS_POST) {
            returnJson(415);
        }
        if (!authUser(I("post.stuNum"), I("post.idNum")))
            returnJson(403);

        $answer_id = I("post.answer_id");
        if (empty($answer_id))
            returnJson(801);
        $user_id = getUserIdInTable(I("post.stuNum"));

        $prModel = M("praise_remark");
        $answerModel = M("answerlist");
        $datetime = new \DateTime();

        $checkUnique = $prModel
            ->where(array(
                "user_id" => $user_id,
                "target_id" => $answer_id,
                "state" => 1,
                "type" => 1,
            ))
            ->find();
        if (!empty($checkUnique))
            returnJson(403, "you have praise the answer once");

        $checkSelf = $answerModel
            ->where(array(
                "state" => 1,
                "id" => $answer_id,
            ))
            ->getField("user_id");
        if (empty($checkSelf) || $checkSelf == $user_id)
            returnJson(403, "can`t praise yourself");


        $prModel->create();
        $prModel->type = 1;
        $prModel->target_id = $answer_id;
        $prModel->user_id = $user_id;
        $prModel->created_at = $datetime->format("Y-m-d H:i:s");
        $prModel->state = 1;
        if ($prModel->add())
            $answerModel->where(array(
                "id" => $answer_id,
                "state" => 1,
            ))->setInc("praise_num", 1);
        else
            returnJson(500);
        returnJson(200);
    }

    //取消赞
    public function cancelPraise()
    {
        if (!IS_POST) {
            returnJson(415);
        }
        if (!authUser(I("post.stuNum"), I("post.idNum")))
            returnJson(403);

        $answer_id = I("post.answer_id");
        if (empty($answer_id))
            returnJson(801);
        $user_id = getUserIdInTable(I("post.stuNum"));

        $prModel = M("praise_remark");
        $answerModel = M("answerlist");

        $checkExistence = $prModel
            ->where(array(
                "state" => 1,
                "type" => 1,
                "user_id" => $user_id,
                "target_id" => $answer_id,
            ))
            ->setField("state", 0);
        if ($checkExistence == 0)
            returnJson(403, "the praise isn`t exist in the table");
        else {
            $answerModel->where(array(
                "id" => $answer_id,
                "state" => 1,
            ))->setDec("praise_num", 1);
            returnJson(200);
        }

    }

    //评论
    public function remark()
    {
        if (!IS_POST) {
            returnJson(415);
        }
        if (!authUser(I("post.stuNum"), I("post.idNum")))
            returnJson(403);

        $answer_id = I("post.answer_id");
        $content = I("post.content");

        if (empty($answer_id) || empty($content))
            returnJson(801);
        $user_id = getUserIdInTable(I("post.stuNum"));

        $prModel = M("praise_remark");
        $answerModel = M("answerlist");
        $datetime = new \DateTime();

        $prModel->create();
        $prModel->type = 2;
        $prModel->content = json_encode($content);
        $prModel->target_id = $answer_id;
        $prModel->user_id = $user_id;
        $prModel->created_at = $datetime->format("Y-m-d H:i:s");
        $prModel->state = 1;
        if ($prModel->add())
            $answerModel->where(array(
                "id" => $answer_id,
                "state" => 1,
            ))->setInc("comment_num", 1);
        else
            returnJson(500);
        returnJson(200);
    }

    //获取评论列表
    public function getRemarkList()
    {
        if (!IS_POST) {
            returnJson(415);
        } elseif (!authUser(I("post.stuNum"), I("post.idNum")))
            returnJson(403);

        $targetID = I("post.answer_id") ?: 0;
        if ($targetID == 0)
            returnJson(801);

        $page = I("post.page") ?: 0;
        $size = I("post.size") ?: 15;

        $prModel = M("praise_remark");

        $queryResult = $prModel
            ->field(array(
                'content',
                "user_id",
                "created_at",
            ))
            ->page($page)
            ->limit($size)
            ->where(array(
                "target_id" => $targetID,
                "state" => 1,
                "type" => 2,
            ))->select();

        $data = array();
        foreach ($queryResult as $value) {
            $userInfo = getUserBasicInfoInTable($value['user_id']);
            $value['nickname'] = $userInfo['nickname'];
            $value['photo_thumbnail_src'] = $userInfo['photo_thumbnail_src'];
            $value['gender'] = $userInfo['gender'];
            $value['content'] = json_decode($value['content']);
            unset($value['user_id']);
            array_push($data, $value);
        }

        returnJson(200, "success", $data);
    }
}