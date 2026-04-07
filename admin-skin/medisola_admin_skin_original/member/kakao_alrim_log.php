<form id="frmSearch" method="get" class="js-form-enter-submit">
    <input type="hidden" name="detailSearch" value="y"/>

    <div class="page-header js-affix">
        <h3><?php echo end($naviMenu->location); ?>
        </h3>
    </div>
<?php
if (gd_is_plus_shop(PLUSSHOP_CODE_KAKAOALRIMLUNA) === true) {
    ?>
    <ul class="nav nav-tabs mgb30">
        <li role="presentation" class="active">
            <a href="#biz" aria-controls="biz" role="tab" data-toggle="tab">비즈엠</a>
        </li>
        <li role="presentation">
            <a href="#luna" aria-controls="luna" role="tab" data-toggle="tab">루나소프트</a>
        </li>
    </ul>
    <?php
}
?>
    <div class="table-title gd-help-manual">
        알림톡 발송 내역 검색
    </div>

    <div class="search-detail-box">
        <table class="table table-cols">
            <colgroup>
                <col class="width-sm"/>
                <col />
            </colgroup>
            <tbody>
            <tr>
                <th>검색어</th>
                <td class="form-inline">
                    <?php echo gd_select_box(null, 'key', ['contents' => '템플릿내용', 'subject' => '템플릿명', ], '', gd_isset($search['key'])); ?>
                    <input type="text" name="keyword" value="<?php echo $search['keyword']; ?>" class="form-control width-md" placeholder="키워드를 입력해 주세요."/>
                </td>
            </tr>
            <tr>
                <th>기간검색</th>
                <td>
                    <div class="form-inline">
                        <?php echo gd_select_box(
                            'treatDateFl', 'treatDateFl', [
                            'regDt'     => '발송일',
                            'reserveDt' => '예약일',
                        ], '', $search['treatDateFl']
                        ); ?>
                        <div class="input-group js-datepicker">
                            <input type="text" name="treatDate[start]" value="<?php echo $search['treatDate']['start']; ?>" class="form-control width-xs" placeholder="수기입력 가능"/>
                            <span class="input-group-addon"><span class="btn-icon-calendar"></span></span>
                        </div>
                        ~
                        <div class="input-group js-datepicker">
                            <input type="text" name="treatDate[end]" value="<?php echo $search['treatDate']['end']; ?>" class="form-control width-xs" placeholder="수기입력 가능"/>
                            <span class="input-group-addon"><span class="btn-icon-calendar"></span></span>
                        </div>
                        <div class="btn-group js-dateperiod" data-toggle="buttons" data-target-name="treatDate">
                            <label class="btn btn-white btn-sm hand">
                                <input type="radio" value="0">
                                오늘
                            </label>
                            <label class="btn btn-white btn-sm hand">
                                <input type="radio" value="6" checked="checked">
                                7일
                            </label>
                            <label class="btn btn-white btn-sm hand">
                                <input type="radio" value="14">
                                15일
                            </label>
                            <label class="btn btn-white btn-sm hand">
                                <input type="radio" value="29">
                                1개월
                            </label>
                            <label class="btn btn-white btn-sm hand">
                                <input type="radio" value="89">
                                3개월
                            </label>
                            <label class="btn btn-white btn-sm hand">
                                <input type="radio" value="364">
                                1년
                            </label>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <th>발송 상태</th>
                <td class="form-inline">
                    <input type="hidden" name="sendFl" value="kakao">
                    <input type="hidden" name="smsType" value="">
                    <?php echo gd_select_box(null, 'sendStatus', $smsSendStatus, '', $search['sendStatus'], '=전체보기='); ?>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <div class="table-btn">
        <input type="submit" value="검색" class="btn btn-lg btn-black"/>
    </div>

    <div class="table-header">
        <div class="pull-left">
            <?= gd_display_only_search_result($page->recode['total'], '개'); ?>
        </div>
        <div class="pull-right">
            <ul>
                <li>
                    <?php echo gd_select_box('sort', 'sort', $search['sortList'], null, $search['sort'], null); ?>
                </li>
                <li>
                    <?php echo gd_select_box_by_page_view_count($search['pageNum']); ?>
                </li>
            </ul>
        </div>
    </div>
</form>

<table class="table table-rows table-fixed member-sms-log">
    <colgroup>
        <col class="width5p"/>
        <col class="width10p"/>
        <col class="width10p"/>
        <col class="width10p"/>
        <col class="width35p"/>
        <col class="width10p"/>
        <col class="width10p"/>
        <col class="width10p"/>
    </colgroup>
    <thead>
    <tr>
        <th>번호</th>
        <th>발송유형</th>
        <th>차감포인트</th>
        <th>템플릿명</th>
        <th>템플릿내용</th>
        <th>발송(예약)일시</th>
        <th>발송건수</th>
        <th>발송상태</th>
    </tr>
    </thead>
    <tbody class="sms-log-list">
    <?php
    if (empty($logData) === false) {
        $listHtml = [];
        foreach ($logData as $data) {
            $isAutoSend = $data['smsType'] != 'user';

            // 발송자 정보
            $sender = json_decode($data['sender']);

            // 예약 발송 여부
            $reserveFl = '';
            if ($data['reserveDt'] !== '0000-00-00 00:00:00') {
                $reserveFl = '<br/><span class="text-orange-red">[예약발송]</span>';
            }

            // SMS 결과 수신 처리
            $smsSendResult = 'n';
            if ($data['sendStatus'] === 'r' && $data['receiverCnt'] < 100 && $data['reserveFl'] != 'y') {
                $smsSendResult = 'y';
            }

            // 실패경우에 일부 성공일때 추가 상태 표기
            if ($data['sendStatus'] == 'n' && $data['receiverCnt'] != $data['sendFailCnt']) {
                $sSendStatusAdd = '(일부)';
            } else {
                $sSendStatusAdd = '';
            }

            // 템플릿 버튼
            if (empty($data['templateButton']) === false) {
                $tmpBtnData = json_decode($data['templateButton'], true);
                foreach ($tmpBtnData as $bVal) {
                    $tmpData[] = '<span class="alrim-template-button">' . $bVal['name'] . '</span>';
                }
                $data['contents'] .= '<br/><div class="alrim-template-button-list">' . implode(' ', $tmpData) . '</div>';
                unset($tmpBtnData, $tmpData);
            }

            $listHtml[] = '<tr data-sno="' . $data['sno'] . '" data-sendkey="' . $data['smsSendKey'] . '" data-status="' . $data['sendStatus'] . '" data-api-yn="' . $smsSendResult . '">';
            $listHtml[] = '<td class="text-center number">' . number_format($page->idx--) . '</td>';
            $listHtml[] = '<td class="text-center">' . $smsSendType[$data['smsType']] . $reserveFl . '</td>';
            $listHtml[] = '<td class="text-center number">' . ($data['receiverCnt'] * $kakaoPoint) . '</td>';
            $listHtml[] = '<td class="text-center">' . $data['subject'] . '</td>';
            $listHtml[] = '<td style="word-break:break-all;word-wrap:break-word;overflow:hidden;" class="multirows">';
            $listHtml[] = '<div class="div-contents">' . nl2br($data['contents']) . '</div>';
            $listHtml[] = '</td>';
            if ($data['reserveDt'] !== '0000-00-00 00:00:00') {
                $listHtml[] = '<td class="text-center number multirows">' . str_replace(' ', '<br />', $data['reserveDt']) . '</td>';
            } else {
                $listHtml[] = '<td class="text-center number multirows">' . str_replace(' ', '<br />', $data['sendDt']) . '</td>';
            }
            $listHtml[] = '<td class="text-center number">';
            $listHtml[] = number_format($data['receiverCnt']);
            if ($data['sendStatus'] === 'y' || $data['sendStatus'] === 'n') {
                $listHtml[] = '<br />(' . number_format($data['sendSuccessCnt']) . ' / '. number_format($data['sendFailCnt']) . ')';
            }
            $listHtml[] = '</td>';
            $listHtml[] = '<td class="text-center number">';
            $listHtml[] = '<span class="sms-send-status-' . $data['sno'] . '">' . $smsSendStatus[$data['sendStatus']] . $sSendStatusAdd . '</span>';
            $listHtml[] = '<div><button type="button" class="btn btn-xs btn-gray btn-send-list">상세보기</button></div>';
            $listHtml[] = '</td>';
            $listHtml[] = '</tr>';
        }
        echo implode('', $listHtml);
    } else {
        echo '<tr><td colspan="8" class="no-data">SMS 발송내역이 없습니다.</td></tr>';
    }
    ?>
    </tbody>
</table>

<div class="center"><?php echo $page->getPage(); ?></div>

<script language="javascript" type="text/javascript">
    <!--
    $(document).ready(function () {
        $('.btn-modify').click(function (e) {
            layer_close();
            layer_sms_contents($(e.target).closest('tr').data('sno'));
        });

        $('.btn-send-list').click(function (e) {
            layer_close();
            layer_kakao_send_list($(e.target).closest('tr').data('sno'), $(e.target).closest('tr').data('sendkey'), $(e.target).closest('tr').data('status'));
        });

        $('select[name=\'sort\']').change({targetForm: '#frmSearch'}, member.page_sort);
        $('select[name=\'pageNum\']').change({targetForm: '#frmSearch'}, member.page_number);

        // SMS 발송 조건 / 문구 설정 탭
        $('.nav-tabs a').click(function (e) {
            e.preventDefault();
            $(this).tab('show');
            var hrefValue = $(this).prop('href');
            var arrHrefValue = hrefValue.split('#');
            var keyValue = arrHrefValue[1];
            if(keyValue == 'biz'){
                location.href = 'kakao_alrim_log.php';
            }else if(keyValue == 'luna'){
                location.href = 'kakao_alrim_luna_log.php';
            }
        });
    });
    //-->
</script>


