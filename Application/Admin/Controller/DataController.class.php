<?php

namespace Admin\Controller;

use Think\Controller;

use Admin\Controller\ArticleController as Article;

class DataController extends Controller
{
        protected $map = array(
                'role' => '__ROLE__.display_name'
                );

        public function _initialize()
        {
                $info = session('admin');
                if (!isset($info)) {
                        header("HTTP/1.1 403 Forbidden");
                        returnJson(403);
                }
        }

        public function _empty()
        {
            returnJson(404);
        }

        /**
         * 返回不同模块的html 和 js内容
         */
        public function index()
        {
                $part = I('part');

                if (strpos($part, '..')) {
                        returnJson(403);

                }
                if ($part == 'AdminInfo') {
                        $info = array(
                                "columns" => array(
                                        array('data' => 'username',
                                                  'searchable' => false
                                                 ),
                                        array('data' => 'stunum',
                                                  'searchable' => false
                                                 ),
                                        array('data' => 'role',
                                                  'searchable' => false
                                                 ),
                                        array('data' => 'state',
                                                  'searchable' => false
                                                 ),
                                        array('data' => 'last_login_time',
                                                  'searchable' => false
                                                 ),
                                        array('data' => 'last_login_ip',
                                                  'searchable' => false
                                                 ),
                                ),
                                'search' => array('value' => ''),
                                'order' => array(array('column' => 0, 'dir' => "asc")),
                                'args' => array('id' => session('admin.id'))
                        );
                        $data = $this->adminInfo($info);
                        list($data) = $data;
                        $data['isedit'] = false;
                        $this->assign($data);
                }
                $output = $this->fetch('Index/'.$part);
                echo $output;
        }

        /**
         * 获取admin信息通过学号搜索
         * @return [type] [description]
         */
        public function user()
        {
                $data = array();
                //搜索的字段
                $searchField = I('post.field');
                //查询的值
                $value = I('post.value');

                //主查询需要的显示的字段
                $displayField = array(
                        'users.state', 'users.gender',
                        'users.id' =>'id',
                        'users.username' => 'username',
                        'users.stunum'  => 'stunum',
//                      'role', 'role_id', 'status,'
                        "IF(status, IFNULL(role, '用户'), '用户')" => 'role',
                        "IF(status, IFNULL(role_id, '-1'), '-1')"   => 'role_id'
                );
                //处理
                $displayField = $this->displayField($displayField, 'users');

                //搜索的字段非显示的返回false
                if($key = array_search($searchField, $displayField)) {
                        if (!is_numeric($key)) {
                                $searchField = $key;
                        }
                } else {
                        returnJson(404);
                }
                //长度不为10返回错误
                if (strlen($value) != 10) {
                        returnJson(801);

                }
                //子查询需要的字段
                $joinField = array(
                        'stunum', 'role_id',
                        'display_name' => 'role',
                        'state' => 'status'
                );
                //显示字段进行处理
                $joinField = $this->displayField($joinField, 'admin');
                //得到子查询的sql buildSql 返回 (sql)
                $joinSql = M('admin')
                                   ->join('__ROLE__ role ON __ADMIN__.role_id = role.id','left')
                                   ->field($joinField)
                                   ->buildSql();
                //查询的信息
                $parameter = array($searchField => $value);;
                $parameter = $this->parameter($parameter, 'users');

                $users = M('users')
                                ->alias('users')
                                ->join('LEFT JOIN'.$joinSql.' admin ON users.stunum = admin.stunum')
                                ->where($parameter)
                                ->field($displayField)
                                ->select();

                if (!$users) {
                        returnJson(404, '未找到该用户');

                }
                foreach ($users as $user) {
                        if ($user['state'] === 0) {
                                $user['role'] = "黑名单";
                        } else {
                                if ($user['admin.status'] === 0) {
                                        $user['role'] = "用户";
                                }
                        }
                        unset($user['state']);
                        $data[] = $user;
                }
                list($data) = $data;
                returnJson('200', 'success', $data);

        }

        /**
         * 管理员信息列表
         * @return json datatable 格式的
         */
        public function adminList()
        {
                $draw = I('post.draw');
                if(empty($draw)) {
                        returnJson(801);
                }
                $start = I('post.start');

                $length = I('post.length');

                if (!(is_numeric($start) && is_numeric($length))) {
                        returnJson(801);
                }

                //所有post的信息
                $information = I('post.');

                $data = $this->adminInfo($information);

                $recordsFiltered = count($data);

                if ($start > $recordsFiltered) {
                        $data = array();
                } else {
                        $length = ($recordsFiltered-$start)>$length ? $length : ($recordsFiltered-$start);
                        $data = array_slice($data, $start, $length);
                }

                $recordsTotal = M('admin')->count();
                $info = compact('data', 'recordsFiltered', 'recordsTotal', 'draw');
                returnJson('datatable', '', $info);
        }

        protected function adminInfo($information)
        {

                $table = 'admin';
                //$balckField = array('password', 'salt');
                $displayField = array(
                        '__ADMIN__.id' => 'id',
                        'username',
                        'stunum',
                        'display_name'    => 'role',
                        'last_login_time',
                        'last_login_ip',
                        'state'
                );

                $parameter = array();

                //额外条件
                if (!empty($information['args'])) {
                        foreach($information['args'] as $field => $value) {
                                $key = array_search($field, $displayField);
                                if ($key && !is_numeric($key)) {
                                        $field = $key;
                                }

                                $parameter[$field] = $value;
                        }
                }
                $searchValue = $information['search']['value'];
                //搜索的值需匹配的字段
                $searchField = array();

                $columns = $information['columns'];

                if (!empty($searchValue)) {

                        foreach ($columns as $column) {
                                //判断是否需要搜索的
                                if ($column['searchable'] === 'true') {
                                        $key = array_search($column['data'], $displayField);
                                        $searchField[] = (is_numeric($key) || !$key) ? $column['data'] : $key;
                                }
                        }

                        $parameter['*'] = array($searchValue, $searchField);
                }

                $parameter = $this->parameter($parameter, $table);

                $displayField = $this->displayField($displayField, $table);

                $order = array();
                $orders = $information['order'];
                foreach ($orders as $value) {
                        //排序需要的列
                        $field = $columns[$value['column']]['data'];
                        $order[$field] = $value['dir'];
                }

                //子查询需要的字段
                $loginListField  = array(
                        'admin_id',
                        'max(login_time)' => 'last_login_time',
                        'login_ip'        => 'last_login_ip',
                );
                $loginListField = $this->displayField($loginListField, 'loginlist');
                //得到子查询的sql
                $loginList = M('loginlist')->group('admin_id')->field($loginListField)->buildSql();
//                echo $loginList;

                //结果返回带一些详细信息
                $data = M('admin')
                                ->join('LEFT JOIN'.$loginList.' loginlist ON __ADMIN__.id = loginlist.admin_id')
                                ->join('__ROLE__ ON __ADMIN__.role_id = __ROLE__.id')
                                ->where($parameter)
                                ->field($displayField)
                                ->order($order)
                                ->select();

//            var_dump($data);
                return $data;

        }

        public function articleList()
        {
                $information = I('post.');
                $table = 'hotarticles';

                $draw = $information['draw'];

                if(empty($draw)) {
                        returnJson(801);
                }

                $start = $information['start'];

                $length = $information['length'];

                if (!(is_numeric($start) && is_numeric($length))) {
                        returnJson(801);
                }
                $caseSql = $this->tranSqlCase('articletype_id', Article::getType());
                $displayField = array(
                        'article_id' => 'id',
                        'title',
                        'articletype_id' => 'type_id',
                        'author',
                        $caseSql => 'type',
                        'content',
                        'like_num',
                        'remark_num',
                        'created_time',
                        'updated_time',
                        'state',
                );

                $columns = $information['columns'];
                $order = array();

                $orders = $information['order'];

                foreach ($orders as $value) {
                        //排序需要的列
                        $field = $columns[$value['column']]['data'];
                        $order[$field] = $value['dir'];
                }


                $parameter = array();

                //框定条件
                if (!empty($information['args'])) {
                        foreach($information['args'] as $field => $value) {
                                if (in_array($field, $displayField)) {
                                        $parameter[$field] = $value;
                                }
                        }
                }

                $searchField = array();
                $searchvalue = $information['search']['value'];
                if (!empty($searchvalue)) {

                        foreach ($columns as $column) {
                                //判断是否需要搜索的
                                if ($column['searchable'] == 'true') {
                                        if (in_array($column['data'], $displayField)) {
                                                $searchField[] = $column['data'];
                                        }
                                }
                        }
                        $parameter['*'] = array($searchvalue, $searchField);
                }

                $displayField = $this->displayField($displayField, $table);
                //用户帖需要的属性
                $articlesDisplayField = $this->displayField(array(
                        'username' => 'author',
                        'title',
                        'content',
                        'type_id',
                        'id',
                        'state'
                ), 'articles');

                //获得子查询的sql语句
                //bbdd 用户写的文章
                $joinArticles = M('articles')
                            ->join('__USERS__ ON __ARTICLES__.user_id = __USERS__.id')
                            ->field($articlesDisplayField)
                            ->select(false);
            $caseSql = $this->tranSqlCase('articletype_id', array(
                        '1' => "重邮新闻",
                        '2' => "教务在线",
                        '3' => "学术讲座",
                        '4' => "校务公告"
                ));

                //新闻文章
                $newsDisplayField = $this->displayField(array(
                        $caseSql => 'author',
                        'title',
                        'content',
                        'articletype_id' =>'type_id',
                        'id',
                        "'1'"   => 'state'
                ), 'news');
                $joinSql = M('news')
                            ->field($newsDisplayField)
                            ->union($joinArticles)
                            ->buildSql();
                //echo $joinSql;
                //公告
                $noticeDisplayField = $this->displayField(array(
                        'id',
                        'username' => 'author',
                        'title',
                        "'6'" => 'type_id',
                        "'公告'" => 'type',
                        'content',
                        'like_num',
                        'remark_num',
                        'created_time',
                        'updated_time',
                        'state',
                ), 'notices');

                $joinNotice = M('notices')
                                ->field($noticeDisplayField)
                                ->join('__USERS__ ON __USERS__.id=__NOTICES__.user_id')
                                ->select(false);
                 //获得所有文章的字查询表
                $allArticle = M($table)
                                ->join(" JOIN ".$joinSql." article ON article.id=__HOTARTICLES__.article_id and article.type_id = __HOTARTICLES__.articletype_id")
                                ->field($displayField)
                                ->union($joinNotice)
                                ->buildSql();

                $parameter = $this->parameter($parameter);

                //总共的数据量
                $recordsTotal = M()->table($allArticle.' article')->count();
                //筛选的数据
                $result = M()->table($allArticle.' article')->where($parameter)->order($order)->select();
                //筛选得到的数据量
                $recordsFiltered = count($result);
                //分页选取数据
                if ($start >= $recordsFiltered) {
                        $data = array();
                } else {
                        $length = ($recordsFiltered-$start)>$length ? $length : ($recordsFiltered-$start);
                        $data = array_slice($result, $start, $length);

                }
                foreach($data as &$value) {
                    if (mystrlen($value['content']) > 30)
                        $value['content'] = mb_substr($value['content'], 0, 30, 'UTF-8').'...';
                    if (mystrlen($value['content']) > 15)
                        $value['title'] = mb_substr($value['title'], 0, 15, 'UTF-8').'...';
                }
                $info = compact('data', 'recordsFiltered', 'recordsTotal', 'draw');
                returnJson('datatable','', $info);

        }
        //用户信息查看
        public function userList()
        {
                $information = I('post.');
                $draw = $information['draw'];
                if(empty($draw)) {
                        returnJson(801);
                }

                $table = 'users';

                $start = $information['start'];

                $length = $information['length'];
                if (!(is_numeric($start) && is_numeric($length))) {
                        returnJson(404);
                }
                //显示的字段
                $displayField = array(
                        '__USERS__.id'          => 'id',
                        '__USERS__.username'=> 'username',
                        '__USERS__.gender'      => 'gender',
                        '__USERS__.stunum'      => 'stunum',
                        '__USERS__.state'       => 'state',
                        'last_article_time',
                        'last_remark_time',
                        'created_time',
//                        "CONCAT(PERIOD_DIFF(DATE_FORMAT(NOW(), '%Y%m'),DATE_FORMAT(__USERS__.created_time, '%Y%m')), '个月')"   => 'jscy_age'
                );
                $columns = $information['columns'];

                $parameter = array();
                //框定条件
                if (!empty($information['args'])) {
                        foreach($information['args'] as $field => $value) {
                                if (in_array($field, $displayField)) {
                                        $parameter[$field] = $value;
                                }
                        }
                }

                $searchField = array();
                $search = $information['search'];
                $searchValue = $search['value'];

                if (!empty($searchValue)) {

                        foreach ($columns as $column) {
                                //判断是否需要搜索的
                                if ($column['searchable'] === 'true') {
                                        $key = array_search($column['data'], $displayField);
                                        $searchField[] = is_numeric($key) ? $column['data'] : $key;
                                }
                        }

                        $parameter['*'] = array($searchValue, $searchField);
                }
                $parameter = $this->parameter($parameter, $table);

                $displayField = $this->displayField($displayField, $table);

                $order = array();

                $orders = $information['order'];

                foreach ($orders as $value) {
                        //排序需要的列
                        $field = $columns[$value['column']]['data'];
                        $order[$field] = $value['dir'];
                }
                $articleRemarksField = array(
                       'user_id',
                        'max(created_time)'  => 'last_remark_time',
                );

//                $articleRemarksField = $this->displayField($articleRemarksField, 'articleremarks');

                $articleRemarksSql = M('articleremarks')
                                            ->group('user_id')
                                            ->field($articleRemarksField)
                                            ->buildSql();
                $articleField = array(
                    'user_id'            => 'user_id',
                    'max(updated_time)'  => 'last_article_time',
                );
                $articleSql = M("articles")->group('user_id')->field($articleField)->buildSql();

                $data = M('users')
                            ->join(array('LEFT JOIN '.$articleRemarksSql.' articleremark ON __USERS__.id = articleremark.user_id', 'LEFT JOIN '.$articleSql.' article ON __USERS__.id = article.user_id'))
                            ->where($parameter)
                            ->field($displayField)
                            ->order($order)
                            ->select();


                $recordsFiltered = count($data);
                if ($start > $recordsFiltered) {
                        $data = array();
                } else {
                        $length = ($recordsFiltered-$start)>$length ? $length : ($recordsFiltered-$start);
                        $data = array_slice($data, $start, $length);
                        $currentTime = date_create();
                        foreach ($data as &$value) {
                            $createTime = date_create($value['created_time']);
                            unset($value['created_time']);
                            $interval = date_diff($currentTime, $createTime);
                            if ($year = $interval->format('%y'))
                                $value['jscy_age'] = $year.'个年';
                            elseif ($month = $interval->format('%m'))
                                $value['jscy_age'] = $month.'个月';
                            else
                                $value['jscy_age'] = $interval->format('%d')."个天";
                        }
                }
                $recordsTotal = M('users')->count();
                $info = compact('data', 'recordsFiltered', 'recordsTotal', 'draw');
                returnJson('datatable','', $info);

        }

        /**
         * 所有职务
         * @return [type] [description]
         */
        public function role()
        {
                $fields = M('role')
                            ->where(array('state'=>1, 'name' => array('neq', 'root')))
                            ->field('id,display_name')
                            ->select();
                $stunum = I('post.value');
                if (empty($stunum)) {
                        returnJson(801);
                }
                $data = array();
                $admin = M('admin')->where("stunum='%s'", $stunum)->find();
                $role_id = empty($admin) ? -1 : $admin['role_id'];
                $fields[] = array('id'=>'-1', 'display_name' => '用户');
                foreach ($fields as $key => &$field) {
                        if ($role_id == $field['id']) {
                                $field['selected'] = 'true';
                        }
                        $field['text'] = $field['display_name'];
                        unset($field['display_name']);
                }
                returnJson('200', '', $fields);
        }


        /***/
        public function forbidwordList()
        {
                $information = I('post.');
                $draw = $information['draw'];
                if (empty($draw)) {
                        returnJson(801);
                }
                $table = 'forbidwords';
                $start = $information['start'];

                $length = $information['length'];

                if (!is_numeric($start) || !is_numeric($length)) {
                        returnJson(404);
                }
                $forbidwordController = new ForbidwordController;
                $sqlCase = $this->tranSqlCase('type_id', $forbidwordController->getType());

                $typesql = 'GROUP_CONCAT('.$sqlCase.')';

                $displayField = array(
                        'id',
                        'value',
                        'type_id',
                        'type',
                        'updated_time',
                        'state'
                );

                //自查询的参数
                $typeDisplayField = $this->displayField(array(
                        'w_id',
                        'GROUP_CONCAT(Convert(type_id , char))' => 'type_id',
                        $typesql => 'type',
                ), 'word_range');
                //构建子查询
                $typeTable = M('word_range')
                                ->where("state=%d", 1)
                                ->field($typeDisplayField)
                                ->group('w_id')
                                ->buildSql();
                $columns = $information['columns'];
                $parameter = array();
                //框定条件
                if (isset($information['args'])) {
                        foreach($information['args'] as $field => $value) {
                                if (in_array($field, $displayField)) {
                                        $parameter[$field] = $value;
                                }
                        }
                }

                $searchField = array();
                $search = $information['search'];
                $searchvalue = $search['value'];

                if (!empty($searchvalue)) {
                        foreach ($columns as $column) {
                                //判断是否需要搜索的
                                if ($column['searchable'] === 'true') {
                                        $key = array_search($column['data'], $displayField);
                                        $searchField[] = is_numeric($key) ? $column['data'] : $key;
                                }
                        }

                        $parameter['*'] = array($searchvalue, $searchField);
                }
                $orders = $information['order'];
                $order = array();
                foreach ($orders as $value) {
                        //排序需要的列
                        $field = $columns[$value['column']]['data'];
                        $order[$field] = $value['dir'];
                }
                $displayField = $this->displayField($displayField, $table);
                $parameter = $this->parameter($parameter, $table);

                $data = M($table)
                                ->join('LEFT JOIN '.$typeTable.' type ON __FORBIDWORDS__.id = type.w_id')
                                ->where($parameter)
                                ->field($displayField)
                                ->order($order)
                                ->select();
                $recordsFiltered = count($data);
                if ($start > $recordsFiltered) {
                        $data = array();
                } else {
                        $length = ($recordsFiltered-$start)>$length ? $length : ($recordsFiltered-$start);
                        $data = array_slice($data, $start, $length);
                }
                $recordsTotal = M($table)->where($parameter)->count();
                $info = compact('data', 'recordsFiltered', 'recordsTotal', 'draw');
                returnJson('datatable','', $info);

        }

        /**
         * 对某些字段进行过滤,给出一些规则进行匹配
         * @param  string  $field   字段名
         * @param  string  $value   字段对应的值
         * @return bool           是否合法
         */
        protected function validate($field, $data)
        {
                if ($pos = strrpos('.', $field)) {
                        $field = substr($field, $pos+1);
                }

                //默认参数正确
                $mark = true;

                //当不为数组时
                if (!is_array($data)) {
                        $data = array($data);
                }

                switch ($field) {
                        //学号
                        case 'stunum':
                                foreach ($data as $val) {
                                        $mark = $mark && (preg_match('/^20[0-9]{2}21[0-9]{4}$/', $val) > 0);
                                }
                                break;

                        //昵称
                        case 'nickname':
                                //限制的昵称
                                break;

                        //用户的真名
                        case 'username':
                                foreach ($data as $val) {
                                    $mark = $mark && (preg_match('/^[\x{4e00}-\x{9fa5}]+\·?[\x{4e00}-\x{9fa5}]*$/u', $val) > 0);
                                }
                                break;

                        //id值
                        case 'id' :
                                foreach ($data as $val) {
                                    $mark = $mark && is_numeric($val);
                                }
                                break;

                        case 'state':
                                foreach ($data as $val) {
                                    $mark = $mark && is_numeric($val);
                                }
                                break;

                }
                return $mark;
        }

        /**
         * 处理函数参数的
         * @param  array $parameter 查询参数
         * @param  string|bool $table  数据库的表,为false的时候不添加前缀
         * @return array            处理后的参数
         */
        public function parameter($parameter, $table=false)
        {
                if (empty($parameter)) {
                        return $parameter;
                }
                //是否需要模糊查询，是否要添加前缀
                $like_exist = false;

                foreach ($parameter as $key => $value) {
                        if ( !empty($key) || !is_numeric($key)) {
                                // '*' => array('keyword', array('fields')) 关键词, 搜索的区域
                                //*指向查询
                                if ($key === '*') {
                                        $like_exist = true;
                                        continue;
                                } else  {
                                        //数据合法性
                                        if(!$this->validate($key, $value)) {
                                                unset($parameter[$key]);
                                                continue;
                                        }

                                        $field = $this->field($key, $table);
                                }
                        }

                        unset($parameter[$key]);
                        //使用规范的field
                        $parameter[$field] = $value;
                }

                if ($like_exist) {
                        $this->like($parameter, $table);
                }

                return $parameter;
        }

        /**
         * 处理显示字段
         * @param  array|string $displayField 显示的字段
         * @param  string $table         数据表
         * @return array               处理后显示的字段
         */
        public function displayField($displayField, $table)
        {
                if (empty($displayField)) {
                        return $displayField;
                }
                //如果为字符串，转换成数组
                if (is_string($displayField)) {
                        $displayField = explode(',', $displayField);
                }

                foreach ($displayField as $key => $value) {
                        if (is_numeric($key)) {
                                //替换__TABLE__
                                $field = $this->field($value, $table);
                                $displayField[$key] = $field;
                        } else {
                                $field = $this->field($key, $table);
                                unset($displayField[$key]);
                                $displayField[$field] = $value;
                        }
                }

                //按值进行排序，便于union
                uasort($displayField, function($str1, $str2) {
                   if ( false !== $pos = strrpos($str1, '.'))
                        $str1 = substr($str1, $pos + 1);
                   if (false !== $pos = strrpos($str2, '.'))
                        $str2 = substr($str2, $pos + 1);
                   return strcmp($str1, $str2);
                });
                return $displayField;
        }

        /**
         * 模糊查询的时候对参数的处理
         * @param  array &$parameter 查询的参数
         * @param  string|bool $table  数据库的表,为false的时候不添加前缀
         * @return bool             是否成功
         */
        public function like(&$parameter, $table=false)
        {
                //表的前缀
                $prefix = C('DB_PREFIX');
                //表的所有字段名
                $addPrefix = ($table !== false);

                // '*' => array('keyword', array('fields')) 关键词, 搜索的区域
                //*指向查询
                $search = $parameter['*'];

                unset($parameter['*']);
                //将一些关键词转义
                $search[0] = str_replace(array('%', '_'), array('\%', '\_'), $search[0]);
                $condition = array('like', $search[0].'%');
                //模糊查询的条件
                $likeWhere = array();
                foreach ($search[1] as $field) {
                        //如果表里不存在该字段,或者条件里有该字段,或者字段内容格式不匹配,将continue
                        if (!$this->validate($field, $search[0])) {
                                continue;
                        }
                        if ($addPrefix) {
                                $field = $this->field($field, $table);
                        }
                        if (!in_array($field, $parameter)) {
                                $likeWhere[$field] = $condition;
                        }
                }

                $likeWhere['_logic'] = 'or';
                $parameter['_complex'] = $likeWhere;
                return true;
        }
        /**
         * 将字段转换为 带前缀的字段
         * @param  string $value 字段
         * @param  string $table 表名
         * @return string        装换好的字段名
         */
        public function field($value, $table)
        {
                //表的前缀
                $prefix = C('DB_PREFIX');
                //表的所有字段名
                if (empty($table)) {
                        return $value;
                }
                S('table:'.$table, null);
                $fields = S('table:'.$table);
                if (empty($fields)) {
                        $fields = M($table)->getDbFields();
                        S('table:'.$table, $fields);
                }
                if (false !== $pos = strrpos($value, '.')) {
                        $field = preg_replace_callback("/__([A-Z0-9_-]+)__/sU", function($match) use($prefix) {
                                return $prefix.strtolower($match[1]);
                        }, $value, -1, $count);
                        if ($count === 0) {
                                $field = $value;
                        }
                } else {
                        if (in_array($value, $fields)) {
                                $field = $prefix.$table.'.'.$value;
                        } else {
                                $field = $value;
                        }
                }
                return $field;
        }

        /**
         * 生成一个sql 的case语句
         * @param  string $field 字段名
         * @param  array $data  字段的值 => 想转换成的值
         * @return stirng        sql的case语句
         */
        protected function tranSqlCase($field, $data)
        {
                $sql = "CASE $field";
                if (!is_array($data)) {
                        $data = explode(',', $data);
                }
                foreach($data as $key => $value) {
                        $sql .= " WHEN $key THEN '$value' ";
                }
                $sql .= "END ";
                return $sql;
        }

}
