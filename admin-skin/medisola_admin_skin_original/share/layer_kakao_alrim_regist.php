<?php
    foreach ($aCate2 as $k => $v) {
        ?>
        <input type="hidden" id="cate2_<?=$k;?>" value="<?=$v;?>">
        <?php
    }
    foreach ($aCate3 as $k => $v) {
        ?>
        <input type="hidden" id="cate3_<?=$k;?>" value="<?=$v;?>">
        <?php
    }
?>

<form id="frmKakaoAlrimIdRegist">
<div>
    <div class="mgt10"></div>
    <div>
        <table class="table table-cols no-title-line">
            <colgroup>
                <col class="width-md"/>
                <col/>
                <col class="width-md"/>
                <col/>
                <col class="width-xs"/>
            </colgroup>
            <tr>
                <th>플러스친구 아이디</th>
                <td>
                    <input type="text" value="" id="layer_plusId" name="layer_plusId" placeholder="예) @고도몰" size="36" />
                    <div class="notice-info">카카오톡 플러스친구 <span class="text-red">검색용 아이디</span>를 입력해주세요. @를 앞에 붙여주셔야 합니다. 예) @고도몰</div>
                    <div class="notice-info">카카오톡 플러스친구 아이디가 없다면 <a href="https://center-pf.kakao.com/login" target="_blank" class="text-blue">[카카오톡 플러스 친구 관리자]</a>에서 발급받은 후 등록해주세요.</div>
                    <div class="notice-info">발급받은 카카오톡 플러스친구 아이디는 반드시 홈공개로 설정해주셔야 등록이 가능합니다</div>
                </td>
            </tr>
            <tr>
                <th>카테고리</th>
                <td>
                    <span class="select_box">
                        <?= gd_select_box('layer_kakaoCategory1', 'layer_kakaoCategory1', $aCate1, null, null, '대분류', null, 'chosen-select'); ?>
                    </span>
                    <span class="select_box">
                        <?= gd_select_box('layer_kakaoCategory2', 'layer_kakaoCategory2', array(), null, null, '중분류', null, 'chosen-select'); ?>
                    </span>
                    <span class="select_box">
                        <?= gd_select_box('layer_kakaoCategory3', 'layer_kakaoCategory3', array(), null, null, '소분류', null, 'chosen-select'); ?>
                    </span>
                    <div class="notice-info">기존에 플러스친구 아이디가 등록된 경우 같은 카테고리를 선택하셔야만 등록이 가능합니다.</div>
                </td>
            </tr>
            <tr>
                <th>휴대폰 인증</th>
                <td>
                    <div class="">
                        <input type="text" value="" id="phoneNumber" name="phoneNumber" placeholder="휴대폰번호 입력" size="36" />
                        <input type="button" value="인증번호 발송" id="getToken" class="btn btn-black btn-hf" />
                    </div>
                    <div class="">
                        <input type="text" value="" id="token" name="token" placeholder="인증번호 입력" size="36" />
                    </div>
                    <div class="notice-info">카카오톡 플러스친구 관리자센터의 내 계정 정보에 등록된 휴대폰번호와 일치해야 인증번호가 발송됩니다.</div>
                </td>
            </tr>
        </table>
    </div>
    <div>
        <div class="table-title">
            개인정보 제3자 제공 동의
        </div>
        <div class="form-inline">
            <div class="pMail-area">
                1. 제공받는자 : (주)써머스플랫폼<br/>
                2. 이용목적 : 카카오알림톡 서비스 신청 및 관리<br/>
                3. 제공항목 : 카카오톡채널(플러스친구 아이디), 휴대폰번호, 본문내용<br/>
                4. 보유 및 이용기간 : 서비스 해지 후 파기
            </div>
            <div class="mgt10 mgb20 require">
                <label class="checkbox-inline">
                    <input type="checkbox" name="approvalFl" value="y"> (필수) 개인정보 제3자 제공에 동의합니다.
                </label>
            </div>
        </div>
    </div>
</div>

<div>
    <div class="mgt10"></div>
    <div class="text-center">
        <input type="button" id="kakao_add" value="&nbsp;&nbsp;&nbsp;&nbsp;등록&nbsp;&nbsp;&nbsp;&nbsp;" class="btn btn-black btn-hf" />
        <input type="button" id="kakao_close" value="취소" class="btn btn-white btn-hf" />
    </div>
    <div class="mgt10"></div>
</div>
</form>

<script type="text/javascript">
    $(document).ready(function () {
        // 카테고리 변경
        $(document).on('change', '[id^="layer_kakaoCategory"]', function(){
            var tempId = $(this).attr('id');
            var sId = tempId.replace('layer_kakaoCategory', '');

            if (sId == '1') {
                // 중,소분류 초기화
                $('#layer_kakaoCategory2').find("option").remove();
                $('#layer_kakaoCategory2').append("<option value=''>중분류</option>");
                $('#layer_kakaoCategory3').find("option").remove();
                $('#layer_kakaoCategory3').append("<option value=''>소분류</option>");

                // 대분류 기본값 선택시
                if ($(this).val() == '') {
                    return false;
                } else {
                    $('[id^="cate2_' + $(this).val() + '"]').each(function (index) {
                        var tempCate2Id = $(this).attr('id');
                        var cate2Id = tempCate2Id.replace('cate2_', '');
                        $('#layer_kakaoCategory2').append('<option value="' + cate2Id + '">' + $(this).val() + '</option>');
                    });
                }
            } else if (sId == '2') {
                // 소분류 초기화
                $('#layer_kakaoCategory3').find("option").remove();
                $('#layer_kakaoCategory3').append("<option value=''>소분류</option>");

                // 중분류 기본값 선택시
                if ($(this).val() == '') {
                    return false;
                } else {
                    $('[id^="cate3_' + $(this).val() + '"]').each(function (index) {
                        var tempCate2Id = $(this).attr('id');
                        var cate2Id = tempCate2Id.replace('cate3_', '');
                        $('#layer_kakaoCategory3').append('<option value="' + cate2Id + '">' + $(this).val() + '</option>');
                    });
                }
            }
        });

        // 인증번호 받기 클릭시
        $('#getToken').click(function () {
            // 플러스친구아이디 입력 여부 체크
            if ($('#layer_plusId').val().trim() == '') {
                alert('플러스친구 아이디를 먼저 입력해주세요.');
                return false;
            }
            // 휴대폰번호 확인
            if ($('#phoneNumber').val().trim() == '') {
                alert('휴대폰 번호를 입력해주세요.');
                return false;
            }

            var url = 'https://alimtalk-api.bizmsg.kr/v1/sender/token';
            var data = $('#frmKakaoAlrimIdRegist').serializeObject();
            var formData = {
                'yellowId' : data.layer_plusId,
                'phoneNumber' : data.phoneNumber
            };

            $.ajax({
                url: url,
                data: JSON.stringify(formData),
                processData: false,
                contentType: 'Application/json',
                type: 'POST',
                success: function (data) {
                    if (data.code == 'success') {
                        alert('인증번호를 요청하였습니다. 카카오톡을 확인해주세요.');
                    } else {
                        alert(data.message);
                    }
                },
                error: function(data) {
                    alert('인증번호를 요청하지 못하였습니다. 잠시 후 다시 시도해주세요.');
                }
            });
        });

        $("#kakao_add").click(function () {
            $("#kakao_add").hide();
            // 플러스친구아이디 입력 여부 체크
            if ($('#layer_plusId').val().trim() == '') {
                $("#kakao_add").show();
                alert('플러스친구 아이디를 입력해주세요.');
                return false;
            }
            if ($('#layer_plusId').val().charAt(0) != '@') {
                $("#kakao_add").show();
                alert('플러스친구 아이디 앞에 @를 붙여주셔야 합니다.');
                return false;
            }

            if ($('#layer_kakaoCategory3 option:selected').val() == '') {
                $("#kakao_add").show();
                alert('카테고리를 선택해주세요.');
                return false;
            }

            if ($('#phoneNumber').val().trim() == '') {
                $("#kakao_add").show();
                alert('휴대폰 번호를 입력해주세요.');
                return false;
            }
            if ($('#token').val().trim() == '') {
                $("#kakao_add").show();
                alert('휴대폰 인증 정보를 입력해주세요.');
                return false;
            }

            if ($('input[name=approvalFl]').is(':checked') == false) {
                $("#kakao_add").show();
                alert('개인정보 제3자 제공에 동의해주세요.');
                return false;
            }

            var url = '/member/kakao_alrim_ps.php';
            var data = $('#frmKakaoAlrimIdRegist').serializeObject();
            var formData = new FormData();
            formData.append('mode', 'getKakaoKey');
            formData.append('plusId', data.layer_plusId);
            formData.append('phoneNumber', data.phoneNumber);
            formData.append('token', data.token);
            formData.append('categoryCode', data.layer_kakaoCategory3);
            $.ajax({
                url: url,
                data: formData,
                processData: false,
                contentType: false,
                type: 'POST',
                dataType: 'json',
                success: function (data) {
                    if (data.result == 'success') {
                        var saveData = new FormData();
                        saveData.append('mode', 'saveKakaoAlrimConfig');
                        saveData.append('plusId', $('#layer_plusId').val());
                        saveData.append('phoneNumber', $('#phoneNumber').val());
                        saveData.append('kakaoKey', data.data);
                        saveData.append('categoryCode', $('#layer_kakaoCategory3').val());
                        saveData.append('approvalFl', 'y');
                        $.ajax({
                            url: url,
                            data: saveData,
                            processData: false,
                            contentType: false,
                            type: 'POST',
                            dataType: 'json',
                            success: function (save) {
                                if (save.result == 'success') {
                                    $('input[name="plusId"]').val($('#layer_plusId').val());
                                    $('input[name="kakaoKey"]').val(data.data);
                                    $('input[name="useFlag"]').prop('disabled', false);
                                    $('#layerKakaoRegist').toggle();
                                    $('#layerKakaoDelete').toggle();
                                    $('.approvalLog').text('동의함 (' + save.approvalDt + ' | ' + save.approvalId + ')');
                                    alert('플러스친구 아이디 등록에 성공하였습니다.');
                                    $('.close').trigger('click');
                                } else {
                                    $("#kakao_add").show();
                                    alert('카카오 플러스친구 아이디 등록 시 비즈니스 인증이 필요합니다. 인증 후 다시 시도해주세요.');
                                    return false;
                                }
                            },
                            error: function(save) {
                                $("#kakao_add").show();
                                alert('카카오 플러스친구 아이디 등록 시 비즈니스 인증이 필요합니다. 인증 후 다시 시도해주세요.');
                                return false;
                            }
                        });
                    } else {
                        $("#kakao_add").show();
                        if (data.result == 'categoryError') {
                            dialog_confirm('카테고리를 확인해주세요. 기존에 동일한 플러스친구 아이디가 등록된 경우에는 같은 카테고리를 선택하셔야만 등록이 가능합니다. 기존에 등록된 정보로 설정하시겠습니까?', function (result) {
                                if (result) {
                                    setCategory(data.msg);
                                }
                                return false;
                            });
                        } else {
                            alert('카카오 플러스친구 아이디 등록 시 비즈니스 인증이 필요합니다. 인증 후 다시 시도해주세요.');
                        }
                    }
                    return false;
                },
                error: function(data) {
                    $("#kakao_add").show();
                    alert('카카오 플러스친구 아이디 등록 시 비즈니스 인증이 필요합니다. 인증 후 다시 시도해주세요.');
                    return false;
                }
            });
        });

        $("#kakao_close").click(function () {
            $('.close').trigger('click');
        });

        function setCategory(code) {
            var code1 = code.substring(0, 3);
            var code2 = code.substring(3, 7);
            var code3 = code.substring(7, 11);
            if (code1.length != 3 || code2.length != 4 || code3.length != 4) {
                return false;
            }

            // 셀렉트 초기화
            $('#layer_kakaoCategory2').find("option").remove();
            $('#layer_kakaoCategory2').append("<option value=''>중분류</option>");
            $('#layer_kakaoCategory3').find("option").remove();
            $('#layer_kakaoCategory3').append("<option value=''>소분류</option>");
            // 중분류 셀렉트 셋팅
            $('[id^="cate2_' + code1 + '"]').each(function (index) {
                var tempCate2Id = $(this).attr('id');
                var cate2Id = tempCate2Id.replace('cate2_', '');
                $('#layer_kakaoCategory2').append('<option value="' + cate2Id + '">' + $(this).val() + '</option>');
            });
            // 소분류 셀렉트 셋팅
            $('[id^="cate3_' + code1 + code2 + '"]').each(function (index) {
                var tempCate3Id = $(this).attr('id');
                var cate3Id = tempCate3Id.replace('cate3_', '');
                $('#layer_kakaoCategory3').append('<option value="' + cate3Id + '">' + $(this).val() + '</option>');
            });

            $('#layer_kakaoCategory1').val(code1).prop('selected', true);
            $('#layer_kakaoCategory2').val(code1 + code2).prop('selected', true);
            $('#layer_kakaoCategory3').val(code1 + code2 + code3).prop('selected', true);
        }
    });
</script>

