<?php declare(strict_types=1);
/*
    本php文件为utf-8, 所以你看得到函数许多时间都会做编码转换.
    支持php 7.4/8.3
    需要: PostgreSQL14.1-CN-HTML-v1.0.tar.gz
*/

// 假如有编译问题.需要重装 htmlhelp.exe
set_time_limit(300);
error_reporting(0);
session_start();


# 配置文件开始.
header('Content-type: text/html; charset=utf-8');
date_default_timezone_set('UTC');

$pggzfile = __DIR__.'/pgchm/PostgreSQL14.1-CN-HTML-v1.0.tar.gz';
//error_reporting(E_ALL);
if(is_file($pggzfile)){
    $date = date('Ymd', filemtime($pggzfile));
}else{
    $date = date('Ymd',time()).'-empty';
}


define('MANUAL_TITLE','PGSQL简体中文手册-PGchm[分享工作室编译于'.$date.']');
# 建议大家不要修改以下路径
define('WINRAR_PATH','C:/Program Files/WinRAR'); // winrar路径

define('CHM_PATH', __DIR__.'/pgchm');            // 根目录
define('HTML_PATH', __DIR__.'\\pgchm\\html');   // 将html放入这个目录
define('SOURCE_PATH', __DIR__.'/pgchm/source'); // 资源目录.
define('INFOVER', apache_get_version());
define('CHARSET', 'utf-8');
define('GZ_FILE', $pggzfile);
$act = intval($_GET['act']);
// 路径不能放另外的地方? 需要注意.
$hhp = CHM_PATH.'/pgsql_manual_zh.hhp'; // filelist
$hhc = CHM_PATH.'/pgsql_manual_zh.hhc'; // 左则.
$hhk = CHM_PATH.'/pgsql_manual_zh.hhk'; // 搜索项
?>

<!DOCTYPE HTML>
<html>
<head>
    <meta charset="utf-8" />
	<meta http-equiv="content-type" content="text/html" />
	<meta name="author" content="www.atuser.com" />
	<title>PGchm 手册生成解析器</title>
    <style type="text/css">
        span{ background-color: #E5E5E5; border: 1px solid gray; display: block; padding: 8px;}
    </style>
</head>

<body style="font-size: 12px; line-height: 2em;">
<?php        
    $Sact = intval($_SESSION['act']);
    if(isset($_GET['t'])){
        switch ($_GET['act']){
            case 1:
               $_SESSION['act'] = 1; 
               $ERR = '';         
               if(!function_exists('mb_convert_encoding')){
                    $ERR .= 'mb_convert_encoding 未开启! <br />';
               }

              if(is_dir(SOURCE_PATH) === false){
                 mkdir(SOURCE_PATH, 0777);
              }
                             
              if(is_dir(SOURCE_PATH) === false){
                   $ERR .= HTML_PATH.'目录不可缺少!<br />';
              }

              if(is_dir(HTML_PATH))
                 delDirAndFile(HTML_PATH);

              if(!is_dir(HTML_PATH) && is_file(GZ_FILE)){
                $s = @exec($t = '"'.WINRAR_PATH.'/WinRAR.exe" e '.GZ_FILE.' -y '.HTML_PATH.'/..', $info, $err);
                if($err != 0){
                    echo '<span style="color:red">ERROR: '.GZ_FILE.' 解压失败! <br />';
                    var_dump($s);
                    var_dump($info);
                    echo '</span>';
                }else{
                    echo GZ_FILE.' 解压成功!<br />';
                    $htmlfiles = scandir(HTML_PATH);
                    $fi = 0;   

                    echo '<br />人工跳过处理的文件: <br />';
                    foreach($htmlfiles AS $val){
                        if($val === '.' || $val === '..'){
                            continue;
                        }

                        if(!stripos($val, '.html')){
                            echo $val.', ';
                            continue;
                        }
                        $path = HTML_PATH.'\\'.$val;
                        $d = @file_get_contents($path);
                        $d = strtr($d, [' '=>' ']);
                        $d = strtr($d, ['<?xml version="1.0" encoding="UTF-8" standalone="no"?>'=>'']);
                        $d = strtr($d, ['<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'=>'<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">']);
                        $d = strtr($d, ['<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml">'=>'<!doctype html><html lang="en">']);
                        @file_put_contents($path,trim($d));
                        $fi ++;
                    }
                    // 路径不能放另外的地方? 需要注意.
                    deledeflist();

                    echo '<br />人工处理'.$fi.'个文件, 保障html代码正常化.<br /><br />';
                    echo HTML_PATH.' 目录中文件数量: '.(count($htmlfiles)-2).' 个<br />';
                    echo '请继续第二步!';
                }
                if($err){
                    echo $err;
                    print_r($info);
                }
                sleep(2);
              }else{
                if(!is_file(GZ_FILE)){
                   exit('<p style="color:red">'.GZ_FILE.'文件不存在!</p>'); 
                }else{
                   exit('<p style="color:red">html目录删除失败? 有占用!</p>'); 
                }

              }  
               exit();
               break;
            case 3: //  gbk
            case 30: // utf8
                // 处理转gbk码, html码优化.
                if($Sact > 100){
                   exit('<p style="color:red">无法第二次操作处理html, 请执行第一步!</p>');
                }
                $vvjson = array();
                $htmlfiles = scandir(HTML_PATH);
                $fi = 0;   
                foreach($htmlfiles AS $val){
                    if($val === '.' || $val === '..' || strpos($val,'.svg')!==false)
                        continue;
                    
                    $path = HTML_PATH.'\\'.$val;
                    $d = @file_get_contents($path);
                    if(is_utf8($d) && $act === 3){
                        $d = strtr($d, ['charset="utf-8"'=>'charset="gbk"']);
                        $d = mb_convert_encoding($d,'gbk','auto');
                        if(is_utf8($d)){
                           exit($path.' is utf?');
                        }

                        if($d){
                           file_put_contents($path, $d);
                           $fi++;
                        }else{
                           exit($path.' is empty?');
                        }
                   
                    }

                    // utf8
                    if($act === 30){
                        // 就不需要做什么.
                        $path = HTML_PATH.'/'.$val;
                        if(strpos($val,'.html') !== false){
                           $d = file_get_contents($path);
                           
                           if(stripos($d, 'class="atuser"') === false)
                           if(stripos($d, '<a accesskey="h"') !== false){
                              $d = strtr($d, ['<a accesskey="h"'=>'<a class="atuser" target="_blank" href="https://www.atuser.com" title="分享工作室">分享工作室</a> <a accesskey="h"']);
                              file_put_contents($path,$d);
                              $fi++;
                           }

                           //处理一个json.
                           preg_match('/<title>([^<]*)<\/title>/isU', $d, $reg);
                           if(trim($reg[1]) !== ''){
                              $vvjson[$val] = trim($reg[1]);
                           }
                        }
                        
                        // 改变CSS值.  stylesheet.css
                        if($val === 'stylesheet.css'){
                            $path = HTML_PATH.'/stylesheet.css';
                            $d = file_get_contents($path);
                            if(stripos($d, 'BODY>div') === false){
                            file_put_contents($path,"\n".'BODY>div{width: 980px; margin: 0 auto;}',FILE_APPEND);
                            echo 'stylesheet.css 已经更改!<br />';
                            }
                        }
                    }
                }

                if($act === 3){
                   echo '数量文件:'.$fi.' 编码转为GBK成功!<br />'; 
                   echo '转化为GBK后, 才可以编译chm.<br />';
                }else{
                       

                    @file_put_contents(SOURCE_PATH.'/pgsql_manual_data.php', "<?php \r\n return ". var_export($vvjson, true).';' ); 
                    @file_put_contents(HTML_PATH.'/pgsql_manual_data.php', "<?php \r\n return ". var_export($vvjson, true).';');
                    echo '文件索引数组:'. count($vvjson) .' Array类型: pgsql_manual_data.php !<br />';  
                    echo '数量文件:'.$fi.' 处理为展示版完成!<br />'; 
                    echo '展示版适合在线访问.<br />';
                }  
                echo '请继续下一步!';
                $_SESSION['act'] = $fi;
                exit();
                break; 
            case 2:
                if($Sact > 100){
                   exit('<p style="color:red">编码已经变更后, 无法再生成编译数据! 请执行第一步!</p>');
                }
                deledeflist();
                echo '编译数据处理:<br />';
                $keylist = get_indexofhtmlfile('index.html');
                $keylist['biblio.html'] = ['tie'=>'参考书目','sub'=>[]];
                $keylist['bookindex.html'] = ['tie'=>'索引','sub'=>[]];
                $keylist = array_merge( ['index.html'=>['tie'=>'首页','sub'=>[]]],$keylist);

                $dd = parse_xmlul($keylist);
                $fhed = '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML//EN">
<html>
<head>
    <meta name="generator" content="PHP 8.x+ - Tuesday,SZ">
</head>
<body><object type="text/site properties">
    <param name="Window Styles" value="0x800227">
    </object>'.$dd;
            $fz =  file_put_contents($hhc, $fhed);
            echo $hhc.' 写入数据: '.$fz.'<br />';

                $htmlfiles = scandir(HTML_PATH);
                $fhed = '[OPTIONS]
Binary Index=Yes
Compatibility=1.1 or later
Compiled file=pgsql_manual_zh.chm
Contents file=pgsql_manual_zh.hhc
Index file=pgsql_manual_zh.hhk
Default Window=phpdoc
Default topic='.HTML_PATH.'\index.html
Display compile progress=Yes
Full-text search=Yes
Language=0x804 Simplified Chinese
Title='.mb_convert_encoding(MANUAL_TITLE,'gbk','auto').'
Default Font=Verdana,9,0

[WINDOWS]
phpdoc="'.mb_convert_encoding(MANUAL_TITLE,'gbk','auto').'","pgsql_manual_zh.hhc","pgsql_manual_zh.hhk","'.HTML_PATH.'\index.html","'.HTML_PATH.'\index.html",,,,,0x22520,,0x6c,,,,,,,,0

[FILES]
';

                if($htmlfiles){
                   foreach($htmlfiles AS $val){
                      if(strlen($val) <= 2)
                         continue;
                        $fhed .= HTML_PATH.'\\'.$val."\n";
                   }
                  $fz =  file_put_contents($hhp, $fhed);
                  echo $hhp.' 写入数据: '.$fz.'<br />';
                  @file_put_contents(SOURCE_PATH.'/pgsql_manual_data.php', "<?php \r\n return ". var_export($vvjson, true).';' );
                }else{
                  echo $hhp.' 未成功写入, 请注意!!!<br />'; 
                }
            
$fhed ='<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML//EN">
<html>
<head>
  <meta name="generator" content="PHP 8.x+ - Tuesday,SZ">
</head>
<body><object type="text/site properties">
    <param name="Window Styles" value="0x800227">
  </object><UL>
';
            $keylist = array();    
            foreach($htmlfiles AS $val){
                if(strlen($val) <= 2)
                   continue;
                if(stripos($val,'.html') !== false){
                    $htmldata = file_get_contents(HTML_PATH.'\\'.$val);
                    preg_match('/<title>([^<]*)<\/title>/isU', $htmldata, $reg);
                    if(trim($reg[1]) !== ''){
                        $keylist[$val]['tie'] = trim($reg[1]);
                        $fhed .= '<li><object type="text/sitemap">
                        <param name="Local" value="'.HTML_PATH.'\\'.$val.'">
                        <param name="Name" value="'. mb_convert_encoding(trim($reg[1]),'gbk','auto').'">
                      </object></li>'."\n";
                    }

                    // 取出文章中的链接.
                    // <a class="link" href="libpq-connect.html#LIBPQ-CONNSTRING" title="34.1.1. 连接字符串">连接字符串</a>
                    preg_match_all('/<a [^>]*>.*<\/a>/isU', $htmldata, $reg);
                    if($reg[0]){
                        foreach($reg[0] AS $k => $v){
                            if(stripos($v,'#') === false || stripos($v,'href="http') !== false){
                               unset($reg[0][$k]); 
                            }
                        }

                        foreach($reg[0] AS $k => $v){
                            $ret = array();
                            if(stripos($v,'title=') !== false){
                               preg_match('/title="([^"]*)"/isU', $v, $ret[0]);
                            }
                            
                            preg_match('/href="([^"]*)"/isU', $v, $ret[1]);
                            $tem = explode('#', trim($ret[1][1]));
                            $f = trim($tem[0]);
                            $kk = trim($tem[1]);
                            preg_match('/<a [^>]*>(.*)<\/a>/isU', $v, $ret[2]);
                            if(isset($ret[2][1])){
                                // html去掉.
                                $ret[2][1] = strip_tags($ret[2][1]);
                            }
                            // 如果没有标题title. 就用a描述.
                            if(!isset($ret[0][1])){
                               $ret[0][1] = (string) trim($ret[2][1]);
                            }
                            $ret[2][1] = strtr(strtolower($kk),['-'=>' '] );
                            $keylist[$f]['sub'][$kk] = array('htm'=>$v,'tie'=>(string) trim($ret[0][1]),'fuc'=>$ret[2][1] );
                            // debug list
                            $fhed .= '<li><object type="text/sitemap">
                        <param name="Local" value="'.HTML_PATH.'\\'.$val.'#'.$kk.'">
                        <param name="Name" value="'.mb_convert_encoding(trim($ret[0][1]),'gbk','auto') .'">
                      </object></li>'."\n";
                        $fhed .= '<li><object type="text/sitemap">
                        <param name="Local" value="'.HTML_PATH.'\\'.$val.'#'.$kk.'">
                        <param name="Name" value="'. mb_convert_encoding(trim($ret[2][1]),'gbk','auto') .'">
                        </object></li>'."\n";
                        }
                    }
                }
            }

            if($keylist){
                $fz =  file_put_contents($hhk, $fhed.'</UL></body></html>');
                echo $hhk.' 写入数据: '.$fz.' 数据组: '. count($keylist) .' <br />';
            }else{
                echo $hhk.' 未成功写入, 请注意!!!<br />';
            }
            echo '三条数据文件写入量需要大于: 62612.<br />';
            echo '验证正常后继续下一步!';
            exit();
            break;
            case 4:
                // TODO: 第四步提示编译成chm
                echo '请下载安装HTML Help Workshop: <a href="http://msdn.microsoft.com/en-us/library/ms669985.aspx">http://msdn.microsoft.com/en-us/library/ms669985.aspx</a><br />';
                echo '<img style="width: 720px;" src="./source/1.jpg" /><br />';
                echo '先点击打开, 然后指向下面的编译路径, 然后就点击蓝色指示点击编译即可.<br />';
                echo '<img style="width: 720px;" src="./source/2.jpg" /><br />';
                echo '速度有点慢, 慢慢等待完成. 切勿中途取消!<br />';
                echo '编译文件路径: '. realpath(CHM_PATH).'\\pgsql_manual_zh.hhp <br />';
                echo '编译出来的chm放在: 根目录中. <br />';
                exit();
                break;
            case 5:
                echo '清除开始....<br />';
                delDirAndFile(HTML_PATH);
                deledeflist();
                echo '编译数文件删除成功!';
                $_SESSION['act'] = 0;
                exit();
            break;
        }   
    }
    
    // 文件转移.
    if(is_file('./chm/php_manual_zh.chm')){
       rename('./chm/php_manual_zh.chm', './php_manual_zh.chm'); 
    }
?>
<!-- TODO: html 显示内容, 在这儿. -->
<div style="margin: 30px; margin-bottom:0px; width: 800px;">
    <h3>
    <div style="float: left; overflow: hidden;">PGSQL 轻松制作教程手册</a>
    </div>
    <div style="float: right; overflow: hidden; font-weight: normal;"><a href="./pgchm.php">首页</a> - <a href="./">php手册</a></div>
    </h3>
    <div style="clear: both;"></div>
    <?php if($act < 1) {?>
    <span><a href="./pgchm.php?act=1">第一步更新Zip包</a></span><br />
    <?php } ?>
    <?php if($act < 2) {?>
    <span><a href="./pgchm.php?act=2">第二步生成编译文件</a></span><br />
    <?php } ?>
    <?php if($act < 3) {?>
    <span><a onclick="return confirm('确认GBK处理?')" href="./pgchm.php?act=3">GBK文件CHM工作</a>, <a href="./pgchm.php?act=30" onclick="return confirm('确认UTF8处理?')" title="">UTF8展示版重组</a></span><br />
    <?php } ?>
    <?php if($act < 4) {?>
    <span><a href="./pgchm.php?act=4">第四开始编译</a></span><br />
    <?php } ?>
    <?php if($act < 5) {?>
    <span><a href="./pgchm.php?act=5">第五步还原默认 (可选功能)</a></span><br />
    <?php } ?>
    
    <div>
        <span><a href="./pgchm/pgsql_manual_zh.chm">pgsql_manual_zh.chm</a>: <?php
        if(is_file('./pgchm/pgsql_manual_zh.chm')){
           echo sprintf('%.2f',filesize('./pgchm/pgsql_manual_zh.chm') / 1024 /1024).'MB, ';
           echo date('Y-m-d H:i:s', filemtime('./pgchm/pgsql_manual_zh.chm')+8*3600);
        }
        ?> </span>
    </div>
    
    * 执行时间有点长, 请耐心等候!, <a target="_blank" href="./pgchm/html">html查看</a>
    </div>
    <?php if($_GET['act'] && !$_GET['t']){?>
    <h3 style="margin-left: 30px;">执行开始, 请等待结果...</h3>
    <iframe src="./pgchm.php?act=<?php echo $_GET['act'];?>&t=<?php echo $x;?>" style="width: 800px; font-size: 12px; margin-left: 30px; height: 600px; border: none; border: 1px solid silver;"></iframe>
    <?php } ?>
</body>
</html>
<?php

#第二步内容函数完成.
function delDirAndFile( $dirName ){
    if ( $handle = opendir($dirName) ) {
       while ( false !== ( $item = readdir( $handle ) ) ) {
       if ( $item != "." && $item != ".." ) {
               if ( is_dir($dirName.'/'.$item) ) {
                    delDirAndFile( $dirName.'/'.$item);
               } else {
                    if(!unlink($dirName.'/'.$item))
                      echo 'not '."$dirName/$item";
               }
       }
    }
     @closedir( $handle );
	 if(is_dir($dirName))
	   if(rmdir( trim($dirName,'/') ) ){
          echo "删除目录成功： $dirName<br />\n";
        }else{
          echo "删除目录失败： $dirName<br />\n";
       };
    }else{
        echo "删除目录成功： $dirName<br />\n";  
    }
}

/**
 * 检测字符是否utf-8
 */
function is_utf8($str) {
   return (strtoupper(mb_detect_encoding(strval($str),'UTF-8,GBK')) === 'UTF-8');
}

function parse_xmlul($keylist){
    $html = '';
    foreach($keylist AS $k => $v){
        $imgk = $v['sub']?1:11;
        $html .='<Ul><li><object type="text/sitemap">
        <param name="Name" value="'. mb_convert_encoding(htmlspecialchars($v['tie']),'gbk','auto').'">
        <param name="Local" value="'.HTML_PATH.'\\'.$k.'">
        <param name="ImageNumber" value="'.$imgk.'">
        </object></li>';
        if($v['sub']){
           $html .= parse_xmlul($v['sub']);
        }
        $html .= '</UL>';
    }
    return $html;
}

function get_indexofhtmlfile($htmlfile) {
    $d = file_get_contents(HTML_PATH.'\\'.$htmlfile);
    preg_match('/<dl class="toc">(.*)<\/dl>/is', $d, $reg);
    $dldd = trim((string) $reg[1]);
    if(stripos($dldd,'<dl>') !== false){
       $dldd .= '</dl>';
       $reg = array();
       preg_match_all('/<dt>(.*)<\/dt><\/dl>/isU', $dldd, $reg);
    }else{
       preg_match_all('/(<dt>.*<\/dt>)/isU', $dldd, $reg);
    }

    $keylist = array();
    !isset($reg[1]) && $reg[1] = array();
    foreach($reg[1] AS $val){
        if(stripos($val,'<dt>') === false)
            continue;
        preg_match_all('/<a href="([^"]*)">(.*)<\/a>/isU', $val, $sreg);
        if($sreg[1]){
        $ook = trim($sreg[1][0]);
        $keylist[$ook] = ['tie'=> strip_tags(trim($sreg[2][0]))];
        $keylist[$ook]['sub'] = [];
        unset($sreg[1][0]);

        foreach($sreg[1] AS $k => $vv){
            $subs = array();
            if(stripos($vv,'#') === false)
               $subs = get_indexofhtmlfile($vv);
            $keylist[$ook]['sub'][trim($vv)] = ['tie'=> strip_tags(trim($sreg[2][$k])),'sub'=>$subs];
        }
        }
    }
    return $keylist;
}

function deledeflist(){
    global $hhp,$hhc, $hhk;
    if(is_file($hhp)) unlink($hhp);
    if(is_file($hhc)) unlink($hhc);
    if(is_file($hhk)) unlink($hhk);
    if(is_file(CHM_PATH.'\\pgsql_manual_zh.chm')) unlink(CHM_PATH.'\\pgsql_manual_zh.chm');
    if(is_file(CHM_PATH.'\\pgsql_manual_zh.chw')) unlink(CHM_PATH.'\\pgsql_manual_zh.chw');
}
