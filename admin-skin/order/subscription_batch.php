<?php if($wmSubscription) { ?>
<div class='page-header js-affix'>
    <h3><?=end($naviMenu->location)?></h3>
</div>

<form method='get' action='' autocomplete='off'>
    <table class='table table-cols'>
        <tr>
            <th width='120'>처리일</th>
            <td class='form-inline'>
               <div class="input-group js-datepicker">
                <input type="text" class="form-control width-xs" name="date" value="<?=$search['date']?>" />
                <span class="input-group-addon"><span class="btn-icon-calendar"></span></span>
              </div> 
            </td>
        </tr>
        <tr>
            <th>작업구분</th>
            <td>
                <input type="radio" name="mode" value="batch_sms"<?php if (empty($search['mode']) || $search['mode'] == 'batch_sms') echo " checked"; ?>>SMS전송&nbsp;
                <input type="radio" name="mode" value="batch_pay"<?php if ($search['mode'] == 'batch_pay') echo " checked"; ?>>결제처리 
                <input type="radio" name="mode" value="batch_auto_extend"<?php if ($search['mode'] == 'batch_auto_extend') echo " checked"; ?>>자동연장 (<span style='color: red; font-size: 11px;'>자동연장 처리는 처리일을 선택할 필요가 없습니다.</span>)
        </td>
        </tr>
    </table>
    <div class='center'><input type='submit' value='검색하기' class='btn btn-lg btn-black'></div>
</form>
<p>&nbsp;</p>
<div class='table-title'>처리 리스트</div>
<form method='post' action='../order/indb_subscription.php' target='ifrmProcess' autocomplete='off'>
<input type="hidden" name="mode" value="batch">
<input type="hidden" name="proc_mode" value="<?=$search['mode']?>">
<table class='table table-rows'>
    <tr>
        <th width="20" class="center"><input type="checkbox" class='js-checkall' data-target-name='idx'></th>
        <th width="140" class="center">신청번호</th>
        <th width="100" class="center">결제예정일</th>
        <th width="100" class="center">SMS예정일</th>
        <th width="150" class="center">신청회원</th>
        <th width="80" class="center">주문자</th>
        <th width="80" class="center">받는분</th>
        <th class="center">주문상품</th>
    </tr>
<?php if (gd_isset($list)) : ?>
<?php foreach ($list as $li) : 
        $items = $li['items'];
?>
    <tr>
        <td align='center'><input type='checkbox' name='idx[]' value='<?=$li['idx']?>'></td>
        <td align='center'><?=$li['uid']?></td>
        <td align='center'><?=date("Y.m.d", $li['schedule_stamp'])?></td>
        <td align='center'><?=date("Y.m.d", $li['sms_stamp'])?></td>
        <td align='center'><?=$li['memNm']?><?=$li['memId']?"(".$li['memId'].")":""?></td>
        <td align='center'><?=$li['orderName']?></td>
        <td align='center'><?=$li['receiverName']?></td>
        <td>
            <?php
                echo $items[0]['goodsNm'];
                if (count($items) > 1) echo " 외".(count($items) - 1)."건";
            ?>
            
        </td>
    </tr>
<?php endforeach; ?>
<?php endif; ?>
</table>
<div class='table-action' style='padding-left: 10px;'>
    <input type='submit' value='처리하기' class='btn btn-white' onclick="return confirm('정말 처리하시겠습니까?');">
</div> 
</form>
<?php } ?>