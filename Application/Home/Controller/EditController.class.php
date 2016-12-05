<?php

namespace Home\Controller;

use Think\Controller;
use Home\Controller\BaseController;
use Home\Controller\ArticleController;

class EditController extends BaseController
{
	public static $table_type = array(
			1 		=> 'news',
			2		=> 'news',
			3 		=> 'news',
			4 		=> 'news',
			5 		=> 'articles',
			6		=> 'notices',
			7		=> 'topicarticles',
			'topic' => 'topics',
		);
	/**
	 * 删除文章
	 * @return [type] [description]
	 */
	public function deleteArticle() 
	{
		$type_id 	= I('post.type_id');
		$article_id = I('post.article_id');
		$stunum		= I('post.stuNum');

		//确认参数完整
		if (empty($type_id) || empty($article_id)) {
			returnJson(801);
			exit;
		}

		$article = $this->getArticle($article_id, $type_id);
		
		if($article === false) {
			returnJson(404, '该文章不存在');
			exit;
		}
		//是否有权力删除文章
		if ($this->hasPower($article_id, $type_id, $stunum, $error)) {
				$result = $this->delete($article_id, $type_id);
				if($result) {
					returnJson(200);
				} else {
					returnJson(404, '操作失败');
				}
			
		} else {	
			returnJson(403, $error);
		}

	}


	/**
	 * 获取文章的信息
	 * @param  int  $article_id 文章的id值
	 * @param  mix 	$type   	文章类型
	 * @return bool|array      不存在返回false,存在返回文章数据
	 */
	protected function getArticle($article_id, $type) {
		$position = array('id'	=> $article_id,);
		$Article = D(self::$table_type[$type])->where($position)->find();
		if(empty($Article)) {
			return false;
		}
		return $Article;
	}


	/**
	 * 进行文章的删除工作
	 * @param  [type] $article_id [description]
	 * @param  [type] $type_id    [description]
	 * @return [type]             [description]
	 */
	public function delete($article_id, $type_id)
	{
		if (empty($article_id) || empty($type_id)) {
			return false;
		}
		$article = M(self::$table_type[$type_id]);
		$praise = M('articlepraises');
		$remark = M('articleremarks');
		//应用事务进行删除
		M()->startTrans();
		$remark_exist = $remark->where(array(
			'article_id'		=> $article_id,
			'articletypes_id'	=> $type_id,
			))->select();
		if (empty($remark_exist)){
			$remark_result = true;
		} else {
			$remark_result = $remark->where(array(
				'article_id'	 => $article_id,
				'articletypes_id'=> $type_id,))
				->delete();
		}
		$praise_exist = $praise->where(array(
			'article_id'			=> $article_id,
			'articletype_id'		=> $type_id,
			))->select();
		if (empty($praise_exist)) {
			$praise_result = true;
		} else {
			$praise_result = $praise->where(array(
			'article_id'	=> $article_id,
			'articletype_id'=> $type_id,
			))->delete();
		}
		//不为通知则删除hotarticle里的内容
		if ($type_id != 6) {
			$hotarticles = M('hotarticles');
			$hotarticles_result = $hotarticles->where(array(
				'article_id' 	=> $article_id,
				'articletype_id' => $type_id,
				))->delete();
		} else {
			$hotarticles_result = true;
		}

		$article_result = $article->where('id=%d',$article_id)->delete();

		$result = $remark_result && $praise_result && $article_result && hotarticles_result;
		if ($result) {
			M()->commit();
		} else {
			M()->rollback();
		}
		return $result;
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
    	$information = I('post.');

    	if (!$this->hasPower($information['article_id'], $information['type_id'], $information['stuNum'], $error)) {
    		returnJson(403, $error);
    	}

    	$article = new ArticleController;

    	if ($article->produceArticleInformation($information, false, $error)) {
    		returnJson(404, $error);
    	}

    	$information['updated_time'] = date('Y-m-d H:i:s');
    	$result = M(self::$table_type[$type_id])->save($information);
    	if ($result) {
    		returnJson(200);
    	} else {
    		returnJson(500, 'error');
    	}
    }

    /**
     * 判断是否有修改文章的权限
     * @param  number  $id      文章的id
     * @param  mix     $type_id 文章类型id
     * @param  string  $stunum  学号
     * @param  string  $error   没有权限的原因
     * @return boolean          是否有权力
     */
    protected function hasPower($id, $type_id, $stunum, $error='')
    {
    	$article = $this->getArticle($id, $type_id);

        //判断文章是否存在
        if (!$article) {
            $error = 'not match the article';
            return false;
        }
        //是否有权限
        if ($type_id == 6 || $type_id <= 4 || $article['user_id'] == 0) {
            return $this->is_admin($stunum);
        } else {
            //如果为管理员
            if ($this->is_admin($stunum)) {
                return true;
            }
            $user = M('users')->find($article['user_id']);
            return $user['stunum'] == $stunum;
        }
    }	

}