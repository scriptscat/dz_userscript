<?php

/**
 * 帖子发表页面
 */

if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

define('SCRIPT_STATUS_DELETE', 0); //脚本状态,删除
define('SCRIPT_STATUS_ACTIVE', 1); //脚本状态,激活

define('SCRIPT_CODE_STATUS_DELETE', 0); //脚本代码状态,删除
define('SCRIPT_CODE_STATUS_ACTIVE', 1); //脚本代码状态,激活

class threadplugin_tampermonkey_install
{

    public $name = '油猴脚本'; //主题类型名称

    public $iconfile = 'tm.jpg'; //发布主题链接中的前缀图标

    public $buttontext = '发布油猴脚本'; //发帖时按钮文字

    public function newthread($fid)
    {
        $info = <<<Eof
<div class="pbt cl">
<div class="z">
<p style="font-size: 16px">请在<a href="https://scriptcat.org/post-script" target="_blank" style="color:#1976d2;">脚本站</a>发布脚本,然后将链接复制到下方</p>
<input name="script_url" id="script_url" style="width: 400px" placeholder="https://scriptcat.org/script-show-page/@id">
</div>
</div>
<p style="font-size: 16px;color:#ff5b5b">请注意帖子内容与脚本站描述不共用,需要分开编辑</p>
Eof;
        return $info;
    }

    protected $meta;

    public function saveCode($id, $name, $code, $meta)
    {
        //echo $name = urlencode($name);
        //$name = mb_substr($name, 0, 90, 'utf-8');
        //-{$name}
        $codeName = "{$id}.user.js";
        $metaName = "{$id}.meta.js";

        $codeFile = fopen("./script/{$codeName}", "w") or die("Unable to open file!");
        fwrite($codeFile, $code);
        fclose($codeFile);

        $metaFile = fopen("./script/{$metaName}", "w") or die("Unable to open file!");
        fwrite($metaFile, $meta);
        fclose($metaFile);
    }

    public function newthread_submit($fid)
    {
        global $_G;
        // if($_POST['script_url']){
        $pos = strrpos($_POST['script_url'], "/");
        $scriptId = substr($_POST['script_url'], $pos === -1 ? 0 : ($pos + 1));
        $script = self::sql_select('cdb_tampermonkey_script', ['id' => $scriptId], '*');
        if (!$script) {
            showmessage("脚本不存在,请确认脚本链接正确");
            return false;
        }
        if ($script[0]['user_id'] != $_G['uid']) {
            showmessage("你并不是本脚本的发布者");
            return false;
        }
        if ($script[0]['post_id']) {
            showmessage("本脚本已经绑定帖子:https://bbs.tampermonkey.net.cn/forum.php?mod=viewthread&tid=" . $script[0]['post_id']);
            return false;
        }
        $_G['script_url_id'] = $scriptId;
        // }else{
        //     $_G['script'] = $this->parseMeta($_POST['JB_code'], $tid);
        //     if (!is_array($_G['script'])) {
        //         showmessage($_G['script']);
        //         return false;
        //     }
        // }
    }

    public function newthread_submit_end($fid, $tid)
    {
        global $_G;
        if ($_POST['script_url']) {
            self::sql_update('cdb_tampermonkey_script', ['post_id' => $tid], ['id' => $_G['script_url_id']]);
        } else {
            $_G['script'] = $this->parseMeta($_POST['JB_code'], $tid);
            $script = $this->postScript($tid, $_G['script']);
            $script_id = self::sql_insert('cdb_tampermonkey_script', $script);
            $data = $this->postScriptCode($script_id, $_G['script']);
            $newid = self::sql_insert('cdb_tampermonkey_script_code', $data);
            //echo dirname(__file__);
            //die;
            $this->saveCode($script_id, $_G['script']['name'], $_G['script']['code'], $_G['script']['meta_info']);
        }
    }

    protected function postScript($tid, $meta)
    {
        global $_G;
        $script = [
            'post_id' => $tid,
            'user_id' => $_G['uid'],
            'name' => $meta['name'],
            'description' => $meta['description'],
            'content' => "请填写脚本说明",
            'status' => SCRIPT_STATUS_ACTIVE,
            'createtime' => time(),
            'updatetime' => 0,
        ];
        return $script;
    }

    protected function postScriptCode($script_id, $meta)
    {
        global $_G;
        $data = [
            'user_id' => $_G['uid'],
            'script_id' => $script_id,
            'code' => $_POST['JB_code'],
            'meta' => $meta['meta_info'],
            'version' => $meta['version'],
            'changelog' => $_POST['JB_changelog'] ?: '',
            'createtime' => time(),
            'status' => SCRIPT_CODE_STATUS_ACTIVE,
        ];
        return $data;
    }

    protected function findScriptByTid($tid)
    {
    }

    protected function findScriptCodeByScriptId($script_id)
    {
    }

    public function parseMeta($script, $tid = '')
    {
        if ($tid == '') {
            $url = "
// @supportURL   https://bbs.tampermonkey.net.cn
// @homepage     https://bbs.tampermonkey.net.cn";
        } else {
            $url = "
// @supportURL   https://bbs.tampermonkey.net.cn/forum.php?mod=viewthread&tid={$tid}
// @homepage     https://bbs.tampermonkey.net.cn/forum.php?mod=viewthread&tid={$tid}";
        }
        if (!strpos($script, "@supportUrl")) {
            $script = str_replace("\r\n// ==/UserScript==", "{$url}\r\n// ==/UserScript==", $script);
        }
        if (!preg_match("#\/\/ ==UserScript==([\s\S]+?)\/\/ ==\/UserScript==#", $script, $matches)) {
            return '脚本代码不正确，请检查';
        }
        if (!preg_match_all("#@(.+?)\s+(.*?)[\r\n]#", $matches[1], $kvMatches, PREG_SET_ORDER)) {
            return '脚本代码不正确，请检查';
        }
        //print_r($matches);
        // print_r($kvMatches);
        $ret = [];
        foreach ($kvMatches as $k => $v) {
            switch ($v[1]) {
                case "grant":
                {
                    $ret[$v[1]][] = $v[2];
                    break;
                }
                default:
                {
                    $ret[$v[1]] = $v[2];
                    break;
                }
            }
        }
        if ($ret['name'] == '' || mb_strlen($ret['name']) > 128) {
            return "脚本名(name)不符合格式(不能为空和不能超过128个字符)";
        }
        if ($ret['description'] != '' && mb_strlen($ret['description']) > 4096) {
            return "描述内容(description)不符合格式(不能为空和不能超过4096个字符)";
        }
        $ret['code'] = $script;
        $ret['meta_info'] = $matches[0];
        return $ret;
    }

    public function editpost($fid, $tid)
    {
        global $_G;
        $script = self::sql_select('cdb_tampermonkey_script', ['post_id' => $_GET['tid']], '*')[0];
        $jsInfo = self::sql_select('cdb_tampermonkey_script_code', ['script_id' => $script['id']], '*', 'order by id desc')[0];
        $info = <<<Eof
<div class="pbt cl">
<div class="z"
<p style="font-size: 16px"><a href="https://scriptcat.org/post-script" target="_blank" style="color:#1976d2">脚本站</a>脚本url</p>
<input name="script_url" id="script_url" style="width: 100%" value="https://scriptcat.org/script-show-page/{$script['id']}" disabled><br>
<p style="font-size: 16px">关于脚本的更新请在<a href="https://scriptcat.org/script-show-page/{$script['id']}" target="_blank" style="color:#1976d2">脚本页->更新脚本</a>进行编辑和修改</p>
Eof;
        $info .= "
</div>
</div>
<p style='font-size: 16px;color:#ff5b5b'>请注意帖子内容与脚本站描述不共用,需要分开编辑</p>";
        return $info;
    }

    public function editpost_submit($fid, $tid)
    {
        // global $_G;
        // $_G['script'] = $this->parseMeta($_POST['JB_code'], $tid);
        // $_POST['specialextra'] = '';
        // if (!is_array($_G['script'])) {
        //     showmessage($_G['script']);
        //     return false;
        // }
    }

    public function editpost_submit_end($fid, $tid)
    {
        // global $_G;
        // $scriptData = $this->postScript($tid, $_G['script']);
        // $user_id=$scriptData['user_id'];
        // unset($scriptData['user_id']);
        // unset($scriptData['content']);
        // unset($scriptData['createtime']);
        // $scriptData['updatetime'] = time();
        // $script = self::sql_select('cdb_tampermonkey_script', ['post_id' => $_GET['tid']], '*')[0];
        // self::sql_update('cdb_tampermonkey_script', $scriptData, ['id' => $script['id']]);
        // $data = $this->postScriptCode($script['id'], $_G['script']);
        // $scriptCode = self::sql_select('cdb_tampermonkey_script_code', ['script_id' => $script['id'],'version'=>$data['version']], '*');
        // if($scriptCode){
        //     self::sql_update('cdb_tampermonkey_script_code', $data,['id'=>$scriptCode[0]['id']]);
        // }else{
        //     self::sql_insert('cdb_tampermonkey_script_code', $data);
        // }
        // $this->saveCode($script['id'], $_G['script']['name'], $_G['script']['code'], $_G['script']['meta_info']);
    }

    public function newreply_submit_end($fid, $tid)
    {
    }

    public function viewthread($tid)
    {
        $script = self::sql_select('cdb_tampermonkey_script', ['post_id' => $_GET['tid']], '*')[0];
        $code = self::sql_select('cdb_tampermonkey_script_code', ['script_id' => $script['id']], '*', 'order by id desc')[0];
        //$jsInfo = self::sql_select('cdb_tampermonkey_script_code', ['script_id' => $script['id']], '*', 'order by id desc')[0];
        $url = 'https://scriptcat.org/scripts/code/' . $script['id'] . '/' . urlencode($script['name']) . '.user.js';

        $info = <<<Eof
<style type="text/css">

#install-area {
    margin-bottom: 1em
}

#install-area .install-help-link:focus,#install-area .install-help-link:hover,#install-area .install-link:focus,#install-area .install-link:hover {
    transition: box-shadow .2s;
    box-shadow: 0 8px 16px 0 rgba(0,0,0,.2),0 6px 20px 0 rgba(0,0,0,.19)
}

.install-help-link,.install-link,.install-link:active,.install-link:hover,.install-link:visited {
    transition: box-shadow .2s;
    display: inline-block;
    background-color: #005200;
    padding: .8em 3em;
    color: #fff;
    text-decoration: none
}

.install-help-link,.install-help-link:active,.install-help-link:hover,.install-help-link:visited {
    background-color: #1e971e;
    color: #fff
}
</style>
Eof;
        switch ($script['type']) {
            case 3:
                $name = urlencode($script['name']);
                $info .= <<<Eof
<input type="text" value="// @require https://scriptcat.org/lib/${script['id']}/{$code['version']}/${name}.js" disabled style="width: 80%"/>
<br>
<a class="install-help-link" title="如何使用" target="_blank" href="https://bbs.tampermonkey.net.cn/thread-249-1-1.html">如何使用？</a>
<a class="install-help-link" style="background-color:#3399FF" title="库问题反馈" target="_blank" href="https://scriptcat.org/script-show-page/{$script[id]}/issue">库问题反馈</a>
<a class="install-help-link" style="background-color:#2261b7" title="给库评分" target="_blank" href="https://scriptcat.org/script-show-page/{$script[id]}/comment">给库评分</a>
<a class="install-help-link" style="background-color:red" title="查看代码" target="_blank" href="https://scriptcat.org/script-show-page/{$script[id]}/code">查看代码</a>
Eof;
                break;
            default:
                $info .= <<<Eof
<a href="{$url}" class="install-link">安装此脚本</a>
<a class="install-help-link" title="如何安装" target="_blank" href="https://bbs.tampermonkey.net.cn/forum.php?mod=viewthread&tid=57">如何安装？</a>
<a class="install-help-link" style="background-color:#3399FF" title="脚本问题反馈" target="_blank" href="https://scriptcat.org/script-show-page/{$script[id]}/issue">脚本问题反馈</a>
<a class="install-help-link" style="background-color:#2261b7" title="给脚本评分" target="_blank" href="https://scriptcat.org/script-show-page/{$script[id]}/comment">给脚本评分</a>
<a class="install-help-link" style="background-color:red" title="查看代码" target="_blank" href="https://scriptcat.org/script-show-page/{$script[id]}/code">查看代码</a>
Eof;
                break;
        }
        //<a class="install-help-link" title="如何安装" rel="nofollow" href="/zh-CN/help/installing-user-scripts">?</a>
        return $info;
    }

    public function sql_select($table, $where, $field = '*', $order = '')
    {
        if (count($where) == 0) {
            return false;
        }
        foreach ($where as $k => $v) {
            $strw[] = "`" . $k . "` = '" . addslashes($v) . "'"; //将字段作为一个数组；
        }

        $strw = implode(' and ', $strw);
        $sql = "SELECT {$field} FROM `{$table}` where {$strw} {$order}";
        return DB::fetch_all($sql);
    }

    public function sql_insert($table, $data)
    {
        if (count($data) == 0) {
            return false;
        }
        foreach ($data as $k => $v) {
            $k1[] = '`' . addslashes($k) . '`'; //将字段作为一个数组；
            $v1[] = "'" . addslashes($v) . "'"; //将插入的值作为一个数组；
        }
        $strv .= implode(',', $v1);
        $strk .= implode(",", $k1);
        $sql = "insert into `{$table}` ({$strk}) values ({$strv})";
        //echo $sql;
        return DB::query($sql);
    }

    public function sql_update($table, $data, $where)
    {
        foreach ($data as $k => $v) {
            $str[] = "`" . $k . "` = '" . addslashes($v) . "'"; //将字段作为一个数组；
        }
        foreach ($where as $k => $v) {
            $strw[] = "`" . $k . "` = '" . addslashes($v) . "'"; //将字段作为一个数组；
        }
        $strs = implode(',', $str);
        $strw = implode(' and ', $strw);
        $sql = "update `{$table}` set {$strs} where {$strw}";
        return DB::query($sql);
    }
}
