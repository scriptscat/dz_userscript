<?php


if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

class plugin_tampermonkey_install
{

    public function viewthread_postheader_output()
    {
        global $_G, $postlist;
        // print_r($_G);
        //处理tampermonkey_install问题
        foreach ($postlist as $k => $v) {
            $msg = explode("\x0", $postlist[$k]['message']);
            if (count($msg) > 0) {
                $postlist[$k]['message'] = $msg[0].'</textarea>';
            }
        }
    }
}

class plugin_tampermonkey_install_forum extends plugin_tampermonkey_install
{
}
