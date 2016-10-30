<?php
namespace Home\Controller;
use Think\Controller;
class SearchPeopleController extends Controller {
    //api网址
    protected $url = 'http://jwzx.cqupt.edu.cn/jwzxtmp/data/json_studentList.php';
    

    public function index(){
        $studentNum = I('stunum');
        if(strlen($studentNum)==10){
            $data = $this->exec($this->url ,$studentNum);
            if (empty($data)) {
                $this->returnJson(404, '', array(), JSON_FORCE_OBJECT);
            } else {
                $this->returnJson(200, '', $data[0], JSON_FORCE_OBJECT);
            }            
        }else{
           $this->returnJson(404, '', array(), JSON_FORCE_OBJECT);
        }
    }

    public function peopleList(){
        $studentNum = I('stu');
        $goal_mod = strlen($studentNum)%3;
        $studentList = array();
        if(strlen($studentNum) == 0){
           $this->returnJson(404, '', array(), true);
        }else if(!preg_match("/[^\\x80-\\xff]/",$studentNum) && strlen($studentNum) == 3){
            $this->returnJson(404, '', array(), true);
        }else if(is_numeric($studentNum) && strlen($studentNum) != 10){
            $this->returnJson(404, '', array(), true);
        }else{
            
            $data = $this->exec($this->url, $studentNum);
            if (empty($data)) {
               $this->returnJson(404, '', array(), true);
            }else{
                $this->returnJson(200, '', $data, true);
            }
        }
    }

    private function _patternGoal($pattern,$string){//匹配函数
        preg_match_all($pattern,$string,$goalarray);
        return $goalarray;
    }

    private function _curl_init($url, $data=array()){//初始化目标网站
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
 
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt ($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        $output = curl_exec($ch);
        return $output;
    }

    public function _empty() {
        $this->display('Empty/index');
    }

    /**
     * 获取学生信息
     * @return array 返回想获取的信息
     */
    private function getInfo($url, $parameter=array(), $method='get')
    {
        $method = strtolower($method);
        if ($method=='get') {
            $url = $this->makeUrl($this->url, $parameter);
            $output = $this->_curl_init($url);
        } elseif ($method=='post') {
            $url = $this->url;
            $output = $this->_curl_init($url, $parameter);
        } else {
           return false;
        }
        $output = json_decode($output);
        $total = $output->total;
        $data = array();

        foreach ($output->rows as $student) {
            $value = array(
                'stunum'   => $student->xh,
                'name'     => $student->xm,
                'gender'   => $student->xb,
                'classnum' => $student->bj,
                'major'    => $student->zym,
                'depart'   => $student->yxm,
                'grade'    => $student->nj
                );
            array_push($data, $value);
        }
        if($total>$parameter['page']*$parameter['rows']) {
            $parameter['page'] += 1;
            $data  = array_merge($data, $this->getInfo($parameter));
        } 
        return $data;   
    }

    /**
     * 像url添加get参数
     * @param  string $url      
     * @param  array $parameter array('key'=>'value')
     * @return string            [description]
     */
    private function makeUrl($url, $parameter)
    {
        $url .= '?';
        foreach ($parameter as $key => $value) {
            $url = $url.$key.'='.$value.'&';
        }
        $url = rtrim($url, '&');
        return $url;
    }

    /**
     * 执行一次信息的搜素
     * @param  string $searchKey 搜索的内容
     * @return array            返回所有人的信息你
     */
    public function exec($url, $searchKey) 
    {
        $searchKey  = $searchKey;
        $page = 1;
        $rows = 30;
        $dirId = '';
        $info = compact('dirId','searchKey', 'page', 'rows');
        $data = $this->getInfo($url ,$info);
        return $data;
    }
    /**
     * 返回json的统一出口 
     * @param  int $status 状态码
     * @param  string $info   提示信息
     * @param  array $data   返回的具体数据
     * @param  string $json   en_json的参数,默认为数组
     */
    protected function returnJson($status, $info='', $data=array(), $json='')
    {
       // print_r(debug_backtrace());
         switch ($status) {
            case 404: 
                $report = array( 'state'=> 404,'status'=>404, 'info'=>'请求参数错误');
                break;
            case 403:
                $report = array('state'=> 403, 'status'=>403, 'info'=>'Don\'t permit');
                break;
            case 801:
                $report = array('state'=>801, 'status'=>801, 'info'=>'invalid parameter');
                break;
            case 200:
                $report = array('state'=>200, 'status'=>200, 'info'=>'success');
                break;
            default:
                $report = array('state'=>$status, 'status'=>$status, 'info'=>"");
        }

        if(!empty($info)) {
            $report['info'] = $info;
        }
        
        $report['data'] = $data;
        header('Content-type:application/json');
        if (empty($json)) {
            $json = json_encode($report);
        } else {
            $json = json_encode($report, $json);
        }
        echo $json;
        exit;

    }
}

