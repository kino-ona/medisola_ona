<div class="page-header js-affix">
    <h3><?php echo end($naviMenu->location); ?></h3>
</div>

<?php include $layoutOrderSearchForm;// 검색 및 프린트 폼 ?>

<form id="frmDeliveryStatus" action="./order_ps.php" method="post">
    <input type="hidden" name="mode" value="combine_delivery_status_change"/>
    <input type="hidden" name="searchView" value="<?=$search['view']?>"/>
    <input type="hidden" name="fromPageMode" value="order_list_goods"/>
    <input type="hidden" id="deliveryStatus" name="changeStatus" value=""/>

    <div class="table-action-dropdown">
        <div class="table-action mgt0 mgb0">
            <div class="pull-left form-inline">
                <span class="action-title">선택한 배송을</span>
                <?php 
                    echo gd_select_box('deliveryStatusTop', null, $selectBoxOrderStatus, null, null, '=배송상태='); 
                ?>
                <button type="submit" class="btn btn-white js-order-status" />일괄처리</button>
                <button type="button" class="btn btn-white js-save-invoice-d" />송장번호 저장</button>
            </div>
            <div class="pull-right">
                <div class="form-inline">
                    <?php if ($search['view'] != 'orderGoods') { ?>
                    <div class="dropdown">
                        <button type="button" id="btnSmsLayer" class="btn btn-red js-sms-layer-open dropdown-toggle dropdown-arr" data-toggle="dropdown" aria-haspopup="false" aria-expanded="false">SMS발송</button>
                        <ul class="dropdown-menu mgt10" aria-labelledby="btnSmsLayer">
                            <li class="dropdown-item"><a class="js-sms-send" data-type="select" data-opener="order" data-target-selector="input[name*=statusCheck]:checked">선택 주문 배송</a></li>
                            <li class="dropdown-item"><a class="js-sms-send" data-type="search" data-opener="order" data-target-selector="#frmSearchOrder" data-status-mode="<?=$currentStatusCode?>">검색 주문 배송</a></li>
                        </ul>
                    </div>
                    <?php } ?>
                    
                </div>
            </div>
        </div>
    </div>

    <?php include $layoutOrderList;// 주문리스트 ?>

    <div class="table-action">
        <div class="pull-left form-inline">
            <span class="action-title">선택한 주문을</span>
            <?php echo gd_select_box('deliveryStatusBottom', 'deliveryStatusBottom', $selectBoxOrderStatus, null, null, '=배송상태='); ?>
            <button type="submit" class="btn btn-white js-order-status" />일괄처리</button>
            <button type="button" class="btn btn-white js-save-invoice-d" />송장번호 저장</button>
        </div>
        <div class="pull-right">
            <button type="button" class="btn btn-white btn-icon-excel js-excel-download" data-target-form="frmSearchOrder" data-search-count="<?=$page->recode['total']?>" data-total-count="<?=$page->recode['amount']?>" data-state-code ="<?=$currentStatusCode?>" data-target-list-form="frmDeliveryStatus" data-target-list-sno="statusCheck" >엑셀다운로드</button>
        </div>
    </div>
</form>

<div class="text-center"><?= $page->getPage(); ?></div>

<script type="text/javascript" src="<?=PATH_ADMIN_SKIN?>script/scheduledDeliveryList.js?ts=<?=time();?>"></script>
<script type="text/javascript" src="<?=PATH_ADMIN_GD_SHARE?>script/orderList.js?ts=<?=time();?>"></script>
