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
			6		=>	'notices',
		);
	/**
	 * 删除文章
	 * @return [type] [description]
	 */
	public function deleteArticle() {
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
				$result = $remark->where(array(
					'article_id'			=> $article_id,
					'articletypes_id'		=> $type_id,
					))->delete();
				$result1 = $praise->where(array(
					'article_id'			=> $article_id,
					'articletype_id'		=> $type_id,
					))->delete();
				$result2 = $article->where('id='.$article_id)->delete();
				if($result && $result1 && $result2) {
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
	 * 根据status返回对应的json语句
	 * @param  int $status 		http请求码
	 * @param  array  $data   json里需要返回的数据
	 * @param  string $info   重写info信息
	 * @return [type]         [description]
	 */
	protected function returnJson($status, $data = array(), $info="") {
		switch ($status) {
			case 404: 
				$report = array('status'=>'404', 'info'=>'请求参数错误');
				break;
			case 403:
				$report = array('status'=>'403', 'info'=>'Don\'t permit');
				break;
			case 801:
				$report = array('status'=>'801', 'info'=>'invalid parameter');
				break;
			case 200:
				$report = array('status'=>'200', 'info'=>'success');
				break;
			default:
				$report = array('status'=>$status, 'info'=>"");
		}

		if(!empty($info)) {
			$report['info'] = $info;
		}
		if(!empty($data)) {
			if(array_key_exists('info', $data) || array_key_exists('status', $data)) {
				return false;
			} else {
				$report = array_merge($report, $data);
			}
		}
		$json = json_encode($report);
		echo $json;
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
		$user = D('users')->where('stunum='.$stunum)->find();
		$admin_exist = M('administrators')->where('user_id='.$user['id'])->find();
		if ($admin_exist) {
			return $role = 'admin';
		} elseif ($writor_id == $user['id']) {
			return $role = 'writor';
		} else {
			$this->returnJson('403');
			return false;
		}
	}	
}