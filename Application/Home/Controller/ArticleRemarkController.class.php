<?php
namespace Home\Controller;
use Home\Common\Article;
use Think\Controller;

class ArticleRemarkController extends BaseController {
    public function getRemark(){
        $page = I('post.page');
        $size = I('post.size');
        $remark_id = I('post.article_id');
        $type_id   = I('post.type_id');
        if($remark_id == null||$type_id == null){
            $info = array(
                    'state' => 801,
                    'status' => 801,
                    'info'  => 'invalid parameter',
                    'data'  => array(),
                );
            echo json_encode($info,true);
            exit;
        }
        $remark = M('articleremarks');
        $condition = array(
            "article_id" => $remark_id,
        );
        
        $remark = $remark
                    ->join('cyxbsmobile_users ON cyxbsmobile_articleremarks.user_id =cyxbsmobile_users.id')
                    ->where("cyxbsmobile_articleremarks.article_id = '$remark_id' and cyxbsmobile_articleremarks.articletypes_id = '$type_id'")
                    ->order('created_time DESC')
                    ->field('stunum,nickname,username,photo_src,photo_thumbnail_src,cyxbsmobile_articleremarks.created_time,content,answer_user_id');
        //如果设置page将分页返回回复
        if($page != null ){
            $page = empty($page) ? 0 : $page;
            $size = empty($size) ? 15 : $size;
            $start = $page*$size;
            $remark->limit($start,$size);
        }

        $result = $remark->select();
       	$info = array(
                    'state' => 200,
                    'status' => 200,
                    'data'  => $result,
                );
        echo json_encode($info,true);
    }

    /**
     * @post stuNum string required
     * @post idNum string  required
     * @post content string required
     * @post answer_user_id int default 0
     * @post article_id int required
     * @post type_id int required
     */
    public function postRemarks(){
        $information = I('post.');
        if($information['id']< 200 && $information['type_id'] == 5) {
            $information['type_id'] = 6;
        }
        if (empty($information['content']) || empty($information['article_id']) || empty($information['type_id']))
            returnJson(801, '', array('state' => 801));
        if (false === $article = Article::setArticle($information, $information['stuNum'])) {
            returnJson(404, 'error article', array('state' => 401));
        }
        $result = $article->addRemark($information['content'], $information['answer_user_id']);
        if ($result == false)
            returnJson(404, $article->getError(), array('state' => 404));
        else
            returnJson(200, '', array('state' => 200));
    }




}