<?php
namespace Home\Controller;
use Think\Controller;
class PhotoController extends Controller {

    protected $key = 'fold';
    protected $rootPath = './Public/photo/';
    
    public function index(){

    }

    public function search(){
        header("Content-type: application/json");
        $stunum = I('post.stunum');        
        $photo = M('photo');
        $condition = array(
                "stunum" => I('post.stunum')
            );
        $goal = $photo->where($condition)->field('stunum,date,photosrc,thumbnail_src')->find();
        if($goal){
             $info = array(
                    'state' => 200,
                    'status' => 200,
                    'info'  => 'success',
                    'data'  => $goal,
                );
         }else{
             $info = array(
                    'state' => 404,
                    'status' => 404,
                    'info'  => 'failed',
                    'data'  => array(),
                );
         }
         echo json_encode($info,true);
    }

    public function searchArticle(){
        header("Content-type: application/json");
        $img_id = I('post.img_id');
        if($img_id == null){
            $info = array(
                    'state' => 404,
                    'status' => 404,
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

    /**
     * 获得上传者的学号
     * @return bool|string 
     */
    public function getUploader()
    {
        if (I('post.stunum') != null) {
            $stunum = I('post.stunum');
        
        } elseif (session('admin.stunum')) {
           
            $result = M('admin')->where('stunum=\'%s\'', session('admin.stunum'))->find();
           
            if (!$result)
                return false;
    
            $stunum = session('admin.stunum');
        } else {
            return false;
        }
        return $stunum;
    }
    
    public function uploadArticle()
    {
        if (!$stunum = $this->getUploader()) {
            returnJson(404, 'failed', array('status'=> 404, 'data'=>array()));
        }
        
        if (!$info = $this->pictrueUpload()) {
            returnJson(404, 'failed', array('status'=> 404, 'data'=>array()));
        }
       
        $content = array_pop($info);
        $content['stunum'] = $stunum;                                 
        returnJson(200, '', array('status'=> 200, 'data'=>$content));
    }
    //多文件上传
    public function multipleUploadArticle()
    {
        if (!$stunum = $this->getUploader()) {
            returnJson(404, 'failed', array('status'=> 404, 'data'=>array()));
        }
        
        if (!$info = $this->pictrueUpload(array(), $error)) {
            returnJson(404, 'failed', array('error'=> $error));
        }

        $trans = array(
            'photosrc' => 'url',
            "thumbnail_src" => 'thumbnailUrl',        
        );
        //更改信息
        foreach ($info as  $file) {
            foreach ($file as $key => $value) {
                if (isset($trans[$key])) {
                    $file[$trans[$key]] = $value;
                    unset($file[$key]);
                }
            }
        }
    }

    public function pictrueUpload($files=array(), &$error='')
    {
        $upload = new \Think\Upload();
        $upload->maxSize = 4194304;
        $upload->exts = array('png', 'jpeg',"jpg" , 'PNG','JPEG','JPG');
        $upload->rootPath  =  $this->rootPath;
        $upload->saveName = time().'_'.mt_rand();
        $upload->autoSub = false;
        $files = $upload->upload();
        if(($error = $upload->getError()) != null){
           return false;
        }else{
           $info = $this->processPhoto($files);
           $result = $this->consoleUpload($stunum, $info);
        }
        return $result ? $info : false;
    }

    public function upload(){
        header("Content-type: application/json");
        $photo = M('photo');
        if(I('post.stunum') != null){
            $stunum = I('post.stunum');
        
        } elseif(session('admin.stunum')) {
           
            $result = M('admin')->where('stunum=\'%s\'', session('admin.stunum'))->find();
           
            if (!$result)
                returnJson(404, 'failed', array('status'=> 404, 'data'=>array()));
    
            $stunum = session('admin.stunum');
        } else {
            returnJson(404, 'failed', array('status'=> 404, 'data'=>array()));
        }
        $condition = array(
            "stunum" => $stunum
        );
        $checkExist = $photo->where($condition)->find();
        $upload = new \Think\Upload();
        $upload->maxSize = 4194304;
        $upload->exts = array('png', 'jpeg',"jpg" , 'PNG','JPEG','JPG');
        $upload->rootPath  =  "./Public/photo/";
        $upload->saveName = time().'_'.mt_rand();
        $upload->autoSub = false;
        $a = $upload->upload();
        if($upload->getError() != null){
            $info = array(
                'state' => 404,
                'status' => 404,
                'info'  => 'failed',
                'data'  => array(),
            );
        }else{
            $site = $_SERVER["SERVER_NAME"];
            $folder_name = explode('/',$_SERVER["SCRIPT_NAME"]);
            $thunmbnail_src =  "http://".$site.'/'.$folder_name[1].'/Public/photo/thumbnail/'.$upload->saveName.".".$a['fold']['ext'];
            $content = array(
                "stunum"   => $stunum,
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
            $checkUser = $user->where($condition)->find();
            if($checkUser != NULL){
                $user_content = array(
                    "photo_src" => "http://".$site.'/'.$folder_name[1]."/Public/photo/".$upload->saveName.".".$a['fold']['ext'],
                    "photo_thumbnail_src" => $thunmbnail_src,
                    "updated_time"  => date("Y-m-d H:i:s", time())
                );
                $goal_2 = $user->where($condition)->data($user_content)->save();
            }else{
                $user_content = array(
                    "stunum"   => $stunum,
                    "photo_src" => "http://".$site.'/'.$folder_name[1]."/Public/photo/".$upload->saveName.".".$a['fold']['ext'],
                    "photo_thumbnail_src" => $thunmbnail_src,
                    "created_time" => date("Y-m-d H:i:s", time()),
                    "updated_time"  => date("Y-m-d H:i:s", time())
                );
                $goal_2 = $user->add($user_content);
            }
            if($goal){
                $info = array(
                    'state' => 200,
                    'status' => 200,
                    'info'  => 'success',
                );
            }else{
                $info = array(
                    'state' => 404,
                    'status' => 404,
                    'info'  => 'failed',
                    'data'  => array(),
                );
            }
        }
        echo json_encode($info,true);
    }
    /**
     * 将上传的图片进行压缩处理
     * @param  array $files 由 upload类返回的数组
     * @param  bool  $is_detaild 是否详细信息
     * @return bool|array       返回信息集
     */
    protected function processPhoto($files, $is_detaild = false)
    {
        $site = $_SERVER["SERVER_NAME"];
        $folder_name = explode('/',$_SERVER["SCRIPT_NAME"]);
        $thumbnail = new \Think\Image();
        
        $info = array();
        foreach ($files as $key => $file) {
            
            if ($file['key'] != $this->key)
                continue;
            //原图地址
            $photosrc = "http://".$site.'/'.$folder_name[1]."/Public/photo/".$file['savename'];
            //缩略图地址
            $thunmbnail_src =  "http://".$site.'/'.$folder_name[1].'/Public/photo/thumbnail/'.$file['savename'];
            $content = array(
                "date"     => date("Y-m-d H:i:s", time()),
                "photosrc" => $photosrc,
                "thumbnail_src" => $thunmbnail_src,
                'state'    => 1,
            );
    
            if ($is_detaild) {
                $content = array_merge(array(
                    'name' => $file['name'],
                    'size' => $file['size'],
                    'type' => $file['type'],
                ), $content);
            }
            $thumbnail->open('./Public/photo/'.$file['savename']);
            $thumbnail->thumb(150, 150)->save('./Public/photo/thumbnail/'.$file['savename']);
           
            $info[$key] = $content;
        }
        
        if (empty($info))
            return false;
        
        return $info;
    }


    /**
     * 记录上传图片
     * @param  string $stunum 学号
     * @param  array  $files  文件
     * @return boolean        是否记录成功
     */
    protected function consoleUpload($stunum, $files)
    {
        if (empty($stunum) || empty($files)) {
            return false;
        }
        
        $photo = M('articlephoto');
        
        foreach ($files as $key => $value) {
            $value['stunum'] = $stunum;
            $result = $photo->add($value);
            if (!$result) {
                return false;
            }
        }
        return true;
    }

    public function deleteFile($filename = '')
    {
        if (empty($filename)) {
            $filename = I('fold');
            if (empty($file)) {
                returnJson(404, 'error', array('error' => '没找到该文件'));
            } 
        }

        $filepath = $this->rootPath.$filename;
        $thumbnailPath = $this->rootPath.'thumbnail/'.$filename;
        $success = is_file($filepath) && $filename[0] !== '.' && unlink($filepath) && unlink($thumbnailPath);
        if ($success) {
            returnJson(200);
        } else {
            returnJson(404);
        }
    }

}

