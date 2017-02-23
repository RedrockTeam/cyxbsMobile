<?php

namespace Admin\Common;

class JWT
{
        private $key = 'redrock';

        /**
         * 生成头部
         * @return string base64后的字符串
         */
        protected function enheader()
        {
                //头部
                $head = array('type' => 'jwt', 'alg' => "sha256");
                $json = json_encode($head);
                return base64_encode($json);
        }

        /**
         * 生成负载
         * @param  mix  $data 需要携带的数据
         * @param  integer $exp  消息存活的时间,单位秒
         * @return string        base64后的字符串
         */
        protected function enbody($data, $exp)
        {
                //当地时间
                $localtime = time();
                //负载
                $body = array(
                        'iat' => $localtime,
                        'exp' => $localtime+$exp,
                        'data'=> $data
                );
                $json = json_encode($body);
                return base64_encode($json);
        }

        /**
         * 生成签名
         * @param  string $header base64后的头部
         * @param  string $body   base64后的负载
         * @return string         签名
         */
        protected function entoken($header, $body)
        {
                $token = $header.'.'.$body;

                $token = $this->cycript($token, $this->key);

                return $token;
        }

        /**
         * 签名使用的加密函数
         * @param  string $message 加密的信息
         * @param  string $salt    密钥
         * @return string          序列化后的散列值
         */
        private function cycript($message, $salt)
        {
                return base64_encode(hash_hmac('sha256', $message, $salt, true));
        }

        /**
         * 判断头部是否正确
         * @param  string $header base64后的头部
         * @return bool         是否合法
         */
        protected function judgeHeader($header)
        {
                $header = json_decode(base64_decode($header), true);
                //非法头部
                if (!$header) {
                        return false;
                }

                //类型错误
                if (strtolower($header['type']) != 'jwt') {
                        return false;
                }

                return true;
        }

        /**
         * 判断负载是否合法
         * @param  string $body base64的负载
         * @return mix       负载携带的数据
         */
        protected function judgeBody($body)
        {
                $body = json_decode(base64_decode($body), true);
                $localtime = time();
                //非法负载
                if (!$body) {
                        return false;
                }

                //时间不合法，或者以过过期时间
                if ($localtime < $body['iat'] || $localtime > $body['exp']) {
                        return false;
                }

                return $body['data'];
        }

        /**
         * 生成jwt
         * @param  mix  $data jwt需携带的数据
         * @param  integer $exp  存活时间，单位s
         * @return string        jwt编码
         */
        public function encode($data, $exp=5)
        {
                $header = $this->enheader();
                $body   = $this->enbody($data, $exp);
                $token  = $this->entoken($header, $body);

                return $header.'.'.$body.'.'.$token;
        }

        /**
         * 对jwt进行解码
         * @param  string $jwt jwt的字符串编码
         * @return mix      jwt携带的数据
         */
        public function decode($jwt)
        {
                list($header, $body, $token) = explode('.', $jwt);

                if (empty($header) || empty($body) || empty($token)) {
                        return false;
                }

                if ($token !== $this->entoken($header, $body)) {
                        return false;
                }


                $data = $this->judgeBody($body);

                if ($this->judgeHeader($header) && $data !== false) {
                        return $data;
                }
                return false;
        }
}
