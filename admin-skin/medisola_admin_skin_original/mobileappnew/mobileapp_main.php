<?php if ($isAlarm == 'T') { ?>
<h2 class="section-header section-header1 mgb0">알림</h2>
<div class="container-default">
    <table class="table table-bordered mgb0">
        <colgroup>
            <col/><col/>
        </colgroup>
        <tbody class="rowlink" id="mobileapp_alarmArea">
        <?php
        foreach ($aAlarm as $key => $val) {
            if ($val['count'] > 0) {
        ?>
        <tr>
            <?php if ($key == 'order' && $val['name'] == '주문') { ?>
            <td height="36px" class="section-header1" style="padding-left: 50px; !important;" id="mobileapp_alarmTdorder" data-sdate="<?=$val['edate']?>" data-edate="<?=date('Ymd')?>">
                <!--a href="/mobileapp/mobileapp_order_list.php"-->
            <?php } else { ?>
            <td height="36px" class="section-header1" style="padding-left: 50px;" id="mobileapp_alarmTd<?=$val['sno']?>">
                <!--a href="/mobileapp/mobileapp_board_article.php?sno=<?= $val['sno'] ?>"-->
            <?php } ?>
                <div style="margin: 0 10px 0 10px;">
                    <?= $val['name'] ?> <strong class="text-primary-app"><?= $val['count'] ?>건</strong> <img src="/admin/gd_share/img/mobileapp/icon/ico_new.png" width="20"><span class="pull-right">></span>
                </div><!--/a-->
            </td>
        </tr>
        <?php
            }
        }
        ?>
        </tbody>
    </table>
</div>
<?php } ?>

<?php if ($mainServiceOrderAccess != '') { ?>
<h2 class="section-header section-header1 <?=($isAlarm == 'T' ? 'mg0' : 'mgb0');?>">주문관리</h2>
<div class="container-default">
    <table class="table table-bordered">
        <colgroup>
            <col style="width: 25%;"/><col/>
        </colgroup>
        <tbody class="rowlink">
        <?php if (empty($eachOrderStatus) === false) { ?>
            <?php
            $orderList = [];
            $eachOrderStatus = array_merge($eachOrderStatus, array_fill(0, (8 - count($eachOrderStatus)), ''));
            foreach ($eachOrderStatus as $index => $orderStatus) {
                if ((($index + 1) % 4) === 1) {
                    $orderList[] = '<tr class="text-center" height="80px">';
                }

                if (empty($orderStatus)) {
                    $orderList[] = '<td style="width: 25% !important;">&nbsp;</td>';
                } else {
                    $tempLink = '/mobileappnew/mobileapp_order_list.php?';
                    $aExplodeLink = explode('php?', $orderStatus['link']);
                    $aExplodeLink2 = explode('&', $aExplodeLink[1]);
                    foreach ($aExplodeLink2 as $val) {
                        $aExplodeLink3 = explode('=', $val);
                        if ($aExplodeLink3[0] == 'treatDate[]') {
                            $tempLink .= $aExplodeLink3[0] . '=' . str_replace('-', '', $aExplodeLink3[1]) . '&';
                        }
                        if ($aExplodeLink3[0] == 'orderStatus[]') {
                            $tempLink .= 'status_mode=' . $aExplodeLink3[1];
                        }
                    }
                    $orderList[] = '<td style="width: 25% !important;"><a href="' . $tempLink . '">';
                    $orderList[] = '<span class="center-block"><strong class="text-primary-app">' . number_format($orderStatus['count']) . '</strong>';
                    $orderList[] = '<span class="center-block">' . $orderStatus['name'] . '</span>';
                    $orderList[] = '</a></td>';
                }

                if ((($index + 1) % 4) === 0) {
                    $orderList[] = '</tr>';
                }
            }

            if (count($eachOrderStatus) % 4 > 0) {
                $orderList[] = '</tr>';
            }
            array_pop($orderList);
            echo implode('', $orderList);
            ?>
        <?php } else { ?>
        <tr class="text-center" height="80px">
            <td>
                <p class="no-data">주문내역이 없습니다.</p>
            </td>
        </tr>
        <?php } ?>
        </tbody>
    </table>
    <p class="mgb10"><strong class="text-primary-app" style="font-weight:bold; color:#ec534b; margin-left: 10px">ⓘ</strong> 최근 7일 내 주문관리 현황입니다.</p>
</div>
<?php } ?>

<?php if ($mainServiceBoardAccess != '') { ?>
<h2 class="section-header section-header1 mg0">문의/답변관리</h2>
<div class="container-default">
    <table class="table table-bordered">
        <colgroup>
            <col style="width: 33%;"/><col/>
        </colgroup>
        <tbody class="rowlink">
        <tr class="text-center" height="80px">
            <?php
            $boardList = [];
            $boardDataKeys = array_keys($boardData);
            $boardLinkKeys = array_keys($boardLink);
            for ($i = 0; $i < 3; $i++) {
                if (empty($boardDataKeys[$i])) {
                    $boardList[] = '<td style="width: 33%;"></td>';
                    continue;
                }

                $boardList[] = '<td style="width: 33%;"><a href="' . $boardLink[$boardLinkKeys[$i]] . '"><span class="center-block">';
                if ($i == 0) {
                    $boardList[] = '<strong class="text-primary-app">' . $boardData[$boardDataKeys[$i]]['count']. '</strong>건</span><span class="center-block">' . $boardData[$boardDataKeys[$i]]['name'] . '</span></a></td>';
                } else {
                    $boardList[] = '<strong class="text-primary-app">' . $boardData[$boardDataKeys[$i]]['na'] . '</strong>/';
                    $boardList[] = $boardData[$boardDataKeys[$i]]['count'] . '건</span><span class="center-block">' . $boardData[$boardDataKeys[$i]]['name'] . '</span></a></td>';
                }

            }
            echo implode('', $boardList);
            ?>
        </tr>
        </tbody>
    </table>
    <p class="mgb10"><strong class="text-primary-app" style="font-weight:bold; color:#ec534b; margin-left: 10px">ⓘ</strong> 최근 7일 내 문의/답변관리 현황입니다.</p>
</div>
<?php } ?>

<?php if ($mainServicePresentationAccess != '') { ?>
<?php if ($mainStatisticsAccess['order'] > 0 || $mainStatisticsAccess['sales'] > 0) { ?>
<h2 class="section-header section-header1 mg0">매출현황</h2>
<div class="container-default">
    <table class="table table-bordered mg0">
        <colgroup>
            <col style="width: 25%; height: 36px;"/><col/>
        </colgroup>
        <tbody >
        <tr>
            <th style="width: 25%; height:36px; padding-bottom: 0px; padding-top: 0px;"></th>
            <th style="width: 25%; height:36px; padding-bottom: 0px; padding-top: 0px;"><?=$saleData['title'][0];?></th>
            <th style="width: 25%; height:36px; padding-bottom: 0px; padding-top: 0px;"><?=$saleData['title'][1];?></th>
            <th style="width: 25%; height:36px; padding-bottom: 0px; padding-top: 0px;"><?=$saleData['title'][2];?></th>
        </tr>
        <?php if ($mainStatisticsAccess['sales'] > 0) { ?>
        <tr class="text-center">
            <td style="height:36px;">매출금액</td>
            <td><?=$saleData['sale'][0];?></td>
            <td><?=$saleData['sale'][1];?></td>
            <td><?=$saleData['sale'][2];?></td>
        </tr>
        <?php } ?>
        <?php if ($mainStatisticsAccess['order'] > 0) { ?>
        <tr class="text-center">
            <td style="height:36px;">구매건수</td>
            <td><?=$saleData['count'][0];?></td>
            <td><?=$saleData['count'][1];?></td>
            <td><?=$saleData['count'][2];?></td>
        </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
<?php } ?>
<?php if ($mainStatisticsAccess['member'] > 0 || $mainStatisticsAccess['visit'] > 0) { ?>
<h2 class="section-header section-header1 mg0">회원현황</h2>
<div class="container-default">
    <table class="table table-bordered mg0">
        <colgroup>
            <col style="width: 25%;"/><col/>
        </colgroup>
        <tbody >
        <tr>
            <th style="width: 25%; height:36px; padding-bottom: 0px; padding-top: 0px;"></th>
            <th style="width: 25%; height:36px; padding-bottom: 0px; padding-top: 0px;"><?=$memberData['title'][0];?></th>
            <th style="width: 25%; height:36px; padding-bottom: 0px; padding-top: 0px;"><?=$memberData['title'][1];?></th>
            <th style="width: 25%; height:36px; padding-bottom: 0px; padding-top: 0px;"><?=$memberData['title'][2];?></th>
        </tr>
        <?php if ($mainStatisticsAccess['member'] > 0) { ?>
        <tr class="text-center">
            <td style="height:36px;">신규회원</td>
            <td><?=$memberData['new'][0];?></td>
            <td><?=$memberData['new'][1];?></td>
            <td><?=$memberData['new'][2];?></td>
        </tr>
        <?php } ?>
        <?php if ($mainStatisticsAccess['visit'] > 0) { ?>
        <tr class="text-center">
            <td style="height:36px;">방문자수</td>
            <td><?=$memberData['visit'][0];?></td>
            <td><?=$memberData['visit'][1];?></td>
            <td><?=$memberData['visit'][2];?></td>
        </tr>
        <?php } ?>
        <?php if ($mainStatisticsAccess['member'] > 0) { ?>
        <tr class="text-center">
            <td style="height:36px;">전체회원</td>
            <td><?=$memberData['total'][0];?></td>
            <td><?=$memberData['total'][1];?></td>
            <td></td>
        </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
<?php } ?>
<?php } ?>

<a href="https://mobileapp.godo.co.kr/new2/app/notice.php">
    <h2 class="section-header section-header1 mg0">공지사항 <span class="pull-right text-graylighter cursor-p" style="font-size:12px; margin-top:4px">더보기 ><span></h2>
</a>
<div class="container-default">
    <table class="table table-bordered mgb0">
        <colgroup>
            <col/><col/>
        </colgroup>
        <tbody class="rowlink" id="mobileapp_mainNoticeArea">

        </tbody>
    </table>
</div>
<h2 class="section-header section-header1 mg0">이용 정보</h2>
<div class="container-default">
    <ul class="use-info">
        <li>
            <span style="margin-left: 5px;">쇼핑몰솔루션</span>
            <span class="pull-right text-graylighter" style="margin-right: 5px;">고도몰5 <?=$useInfo['ecKind'];?></span>
        <li>
        <li>
            <span style="margin-left: 5px;">쇼핑몰URL</span>
            <span class="pull-right text-graylighter" style="margin-right: 5px;"><?=$useInfo['mallDomain'];?></span>
        <li>
        <li>
            <span style="margin-left: 5px;">사용기간</span>
            <span class="pull-right text-graylighter" style="margin-right: 5px;"><?=$useInfo['useSdate'];?> ~ <?=$useInfo['useEdate'];?></span>
        <li>
        <li>
            <span style="margin-left: 5px;">남은기간</span>
            <span class="pull-right text-graylighter" style="margin-right: 5px;"><?=$useInfo['limitDate'];?>일</span>
        <li>
        <!--li>
            <span style="margin-left: 5px;">상품</span>
            <span class="pull-right text-graylighter" style="margin-right: 5px;">971 개 (무제한)</span>
        <li-->
        <li>
            <span style="margin-left: 5px;">용량(사용량/제공량)</span>
            <span class="pull-right text-graylighter" style="margin-right: 5px;"><?=$useInfo['usedDisk'];?> / <?=$useInfo['supplyDiskMb'];?>(<?=$useInfo['supplyDisk'];?>)</span>
        <li>
    </ul>
</div>

<h2 class="section-header section-header1 mg0">앱 정보</h2>
<div class="container-default">
    <div class="use-info">
        <span style="margin-left: 5px;">앱 버전</span>
        <span class="pull-right text-graylighter" style="margin-right: 5px;"><script>var version = navigator.userAgent.match(/appVersion\/(.*?)\s/);document.write(version[1]);</script></span>
    </div>
</div>