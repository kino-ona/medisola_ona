<?php
$managerEmail = implode('@', $manager['email']);
?>
<form id="frmGrowQna" name="frmGrowQna" action="index_grow_qna_ps.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="mode" value="grow_qna_register" />
    <div class="layer_helper_write" style="display:none;">
        <script>

            var originalSelectValue;
            var originalSubSelectValue;

            <?php
            echo "var managerId = '$manager[managerId]';";
            echo "var managerCellphone = '$manager[cellPhone]';";
            echo "var managerEmail = '$managerEmail';";
            ?>

            // 정규식 검사
            $.validator.addMethod('regexCheck', function (value, element, param){
                let checkType = param[0];
                let pattern = param[1];

                if (checkType) {
                    return !pattern.test(value);
                } else {
                    return pattern.test(value);
                }
            });

            $.validator.addMethod('fileSize', function (value, element) {
                // 파일 유무 체크
                if (!element.files || element.files.length == 0) {
                    return true;
                }

                let maxSize = 5 * 1024 * 1024; // 5MB 파일 업로드 제한
                let fileSize = element.files[0].size;

                return fileSize <= maxSize;
            });

            $.validator.addMethod('fileExtension', function (value, element) {
                // 파일 유무 체크
                if (!element.files || element.files.length == 0) {
                    return true;
                }

                if (/\.(gif|png|bmp|jpg|jpeg|xlsx|xls|doc|hwp|zip|rar|alz)$/i.test(value)) {
                    return true;
                }

                return false;
            });

            $(document).ready(function() {
                // 입력 제한
                function block_input(id, type, pattern = null) {
                    switch (type) {
                        case 'block':
                            $(id).on("input", function() {
                                var value = $(this).val();
                                var oldValue = $(this).data("oldValue");

                                if (!value.match(pattern)) {
                                    value = oldValue;
                                }

                                $(this).data("oldValue", value);
                                $(this).val(value);
                            });
                            break;
                        case 'replace':
                            $(id).on("input", function() {
                                var value = $(this).val();
                                value = value.replace(pattern, '');

                                $(this).val(value);
                            });
                            break;
                        case 'trim':
                            $(id).on("focusout", function() {
                                var value = $(this).val();
                                value = value.trim();

                                $(this).val(value);
                            });
                            break;
                    }
                }

                block_input("#form_title", 'replace', /[\&\"\'\<\>]/);
                block_input("#form_title", 'trim');
                block_input("#form_content", 'trim');
                block_input("#form_adminer", 'block', /^[0-9A-Za-z!"#$%&'()*+,\-./:;<=>?@\[\\\]^_`{|}~]*$/g);
                block_input("#form_adminer_pw", 'block', /^[0-9A-Za-z!"#$%&'()*+,\-./:;<=>?@\[\\\]^_`{|}~]*$/g);
                block_input("#form_ftp", 'replace', /[\s]/g);
                block_input("#form_ftpId", 'block', /^[0-9A-Za-z]*$/g);
                block_input("#form_ftpPassword", 'block', /^[0-9A-Za-z]*$/g);
                block_input("#form_account_holder", 'replace', /[!@#$%^&*()_+{}\[\]:;<>,?~\\\-=|]/g);
                block_input("#form_bank_name", 'replace', /[!@#$%^&*()_+{}\[\]:;<>,?~\\\-=|]/g);
                block_input("#form_account_number", 'block', /^[0-9]*$/);
                block_input("#form_number", 'block', /^[0-9]*$/);

                $("#frmGrowQna").validate({
                    submitHandler: function (form) {
                        if($("#ck_ag").is(":checked") == true){
                            form.target = 'ifrmProcess';
                            form.submit();
                        }else{
                            alert('개인정보 수집/동의를 체크하여 주세요.')
                            return false;
                        }
                    },
                    rules: {
                        ck_ag: {
                            required: function() {
                                return ($('input[name=ck_ag]:checked').val() == 'e');
                            },
                        },
                        categoryNo: {
                            required: true
                        },
                        title: {
                            required: true
                        },
                        contents: {
                            required: true
                        },
                        qnaFile: {
                            fileExtension: true,
                            fileSize: true
                        },
                        adminId: {
                            required: true,
                            regexCheck: [false, /^[0-9A-Za-z!"#$%&'()*+,\-./:;<=>?@\[\\\]^_`{|}~]*$/]
                        },
                        ftpId: {
                            regexCheck: [false, /^[0-9A-Za-z]*$/]
                        },
                        adminPassword: {
                            required: true,
                            regexCheck: [false, /^[0-9A-Za-z!"#$%&'()*+,\-./:;<=>?@\[\\\]^_`{|}~]*$/]
                        },
                        ftpPassword: {
                            regexCheck: [false, /^[0-9A-Za-z]*$/]
                        },
                        email: {
                            required: true,
                            email: true,
                            regexCheck: [true, /[\'"<>;:,\[\]]+|[^@]*@@+/]
                        },
                        hp: {
                            required: true,
                            minlength: 8,
                            regexCheck: [false, /^01([0|1|6|7|8|9])/]
                        }
                    },
                    messages: {
                        categoryNo: {
                            required: '문의 분류를 선택해 주세요.'
                        },
                        title: {
                            required: '문의 제목을 입력해 주세요.'
                        },
                        contents: {
                            required: '문의 내용을 입력해 주세요.'
                        },
                        qnaFile: {
                            fileExtension: '지원하지 않는 형식의 파일입니다.',
                            fileSize: '지원하지 않는 크기의 파일입니다.'
                        },
                        adminId: {
                            required: '관리자 아이디를 입력해 주세요.',
                            regexCheck: '입력하신 아이디가 올바르지 않습니다.'
                        },
                        ftpId: {
                            regexCheck: '입력하신 FTP 아이디가 올바르지 않습니다.'
                        },
                        adminPassword: {
                            required: '관리자 비밀번호를 입력해 주세요.',
                            regexCheck: '입력하신 비밀번호가 올바르지 않습니다.'
                        },
                        ftpPassword: {
                            regexCheck: '입력하신 FTP 비밀번호가 올바르지 않습니다.'
                        },
                        email: {
                            required: '이메일 주소를 입력해 주세요.',
                            email: '입력하신 이메일 주소가 올바르지 않습니다.',
                            regexCheck: '입력하신 이메일 주소가 올바르지 않습니다.'
                        },
                        hp: {
                            required: '휴대폰 번호를 입력해 주세요.',
                            minlength: '입력하신 휴대폰 번호가 올바르지 않습니다.',
                            regexCheck: '입력하신 휴대폰 번호가 올바르지 않습니다.'
                        }
                    }
                });

                // 1:1문의 아이콘 클릭 시 레이어팝업 노출
                $('.btn_grow').on('click', function (){
                    if(!managerEmail && !managerCellphone){
                        alert('관리자 휴대번호 또는 이메일을 인증 등록하여 주세요.');
                    }else{
                        $('.layer_helper_write').show();
                        $("#form_number").val(managerCellphone);
                        $("#form_email").val(managerEmail);
                        $("#form_adminer").val(managerId);
                    }
                });


                $('.btn_err').on('click', function (){
                    alert('1:1 문의 서비스는 최고운영자 계정으로만 이용이 가능합니다. 그 외 추가 계정은 추후 서비스 제공될 예정입니다.');
                });

                $('.bg_red').on('click', function (){
                    //alert(11)
                });
                // 첨부파일 처리
                $('.input_add_file .input_file').change(function(){
                    var i = $(this).val();
                    $(this).parents('.input_add_file').find('.input_text').val(i);
                });

                // 첨부파일 삭제 처리
                $('.btn_del').on('click', function (e){
                    e.preventDefault();
                    $(this).parents('.input_add_file').find('input').val('');
                })

                // 개인정보 수집 이용 내용 펼치기, 숨기기
                $('.btn_sT').on('click', function (e) {
                    e.preventDefault();
                    if (!$(this).closest('.input_group').hasClass('show')) {
                        $(this).closest('.input_group').addClass('show');
                        $(this).text('숨기기');
                    } else {
                        $(this).closest('.input_group').removeClass('show');
                        $(this).text('펼치기');
                    }
                });

                // 레이어 팝업 닫기
                $('.helper_write_wrap .btn_close').on('click', function(e){
                    e.preventDefault();
                    $('.layer_helper_write').hide();
                    $('body').removeAttr('style');
                });
                $('.btn_pop_cancle').on('click', function(){
                    $('.layer_helper_write').hide();
                });

                // 문의 분류 기억
                $('#categoryNo').focus(function () {
                    originalSelectValue= $('#categoryNo').val();
                    originalSubSelectValue= $('#categorySubNo').val();
                });
                $('#categorySubNo').focus(function () {
                    originalSubSelectValue= $('#categorySubNo').val();
                });

            });

            function change_category(val) {
                var textareaContent = $("#form_content").val();
                if (textareaContent !== "") {
                    dialog_confirm('문의 분류 변경 시 작성한 문의 내용이 삭제됩니다.<br>문의 분류를 변경하시겠습니까?', function (result) {
                        if (result) {
                            $("#form_content").val("");
                            select_category(val);
                        } else {
                            $('#categoryNo').val(originalSelectValue);
                            $('#categorySubNo').val(originalSubSelectValue);
                        }
                    });
                } else {
                    select_category(val);
                }
            };

            function change_sub_category() {
                var textareaContent = $("#form_content").val();
                if (textareaContent !== "") {
                    dialog_confirm('문의 분류 변경 시 작성한 문의 내용이 삭제됩니다.<br>문의 분류를 변경하시겠습니까?', function (result) {
                        if (result) {
                            $("#form_content").val("");
                        } else {
                            $('#categorySubNo').val(originalSubSelectValue);
                        }
                    });
                }
            };
            
            //2차 select_category
            function select_category(val) {
                $.post('index_grow_qna_ps.php', {'mode': 'select_category', 'categoryNo': val }, function (data) {
                    //console.log(data);
                    var locationList = $.parseJSON(data);
                    var addHtml = "<option value=''>선택</option>";
                    if(locationList.info) {
                        $.each(locationList.info, function (key, val) {
                            addHtml += "<option value='" + key + "'>" + val+ "</option>";
                        });
                    }
                    $('select[name="categorySubNo"]').html(addHtml);

                    <?php if($data['categoryNo'] && $data['categorySubNo']) { ?>
                    $('select[name="categorySubNo"]').val('<?=$data['categorySubNo']?>');
                    <?php } ?>
                });
            }

        </script>
        <div class="helper_write_wrap_dim"></div>
        <div class="helper_write_wrap">
            <h1>1:1문의하기</h1>
            <a href="#" class="btn_close">닫기</a>
            <div class="gray_box">
                <div style="font-size: 10pt;font-weight:800">[안내사항]</div>
                <ul class="dot_li">
                    <li>질문/답변 내역은 아래 '문의 서비스' 에 가입된 회원정보 '마이페이지' 에서 확인 가능합니다.</li>
                    <li><strong>문의 서비스: [고도몰 <?php echo Globals::get('gLicense.ecKind'); ?>] <?= $svcInfos['mallDomain']; ?> </strong></li>
                </ul>
            </div>
            <h2>1. 문의유형 선택</h2>
            <table>
                <colgroup>
                    <col style="width:160px;">
                    <col style="wiath:auto;">
                </colgroup>
                <tbody>
                <tr>
                    <th scope="row"><span class="colRed">*</span> <label>분류</label></th>
                    <td>
                        <div class="col_2_select">
                            <div class="select_box err">
                                <select id="categoryNo" name="categoryNo" class="form-control select_form" onchange="change_category(this.value)">
                                    <option value="">선택</option>
                                    <?php foreach($categoryNo as $k => $v) { ;?>
                                        <option value="<?=$k?>" <?php if($data['categoryNo'] == $k) { echo "selected='selected'"; }  ?>><?=$v?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="select_box select_dis">
                                <select id="categorySubNo" name="categorySubNo" class="form-control select_form" onchange="change_sub_category()">
                                    <option value="">선택</option>
                                </select>
                            </div>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
            <h2>2. 문의 내용</h2>
            <table>
                <colgroup>
                    <col style="width:160px;">
                    <col style="wiath:auto;">
                </colgroup>
                <tbody>
                <tr>
                    <th scope="row"><span class="colRed">*</span> <label for="form_title">제목</label></th>
                    <td>
                        <input type="text" id="form_title" name="title" maxlength="50" class="form-control" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><span class="colRed">*</span> <label for="form_content">내용</label></th>
                    <td>
                            <textarea id="form_content" class="form-control" placeholder="※ 내용 본문에 이메일 주소, 연락처, 계좌번호 등 개인 정보를 절대로 입력하지 마시기 바랍니다.
※ FTP, DB 등의 ID/PW 정보는 '추가 정보 작성' 단계에 입력해 주시기 바랍니다.
※ 디자인 분석 및 HTML 수정은 지원하지 않습니다.
   디자인 관련 내용은 에이전시를 통해 문의 부탁 드립니다." name="contents" ></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="form_file">파일첨부</label></th>
                    <td>
                        <div class="input_add_file">
                            <div>
                                <input type="file" id="form_file" class="input_file" name="qnaFile" />
                                <input type="text" class="input_text" placeholder="선택된 파일 없음" readonly name="qnaFile"/>
                                <button class="btn_del"></button>
                            </div>
                            <label for="form_file">파일선택</label>
                        </div>
                        <p class="input_bot_infor_txt">최대 5MB 이하, gif, png, bmp, jpeg, jpg, xlsx, xls, doc, hwp, zip, rar, alz 파일만 첨부 가능</p>
                    </td>
                </tr>
                </tbody>
            </table>
            <h2 class="underline">3. 테스트 정보</h2>
            <ul class="comm_li">
                <li class="colRed">• 문의주신 내용을 더욱 안전하게 처리할 수 있도록 관리자정보에는 CS계정을 입력해주세요.</li>
                <li>• CS계정 생성은 [고도몰 관리자> 기본설정 > 관리 정책 > 운영자 관리 > CS계정 관리] 에서 가능합니다.</li>
                <li>• FTP 미사용 쇼핑몰은 관리자 아이디와 비밀번호만 입력해 주세요.</li>
                <li class="colRed">• 입력된 정보는 안전하게 암호화 처리되어 저장되며, 답변 완료 후 자동 삭제 됩니다.</li>
            </ul>
            <table>
                <colgroup>
                    <col style="width:160px;">
                    <col style="wiath:auto;">
                </colgroup>
                <tbody>
                <tr>
                    <th scope="row"><span class="colRed">*</span> <label for="form_adminer_pw">관리자 정보</label></th>
                    <td>
                        <div class="col_2_input">
                            <input type="text" placeholder="관리자 아이디" id="form_adminer" name="adminId" maxlength="60" class="form-control" />
                            <input type="password" id="form_adminer_pw" placeholder="관리자 비밀번호" name="adminPassword" maxlength="30" class="form-control" />
                        </div>
                        <p class="min_txt" style="float:left;">영문, 숫자, 특수문자 입력 가능합니다.</p><p class="min_txt" style="float:right; width:calc(50% - 4px);">영문, 숫자, 특수문자 입력 가능합니다.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><span class="colRed">*</span> <label for="form_ftp">FTP 정보</label></th>
                    <td>
                        <input type="text" id="form_ftp" name="ftpAddress" maxlength="320" placeholder="FTP 주소" />
                        <div class="col_2_input">
                            <input type="text" id="form_ftpId" name="ftpId" maxlength="12" placeholder="FTP 아이디" />
                            <input type="password" id="form_ftpPassword" name="ftpPassword" placeholder="FTP 비밀번호" maxlength="16" class="form-control"/>
                        </div>
                        <p class="min_txt" style="float:left;">영문 및 숫자만 입력 가능합니다.</p><p class="min_txt" style="float:right; width:calc(50% - 4px);">영문 및 숫자만 입력 가능합니다.</p></div>
                    </td>
                </tr>
                </tbody>
            </table>
            <h2 class="underline">4. 환불 정보</h2>
            <ul class="comm_li">
                <li>• 환불이 필요한 경우 환불 정보를 입력해 주세요.</li>
                <li>• 접수된 정보는 암호화 처리되어 접수되며, 답변 완료 후 자동 파기됩니다.</li>
            </ul>
            <table>
                <colgroup>
                    <col style="width:160px;">
                    <col style="wiath:auto;">
                </colgroup>
                <tbody>
                <tr>
                    <th scope="row"><label for="form_account_holder">환불 정보</label></th>
                    <td>
                        <div class="col_2_input">
                            <input type="text" placeholder="예금주 성함" id="form_account_holder" name="accountHolder" maxlength="50" class="form-control" />
                            <input type="text" id="form_bank_name" placeholder="환불 은행명" name="bankName" maxlength="50" class="form-control" />
                        </div>
                        <p class="min_txt" style="float:left;">기호는 .만 입력 가능합니다.</p><p class="min_txt" style="float:right; width:calc(50% - 4px);">기호는 .만 입력 가능합니다.</p>
                        <input type="text" id="form_account_number" name="accountNumber" maxlength="14" placeholder="환불 계좌 번호 (숫자만 입력)" />
                    </td>
                </tr>
                </tbody>
            </table>
            <h2 class="underline">5. 연락처</h2>
            <ul class="comm_li">
                <li>• 답변 완료 시 답변 완료 알림 SMS와, 이메일로 답변 내용을 전달드립니다.</li>
                <li>• 답변 내용은 NHN커머스 1:1문의 내역에서도 확인할 수 있습니다.</li>
            </ul>
            <table>
                <colgroup>
                    <col style="width:160px;">
                    <col style="wiath:auto;">
                </colgroup>
                <tbody>
                <tr>
                    <th scope="row"> <span class="colRed">*</span> 휴대폰 번호</th>
                    <td>
                        <input type="text" id="form_number" class="wid304"  name="hp" maxlength="11" placeholder="- 없이 입력하세요."/>
                    </td>
                </tr>
                <tr>
                    <th scope="row"> <span class="colRed">*</span> 이메일 주소</th>
                    <td>
                        <input type="text" id="form_email" class="wid304" name="email" maxlength="320" placeholder="이메일 형식에 맞춰 작성해 주세요."/>
                    </td>
                </tr>
                </tbody>
            </table>
            <h2 class="underline">6. 개인정보 수집/동의</h2>
            <div class="input_group">
                <div class="form_group">
                    <div class="form_input_ck">
                        <input id="ck_ag" type="checkbox">
                        <label class="ck_ag" for="ck_ag"><strong>[필수]</strong> 개인정보 수집ㆍ이용에 동의합니다.</label>
                    </div>
                    <a href="#" class="btn_sT">펼치기</a>
                </div>
                <div class="box_privacy">
                    <p class="title">개인정보 수집 및 이용 동의</p>
                    <p>
                        1. 개인정보 수집 및 이용 목적 : 문의에 대한 상담 및 처리<br />
                        2. 수집하는 개인 정보 항목 : 연락처(휴대폰번호), 이메일, 쇼핑몰 접속정보(관리자, FTP), 사업자등록증 등 증빙서류 내 기재정보(본인확인, 위임 등 증빙 필요시)<br />
                        <span>3. 보유 및 이용 기간 : 수집, 이용 동의 일로부터 이용 목적 달성 시까지 보관</span>(단, 관계법령의 규정에 의하여 보존할 필요가 있는 경우 해당 기간 동안 개인정보를 보관)
                    </p>
                    <p>
                        ※ 이용자는 개인정보 수집 및 이용에 동의하지 않을 권리가 있습니다. 다만 개인정보 수집 및 이용 동의를 거부하실 경우 서비스 이용에 제한이 있을 수 있습니다. <br />
                        ※ 상담 처리 과정에서 불가피한 경우 상담 관리자는 쇼핑몰 관리자 페이지, DB에 접근할 수 있습니다.<br />
                        ※ 쇼핑몰 접속정보가 변경되면 문의 내용에 변경 내역을 알려주셔야 상담처리가 가능합니다.
                    </p>
                </div>
            </div>
            <div class="btn_area">
                <span class="btn_round btn_pop_cancle" style="cursor: pointer">취소</span>
                <input type="submit" value="등록" class="btn_round bg_red btn_pop_submit">
            </div>
        </div>
    </div>
</form>


