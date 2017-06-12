<?php
/**
 * Created by PhpStorm.
 * User: pumbf
 * Date: 2017/4/1
 * Time: 23:34
 */

namespace Home\Controller;


class ExtentController
{
    protected $apiUrl = array(
        'electric' => 'http://hongyan.cqupt.edu.cn/MagicLoop/index.php?s=/addon/ElectricityQuery/ElectricityQuery/getElectricByRoom'
    );
    public function getElectric()
    {
        $version = '1.0.1';
        if (true !== authUser(I('post.stuNum'), I('post.idNum'))) {
            returnJson('403', '没有登陆');
        }
        $user = getUserInfo(I('post.stuNum'));
        $dormitory = D('dormitory')->where(array('user_id' => $user['id'], 'state' => 1))->find();
        if (!$dormitory)    returnJson(403, '你先前未绑定寝室号');

        $dormitoryId = $dormitory['building'] . '-' . $dormitory['room'];
        $month = I('post.month') ? I('post.month') : 6;
        if ($month > 24)
            returnJson(404, '浏览记录不能超过两年', compact('version'));
        $result = curlPost($this->apiUrl['electric'], array('month'=>$month,'room'=>$dormitoryId));
        if ($result === false) {
            returnJson(404, 'api error');
        }
        $result = json_decode($result, true);

        $electric = $result['data'];
        //本月的
        $trend = array();
        $current = $electric[0];
        unset($electric[0]);
        $time = mb_substr($current['record_time'],0,2,'utf-8');
        foreach ($electric as $value) {
            $time--;
            $time =  $time==0 ?  12 : $time;
            $elec['time'] = $time;
            $elec['spend'] = $value['elec_spend'];
            $elec['elec_start'] = $value['elec_start'];
            $elec['elec_end'] = $value['elec_end'];
            $trend[] = $elec;
        }
        $data = array(
            'version'=>$version,
            'data'=>array(
                'building' => $dormitory['building'],
                'room' => $dormitory['room'],
                'result'=> array(
                    'current' => $current,
                    'trend' => $trend,
                ),
            ),
        );
        returnJson(200, '电费信息', $data);
    }
}