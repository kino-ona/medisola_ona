<div class='page-header js-affix'>
    <h3>주문목록</h3>
</div>

<table class='table table-rows'>
    <tr>
        <th width='50' class='center'>번호</th>
        <th width='100' class='center'>주문일시</th>
        <th width='130' class='center'>주문번호</th>
        <th width='130' class='center'>총상품금액</th>
        <th width='130' class='center'>총 배송비</th>
        <th width='130' class='center'>총 주문금액</th>
        <th width='110' class='center'>결제상태</th>
        <th class='center'>관리</th>
    </tr>
<?php if (gd_isset($list)) : ?>
<?php foreach ($list as $li) : 
        $orderStatus = substr($li['orderStatus'], 0, 1);
 ?>
    <tr>
        <td align='center'><?=$page->idx--?></td>
        <td align='center'><?=substr($li['regDt'], 10)?></td>
        <td align='center'>
            <a href='../order/order_view.php?orderNo=<?=$li['orderNo']?>' class="font-num" target='_blank'><?=$li['orderNo']?></a>
            <img src="<?=PATH_ADMIN_GD_SHARE?>img/icon_grid_open.png" alt="팝업창열기" class="hand mgl5" border="0" onclick="javascript:order_view_popup('<?=$li['orderNo']?>');" />
        </td>
        <td align='center'><?=number_format($li['totalGoodsPrice'])?>원</td>
        <td align='center'><?=number_format($li['totalDeliveryCharge'])?>원</td>
        <td align='center'><?=number_format($li['settlePrice'])?>원</td>
        <td align='center'><?=$status[$li['orderStatus']]?></td>
        <td align='center'>
        <?php if ($orderStatus == 'p') : ?>
        <a href='../order/indb_subscription.php?mode=cancelOrder&orderNo=<?=$li['orderNo']?>' class='btn btn-black' onclick="return confirm('정말 취소하시겠습니까?');" target='ifrmProcess'>카드결제취소</a>
        <?php endif; ?>
        </td>
    </tr>
<?php endforeach; ?>
<?php endif; ?> 
</table>
<div class='center'><?=$pagination?></div>