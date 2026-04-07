<div class="mobileapp-order-view">
    <form name="mobileapp_order_view_form" id="mobileapp_order_view_form" >
    <input type="hidden" name="orderNo" id="orderNo" value="<?= $data['orderNo']; ?>" />

    <h2 class="section-header section-header1 oView-Title">주문정보</h2>
    <div class="container-default">
        <table class="table table-bordered table1 oList-table">
            <colgroup>
                <col style="width: 30%;">
                <col>
            </colgroup>
            <tbody>
            <tr>
                <th>주문번호</th>
                <td>
                    <span style=""><strong><?= $data['orderNo']; ?></strong></span>
                </td>
            </tr>
            <tr>
                <th>주문일시</th>
                <td>
                    <?= gd_date_format('Y-m-d H:i', gd_isset($data['regDt'])); ?>
                </td>
            </tr>
            <tr>
                <th>총 결제금액</th>
                <td>
                    <span style=""><strong><?= gd_currency_display(gd_isset($data['dashBoardPrice']['settlePrice'])); ?></strong></span>
                </td>
            </tr>
            <tr>
                <th>총 취소금액</th>
                <td>
                    <?= gd_currency_display(gd_isset($data['dashBoardPrice']['cancelPrice'])); ?>
                </td>
            </tr>
            <tr>
                <th>총 환불금액</th>
                <td style="padding: 5px 0 5px 0">
                    <?= gd_currency_display(gd_isset($data['dashBoardPrice']['refundPrice'])); ?>
                </td>
            </tr>
            </tbody>
        </table>
    </div>

    <!-- 주문품목내역 start -->
    <h2 class="section-header-order-view oView-Title" style="margin-top: 0px;" id="mobileapp_orderList_display">
        <input type="hidden" id="mobileapp_orderList_num" value="<?= ($data['normalGoods']['ordercnt']['orderGoodsCnt'] == '') ? 0 : $data['normalGoods']['ordercnt']['orderGoodsCnt']; ?>">
        주문내역 <span>(<?=number_format($data['normalGoods']['ordercnt']['orderGoodsCnt'])?>건)</span>
        <span id="mobileapp_orderList_icon" class="pull-right"><img src="/admin/gd_share/img/mobileapp/icon/arrow_open.png" width="15"><span>
    </h2>
    <div id="mobileapp_orderList_div" class="container-default overflow-h" style="display:none;">
        <table class="table table-bordered table-condensed table2">
            <tbody id="mobileapp_orderList">
            </tbody>
        </table>
        <center><a id="mobileapp_orderListModify" class="btn btn-lg btn-block-app btn-default-gray border-r-n" style="margin-bottom: 5px" data-toggle="modal" data-target="#myModal">상품상태/송장등록 일괄처리</a></center>
    </div>

    <h2 class="section-header-order-view oView-Title" style="margin-top: 0px;" id="mobileapp_cancelList_display">
        <input type="hidden" id="mobileapp_cancelList_num" value="<?= ($data['normalGoods']['cancelcnt']['orderGoodsCnt'] == '') ? 0 : $data['normalGoods']['cancelcnt']['orderGoodsCnt']; ?>">
        취소내역 <span>(<?=number_format($data['normalGoods']['cancelcnt']['orderGoodsCnt'])?>건)</span>
        <span id="mobileapp_cancelList_icon" class="pull-right"><img src="/admin/gd_share/img/mobileapp/icon/arrow_open.png" width="15"><span>
    </h2>
    <div id="mobileapp_cancelList_div" class="container-default overflow-h" style="display:none;">
        <table class="table table-bordered table-condensed table2">
            <tbody id="mobileapp_cancelList">
            </tbody>
        </table>
        <center><a id="mobileapp_cancelListModify" class="btn btn-lg btn-block-app btn-default-gray border-r-n" style="margin-bottom: 5px" data-toggle="modal" data-target="#myModal">상품상태/송장등록 일괄처리</a></center>
    </div>

    <h2 class="section-header-order-view oView-Title" style="margin-top: 0px;" id="mobileapp_exchangeList_display">
        <input type="hidden" id="mobileapp_exchangeList_num" value="<?= ($data['normalGoods']['exchangecnt']['orderGoodsCnt'] == '') ? 0 : $data['normalGoods']['exchangecnt']['orderGoodsCnt']; ?>">
        교환내역 <span>(<?=number_format($data['normalGoods']['exchangecnt']['orderGoodsCnt'])?>건)</span>
        <span id="mobileapp_exchangeList_icon" class="pull-right"><img src="/admin/gd_share/img/mobileapp/icon/arrow_open.png" width="15"><span>
    </h2>
    <div id="mobileapp_exchangeList_div" class="container-default overflow-h" style="display:none;">
        <table class="table table-bordered table-condensed table2">
            <tbody id="mobileapp_exchangeList">
            </tbody>
        </table>
        <center><a id="mobileapp_exchangeListModify" class="btn btn-lg btn-block-app btn-default-gray border-r-n" style="margin-bottom: 5px" data-toggle="modal" data-target="#myModal">상품상태/송장등록 일괄처리</a></center>
    </div>

    <h2 class="section-header-order-view oView-Title" style="margin-top: 0px;" id="mobileapp_backList_display">
        <input type="hidden" id="mobileapp_backList_num" value="<?= ($data['normalGoods']['backcnt']['orderGoodsCnt'] == '') ? 0 : $data['normalGoods']['backcnt']['orderGoodsCnt']; ?>">
        반품내역 <span>(<?=number_format($data['normalGoods']['backcnt']['orderGoodsCnt'])?>건)</span>
        <span id="mobileapp_backList_icon" class="pull-right"><img src="/admin/gd_share/img/mobileapp/icon/arrow_open.png" width="15"><span>
    </h2>
    <div id="mobileapp_backList_div" class="container-default overflow-h" style="display:none;">
        <table class="table table-bordered table-condensed table2">
            <tbody id="mobileapp_backList">
            </tbody>
        </table>
        <center><a id="mobileapp_backListModify" class="btn btn-lg btn-block-app btn-default-gray border-r-n" style="margin-bottom: 5px" data-toggle="modal" data-target="#myModal">상품상태/송장등록 일괄처리</a></center>
    </div>

    <h2 class="section-header-order-view oView-Title" style="margin-top: 0px;" id="mobileapp_refundList_display">
        <input type="hidden" id="mobileapp_refundList_num" value="<?= ($data['normalGoods']['refundcnt']['orderGoodsCnt'] == '') ? 0 : $data['normalGoods']['refundcnt']['orderGoodsCnt']; ?>">
        환불내역 <span>(<?=number_format($data['normalGoods']['refundcnt']['orderGoodsCnt'])?>건)</span>
        <span id="mobileapp_refundList_icon" class="pull-right"><img src="/admin/gd_share/img/mobileapp/icon/arrow_open.png" width="15"><span>
    </h2>
    <div id="mobileapp_refundList_div" class="container-default overflow-h" style="display:none;">
        <table class="table table-bordered table-condensed table2">
            <tbody id="mobileapp_refundList">
            </tbody>
        </table>
        <center><a id="mobileapp_refundListModify" class="btn btn-lg btn-block-app btn-default-gray border-r-n" style="margin-bottom: 5px" data-toggle="modal" data-target="#myModal">상품상태/송장등록 일괄처리</a></center>
    </div>
    <!-- 주문품목내역 end -->

    <!-- Modal start-->
    <div class="modal fade modal-center" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title text-center" id="myModalLabel">상품상태/송장등록</h4>
                </div>
                <div class="modal-body">
                    <ul class="pd-modify">
                        <li class="form-group selectbox" id="selectBoxOrderStatusHtmlLi">
                        </li>
                        <li class="form-group selectbox" id="selectBoxDeliveryHtmlLi">
                        </li>
                        <li>
                            <input type="text" id="invoiceNo" class="invoice" placeholder="송장번호"/>
                            <button type="button" id="btn_barcodescan" class="btn btn-lg btn-info border-r-n" style="width:30%; background-color:#fa2828; padding-bottom: 3px;padding-top: 3px;">송장스캔</button>
                        </li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <p class="description">변경 후 저장 시 즉시 반영 됩니다.</p>
                    <div class="text-center overflow-h">
                        <div class="pull-left" style="width:80%; padding: 0 5px 0 0;">
                            <input type="hidden" id="modify_type" value="">
                            <button type="button" id="state_delivery_modify" class="btn btn-lg btn-info border-r-n" style="width:100%; background-color:#fa2828;">저장</button>
                        </div>
                        <div class="pull-right" style="width:20%;">
                            <button type="button" class="btn btn-lg btn-inverse gd-btn-list btn_type1 border-r-n" data-dismiss="modal" style="width:100%;">닫기</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal start-->

    <!-- 최초결제정보 start -->
    <div class="container-default overflow-h" style="margin-top: 5px;">
        <table class="table table-bordered table1 oList-table" style="margin-bottom: 0px;">
            <colgroup>
                <col style="width: 30%;">
                <col>
            </colgroup>
            <tbody>
            <tr>
                <th>상품 판매금액</th>
                <td>
                    <?= gd_currency_display(gd_isset($data['totalGoodsPrice'])); ?>
                </td>
            </tr>
            <tr>
                <th>총 배송비</th>
                <td style="color:#117ef9;">
                    (+) <?= gd_currency_display(gd_isset($data['totalDeliveryCharge'])); ?>
                </td>
            </tr>
            <tr>
                <th>총 할인금액</th>
                <td style="color:#fa2828;">
                    (-) <?= gd_currency_display($data['totalGoodsDcPrice'] + $data['totalMemberDcPrice'] + $data['totalMemberOverlapDcPrice'] + $data['totalCouponOrderDcPrice'] + $data['totalCouponGoodsDcPrice'] + $data['totalCouponDeliveryDcPrice'] + $data['totalMyappDcPrice']); ?>
                </td>
            </tr>
            <tr>
                <th>총 부가결제금액</th>
                <td style="color:#fa2828;">
                    (-) <?= gd_currency_display($data['useDeposit'] + $data['useMileage']); ?>
                </td>
            </tr>
            <tr>
                <th>네이버페이 할인금액</th>
                <td>
                    <?= gd_currency_display($data['naverpay']['NaverMileagePaymentAmount'] + $data['naverpay']['ChargeAmountPaymentAmount']); ?>
                </td>
            </tr>
            <tr>
                <th>총 실결제금액</th>
                <td>
                    <strong><?= gd_currency_display(gd_isset($data['totalRealSettlePrice'])); ?></strong>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <!-- 최초결제정보 end -->

    <!-- 결제수단 start -->
    <div class="container-default overflow-h" style="margin-top: 5px;">
        <table class="table table-bordered table1 oList-table" style="margin-bottom: 0px;">
            <colgroup>
                <col style="width: 30%;">
                <col>
            </colgroup>
            <tbody>
            <tr>
                <th>주문 채널</th>
                <td>
                    <?=$data['orderChannelFl']?>
                </td>
            </tr>
            <tr>
                <th>결제 방법</th>
                <td>
                    <?php if($data['orderChannelFl'] == 'naverpay'){
                        echo $data['checkoutData']['orderData']['PaymentMeans'];
                        if ($data['settleKind'] == 'fa' || $data['settleKind'] == 'gr') {
                            echo  '(입금기한 : '.$data['checkoutData']['orderData']['PaymentDueDate'].')';
                        }
                    } else {?>
                        <?php if (gd_isset($settle['prefix']) == 'e') { ?>
                            에스크로
                        <?php } ?>
                        <?php if (gd_isset($settle['prefix']) == 'f') { ?>
                            간편결제
                        <?php } ?>
                        <?= gd_isset($settle['name']); ?>
                    <?php }?>
                </td>
            </tr>
            <tr>
                <th>결제확인일</th>
                <td>
                    <?= gd_isset($data['paymentDt']); ?>
                    <?php if ($settle['payChecker'] != '') { echo "<br/>(처리자 : " . $settle['payChecker'] . ")"; } ?>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <!-- 결제수단 end -->

    <!-- 주문자 정보 start -->
    <div class="container-default overflow-h" style="margin-top: 5px;">
        <table class="table table-bordered table1 oList-table" style="margin-bottom: 0px;">
            <colgroup>
                <col style="width: 30%;">
                <col>
            </colgroup>
            <tbody>
            <tr>
                <th>주문자명</th>
                <td id="memberLink" data-memNo="<?php if (empty($memInfo) === false && !$isProvider) { echo $data['memNo']; } else { echo '0'; } ?>">
                    <?php if (empty($memInfo) === false) { ?>
                        <?php if (!$isProvider) { ?>
                            <strong style="color:darkblue;">
                        <?php } ?>
                    <?php } ?>
                    <?= gd_isset($data['orderName']); ?>
                    <?php if (empty($memInfo) === false) { ?>
                        <?php if (!$isProvider) { ?>
                            </strong>
                        <?php } ?>
                    <?php } ?>
                </td>
            </tr>
            <tr>
                <th>이메일</th>
                <td>
                    <?= gd_isset($data['orderEmail']); ?>
                </td>
            </tr>
            <tr>
                <th>휴대폰번호</th>
                <td>
                    <?php if (empty($data['orderCellPhone']) === false) { ?>
                        <?= gd_isset($data['orderCellPhone']) ?>
                    <?php } ?>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <!-- 주문자 정보 end -->

    <!-- 수령자 정보 start -->
    <div class="container-default overflow-h" style="margin-top: 5px;">
        <table class="table table-bordered table1 oList-table" style="margin-bottom: 0px;">
            <colgroup>
                <col style="width: 30%;">
                <col>
            </colgroup>
            <tbody>
            <tr>
                <th>수령자명</th>
                <td>
                    <input type="hidden" name="info[sno]" value="<?= gd_isset($data['infoSno']); ?>" />
                    <input type="text" name="info[receiverName]" value="<?= gd_isset($data['receiverName']); ?>" class="form-control width-sm"/>
                </td>
            </tr>
            <tr>
                <th>휴대폰번호</th>
                <td>
                    <input type="text" name="info[receiverCellPhone]" value="<?= gd_isset(implode("",$data['receiverCellPhone'])); ?>" maxlength="12" class="form-control js-number-only width-md"/>
                </td>
            </tr>
            <tr>
                <th>주소</th>
                <td>
                    <div>
                        <input type="number" name="info[receiverZonecode]" value="<?= gd_isset($data['receiverZonecode']); ?>" class="form-control"/>
                        <input type="hidden" name="info[receiverZipcode]" value="<?= gd_isset($data['receiverZipcode']); ?>"/>
                    </div>
                    <div class="mgt5">
                        <textarea maxlength="50" name="info[receiverAddress]" class="form-control js-maxlength" style="min-height:34px !important;"><?= gd_isset($data['receiverAddress']); ?></textarea>
                    </div>
                    <div class="mgt5">
                        <textarea maxlength="50" name="info[receiverAddressSub]" class="form-control js-maxlength" style="min-height:34px !important;"><?= gd_isset($data['receiverAddressSub']); ?></textarea>
                    </div>
                </td>
            </tr>
            <tr>
                <th>배송 메세지</th>
                <td>
                    <textarea maxlength="1000" name="info[orderMemo]" class="form-control js-maxlength" style="height: 78px;"><?= gd_isset($data['orderMemo']); ?></textarea>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <!-- 수령자 정보 end -->

    <!-- 요청사항 start -->
    <div class="container-default overflow-h" style="margin-top: 5px;">
        <table class="table table-bordered table1 oList-table" style="margin-bottom: 0px;">
            <colgroup>
                <col style="width: 30%;">
                <col>
            </colgroup>
            <tbody>
            <tr>
                <th>요청사항</th>
                <td style="height: 78px;!important;">
                    <?php
                    if (empty($data['consult']) === false) {
                        $tempCount = count($data['consult']) - 1;
                        foreach ($data['consult'] as $key => $val) {
                            if ($key == $tempCount) {
                                ?>
                                <textarea maxlength="1000" name="consult[requestMemo]" class="form-control js-maxlength" style="height: 78px;"><?=$val['requestMemo']?></textarea>
                                <?php
                            }
                        }
                    } else {
                    ?>
                        <textarea maxlength="1000" name="consult[requestMemo]" class="form-control js-maxlength" style="height: 78px;"></textarea>
                        <?php
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th>상담메모</th>
                <td style="height: 78px;!important;">
                    <?php
                    if (empty($data['consult']) === false) {
                        $tempCount = count($data['consult']) - 1;
                        foreach ($data['consult'] as $key => $val) {
                            if ($key == $tempCount) {
                                ?>
                                <input type="hidden" name="consult[sno]" value="<?= $val['sno'] ?>">
                                <textarea maxlength="1000" name="consult[consultMemo]" class="form-control js-maxlength" style="height: 78px;"><?= $val['consultMemo'] ?></textarea>
                                <?php
                            }
                        }
                    } else {
                    ?>
                        <input type="hidden" name="consult[sno]" value="">
                        <textarea maxlength="1000" name="consult[consultMemo]" class="form-control js-maxlength" style="height: 78px;"></textarea>
                    <?php
                    }
                    ?>

                </td>
            </tr>
            </tbody>
        </table>
        <p class="description" style="margin: 5px 10px 0 10px;">모바일에서는 가장 최근 요청사항 및 상담메모만 확인/수정이 가능합니다</p>
    </div>
    <!-- 요청사항 end -->

    </form>
    <div class="container-default oView-footer-btn-area">
        <center>
        <div class="row">
           <div class="col-xs-8">
               <button type="button" class="btn btn-lg btn-info border-r-n" style="width:100%; background-color:#fa2828;" id="mobileapp_orderViewModifyBtn">저&nbsp;장</button>
           </div>
            <div class="col-xs-4">
                <button type="button" class="btn btn-lg btn-default-gray border-r-n" id="mobileapp_orderViewListBtn">목&nbsp;록</button>
            </div>
        </div>
        </center>
    </div>
</div>
