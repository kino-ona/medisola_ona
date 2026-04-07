<input type="hidden" id="kakaoId" name="kakaoId" value="<?php echo $kakaoSetting['plusId']?>"/>
<input type="hidden" id="kakaomode" name="kakaomode" value="<?php echo $mode?>"/>
<form id="frmSearch" method="get" class="js-form-enter-submit">
    <input type="hidden" name="detailSearch" value="y"/>

    <div class="page-header js-affix">
        <h3><?php echo end($naviMenu->location); ?>
        </h3>
        <div class="btn-group">
            <input type="button" id="layerTemplateRegist" value="+ 템플릿 등록" class="btn btn-red"/>
        </div>
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
        알림톡 템플릿 목록 검색
    </div>

    <div class="search-detail-box">
        <table class="table table-cols">
            <colgroup>
                <col class="width-sm"/>
                <col class="width-2xl"/>
                <col class="width-sm"/>
                <col/>
            </colgroup>
            <tbody>
            <tr>
                <th>검색어</th>
                <td colspan="3" class="form-inline">
                    <?php echo gd_select_box(null, 'key', ['all' => '=통합검색=', 'templateCode' => '템플릿코드', 'templateName' => '템플릿명', 'templateContent' => '템플릿내용', ], '', gd_isset($search['key'])); ?>
                    <input type="text" name="keyword" value="<?php echo $search['keyword']; ?>" class="form-control width-md" placeholder="키워드를 입력해 주세요."/>
                </td>
            </tr>
            <tr>
                <th>기간검색</th>
                <td colspan="3">
                    <div class="form-inline">
                        <?php echo gd_select_box(
                            'treatDateFl', 'treatDateFl', [
                            'regDt'     => '등록일',
                        ], '', $search['treatDateFl'], '' , 'style=display:none'
                        ); ?>
                        등록일
                        <div class="input-group js-datepicker">
                            <input type="text" name="treatDate[start]" value="<?php echo $search['treatDate']['start']; ?>" class="form-control width-xs" placeholder="수기입력 가능"/>
                            <span class="input-group-addon"><span class="btn-icon-calendar"></span></span>
                        </div>
                        ~
                        <div class="input-group js-datepicker">
                            <input type="text" name="treatDate[end]" value="<?php echo $search['treatDate']['end']; ?>" class="form-control width-xs" placeholder="수기입력 가능"/>
                            <span class="input-group-addon"><span class="btn-icon-calendar"></span></span>
                        </div>
                        <?= gd_search_date($search['searchPeriod'], 'treatDate', false) ?>
                    </div>
                </td>
            </tr>
            <tr>
                <th>템플릿 구분</th>
                <td class="form-inline">
                    <?php echo gd_select_box(null, 'smsType', ['all' => '=전체보기=', 'order' => '주문배송관련', 'member' => '회원관련', 'board' => '게시물등록 알림', ], '', $search['smsType']); ?>
                </td>
                <th>검수 상태</th>
                <td class="form-inline">
                    <?php echo gd_select_box(null, 'inspectionStatus', ['all' => '=전체보기=', 'REG' => '등록', 'REQ' => '심사요청', 'APR' => '승인', 'REJ' => '반려', 'NOT' => '사용불가'], '', $search['inspectionStatus']); ?>
                </td>
            </tr>
            <tr>
                <th>사용여부</th>
                <td colspan="3">
                    <label class="radio-inline">
                        <input type="radio" name="useFlag" value="all" <?php echo gd_isset($checked['useFlag']['all']); ?> />
                        전체
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="useFlag" value="y" <?php echo gd_isset($checked['useFlag']['y']); ?> />
                        사용함
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="useFlag" value="n" <?php echo gd_isset($checked['useFlag']['n']); ?> />
                        사용안함
                    </label>
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
            <?php echo gd_display_search_result($page->recode['total'], $page->recode['amount'], '개'); ?>
        </div>
        <div class="pull-right">
            <ul>
                <!--li>
                    <?php //echo gd_select_box('sort', 'sort', $search['sortList'], null, $search['sort'], null); ?>
                </li-->
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
        <col class="width15p"/>
        <col class="width20p"/>
        <col class="width5p"/>
        <col class="width10p"/>
        <col class="width10p"/>
        <col class="width5p"/>
        <col class="width5p"/>
        <col class="width5p"/>
    </colgroup>
    <thead>
    <tr>
        <th>번호</th>
        <th>템플릿코드</th>
        <th>템플릿구분</th>
        <th>템플릿명</th>
        <th>템플릿내용</th>
        <th>검수상태</th>
        <th>요청/답변</th>
        <th>등록일</th>
        <th>사용여부</th>
        <th>수정</th>
        <th>삭제</th>
    </tr>
    </thead>
    <tbody class="sms-log-list">
    <?php
    if (empty($templateData) === false) {
        $listHtml = [];
        foreach ($templateData as $data) {
            // 템플릿 코드 분할
            $aTemplateCode = explode('_', $data['templateCode']);

            if (count($aTemplateCode) > 2) {
                $sTemplateCode = $aTemplateCode[1] . '_' . $aTemplateCode[2];
                $iCodeKeyNum = 1;
            } else {
                $sTemplateCode = $data['templateCode'];
                $iCodeKeyNum = 0;
            }

            // 템플릿구분
            if ($aTemplateCode[$iCodeKeyNum] == 'order') {
                $sTempTemplateType = '주문배송관련';
            } elseif ($aTemplateCode[$iCodeKeyNum] == 'member') {
                $sTempTemplateType = '회원관련';
            } elseif ($aTemplateCode[$iCodeKeyNum] == 'board') {
                $sTempTemplateType = '게시물등록 알림';
            }

            // 검수상태
            if ($data['inspectionStatus'] == 'REG') {
                $sTempInspectionStatus = '등록';
            } elseif ($data['inspectionStatus'] == 'REQ') {
                $sTempInspectionStatus = '심사요청';
            } elseif ($data['inspectionStatus'] == 'APR') {
                $sTempInspectionStatus = '승인';
            } elseif ($data['inspectionStatus'] == 'REJ') {
                $sTempInspectionStatus = '반려';
            }

            // 템플릿 휴면 상태가 휴면인 경우 휴면해제 버튼 노출
            if ($data['status'] === 'S' && $data['dormant'] === '1') {
                $sTempInspectionStatus .= '<span class="text-orange-red">(휴면)</span><br /><span id="wakeUpTemplate' . $data['sno'] . '" data-code="' . $data['templateCode'] . '" style="cursor:pointer"><button type="button" class="btn btn-white btn-sm btnModify">휴면해제</button></span>';
            }

            // 템플릿 휴면 상태가 차단/휴면삭제 사용불가 처리
            if ($data['status'] === 'S' && $data['dormant'] === '9') {
                $sTempInspectionStatus = '사용불가';
                $sTempStatus = '-';
            }

            // 요청/문의
            if ($data['inspectionStatus'] == 'REG') {
                $sTempStatus = '<span id="requestTemplate' . $data['sno'] . '" data-code="' . $data['templateCode'] . '" style="cursor:pointer"><button type="button" class="btn btn-white btn-sm btnModify">검수요청</button></span>';
            } elseif ($data['inspectionStatus'] == 'REQ') {
                $sTempStatus = '<span id="commentTemplate' . $data['sno'] . '" data-code="' . $data['templateCode'] . '" style="cursor:pointer"><button type="button" class="btn btn-white btn-sm btnModify">답변확인</button></span>';
            } elseif ($data['inspectionStatus'] == 'APR') {
                $sTempStatus = '-';
            } elseif ($data['inspectionStatus'] == 'REJ') {
                $sTempStatus = '<span id="commentTemplate' . $data['sno'] . '" data-code="' . $data['templateCode'] . '" style="cursor:pointer"><button type="button" class="btn btn-white btn-sm btnModify">답변확인</button></span>';
            }

            // 사용여부
            if ($data['useFlag'] == 'y') {
                $sTempUseFlag = '사용함';
            } else {
                $sTempUseFlag = '사용안함';
            }

            // 수정
            if ($data['status'] == 'R' && ($data['inspectionStatus'] == 'REG' || $data['inspectionStatus'] == 'REJ')) {
                $sTempModify = '<span id="updateTemplate' . $data['sno'] . '" data-code="' . $data['templateCode'] . '" style="cursor:pointer"><button type="button" class="btn btn-white btn-sm btnModify">수정</button></span>';
            } else {
                $sTempModify = '';
            }

            // 삭제
            if ($data['status'] == 'R' && ($data['inspectionStatus'] == 'REG' || $data['inspectionStatus'] == 'REQ' || $data['inspectionStatus'] == 'REJ')) {
                $sTempDelete = '<span id="deleteTemplate' . $data['sno'] . '" data-code="' . $data['templateCode'] . '" style="cursor:pointer"><button type="button" class="btn btn-white btn-sm btnModify">삭제</button></span>';
            } else {
                //$sTempDelete = '<div><button type="button" class="btn btn-xs btn-gray btn-send-list">삭제</button></div>';
                $sTempDelete = '';
            }

            // 템플릿 버튼
            if (empty($data['templateButton']) === false) {
                $tmpBtnData = json_decode($data['templateButton'], true);
                foreach ($tmpBtnData as $bVal) {
                    $tmpData[] = '<span class="alrim-template-button">' . $bVal['name'] . '</span>';
                }
                $data['templateContent'] .= '<br/><div class="alrim-template-button-list">' . implode(' ', $tmpData) . '</div>';
                unset($tmpBtnData, $tmpData);
            }

            $listHtml[] = '<tr data-templateCode="' . $data['templateCode'] . '" >';
            $listHtml[] = '<td class="text-center number">' . number_format($page->idx--) . '</td>';
            $listHtml[] = '<td class="text-center">' . $sTemplateCode . '</td>';
            $listHtml[] = '<td class="text-center">' . $sTempTemplateType . '</td>';
            $listHtml[] = '<td class="text-center">' . $data['templateName'] . '</td>';
            $listHtml[] = '<td class="text">' . nl2br($data['templateContent']) . '</td>';
            $listHtml[] = '<td class="text-center">' . $sTempInspectionStatus . '</td>';
            $listHtml[] = '<td class="text-center">' . $sTempStatus . '</td>';
            $listHtml[] = '<td class="text-center">' . $data['regDt'] . '</td>';
            $listHtml[] = '<td class="text-center">' . $sTempUseFlag . '</td>';
            $listHtml[] = '<td class="text-center">' . $sTempModify . '</td>';
            $listHtml[] = '<td class="text-center">' . $sTempDelete . '</td>';
            $listHtml[] = '</tr>';
        }
        echo implode('', $listHtml);
    } else {
        echo '<tr><td colspan="11" class="no-data">등록된 템플릿내역이 없습니다.</td></tr>';
    }
    ?>
    </tbody>
</table>

<div class="center"><?php echo $page->getPage(); ?></div>

<script language="javascript" type="text/javascript">
    <!--
    $(document).ready(function () {
        $('#layerTemplateRegist').click(function () {
            if ($('#kakaoId').val() == '') {
                alert('카카오 알림톡 설정 메뉴에서 카카오톡 플러스친구 아이디를 먼저 등록해주세요.');
                return false;
            }

            var layerFormID = 'layerForm';
            var addParam = '';
            var fileStr = '';
            var parentFormID = 'frmKakaoTemplateRegist';
            var dataFormID = 'id';
            var layerTitle = '카카오 알림톡 템플릿 등록';
            fileStr = 'kakao_alrim_template_regist';
            var mode = 'frmKakaoTemplateRegist';

            var addParam = {
                "mode": mode,
                "layerFormID": layerFormID,
                "parentFormID": parentFormID,
                "dataFormID": dataFormID,
                "layerTitle": layerTitle,
                "size": 'wide',
                "backdrop": 'static'
            };

            layer_add_info(fileStr, addParam);
        });

        $('[id^="updateTemplate"]').click(function () {
            var layerFormID = 'layerForm';
            var addParam = '';
            var fileStr = '';
            var parentFormID = 'frmKakaoTemplateUpdate';
            var dataFormID = 'id';
            var layerTitle = '카카오 알림톡 템플릿 수정';
            fileStr = 'kakao_alrim_template_regist';
            var mode = 'frmKakaoTemplateUpdate';
            var templateCode = $(this).data('code');

            var addParam = {
                "mode": mode,
                "templateCode": templateCode,
                "layerFormID": layerFormID,
                "parentFormID": parentFormID,
                "dataFormID": dataFormID,
                "layerTitle": layerTitle,
                "size": 'wide',
                "backdrop": 'static'
            };

            layer_add_info(fileStr, addParam);
        });

        $('[id^="commentTemplate"]').click(function () {
            var layerFormID = 'layerForm';
            var addParam = '';
            var fileStr = '';
            var parentFormID = 'frmKakaoTemplateComment';
            var dataFormID = 'id';
            var layerTitle = '카카오 알림톡 템플릿 검수 답변 확인';
            fileStr = 'kakao_alrim_template_comment';
            var mode = 'frmKakaoTemplateComment';
            var templateCode = $(this).data('code');

            var addParam = {
                "mode": mode,
                "templateCode": templateCode,
                "layerFormID": layerFormID,
                "parentFormID": parentFormID,
                "dataFormID": dataFormID,
                "layerTitle": layerTitle,
                "size": 'wide',
                "backdrop": 'static'
            };

            layer_add_info(fileStr, addParam);
        });

        $('[id^="deleteTemplate"]').click(function () {
            var code = $(this).data('code');
            BootstrapDialog.confirm({
                type: BootstrapDialog.TYPE_DANGER,
                title: '카카오 알림톡 템플릿 삭제',
                message: '정말 삭제하시겠습니까?',
                closable: false,
                callback: function(confirm) {
                    if (confirm) {
                        var url = 'kakao_alrim_ps.php';
                        var formData = new FormData();
                        formData.append('mode', 'deleteTemplate');
                        formData.append('templateCode', '' + code + '');
                        $.ajax({
                            url: url,
                            data: formData,
                            processData: false,
                            contentType: false,
                            type: 'POST',
                            dataType: 'json',
                            success: function (data) {
                                if (data.result == 'success') {
                                    BootstrapDialog.alert({
                                        type: BootstrapDialog.TYPE_INFO,
                                        message: '템플릿을 삭제하였습니다.',
                                        closable: false,
                                        callback: function (result) {
                                            if (result) {
                                                location.reload();
                                            }
                                        }
                                    });
                                } else if (data.result == 'delete') {
                                    BootstrapDialog.alert({
                                        type: BootstrapDialog.TYPE_INFO,
                                        message: '해당 템플릿은 같은 플러스친구 아이디를 사용중인 다른 상점에서 삭제 처리된 템플릿입니다.',
                                        closable: false,
                                        callback: function (result) {
                                            if (result) {
                                                location.reload();
                                            }
                                        }
                                    });
                                } else {
                                    alert('템플릿 삭제를 실패하였습니다. 잠시후 다시 시도해주세요.');
                                }
                                return false;
                            },
                            error: function(data) {
                                alert('템플릿 삭제를 실패하였습니다. 잠시후 다시 시도해주세요.');
                                return false;
                            }
                        });
                    }
                }
            });
        });

        /**
         * 템플릿 검수요청
         *
         */
        $('[id^="requestTemplate"]').click(function () {
            var code = $(this).data('code');
            $.post('layer_request_kakao_template.php', {'templateCode': code}, function (data) {
                layer_popup(data, '카카오 알림톡 템플릿 검수요청');
            });
        });

        /**
         * 템플릿 휴면 해제
         *
         */
        $('[id^="wakeUpTemplate"]').click(function () {
            var templateCode = $(this).data('code');
            dialog_confirm('템플릿을 휴면해제 하시겠습니까?',function (result) {
                if(result){
                    $.ajax({
                        method: "post",
                        url: "kakao_alrim_ps.php",
                        data: {
                            mode: 'wakeUpTemplate',
                            templateCode: templateCode
                        },
                        dataType: 'json',
                        cache: false,
                        async: true,
                    }).success(function (data) {
                        if (data.result == 'success') {
                            alert('휴면해제 되었습니다.');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        }
                        else {
                            alert(data.message);
                        }
                    }).error(function (e) {
                        alert(e.responseText);
                    });
                }
            });
        });

        $('select[name="pageNum"]').change({targetForm: '#frmSearch'}, member.page_number);

        if ($('#kakaomode').val() == 'newpop') {
            $('#layerTemplateRegist').trigger('click');
        }

        // 알림톡 내 버튼 타입에 따른 안내문구 노출
        $(document).on('change', 'select[name="layerTemplateBtnType[]"]', function(e) {
            var index = $(this).closest('tr').attr('id').replace('kakaoButton_', '');
            var target = 'tr[id="kakaoButton_' + index + '"]';

            if ($(this).val() === 'DS') {
                $('.delivery-notice', target).removeClass('display-none');
                $('.page-url', target).val('');
                $('.page-url', target).addClass('display-none');
            } else {
                $('.delivery-notice', target).addClass('display-none');
                $('.page-url', target).removeClass('display-none');
            }
        });

        // 알림톡 내 버튼 추가
        $(document).on('click', '.js-add-kakao-button', function(e) {
            var addHtml = "";
            var complied = _.template($('#addKakaoButton').html());
            var buttonCnt = $('tr[id^="kakaoButton_"]').length;
            var limitButtonCnt = 5;

            if (buttonCnt === limitButtonCnt) {
                alert('버튼은 최대 ' + limitButtonCnt + '개까지만 설정하실 수 있습니다.');
                return false;
            }

            if (buttonCnt === 0) {
                $("#emptyKakaoButton").addClass('display-none');
            }

            addHtml += complied({
                sno: ++buttonCnt,
            });

            $("#kakaoButtonList").append(addHtml);
        });

        // 알림톡 내 버튼 삭제
        $(document).on('click', '.js-remove-kakao-button', function(e) {
            $('#kakaoButton_' + $(this).closest('tr').attr('id').replace('kakaoButton_', '')).remove();
            var afterRemoveIndex = 0;
            var button = $('tr[id^="kakaoButton_"]');

            if (button.length === 0) {
                $("#emptyKakaoButton").removeClass('display-none');
            }

            button.each(function(idx) {
                afterRemoveIndex = ++idx;
                if ($(this).attr('id').replace('kakaoButton_', '') !== afterRemoveIndex) {
                    $(this).attr('id', 'kakaoButton_' + afterRemoveIndex);
                    $(this).find('td:nth-of-type(1)').text(afterRemoveIndex);
                }
            });
        });

        // SMS 발송 조건 / 문구 설정 탭
        $('.nav-tabs a').click(function (e) {
            e.preventDefault();
            $(this).tab('show');
            var hrefValue = $(this).prop('href');
            var arrHrefValue = hrefValue.split('#');
            var keyValue = arrHrefValue[1];
            if(keyValue == 'biz'){
                location.href = 'kakao_alrim_template.php';
            }else if(keyValue == 'luna'){
                location.href = 'kakao_alrim_luna_template.php';
            }
        });
    });
    //-->
</script>
<script type="text/html" id="addKakaoButton">
    <tr id="kakaoButton_<%=sno%>">
        <td class="center">
            <%=sno%>
        </td>
        <td>
            <?= gd_select_box(null, 'layerTemplateBtnType[]', $kakaoButtonType, null, null) ?>
            <div class="notice-info delivery-notice display-none">알림톡 내용에 택배사와 송장번호가 있을 경우 카카오 배송조회 페이지 링크로 이동됩니다.</div>
            <div class="notice-info delivery-notice display-none">카카오 배송조회 페이지에서 지원되지 않는 택배사는 배송조회가 되지 않을 수 있습니다.</div>
            <input type="text" name="layerTemplateBtnUrl[]" class="form-control width300 mgt5 page-url" maxlength="255" placeholder="모바일 쇼핑몰 URL을 넣어주세요.">
        </td>
        <td>
            <input type="text" name="layerTemplateBtnName[]" class="form-control width-md" maxlength="14">
        </td>
        <td class="center">
            <input type="button" class="btn btn-white btn-icon-minus btn-sm js-remove-kakao-button" value="삭제">
        </td>
    </tr>
</script>

