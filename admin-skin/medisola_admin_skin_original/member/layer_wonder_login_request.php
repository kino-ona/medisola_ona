<form id="layerForm" name="layerForm" action="wonder_login_request_ps.php" method="post" enctype="multipart/form-data" target="wonder_login">
    <input type="hidden" name="mode" id="mode" value="<?=$mode; ?>" />
    <input type="hidden" name="token" id="token" value="" />
    <input type="hidden" name="confirmyn" id="confirmyn" value="n" />
    <input type="hidden" name="parentForm" id="parentForm" value="layer" />
    <input type="hidden" name="firstCheck" value="<?=$useFl?>" />
    <table class="table table-cols">
        <colgroup>
            <col class="width-md"/>
            <col/>
        </colgroup>
        <?php if ($mode == 'regist') { ?>
        <tr>
            <th class="require">회사명</th>
            <td>
                <input type="text" name="companyName" id="companyName" value="<?=$baseInfo['companyNm']?>" class="form-control width-lg" placeholder="NHN커머스" />
            </td>
        </tr>
        <?php } ?>
        <tr>
            <th class="require">쇼핑몰명</th>
            <td>
                <input type="text" name="serviceName" id="serviceName" value="<?=$baseInfo['mallNm']?>" class="form-control width-lg" maxlength="40" flag="layer" placeholder="예) 고도몰" />
                <div class="notice-info">위메프 아이디로 로그인할 때 사용자에게 표시되는 이름입니다.</div>
            </td>
        </tr>
        <?php if ($mode == 'regist') { ?>
            <tr>
                <th class="require">이름</th>
                <td>
                    <input type="text" name="serviceUserName" id="serviceUserName" value="<?=$baseInfo['ceoNm']?>" class="form-control width-lg" placeholder="예) 홍길동" />
                </td>
            </tr>
            <tr>
                <th class="require">이메일</th>
                <td>
                    <input type="text" name="serviceEmail" id="serviceEmail" value="<?=$baseInfo['centerEmail']?>" class="form-control width-lg" placeholder="예) admin@godo.co.kr" />
                </td>
            </tr>
            <tr>
                <th class="require">사용자등록번호</th>
                <td>
                    <input type="text" name="businessNo" id="businessNo" value="<?=$baseInfo['businessNo']?>" class="form-control width-lg" placeholder="-빼고 입력해주세요." />
                </td>
            </tr>
        <?php } ?>
        <tr>
            <th class="require">리다이렉트 URI</th>
            <td>
                <textarea name="redirectUri" id="redirectUri" class="form-control textarea"><?=$baseInfo['redirectUri']?></textarea>
                <div class="notice-info">리다이렉트 URI는 위메프 아이디 로그인을 사용하기 위한 사용중인 도메인의 인증 경로입니다.</div>
                <div class="notice-info">[관리자 > 기본설정 > 기본정책 > 기본 정보 설정]의 쇼핑몰 도메인 정보를 통해 PC와 MOBILE 리디렉션 URI를 기본으로 제공합니다.</div>
                <div class="notice-info">도메인 변경 또는 2차 도메인 등 도메인 등록/추가 가능하며, 등록시 도메인 뒤 path(/member/wonder/wonder_login.php)값을 함께 등록 바랍니다.</div>
                <div class="notice-info">보안서버 등록시 반드시 https:// 를 입력하시기 바랍니다.</div>

                <?php if ($mode == 'modify' && empty($secureRedirectUri) == false) { ?>
                    <div class="notice-info mgt10">현재 등록되어있는 리다이렉트 URI에 아래 보안서버 리다이렉트 URI가 있는지 확인 후 없는 경우 아래 내용을 복사해 반드시 등록 바랍니다.</div>
                    <?=$secureRedirectUri; ?>
                <?php } ?>
            </td>
        </tr>
    </table>

    <?php if ($mode == 'regist') { ?>
        <div class="table-title">개인정보 제3자 제공 동의</div>
        <div id="wonderAgreement" class="form-inline panel pd10 term-scroll"><?=nl2br($terms);?></div>
        <div class="form-inline">
            <label class="checkbox-inline mgb10">
                <input type="checkbox" name="agreementFlag" value="y"/>
                (필수) 개인정보 제3자 제공에 동의합니다.
            </label>
        </div>
    <?php } ?>
    <div class="text-center btn-box">
        <button type="button" class="btn btn-lg btn-black" id="layerBtnConfirm"><?= $mode == 'regist' ? '사용신청' : '수정'; ?></button>
        <button type="button" class="btn btn-lg btn-white js-layer-close">취소</button>
    </div>
</form>
<style type="text/css">
    .textarea { width: 650px !important; height:185px !important; overflow-y:scroll; }
</style>
