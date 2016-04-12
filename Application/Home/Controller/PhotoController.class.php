<?php
namespace Home\Controller;
use Think\Controller;
class PhotoController extends Controller {


    public function index(){

    }

    public function search(){
        $stunum = I('post.stunum');        
        $photo = M('photo');
        $condition = array(
                "stunum" => I('post.stunum')
            );
        $goal = $photo->where($condition)->field('stunum,date,photosrc,thumbnail_src')->find();
        if($goal){
             $info = array(
                    'state' => 200,
                    'info'  => 'success',
                    'data'  => $goal,
                );
         }else{
             $info = array(
                    'state' => 404,
                    'info'  => 'failed',
                    'data'  => array(),
                );
         }
         echo json_encode($info,true);
    }

    public function searchArticle(){
        $img_id = I('post.img_id');
        if($img_id == null){
            $info = array(
                    'state' => 404,
                    'info'  => 'failed',
                    'data'  => array(),
                );
            echo json_encode($info,true);
            exit;
        }
        $condition = array(
            'id' => $img_id
        );
        $articlePhoto = D('articlephoto');
    }

    public function uploadArticle(){
        $articlePhoto = D('articlephoto');
        if(I('post.stunum') == null){
            $info = array(
                    'state' => 404,
                    'info'  => 'failed',
                    'data'  => array(),
                );
            echo json_encode($info,true);
            exit;
        }
        $upload = new \Think\Upload();
        $upload->maxSize = 512000;
        $upload->exts = array('png', 'jpeg',"jpg" , 'PNG','JPEG','JPG');
        $upload->rootPath  =  "./Public/photo/";
        $upload->saveName = time().'_'.mt_rand();
        $upload->autoSub = false;
        $a = $upload->upload();
        if($upload->getError() != null){
            $info = array(
                'state' => 404,
                'info'  => 'failed',
                'data'  => array(),
            );
        }else{
            $site = $_SERVER["SERVER_NAME"];
            $folder_name = explode('/',$_SERVER["SCRIPT_NAME"]);
            $thunmbnail_src =  "http://".$site.'/'.$folder_name[1].'/Public/photo/thumbnail/'.$upload->saveName.".".$a['fold']['ext'];
            $content = array(
                "stunum"   => I('post.stunum'),
                "date"     => date("Y-m-d H:i:s", time()),
                "photosrc" => "http://".$site.'/'.$folder_name[1]."/Public/photo/".$upload->saveName.".".$a['fold']['ext'],
                "thumbnail_src" => $thunmbnail_src,
                'state'    => 1,
            );
            $thumbnail = new \Think\Image();
            $thumbnail->open('./Public/photo/'.$upload->saveName.".".$a['fold']['ext']);
            $thumbnail->thumb(150, 150)->save('./Public/photo/thumbnail/'.$upload->saveName.".".$a['fold']['ext']);
            $photo = M('articlephoto');
            $goal = $photo->add($content);

            if($goal){
                $info = array(
                    'state' => 200,
                    'info'  => 'success',
                    'data'  => $content,
                );
            }else{
                $info = array(
                    'state' => 404,
                    'info'  => 'failed',
                    'data'  => array(),
                );
            }
            echo json_encode($info,true);
        }


    }

    public function upload(){
        $photo = M('photo');
        $condition = array(
                "stunum" => I('post.stunum')
            );
        if(I('post.stunum') == null){
            $info = array(
                    'state' => 404,
                    'info'  => 'failed',
                    'data'  => array(),
                );
            echo json_encode($info,true);
            exit;
        }
        $checkExist = $photo->where($condition)->find();
        $upload = new \Think\Upload();
        $upload->maxSize = 512000;
        $upload->exts = array('png', 'jpeg',"jpg" , 'PNG','JPEG','JPG');
        $upload->rootPath  =  "./Public/photo/";
        $upload->saveName = time().'_'.mt_rand();
        $upload->autoSub = false;
        $a = $upload->upload();
        if($upload->getError() != null){
            $info = array(
                'state' => 404,
                'info'  => 'failed',
                'data'  => array(),
            );
        }else{
            $site = $_SERVER["SERVER_NAME"];
            $folder_name = explode('/',$_SERVER["SCRIPT_NAME"]);
            $thunmbnail_src =  "http://".$site.'/'.$folder_name[1].'/Public/photo/thumbnail/'.$upload->saveName.".".$a['fold']['ext'];
            $content = array(
                "stunum"   => I('post.stunum'),
                "date"     => date("Y-m-d H:i:s", time()),
                "photosrc" => "http://".$site.'/'.$folder_name[1]."/Public/photo/".$upload->saveName.".".$a['fold']['ext'],
                "thumbnail_src" => $thunmbnail_src,
                'state'    => 1,
            );
            $thumbnail = new \Think\Image();
            $thumbnail->open('./Public/photo/'.$upload->saveName.".".$a['fold']['ext']);
            $thumbnail->thumb(150, 150,\Think\Image::IMAGE_THUMB_FILLED)->save('./Public/photo/thumbnail/'.$upload->saveName.".".$a['fold']['ext']);
            if($checkExist != NULL){
                $goal = $photo->where($condition)->data($content)->save();
            }else{
                $goal = $photo->add($content);
            }
                        $user = M('users');
            $condition_user = array(
                "stunum" => I('post.stunum'),
            );
            $checkUser = $user->where($condition)->find();
            if($checkUser != NULL){
                $user_content = array(
                    "photo_src" => "http://".$site.'/'.$folder_name[1]."/Public/photo/".$upload->saveName.".".$a['fold']['ext'],
                    "photo_thumbnail_src" => $thunmbnail_src,
                    "update_time"  => date("Y-m-d H:i:s", time())
                );
                $goal_2 = $user->where($condition)->data($user_content)->save();
            }else{
                $user_content = array(
                    "stunum"   => I('post.stunum'),
                    "photo_src" => "http://".$site.'/'.$folder_name[1]."/Public/photo/".$upload->saveName.".".$a['fold']['ext'],
                    "photo_thumbnail_src" => $thunmbnail_src,
                    "created_time" => date("Y-m-d H:i:s", time()),
                    "update_time"  => date("Y-m-d H:i:s", time()),
                );
                $goal_2 = $user->add($user_content);
            }
            if($goal){
                $info = array(
                    'state' => 200,
                    'info'  => 'success',
                );
            }else{
                $info = array(
                    'state' => 404,
                    'info'  => 'failed',
                    'data'  => array(),
                );
            }
        }
        echo json_encode($info,true);
    }

    public function _empty() {
        $this->display('Empty/index');
    }
}

