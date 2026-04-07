<form id="frmConfig" action="dburl_ps.php" method="post" target="ifrmProcess">
    <input type="hidden" name="type" value="config" />
    <input type="hidden" name="company" value="criteo" />
    <input type="hidden" name="mode" value="config" />

    <div class="page-header js-affix">
        <h3><?php echo end($naviMenu->location); ?>
            <small></small>
        </h3>
        <input type="submit" value="저장" class="btn btn-red btn-save-config">
    </div>

    <div class="table-title">
        크리테오 설정
    </div>

    <table class="table table-cols">
        <colgroup>
            <col class="width-md"/>
            <col/>
        </colgroup>
        <tr>
            <th>크리테오 사용설정</th>
            <td>
                <label class="radio-inline">
                    <input type="radio" name="criteoFl" value="y" <?php echo gd_isset($checked['criteoFl']['y']); ?>/>사용함
                </label>
                <label class="radio-inline">
                    <input type="radio" name="criteoFl" value="n" <?php echo gd_isset($checked['criteoFl']['n']); ?>/>사용안함
                </label>
                <div class="notice-info">
                    서비스를 사용하시려면, <a href="../marketing/marketing_info.php?menu=criteo_info" class="snote btn-link" target="_blank">리타게팅 광고 안내</a>에서 사용 신청 후 사용이 가능합니다.
                </div>
            </td>
        </tr>
        <tr>
            <th>네이버페이 구매전환<br/>성과 측정 설정</th>
            <td>
                <label class="radio-inline">
                    <input type="radio" name="criteoNaverPayFl" value="y" <?php echo gd_isset($checked['criteoNaverPayFl']['y']); ?> <?php echo gd_isset($disabled); ?> <?php echo gd_isset($naverPayFl); ?>/>사용함
                </label>
                <label class="radio-inline">
                    <input type="radio" name="criteoNaverPayFl" value="n" <?php echo gd_isset($checked['criteoNaverPayFl']['n']); ?> <?php echo gd_isset($disabled); ?> <?php echo gd_isset($naverPayFl); ?>/>사용안함
                </label>
                <div class="notice-info">
                    네이버페이를 아직 신청하지 않은 경우 먼저 신청을 진행해 주시기 바랍니다. <a href="https://admin.pay.naver.com/join/step1/select" class="snote btn-link" target="_blank">바로가기></a>
                </div>
                <div class="notice-info">
                    <a href="../policy/naver_pay_config.php" class="snote btn-link" target="_blank">기본설정 > 결제 정책 > 네이버페이 설정</a>에서 사용 여부를 ‘사용함’으로 설정된 경우에만 구매전환 성과 측정이 가능합니다.
                </div>
                <div class="notice-info">
                    N PAY 구매 버튼 클릭 시점에 구매전환 성과 측정이 진행되어 실제 구매 측정값과 상이할 수 있습니다.
                </div>
            </td>
        </tr>
        <tr>
            <th>서비스 적용범위</th>
            <td>
                <label class="radio-inline">
                    <input type="radio" name="criteoRange" value="all" <?php echo gd_isset($checked['criteoRange']['all']); ?> <?php echo gd_isset($disabled); ?>/>PC + 모바일
                </label>
                <label class="radio-inline">
                    <input type="radio" name="criteoRange" value="pc" <?php echo gd_isset($checked['criteoRange']['pc']); ?> <?php echo gd_isset($disabled); ?>/>PC
                </label>
                <label class="radio-inline">
                    <input type="radio" name="criteoRange" value="mobile" <?php echo gd_isset($checked['criteoRange']['mobile']); ?> <?php echo gd_isset($disabled); ?>/>모바일
                </label>
            </td>
        </tr>
        <tr>
            <th>크리테오 광고ID</th>
            <td>
                <input type="text" name="criteoCode" class="form-control" style="width:250px;" value="<?php echo gd_isset($data['criteoCode']); ?>" <?php echo gd_isset($disabled); ?>/>
                <div class="notice-info" >
                    크리테오에서 제공하는 광고주코드를 정확히 입력하여 주시기 바랍니다.
                </div>
            </td>
        </tr>
    </table>
</form>

<div class="table-title">
    크리테오 상품DB URL
</div>

<table class="table table-cols">
    <colgroup>
        <col class="width-md"/>
        <col/>
    </colgroup>
    <tr>
        <th>상품DB URL페이지</th>
        <td>
            <?php
            echo '<div><a href="' . $mallDomain . 'partner/criteo.php" target="_blank">' . $mallDomain . 'partner/criteo.php</a> <a href="' . $mallDomain . 'partner/criteo.php" target="_blank" class="btn btn-gray btn-sm">미리보기</a></div>';
            ?>
            <div class="notice-info">
                1일 1회 08시 스케줄러를 통해 상품 피드 EP가 생성되기 때문에 08시 이후 사용설정을 하신 경우 다음날 08시 이후에 확인이 가능합니다.
            </div>
        </td>
    </tr>
</table>

<script type="text/javascript">
    <!--
    $(document).ready(function () {
        // 미사용시 범위 및 코드 disabled 처리
        $('input[name="criteoFl"]').on('click', function () {
            if ($(this).val() === 'n') {
                $('input[name="criteoNaverPayFl"]').attr('disabled', 'disabled');
                $('input[name="criteoRange"]').attr('disabled', 'disabled');
                $('input[name="criteoCode"]').attr('disabled', 'disabled');
            } else {
                $('input[name="criteoNaverPayFl"]').removeAttr('disabled');
                $('input[name="criteoRange"]').removeAttr('disabled');
                $('input[name="criteoCode"]').removeAttr('disabled');
            }
        });

        // 저장시 크리테오 광고ID 체크
        $(document).on('click','.btn-save-config', function (e) {
            if($('input[name=criteoFl]:checked').val() == "y" && $('input[name=criteoCode]').val() == "") {
                alert("크리테오 광고 ID를 입력해주세요.");
                return false;
            }
        });
    });
    //-->
</script>
