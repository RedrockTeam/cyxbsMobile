<?php
/**
 * Created by PhpStorm.
 * User: Haku
 * Date: 12/8/15
 * Time: 10:54
 */

namespace Home\Controller;

use Home\Common\apiCurlController;
use Think\Controller;

/**
 * Class CourseController
 * @package Home\Controller
 */
class CourseController extends Controller
{

    private $url = "http://hongyan.cqupt.edu.cn/redapi2/api/kebiao";
    private $curlController = null;

    /**
     * CourseController constructor.
     */
    public function __construct()
    {
        // 调用父类控制器
        parent::__construct();
        // 初始化curl管理器
        $this->_curl_init();
    }

    /**
     * 显示无课表网页信息
     * */
    public function index() {
        C('TMPL_L_DELIM', '<{'); C('TMPL_R_DELIM', '}>');

        /*<{{*/
        $jsonData = strip_tags(trim($GLOBALS['HTTP_RAW_POST_DATA']));
        /*}}>*/

        $this->assign(array('jsonData' => $jsonData));
        $this->display('FreeTable/index');
    }

    /**
     * 课表参数解析
     * @param $param
     * @return array
     */
    private function _parse($param)
    {
        /**
         * @example "张三,李四,王麻子"
         * @return ["张三", "李四", "王麻子"]
         * */
        if (strpos($param, ',') !== false)
            return explode(',', $param);
        /**
         * @example ["王二", "李狗蛋", "张大山"]
         * @return
         * */
        else if (is_array($param))
            return $param;

        /**
         * 否则将单个参数返回
         * */
        return $param;
    }

    /**
     * 初始化api连接管理器
     * */
    private function _curl_init()
    {
        $this->curlController = apiCurlController::init();
    }

    public function wukebiao()
    {
        if ($this->isPost()) {
            // 解析POST参数
            $post = I('post.');

            /**
             * 形如 ['stuNum' => [], 'week' => '']
             * */
            if (array_key_exists('stuNum', $post)) {
                $stuNums = $this->_parse($post['stuNum']);

                if (array_key_exists('week', $post)) {
                    $week = $post['week'];
                }
            } else {
                die();
            }

            header('Content-Type:application/json; charset=utf-8');
            echo json_encode((object) $this->compareTable($stuNums, $week));
        }

    }

    /**
     * @param array $stuGroup 学号
     * @param string $week
     * @return mixed
     */
    private function compareTable($stuGroup = array(), $week = '0') {
        if (!is_array($stuGroup) && is_string($stuGroup)) {
            return $this->getTable($stuGroup, $week);
        } else if (is_array($stuGroup)) {

            // 待对比的课表集合
            $tables = array(); $tempTable = null;

            // 遍历学号组成的集合
            foreach ($stuGroup as $stu) {
                // 获得过滤后的课表数据,并放入待对比的课表集合中
                $tempTable = $this->getTable($stu, $week);
                $_lesson = -1; $_day = -1;
                foreach ($tempTable as $class) {
                    $clz = $this->processClass($class);
                    // 先获取当前天数
                    $day = $clz['hash_day'];
                    // 获取当前这节课是第几课
                    $lesson = $clz['hash_lesson'];
                    // 如果已经存在当前有课的同学
                    if (array_key_exists($day, $tables)) {
                        if (array_key_exists($lesson, $tables[$day])) {
                            $_lesson = $lesson; $_day = $day;
                            $tables[$day][$lesson]['names'][] = $stu;
                            continue;
                        }
                        $clz['names'][] = $stu;
                        $tables[$day][$lesson] = $clz;
                    } else {
                        $tables[++$_day][++$_lesson] = array();
                    }

                }

                // 销毁临时数据
                unset($tempTable);
            }

            return $tables;
        }

        return null;
    }

    /**
     * 得到某学生的当前课表,可以选择周数
     *
     * @param string $stu 学号
     * @param string $week 周数
     * @return array
     * */
    function getTable($stu, $week = '0')
    {
        if (empty($stu)) $this->ajaxReturn(array(
            'success' => 'false',
            'info' => 'stuNum not allowed',
        ));

        // 请求curl连接获得课表数据
        $res = $this->curlController->request($this->url, array(
            'stuNum' => $stu, 'week' => $week
        ))->response();

        /* 5.3.3 兼容写法 */
        $json_to_arr = json_decode($res, true);

        return $json_to_arr['data'];
    }

    /**
     * 获取课表数据的特定内容
     *
     * @param $table 课表数据
     * @return array $clear 过滤后的课表数据
     */
    function processClass($table) {
        $whitelist = array('hash_day', 'hash_lesson');

        $clear = array_filter($table, function($k) use ($whitelist) {
            return in_array($k, $whitelist);
        }, ARRAY_FILTER_USE_KEY);

        return $clear;
    }

    /**
     * 对于非POST请求的访问直接拒绝
     *
     * @return null|bool
     * */
    function isPost()
    {
        if (!IS_POST) $this->ajaxReturn(array(
            'success' => 'false',
            'info' => 'Wrong dispatch method',
        ));

        return true;
    }
}