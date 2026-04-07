<div class='page-header js-affix'>
    <h3>결제일정관리</h3>
</div>

<?php
if(\Request::getRemoteAddress() == '182.216.219.1571') {
    gd_Debug($list);
}
?>

<div class='table-title'>결제일정 리스트</div>
<form method='post' action='../order/indb_subscription.php' target='ifrmProcess' autocomplete='off'>
<input type='hidden' name='mode' value='change_schedule_list'>
<table class='table table-rows'>
<tr>
    <th width='20' class='center'><input type='checkbox' class='js-checkall' data-target-name='idx'></th>
    <th class='center' width='50'>회차</th>
    <th class='center' width='120'>상태</th>
    <th class='center' width='120'>결제예정일</th>
    <th class='center' width='120'>SMS전송일</th>
    <th class='center' width='120'>배송예정일</th>
    <th class='center' width='130'>결제금액</th>
    <th class='center' width='100'>할인금액</th>
    <th class='center'>결제주문번호</th>
</tr>
<?php if (gd_isset($list)) : ?>
<?php foreach ($list as $k => $li) :  ?>

<tr>
    <td class='center'><input type='checkbox' name='idx[]' value='<?=$li['idx']?>'></td>
    <td class='center'><?=$k+1?></td>
    <td class='center'>
    <?php 
    if ($li['isPayed']) {
        echo "<div style='color: red;'>결제완료</div>";
    } else {
        if ($li['isStop']) {
            echo "<div style='color: red;'>결제중단</div>"; 
        } else {
            echo "<div style='color: blue;'>결제예정</div>";
            echo "<a href='../order/indb_subscription.php?mode=manual_pay&idx={$li['idx']}' class='btn btn-white btn-sm' onclick=\"return confirm('정말 수동결제 하시겠습니까?');\" target='ifrmProcess'>수동결제</a>";
        }
    }
   ?>   
    </th>
    <td class='center'>
		<div style="color:#ccc;"><?php echo date("Y-m-d", $li['schedule_stamp']);?></div>
		<input type='hidden' name='schedule_date_org[<?=$li['idx']?>]' value='<?php if ($li['schedule_stamp'] > 0) echo date("Y-m-d", $li['schedule_stamp']);?>'>
		<div class="input-group js-datepicker">
            <input type='text' name='schedule_date[<?=$li['idx']?>]' value='<?php if ($li['schedule_stamp'] > 0) echo date("Y-m-d", $li['schedule_stamp']);?>' class="form-control width-xs">
            <span class="input-group-addon"><span class="btn-icon-calendar"></span></span>
        </div> 
    </th>
    <td class='center'>
        <?=date("Y.m.d", $li['sms_stamp'])?>
        <?php if ($li['smsStamp'] > 0) : ?>
         <div style='color: red; font-weight: bold;'>전송처리일<br><?=date("Y.m.d", $li['smsStamp'])?></div>
        <?php endif; ?>
    </th>
    <td class='center'><?=date("Y.m.d", $li['delivery_stamp'])?></th>
    <td class='center'>
     <?php 
      if ($li['order']) 
          echo number_format($li['order']['settlePrice']) . "원";
      else 
         echo number_format(($li['orderPrice']['totalGoodsPrice'] - $li['dcInfo']['totalDcPrice']) + $li['orderPrice']['settleTotalDeliveryCharge'])."원";
      ?>
    </td>
    <td class='center'>
     <?php 
        if ($li['order']) {
          
            $discount = $li['order']['totalGoodsDcPrice'] + $li['order']['totalMemberDcPrice'] + $li['order']['totalMemberOverlapDcPrice'] + $li['order']['totalMemberDeliveryDcPrice'] + $li['order']['totalCouponGoodsDcPrice'] + $li['order']['totalCouponOrderDcPrice'] + $li['order']['totalCouponDeliveryDcPrice'];
            echo number_format($discount) . "원";

        } else {
            if ($li['orderPrice'])
                echo number_format($li['dcInfo']['totalDcPrice'])."원";
        }
      ?>
    </td>
    <td class='center'>
     <?php if ($li['orderNo']) : ?>
     <div>
        <?=$li['orderNo']?>
        <img src="<?=PATH_ADMIN_GD_SHARE?>img/icon_grid_open.png" alt="팝업창열기" class="hand mgl5" border="0" onclick="javascript:order_view_popup('<?=$li['orderNo']?>');" />
     </div>
     <?php if ($li['order']['orderStatusStr']) : ?> 
     <div style='color: red; font-weight: bold;'><?=$li['order']['orderStatusStr']?></div>
     <?php endif; ?>
     <?php endif; ?>
     <?php if (!$li['isPayed']) : ?>
        <a href='../order/indb_subscription.php?mode=delete_schedule_each&idx=<?=$li['idx']?>' class='btn btn-sm btn-red' target='ifrmProcess' onclick="return confirm('정말 삭제하시겠습니까?');">삭제</a>
     <?php endif; ?>
    </th>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</table>
<div class='table-action form-inline' style='padding-left: 10px;'>
    <select name='procMode' class='form-control'>
        <option value='unpaid'>결제예정</option>
        <option value='paid'>결제완료</option>
        <option value='date'>결제일</option>
        <option value='stop'>결제중단</option>
        <option value='open'>결제재개</option>
    </select>
    <input type='submit' value='변경하기' class='btn btn-black' onclick="return confirm('정말 변경하시겠습니까?');">
</div> <!-- table-action -->
</form>
<div class='table-title'>결제일정 추가</div>
<form method='post' action='../order/indb_subscription.php' target='ifrmProcess' autocomplete='off'>
<input type='hidden' name='mode' value='register_schedule'>
<input type='hidden' name='uid' value='<?=$uid?>'>
<table class='table table-cols'>
<tr>
    <th width='100'>배송횟수</td>
    <td width='100'>
        <select name='delivery_ea' class='form-control' style='width: 100%;'>
        <?php for ($i = 1; $i <= 12; $i++) : ?>
        <option value='<?=$i?>'><?=$i?>회</option> 
        <?php endfor; ?>
        </select>
    </td>
    <th width='100'>배송주기</td>
    <td>
        <div style='margin-bottom: 6px;'>
        <?php for ($i = 1; $i <= 10; $i++) : ?>   
            <input type='radio' name='period' value='<?=$i?>_week'><?=$i?>주&nbsp;&nbsp;
        <?php endfor; ?>
        </div>
        <div>
         <?php for ($i = 1; $i <= 10; $i++) : ?>   
            <input type='radio' name='period' value='<?=$i?>_month'><?=$i?>달&nbsp;&nbsp;
        <?php endfor; ?>  
        </div>
    </td>
</tr>
</table>
<div class='center'><input type='submit' value='추가하기' class='btn btn-lg btn-black'></div>
</form>