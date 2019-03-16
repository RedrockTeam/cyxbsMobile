<?php
/**
 * Created by PhpStorm.
 * User: uncomplex func
 * Date: 2018/2/8
 * Time: 17:52
 */

namespace QA\Controller;

use Think\Controller;
use Think\Upload;

class AnswerController extends Controller
{
    protected $fileConfig = array(
        "maxSize" => 6400000,
        'rootPath' => './Public/QA/Answer/',
        "saveName" => "uniqid",
        "exts" => array('jpg', 'gif', 'png', 'jpeg'),
        "autoSub" => false,
        "subName" => array('date', "Ymd"),
    );
//    private $domain = "https://wx.idsbllp.cn/springtest/cyxbsMobile";
    private $domain = DOMAIN;
    private $filePath = "/Public/QA/Answer/";

    private function urlTranslate($url)
    {
        $result = array();
        if (is_array($url)) {
            foreach ($url as $value) {
                $value = $this->domain . $value;
                array_push($result, $value);
            }
            return $result;
        } else
            return $this->domain . $url;
    }


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

        $answer_id = $answerModel->add();
        if (isset($answer_id)) {
            $questionModel
                ->where(array(
                    "id" => $question_id,
                    "state" => 1,
                ))
                ->setInc("answer_num", 1);
            returnJson(200, "success", $answer_id);
        } else
            returnJson(500);
    }

    //回答列表
    public function getAnswerlist()
    {
        if (!IS_POST) {
            returnJson(415);
        }

        if (!authUser(I("post.stuNum"), I("post.idNum")))
            returnJson(403);

        $stunum = I("post.stuNum");

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

        $photoModel = M("answer_photos");
        $prModel = M("praise_remark");

        for ($i = 0; $i < count($data); $i++) {
            $userinfo = getUserBasicInfoInTable($data[$i]['user_id']);
            $data[$i]['photo_url'] = $photoModel->where(array(
                "answer_id" => $data[$i]['id'],
                "state" => 1,
            ))->getField("file_path", true);//回答问题
            $data[$i]['photo_url'] = $this->urlTranslate($data[$i]['photo_url']);
            $data[$i]['content'] = $data[$i]['content'];
            $data[$i]['photo_thumbnail_src'] = $userinfo['photo_thumbnail_src'];
            $data[$i]['nickname'] = $userinfo['nickname'];
            $data[$i]['gender'] = $userinfo['gender'];
            $is_praised = $prModel
                ->where(array(
                    "type" => 1,
                    "target_id" => $data[$i]['id'],
                    "user_id" => getUserIdInTable($stunum),
                    "state" => 1,
                ))
                ->count();
            $data[$i]['is_praised'] = 0;
            if ($is_praised > 0)
                $data[$i]['is_praised'] = 1;
        }
        returnJson(200, "success", $data);
    }

    //采纳

    /**@author yangruixin
     * @todo 积分处理 在积分模块之后需要补充
     *
     */
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
            ->getField("user_id");  //检查用户权限是否足够
        if (empty($check_user))
            returnJson(403, "invalid question or user power is not enough");


        $checkAdopted = $answerModel
            ->where(array(
                "id" => $answer_id,
                "state" => 1,
                "is_adopted" => 1,
            ))
            ->getField("id");   //检查该答案是否已经被采纳
        $checkOnly = $answerModel
            ->where(array(
                "question_id" => $question_id,
                "state" => 1,
                "is_adopted" => 1,
            ))
            ->getField("id");   //检查该问题是否已经有被采纳的答案
        if (!empty($checkAdopted) || !empty($checkOnly))
            returnJson(801, "invalid question to answer");


        $answerModel
            ->where(array(
                "id" => $answer_id,
                "state" => 1,
            ))
            ->setField("is_adopted", 1);
        $questionModel
            ->where(array(
                "id" => $question_id,
                "state" => 1
            ))
            ->setField("is_adopted", 1);


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
        $prModel->content = $content;
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

    //答案图片上传
    public function uploadPicture()
    {
        if (!IS_POST)
            returnJson(415);
        $stuNum = I("post.stuNum") ?: 0;
        $idNum = I("post.idNum") ?: 0;
        if ($stuNum === 0 || $idNum === 0) {
            returnJson(403, "idNum or stuNum is null");
        }
        if (!authUser($stuNum, $idNum))
            returnJson(403, "idNum or stuNum is wrong");

        $user_id = getUserIdInTable($stuNum);
        $answer_id = I("post.answer_id");
        $answerModel = M("answerlist");
        $checkExist = $answerModel
            ->where(array(
                "user_id" => $user_id,
                "id" => $answer_id,
                "state" => 1,
            ))->find();
        if (empty($checkExist))
            returnJson(801, "invalid question or it is not your question");

        $upload = new Upload($this->fileConfig);
        $info = $upload->upload();
        if (!$info) {
            $this->error($upload->getError());
        } else {
            $result = array();
            foreach ($info as $key => $value) {
                if (preg_match('/photo[0-9]/', $key) != 1)
                    returnJson(801, "the file key is wrong");
                $photoModel = M("answer_photos");
                $checkExist = $photoModel
                    ->where(array(
                        "answer_id" => $answer_id,
                        "state" => 1,
                    ))
                    ->find();
                if (!empty($checkExist))
                    returnJson(403, "the answer has already haven the photos");
                $datetime = new \DateTime();
                $photoModel->create();
                $photoModel->file_path = $this->filePath . $value['savename'];
                $photoModel->answer_id = $answer_id;
                $photoModel->created_at = $datetime->format("Y-m-d H:i:s");
                $photoModel->add();
                array_push($result, $this->domain . $this->filePath . $value['savename']);
            }
            returnJson(200, "success", $result);
        }
    }

    public function remarkJsonProcess()
    {
        $model = M("praise_remark");
        var_dump("123");
        $data = $model->field(array("id", "content"))->where(array("type" => 2))->select();
        for ($i = 0; $i < count($data); $i++) {
            $data["content"] = json_decode($data["content"]);
            $model->where(array("id" => $data["id"]))->setField(array("content" => $data["content"]));
        }
        return "123";
    }
}