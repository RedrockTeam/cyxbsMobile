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

        $user_id=getUserIdInTable(I("post.stuNum"));

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
        $answerModel->created_at = $date->format("Y-m-d H:i:s");
        $answerModel->updated_at = $answerModel->created_at;
        $answerModel->state = 1;

        if ($answerModel->add()) {
            $questionModel
                ->where(array(
                    "id" => $question_id,
                    "state" => 1,
                ))
                ->setField("answer_num", 1);

            returnJson(200);
        } else
            returnJson(500);
    }

    public function getAnswerlist(){
        if (!IS_POST){
            returnJson(415);
        }

        if(!authUser(I("post.stuNum"),I("idNum")))
            returnJson(403);
    }
}