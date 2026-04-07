<?php
foreach ($aCate2 as $k => $v) {
    ?>
    <input type="hidden" id="cate2_<?=$k;?>" value="<?=$v;?>">
    <?php
}
?>

<input type="hidden" id="layerSmsTypeOrg" name="layerSmsTypeOrg" value="">
<form id="frmKakaoAlrimTemplateRegist">
    <input type="hidden" id="layerTemplateCode" name="layerTemplateCode" value="<?php echo $templateCode; ?>">
    <div>
        <div class="mgt10"></div>
        <div>
            <table class="table table-cols no-title-line">
                <colgroup>
                    <col class="width-xs"/>
                    <col/>
                    <col class="width-md"/>
                    <col/>
                </colgroup>
                <tr>
                    <th>템플릿 구분</th>
                    <td>
                        <?php if ($mode == 'frmKakaoTemplateRegist') { ?>
                            <select class="form-control" id="layerSmsType" name="layerSmsType">
                                <option value="order" <?= gd_isset($selected['layerSmsType']['order']); ?>>주문배송관련</option>
                                <option value="member" <?= gd_isset($selected['layerSmsType']['member']); ?>>회원관련</option>
                                <option value="board" <?= gd_isset($selected['layerSmsType']['board']); ?>>게시물등록 알림</option>
                            </select>
                        <?php } else { ?>
                            <input type="hidden" id="layerSmsType" name="layerSmsType" value="<?php echo $data['smsType']; ?>">
                            <?php if ($data['smsType'] == 'order') { ?>
                                주문배송관련
                            <?php } elseif ($data['smsType'] == 'member') { ?>
                                회원관련
                            <?php } else {?>
                                게시물등록 알림
                            <?php } ?>
                            <div class="notice-info">템플릿 수정 시에는 템플릿 구분 수정은 할 수 없습니다.</div>
                        <?php } ?>
                    </td>
                </tr>
                <tr>
                    <th>템플릿명</th>
                    <td>
                        <input type="text" value="<?php echo $data['templateName']; ?>" id="layerTemplateName" name="layerTemplateName" size="36" />
                        <div class="notice-info">템플릿명은 템플릿 구분을 위한 입력 항목이며, 알림톡 내용에 포함되지 않습니다.</div>
                    </td>
                </tr>
                <tr>
                    <th>템플릿내용</th>
                    <td class="form-inline sms-replace-code-area">
                        <div class="row">
                            <div class="col-xs-12 pdb10">
                                템플릿 카테고리 설정
                                <select id="templateCategory" name="templateCategory">
                                    <option value="">그룹 카테고리 선택</option>
                                    <?php foreach ($aCate1 as $key => $val) { ?>
                                        <option value="<?php echo $key; ?>" <?= gd_isset($selected['templateCategory'][$key]); ?>><?php echo $val; ?></option>
                                    <?php } ?>
                                </select>
                                <select id="templateCategory2" name="templateCategory2">
                                    <option value="">카테고리 선택</option>
                                    <?php
                                    if ($mode == 'frmKakaoTemplateUpdate') {
                                        foreach ($aCate2 as $key => $val) {
                                            if (substr($key, 0, 3) == $data['categoryGroupCode']) {
                                                ?>
                                                <option value="<?php echo $key; ?>" <?= gd_isset($selected['templateCategory2'][$key]); ?>><?php echo $val; ?></option>
                                                <?php
                                            }
                                        }
                                    }
                                    ?>
                                </select>
                                <div class="notice-info">템플릿 카테고리는 카카오에서 사용하는 템플릿 구분 값으로, 미설정 시 '기타'로 등록 됩니다.</div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-5 pdr15">
                                <button type="button" class="btn btn-black btn-sm pull-right js-toggle-replace-code" data-target=".replace_code_area" data-text="치환코드 닫기">치환코드 보기</button>
                            </div>
                            <div class="col-xs-7">
                                <div id="notice_order" class="notice-info pull-left mgl5 display-none">주문배송관련 치환코드만 노출됩니다.</div>
                                <div id="notice_member" class="notice-info pull-left mgl5 display-none">회원관련 치환코드만 노출됩니다.</div>
                                <div id="notice_board" class="notice-info pull-left mgl5 display-none">게시물등록 알림 치환코드만 노출됩니다.</div>
                            </div>
                        </div>
                        <div class="row pdt5">
                            <div class="col-xs-5 pdr0">
                                <label class="width100p">
                                    <div style="width: 282px; !important; background-color: #fee800;" align="center"><img src="/admin/gd_share/img/talk_titimg02.png"></div>
                                    <textarea id="layerTemplateContent" name="layerTemplateContent" rows="13" class="form-control" style="width: 282px; padding: 16px" data-close="true"><?php echo $data['templateContent']; ?></textarea>
                                </label>
                            </div>
                            <div class="col-xs-7 display-none replace_code_area">
                                <div class="table-scroll-kakao">
                                    <table class="table table-bordered table-rows mgb0 js-table-replace-code">
                                        <colgroup>
                                            <col class="width-sm">
                                            <col class="width-2xl">
                                            <col class="width-3xs">
                                        </colgroup>
                                        <thead>
                                        <tr>
                                            <th>치환코드</th>
                                            <th>설명</th>
                                            <th>삽입</th>
                                        </tr>
                                        </thead>
                                        <!-- @formatter:off -->
                                        <tbody id="orderReplaceCode" class="replace-code-area display-none" data-type="order">
                                        <tr> <td class="center">[#{rc_mallNm}]</td> <td>쇼핑몰 명, 상점명</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{shopUrl}</td> <td>쇼핑몰 도메인</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{orderNo}</td> <td>주문번호</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{orderName}</td> <td>주문자 이름</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{settlePrice}</td> <td>총 주문 금액</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{cancelPrice}</td> <td>취소금액</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{gbRefundPrice}</td> <td>실 환불금액</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{orderDate}</td> <td>주문일</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{depositNm}</td> <td>입금자명</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{bankAccount}</td> <td>입금계좌</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{deliveryName}</td> <td>배송업체명</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{invoiceNo}</td> <td>송장번호</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{goodsNm}</td> <td>주문 상품명</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{userExchangeStatus}</td> <td>클레임 상태</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{expirationDate}</td> <td>입금만료일</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        </tbody>
                                        <tbody id="memberReplaceCode" class="replace-code-area display-none" data-type="member">
                                        <tr> <td class="center">[#{rc_mallNm}]</td> <td>쇼핑몰 명, 상점명</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{shopUrl}</td> <td>쇼핑몰 도메인</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{memId}</td> <td>회원 아이디</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{memNm}</td> <td>회원명</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{sleepScheduleDt}</td> <td>휴면회원전환예정일</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{smsAgreementFl}</td> <td>SMS 수신동의 여부</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{smsAgreementDt}</td> <td>SMS 수신동의일</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{mailAgreementFl}</td> <td>메일 수신동의 여부</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{mailAgreementDt}</td> <td>메일 수신동의일</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{groupNm}</td> <td>회원등급</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{mileage}</td> <td>보유한 마일리지</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{rc_mileage}</td> <td>지급/차감/소멸 예정 마일리지<br/>(소멸 예정 마일리지로 사용 시 마일리지 소멸 예정일시와 함께 사용 필수)</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{rc_deleteScheduleDt}</td> <td>마일리지 소멸 예정일시</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{deposit}</td> <td>보유한 예치금</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{rc_deposit}</td> <td>지급/차감 예치금</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{rc_certificationCode}</td> <td>비밀번호 찾기 인증번호</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{rc_certificationCode}</td> <td>휴면회원 해제 인증번호</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        </tbody>
                                        <tbody id="boardReplaceCode" class="replace-code-area display-none" data-type="board">
                                        <tr> <td class="center">[#{rc_mallNm}]</td> <td>쇼핑몰 명, 상점명</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{shopUrl}</td> <td>쇼핑몰 도메인</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        <tr> <td class="center">#{wriNm}</td> <td>작성자명</td> <td class="center"> <button class="btn btn-sm btn-white js-btn-insert" type="button">삽입</button> </td> </tr>
                                        </tbody>
                                        <!-- @formatter:on -->
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="row pdt10">
                            <span class="col-xs-5">
                                <!-- //@formatter:off -->
                                <input type="text" id="templateStringCount" value="0" readonly="readonly" class="form-control width-3xs"> / 1000 자
                                <!-- //@formatter:on -->
                            </span>
                            <span id="replace_code_area_info" class="col-xs-7 display-none">
                                <div class="notice-info">발송항목에 치환코드 값이 없을 시 빈 값으로 발송됩니다.</div>
                            </span>
                        </div>
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="notice-info">템플릿내용은 URL을 포함해 1,000자까지 사용하실수 있습니다.</div>
                                <div class="notice-info">치환코드 사용 시 1,000자가 넘지 않도록 주의해주세요.</div>
                                <div class="notice-info">알림톡에 치환코드를 사용하시려면 모든 치환코드 앞에 #을 붙여 입력하셔야 합니다.</div>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>보안 템플릿 설정</th>
                    <td>
                        <div class="row">
                            <div class="col-xs-12">
                                <label class="checkbox-inline">
                                    <input type="checkbox" id="securityflag" name="securityflag" value="T" <?php echo $checked['securityflag']; ?>> 보안 템플릿 사용
                                </label>
                                <div class="notice-danger">템플릿 내용에 인증번호, 비밀번호 등의 내용이 포함되는 경우 보안 템플릿을 필수로 사용해야 합니다.</div>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>버튼<br/><input type="button" class="btn btn-white btn-icon-plus btn-sm mgt10 js-add-kakao-button" value="추가"></th>
                    <td>
                        <table class="table table-cols">
                            <thead>
                            <tr>
                                <th class="width10p">순서</th>
                                <th>버튼타입</th>
                                <th class="width20p">버튼명</th>
                                <th class="width10p"></th>
                            </tr>
                            </thead>
                            <tbody id="kakaoButtonList">
                            <?php if (empty($data['templateButton'])) { ?>
                                <tr id="emptyKakaoButton">
                                    <td colspan="4"><div class="notice-info">[+ 추가] 버튼을 클릭하여 버튼을 추가할 수 있습니다.</div></td>
                                </tr>
                            <?php } else {
                                foreach ($data['templateButton'] as $val) { ?>
                                    <tr id="kakaoButton_<?=$val['ordering'];?>">
                                        <td class="center">
                                            <?=$val['ordering'];?>
                                        </td>
                                        <td>
                                            <?= gd_select_box(null, 'layerTemplateBtnType[]', $kakaoButtonType, null, $val['linkType']) ?>
                                            <div class="notice-info delivery-notice <?=$val['linkType'] != 'DS' ? 'display-none':'';?>">알림톡 내용에 택배사와 송장번호가 있을
                                                경우 카카오 배송조회 페이지 링크로 이동됩니다.
                                            </div>
                                            <div class="notice-info delivery-notice <?=$val['linkType'] != 'DS' ? 'display-none':'';?>">카카오 배송조회 페이지에서 지원되지 않는
                                                택배사는 배송조회가 되지 않을 수 있습니다.
                                            </div>
                                            <input type="text" name="layerTemplateBtnUrl[]"
                                                   class="form-control width300 mgt5 page-url <?=$val['linkType'] == 'DS' ? 'display-none':'';?>" maxlength="255"
                                                   placeholder="모바일 쇼핑몰 URL을 넣어주세요." value="<?=$val['linkMo'];?>">
                                        </td>
                                        <td>
                                            <input type="text" name="layerTemplateBtnName[]"
                                                   class="form-control width-md" maxlength="14" value="<?=$val['name'];?>">
                                        </td>
                                        <td class="center">
                                            <input type="button"
                                                   class="btn btn-white btn-icon-minus btn-sm js-remove-kakao-button"
                                                   value="삭제">
                                        </td>
                                    </tr>
                                    <tr id="emptyKakaoButton" class="display-none">
                                        <td colspan="4"><div class="notice-info">[+ 추가] 버튼을 클릭하여 버튼을 추가할 수 있습니다.</div></td>
                                    </tr>
                                <?php }
                            }?>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <div class="notice-info">모든 템플릿은 등록 후 카카오에서 검수 완료를 해야 사용이 가능합니다.</div>
    <div class="notice-info">검수는 카카오톡에서 진행되며, 검수는 영업일 기준 1~2일 이상 시간이 소요됩니다.</div>

    <div>
        <div class="mgt30"></div>
        <div class="text-center">
            <?php if ($mode == 'frmKakaoTemplateRegist') { ?>
                <input type="button" id="kakao_add" value="&nbsp;&nbsp;&nbsp;&nbsp;등록&nbsp;&nbsp;&nbsp;&nbsp;" class="btn btn-black btn-hf" />
            <?php } else { ?>
                <input type="button" id="kakao_update" value="&nbsp;&nbsp;&nbsp;&nbsp;수정&nbsp;&nbsp;&nbsp;&nbsp;" class="btn btn-black btn-hf" />
            <?php } ?>
            <input type="button" id="kakao_close" value="취소" class="btn btn-white btn-hf" />
        </div>
        <div class="mgt10"></div>
    </div>
</form>

<script type="text/javascript">
    $(document).ready(function () {
        // 카테고리 변경
        $(document).on('change', '[id="templateCategory"]', function(){
            // 중,소분류 초기화
            $('#templateCategory2').find("option").remove();
            $('#templateCategory2').append("<option value=''>카테고리 선택</option>");

            // 대분류 기본값 선택시
            if ($(this).val() == '') {
                return false;
            } else {
                $('[id^="cate2_' + $(this).val() + '"]').each(function (index) {
                    var tempCate2Id = $(this).attr('id');
                    var cate2Id = tempCate2Id.replace('cate2_', '');
                    $('#templateCategory2').append('<option value="' + cate2Id + '">' + $(this).val() + '</option>');
                });
            }
        });

        var alrim = {};
        alrim.last_selected_index = -1;
        alrim.contents_focus_position = 0;

        $('.js-toggle-replace-code').click(function () {
            var $this = $(this);
            var $target = $($this.data('target'));

            $target.toggleClass('display-none');

            var text = $this.data('text');
            if (text) {
                $this.data('text', $this.text());
                $this.text(text);
            }

            if ($target.hasClass('display-none')) {
                $('#notice_order').addClass('display-none');
                $('#notice_member').addClass('display-none');
                $('#notice_board').addClass('display-none');
                $('#orderReplaceCode').addClass('display-none');
                $('#memberReplaceCode').addClass('display-none');
                $('#boardReplaceCode').addClass('display-none');
                $('#replace_code_area_info').addClass('display-none');
            } else {
                <?php if ($mode == 'frmKakaoTemplateRegist') { ?>
                var $sms = $('select[name=layerSmsType] option:selected');
                <?php } else { ?>
                var $sms = $('#layerSmsType');
                <?php } ?>
                $('#notice_' + $sms.val()).toggleClass('display-none');
                $('#' + $sms.val() + 'ReplaceCode').toggleClass('display-none');
                $('#replace_code_area_info').toggleClass('display-none');
            }
        });

        var prev_val ;
        $('select[name=layerSmsType]').focus(function(){
            prev_val = $(this).val();
        }).change(function() {
            if ($('#layerTemplateContent').val() != '') {
                BootstrapDialog.confirm({
                    type: BootstrapDialog.TYPE_DANGER,
                    title: '템플릿 구분 변경',
                    message: '템플릿 구분을 변경할 경우 작성중인 템플릿내용은 모두 삭제됩니다. 템플릿 구분을 변경하시겠습니까?',
                    closable: false,
                    callback: function(result) {
                        if (result) {
                            $('#layerTemplateContent').val('');
                            setSendLength();
                            if ($('.replace_code_area').hasClass('display-none')) {

                            } else {
                                $('#notice_order').addClass('display-none');
                                $('#notice_member').addClass('display-none');
                                $('#notice_board').addClass('display-none');
                                $('#orderReplaceCode').addClass('display-none');
                                $('#memberReplaceCode').addClass('display-none');
                                $('#boardReplaceCode').addClass('display-none');

                                $('#notice_' + $('select[name=layerSmsType] option:selected').val()).toggleClass('display-none');
                                $('#' + $('select[name=layerSmsType] option:selected').val() + 'ReplaceCode').toggleClass('display-none');
                            }
                        } else {
                            $('select[name=layerSmsType]').val(prev_val);
                        }
                    }
                });
            } else {
                if ($('.replace_code_area').hasClass('display-none')) {

                } else {
                    $('#notice_order').addClass('display-none');
                    $('#notice_member').addClass('display-none');
                    $('#notice_board').addClass('display-none');
                    $('#orderReplaceCode').addClass('display-none');
                    $('#memberReplaceCode').addClass('display-none');
                    $('#boardReplaceCode').addClass('display-none');

                    $('#notice_' + $('select[name=layerSmsType] option:selected').val()).toggleClass('display-none');
                    $('#' + $('select[name=layerSmsType] option:selected').val() + 'ReplaceCode').toggleClass('display-none');
                }
            }

            // 템플릿 구분값이 '주문배송관련'이 아닌데 버튼정보가 있으면 버튼타입을 '페이지 링크'로 제한
            if ($('select[name=layerSmsType] option:selected').val() != 'order') {
                if ($('select[name="layerTemplateBtnType[]"]').length > 0) {
                    $('select[name="layerTemplateBtnType[]"]').each(function() {
                        $(this).find('option[value="WL"]').prop('selected', true);
                        $(this).find('option[value="DS"]').prop('disabled', true);

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
                }
            } else {
                $('select[name="layerTemplateBtnType[]"]').each(function() {
                    $(this).find('option[value="DS"]').prop('disabled', false);
                });
            }
        });

        $("#kakao_add").click(function () {
            // 필수값 체크
            if ($('#layerTemplateName').val().trim() == '') {
                alert('템플릿명을 입력해주세요.');
                return false;
            }
            if ($('#templateCategory option:selected').val() != '' && $('#templateCategory2 option:selected').val() == '') {
                alert('카테고리를 선택해주세요.');
                return false;
            }
            if ($('#layerTemplateContent').val().trim() == '') {
                alert('템플릿내용을 입력해주세요.');
                return false;
            }
            if ($('#layerTemplateContent').val().length > 1000) {
                alert('템플릿내용은 최대 1000자 입니다.');
                return false;
            }

            if (validateButtonInfo() === false) {
                return false;
            }

            var url = '/member/kakao_alrim_ps.php';
            var data = $('#frmKakaoAlrimTemplateRegist').serializeObject();
            var formData = new FormData();
            formData.append('mode', 'registTemplate');
            formData.append('smsType', data.layerSmsType);
            formData.append('templateName', data.layerTemplateName);
            formData.append('templateContent', data.layerTemplateContent);
            formData.append('templateCategory', data.templateCategory);
            formData.append('templateCategory2', data.templateCategory2);
            formData.append('securityflag', data.securityflag);
            if (!_.isUndefined(data.layerTemplateBtnType)) {
                for (var i = 0; i < data.layerTemplateBtnType.length; i++) {
                    formData.append('buttons[' + i + '][ordering]', i + 1);
                    formData.append('buttons[' + i + '][linkType]', data.layerTemplateBtnType[i]);
                    formData.append('buttons[' + i + '][name]', data.layerTemplateBtnName[i]);
                    formData.append('buttons[' + i + '][linkMo]', data.layerTemplateBtnUrl[i]);
                    formData.append('buttons[' + i + '][linkPc]', '');
                    formData.append('buttons[' + i + '][linkIos]', '');
                    formData.append('buttons[' + i + '][linkAnd]', '');
                }
            }
            $.ajax({
                url: url,
                data: formData,
                processData: false,
                contentType: false,
                type: 'POST',
                dataType: 'json',
                success: function (data) {
                    if (data.result == 'success') {
                        alert('템플릿을 등록하였습니다.');
                        setTimeout(function() {
                            location.href = '/member/kakao_alrim_template.php';
                        }, 2000);
                        return false;
                    } else {
                        alert('템플릿을 등록할 수 없습니다. ' + data.message);
                        return false;
                    }
                },
                error: function(data) {
                    alert('템플릿을 등록할 수 없습니다. 잠시 후 다시 시도해주세요.');
                    return false;
                }
            });
        });

        $("#kakao_update").click(function () {
            // 필수값 체크
            if ($('#layerTemplateName').val().trim() == '') {
                alert('템플릿명을 입력해주세요.');
                return false;
            }
            if ($('#templateCategory option:selected').val() != '' && $('#templateCategory2 option:selected').val() == '') {
                alert('카테고리를 선택해주세요.');
                return false;
            }
            if ($('#layerTemplateContent').val().trim() == '') {
                alert('템플릿내용을 입력해주세요.');
                return false;
            }
            if ($('#layerTemplateContent').val().length > 1000) {
                alert('템플릿내용은 최대 1000자 입니다.');
                return false;
            }

            if (validateButtonInfo() === false) {
                return false;
            }

            var url = '/member/kakao_alrim_ps.php';
            var data = $('#frmKakaoAlrimTemplateRegist').serializeObject();
            var formData = new FormData();
            formData.append('mode', 'updateTemplate');
            formData.append('smsType', data.layerSmsType);
            formData.append('templateName', data.layerTemplateName);
            formData.append('templateContent', data.layerTemplateContent);
            formData.append('templateCode', data.layerTemplateCode);
            formData.append('templateCategory', data.templateCategory);
            formData.append('templateCategory2', data.templateCategory2);
            formData.append('securityflag', data.securityflag);
            if (!_.isUndefined(data.layerTemplateBtnType)) {
                for (var i = 0; i < data.layerTemplateBtnType.length; i++) {
                    formData.append('buttons[' + i + '][ordering]', i + 1);
                    formData.append('buttons[' + i + '][linkType]', data.layerTemplateBtnType[i]);
                    formData.append('buttons[' + i + '][name]', data.layerTemplateBtnName[i]);
                    formData.append('buttons[' + i + '][linkMo]', data.layerTemplateBtnUrl[i]);
                    formData.append('buttons[' + i + '][linkPc]', '');
                    formData.append('buttons[' + i + '][linkIos]', '');
                    formData.append('buttons[' + i + '][linkAnd]', '');
                }
            }
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
                            message: '템플릿을 수정하였습니다.',
                            closable: false,
                            callback: function (result) {
                                if (result) {
                                    location.reload();
                                }
                            }
                        });
                        return false;
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
                        alert('템플릿을 수정할 수 없습니다. ' + data.message);
                        return false;
                    }
                },
                error: function(data) {
                    alert('템플릿을 수정할 수 없습니다. 잠시 후 다시 시도해주세요.');
                    return false;
                }
            });
        });

        $("#kakao_close").click(function () {
            $('.close').trigger('click');
        });

        /**
         * 발송내용 포커스 아웃 이벤트
         * @param selector
         */
        function set_change_focus_contents (selector) {
            $send_contents = $(selector);
            $send_contents.focusout(function (e) {
                var $this = $(e.target);
                alrim.contents_focus_position = $this.prop('selectionStart');
            });
        };

        /**
         * 발송내용 변경 시 마지막 포커스 위치 및 글자 체크 이벤트 갱신
         */
        function refresh_send_contents() {
            $send_contents = $('#layerTemplateContent');
            alrim.contents_focus_position = $send_contents.val().length;
            $send_contents.focus();
            setSendLength();
        }

        /**
         * 치환코드 삽입 이벤트 설정
         * @param selector
         */
        function set_click_replace_code_insert (selector) {
            $(selector).click(function (e) {
                var $send_contents = $('#layerTemplateContent');
                var $this = $(e.target);
                var code = $this.closest('tr').find('td:eq(0)').text();
                var input = $send_contents.val();
                var output = [input.slice(0, alrim.contents_focus_position), code, input.slice(alrim.contents_focus_position)];
                $send_contents.val(output.join(''));
                $send_contents.get(0).selectionEnd = alrim.contents_focus_position + code.length;
                refresh_send_contents();
            });
        }

        /**
         * 문자열 Byte 체크 (한글 2byte)
         */
        function stringToByte(str) {
            var length = 0;
            for (var i = 0; i < str.length; i++) {
                if (escape(str.charAt(i)).length >= 4)
                    length += 2;
                else if (escape(str.charAt(i)) != "%0D")
                    length++;
            }
            return length;
        }

        /**
         * 템플릿 내용 길이 체크
         */
        function setContentsLength(contentsNm, countId) {
            var textarea = $('textarea[name=' + contentsNm + ']');
            var contentsText = textarea.val();
            //var textLength = stringToByte(contentsText);
            var textLength = contentsText.length;
            if (textLength > 1000) {
                if (textarea.data('close')) {
                    textarea.data('close', false);
                    BootstrapDialog.show({
                        message: '템플릿내용은 최대 1,000자 까지 가능합니다.',
                        onhidden: function () {
                            var output = contentsText.slice(0, 1000);
                            textarea.val(output);
                            textarea.data('close', true);
                            setSendLength()
                        }
                    });
                }

                $('#' + countId).css("color", "#FF0000");
                $('.sms-type').hide();
                $('.lms-type').show();
                $('input[name=sendFl]').val('lms');
            } else {
                $('#' + countId).css("color", "");
                $('.sms-type').show();
                $('.lms-type').hide();
                $('input[name=sendFl]').val('sms');
            }
            $('#' + countId).val(textLength);
        }

        function setSendLength() {
            setContentsLength('layerTemplateContent', 'templateStringCount');
        }

        // 글자수 체크
        $('textarea[name=layerTemplateContent]').keyup(setSendLength).change(setSendLength);

        setSendLength();

        set_click_replace_code_insert('.replace-code-area .js-btn-insert');
        set_change_focus_contents('textarea[name="layerTemplateContent"]');
    });

    /**
     * 템플릿 버튼 검증
     */
    function validateButtonInfo() {
        if ($('select[name="layerTemplateBtnType[]"]').length > 0) {
            var emptyUrlFl = false, emptyBtnNameFl = false, wrongBtnUrlFl = false, btnUrl;
            $('select[name="layerTemplateBtnType[]"]').each(function() {
                btnUrl = '';
                // 버튼명 검증
                if ($(this).closest('tr').find('input[name="layerTemplateBtnName[]"]').val() == '') {
                    emptyBtnNameFl = true;
                    return false;
                }

                // 버튼 url 검증
                btnUrl = $(this).closest('td').find('input[name="layerTemplateBtnUrl[]"]').val();
                if ($(this).val() == 'WL') {
                    if (btnUrl == '') {
                        emptyUrlFl = true;
                        return false;
                    } else if ((btnUrl.indexOf('http://') != -1 || btnUrl.indexOf('https://') != -1) === false) {
                        wrongBtnUrlFl = true;
                        return false
                    }
                }
            });
            if (emptyBtnNameFl) {
                alert('버튼명을 입력해주세요');
                return false;
            }
            if (emptyUrlFl) {
                alert('URL을 입력해주세요');
                return false;
            }
            if (wrongBtnUrlFl) {
                alert('http 또는 https를 포함한 URL을 입력해주세요');
                return false;
            }
        }
        return true;
    }
</script>

