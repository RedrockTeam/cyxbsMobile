<?php
/**
 * Created by PhpStorm.
 * User: Haku
 * Date: 12/8/15
 * Time: 11:07
 */

namespace Home\Common;

use Think\Controller;
use Closure;

Abstract class CurlController
{
    // 当前对象的单例实例化对象
    public static $clz = null;

    protected static $_curl = null;
    protected $_handler = null;

    protected $_options = array();

    private function __construct()
    {
        // 初始化curl为私有变量
        self::$_curl = curl_init();

        // 基础选项
        $this->_options['timeout'] = 10;
        $this->_options['temp'] = sys_get_temp_dir();
        $this->_options['user_agent'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2503.0 Safari/537.36';
    }

    private function __clone() {
        trigger_error("Clone is not permit", E_USER_ERROR);
    }

    /*
     * 设置curl对象
     * @param mixed $option 可以为数组和字符串
     * @param string(optional) $val 当$option为字符串时,可以为单个选项提供设置参数
     * */
    protected function setOpt($option, $val = null)
    {
        if (is_array($option)) {
            if (function_exists('curl_setopt_array')) {
                curl_setopt_array(self::$_curl, $option);
            }
        } else if (!is_null($val)) {
            curl_setopt(self::$_curl, $option, $val);
        }

        return $this;
    }

    protected function handler($url, $referer = '')
    {
        if (!is_array($url)) {
            $this->_handler = array(
                'url' => $url,
                'referer' => $referer
            );
        }

        return $this;
    }

    /**
     * 检测是否初始化curl连接
     *
     * @param Closure $check_func
     * @param $param 检测回调函数的参数
     * @return bool
     */
    protected function check(Closure $check_func, $param) {
        if (self::$_curl == null) return $check_func($param);

        return true;
    }

    /**
     * 得到对应CURL控制器的实例化对象
     * 如果已存在,则直接返回该对象
     *
     * @return CurlController 继承CurlController的对象
     */
    final public static function init() {
        return !(self::$clz instanceof static) ? self::$clz = new static : self::$clz;
    }

    /**
     * 打开一个curl连接
     *
     * @return $this 可以使用链式调用
     */
    abstract protected function open();

    /**
     * curl通过POST方法连接
     *
     * @param $query 查询条件
     * @return $this
     */
    abstract protected function post($query);

    /**
     * curl通过GET方法连接
     *
     * @param $query 查询条件
     * @return $this
     */
    abstract protected function get($query);

    /**
     * 执行一次curl连接,如果执行成功则返回结果
     * 失败则返回FALSE
     *
     * @param $url URL连接
     * @param $query 需要查询的数据
     * @param string $queryMode 查询方法 POST / GET
     * @param bool $apiMode
     * @param string $referrer 回调地址
     * @return mixed 结果集 / FALSE
     * @internal param bool $apiMode API模式下不需要返回HEADER
     */
    abstract public function request($url, $query, $queryMode = 'POST', $apiMode = true, $referrer = '');

    /**
     * 得到非JSON格式的消息头
     *
     * @param resource $curl
     * @param $res HTTP响应体
     * @return string $header HTTP响应消息头
     */
    abstract public function header($curl, $res);

    /**
     * 得到非JSON格式的HTTP主体内容
     *
     * @param resource $curl
     * @return string $body HTTP响应主体
     */
    abstract public function body($curl);
}

