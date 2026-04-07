<div class='page-header js-affix'>
	<h3><?=end($naviMenu->location)?></h3>
	<div class='btn-group'>
		<input type="button" value="저장" class='btn btn-red' onclick="frm.submit();"> 
	</div>
</div> 

<form name='frm' method='post' action='../goods/indb_gift_order.php' target='ifrmProcess' autocomplete='off'>
<input type='hidden' name='mode' value='update_use_set'>
<table class='table table-cols'>
	<tr>
		<th width='130'>사용여부</th>
		<td>
			<input type='radio' name='isUse' value='1' id='isUse1'<?=$isUse?" checked":""?>>
			<label for='isUse1'>사용</label>&nbsp;&nbsp;
			<input type='radio' name='isUse' value='0' id='isUse0'<?=$isUse?"":" checked"?>>
			<label for='isUse0'>미사용</label>
		</td>
	</tr>
	<tr>
		<th>적용범위</th>
		<td>
			<input type='radio' name='useRange' value='all' id='useRangeAll'<?php if ($useRange == 'all') echo " checked";?>>
			<label for='useRangeAll'>전체적용</label>&nbsp;&nbsp;
			<input type='radio' name='useRange' value='goods' id='useRangeGoods'<?php if ($useRange == 'goods') echo " checked";?>>
			<label for='useRangeGoods'>상품개별적용</label>
		</td>
	</tr>

	<tr>
		<th>카드유형</th>
		<td>
			<input type='text' name='cardTypes' value='<?=implode(",", $cardTypes)?>' class='form-control' placeholder='여러개 입력시 콤마(,)로 구분하여 입력'>
		</td>
	</tr>
	<tr style="visibility: hidden;position: absolute;">
		<th>SMS전송주문상태</th>
		<td>
			<input type='checkbox' name='orderStatus[]' value='p' id='orderStatus_p'<?php if (in_array("p", $orderStatus)) echo " checked";?>><label for='orderStatus_p'>입금확인</label>&nbsp;&nbsp;
			<input type='checkbox' name='orderStatus[]' value='g' id='orderStatus_g'<?php if (in_array("g", $orderStatus)) echo " checked";?>><label for='orderStatus_g'>상품준비중</label>
		</td>
	</tr>
	<tr>
		<th>배송주소입력시한</th>
		<td class='form-inline'>
			주문일로 부터 <input type='text' name='expireDays' value='<?=$expireDays?>' class='form-control right' size='3'>일까지 
			/ 만료일로 부터 <input type='text' name='expireSmsDays' value='<?=$expireSmsDays?>' class='form-control right' size='3'>일전 SMS 전송
		</td>
	</tr>
    <tr>
        <th>선물하기 URL SMS치환코드</th>
        <td>
            {to} 선물받는 사람<br>
            {from} 선물한 사람<br>
            {message} 선물메세지<br>
            {goodsNm} 선물하는 상품명<br>
            {giftUrl} 선물 배송주소 입력 URL<br>
            {expireDate} - 배송주소 입력시한
        </td>
    </tr>

    <tr>
        <th>
            배송주소입력시간<br>
            만료안내<br>
            SMS 치환코드
        </th>
        <td>
            {to} 선물받는 사람<br>
            {from} 선물한 사람<br>
            {message} 선물메세지<br>
            {goodsNm} 선물하는 상품명<br>
            {giftUrl} 선물 배송주소 입력 URL<br>
            {expireDate} - 배송주소 입력시한
        </td>
    </tr>

    <tr>
        <th>(선물받는 분)<br>선물하기<br>SMS문구<br>주소 입력 선물 주무건</th>
        <td>
            <textarea name='smsTemplate2' class='form-control' rows='7' style='resize: none;'  ><?=$smsTemplate2?></textarea>
        </td>
    </tr>

	<tr>
		<th>(선물받는 분)<br>선물하기<br>SMS문구<br>주소 미입력 선물 주문건</th>
		<td>
			<textarea name='smsTemplate' class='form-control' rows='7' style='resize: none;'  ><?=$smsTemplate?></textarea>
		</td>
	</tr>

	<tr>
		<th>(선물받는 분)<br>배송주소입력시간<br>만료안내<br>SMS문구</th>
		<td>
			<textarea name='smsExpireTemplate1' class='form-control' rows='7' style='resize: none;'  ><?=$smsExpireTemplate1?></textarea>
		</td>
	</tr>
	<tr>
		<th>(선물하신 분)<br>배송주소입력시간<br>만료안내<br>SMS문구</th>
		<td>
			<textarea name='smsExpireTemplate2' class='form-control' rows='7' style='resize: none;'  ><?=$smsExpireTemplate2?></textarea>
		</td>
	</tr>

</table>
<div class='table-btn'>
	<input type='submit' value='저장하기' class='btn btn-lg btn-black'>
</div>
</form>