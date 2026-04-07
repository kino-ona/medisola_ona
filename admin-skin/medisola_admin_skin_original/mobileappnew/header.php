<?php
/**
 * 관리자 상단
 *
 * @copyright ⓒ 2016, NHN godo: Corp.
 * @link      http://www.godo.co.kr
 * @author    Shin Donggyu <artherot@godo.co.kr>
 */
?>
<a class="sr-only" href="#content">Skip navigation</a><!-- 메인 네비게이션 출력 시작 -->
<nav id="lnb" class="navmenu navmenu-inverse navmenu-fixed-left" role="navigation">
    <!-- <a class="navmenu-brand" href="#">Brand</a> -->
    <ul class="nav navmenu-nav">
        <li><a href="https://mobileapp.godo.co.kr:443/new/app/login.php" class="icon-login">관리상점리스트</a></li>
        <li><a href="https://mobileapp.godo.co.kr:443/new/app/my/my-hosting.php" class="icon-hosting">MY호스팅</a></li>
        <li class="<?=$nav_active[0];?>"><a href="/mobileapp/mobileapp_main.php" class="icon-today" id="main_link">메인</a></li>
        <li class="<?=$nav_active[1];?>"><a href="/mobileapp/mobileapp_order_list.php" class="icon-order ">주문관리</a></li>
        <li class="<?=$nav_active[2];?>"><a href="/mobileapp/mobileapp_goods_list.php" class="icon-goods ">상품관리</a></li>
        <li class="<?=$nav_active[3];?>"><a href="/mobileapp/mobileapp_goods_register.php" class="icon-new-goods ">상품등록<i class="icon-app"></i></a></li>
        <li class="<?=$nav_active[4];?>"><a href="/mobileapp/mobileapp_member_list.php" class="icon-member ">회원관리</a></li>
        <li class="<?=$nav_active[5];?>"><a href="/mobileapp/mobileapp_board_list.php" class="icon-board ">게시판관리</a></li>
        <li class="<?=$nav_active[6];?>"><a href="/mobileapp/mobileapp_statistics_visit.php" class="icon-access ">방문자분석</a></li>
        <li class="<?=$nav_active[7];?>"><a href="/mobileapp/mobileapp_statistics_order.php" class="icon-sales ">주문분석</a></li>
        <li class="<?=$nav_active[8];?>"><a href="/mobileapp/mobileapp_statistics_sale.php" class="icon-rank ">판매순위분석</a></li>
        <li class="<?=$nav_active[9];?>"><a href="https://mobileapp.godo.co.kr:443/new/app/notice.php" class="icon-notice ">공지사항</a></li>
        <li class="<?=$nav_active[10];?>"><a href="/mobileapp/mobileapp_config.php" class="icon-notification ">알림설정</a></li>
    </ul>
</nav>
<!-- 메인 네비게이션 출력 끝 -->

<div id="wrapper">
    <nav class="navbar navbar-default navbar-fullwidth navbar-static-top navbar-hide" role="navigation">
        <div class="container-fluid">
            <div class="navbar-header">
                <h1 class="navbar-brand"><?=$nav_title;?></h1>
            </div><!-- /.navbar-header -->

            <!-- 좌측 버튼 리스트 -->
            <ul class="navbar-top-links nav navbar-nav navbar-left">
                <li>
                    <button class="btn gd-navbar-btn navbar-menu" data-toggle="offcanvas" data-target="#lnb" data-canvas="#wrapper">
                        <span class="navbar-menu-name">메뉴</span>
                    </button>
                </li>
                <li><a href="#" class="gd-navbar-btn navbar-back">뒤로가기</a></li>
            </ul>

            <!-- 우측 버튼 리스트 -->
            <ul class="navbar-top-links nav navbar-nav navbar-right">
                <li>
                    <a href="https://mobileapp.godo.co.kr:443/new/app/notice.php" title="공지사항" class="gd-navbar-btn navbar-notice">
                        <span class="navbar-menu-name">공지</span><span class="badge h-skip">0</span>
                    </a>
                </li>
                <li><a href="/mobileapp/mobileapp_main.php" class="gd-navbar-btn navbar-home">메인으로</a></li>
            </ul>
            <!-- <a href="notice.php" title="공지사항" class="gd-navbar-btn navbar-notice"><span class="text_menu">공지</span><span class="badge"></span></a> -->
        </div>
    </nav>