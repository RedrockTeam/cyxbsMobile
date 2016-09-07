<?php

namespace Home\Controller;

use Think\Controller;

class EditController extends BaseController
{
	protected $writor_allowed_type = array(5);
	protected $admin_allowed_type = array(5,6);
	protected $table_type = array(
			1 		=> 'news',
			2		=> 'news',
			3 		=> 'news',
			4 		=> 'news',
			5 		=> 'articles',
			6		=> 'notices',
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
			$this->returnJson(801);
			exit;
		}

		if(!in_array($type_id, $this->admin_allowed_type)) {
			$this->returnJson(403);
			exit;
		}
		$article = $this->getArticle($article_id, $type_id);
		if($article === false) {
			$this->returnJson(404, '', '该文章不存在');
			exit;
		}
		//获取角色
		$role = $this->getRole($article['user_id'], $stunum);
		if (!$role){
			exit;
		}
		if ($role == 'admin'  || ($role == 'writor' && in_array($type_id, $this->writor_allowed_type))) {
				//应用事务进行删除
				$article = M($this->table_type[$type_id]);
				$praise = M('articlepraises');
				$remark = M('articleremarks');
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
				if ($type_id != 6) {
					$hotarticles = M('hotarticles');
					$hotarticles_result = $hotarticles->where(array(
						'article_id' 	=> $article_id,
						'articletype_id' => $type_id,
						))->delete();
				} else {
					$hotarticles_result = true;
				}
				$article_result = $article->where('id='.$article_id)->delete();
				if($remark_result && $praise_result && $article_result && hotarticles_result) {
					M()->commit();
					$this->returnJson(200);
				} else {
					M()->rollback();
					$this->returnJson(404, array(), '操作失败');
				}
			
		} else {	
			$this->returnJson(403);
		}

	}


	/**
	 * 获取文章的信息
	 * @param  int  $article_id 文章的id值
	 * @param  int $type_id    文章类型
	 * @return bool|array      不存在返回false,存在返回文章数据
	 */
	protected function getArticle($article_id, $type_id) {
		$position = array('id'		=> $article_id,);
		$Article = D($this->table_type[$type_id])->where($position)->find();
		if(empty($Article)) {
			return false;
		}
		return $Article;
	}


	/**
	 * 得到该用户的角色
	 * @param  int $article_user_id 文章作者的id
	 * @param  int $stunum          学号
	 * @return bool                  
	 */
	protected function getRole($writor_id, $stunum) {
		if(empty($writor_id) || empty($stunum)) {
			$this->returnJson('404');
			return false;
		}
		$is_admin = $this->is_admin($stunum);
		if ($is_admin) {
			return $role = 'admin';
		} elseif ($writor_id == $user['id']) {
			return $role = 'writor';
		} else {
			$this->returnJson('403');
			return false;
		}
	}	
}