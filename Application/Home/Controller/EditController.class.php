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
        $information = I('post.');
        $url = U("Home/Article/deleteArticle",'',true,true);
        $result = curlPost($url, $information);
        if (!$result)
            returnJson(404);

        echo $result;
    }


    /**
     * 修改topic
     */
    public function editTopic()
    {


    }
    public function editArticle()
    {

    }

    public function recoverArticle() {
        $information = I('post.');
        $url = U("Article/recoverArticle", '',true,true);
        $result = curlPost($url, $information);
        if (!$result)
            returnJson(404);

        echo $result;
    }



}