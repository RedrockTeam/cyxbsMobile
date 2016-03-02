<?php
namespace Home\Controller;
use Think\Controller;
class SearchPeopleController extends Controller {

    public function index(){
        $studentNum = I('stunum');
        $goal_people = $this->_curl_init("http://jwzx.cqupt.edu.cn/pubBjStu.php?searchKey=$studentNum");
        $goal_people = mb_convert_encoding($goal_people,'utf-8','gb2312');
        $info_pattern = "/<td>&nbsp;(.*?)<\/td>/";
        $goal_people = $this->_patternGoal($info_pattern,$goal_people);
        //var_dump($goal_people);
        $people_num = implode('',$goal_people[1]);
        $people_num = strlen($people_num);
        if($people_num!=0&&strlen($studentNum)==10){
            $need_people = array(
                    'state' => 200,
                    'info'  => 'success',
                    'data'  => array(
                        'stunum'   => $goal_people[1][0],
                        'name'     => $goal_people[1][1],
                        'gender'   => $goal_people[1][2],
                        'classnum' => $goal_people[1][3],
                        'major'    => $goal_people[1][4],
                        'depart'   => $goal_people[1][5],
                        'grade'    => $goal_people[1][6]
                        ),
                );
        }else{
            $need_people = array(
                'state' => 404,
                'info'  => 'failed',
                'data'  => array(),
            );

        }
        echo json_encode($need_people,JSON_FORCE_OBJECT);
        exit;
    }

    public function peopleList(){
        $studentNum = I('stu');
        $goal_mod = strlen($studentNum)%3;
        if(strlen($studentNum) == 0){
            $need_people = array(
                'state' => 404,
                'info'  => 'failed',
                'data'  => array(),
            );
        }else if(!eregi("[^\x80-\xff]",$studentNum) && strlen($studentNum) == 3){
            $need_people = array(
                'state' => 404,
                'info'  => 'failed',
                'data'  => array(),
            );
        }else if(is_numeric($studentNum) && strlen($studentNum) != 10){
            $need_people = array(
                'state' => 404,
                'info'  => 'failed',
                'data'  => array(),
            );
        }else{
            $studentNum = iconv( "UTF-8", "gb2312//IGNORE" , $studentNum);
            $goal_people = $this->_curl_init("http://jwzx.cqupt.edu.cn/pubBjStu.php?searchKey=$studentNum");
            $goal_people = mb_convert_encoding($goal_people,'utf-8','gb2312');
            $info_pattern = "/<td>&nbsp;(.*?)<\/td>/";
            $goal_people = $this->_patternGoal($info_pattern,$goal_people);
            $goal_emp = implode("", $goal_people[1]);
            if(empty($goal_emp)){
                $need_people = array(
                'state' => 404,
                'info'  => 'failed',
                'data'  => array(),
            );
            }else{
                $people_num = count($goal_people[1])/8;
                for($i = 0; $i < $people_num; $i++){
                    $num = $i*8;
                    $student_now = array(
                            'stunum'   => $goal_people[1][$num+0],
                            'name'     => $goal_people[1][$num+1],
                            'gender'   => $goal_people[1][$num+2],
                            'classnum' => $goal_people[1][$num+3],
                            'major'    => $goal_people[1][$num+4],
                            'depart'   => $goal_people[1][$num+5],
                            'grade'    => $goal_people[1][$num+6]
                            );
                    $studentList[] = $student_now;
                }
                $need_people = array(
                        'state' => 200,
                        'info'  => 'success',
                        'data'  => $studentList
                    );
            }
        }
        echo json_encode($need_people,JSON_FORCE_OBJECT);
        exit;
    }

    private function _patternGoal($pattern,$string){//匹配函数
        preg_match_all($pattern,$string,$goalarray);
        return $goalarray;
    }

    private function _curl_init($url){//初始化目标网站
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        $output = curl_exec($ch);
        return $output;
    }

    public function _empty() {
        $this->display('Empty/index');
    }
}

