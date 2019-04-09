<?php


namespace QA\Controller;

use Think\Controller;

class CIController extends Controller
{
    public function gitPull()
    {
        if (!IS_POST)
            returnJson(415);


        chdir("/var/www/html/app");
        shell_exec("git pull");
    }
}