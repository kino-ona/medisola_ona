<?php
/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall to newer
 * versions in the future.
 *
 * @copyright ⓒ 2022, NHN COMMERCE Corp.
 */
?>

<form id="facebookConfigExtension" action="dburl_ps.php" method="post">
    <div class="page-header js-affix">
        <h3><?php echo end($naviMenu->location); ?>
            <small></small>
        </h3>
        <input type="button" value="설정 방법 확인" name="openManual" id="openManual" class="btn btn-white" style="margin-right: 80px">
        <input type="submit" value="저장" class="btn btn-red fbe2-submit">
    </div>

    <input type="hidden" name="type" value="config"/>
    <input type="hidden" name="company" value="facebookExtensionV2"/>
    <input type="hidden" name="mode" value="config"/>
    <input type="hidden" name="domainCodeSaveFl" value="y"/>
    <table class="table table-cols">
        <colgroup>
            <col class="width-md"/>
            <col/>
        </colgroup>
        <div class="table-title">
            페이스북 광고 설정
        </div>
        <tr>
            <th>Facebook Business Extension 설정</th>
            <td>
                <?php if (empty($fbe2Data) === false) { ?>
                    <input type="button" class="btn btn-gray btn-sm" name="btnFbe2Popup" id="btnFbe2Popup" value="Facebook Business Extension 설정변경"/>
                    <input type="button" class="btn btn-gray btn-sm" name="btnFbe2DisconnPopup" id="btnFbe2DisconnPopup" value="설정해제"/>
                <?php } else { ?>
                    <input type="button" class="btn btn-gray btn-sm" name="btnFbe2Popup" id="btnFbe2Popup" value="Facebook Business Extension 시작하기"/>
                <?php } ?>
                <div class="notice-info">
                    Facebook Business Extension(FBE) 을 이용하면 페이스북 페이지 연결, 비즈니스 관리자 연결, 광고 계정 연결, 픽셀 ID 설정, 제품 피드 연동까지 설정 가능합니다.
                </div>
                <div class="notice-info">페이스북 광고 설정이 ‘피드 생성’으로 설정된 상품이 1개 이상 있을 경우에만 기능 사용이 가능합니다. (설정은 각각의 상품등록 페이지 > 페이스북 광고 설정에서 설정하실 수 있습니다.)
                </div>
            </td>
        </tr>
        <?php if (empty($fbe2Data) === false) { ?>
            <tr>
                <th>Facebook Business<br />Extension 설정정보</th>
                <td>
                    픽셀 ID: <span id="pixelIdCheck"><?php echo $fbe2Data['pixel_id'] ?></span>
                    <div>
                        <a href="./facebook_extension_v2_ps.php?mode=link" target="about:blank" style="cursor:pointer; color:blue;text-decoration: underline; ">관리자 바로가기</a><br />
                        <a href="https://www.facebook.com/products/catalogs/<?php echo $fbe2Data['product_catalog_id']?>/products" target="_blank" style="cursor:pointer; color:blue;text-decoration: underline; ">카탈로그 바로가기</a>
                        (마지막 페이스북 제품 피드 생성 정보 : 총 <?php echo $totalGoodsFeedCnt ?>개, <?php echo $makeFileTime ?>)
                    </div>
                    <div class="notice-info">페이스북 '피드 생성' 대상 상품이 많을 경우 피드 파일 생성 시간이 지연될 수 있습니다.</div>
                    <div class="notice-info">생성된 피드 파일의 업데이트 일정은 페이스북 비즈니스 관리자의 카달로그 설정에서 확인하실 수 있습니다.</div>
                </td>
            </tr>
            <tr>
                <th>Facebook Business<br />광고 집행</th>
                <td>
                    <input type="button" class="btn btn-gray btn-sm" value="광고 집행하기" id="adsCreate">
                    <input type="button" class="btn btn-gray btn-sm" value="광고 관리" id="adsInsight">
                    <div class="notice-info">페이스북 채널에 연결된 광고 계정으로 쉽고 빠르게 광고를 생성하고 성과를 확인할 수 있습니다.</div>
                    <div class="notice-info">생성된 광고의 성과는 [광고 관리] > 광고 관리자에서 확인하실 수 있습니다.</div>
                </td>
            </tr>
            <tr>
                <th>도메인 인증코드 설정</th>
                <td>
                    <input type="text" name="domainAuthCode" id="domainAuthCode" class="form-control width-2xl" maxlength="100" value="<?php echo gd_isset($fbe2Data['domainAuthCode']) ?>"/>
                    <div class="notice-info">
                        반드시 도메인 인증코드를 설정해주셔야, 정상적으로 페이스북 광고를 진행하실 수 있습니다.자세한 방법은 아래 안내를 참고하여 주시기 바랍니다.
                    </div>
                </td>
            </tr>
            <tr>
                <th>페이스북 카달로그<br />수동 업데이트</th>
                <td>
                    <input type="button" class="btn btn-gray btn-sm" name="makeFbGoodsFeed" id="makeFbGoodsFeed" value="업데이트 요청"/>
                    <div class="notice-info">
                        페이스북 제품 피드 자동생성 시간 외 상품정보가 변경 된 경우 페이스북으로 피드 파일을 업로드 할 수 있습니다.
                    </div>
                    <div class="notice-danger">
                        업데이트 요청은 1시간에 1번만 가능하며, 제품 피드 생성 후 업로드까지 시간이 지연될 수 있습니다.
                    </div>
                </td>
            </tr>
        <?php } ?>
    </table>
    <?php if (empty($fbe2Data) === false) { ?>
    <table class="table table-cols">
        <colgroup>
            <col class="width-md"/>
            <col/>
        </colgroup>
        <div class="table-title">
            구매전환 성과 측정 설정
        </div>
        <tr>
            <th>액세스 토큰 설정</th>
            <td>
                <input type="text" name="userAccessToken" id="userAccessToken" class="form-control width-2xl" maxlength="500" value="<?php echo gd_isset($fbe2Data['userAccessToken']) ?>" readonly="readonly" />
                <div class="notice-info">
                    Facebook Business Extension 연결 시 액세스토큰이 발급되며, 일반, 네이버페이 구매전환 성과 측정이 가능합니다.
                </div>
                <div class="notice-info">
                    액세스토큰이 확인 불가한 경우 Facebook Business Extension 설정을 변경하시기 바랍니다.
                </div>
            </td>
        </tr>
        <tr>
            <th>일반 구매전환<br />성과 측정 설정</th>
            <td>
                <label><input type="radio" name="userDefaultAccessTokenUseFl" value="y" <?php echo $checked['userDefaultAccessTokenUseFl']['y']; ?>> 사용함</label>
                <label><input type="radio" name="userDefaultAccessTokenUseFl" value="n" <?php echo $checked['userDefaultAccessTokenUseFl']['n']; ?>> 사용안함</label>
                <div class="notice-info">
                    일반 구매전환 성과 측정 항목을 ‘사용함’으로 설정한 시점부터 구매 관련 서버이벤트 정보가 페이스북으로 전송됩니다.
                </div>
            </td>
        </tr>
        <tr>
            <th>네이버페이 구매전환<br />성과 측정 설정</th>
            <td>
                <label><input type="radio" name="userAccessTokenUseFl" value="y" <?php echo $checked['userAccessTokenUseFl']['y']; ?>> 사용함</label>
                <label><input type="radio" name="userAccessTokenUseFl" value="n" <?php echo $checked['userAccessTokenUseFl']['n']; ?>> 사용안함</label>
                <div class="notice-info">
                    네이버페이 구매전환 성과 측정 ‘사용함’ 설정 시, 페이스북에서 네이버페이 구매 전환 성과 측정이 가능합니다.
                </div>
            </td>
        </tr>
    </table>
    <?php } ?>
</form>
<script type="text/javascript">
    $(document).ready(function () {
        $.validator.addMethod("authFormat", function(value, element ) {
            return !/[#\&\\+\-%@=\/\\\:;,\.\'\"\^`~\_|\!\/\?\*$#<>()\[\]\{\}]/i.test(value);
        });
        $("#facebookConfigExtension").validate({
            submitHandler: function (form) {
                form.target = 'ifrmProcess';
                form.submit();
            },
            rules: {
                domainAuthCode: {
                    required: true,
                    authFormat: true,
                }
            },
            messages: {
                domainAuthCode: {
                    required: '도메인 인증코드 설정 값이 있어야 합니다.',
                    authFormat: '특수문자는 지원되지않습니다. 재 입력 부탁드립니다.'
                }
            }
        });
        $(document).on('click', '#openManual', function() {
            window.open('https://marketing.nhn-commerce.com/insite/support-view.gd?idx=2', '_blank');
        });
        $(document).on('click', '#btnFbe2Popup', function() {
            $.post('./facebook_extension_v2_ps.php', {'mode': 'makeFbGoodsFeed'}, function (data) {
                var res = data.split('<?php echo STR_DIVISION?>');

                if (res[0] == 'FEED_GOODS_ZERO') {
                    alert('페이스북 광고 설정이 “피드 생성”으로 설정된 상품이 1개 이상 있을 경우에만 기능 사용이 가능합니다. (설정은 각각의 상품등록 페이지 > 페이스북 광고 설정에서 설정하실 수 있습니다.)');
                } else {
                    window.open('./facebook_extension_v2_ps.php?mode=login', 'popupFbe2', 'width=560, height=670, location=no, scrollbars=yes');
                }
            });
        });
        $(document).on('click', '#btnFbePopup', function() {
            window.open('./facebook_extension_v2_ps.php?mode=loginTest', 'popupFbe2', 'width=560, height=670, location=no, scrollbars=yes');
        });
        $(document).on('click', '#btnFbe2DisconnPopup', function() {
            <?php if (empty($fbe2Data['userAccessToken']) === true) { ?>
            alert('페이스북 시스템 사용자 정보가 확인되지 않습니다.<br />Facebook Business Extension 설정변경 후 해제하시기 바랍니다.');
            <?php } else {?>
            window.open('./facebook_extension_v2_ps.php?mode=disconnect', 'popupFbe2', 'width=560, height=670, location=no, scrollbars=yes')
            <?php } ?>
        });

        $(document).on('click', '#adsCreate', function() {
            window.open("./facebook_extension_v2_ps.php?mode=create", 'popupFbe2', 'width=1060, height=700, location=no, scrollbars=yes');
        });
        $(document).on('click', '#adsInsight', function() {
            window.open("./facebook_extension_v2_ps.php?mode=insight", 'popupFbe2', 'width=1060, height=700, location=no, scrollbars=yes');
        });

        $(document).on('click', '#makeFbGoodsFeed', function () {
            $.post('./facebook_extension_v2_ps.php', {'mode': 'makeFbGoodsFeed'}, function (data) {
                if (data == '1') {
                    window.open('./facebook_extension_v2_ps.php?mode=setCatalog', 'popupFbe2', 'width=560, height=670, location=no, scrollbars=yes');
                } else {
                    var res = data.split('<?php echo STR_DIVISION?>');

                    if (res[0] == 'MAKE_FILE_NOT_END') {
                        alert('');
                    } else if (res[0] == 'FILE_CREATION_WITHIN_AN_HOUR') {
                        alert('제품 피드 수동생성은, 마지막 생성 요청(' + res[1] + ') 후 한 시간 뒤 가능합니다.\n잠시 후 다시 시도하시기 바랍니다.');
                    } else if (res[0] == 'NOT_OBJECT') {
                        alert('');
                    } else if (res[0] == 'FEED_GOODS_ZERO') {
                        alert('페이스북 광고 설정이 “피드 생성”으로 설정된 상품이 1개 이상 있을 경우에만 기능 사용이 가능합니다. (설정은 각각의 상품등록 페이지 > 페이스북 광고 설정에서 설정하실 수 있습니다.)');
                    }
                }
            });
        });

        // 중개서버와 연동 성공시 페이지 리로드
        const receiveMessage1 = function(e) {
            if (e.data.code == "parentreload") {
                location.reload();
            }
        }
        window.addEventListener("message", receiveMessage1, false);
    });
</script>