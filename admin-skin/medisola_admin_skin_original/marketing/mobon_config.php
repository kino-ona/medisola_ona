<form id="frmConfig" action="dburl_ps.php" method="post" target="ifrmProcess">
    <input type="hidden" name="type" value="config" />
    <input type="hidden" name="company" value="mobon" />
    <input type="hidden" name="mode" value="config" />

    <div class="page-header js-affix">
        <h3><?php echo end($naviMenu->location); ?>
            <small></small>
        </h3>
        <input type="submit" value="저장" class="btn btn-red">
    </div>

    <div class="table-title">
        모비온 설정
    </div>

    <table class="table table-cols">
        <colgroup>
            <col class="width-md"/>
            <col/>
        </colgroup>
        <tr>
            <th>모비온 사용설정</th>
            <td>
                <label class="radio-inline">
                    <input type="radio" name="mobonFl" value="y" <?php echo gd_isset($checked['mobonFl']['y']); ?>/>사용함
                </label>
                <label class="radio-inline">
                    <input type="radio" name="mobonFl" value="n" <?php echo gd_isset($checked['mobonFl']['n']); ?>/>사용안함
                </label>
                <div class="notice-info">
                    서비스를 사용하시려면, <a href="../marketing/marketing_info.php?menu=criteo_info" class="snote btn-link" target="_blank">리타게팅 광고 안내</a>에서 사용 신청 후 사용이 가능합니다.
                </div>
            </td>
        </tr>
        <tr>
            <th>서비스 적용범위</th>
            <td>
                <label class="radio-inline">
                    <input type="radio" name="mobonRange" value="all" <?php echo gd_isset($checked['mobonRange']['all']); ?> <?php echo gd_isset($disabled); ?>/>PC + 모바일
                </label>
                <label class="radio-inline">
                    <input type="radio" name="mobonRange" value="pc" <?php echo gd_isset($checked['mobonRange']['pc']); ?> <?php echo gd_isset($disabled); ?>/>PC
                </label>
                <label class="radio-inline">
                    <input type="radio" name="mobonRange" value="mobile" <?php echo gd_isset($checked['mobonRange']['mobile']); ?> <?php echo gd_isset($disabled); ?>/>모바일
                </label>
            </td>
        </tr>
        <tr>
            <th>모비온 광고ID</th>
            <td>
                <input type="text" name="mobonId" class="form-control" style="width:250px;" value="<?php echo gd_isset($data['mobonId']); ?>" <?php echo gd_isset($disabled); ?>/>
                <div class="notice-info" >
                    모비온에서 제공하는 광고ID를 정확히 입력하여 주시기 바랍니다.
                </div>
            </td>
        </tr>
        <tr>
            <th>모비온 광고 ID 코드</th>
            <td>
                <input type="text" name="mobonIdCode" class="form-control" style="width:250px;" value="<?php echo gd_isset($data['mobonIdCode']); ?>" <?php echo gd_isset($disabled); ?>/>
                <div class="notice-info" >
                    모비온에서 제공하는 광고ID 코드를 정확히 입력하여 주시기 바랍니다.
                </div>
            </td>
        </tr>
    </table>
</form>

<div class="table-title">
    모비온 상품DB URL
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
            echo '<div><a href="' . $mallDomain . 'partner/mobon.php" target="_blank">' . $mallDomain . 'partner/mobon.php</a> <a href="' . $mallDomain . 'partner/mobon.php" target="_blank" class="btn btn-gray btn-sm">미리보기</a></div>';
            ?>
        </td>
    </tr>
</table>

<script type="text/javascript">
    <!--
    $(document).ready(function () {
        // 미사용시 범위 및 코드 disabled 처리
        $('input[name="mobonFl"]').on('click', function () {
            if ($(this).val() === 'n') {
                $('input[name="mobonRange"]').attr('disabled', 'disabled');
                $('input[name="mobonId"]').attr('disabled', 'disabled');
                $('input[name="mobonIdCode"]').attr('disabled', 'disabled');
            } else {
                $('input[name="mobonRange"]').removeAttr('disabled');
                $('input[name="mobonId"]').removeAttr('disabled');
                $('input[name="mobonIdCode"]').removeAttr('disabled');
            }
        });
    });
    //-->
</script>
