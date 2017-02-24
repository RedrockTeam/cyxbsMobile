<<<<<<< HEAD
<?php
/**
 * Created by PhpStorm.
 * User: Haku
 * Date: 12/8/15
 * Time: 17:05
 */

namespace Home\Common\Api;

class ApiCurlController extends CurlController
{

    /**
     * 打开一个curl连接
     *
     * @return $this 可以使用链式调用
     */
    protected function open()
    {
        $this->setOpt(array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => $this->_options['user_agent'],
            CURLOPT_CONNECTTIMEOUT => $this->_options['timeout'],
            CURLOPT_TIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => array('Expect:', 'X-Requested-With: XMLHttpRequest')
        ));
    }

    /**
     * curl通过POST方法连接
     *
     * @param $query
     * @return $this
     */
    protected function post($query)
    {
        if(is_array($query)) {
            foreach($query as $key => $val) {
                if(urlencode($key) != $key) {
                    unset($query[$key]);
                }

                $query[urlencode($key)] = urlencode($val);
            }
        }

        $this->setOpt(array(
            CURLOPT_POST => true,
            CURLOPT_URL => $this->_handler['url'],
            CURLOPT_REFERER => $this->_handler['referer'],
            CURLOPT_POSTFIELDS => http_build_query($query)
        ));

        return $this;
    }

    /**
     * curl通过GET方法连接
     *
     * @param $query 查询条件
     * @return $this
     */
    protected function get($query)
    {
        $url = $this->_handler['url'];
        if(!empty($query)) {
            $url = $url . (strpos($url, '?') ? '&' : '?')
                . (is_array($query) ? http_build_query($query) : $query);
        }

        $this->setOpt(array(
            CURLOPT_URL => $url,
            CURLOPT_REFERER => $this->_handler['referer']
        ));

        return $this;
    }

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
    public function request($url, $query, $queryMode = 'POST', $apiMode = true, $referrer = '')
    {
        if (empty($url)) die('没有传入有效的URL地址');

        // 检测是否为api模式
        $this->setOpt(CURLOPT_HEADER, !$apiMode);
        // 开启一个curl连接
        if ($this->check(function() {
            die('没有开启CURL连接');
        })) {
            $this->open();
        }

        $this->handler($url, $referrer);

        if ($queryMode != '' && ($queryMode = strtolower($queryMode))) $this->{$queryMode}($query);

        return $this;
    }

    /**
     * 执行一次curl连接
     *
     * @return mixed 结果集 / FALSE
     */
    public function response()
    {
        $response = curl_exec(self::$_curl);

        $errorCode = curl_errno(self::$_curl);
        // php 5.5
        $errorMsg = version_compare(PHP_VERSION, '5.5.0') >= 0 ? curl_strerror($errorCode) : 'unknown error';

        if($errorCode) {
            die($errorCode . ' : ' . $errorMsg);
        }

        return $response;
    }


    /**
     * 得到非JSON格式的消息头
     *
     * @param resource $curl
     * @param $res HTTP响应体
     * @return string $header HTTP响应消息头
     */
    public function header($curl, $res)
    {
        // TODO: Implement header() method.
    }

    /**
     * 得到非JSON格式的HTTP主体内容
     *
     * @param resource $curl
     * @return string $body HTTP响应主体
     */
    public function body($curl)
    {
        // TODO: Implement body() method.
    }
>>>>>>> aa2fd0570fb2db681aba5763212882534dbe7ee9
}