<?php
/**
 * Created by PhpStorm.
 * User: uncomplex func
 * Date: 2018/2/6
 * Time: 15:58
 */

namespace QA\Controller;


use Think\Controller;
use Think\Think;

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


    //提问
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

        $question = M("questionlist");
        $question->create();


        //请求字段
        $question->user_id = getUserIdInTable($stunum) ?: 0;
        if ($question->user_id == 0)
            returnJson(403, "invalid stunum");
        $question->title = json_encode(I("post.title"));
        $question->description = json_encode(I("post.description"));
        $question->is_anonymous = I("post.is_anonymous");
        $question->kind = I("post.kind");
        $question->tags = json_encode(I("post.tags"));

        //积分认证模块
        //记得写好这里
        //by uncomplex func
        //in 2018/2/8 1:10
        $question->reward = I("post.reward");

        //时间字段格式校验
        $disappearTime = I("post.disappear_time");
        if (date("Y-m-d H:i:s", strtotime($disappearTime)) == $disappearTime)
            $question->disappear_at = $disappearTime;
        else
            returnJson(801, "invalid time kind");

        //表中默认字段
        $question->state = 1;
        $question->created_at = $date->format("Y-m-d H:i:s");
        $question->updated_at = $question->created_at;
        $question->answer_num = 0;

        $question->add();
        returnJson(200);
    }

    public function uploadPicture()
    {
        //文件上传测试
        $fileConfig = array(
            "maxSize" => 3145728,
            'rootPath' => './QA/',
            'savePath' => 'Question',
            "saveName" => "uniqid",
            "exts" => array('jpg', 'gif', 'png', 'jpeg'),
            "autoSub" => true,
            "subName" => array('date', "Ymd"),
        );
        $upload = new \Think\Upload($fileConfig);
        $info = $upload->upload();
        if (!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
        } else {// 上传成功 获取上传文件信息
            foreach ($info as $file) {
                echo $file['savepath'] . $file['savename'] . "_" . $file['key'];
            }
        }
    }

    public function _before_updateReward()
    {
        if (!IS_POST) {
            returnJson(415);
        }
    }

    //修改悬赏分
    public function updateReward()
    {
        $checkField = array(
            "stuNum",
            "idNum",
            "reward",
            "question_id",
        );
        if (!checkParameter($checkField))
            returnJson(801);

        $request = I("post.");
        if (!authUser($request['stuNum'], $request['idNum']))
            returnJson(403);
        //积分确认模块!!!
        $questionModel = M("questionlist");
        $questionModel->reward = $request['reward'];
        $questionModel->where("id=" . $request['question_id'])->setField(
            array(
                "reward" => (int)$request['reward'],
            )
        );
        returnJson(200);
    }

    //取消提问
    public function cancelQuestion()
    {
        if (!IS_POST)
            returnJson(415);
        $stunum = I("post.stuNum");
        $idnum = I("post.idNum");
        if (!authUser($stunum, $idnum))
            returnJson(403, "invalid user or password");
        $question_id = I("post.question_id");
        $questionModel = M("questionlist");
        $result = $questionModel->where("id=" . $question_id)
            ->field(array(
                "user_id"
            ))
            ->find();
        if ($result['user_id'] == getUserIdInTable(I("post.stuNum"))) {
            $questionModel->where("id=" . $question_id)->setField(array(
                "state" => 0,
            ));
            returnJson(200);
        } else
            returnJson(500);
    }


    //首页问题列表
    public function getQuestionList()
    {
        $page = I("post.page") ?: 0;
        $size = I("post.size") ?: 6;
        $kind = I("post.kind") ?: 0;

        if ($kind === 0)
            returnJson(801);

        $queryField = array(
            "title",
            "description",
            "user_id",
            "kind",
            "tags",
            "reward",
            "answer_num",
            "disappear_at",
            "created_at",
            "is_anonymous",
            "id",
        );

        $questionModel = M("questionlist");
        $userModel = M("users");

        $result = $questionModel
            ->page($page, $size)
            ->field($queryField)
            ->where(array(
                "kind" => $kind,
                "state" => 1
            ))
            ->select();

        $data = array();
        foreach ($result as $question) {

            $userId = $question['user_id'];
            $info = $userModel->field("nickname,photo_thumbnail_src")
                ->where("id=" . $userId)
                ->find();
            unset($question['user_id']);


            if ($question['is_anonymous'] == 0) {
                $question['photo_thumbnail_src'] = $info['photo_thumbnail_src'];
                $question['nickname'] = $info['nickname'];
            } else {
                $question['photo_thumbnail_src'] = null;
                $question['nickname'] = "匿名用户";
            }

            $question['reward'] = (int)$question['reward'];
            $question['answer_num'] = (int)$question['answer_num'];
            $question['id'] = (int)$question['id'];
            $question['is_anonymous'] = (int)$question['is_anonymous'];
            $question['title'] = json_decode($question['title']);
            $question['description'] = json_decode($question['description']);
            $question['tags'] = json_decode($question['tags']);

            array_push($data, $question);
        }
        returnJson(200, '', $data);
    }


    //问题详细信息
    public function getDetailedInfo()
    {
        if (!authUser(I("post.stuNum"), I("post.idNum")))
            returnJson(403);

        $question_id = I("post.question_id");
        if (empty($question_id))
            returnJson(801);

        $questionModel = M("questionlist");
        $answerModel = M("answerlist");

        $queryField = array(
            "title",
            "description",
            "user_id",
            "tags",
            "reward",
            "answer_num",
            "disappear_at",
            "created_at",
            "is_anonymous",
        );

        $question = $questionModel
            ->field($queryField)
            ->where(array(
                "id" => $question_id,
                "state" => 1,
            ))
            ->find();
        if (empty($question))
            returnJson(801,'invalid question');
        $userinfo = getUserBasicInfoInTable($question['user_id']);
        $answerSet = $answerModel
            ->field(array(
                "user_id",
                "content",
                "created_at",
                "praise_num",
                "comment_num",
            ))
            ->page(0,6)
            ->where(array(
                "question_id" => $question_id,
                "state" => 1,
            ))
            ->select();

        $data = new \stdClass();

        $data->is_self=0;
        if (getUserIdInTable(I("post.stuNum"))==$question['user_id'])
            $data->is_self=1;

        $data->title = json_decode($question['title']);
        $data->description = json_decode($question['description']);
        $data->reward = $question['reward'];
        $data->dispaaear_at = $question['disappear_at'];
        $data->tags = json_decode($question['tags']);

        if ($question['is_anonymous'] == 0) {
            $data->questioner_nickname = $userinfo['nickname'];
            $data->questioner_photo_thumbnail_src = $userinfo['photo_thumbnail_src'];
        } else {
            $data->questioner_nickname = "匿名用户";
            $data->questioner_photo_thumbnail_src = '';
        }



        $data->answers = array();
        foreach ($answerSet as $value) {
            $answer = new \stdClass();
            $answerer = getUserBasicInfoInTable($value['user_id']);
            $answer->nickname = $answerer['nickname'];
            $answer->photo_thumbnail_src = $answerer['photo_thumbnail_src'];
            $answer->content = json_decode($value['content']);
            $answer->created_at = $value['created_at'];
            $answer->praise_num = $value['praise_num'];
            $answer->comment_num = $value['comment_num'];
            array_push($data->answers, $answer);
        }

        returnJson(200, 'success', $data);

    }
}