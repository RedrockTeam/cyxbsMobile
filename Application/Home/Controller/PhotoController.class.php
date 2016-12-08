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
            returnJson(404, 'failed', array('state'=> 404, 'data'=>array()));
        }
        
        if (!$info = $this->pictrueUpload()) {
            returnJson(404, 'failed', array('state'=> 404, 'data'=>array()));
        }
       
        $content = array_pop($info);
        $content['stunum'] = $stunum;                                 
        returnJson(200, '', array('state'=> 200, 'data'=>$content));
    }
    //多文件上传
    public function multipleUploadArticle()
    {
        if (!$stunum = $this->getUploader()) {
            returnJson(404, 'failed', array('state'=> 404, 'data'=>array()));
        }
        
        if (!$info = $this->pictrueUpload(array(), $error, true)) {
            returnJson(404, 'failed', array('error'=> $error));
        }

        $trans = array(
            'photosrc' => 'url',
            "thumbnail_src" => 'thumbnailUrl',        
        );
        //更改信息
        foreach ($info as &$file) {
            foreach ($file as $key => $value) {
                if (isset($trans[$key])) {
                    $file[$trans[$key]] = $value;
                    unset($file[$key]);
                }
            }
            $file['deleteType'] = "GET";
            $file['deleteUrl'] = $this->getDeleteUrl($file['savename']);
            unset($file['savename']);
        }
        echo json_encode(array('files'=>$info));exit;

    }

    public function pictrueUpload($files=array(), &$error='', $is_detaild = false)
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
           $info = $this->processPhoto($files, $is_detaild);
           $stunum = $this->getUploader();
           $result = $this->consoleUpload($stunum, $info);
        }
        return $result ? $info : false;
    }

    protected function getDeleteUrl($filename)
    {
        $site = $_SERVER["SERVER_NAME"];
        $folder_name = explode('/',$_SERVER["SCRIPT_NAME"]);
        $deleteUrl =  "http://".$site.'/'.$folder_name[1].'/index.php/Home/Photo/deleteFile?fold='.$filename;
        return $deleteUrl;
    }
    public function upload(){
        header("Content-type: application/json");
        $photo = M('photo');
        if(I('post.stunum') != null){
            $stunum = I('post.stunum');
        
        } elseif(session('admin.stunum')) {
           
            $result = M('admin')->where('stunum=\'%s\'', session('admin.stunum'))->find();
           
            if (!$result)
                returnJson(404, 'failed', array('state'=> 404, 'data'=>array()));
    
            $stunum = session('admin.stunum');
        } else {
            returnJson(404, 'failed', array('state'=> 404, 'data'=>array()));
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
                    'savename' => $file['savename'],
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
        
        foreach ($files as $key => $file) {
            $file['stunum'] = $stunum;
            $result = $photo->add($file);
            if (!$result) {
                return false;
            }
        }
        return true;
    }

    public function deleteFile($filename='')
    {
        if (empty($filename)) {
            $filename = I('fold');
            if (empty($filename)) {
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

    //上传
    public function uploadPicture()
    {
        
        if (!$this->verifyRole()) {
            returnJson(403);
        }
        $info = I('post.');
        $start = timeFormate($info['start']);

        $created_time = timeFormate();
        
        $stunum = empty($info['stuNum']) ? session('admin.stunum') : $info['stuNum'];
        $photo_src = $info['photo_src'];
        //显示区域
        $column = $info['column'];
        if (empty($column) || empty($photo_src)) {
            returnJson(404);
        }
        $target_url = $info['target_url'];
        $user = M('users')->where('stunum=\'%s\'', $stunum)->find();

        if (empty($user)) {
            returnJson(404);
        }
        $user_id = $user['id'];
        $annotation = $info['annotation'];
        $data = compact('start', 'created_time', 'stunum', 'photo_src', 'column', 'user_id', 'target_url', 'annotation');
        $result = M('displaypicture')->add($data);
        if ($result) {
            returnJson(200);
        } else {
            returnJson(404);
        }
    }

    //显示
    public function showPicture()
    {
        $column = I('column');
        
        $display = S('displayPicture'); 
        if (!empty($column)) {
            $display = $display[$column];
        } 
        if (false ===$cache || empty($display)) {
            $current_time = timeFormate();
            $pos = array(
                'state' => 1,
                'start' => array('ELT', $current_time)
            );
            $field = array('target_url', 'photo_src', 'max(`start`)'=>'start', 'column', 'id', 'annotation');
            $data = M('displaypicture')->where($pos)->field($field)->group('`column`')->select();
            if (!$data) {
                returnJson(404);
            }
            $display = array();
            foreach ($data as $key => $picture) {
                $display[$picture['column']] = array(
                    'target_url' => $picture['target_url'], 
                    'photo_src'=>$picture['photo_src'], 
                    'start'=>$picture['start'], 
                    'id'=>$picture['id'],
                    'annotation' => $picture['annotation'],
                    );
            }
            S('displayColumn', $display, 60*60*24);
            if (!empty($column)) {
                $display = $display[$column];
            } 
        }
    
        if (empty($display)) {
            returnJson(404, '错误关键词');
        }
        returnJson(200, '', array('data' => $display));
    }

    //重置缓存
    public function refresh()
    {
        if (!$this->verifyRole()) {
            returnJson(403);
        }
        S('displayPicture', null);
        returnJson(200);
    }

    protected function verifyRole()
    {
        $stuNum = I('post.stuNum');
        $baseConfirm = new BaseController;
        return is_admin($stuNum);
    }
    /**
     * 上传记录
     */
    public function uploadPictureList()
    {
        if (!$this->verifyRole()) {
            returnJson(403);
        }

        $info = I('post.');
        
        $page = empty($info['page']) ? 0 : $info['page'];
        $size = empty($info['size']) ? 10: $info['size'];
        $pos = array('displaypicture.state'=> 1);
        
        if (!empty($info['column'])) {
            $pos['column'] = $info['column'];
        }

        if (!empty($info['uploadStuNum'])) {
            $user = M('users')->where('stunum=\'%s\'', $info['uploadStuNum'])->find();
            if (!$user) {
                returnJson(404, 'error stunum');
            }
            $pos['user_id'] = $user['id'];
        }

        $field = array(
                'displaypicture.id' => 'id', 
                'column',
                'stunum' => 'uploaderStunum',
                'username' => 'uploaderName',
                "displaypicture.photo_src",
                'displaypicture.`start`',
                'target_url',
                'displaypicture.created_time',
                'annotation',
                );
        //查询
        $data = M('displaypicture')
                    ->alias('displaypicture')
                    ->join('join __USERS__ ON __USERS__.id = displaypicture.user_id')
                    ->where($pos)
                    ->field($field)
                    ->order('displaypicture.created_time desc')
                    ->limit($page*$size, $size)
                    ->select();
        returnJson(200, '', array('data'=> $data));
    }

    //修改
    protected function editDb($object, $change, $primarykeyName = 'id')
    {
        if (empty($object))
            return false;
        
        elseif (is_array($object)) {    
            if (!in_array($primarykeyName, $object)) {
                return false;
            }
            $pk = $object[$primarykeyName];
        } else {
            $pk = $object;
        }
        $tableColumns = M('displaypicture')->getDbFields();
        foreach ($change as $key => $value) {
            if (!in_array($key, $tableColumns)) {
                return false;
            } 
        }
        $change[$primarykeyName] = $pk;
        $change['created_time'] =  timeFormate();
        $result = M('displaypicture')->save($change);
        return $result ? true : false;
    }

    public function delete()
    {
        if (!$this->verifyRole()) {
            returnJson(403);
        }
        $id = I('post.id');
        if ($this->editDb($id, array('state' => 0))) {
            returnJson(200);
        } else {
            returnJson(404);
        }
    }

    public function edit()
    {
        if (!$this->verifyRole()) {
            returnJson(403);
        }
        $info = I('post.');
        $stunum = empty($info['stuNum']) ? session('admin.stunum') : $info['stuNum'];
        $user = M('users')->where('stunum=\'%s\'', $stunum)->find();
        if (!$user) {
            returnJson(404, 'error stunum');
        }
        
        unset($info['stuNum']);
        unset($info['idNum']);
        unset($info['state']);
        $info['user_id'] = $user['id'];
        if ($this->editDb($info['id'], $info))
            returnJson(200);
        else
            returnJson(404);
    }

}

