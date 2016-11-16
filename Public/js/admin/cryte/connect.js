var connection = function(){

        //秘钥测试用
        var key = "redrock";

        /**
         * JWT的头部
         * @param  {String} alg 签名加密算法名
         * @return {String}     [description]
         */
        var enheader = function() {
                var head = {
                        type:'jwt',
                        alg: 'sha256'
                };
                return Base64.encode(JSON.stringify(head));
        };

        /**
         * jwt的负载 存放主要信息
         * @param  {mix} data 需要传的信息
         * @param  {Number} exp  请求存活的时间，单位s
         * @return {string}      加密的信息
         */
        var enbody  = function(data, exp) {
                var local = parseInt(new Date().getTime()/1000);
                var body = {
                        iat: local,
                        exp: local+exp,
                        data: data,
                };
                return Base64.encode(JSON.stringify(body));
        };

        //对信息进行签名
        var entoken = function(header, body) {
                var ensign = header + '.' + body;
                return CryptoJS.enc.Base64.stringify(CryptoJS.HmacSHA256(ensign, key));
        };

        var judgeHeader = function(header) {

                header = JSON.parse(Base64.decode(header));
                if (header === '') {
                        return false;
                }

                if (header.type.toLowerCase() !== 'jwt') {
                        return false;
                }
                return true;

        }

        var judgeBody = function(body) {
                //解码
                body = JSON.parse(Base64.decode(body));

                var localtime = new Date().getTime();

                //解码错误，返回false
                if (body === '') {
                        return false;
                }

                //时间错误
                if (body.iat*1000 > localtime) {
                        return false;
                }

                //超过过期时间
                if (body.exp*1000 < localtime) {
                        return false;
                }

                //没问题的返回data值
                return body.data;
        }
        return {
                encode : function(data) {
                        //data = Base64.encode(JSON.stringify(data));
                        return data;
                },

                JWTEncode : function(data, exp=5) {
                        var header = enheader();
                        var body   = enbody(data, exp);
                        var token   = entoken(header, body);
                        return header + '.' + body + '.' + token;
                },

                decode : function(data) {
                        return JSON.parse(Base64.decode(data));
                },

                JWTDecode : function(jwt) {
                        [header, body, token] = jwt.split('.');

                        if (!header  || !body || !token) {
                                return false;
                        }

                        //判断是否被串改
                        if (token !== entoken(header, body)) {
                                return false;
                        }
                        var data = judgeBody(body);

                        if (judgeHeader(header) && data) {
                                return data;
                        }
                        return false;

                }

        }


}();
