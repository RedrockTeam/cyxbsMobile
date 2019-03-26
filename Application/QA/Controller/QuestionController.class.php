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
    protected $fileConfig = array(
        "maxSize" => 6400000,
        'rootPath' => './Public/QA/Question/',
        "saveName" => "uniqid",
        "exts" => array('png', 'jpeg', "jpg", 'PNG', 'JPEG', 'JPG'),
        "autoSub" => false,
        "subName" => array('date', "Ymd"),
    );
    private $filePath = "/Public/QA/Question/";

    public function index()
    {
        echo "hello world!";
    }

    public function _before_add()
    {
        //前置操作 不是POST直接返回405
        if (!IS_POST)
            returnJson(405);
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

        $insertID = (int)$question->add();
        returnJson(200, "success", array(
            "id" => $insertID
        ));
    }

    //图片上传
    public function uploadPicture()
    {
        if (!IS_POST) {
            returnJson(415);
        }
        $stuNum = I("post.stuNum");
        $idNum = I("post.idNum");
        $question_id = I("post.question_id");

        $user_id = getUserIdInTable($stuNum);
        if (!authUser($stuNum, $idNum))
            returnJson(403);
        $questionModel = M("questionlist");
        $datetime = new \DateTime();

        $checkExist = $questionModel
            ->where(array(
                "user_id" => $user_id,
                'id' => $question_id,
                "state" => 1,
            ))
            ->find();
        if (empty($checkExist))
            returnJson(403, "it is not your question or invalid question");


        $upload = new \Think\Upload($this->fileConfig);
        $info = $upload->upload();
        $photoModel = M("question_photos");
        $checkExist = $photoModel
            ->where(array(
                "question_id" => $question_id,
                "state" => 1,
            ))->find();
        if (!empty($checkExist))
            returnJson(403, "the question has already haven the photos");

        if (!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
        } else {// 上传成功 获取上传文件信息
            $result = array();
            foreach ($info as $key => $value) {
                if (preg_match('/photo[0-9]/', $key) != 1)
                    returnJson(801, "the file key is wrong");
                $photoModel->create();
                $photoModel->filepath = ($this->filePath) . $value['savename'];
                $photoModel->question_id = $question_id;
                $photoModel->created_at = $datetime->format("Y-m-d H:i:s");
                $photoModel->updated_at = $photoModel->created_at;
                $photoModel->add();
                array_push($result, DOMAIN . $this->filePath . $value['savename']);
            }
            returnJson(200, "success", $result);
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
        $questionModel
            ->where("id=" . $request['question_id'])
            ->setField(array(
                "reward" => (int)$request['reward'],
            ));
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
        //认证部分 确定用户身份
        $stunum = I("post.stunum");
        $idnum = I("post.idnum");
        if (!authUser($stunum, $idnum))
            returnJson(403);

        $page = I("post.page") ?: 0;
        $size = I("post.size") ?: 6;
        $kind = I("post.kind") ?: 0;

        if ($kind === 0)
            returnJson(400, "invalid kind");

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
        );  //所需查询的列

        $userId = getUserIdInTable($stunum);
        //加载该用户忽略列表
        $ignoreModel = M("ignore_problems");
        $ignoreList = $ignoreModel
            ->where(array(
                "user_id" => $userId,
                "state" => "1"
            ))
            ->getField('question_id', true);

        $questionModel = M("questionlist");
        $timeRequire = array();
        if (empty($ignoreList)) {
            $timeRequire = array(
                "disappear_at" => array("EGT", date("Y-m-d H:i:s")),
            );
        } else {
            $timeRequire = array(
                "disappear_at" => array("EGT", date("Y-m-d H:i:s")),
                "id" => array("NOT IN", $ignoreList)
            );
        }
        if ($kind == "全部") {
            $result = $questionModel
                ->page($page, $size)
                ->field($queryField)
                ->where(array(
                    "state" => 1,
                ))
                ->where($timeRequire)
                ->order("disappear_at asc")
                ->select();
        } else {
            $result = $questionModel
                ->page($page, $size)
                ->field($queryField)
                ->where(array(
                    "kind" => $kind,
                    "state" => 1,
                ))
                ->where($timeRequire)
                ->order("disappear_at asc")
                ->select();
        }

        $data = array();
        foreach ($result as $question) {

            $userId = $question['user_id'];
            $info = getUserBasicInfoInTable($userId);
            $question["is_self"] = 0;
            if ($userId == getUserIdInTable($stunum))
                $question["is_self"] = 1;

            unset($question['user_id']);


            if ($question['is_anonymous'] == 0) {
                $question['photo_thumbnail_src'] = $info['photo_thumbnail_src'];
                $question['nickname'] = $info['nickname'];
                $question['gender'] = $info['gender'];
            } else {
                $question['photo_thumbnail_src'] = "";
                $question['nickname'] = "匿名用户";
                $question['gender'] = '';
            }

            $question['reward'] = (int)$question['reward'];
            $question['answer_num'] = (int)$question['answer_num'];
            $question['id'] = (int)$question['id'];
            $question['is_anonymous'] = (int)$question['is_anonymous'];

            array_push($data, $question);
        }
        returnJson(200, 'success', $data);
    }


    //问题详细信息
    /*
     * @todo 该接口即将废弃
     */
    public function getDetailedInfo()
    {
        if (!authUser(I("post.stuNum"), I("post.idNum")))
            returnJson(403);

        $question_id = I("post.question_id");
        if (empty($question_id))
            returnJson(801);

        //请求者用户id
        $requester = getUserIdInTable(I("post.stuNum"));

        //所需要使用的模型初始化
        $questionModel = M("questionlist");
        $answerModel = M("answerlist");
        $prModel = M("praise_remark");
        $photoModel = M("question_photos");
        $answerPhotoModel = M("answer_photos");

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
            "kind",
        );

        $question = $questionModel
            ->field($queryField)
            ->where(array(
                "id" => $question_id,
                "state" => 1,
            ))
            ->find();
        if (empty($question))
            returnJson(801, 'invalid question');

        //提问者用户信息
        $userinfo = getUserBasicInfoInTable($question['user_id']);

        //问题信息压制
        $data = new \stdClass();
        $data->is_self = 0;
        if (getUserIdInTable(I("post.stuNum")) == $question['user_id'])
            $data->is_self = 1;

        $data->title = $question['title'];
        $data->description = $question['description'];
        $data->reward = $question['reward'];
        $data->disappear_at = $question['disappear_at'];
        $data->tags = $question['tags'];
        $data->kind = $question['kind'];

        //图片链接压制
        $pictureSet = $photoModel->where(array(
            "question_id" => $question_id,
            "state" => 1,
        ))->getField("filepath", true);

        $data->photo_urls = array();

        foreach ($pictureSet as $value) {
            array_push($data->photo_urls, DOMAIN . $value);
        }

        //判断提问者是否匿名
        if ($question['is_anonymous'] == 0) {
            $data->questioner_nickname = $userinfo['nickname'];
            $data->questioner_photo_thumbnail_src = $userinfo['photo_thumbnail_src'];
            $data->questioner_gender = $userinfo['gender'];
        } else {
            $data->questioner_nickname = "匿名用户";
            $data->questioner_photo_thumbnail_src = '';
            $data->questioner_gender = "";
        }


        $answerSet = $answerModel
            ->field(array(
                "id",
                "user_id",
                "content",
                "created_at",
                "praise_num",
                "comment_num",
                "is_adopted"
            ))
            ->page(0, 6)
            ->where(array(
                "question_id" => $question_id,
                "state" => 1,
            ))
            ->order(array(
                "is_adopted" => 'desc',
                "created_at" => "desc",
            ))
            ->select();

        //答案列表信息压制
        $data->answers = array();
        foreach ($answerSet as $value) {
            $answer = new \stdClass();
            $answer->id = $value['id'];
            $answerer = getUserBasicInfoInTable($value['user_id']);
            $answer->nickname = $answerer['nickname'];
            $answer->photo_thumbnail_src = $answerer['photo_thumbnail_src'];
            $answer->gender = $answerer['gender'];
            $answer->content = $value['content'];
            $answer->created_at = $value['created_at'];
            $answer->praise_num = $value['praise_num'];
            $answer->comment_num = $value['comment_num'];
            $answer->is_adopted = $value['is_adopted'];

            $is_praised = $prModel
                ->where(array(
                    "type" => 1,
                    "target_id" => $answer->id,
                    "user_id" => $requester,
                    "state" => 1,
                ))
                ->count();
            if ($is_praised == 0)
                $answer->is_praised = 0;
            else
                $answer->is_praised = 1;

            //答案图片链接
            $answer->photo_url = array();
            $photoSet = $answerPhotoModel
                ->where(array(
                    "id" => $answer->id,
                    "state" => 1,
                ))
                ->getField("file_path", true);
            foreach ($photoSet as $item) {
                array_push($photoSet, DOMAIN . $item);
            }
            array_push($data->answers, $answer);
        }

        returnJson(200, 'success', $data);
    }

    //问题忽略功能
    public function ignore()
    {
        if (!authUser(I("post.stuNum"), I("post.idNum")))
            returnJson(403);

        $questionId = I("post.question_id");

        if (empty($questionId))
            returnJson(400);

        $userId = getUserIdInTable(I("post.stuNum"));

        $model = M("ignore_problems");

        $checkExistence = $model
            ->field(array("user_id", "question_id"))
            ->where(array(
                "user_id" => $userId,
                "question_id" => $questionId,
                "state" => 1
            ))->find();
        if (isset($checkExistence) || $checkExistence["user_id"] == $userId)
            returnJson(403, "already ignored the question or it's your question, please check it");
        else {
            $model->create();
            $model->user_id = $userId;
            $model->question_id = $questionId;
            if ($model->add())
                returnJson(200);
        }
    }

    /*
     * @todo 草稿箱图片功能
     */
}