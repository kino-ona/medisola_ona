<script type="text/javascript">
    <!--
    $(document).ready(function () {

        $("#frmExcelForm").validate({
            dialog: false,
            rules: {
                password: {
                    required: true,
                    minlength: 10,
                    maxlength: 16,
                    equalTo: "input[name=rePassword]"
                },
                rePassword: {
                    required: true
                },
                excelDownloadReason: {
                    required: true
                }
            },
            messages: {
                password: {
                    required: "비밀번호를 입력해주세요",
                    minlength: '비밀번호는 영문대문자/영문소문자/숫자/특수문자 등 2개 포함, 10~16자 적용을 권장',
                    maxlength: '비밀번호는 영문대문자/영문소문자/숫자/특수문자 중 2개 포함, 10~16자 적용을 권장',
                    equalTo: "동일한 비밀번호를 입력해주세요."
                },
                rePassword: {
                    required: "비밀번호 확인을 입력해주세요",
                }
            },
            submitHandler: function (form) {
                <?php if($securityFl == 'y'){?>
                form.target = 'ifrmProcess';
                form.submit();
                <?php } else { ?>
                excel_download_reason(form);
                <?php } ?>
            }
        });

        //영어랑숫자만입력
        $("input.js-type-normal").bind('keyup', function () {
            $(this).val($(this).val().replace(/[^a-z0-9!@#$%^_{}~,.]*/gi, ''));
        });

        $('input[maxlength]').maxlength({
            showOnReady: true,
            alwaysShow: true
        });

        // maxlength의 경우 display none으로 되어있으면 정상작동 하지 않는다 따라서 페이지 로딩 후 maxlength가 적용된 후 display none으로 강제 처리 (임시방편 처리)
        setTimeout(function () {
            $('#frmExcelForm').find('input[maxlength]').next('span.bootstrap-maxlength').css({top: '1px', left: '255px'});
        }, 1000);
    });

    function open_order_delete_excel_auth() {
        var params = {
            mode: 'lapse_order_delete_excel_download',
            url: '../share/layer_excel_ps.php',
            data: $("#frmExcelForm").serializeArray(),
        };
        $.get('../share/layer_excel_auth.php', params, function (data) {
            BootstrapDialog.show({
                title: '엑셀 다운로드 보안 인증',
                message: $(data),
                closable: false,
                onshow: function (dialog) {
                    var $modal = dialog.$modal;
                    BootstrapDialog.currentId = $modal.attr('id');
                }
            });
        });
    }

    function excel_download_reason(form) {
        var complied = _.template($('#downloadReason').html());
        var message = complied();
        var target = $(this);
        BootstrapDialog.show({
            title: '엑셀 다운로드 사유',
            size: BootstrapDialog.SIZE_WIDE,
            message: message,
            buttons: [{
                label: '확인',
                cssClass: 'btn-black',
                hotkey: 32,
                size: BootstrapDialog.SIZE_LARGE,
                action: function (dialog) {
                    if ($('#excelDownloadReason').val() == '') {
                        $('#reasonError').removeClass('display-none');
                        return false;
                    }
                    dialog.close();
                    form.target = 'ifrmProcess';
                    form.submit();
                }
            }]
        });
    }

    function excel_download_auth_success_reason(){
        var complied = _.template($('#downloadReason').html());
        var message = complied();
        var target = $(this);
        var form = $('#frmExcelForm');
        BootstrapDialog.show({
            title: '엑셀 다운로드 사유',
            size: BootstrapDialog.SIZE_WIDE,
            message: message,
            buttons: [{
                label: '확인',
                cssClass: 'btn-black',
                hotkey: 32,
                size: BootstrapDialog.SIZE_LARGE,
                action: function (dialog) {
                    if ($('#excelDownloadReason').val() == '') {
                        $('#reasonError').removeClass('display-none');
                        return false;
                    }
                    dialog.close();
                    $('#frmExcelForm input[name=authFl]').val('y');
                    form.target = 'ifrmProcess';
                    form.submit();
                }
            }]
        });
    }

    //-->
</script>

<!-- //@formatter:off -->
<form id="frmExcelForm" name="frmExcelForm" action="../share/layer_excel_ps.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="mode" value="lapse_order_delete_excel_download" />
    <input type="hidden" name="authFl" value="" />
    <input type="hidden" name="sno" value="<?=$setData['sno']?>" />
    <input type="hidden" name="location" value="order_delete" />
    <input type="hidden" name="whereFl" value="search" />
    <input type="hidden" name="passwordFl" value="y" />
    <input type="hidden" name="excelPageNum" value="10000" />
    <input type="hidden" name="downloadFileName" value="<?=$setData['downloadFileName']?>" />
    <input type="hidden" name="layerExcelToken" value="<?=$layerExcelToken?>" >

    <div class="table-title gd-help-manual">엑셀다운로드</div>
    <table class="table table-cols no-title-line">
        <colgroup>
            <col class="width-md"/>
            <col/>
            <col class="width-md"/>
            <col/>
        </colgroup>
        <tr>
            <th>비밀번호 설정</th>
            <td colspan="3">
                <div class="form-inline pw_marking">
                    <input type="password" name="password" class="form-control width-xl js-type-normal" placeholder="영문/숫자/특수문자 2개 포함, 10~16자"/>
                    <a href="#" class="icon_pw_marking"></a>
                </div>
            </td>
        </tr>
        <tr>
            <th>비밀번호 확인</th>
            <td>
                <div class="form-inline pw_marking">
                    <input type="password" name="rePassword" class="form-control width-xl js-type-normal"/>
                    <a href="#" class="icon_pw_marking"></a>
                </div>
            </td>
        </tr>
    </table>
    <script>
        $(document).ready(function () {
            $('.icon_pw_marking').on('click', function(){
                var $thisInput = $(this).closest('.pw_marking').find('.form-control');

                if($thisInput.attr('type') == 'password'){
                    $thisInput.attr('type','text')
                    $(this).addClass('on');
                }else{
                    $thisInput.attr('type','password')
                    $(this).removeClass('on');
                }
            });
        });
    </script>
    <p class="notice-info">
        개인정보를 개인용 PC에 저장할 시 암호화가 의무이므로 비밀번호 꼭! 입력하셔야 합니다.
    </p>
    <p class="notice-info">
        개인정보의 안정성 확보조치 기준(고시)에 의거하여 개인정보 다운로드 시 사유 확인이 필요합니다.
    </p>
    <div class="table-btn">
        <input type="submit" value="다운로드" class="btn btn-lg btn-black js-order-download">
    </div>
</form>

<script type="text/html" id="downloadReason">
    <div class="search-detail-box">
        <table class="table table-cols">
            <colgroup>
                <col class="width-sm">
                <col>
            </colgroup>
            <tbody>
            <tr style="border-top: 1px solid #E6E6E6;">
                <th>사유 선택</th>
                <td>
                    <div class="form-inline">
                        <?= gd_select_box('excelDownloadReason', 'excelDownloadReason', $reasonList, null, null, '=사유 선택=', null, 'form-control'); ?>
                        <div id="reasonError" class="text-red display-none">사유 선택은 필수입니다.</div>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <div class="well">
        <div class="notice-info">개인정보의 안전성 확보조치 기준(고시)에 의거하여 개인정보를 다운로드한 경우 사유 확인이 필요합니다.</div>
    </div>
</script>

<script type="text/html" id="downloadReason">
    <div class="search-detail-box">
        <table class="table table-cols">
            <colgroup>
                <col class="width-sm">
                <col>
            </colgroup>
            <tbody>
            <tr style="border-top: 1px solid #E6E6E6;">
                <th>사유 선택</th>
                <td>
                    <div class="form-inline">
                        <?= gd_select_box('excelDownloadReason', 'excelDownloadReason', $reasonList, null, null, '=사유 선택=', null, 'form-control'); ?>
                        <div id="reasonError" class="text-red display-none">사유 선택은 필수입니다.</div>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <div class="well">
        <div class="notice-info">개인정보의 안전성 확보조치 기준(고시)에 의거하여 개인정보를 다운로드한 경우 사유 확인이 필요합니다.</div>
    </div>
</script>

<script type="text/javascript">
    <!--
    <?php if (empty($tooltipData) === false) {?>
    $(document).ready(function () {
        // 툴팁 데이터
        var tooltipData = <?php echo $tooltipData;?>;
        var sectionEle = null;
        $('#frmExcelForm .table.table-cols th').each(function(idx){
            if ($(this).closest('table').siblings('.table-title').length) {
                sectionEle = $(this).closest('table').prevAll('.table-title:first');
            } else if ($(this).closest('table').parent('div').siblings('.table-title').length) {
                sectionEle = $(this).closest('table').parent('div').prevAll('.table-title:first');
            } else {
                sectionEle = $(this).closest('table').parent('div').parent('div').prevAll('.table-title:first');
            }
            if (typeof sectionEle[0] !== "undefined") {
                var sectionTitle = $(sectionEle[0]).html().replace(/\(?<\/?[^*]+>/gi, '').trim().replace(/ /gi, '').replace(/\n/gi, '');
                var titleName = $(this).text().trim().replace(/ /gi, '').replace(/\n/gi, '');
                for (var i in tooltipData) {
                    if (tooltipData[i].title == sectionTitle) {
                        if (tooltipData[i].attribute == titleName) {
                            $(this).append('<button type="button" onclick="tooltip(this)" class="btn btn-xs js-layer-tooltip" title="' + tooltipData[i].content + '" data-placement="right" data-width="' + tooltipData[i].cntWidth + '"><span title="" class="icon-tooltip"></span></button>');
                        }
                    }
                }
            }
        });
        $(document).on('click', '.tooltip.in .tooltip-close', function () {
            $('.js-layer-tooltip[aria-describedby=' + $(this).parent().attr('id') + ']').trigger('click');
        });
        $('button.close').click(function(){
            $('.tooltip.in .tooltip-close').trigger('click');
        });
    });

    function tooltip(e) {
        if ($(e).attr('aria-describedby')) {
            $(e).tooltip('destroy');
        } else {
            var option = {
                trigger: 'click',
                container: '#content',
                html: true,
                template: '<div class="tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div><button class="tooltip-close">close</button></div>',
            };
            $(e).on('shown.bs.tooltip', function () {
                $(".tooltip.in").css({
                    width: 254,
                    maxWidth: "none",
                });
            });
            $(e).tooltip(option).tooltip('show');
        }
    }
    <?php }?>
    //-->
</script>
<style>
    .js-layer-tooltip {background-color: transparent;}
</style>
<!-- //@formatter:on -->
