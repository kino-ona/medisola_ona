<?php
/**
 * 관리자앱 전용 레이아웃
 * @author qnibus
 * @version 1.0
 * @since 1.0
 * @copyright Copyright (c), NHN godo: corp.
 */

/*
 * 앱으로부터의 접속을 체크 후 native 기능을 정의 및 사용
 */
$device_uid = \Cookie::get('device_uid');
$cordovaFile = $cordovaPluginFile = $cordovaCameraProcessFile = '';
if(trim($device_uid) !== ''){
    $Android = stripos(\Request::server()->get('HTTP_USER_AGENT'), "Android");
    if((int)$Android > 0){
        $cordovaFile = 'cordova.js?ts='.time();
    }
    else {
        $cordovaFile = 'cordova_ios.js?ts='.time();
    }

    //상품관련 페이지 플러그인 로드
    $mobileapp_pageName = basename(\Request::server()->get('PHP_SELF'));
    if($mobileapp_pageName === 'mobileapp_goods_list.php' || $mobileapp_pageName === 'mobileapp_goods_register.php'){
        if((int)$Android > 0) {
            $cordovaPluginFile = 'cordova_plugins.js?ts=' . time();
        } else {
            $cordovaPluginFile = 'cordova_plugins_ios.js?ts=' . time();
        }

        if($mobileapp_pageName === 'mobileapp_goods_register.php'){
            $cordovaCameraProcessFile = 'mobileapp_goodsImage.js?ts='.time();
        }
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ko" xml:lang="ko">
<head>
    <title>쇼핑몰 관리자앱 - 고도몰5</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="description" content="" />
    <meta name="author" content="nokoon" />
    <meta name="HandheldFriendly" content="True">
    <meta name="MobileOptimized" content="320">
    <meta name="viewport" content="minimal-ui, width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
    <meta http-equiv="cleartype" content="on">

    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="https://mobileapp.godo.co.kr:443/new/assets/ico/apple-touch-icon-144-precomposed.png" />
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="https://mobileapp.godo.co.kr:443/new/assets/ico/apple-touch-icon-114-precomposed.png" />
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="https://mobileapp.godo.co.kr:443/new/assets/ico/apple-touch-icon-72-precomposed.png" />
    <link rel="apple-touch-icon-precomposed" href="https://mobileapp.godo.co.kr:443/new/assets/ico/apple-touch-icon-57-precomposed.png" />
    <link rel="shortcut icon" sizes="196x196" href="https://mobileapp.godo.co.kr:443/new/assets/ico/favicon-196.png">
    <link rel="shortcut icon" href="https://mobileapp.godo.co.kr:443/new/assets/ico/favicon.png" />

    <meta name="msapplication-TileImage" content="https://mobileapp.godo.co.kr:443/new/assets/ico/apple-touch-icon-144-precomposed.png">
    <meta name="msapplication-TileColor" content="#222222">

    <link rel="canonical" href="https://www.nhn-commerce.com/" >

    <meta name="mobile-web-app-capable" content="yes">

    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="">

    <!-- Latest compiled and minified CSS -->
    <link type="text/css" href="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'css/mobileappnew/bootstrap.min.css')?>" rel="stylesheet"/>
    <link type="text/css" href="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'css/mobileappnew/gd-common.css')?>" rel="stylesheet"/>
    <link type="text/css" href="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'css/mobileappnew/gd5-mobileapp-style.css?ts='.time())?>" rel="stylesheet"/>
    <link type="text/css" href="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'css/mobileappnew/bootstrap-modal-carousel.min.css')?>" rel="stylesheet" />
    <?php
    $headerStyle = gd_isset($headerStyle);
    if (is_array($headerStyle)) {
        foreach ($headerStyle as $v) { ?>
            <link type = "text/css" href = "<?=gd_set_browser_cache($v); ?>" rel = "stylesheet" />
            <?php
        }
    }
    ?>

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->

    <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/mobileappnew/jquery.min.js?ts='.time())?>"></script>
    <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/jquery/jquery-cookie/jquery.cookie.js?ts='.time())?>"></script>
    <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/mobileappnew/bootstrap.js')?>"></script>

    <!-- 앱으로 접속시 관련 cordova 로드 -->
    <?php if($cordovaFile){ ?>
        <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/mobileappnew/'.$cordovaFile)?>"></script>
    <?php } ?>
    <!-- 상품관련 페이지 cordova 플러그인 로드 -->
    <?php if($cordovaPluginFile){ ?>
        <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/mobileappnew/'.$cordovaPluginFile)?>"></script>
    <?php } ?>
    <!-- cordova 카메라/갤러리 기능 사용 -->
    <?php if($cordovaCameraProcessFile){ ?>
        <script type="text/javascript" src="<?=gd_set_browser_cache(PATH_ADMIN_GD_SHARE . 'script/mobileappnew/'.$cordovaCameraProcessFile)?>"></script>
    <?php } ?>

    <?php
    $headerScript = gd_isset($headerScript);
    if (is_array($headerScript)) {
        foreach ($headerScript as $url) { ?>
            <script type="text/javascript" src="<?=($url)?>"></script>
            <?php
        }
    } ?>
    <script type="text/javascript">
        <!--
        $(document).ready(function() {
            $(".navbar-top-links").click(function(e){
                if($(".navbar").is(".navbar-hide") === true ) {
                    $(".navbar-hide").removeClass("navbar-hide");
                    $(".section-header1").css("margin","0 0 10px");
                } else {
                    setTimeout(function(){
                        $(".navbar").addClass("navbar-hide");
                        $(".section-header1").css("margin","44px 0 10px");
                    },400);
                }

            });

            $("#wrapper").click(function(e){
                if($(".canvas-slid").is(".canvas-slid") === true ) {
                    setTimeout(function(){
                        $(".navbar").addClass("navbar-hide");
                        $(".section-header1").css("margin","44px 0 10px");
                    },400);
                }
            });

            $('.modal')
                .on('shown', function(){
                    //$('#wrapper').on('touchmove', false);
                    $('body').css({overflow:'visible'}).bind('touchmove', function(e){e.preventDefault()});
                })
                .on('hidden', function(){
                    $('body').unbinde('touchmove', function(e){e.preventDefault()});
                });

            var select = $(".select-opt");

            select.change(function(){
                var select_name = $(this).children("option:selected").text();
                $(this).siblings("label").text(select_name);
            });

            // 네비 백버튼
            $(document).on('click', '.navbar-back', function(e) {
                e.preventDefault();
                history.go(-1);
            });

            if ($.cookie('isAlarm') != 'undefined' && $.cookie('isAlarm') > 0) {
                $('#main_link').html('메인<i class="icon-alarm-new"></i>');
            }

            function onLoad() {
                document.addEventListener("deviceready", onDeviceReady, false);
            }

            function onDeviceReady() {
                document.addEventListener("backbutton", onBackKeyDown, false);
                // 안드로이드 백버튼 이벤트
                $(document).bind("backbutton.android", function(e) {
                    window.plugins.webBridge.callbackClass('gdBackButton',function (successCallback) {

                    }, function (errorCallback) {

                    },'backButton');
                });

                //상품 등록페이지에서 사용
                if(typeof onDeviceReadyGoods == 'function'){
                    onDeviceReadyGoods();
                }
            }

            function onBackKeyDown() {
                history.go(-1);
            }

            /*
            * 페이지간의 리스트 호환(목록유지)를 위한 url, parameter 를 storage 에 저장
            * 사용처 : 상품리스트, 회원리스트, 주문리스트
            */
            function setMobileappPageStorage() {
                var mobileapp_modeParam = null;
                var mobileapp_goodsNoParam = null;
                var mobileapp_memNoParam = null;
                var mobileapp_modeParamRexp = new RegExp('[\?&]mode=([^&#]*)').exec(window.location.href);
                var mobileapp_goodsNoParamRexp = new RegExp('[\?&]goodsNo=([^&#]*)').exec(window.location.href);
                var mobileapp_memNoParamRexp = new RegExp('[\?&]memNo=([^&#]*)').exec(window.location.href);
                if (mobileapp_modeParamRexp !== null) {
                    mobileapp_modeParam = mobileapp_modeParamRexp[1];
                }
                if (mobileapp_goodsNoParamRexp !== null) {
                    mobileapp_goodsNoParam = mobileapp_goodsNoParamRexp[1];
                }
                if (mobileapp_memNoParamRexp !== null) {
                    mobileapp_memNoParam = mobileapp_memNoParamRexp[1];
                }

                var mobileapp_pathName = window.location.pathname;
                var mobileapp_fileName = mobileapp_pathName.substring(mobileapp_pathName.lastIndexOf('/') + 1);

                localStorage.setItem("mobileapp-page", mobileapp_fileName);
                localStorage.setItem("mobileapp-page-parameter-mode", mobileapp_modeParam);
                localStorage.setItem("mobileapp-page-parameter-goodsNo", mobileapp_goodsNoParam);
                localStorage.setItem("mobileapp-page-parameter-memNo", mobileapp_memNoParam);
            }

            setMobileappPageStorage();

            onLoad();
        });
        //-->
    </script>
</head>

<body class=" today shop" data-deviceUid="<?= $device_uid; ?>">

<?php include($layoutContent); ?>

<?php include($layoutFooter); ?>

<div id="ajax_loading" class="ajax_loading"></div>

<script type="text/javascript">
    <!--
    $(document).ready(function () {
        <?= gd_isset($menuAccessAuth); ?>
    });
    //-->
</script>
</body>
</html>
