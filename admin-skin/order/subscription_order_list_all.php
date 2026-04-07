<?php if($wmSubscription) { ?>
<div class='page-header js-affix'>
    <h3><?=end($naviMenu->location)?></h3>
</div>

<div class='table-title'>주문 검색</div>
<form method='get' action='' autocomplete='off'>
<table class='table table-cols'>
    <tr>
        <th width='100'>주문일</th>
        <td class='form-inline' width='450'>
            <div class="input-group js-datepicker">
                <input type="text" class="form-control width-xs" name="searchDate[]" value="<?=$search['searchDate'][0]; ?>" />
                <span class="input-group-addon"><span class="btn-icon-calendar"></span></span>
             </div>
              ~
             <div class="input-group js-datepicker">
               <input type="text" class="form-control width-xs" name="searchDate[]" value="<?=$search['searchDate'][1]; ?>" />
               <span class="input-group-addon"><span class="btn-icon-calendar"></span></span>
             </div>
             <?= gd_search_date($search['searchPeriod']) ?>
        </td>  
    </tr>
    <tr>
        <th>주문상태</th>
        <td>
            <?php if ($status) : 
                $no = 0;
            ?>
            <?php foreach ($status as $k => $v) : 
                        if (strlen($k) != 2)
                            continue;
                     if ($no > 0 && $no % 10 == 0) echo "<br>";
                     $no++;
            ?>  
                <input type='checkbox' name='orderStatus[]' value='<?=$k?>'<?php if (@in_array($k, $search['orderStatus'])) echo " checked";?>><?=$v?>&nbsp;&nbsp;
                
            
            <?php endforeach; ?>
            <?php endif; ?>
        </td>
    </tr>
    <tr>
        <th>검색조건</th>
        <td class='form-inline'>
            <select name='sopt' class='form-control'>
                <option value='all'<?php if ($search['sopt'] == 'all') echo " selected";?>>- 통합검색 -</option>
                <option value='oi.orderNo'<?php if ($search['sopt'] == 'oi.orderNo') echo " selected";?>>주문번호</option>
                <option value='name'<?php if ($search['sopt'] == 'name') echo " selected";?>>이름</option>
                <option value='m.memId'<?php if ($search['sopt'] == 'm.memId') echo " selected";?>>아이디</option>
                <option value='mobile'<?php if ($search['sopt'] == 'mobile') echo " selected";?>>연락처</option>
            </select>
             <input type='text' name='skey' class='form-control' value='<?=$search['skey']?>' size='30'>
        </td>
    </tr>
</table>
<div class='center'><input type='submit' value='검색하기' class='btn btn-lg btn-black'></div>
</form>
<p>&nbsp;</p>
<div class='table-title'>주문 리스트</div>
<table class='table table-rows'>
    <tr>
        <th width='50' class='center'>번호</th>
        <th width='140' class='center'>주문일시</th>
        <th width='130' class='center'>주문번호</th>
        <th width='130' class='center'>주문자</th>
        <th class='center'>주문상품</th>
        <th width='130' class='center'>총상품금액</th>
        <th width='130' class='center'>총 배송비</th>
        <th width='130' class='center'>총 주문금액</th>
        <th width='110' class='center'>결제상태</th>
        <th width='120'  class='center'>관리</th>
    </tr>
<?php if (gd_isset($list)) : ?>
<?php foreach ($list as $li) : 
        $orderStatus = substr($li['orderStatus'], 0, 1);
 ?>
    <tr>
        <td align='center'><?=$page->idx--?></td>
        <td align='center'><?=$li['regDt']?></td>
        <td align='center'>
            <a href='../order/order_view.php?orderNo=<?=$li['orderNo']?>' class="font-num" target='_blank'><?=$li['orderNo']?></a>
            <img src="<?=PATH_ADMIN_GD_SHARE?>img/icon_grid_open.png" alt="팝업창열기" class="hand mgl5" border="0" onclick="javascript:order_view_popup('<?=$li['orderNo']?>');" />
        </td>
        <td align='center'><?=$li['orderName']?></td>
        <td align='center'><?=$li['orderGoodsNm']?></td>
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
<?php } ?>