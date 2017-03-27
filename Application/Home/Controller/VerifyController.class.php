<?php
/**
 * Created by PhpStorm.
 * User: pumbf
 * Date: 2017/3/27
 * Time: 17:15
 */

namespace Home\Controller;


class VerifyController  extends BaseController
{
    public function _initialize() {}
    public function verifyLogin()
    {

        $stuNum = I('post.stuNum');
        $idNum = I('post.idNum');
        if (empty($idNum) || empty($stuNum)) {
            returnJson(801);
        }

        if (6 != mystrlen($idNum)) {
            if (false === $idNum = $this->decrypt($idNum)) {
                returnJson(404);
            }
        }

        echo curlPost($this->apiUrl, compact('stuNum', 'idNum'));

    }
}