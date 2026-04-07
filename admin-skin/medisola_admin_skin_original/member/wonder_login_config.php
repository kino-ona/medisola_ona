<form id="form" name="form" action="wonder_login_request_ps.php" method="post" enctype="multipart/form-data" target="ifrmProcess">
    <input type="hidden" name="firstCheck" value="<?=$data['useFl']?>" />
    <input type="hidden" name="mode" value="useFl" />
    <div class="page-header js-affix">
        <h3><?php echo end($naviMenu->location); ?></h3>
        <input type="button" value="저장" id="btnConfirm" class="btn btn-red"/>
    </div>

    <div class="table-title">
        위메프 아이디 로그인 설정
    </div>
    <table class="table table-cols">
        <colgroup>
            <col class="width-md"/>
            <col/>
        </colgroup>
        <tbody>
        <tr>
            <th>사용여부</th>
            <td>
                <label class="radio-inline">
                    <input type="radio" name="useFl" id="useFlY" value="y" <?= $checked['useFl']['y']; ?> <?=$data['useFl'] == 'f' ? 'disabled="disabled"' : '' ?>>
                    사용함
                </label>
                <label class="radio-inline">
                    <input type="radio" name="useFl" id="useFlN" value="n" <?= $checked['useFl']['n']; ?>>
                    사용안함
                </label>
                <?php if($data['useFl'] == 'f') { ?>
                    <div class="notice-info">[<a href="#" class="btn-link regist-btn">위메프 아이디 로그인 사용 신청</a>]을 통해 신청을 하셔야 위메프 아이디 로그인 사용을 하실 수 있습니다.</div>
                <?php } ?>
                <div class="notice-info">사용함으로 선택 시 쇼핑몰에 위메프 아이디 로그인 영역이 노출되지 않으면 스킨패치를 진행하시기 바랍니다.</div>
                <div class="notice-info">위메프 아이디 로그인 사용시 쇼핑몰 본인인증 서비스는 실행되지 않습니다.</div>
            </td>
        </tr>
        <tr>
            <th class="require">Client ID</th>
            <td>
                <label>
                    <input type="text" name="clientId" id="clientId" value="<?= $data['clientId']; ?>" class="form-control width-2xl useFl" readonly="readonly"/>
                </label>
                <?php if($data['useFl'] == 'f') { ?>
                    <button type="button" class="btn btn-gray btn-sm js-wonder-login-request" version="<?= $data['useFl'] == 'f' ? 'first' : 'second' ?>"> 위메프 아이디 로그인 사용 신청</button>
                <?php } else { ?>
                    <span class="notice-info mgl5">재신청 및 Client ID는 변경할 수 없습니다.</span>
                <?php } ?>
            </td>
        </tr>
        <tr>
            <th class="require">Client 시크릿코드</th>
            <td>
                <label>
                    <input type="text" name="clientSecret" id="clientSecret" value="<?= $data['clientSecret']; ?>" class="form-control width-2xl useFl" readonly="readonly"/>
                </label>
            </td>
        </tr>
        <tr>
            <th>위메프 아이디 로그인<br />사용 신청정보</th>
            <td>
                <div>1. 회사명 : <?= $data['companyName']; ?></div>
                <div>2. 쇼핑몰 : <?= $data['serviceName']; ?></div>
                <div>3. 이름 : <?= $data['serviceUserName']; ?></div>
                <div>4. 이메일 : <?= $data['serviceEmail']; ?> </div>
                <div>5. 사업자등록번호 : <?= $data['businessNo']; ?></div>
                <div>
                    <div class="flo-left">6. 리다이렉트 URI : </div>
                    <div class="flo-left"><?= str_replace(',', '<br />', $data['redirectUri']); ?></div>
                </div>
                <?php if($data['useFl'] != 'f') { ?>
                    <div class="notice-info clear-left">신청한 정보가 다를 경우 [<a href="#" class="btn-link modify-btn">위메프 아이디 로그인 정보 수정</a>]을 클릭하여 재신청 해주시기 바랍니다.</div>
                <?php } ?>
            </td>
        </tr>
        </tbody>
    </table>
</form>
<script type="text/javascript">
    $(function() {
        $(".regist-btn").click(function(e) {
            e.preventDefault();
            $('.js-wonder-login-request').trigger("click");
        });
        $(".modify-btn").click(function(e) {
            $.ajax({
                url: '../member/layer_wonder_login_request.php?mode=modify',
                type: 'get',
                async: false,
                success: function (data) {
                    BootstrapDialog.show({
                        title: '위메프 아이디 로그인 정보 수정',
                        size: BootstrapDialog.SIZE_WIDE_LARGE,
                        message: $(data),
                        closable: true
                    });
                }
            });
        });

        $('.js-wonder-login-request').click(function (e) {
            e.preventDefault();
            var firstCheck = $("input[name=firstCheck]").val();

            if (firstCheck == "f" || $(':radio[name="useFl"]:checked').val() == 'y') {
                var loadChk = 0;
                $.ajax({
                    url: '../member/layer_wonder_login_request.php',
                    type: 'get',
                    async: false,
                    success: function (data) {
                        if (loadChk == 0) {
                            data = '<div id="layerWonderLogin">' + data + '</div>';
                        }
                        BootstrapDialog.show({
                            title: '위메프 아이디 로그인 사용신청',
                            size: BootstrapDialog.SIZE_WIDE_LARGE,
                            message: $(data),
                            closable: true
                        });
                    }
                });
            } else {
                alert('사용설정을 사용함으로 선택해 주시기 바랍니다.');
            }
        });

        $(document).on('keydown', 'input[name="serviceName"]', function(e) {
            if (e.key.search(pattern) > -1) {
                e.preventDefault();
            }
            if(e.keyCode == 25) {
                e.preventDefault();
            }
            if ($(this).val().length > $(this).attr('maxlength')) {
                alert("최대 " + $(this).attr('maxlength') + "자까지만 입력이 가능합니다.");
                $(this).val($(this).val().substr(0, $(this).attr('maxlength')));
            }
        }).on('paste', 'input[name="serviceName"]', function(e) {
            /*var thisValue = $(this);
            setTimeout(function() {
                var pasteValue = thisValue.val();
                if (pattern.test(pasteValue)) {
                    pasteValue = pasteValue.replace(/[^ㄱ-ㅎㅏ-ㅣ가-힣a-zA-Z0-9\s\-\_]/g, "");
                    $('.check-msg-' + thisValue.attr("flag")).addClass('display-none');
                }
                $(thisValue).val(pasteValue);
            }, 100);*/
        }).on('input propertychange', 'input[name="serviceName"]', function(e) {
            /*var value = e.target.value;
            var checkMsg = '.check-msg-' + $(this).attr("flag");
            if (value.search(pattern) > -1) {
                $(checkMsg).removeClass('display-none');
            } else {
                $(checkMsg).addClass('display-none');
            }
            if ($(this).val().length > $(this).attr('maxlength')) {
                alert("최대 " + $(this).attr('maxlength') + "자까지만 입력이 가능합니다.");
                $(this).val($(this).val().substr(0, $(this).attr('maxlength')));
            }*/
        });

        $(document).on('click', '#btnConfirm', function () {
            target = 'form';
            dataSubmit(target);
        });

        $(document).on('click', '#layerBtnConfirm', function () {
            target = 'layerForm';
            dataSubmit(target);
        });
    });

    function dataSubmit(target) {
        if (target == 'form' && !$('input[name="clientId"]').val()) {
            return false;
        }
        var mode = $('#' + target + ' input[name="mode"]').val();

        if($('#' + target + ' input[name="companyName"]').val() == "") {
            alert('회사명을 입력해주세요.');
            return false;
        }
        if($('#' + target + ' input[name="serviceName"]').val() == "") {
            alert('쇼핑몰명을 입력해주세요.');
            return false;
        }
        if (mode == 'regist') {
            if($('#' + target + ' input[name="serviceUserName"]').val() == "") {
                alert('이름을 입력해주세요.');
                return false;
            }
            if($('#' + target + ' input[name="serviceEmail"]').val() == "") {
                alert('이메일을 입력해주세요.');
                return false;
            }
            if($('#' + target + ' input[name="businessNo"]').val() == "") {
                alert('사용자등록번호를 입력해주세요.');
                return false;
            }
        }
        if($('#' + target + ' input[name="redirectUri"]').val() == "") {
            alert('리다이렉트 URI를 입력해주세요.');
            return false;
        }
        if (mode == 'regist') {
            if($('#' + target + ' input[name="agreementFlag"]').is(':checked') != true) {
                alert('위메프 아이디 로그인 서비스 사용하려면 개인정보 제3자 제공에 동의해 주세요.');
                return false;
            }
        }

        document.getElementById(target).target = 'ifrmProcess';
        document.getElementById(target).submit();
    }
</script>
