<input type="hidden" id="originFlag" name="originFlag" value="<?php echo $kakaoSetting['useFlag']; ?>"/>
<input type="hidden" id="originSelectValue" name="originSelectValue" value=""/>
<input type="hidden" id="lunaKakaoUseFlag" name="lunaKakaoUseFlag" value="<?php echo $lunaKakaoUseFlag; ?>"/>
<form id="frmKakaoAlrimSetting" name="frmKakaoAlrimSetting" method="post" action="kakao_alrim_ps.php">
    <input type="hidden" name="mode" value="saveKakaoAlrimConfig"/>
    <input type="hidden" name="return_mode" value="layer"/>
    <div class="page-header js-affix">
        <h3><?php echo end($naviMenu->location); ?>
        </h3>
        <div class="btn-group">
            <input type="submit" value="저장" class="btn btn-red"/>
        </div>
    </div>
    <?php
    if (gd_is_plus_shop(PLUSSHOP_CODE_KAKAOALRIMLUNA) === true) {
    ?>
    <div class="design-notice-box" style="margin-bottom:20px;">
        <strong>카카오 알림톡은 비즈엠과 루나소프트 중 한곳만 사용이 가능합니다.</strong><br/>
        비즈엠 알림톡은 카카오 플러스친구 등록 후 사용 가능하며, 비용은 고도몰 SMS 발송건수에서 차감됩니다.<br/>
        루나소프트 알림톡은 루나소프트 회원가입 후 사용 가능하며, 비용은 루나소프트에서 청구됩니다.
    </div>
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
        카카오톡 플러스친구 아이디 등록
    </div>
    <table class="table table-cols">
        <colgroup>
            <col class="width-md"/>
            <col/>
        </colgroup>
        <tr>
            <th>플러스친구 아이디</th>
            <td class="form-inline">
                <input type="text" name="plusId" class="" style="background-color: #f1f1f1" value="<?php echo $kakaoSetting['plusId']; ?>" readonly/>
                <button type="button" id="layerKakaoRegist" class="btn btn-gray btn-sm"<?php if ($kakaoSetting['plusId'] != '') { ?> style="display: none"<?php } ?>>플러스친구 아이디 등록</button>
                <button type="button" id="layerKakaoDelete" class="btn btn-gray btn-sm"<?php if ($kakaoSetting['plusId'] == '') { ?> style="display: none"<?php } ?>>플러스친구 아이디 삭제</button>
                <span class="approvalLog"><?php if($kakaoSetting['approvalFl'] == 'y') { ?>동의함 (<?=$kakaoSetting['approvalDt'].' | '.$kakaoSetting['approvalId']?>)<?php } ?></span>
                <div class="notice-info">알림톡을 사용하시려면 발신프로필키가 필요합니다.</div>
                <div class="notice-info">발신프로필키는 카카오톡 플러스친구 아이디 등록을 하여 발급받을 수 있습니다.</div>
                <div class="notice-info">카카오톡 플러스친구 아이디가 없다면 <a href="https://center-pf.kakao.com/login" target="_blank" class="text-blue">[카카오톡 플러스 친구 관리자]</a>에서 발급받은 후 등록해주세요.</div>
            </td>
        </tr>
        <tr>
            <th>발신프로필키</th>
            <td class="form-inline">
                <input type="text" name="kakaoKey" class="" style="background-color: #f1f1f1" value="<?php echo $kakaoSetting['kakaoKey']; ?>" readonly/>
            </td>
        </tr>
        <?php
        // 플러스친구 아이디 등록시에만 노출
        if (empty($kakaoSetting['plusId']) === false) {
        ?>
        <tr>
            <th>발신프로필 상태</th>
            <td class="form-inline">
                <?php echo $senderProfileStatus; ?>
                <?php if ($senderProfileStatus == '휴면' && $senderProfileDormant == '1') { ?>
                <input type="hidden" name="senderProfileDormant" value="<?php echo $senderProfileDormant; ?>"/>
                <button type="button" id="layerKakaoRecover" class="btn btn-white btn-sm pdl10">휴면해제</button>
                <div class="notice-info">1년간 알림톡 발송 이력이 없을 경우, 발신프로필이 ‘휴면’ 상태로 전환됩니다. 휴면해제를 원하시면 휴면해제 버튼을 클릭해주세요.</div>
                <?php } ?>
            </td>
        </tr>
        <?php } ?>
    </table>

    <div class="table-title gd-help-manual">
        알림톡 사용 설정
    </div>
    <table class="table table-cols">
        <colgroup>
            <col class="width-md"/>
            <col/>
        </colgroup>
        <tr>
            <th>알림톡 사용 설정</th>
            <td class="form-inline">
                <label title="사용함" class="radio-inline">
                    <input type="radio" name="useFlag" value="y" <?php echo gd_isset($checked['useFlag']['y']); ?> <?php if ($kakaoSetting['plusId'] == '') { ?>disabled<?php } ?>/>
                    사용함
                </label>
                <label title="사용안함" class="radio-inline">
                    <input type="radio" name="useFlag" value="n" <?php echo gd_isset($checked['useFlag']['n']); ?> />
                    사용안함
                </label> <span class="notice-danger">카카오 알림톡 발송 시 1건당 SMS 0.6포인트가 차감됩니다.</span>
                <div class="notice-danger mgt5">반드시 기본설정 > 기본 정보 설정 > 쇼핑몰 도메인에 올바른 도메인을 입력해 주셔야만 알림톡이 발송됩니다.</div>
                <div class="notice-info">카카오 알림톡 발신프로필키를 받은 후 최소 1시간이 경과해야 정상적으로 사용이 가능합니다.</div>
                <div class="notice-info">SMS 포인트가 없을 경우 카카오 알림톡은 발송 되지 않습니다. <a href="/member/sms_charge.php" target="_blank" class="text-blue">[SMS 포인트충전 바로가기]</a></div>
            </td>
        </tr>
    </table>

    <div class="table-title gd-help-manual">
        알림톡 발송 조건 / 문구 설정
    </div>
    <ul class="nav nav-tabs mgb30">
        <li role="presentation" class="active">
            <a href="#order" aria-controls="sms-auto-content-order" role="tab" data-toggle="tab">주문배송관련</a>
        </li>
        <li role="presentation" class="">
            <a href="#member" aria-controls="sms-auto-content-member" role="tab" data-toggle="tab">회원관련</a>
        </li>
        <li role="presentation" class="">
            <a href="#board" aria-controls="sms-auto-content-board" role="tab" data-toggle="tab">게시물등록 알림</a>
        </li>
    </ul>

    <div class="tab-content">
        <?php
        $rePayPartHideFl = false;
        if($mallSettingDate > '20181226') $rePayPartHideFl = true; // @todo sdate > 20181226  20181227 이후 설치솔루션은 카드 부분 취소 미노출(설치일기준)
        foreach ($smsAutoList as $sKey => $sVal) {
            ?>
            <table class="table table-cols tab-pane fade<?php if ($sKey === 'order') { ?> in active<?php } ?>" id="<?php echo $sKey; ?>Set">
                <colgroup>
                    <col class="width-md"/>
                    <col/>
                </colgroup>
                <tr>
                    <th>
                    <?php if ($sKey == 'order') { ?>
                        주문배송관련 메시지
                    <?php } elseif ($sKey == 'member') { ?>
                        회원관련 메시지
                    <?php } else { ?>
                        게시물등록 알림
                    <?php } ?>
                        <br />알림톡으로 사용
                    </th>
                    <td class="form-inline">
                        <div id="<?php echo $sKey; ?>SetContent" class="<?php if ($kakaoSetting['useFlag'] == 'n') { ?>display-none<?php } ?>">
                            <label title="사용함" class="radio-inline">
                                <input type="radio" name="<?php echo $sKey; ?>UseFlag" value="y" <?php if ($smsAutoData[$sKey . 'UseFlag'] == 'y') { ?>checked="checked"<?php } ?> />
                                사용함
                            </label>
                            <label title="사용안함" class="radio-inline">
                                <input type="radio" name="<?php echo $sKey; ?>UseFlag" value="n" <?php if ($smsAutoData[$sKey . 'UseFlag'] == 'n') { ?>checked="checked"<?php } ?> />
                                사용안함
                            </label>
                            <div class="notice-info">알림톡으로 사용 시 자동 SMS는 발송되지 않습니다.</div>
                            <div class="notice-info">카카오톡 미설치 등으로 알림톡 발송 실패 시 SMS/LMS로 동일 메시지가 재발송됩니다.</div>
                        </div>
                        <div id="<?php echo $sKey; ?>SetLayer" class="<?php if ($kakaoSetting['useFlag'] != 'n') { ?>display-none<?php } ?>">
                            <div class="notice-danger">알림톡 사용 설정을 '사용함'으로 변경하셔야 알림톡을 발송할 수 있습니다.</div>
                        </div>
                    </td>
                </tr>
            </table>

            <div role="tabpanel" class="tab-pane fade<?php if ($sKey === 'order') { ?> in active<?php } ?>" id="<?php echo $sKey; ?>">
                <table class="table table-cols member-sms-auto">
                    <colgroup>
                        <col class="width-sm"/>
                        <?php if ($sKey != 'admin') { ?>
                            <col class="width-md"/>
                            <col class="width-lg"/>
                        <?php } ?>
                        <col class="width-lg"/>
                        <col class="width-lg"/>
                    </colgroup>
                    <tr>
                        <th rowspan="2" class="text-center">발송항목</th>
                        <?php if ($sKey != 'admin') { ?>
                            <th rowspan="2" class="text-center">발송종류</th> <?php } ?>
                        <th colspan="<?= gd_use_provider() ? 3 : 2 ?>" class="text-center">발송대상 및 알림톡 문구설정</th>
                    </tr>
                    <tr>
                        <?php if ($sKey != 'admin') { ?>
                            <th class="text-center">회원</th><?php } ?>
                        <th class="text-center">본사 운영자</th>
                        <?php if (gd_use_provider()) { ?>
                            <th class="text-center">공급사 운영자</th>
                        <?php } ?>
                    </tr>
                    <?php
                    if (empty($smsAutoData[$sKey]) === false) {
                        foreach ($smsAutoData[$sKey] as $typeData) {
                            if($typeData['code'] == 'REPAYPART' && $rePayPartHideFl === true) continue; //  20181227 이후 설치솔루션은 카드 부분 취소 미노출(설치일기준)
                            $sendType = gd_array_change_key_value(explode('_', $typeData['sendType']));
                            ?>
                            <tr>
                                <td class="text-center">
                                    <input type="hidden" name="<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][subject]" value="<?php echo $typeData['text']; ?>"/>
                                    <?php echo $typeData['text']; ?>
                                    <?php if (empty($typeData['desc']) === false) {
                                        echo '<br/><p class="notice-info left">(' . $typeData['desc'] . ')</p>';
                                    } ?>
                                </td>
                                <?php if ($sKey != 'admin') { ?>
                                    <td class="form-inline text-center">
                                        <?php if ($typeData['orderCheck'] === 'y') { ?>
                                            <div class="pdt5">
                                                최근
                                                <?php echo gd_select_box(null, $sKey . '[' . $typeData['code'] . '][smsOrderDate]', gd_array_change_key_value($smsAutoOrderPeriod), '일', $typeData['smsOrderDate']); ?>
                                                주문건만 발송
                                            </div>
                                        <?php } ?>
                                        <?php if ($typeData['agreeCheck'] === 'y') { ?>
                                            <input type="hidden" name="<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][smsAgree]" value="y"/>
                                            <div class="text-center text-blue bold">수신동의 회원만 발송</div>
                                        <?php } ?>
                                        <?php if ($typeData['couponCheck'] === 'y') { ?>
                                            <div style="margin:10px 0 0; padding:7px 0 0; border-top:1px solid #d6d6d6">
                                                쿠폰만료
                                                <?php echo gd_select_box(null, $sKey . '[' . $typeData['code'] . '][smsCouponLimitDate]', gd_array_change_key_value($smsAutoCouponLimitPeriod), '일', $typeData['smsCouponLimitDate']); ?>
                                                전 발송
                                            </div>
                                        <?php } ?>
                                        <?php if ($typeData['nightCheck'] === 'y') { ?>
                                            <div <?php if ($typeData['orderCheck'] === 'y' || $typeData['agreeCheck'] === 'y') { ?>style="margin:10px 0 0; padding:7px 0 0; border-top:1px solid #d6d6d6"<?php } ?>>
                                                <label title="설정시 체크" class="radio-inline">
                                                    <input type="checkbox" name="<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][smsNightSend]" value="y" <?php if (gd_isset($typeData['smsNightSend']) === 'y') {
                                                        echo 'checked=\'checked\'';
                                                    } ?> />
                                                    야간시간에도 발송
                                                </label>
                                                <br/>
                                                <p class="notice-info">(정보통신망법에 의해 08:00 ~ 21:00 에만 발송)</p>
                                            </div>
                                        <?php } ?>
                                        <?php if ($typeData['code'] === 'DELIVERY') { ?>
                                            <?= gd_select_box('', $sKey.'['.$typeData['code'].'][smsOrdDeliverySend]', ['n'=>'주문번호 기준 1회만 발송','y'=>'부분 배송 시 배송 건 별 발송'], null, $typeData['smsOrdDeliverySend']) ?>
                                        <?php } ?>
                                        <?php if ($typeData['code'] === 'DELIVERY_COMPLETED') { ?>
                                            <?= gd_select_box('', $sKey.'['.$typeData['code'].'][smsOrdDeliveryCompletedSend]', ['n'=>'주문번호 기준 1회만 발송','y'=>'부분 배송 시 배송 건 별 발송'], null, $typeData['smsOrdDeliveryCompletedSend']) ?>
                                        <?php } ?>
                                        <?php if ($typeData['code'] === 'CANCEL') { ?>
                                            <?= gd_select_box('', $sKey.'['.$typeData['code'].'][smsOrdCancelSend]', ['n'=>'주문번호 기준 1회만 발송','y'=>'부분 배송 시 배송 건 별 발송'], null, $typeData['smsOrdCancelSend']) ?>
                                        <?php } ?>
                                        <?php if ($typeData['code'] === 'REPAY') { ?>
                                            <?= gd_select_box('', $sKey.'['.$typeData['code'].'][smsOrdRefundSend]', ['n'=>'주문번호 기준 1회만 발송','y'=>'부분 배송 시 배송 건 별 발송'], null, $typeData['smsOrdRefundSend']) ?>
                                        <?php } ?>
                                        <?php if ($typeData['deliveryCheck'] === 'y') { ?>
                                            <?= gd_select_box('', $sKey.'['.$typeData['code'].'][smsDelivery]', ['n'=>'주문번호 기준 1회만 발송','y'=>'부분 배송 시 배송 건 별 발송'], null, $typeData['smsDelivery']) ?>
                                        <?php } ?>
                                        <?php if ($typeData['disapprovalCheck'] === 'y' && $useApprovalFlag) { ?>
                                            <label title="설정시 체크" class="radio-inline">
                                                <input type="checkbox" name="<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][smsDisapproval]" value="y" <?php if (gd_isset($typeData['smsDisapproval']) === 'y') {
                                                    echo 'checked=\'checked\'';
                                                } ?> />
                                                승인대기 회원포함
                                            </label>
                                        <?php } ?>
                                        <?php if ($typeData['code'] == 'ACCOUNT') { ?>
                                            <div style="margin:10px 0 0; padding:7px 0 0; border-top:1px solid #d6d6d6">
                                                <label title="설정시 체크" class="radio-inline">
                                                    <input type="checkbox" name="<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][smsRepeatSend]" value="y" <?php if (gd_isset($typeData['smsRepeatSend']) === 'y') {
                                                        echo 'checked=\'checked\'';
                                                    } ?> />
                                                </label>
                                                주문
                                                <?php echo gd_select_box(null, $sKey . '[' . $typeData['code'] . '][smsOrderAfterDate]', gd_array_change_key_value($smsAutoOrderAfterPeriod), '일', gd_isset($typeData['smsOrderAfterDate'], 3)); ?> 후
                                                <div class="pdt5">
                                                    <?php echo gd_select_box(null, $sKey . '[' . $typeData['code'] . '][smsReSendTime]', gd_array_change_key_value($smsAutoReSendTime), '시', gd_isset($typeData['smsReSendTime'], 10)); ?> 재발송
                                                </div>
                                            </div>
                                        <?php } ?>
                                        <?php if ($typeData['code'] == 'AGREEMENT2YPERIOD') { ?>
                                            <input type="hidden" name="<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][smsAgree]" value="y"/>
                                            <div class="text-center text-blue bold">수신동의여부 안내메일<br/>발송불가 회원 대상</div>
                                        <?php } ?>
                                        <?php if (isset($typeData['reserveHour']) && $typeData['reserveHour'] > 0) { ?>
                                            <div <?php if ($typeData['code'] == 'AGREEMENT2YPERIOD') { ?>style="margin:10px 0 0; padding:7px 0 0; border-top:1px solid #d6d6d6"<?php } ?>>
                                                <?php
                                                echo gd_select_box(null, $sKey . '[' . $typeData['code'] . '][reserveHour]', gd_array_change_key_value($smsAutoReservationTime[$typeData['code']]), '시', $typeData['reserveHour']); ?>
                                                발송
                                            </div>
                                        <?php } ?>
                                    </td>

                                    <td class="">
                                        <?php if (isset($sendType['member']) === true) { ?>
                                            <label title="자동발송시 체크" class="radio-inline">
                                                <input type="checkbox" name="<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][memberSend]" value="y" <?php if (gd_isset($typeData['memberSend']) === 'y') {
                                                    if ($typeData['code'] != 'APPROVAL' || $typeData['code'] == 'APPROVAL' && $useApprovalFlag) {
                                                        echo 'checked=\'checked\'';
                                                    }
                                                }
                                                if ($typeData['code'] == 'APPROVAL' && !$useApprovalFlag) {
                                                    echo 'disabled=\'checked\'';
                                                }
                                                // 휴면회원 관련 힝목 비활성화
                                                if (!$sleepUseFl && in_array($typeData['code'], ['SLEEP_INFO', 'SLEEP_INFO_TODAY'])) {
                                                    echo 'disabled=\'checked\'';
                                                } ?> />
                                                자동발송
                                            </label>
                                            <input type="hidden" name="<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][memberMode]" value="<?php echo $typeData['memberMode']; ?>"/>
                                            <input type="hidden" name="<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][memberSno]" value="<?php echo $typeData['memberSno']; ?>"/>
                                            <textarea name="<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][memberContents]" rows="5" class="form-control mgb10" style="height: 148px; resize: none;<?php if ($typeData['memberTemplateCode'] != '') { ?> background-color: #fefcea;<?php } ?>" readonly><?php echo $typeData['memberContents']; ?></textarea>
                                            <div id="button-list-<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][memberContents]" class="display-none alrim-template-button-list"></div>
                                            <select id="selectTemplate<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][memberContents]" name="<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][memberTemplateCode]" class="form-control width100p">
                                                <option value=""> = 발송 템플릿 변경 = </option>
                                                <?php foreach ($aTemplateList[$sKey] as $aVal) { ?>
                                                    <option value="<?php echo $aVal['templateCode']; ?>" <?php if ($typeData['memberTemplateCode'] == $aVal['templateCode']) { ?>selected="selected"<?php } ?>><?php echo $aVal['templateName']; ?></option>
                                                <?php } ?>
                                                <option value="new"> 새로운 발송 템플릿 등록 </option>
                                            </select>
                                        <?php } else { ?>
                                            <div class="text-center text-blue">운영자 전용</div>
                                        <?php } ?>
                                    </td>
                                <?php } ?>
                                <td class="">
                                    <?php if (isset($sendType['admin']) === true) { ?>
                                        <label title="자동발송시 체크" class="radio-inline">
                                            <?php if ($sKey != 'admin') { ?>
                                                <input type="checkbox" name="<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][adminSend]" value="y" <?php if (gd_isset($typeData['adminSend']) === 'y') {
                                                    echo 'checked=\'checked\'';
                                                } ?> /> 자동발송
                                            <?php } else { ?>
                                                <input type="hidden" name="<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][adminSend]" value="y"/>
                                            <?php } ?>
                                        </label>
                                        <input type="hidden" name="<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][adminMode]" value="<?php echo $typeData['adminMode']; ?>"/>
                                        <input type="hidden" name="<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][adminSno]" value="<?php echo $typeData['adminSno']; ?>"/>
                                        <textarea name="<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][adminContents]" rows="5" class="form-control mgb10" style="height: 148px; resize: none;<?php if ($typeData['adminTemplateCode'] != '') { ?> background-color: #fefcea;<?php } ?>" readonly><?php echo $typeData['adminContents']; ?></textarea>
                                        <div id="button-list-<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][adminContents]" class="display-none alrim-template-button-list"></div>
                                        <select id="selectTemplate<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][adminContents]" name="<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][adminTemplateCode]" class="form-control width100p">
                                            <option value=""> = 발송 템플릿 변경 = </option>
                                            <?php foreach ($aTemplateList[$sKey] as $aVal) { ?>
                                                <option value="<?php echo $aVal['templateCode']; ?>" <?php if ($typeData['adminTemplateCode'] == $aVal['templateCode']) { ?>selected="selected"<?php } ?>><?php echo $aVal['templateName']; ?></option>
                                            <?php } ?>
                                            <option value="new"> 새로운 발송 템플릿 등록 </option>
                                        </select>
                                    <?php } else { ?>
                                        <div class="text-center text-orange-red">회원 전용</div>
                                    <?php } ?>
                                </td>
                                <?php if (gd_use_provider()) { ?>
                                    <td class="">
                                        <?php if (isset($sendType['provider']) === true) { ?>
                                            <label title="자동발송시 체크" class="radio-inline">
                                                <input type="checkbox" name="<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][providerSend]" value="y" <?php if (gd_isset($typeData['providerSend']) === 'y') {
                                                    echo 'checked=\'checked\'';
                                                } ?> />
                                                자동발송
                                            </label>
                                            <input type="hidden" name="<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][providerMode]" value="<?php echo $typeData['providerMode']; ?>"/>
                                            <input type="hidden" name="<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][providerSno]" value="<?php echo $typeData['providerSno']; ?>"/>
                                            <textarea name="<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][providerContents]" rows="5" class="form-control mgb10" style="height: 148px; resize: none;<?php if ($typeData['providerTemplateCode'] != '') { ?> background-color: #fefcea;<?php } ?>" readonly><?php echo $typeData['providerContents']; ?></textarea>
                                            <div id="button-list-<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][providerContents]" class="display-none alrim-template-button-list"></div>
                                            <select id="selectTemplate<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][providerContents]" name="<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][providerTemplateCode]" class="form-control width100p">
                                                <option value=""> = 발송 템플릿 변경 = </option>
                                                <?php foreach ($aTemplateList[$sKey] as $aVal) { ?>
                                                    <option value="<?php echo $aVal['templateCode']; ?>" <?php if ($typeData['providerTemplateCode'] == $aVal['templateCode']) { ?>selected="selected"<?php } ?>><?php echo $aVal['templateName']; ?></option>
                                                <?php } ?>
                                                <option value="new"> 새로운 발송 템플릿 등록 </option>
                                            </select>
                                        <?php } else { ?>
                                            <?php if ($sKey == 'admin') { ?>
                                                <div class="text-center text-orange-red">본사 전용</div>
                                            <?php } else { ?>
                                                <div class="text-center text-orange-red">회원 전용</div>
                                            <?php } ?>
                                        <?php } ?>
                                    </td>
                                <?php } else { ?>
                                    <input type="hidden" name="<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][providerSend]" value="<?php echo $typeData['providerSend'] === 'y' ? 'y' : 'n'; ?>"/>
                                    <input type="hidden" name="<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][providerSno]" value="<?php echo $typeData['providerSno']; ?>"/>
                                    <input type="hidden" name="<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][providerMode]" value="<?php echo $typeData['providerMode']; ?>"/>
                                    <input type="hidden" name="<?php echo $sKey; ?>[<?php echo $typeData['code']; ?>][providerContents]" value="<?php echo $typeData['providerContents']; ?>"/>
                                <?php } ?>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </table>
            </div>

            <?php
                foreach ($aTemplateList[$sKey] as $aVal) {
                    // 템플릿 버튼 정보
                    if (empty($aVal['templateButton']) === false) {
                        $btnNameList = $tmpData = '';
                        $tmpBtnData = json_decode($aVal['templateButton'], true);
                        foreach ($tmpBtnData as $bVal) {
                            $tmpData[] = $bVal['name'];
                        }
                        $btnNameList = implode(',', $tmpData);
                    }
            ?>
            <input type="hidden" id="<?php echo $aVal['templateCode']; ?>" value="<?php echo str_replace("\'", "&#39;", str_replace('\"', "&quot;",str_replace('\n', "\n", str_replace('\r\n', "\r\n", $aVal['templateContent'])))); ?>"/>
            <?php if (empty($aVal['templateButton']) === false && empty($btnNameList) === false) { ?>
            <input type="hidden" id="btn_<?=$aVal['templateCode'];?>" value="<?=$btnNameList;?>"/>
            <?php }
            }?>
            <input type="hidden" id="new" value="new"/>

        <?php
        }
        ?>
    </div>
</form>

<script type="text/javascript">
    <!--
    $(document).ready(function () {
        <?php if(empty($kakaoSetting['plusId']) == false && $kakaoSetting['approvalFl'] == 'n') { ?>
        BootstrapDialog.show({
            title: '안내',
            message: '<div class="table-title text-orange-red">카카오 알림톡 사용을 위해서는 개인정보 제3자 제공 동의가 필요합니다.</div><label class="checkbox-inline"><input type="checkbox" name="approvalFl" value="y"> (필수) 개인정보 제3자 제공에 동의합니다.</label><div class="pMail-area">1. 제공받는자 : (주)써머스플랫폼<br/>2. 이용목적 : 카카오알림톡 서비스 신청 및 관리<br/>3. 제공항목 : 카카오톡채널(플러스친구 아이디), 휴대폰번호, 본문내용<br/>4. 보유 및 이용기간 : 서비스 해지 후 파기</div>',
            closable: true,
            buttons: [{
                label: '취소',
                action: function(dialogItself){
                    dialogItself.close();
                }
            }, {
                label: '확인',
                cssClass: 'btn-black',
                action: function(dialogItself){
                    if ($('input[name=approvalFl]').is(':checked') == false) {
                        alert('개인정보 제3자 제공에 동의해주세요.');
                        return false;
                    }
                    $.ajax({
                        type: "POST",
                        url: "../member/kakao_alrim_ps.php",
                        data: {'mode': 'saveKakaoAlrimConfig', 'approvalFl': 'y'},
                        success: function(save){
                            if (save.result == 'success') {
                                $('.approvalLog').text('동의함 (' + save.approvalDt + ' | ' + save.approvalId + ')');
                                dialogItself.close();
                            } else {
                                alert('카카오 플러스친구 아이디 등록 시 비즈니스 인증이 필요합니다. 인증 후 다시 시도해주세요.');
                                return false;
                            }
                        }
                    })
                }
            }]
        });
        <?php } ?>
        // 폼 체크
        $('#frmKakaoAlrimSetting').validate({
            submitHandler: function (form) {
                var checkboxChecked = 'y';
                /*
                $(':checkbox').each(function (e) {
                    var thisName = $(this).attr('name');
                    var arrName = thisName.split('[');
                    var receiverType = arrName[2].replace('Send]', '');
                    if ($(this).prop('checked') && (arrName[2] == 'memberSend]' || arrName[2] == 'adminSend]' || arrName[2] == 'providerSend]')) {
                        if ($('select[name="' + arrName[0] + '[' + arrName[1] + '[' + receiverType + 'TemplateCode]"] option:selected').val() == '') {
                            $('.nav-tabs a[href="#' + arrName[0] + '"]').trigger('click');
                            checkboxChecked = 'n';
                            $(this).focus();
                            return false;
                        }
                    }
                });
                */
                if ($('#lunaKakaoUseFlag').val() == 'y') {
                    alert('사용중인 알림톡이 있습니다.');
                    return false;
                }
                if (checkboxChecked == 'y') {
                    form.target = 'ifrmProcess';
                    form.submit();
                } else {
                    alert('자동발송 항목 중 발송 템플릿이 선택되지 않은 항목이 있습니다.');
                    return false;
                }
            },
            rules: {
                plusId: "required",
                kakaoKey: "required",
            },
            messages: {
                plusId: {
                    required: '플러스친구 아이디를 등록해주세요.'
                },
                kakaoKey: {
                    required: '플러스친구 아이디를 등록해주세요.'
                },
            }
        });

        $(':checkbox[name$="[smsDisapproval]"]').change(function (e) {
            if (!e.target.checked) {
                alert('\'승인대기 회원 포함\' 설정을 해제하시면, 승인대기 회원에게 회원가입 메세지가 발송되지 않습니다.<br/>해당 설정을 해제하실 경우, 가입 승인 시 발송되는 가입승인 메세지 사용을 권장합니다.');
            }
        });

        // HASH가 있는 경우 자동으로 탭 이동 처리
        if (window.location.hash) {
            $('a[href="' + window.location.hash + '"]').tab('show');
        }

        $(':radio[name="useFlag"]').change(function(e) {
            if ($(this).val() == 'n') {
                $('#orderSetContent').addClass('display-none');
                $('#memberSetContent').addClass('display-none');
                $('#boardSetContent').addClass('display-none');
                $('#orderSetLayer').removeClass('display-none');
                $('#memberSetLayer').removeClass('display-none');
                $('#boardSetLayer').removeClass('display-none');
            } else {
                $('#orderSetContent').removeClass('display-none');
                $('#memberSetContent').removeClass('display-none');
                $('#boardSetContent').removeClass('display-none');
                $('#orderSetLayer').addClass('display-none');
                $('#memberSetLayer').addClass('display-none');
                $('#boardSetLayer').addClass('display-none');
            }

            if ($('#originFlag').val() == 'n' && $(this).val() == 'y') {
                $('input:radio[name="orderUseFlag"]:input[value="y"]').prop("checked", true);
                $('input:radio[name="memberUseFlag"]:input[value="y"]').prop("checked", true);
                $('input:radio[name="boardUseFlag"]:input[value="y"]').prop("checked", true);                
            }
        });

        // SMS 발송 조건 / 문구 설정 탭
        $('.nav-tabs a').click(function (e) {
            e.preventDefault();
            $(this).tab('show');
            var hrefValue = $(this).prop('href');
            var arrHrefValue = hrefValue.split('#');
            var keyValue = arrHrefValue[1];
            if(keyValue == 'biz'){
                location.href = 'kakao_alrim_setting.php';
            }else if(keyValue == 'luna'){
                location.href = 'kakao_alrim_luna_setting.php';
            }else {
                $('#orderSet').removeClass('in active');
                $('#memberSet').removeClass('in active');
                $('#boardSet').removeClass('in active');
                $('#' + keyValue + 'Set').addClass('in active');
            }
        });
    });

    /**
     * 카카오 플러스친구 아이디 등록 클릭시
     *
     */
    $('#layerKakaoRegist').click(function () {
        var layerFormID = 'layerForm';
        var addParam = '';
        var fileStr = '';
        var parentFormID = 'frmKakaoAlrimSetting';
        var dataFormID = 'id';
        var layerTitle = '카카오톡 플러스친구 아이디 등록';
        fileStr = 'kakao_alrim_regist';
        mode = 'simple';

        var addParam = {
            "mode": mode,
            "layerFormID": layerFormID,
            "parentFormID": parentFormID,
            "dataFormID": dataFormID,
            "layerTitle": layerTitle,
            "size": 'wide',
        };

        layer_add_info(fileStr, addParam);
    });

    /**
     * 카카오 플러스친구 아이디 삭제 클릭시
     *
     */
    $('#layerKakaoDelete').click(function () {
        BootstrapDialog.confirm({
            type: BootstrapDialog.TYPE_DANGER,
            title: '플러스친구 아이디 삭제',
            message: '플러스친구 아이디를 삭제하면 카카오 알림톡을 사용할 수 없습니다. 등록된 템플릿 및 관련 설정들도 모두 삭제됩니다. 계속하시겠습니까?',
            closable: false,
            callback: function(confirm) {
                if (confirm) {
                    var url = 'kakao_alrim_ps.php';
                    var formData = new FormData();
                    formData.append('mode', 'deleteKakaoKey');
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
                                saveData.append('mode', 'deleteKakaoAlrimConfig');
                                $.ajax({
                                    url: url,
                                    data: saveData,
                                    processData: false,
                                    contentType: false,
                                    type: 'POST',
                                    dataType: 'json',
                                    success: function (save) {
                                        if (save.result == 'success') {
                                            alert('플러스친구 아이디를 삭제했습니다.');
                                            setTimeout(function() {
                                                location.reload();
                                            }, 2000);
                                        } else {
                                            alert('플러스친구 아이디 삭제를 실패했습니다. 잠시 후 다시 시도해주세요.');
                                        }
                                    },
                                    error: function(save) {
                                        alert('플러스친구 아이디 삭제를 실패했습니다. 잠시 후 다시 시도해주세요.');
                                    }
                                });
                            } else {
                                alert('플러스친구 아이디 삭제를 요청하지 못하였습니다. 잠시 후 다시 시도해주세요.');
                            }
                            return false;
                        },
                        error: function(data) {
                            alert('플러스친구 아이디 삭제를 요청하지 못하였습니다. 잠시 후 다시 시도해주세요.');
                            return false;
                        }
                    });
                }
            }
        });
    });

    $('select').on('click', function(e){
        $('#originSelectValue').val($(this).val());
    });

    $('[id^="selectTemplate"]').change(function () {
        var thisId = $(this).attr('id');
        var sId = thisId.replace('selectTemplate', '');
        var sCheckId = sId.replace('Contents', 'Send');
        if ($('#' + $(this).val()).val() == null) {
            if ($('input[name="' + sCheckId + '"]').is(":checked")) {
                alert('자동발송을 설정한 경우 템플릿을 반드시 선택하셔야 합니다. 사용을 원하지 않으시면, 자동발송 설정 체크박스를 해지해주세요.');
                $(this).val($('#originSelectValue').val());
            } else {
                $('textarea[name="' + sId + '"]').text('템플릿 등록 후 사용해주세요.');
                displayTemplateButtons($(this));
            }
        } else if ($('#' + $(this).val()).val() == 'new') {
            $(this).val($('#originSelectValue').val());
            var url = '/member/kakao_alrim_template.php?mode=newpop';
            window.open(url, "_blank");
        } else {
            $('textarea[name="' + sId + '"]').text($('#' + $(this).val()).val());
            displayTemplateButtons($(this));
        }
    });

    $('[id^="selectTemplate"]').each(function () {
        var thisId = $(this).attr('id');
        var sId = thisId.replace('selectTemplate', '');
        if ($('#' + $(this).val()).val() == null) {
            $('textarea[name="' + sId + '"]').text('템플릿 등록 후 사용해주세요.');
        } else {
            $('textarea[name="' + sId + '"]').text($('#' + $(this).val()).val());
        }
        displayTemplateButtons($(this));
    });

    $(':checkbox').change(function (e) {
        var thisName = $(this).attr('name');
        var arrName = thisName.split('[');
        var receiverType = arrName[2].replace('Send]', '');
        if (e.target.checked && (arrName[2] == 'memberSend]' || arrName[2] == 'adminSend]' || arrName[2] == 'providerSend]')) {
            if ($('select[name="' + arrName[0] + '[' + arrName[1] + '[' + receiverType + 'TemplateCode]"] option:selected').val() == '') {
                alert('발송 템플릿을 먼저 선택해주세요.');
                $(this).prop('checked', false);
            }
        }
    });

    /**
     * 발신프로필 휴면 해제
     *
     */
    $('#layerKakaoRecover').click(function () {
        dialog_confirm('발신프로필 상태를 휴면해제 하시겠습니까?',function (result) {
            if(result){
                $.ajax({
                    method: "post",
                    url: "kakao_alrim_ps.php",
                    data: {
                        mode: 'senderProfileWakeup',
                        dormant: $('input[name=senderProfileDormant]').val()
                    },
                    dataType: 'json',
                    cache: false,
                    async: true,
                }).success(function (data) {
                    if (data.result == 'success') {
                        alert('발신프로필 휴면상태가 해제되었습니다.');
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

    // 등록된 템플릿 버튼 노출 처리
    function displayTemplateButtons(target) {
        var thisId = target.attr('id');
        var sId = thisId.replace('selectTemplate', '');
        var btnTargetEl = $('div[id="button-list-' + sId + '"]');
        var addHtml = '';
        var tmpBtnData = $('#btn_' + target.val()).val();

        if (!_.isUndefined(tmpBtnData)) {
            var btnList = tmpBtnData.split(',');
            for (var i = 0; i < btnList.length; i++) {
                addHtml += '<span class="alrim-template-button">' + btnList[i] + '</span> ';
            }
            btnTargetEl.empty().append(addHtml);
            btnTargetEl.removeClass('display-none');
        } else {
            btnTargetEl.empty();
            btnTargetEl.addClass('display-none');
        }
    }
    //-->
</script>
