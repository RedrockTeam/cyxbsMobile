<?php
/**
 * Created by PhpStorm.
 * User: Haku
 * Date: 12/8/15
 * Time: 10:54
 */

namespace Home\Controller;

use Home\Common\Api\ApiCurlController;
use Home\Common\ExcelGeneratorController;
use Home\Common\UrlGeneratorController;
use Home\Common\MobileDetectController;
use Redis;
use Think\Controller;
use Think\Exception;

/**
 * Class CourseController
 * @package Home\Controller
 */
class CourseController extends Controller
{

    private $url = "http://hongyan.cqupt.edu.cn/redapi2/api/kebiao";

    private $curlController = null;

    private $passKey = "redrock-team";

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
     * 初始化api连接管理器
     *
     * @return void
     * */
    private function _curl_init()
    {
        $this->curlController = apiCurlController::init();
    }

    /**
     * 显示无课表网页信息
     * */
    public function index()
    {

        $table_id = $_GET['table'];

        if (!isset($_GET['table']) || strlen($table_id) != 13) {
            return exit($this->display('Empty/index'));
        }
        if (preg_match('/(\w{7})$/', $table_id) == 0) {
            return exit($this->display('Empty/index'));
        }

        C('TMPL_L_DELIM', '<{');
        C('TMPL_R_DELIM', '}>');

        /*<{{*/
        $jsonData = unserialize(strip_tags(trim($this->getJSON($table_id))));
        /*}}>*/

        $detect = new MobileDetectController;

        if (!$detect->match('micromessenger') && !$detect->isMobile()) {
            $this->assign(array('jsonData' => $jsonData));
            $this->display('FreeTable/index');
        } else {
            return $this->download(json_decode($jsonData));
        }
    }

    /**
     * 计算出对应的table_id并获取到对应的JSON
     *
     * @param string $table_id
     *
     * @return string|void
     */
    function getJSON($table_id)
    {

        $generator = $this->_url_init();
        $redis = $this->_redis_init();

        $idx = $generator->generator($table_id, $this->passKey);

        $json_serialize = $redis->get('mky_tables:key:' . $idx);

        if (false === $json_serialize) return exit($this->display('Empty/index'));

        return $json_serialize;
    }

    /**
     * 初始化URL Generator
     *
     * @return UrlGeneratorController
     * */
    private function _url_init()
    {
        return new UrlGeneratorController();
    }

    /**
     * 初始化Redis连接
     *
     * @return \Redis
     * @throws Exception
     */
    private function _redis_init()
    {
        $redis = new Redis();
        $connected = $redis->connect(C('REDIS_HOST'), C('REDIS_PORT'), C('REDIS_TIMEOUT'));

        if (!$connected) throw new Exception;

        return $redis;
    }

    public function wukebiao()
    {
        if ($this->isPost()) {
            // 解析POST参数
            $post =  file_get_contents('php://input', 'r') ?: I('post.');

            if (is_string($post)) $post = (array) @json_decode($post, true) ?: array();

            // 增加api接口
            if (isset($_GET['w_ak']) && I('get.w_ak') == C('WKY_API_KEY')) {

                /**
                 * 形如 ['stuNum' => [], 'week' => '']
                 * */
                if (array_key_exists('stuNum', $post)) {
                    $stuNums = $this->_parse($post['stuNum']);

                    if (array_key_exists('week', $post)) {
                        $week = $post['week'];
                    }
                }

                header('Content-Type:application/json; charset=utf-8');
                echo json_encode((object) $this->compareTable($stuNums, $week));
            }

            $detect = new MobileDetectController;

            if ($detect->match('micromessenger') || $detect->isMobile()) {
                $generator = $this->_url_init(); $redis = $this->_redis_init();

                $idx = $redis->incr('mky_tables:count');

                $shortcode = $generator->generator($idx, $this->passKey);

                $redis->setex('mky_tables:key:' . $idx, 604800, serialize($post));

                echo '/index.php/Home/get/' . $shortcode;
            }

        }

    }

    /***/

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

    protected function download($json)
    {
        $excel = new ExcelGeneratorController();

        /** 读取默认模板文件,并作为修改的底稿 */
        $excel->excel_generator = $excel->reader('Excel2007', 'ExcelTemplate/template_freetable.xlsx');

        /** 设置Excel的基础属性 */
        $excel->property(function (\PHPExcel_DocumentProperties $property) {
            $property->setCreator('Redrock Team');
            $property->setLastModifiedBy('Redrock Team');
            $property->setTitle('FreeTable');
            $property->setSubject('');
            $property->setDescription('This timetable is automatically generated by the server according to the relevant student information.');
            $property->setKeywords('');
            $property->setCategory('');
        });

        // 设置当前工作簿
        $excel->excel_generator->setActiveSheetIndex(0);

        /** 设置表格的具体内容 */
        $excel->sheet(function (\PHPExcel_Worksheet $sheet) use ($json) {

            /** 替换标题中XXXX年份为当前年份 */
            $sheet->setCellValue('A1', preg_replace('/XXXX/', date('Y', time()), $sheet->getCell('A1')->getValue()));

            /** 设置发布日期 */
            $sheet->setCellValue('A3', date('Y/m/d', time()));

            $rec_it = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($json));
            // 星期数映射至表格的列数
            $week_to_cell = array('B', 'C', 'D', 'E', 'F', 'G', 'H'); $week_flag = 0;

            $cell = ''; $names = ''; $max_height = 0; $max_weight = 0; $temp_height = 0;

            foreach ($rec_it as $key => $item) {

                if (is_int($item)) {

                    /**
                     * 如果发现 $cell 变量不为空,则实际上周数等已经
                     * 转换为了表格对应的行列指标,此时就应该设置对应
                     * 的表格内容,并重置相应状态.
                     */
                    if (!empty($cell) && !empty($names)) {

                        $sheet->setCellValue($cell, rtrim($names));

                        // 计算当前表格中最长最高的字符串
                        $max_height = $max_height < $temp_height ? $temp_height : $max_height;

                        $cell = $names = ''; $week_flag = 0;
                    }

                    /** 将0/1 这种数字转换为字符串 A1 */
                    if (!$week_flag) {
                        $cell .= $week_to_cell[$item]; $week_flag = 1;
                    } else $cell .= ($item + 5);

                    continue;

                } else if (is_string($item)) {

                    $str = preg_replace('/\n/', '', $item);
                    // 计算当前字符串的最大长度
                    $str_len = strlen($str); $max_weight = $max_weight < $str_len ? $str_len : $max_weight;

                    $names .= $str . PHP_EOL;

                    // 当前遍历姓名的次数
                    $temp_height++;

                }

            }

            $sheet->setTitle('FreeTable');

            // 使当前课表单元表格的宽高度自适应
            for($i = 0; $i < 7; $i++) {
                $sheet->getColumnDimension($week_to_cell[$i])->setWidth($max_weight * 0.46);
            }
            for($i = 5; $i <= 10; $i++) {
                $sheet->getRowDimension($i)->setRowHeight($max_height * 0.66);
            }
        });

        $this->getXSLSHeader();

        return $excel->writer($excel->excel_generator, 'Excel2007')->save('php://output');
    }

    function getXSLHeader()
    {
        // Redirect output to a client’s web browser (Excel5)
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="freetable.xls"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0
    }

    function getXSLSHeader()
    {
        // Redirect output to a client’s web browser (Excel2007)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="freetable.xlsx"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
    }

    /**
     * 课表参数解析
     *
     * @param $param
     *
     * @return array
     */
    private function _parse($param)
    {
        /**
         * @example "张三,李四,王麻子"
         * @return  ["张三", "李四", "王麻子"]
         * */
        if (strpos($param, ',') > 0)
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
     * @param array  $stuGroup 学号
     * @param string $week
     *
     * @return mixed
     */
    private function compareTable($stuGroup = array(), $week = '0')
    {
        if (!is_array($stuGroup) && is_string($stuGroup)) {
            return $this->getTable($stuGroup, $week);
        } else if (is_array($stuGroup)) {

            // 待对比的课表集合
            $tables = array();
            $tempTable = null;

            // 遍历学号组成的集合
            foreach ($stuGroup as $stu) {
                // 获得过滤后的课表数据,并放入待对比的课表集合中
                $tempTable = $this->getTable($stu, $week);

                foreach ($tempTable as $class) {
                    $clz = $this->processClass($class);
                    // 先获取当前天数
                    $day = $clz['hash_day'] + 1;
                    // 获取当前这节课是第几课
                    $lesson = $clz['hash_lesson'] + 1;
                    // 如果当日当前的课程区域未初始化
                    if (!array_key_exists($day, $tables))
                        $tables[$day] = array();
                    if (!array_key_exists($lesson, $tables[$day]))
                        $tables[$day][$lesson] = array('stuNums' => array());
                    // 放置学生数据
                    array_push($tables[$day][$lesson]['stuNums'], $stu);
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
     * @param string $stu  学号
     * @param string $week 周数
     *
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
     *
     * @return array $clear 过滤后的课表数据
     */
    function processClass($table)
    {
        $whitelist = array('hash_day', 'hash_lesson');
        $clear = array();

        foreach ($whitelist as $value)
            $clear[$value] = $table[$value];

        return $clear;
    }
}