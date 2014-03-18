<?php
/*
key神---万能密码重设工具 
安全联盟站长平台出品（http://zhanzhang.anquan.org/) 2013.10.17
使用交流QQ群：群1：126020287、群2：154169836、群3：25614719
-----------
[程序更新]
2013.10.25 版本升级为V1.1，加入本地锁定功能。
2013.10.23 加入找回密码后，自动强制卸载本程序。
*/
error_reporting(0);
@set_time_limit(0);
@header('Content-Type: text/html; charset=UTF-8');
/*
 * 定义全局变量
 * 这些全局变量用来保存
 * 1 连接数据库需要的信息
 * 2 添加或者更新用户需要的sql语句
 * 3 用户的输入
 */
/*获取云端信息的URI*/
$g_ks_cms       = 'http://localhost/passwd-reset-tool/cms.php';   //云端URI

/*获取支持的cms的URI*/
$g_ks_tactics   = 'http://localhost/passwd-reset-tool/tactics.php';

/*配置文件中的信息*/
$g_ks_host      = '';   //数据库主机
$g_ks_user      = '';   //数据库用户名
$g_ks_passwd    = '';   //数据库密码
$g_ks_db        = '';   //数据库
$g_ks_tablepre  = '';   //表前缀

/*用户输入的信息*/
$g_manager_name     = 'admin888';   //manager帐号
$g_manager_pass     = isset($_POST['g_manager_pass']) ? $_POST['g_manager_pass'] : 'Admin88!';     //用户输入的manager密码

/*云端提供的信息*/
$g_ks_updatesql = '';   //要执行的update sql语句
$g_ks_insertsql = '';   //要执行的insert语句
$g_ks_selectsql = '';   //要执行的select语句

/*操作产生的结果*/
$g_ks_link      = '';   //保存数据库连接
$g_ks_result    = '';   //保存查询结果
$g_ks_info      = '';   //保存输出的信息

/*
 *
 * 程序主要逻辑代码
 * 1 从云端获取策略代码，策略代码是base64加密的php代码
 * 2 执行策略代码， 实现全局变量的赋值
 * 3 如果管理表中有管理员则直接update， 没有则进行insert
 * 4 最后输出用户的新帐号以及密码
 *
 */
/*文件自我删除*/
if(isset($_POST['selfrm'])){
    if(@unlink($_SERVER['SCRIPT_FILENAME'])){
        errorpage("删除文件成功!");    
    }else{
        errorpage("删除失败，请手工删除！");//linux可能权限原因删除失败
    }

}

if(!isset($_POST['submit']) or $_POST['submit'] == ''){
    /*保存支持的cms*/
    $support_cms = '';
    /*是否自动识别出来*/
    $is_identify = 0;

    $cmses = get_tactics($g_ks_cms);
    if(!$cmses){
        errorpage("与云端服务器连接失败， 请检查网络是否通顺！");
    }
    $arr_cms = explode('|', $cmses);
    $options = '';
    /*添加cms自动设别*/
    for($i=0; $i<count($arr_cms); $i++){
        $t_cms = explode(':', $arr_cms[$i]);
        $cms = $t_cms[0];
        $path = $t_cms[1];
        $lockpath = $t_cms[2];
		if ( $i < (count($arr_cms) - 2) ) {
			$support_cms .= $cms.'，';
		} else if ( $i < (count($arr_cms) - 1) ) {
			$support_cms .= $cms;
		}

        if(file_exists($path)){
            $is_identify = 1;
            /*-----------down added 2013/10/27-----------*/
            $lockfile = $lockpath.'key_god.lock';
            if(file_exists($lockfile)){
                 errorpage('<font color="red" size="2.0">Key神已被锁定，如需再次使用，请手动删除文件：'.$lockfile.'后重新安装！</font>'); 
            }
            if(file_exists('./key_god.lock')){
                errorpage('<font color="red" size="2.0">Key神已被锁定，如需再次使用，请手动删除文件：./key_god.lock后重新安装！</font>');
            }

            /*-----------up added 2013/10/27----------*/
            $g_ks_info['option'] = '<tr><th width="25%">自动识别CMS为：</th><td width="75%"><select name="cms"><option>'.$cms.'</option></select></td></tr>';
        }else {
            if($i == (count($arr_cms)-1)){
                break;
            }else {
                $options .= '<option>'.$cms.'</option>';
                /*支持的cms*/
            }
        }
    }

    if (!$is_identify){
        $g_ks_info['option'] =  '<tr><th width="25%">未识别出CMS：</th><td width="75%"><select name="cms">'.$options.'</select>&nbsp;请检查是否将该文件放在根目录</td></tr>';
    }
    $g_ks_info['support_cms'] = $support_cms;

    templates('index', $g_ks_info);

}else {
    
    $cmses = get_tactics($g_ks_cms);
    if(!$cmses){
        errorpage("与云端服务器连接失败， 请检查网络是否通顺！");
    }
    $arr_cms = explode('|', $cmses);
    $options = '';
    /*添加cms自动设别*/
    for($i=0; $i<count($arr_cms); $i++){
        $t_cms = explode(':', $arr_cms[$i]);
        $cms = $t_cms[0];
        $path = $t_cms[1];
        $lockpath = $t_cms[2];
        if(file_exists($path)){
            $lockfile = $lockpath.'key_god.lock';
            if(file_exists($lockpath)){
                @file_put_contents($lockfile, 'locked');
            }else{
                @file_put_contents('./key_god.lock', 'locked');
            }
            break;
        }
    }
            
        
    $domain = $_SERVER['SERVER_NAME'];    //主机名
    $referer = $_SERVER['HTTP_REFERER'];  //referer
    $ip = $_SERVER['SERVER_ADDR'];        //ip
    $dm = urlencode($domain.'||'.$ip.'||'.$referer);
    $url = $g_ks_tactics.'?cms='.$_POST['cms'].'&dm='.$dm; /*edited 2013/10/25*/
    $eval_code = get_tactics($url);
    if(!$eval_code){
        errorpage("与云端服务器连接失败， 请检查网络是否通顺！");
    }    
    $eval_code = base64_decode($eval_code);
    eval($eval_code);
    
    $g_ks_link = db_connect();
    mysql_select_db($g_ks_db, $g_ks_link);
    $g_ks_result = db_execute($g_ks_selectsql);

    if($row = mysql_fetch_array($g_ks_result)){
        $g_manager_name = $row[0];
        /*修复更新所有帐号密码的问题*/
        $g_ks_updatesql .= "'$g_manager_name'";
        db_execute($g_ks_updatesql);
        $g_ks_info['info'] = "用户名为：$g_manager_name 密码为：$g_manager_pass";
    }else {
        db_execute($g_ks_insertsql);
        $g_ks_info['info'] = "用户名为：$g_manager_name 密码为：$g_manager_pass";
    }
 
    templates('index', $g_ks_info);
} 

/*数据库连接操作函数*/
function db_connect(){
    global $g_ks_host;
    global $g_ks_user;
    global $g_ks_passwd;
    global $g_ks_db;
    global $g_ks_link;

    $link = mysql_connect($g_ks_host, $g_ks_user, $g_ks_passwd) 
        or errorpage(mysql_error());
    return $link;
}

/*数据库查询执行函数*/
function db_execute($sql){
    global $g_ks_link;
    if(!is_resource($g_ks_link)){
        db_connect();
    }

    $result = mysql_query($sql, $g_ks_link)
        or errorpage(mysql_error());
    return $result;
}

/*关闭数据库连接*/
function db_close(){
    global $g_ks_link;
    if(is_resource($g_ks_link)){
        @mysql_close($g_ks_link);
    }
}

/*从服务器端获取策略*/
function get_tactics($url){
    $html = @file_get_contents($url);
    return $html;
}

function templates($tpl, $data){
	extract($data);
	global $support_cms;
	switch ($tpl){
		case "header":
			echo '<html>
			<head>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
			<title>key神---万能后台密码重设工具(安全联盟出品)</title>
			<style>
				body {
					font-family: "Helvetica Neue", Helvetica, Microsoft Yahei, Arial, sans-serif;
					background-color: #f8f8f8;
					color: #333;
				}
				table {
					resize: none;
				}
				tbody th {
					text-align: right;
					padding-right: 10px;
				}
				a {
					color: #09c;
					text-decoration: none;
				}
				a:hover {
					color: #08a;
					text-decoration: underline;
				}
				select {
					border-radius: 3px;
					-webkit-border-radius: 3px;
					-moz-border-radius: 3px;
					border: 1px solid #CCCCCC;
					color: #555555;
					padding: 4px;
					width: 150px;
					outline: none;
				}
				input{
					border: 1px solid #CCCCCC;
					border-radius: 3px 3px 3px 3px;
					-webkit-border-radius: 3px;
					-moz-border-radius: 3px;
					color: #555555;
					display: inline-block;
					line-height: normal;
					padding: 4px;
					width: 150px;
				}   
				.hero-unit {
					margin: 0 auto 0 auto;
					font-size: 18px;
					font-weight: 200;
					line-height: 30px;
					border-radius: 6px;
					padding: 20px 60px 10px;
				}
				.hero-unit>h2 {
					text-shadow: 2px 2px 2px #ccc;
					font-weight: normal;
				}
				.btn {
					display: inline-block;
					padding: 6px 12px;
					margin-bottom: 0;
					font-size: 14px;
					font-weight: 500;
					line-height: 1.428571429;
					text-align: center;
					white-space: nowrap;
					vertical-align: middle;
					cursor: pointer;
					border: 1px solid transparent;
					border-radius: 4px;
					-webkit-user-select: none;
					-moz-user-select: none;
					-ms-user-select: none;
					-o-user-select: none;
					user-select: none;
				}
				.btn:focus {
					outline: thin dotted #333;
					outline: 5px auto -webkit-focus-ring-color;
					outline-offset: -2px;
				}

				.btn:hover,
				.btn:focus {
					color: #ffffff;
					text-decoration: none;
				}

				.btn:active,
				.btn.active {
					outline: 0;
					-webkit-box-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
					box-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
				}

				.btn-default {
					color: #ffffff;
					background-color: #474949;
					border-color: #474949;
				}

				.btn-default:hover,
				.btn-default:focus,
				.btn-default:active,
				.btn-default.active {
					background-color: #3a3c3c;
					border-color: #2e2f2f;
				}
				.btn-success {
					color: #ffffff;
					background-color: #5cb85c;
					border-color: #5cb85c;
				}

				.btn-success:hover,
				.btn-success:focus,
				.btn-success:active,
				.btn-success.active {
					background-color: #4cae4c;
					border-color: #449d44;
				}
				.btn-primary {
					color: #ffffff;
					background-color: #428bca;
					border-color: #428bca;
				}

				.btn-primary:hover,
				.btn-primary:focus,
				.btn-primary:active,
				.btn-primary.active {
					background-color: #357ebd;
					border-color: #3071a9;
				}
				.main {
					width: 960px;
					margin: 0 auto;
				}
				.title, .check{
					text-align: center;
				}
				.check button {
					width: 200px;
					font-size: 20px;
				}
				.check a.btn {
					color: #ffffff;
					text-decoration: none;
				}
				.content {
					margin-top: 20px;
					padding: 15px 30px 30px;
					box-shadow: 0 1px 1px #aaa;
					background: #fff;
				}
				dt {
					font-size: 25px;
				}
				table {
					width: 100%;
					border-collapse:collapse;
					border-spacing: 0;
				}
				th, td {
					text-align: left;
				}
				td {
					border-bottom: solid 1px #e0e0e0;
					height: 40px;
					vertical-align: top;
					line-height: 40px;
				}
				.item_t td {
					border-bottom: 0;
				}
				.item_y {
					word-wrap: break-word;
					word-break: break-word;
					width: 860px;
					color: Red;
					text-indent: 1em;
					padding-bottom: 10px;
				}
				.yt, .yv {
					line-height: 1.7em;
				}
				.yt {
					color: #f00;
				}
				.yv {
					color: #00f;
					font-size: 12px;
				}
				.item_n {
					width: 860px;
					color: #0a0;
					text-indent: 1em;
				}
				.ads>ul {
					list-style: none;
					padding: 0;
				}
				.ads>ul>li {
					float: left;
					padding-right: 20px;
				}
				.foot {
					text-align: center;
					font-size: 13px;
				}
				.clearfix:before,
				.clearfix:after {
					display: table;
					content: " ";
				}
				.clearfix:after {
					clear: both;
				}
				#share {
					text-align: center;
					font-size: 12px;
				}
				p {
					margin: 10px 0;
				}
				.hero-unit {
					border: solid 1px #ddd;
					margin-top: 10%;
				}
				select, input {
					width: 250px;
					margin: 5px 0;
				}
				.main {
					width: 660px;
				}
				form.uninstall {
					text-align: center;
				}
				tbody th {
					font-size: 13px;
					font-weight: normal;
				}
				td {
					border-bottom: none;
					height: auto;
					line-height: auto;
				}
				input.btn {
					width: auto;
				}
				input.btn-success {
					margin-left: 130px;
					margin-top: 5px;
				}
				.support {
					font-size: 14px;
					line-height: 1.2em;
					padding-left: 10px;
				}
				#footer {
					width: 540px;
					font-size: 12px;
					text-align: center;
					margin: 10px auto;
				}
				.con {
					padding: 20px 10px 10px;
					border: solid 1px #ddd;
					border-radius: 5px;
					background-color: #fff;
				}
				.result {
					margin-bottom: 10px;
				}
				.result th {
					font-size: 15px;
					color: #09c;
					text-align: left;
					padding-bottom: 10px;
				}
				.result td {
					font-size: 13px;
					line-height: 1.5em;
				}
				#share.suc {
					background-color: #be0;
				}
				#share>p {
					line-height: 1.5em;
					margin: 0 auto;
					padding-top: 5px;
				}
				#share.suc>p {
					font-size: 16px;
				}
			</style>
			<script src="http://tool.scanv.com/keygod/update_ver.php?v=1.1"></script>
			</head>
			<body>
			<div id="content" class="main">
			<div id="textcontent" class="hero-unit">
			<div><h2 class="title"><a href="http://bbs.anquan.org/forum.php?mod=viewthread&tid=12776">Key神－万能密码重设工具 V1.1</a></h2></div><form action="" method="post" class="uninstall"><input type="submit" name="selfrm"  value="卸 &nbsp; 载" style= "color:red"></form><div align="center"  style= "font-size: 12px;color:red">
						<span id="share_url" style="display:none">http://zhanzhang.anquan.org/</span>
						<span id="share_txt" style="display:none">我忘记后台密码了！怎么办？，请找安全联盟“Key神－万能密码重设工具”。自从有了“Key神”，嘛嘛再也不用担心我忘记后台管理密码了！。下载及教程：http://url.cn/UX4hWU</span>
						<span id="share_tit" style="display:none">安全联盟站长平台</span>
						<ul id="share_pic" style="display:none">
							<li>http://tool.scanv.com/keygod/key_god.png</li>
						</ul>
						<span>分享到：</span>
						<a href="javascript:;" class="tmblog" onclick="shareTo(\'qq\');" id="share_qq" title="分享到QQ">QQ</a>&nbsp;&nbsp;
						<a href="javascript:;" class="tmblog" onclick="shareTo(\'tencent\')" id="share_tencent" title="分享到腾讯微博">腾讯微博</a>&nbsp;&nbsp;
						<a href="javascript:;" class="tmblog" onclick="shareTo(\'qzone\')" id="share_qzone" title="分享到QQ空间">QQ空间</a>&nbsp;&nbsp;
						<a href="javascript:;" class="tmblog" onclick="shareTo(\'pengyou\')" id="share_pengyou" title="分享到朋友网">朋友网</a>&nbsp;&nbsp;
						<a href="javascript:;" class="tmblog" onclick="shareTo(\'sina\')" id="share_sina" title="分享到新浪微博">新浪微博</a></div>';
			break;

		case "footer":
			if ($suc) {
				$share_html = "<div id=\"share\" class=\"suc\">";
			} else {
				$share_html = "<div id=\"share\">";
			}

			echo  <<<HTML
					<p><font size="5.5">使用说明：<a href='http://bbs.anquan.org/forum.php?mod=viewthread&tid=12776'>使用教程</a></font></p> 
					<p style="margin-bottom: 5px;"><font size="2.0">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;“key神－万能密码重设工具”为<a href='http://zhanzhang.anquan.org'>安全联盟站长平台</a>为了解决长期困恼站长们的一个世纪难题：“我忘记后台密码了！怎么办？”而诞生的！站长只要简单下载上传到网站跟目录下，输入密码即可修改网站后台管理员密码。站长再也不用担心忘记密码了！</font><br>
	<font color="red" size="2.0">&nbsp;一些注意事项：</font><br><font color="red" size="1.0">&nbsp;&nbsp;1、本程序在重设密码后将自动强制'卸载',如果卸载不成功，请务必手工删除！<br>
		&nbsp;&nbsp;2、安全联盟提醒您，重设密码时请使用“复杂”、“强壮”密码!<br></font><font size="1.0">
		&nbsp;&nbsp;3、为了兼容各种CMS、博客、论坛、商城程序，本程序采用了“云端”更新技术。目前我们支持如下程序: $support_cms <br>
		&nbsp;&nbsp;4、如果您的网站使用的程序我们不支持，请直接到我们QQ群与我们联系：<br>&nbsp;&nbsp;&nbsp;&nbsp; 群1：126020287、群2：154169836、群3：25614719<br>
		&nbsp;&nbsp;</font></p>
					<div id="footer">
					$share_html
					</div>
					<p style="margin-top: 0;">万能密码重设工具(key神) &nbsp;
					版权所有 &copy;2007-2013 <a href="http://www.knownsec.com/">
					北京知道创宇信息技术有限公司</a></font></td></tr><tr style="font-size: 0px; line-height: 0px; spacing: 0px; padding: 0px; background-color: #698CC3">
					</p></div>
					<script>
						function bind(el, type, fn, useCapture){ 
							if (window.addEventListener) { 
								el.addEventListener(type, function(){ 
									fn.apply(el, arguments);//始终将this指向DOM 
								}, useCapture); 
							} 
							else if (window.attachEvent) { 
								el.attachEvent('on' + type, function(){ 
									fn.apply(el, arguments);//始终将this指向DOM，也可以用call(el,agruments)  
								});
							} 
						} 
					
						/*---------------------------
						功能:停止事件冒泡
						---------------------------*/
						function stopBubble(e) {
							//如果提供了事件对象，则这是一个非IE浏览器
							if ( e && e.stopPropagation )
								//因此它支持W3C的stopPropagation()方法
								e.stopPropagation();
							else
								//否则，我们需要使用IE的方式来取消事件冒泡
								window.event.cancelBubble = true;
						}
						//阻止浏览器的默认行为
						function stopDefault( e ) {
							//阻止默认浏览器动作(W3C)
							if ( e && e.preventDefault )
								e.preventDefault();
							//IE中阻止函数器默认动作的方式
							else
								window.event.returnValue = false;
							return false;
						}
						
						var share_url = document.getElementById('share_url');
						var share_txt = document.getElementById('share_txt');
						var share_tit = document.getElementById('share_tit');
						var share_pic = document.getElementById('share_pic');
						
						function shareTo(type){
							var shareURL = {
								'qq': '"http://connect.qq.com/widget/shareqq/index.html?site='+encodeURI('安全联盟')+'&pics="+(_pic?_pic.join("|"):"")+"&"',
								'tencent': '"http://share.v.t.qq.com/index.php?c=share&a=index&assname=discuzaddon&appkey=08d170f431bb4736bac7dcf67c6d0995&pic="+(_pic?_pic.join("|"):"")+"&"',
								'qzone': '"http://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?site='+encodeURI('安全联盟')+'&pics="+(_pic?_pic.join("|"):"")+"&"',
								'pengyou': '"http://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?site='+encodeURI('安全联盟')+'&to=pengyou&pics="+(_pic?_pic.join("|"):"")+"&"',
								'sina': '"http://service.weibo.com/share/share.php?pic="+(_pic?_pic[0]:"")+"&"'
							};
							var _url = encodeURIComponent(share_url.innerHTML);
							var _pic = function(area) {
								if(!area) {
									return '';
								}
								var _imgarr = area.getElementsByTagName('li');
								var _srcarr = [];
								for (var i = 0; i < _imgarr.length; i++) {
									_srcarr.push(encodeURIComponent(_imgarr[i].innerHTML));
								}
								return _srcarr;
							} (share_pic);
							url = eval(shareURL[type]);
							var _t = share_txt.innerHTML;
							if(_t.length > 120){
								_t= _t.substr(0,117)+'...';
							}
							_t = '%23' + encodeURI(share_tit.innerHTML) + '%23 ' + encodeURI(_t);
							var _u = url + 'url=' + _url + '&title=' + _t;
							if(type == 'qq') {
								_u += '&desc=' + _t;
							}
							var left = (window.screen.width - 700) / 2;
							var top = (window.screen.height - 680) / 2;
							window.open( _u,'', 'width=740,height=680,left='+left+',top='+top+',toolbar=0,menubar=0,scrollbars=0,location=1,resizable=0,status=0' );							
						};
					</script>
					</body>
					</html>
HTML;
			exit;
			break;
		
		case "index":

			templates("header", $_POST);
			if(!empty($_POST['submit'])){
				showinfo($info,'');
				templates("footer",array("suc"=>true));
			}else{
                echo '
                    <form action="" method="post">
					<div class="con">
						<table>'.$option.'<tr><th width="25%">请输入密码：</th><td width="75%"><input class="textinput" type="text" name="g_manager_pass" size="25"></td></tr></table>
						<input type="submit" class="btn btn-success" name="submit" value="提 &nbsp; 交">
					</div>
                    </form>';
				templates("footer",array("suc"=>false));
			}
			break;

	}
}

function showinfo($message,$title = ''){	
		$title = $title ? "<h4>$title</h4>" : "";
		$message = "$title<div class=\"con\" style=\"padding-left: 20px;\"><table class=\"result\"><tr><th align=\"center\">key神提示您：找回密码成功!</th></tr><tr><td><script>alert('$message');document.getElementsByTagName('input')[0].click();</script></td></tr></table></div>";
		echo $message;
}

function errorpage($message, $title=""){
	$title = $title ? "<h4>$title</h4>" : "";
    templates('header', array());
    echo <<<HTML
		$title<div class="con"><table class="result"><tr><th align="center">key神提示您：</th></tr><tr><td>$message</td></tr></table></div>
HTML;
    templates('footer', array());
}
