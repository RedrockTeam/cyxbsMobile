<?php
namespace Home\Controller;
use Home\Common\Article;
use Home\Controller\BaseController;

class EditController extends BaseController
{

    /**
     * 删除文章
     * @return [type] [description]
     */
    public function deleteArticle()
    {
        $type_id 	= I('post.type_id');
        $article_id = I('post.article_id');
        $stuNum		= I('post.stuNum');
        //确认参数完整
        if (empty($type_id) || empty($article_id)) {
            returnJson(801);
            exit;
        }
        $article = compact('type_id', 'article_id');

        $article =  Article::setArticle($article, $stuNum);

        if ($article === false) returnJson(404, "error article");

        $result = $article->delete(I('post.forceDelete'));

        if($result) returnJson(200);
        else    returnJson(404, $article->getError());
    }


    /**
     * 修改topic
     */
    public function editTopic()
    {
        $information = I('post.');
        $topic = $this->getArticle($information['topic_id'], 'topic');

        if (!$topic) {
            returnJson(404);
        }

        if (!$this->hasPower($information['topic_id'], 'topics', $information['stuNum'], $error)) {
            returnJson(403, $error);
        }
        $controller = new ArticleController;
        if (!$controller->produceTopicInformation($information, false,$error)) {
            returnJson(404, $error);
        }
        $information['updated_time'] = date('Y-m-d H:i:s');
        $result = $topic->data($information)->save();
        if ($result)
            returnJson(200);
        else
            returnJson(500, 'unknow error');

    }
    public function editArticle()
    {

    }

    /**
     *
     */
    public function recoverArticle()
    {
        $information = I('post.');
        $article = Article::setArticle($information, $information['stuNum']);

        if ($article === false)
            returnJson(404, 'error article');
        $result = $article->recover();
        if ($result)    returnJson(200);
        else returnJson(404, $article->getError());
    }

}