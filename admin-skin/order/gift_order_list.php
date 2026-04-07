<div class='page-header js-affix'>
	<h3><?=end($naviMenu->location)?></h3>
</div>

<form name="SelectList" action="./gift_order_list.php" method='get' autocomplete='off'>
	<table class='table table-cols'>
		<colgroup>
            <col class="width-sm">
            <col>
            <col class="width-sm">
            <col>
        </colgroup>
		<tr>
           <th>주문일</th>
           <td width='550'>
                <div class="form-inline">
                        <div class="input-group js-datepicker">
                            <input type="text" name="treatDate[]" value="<?= $search['treatDate'][0]; ?>" class="form-control width-xs">
                                <span class="input-group-addon">
                                    <span class="btn-icon-calendar">
                                    </span>
                                </span>
                        </div>
                        ~
                        <div class="input-group js-datepicker">
                            <input type="text" name="treatDate[]" value="<?= $search['treatDate'][1]; ?>" class="form-control width-xs">
                                <span class="input-group-addon">
                                    <span class="btn-icon-calendar">
                                    </span>
                                </span>
                        </div>

                        <?= gd_search_date(gd_isset($search['searchPeriod'], 6), 'treatDate[]', false) ?>
                    </div>   
            </td>
			<th style="width : 138px;">선물상태</th>
			<td>
				<input type='checkbox' name='smsSent' value='1'<?=$search['smsSent']?" checked":""?>>SMS 전송완료&nbsp;&nbsp;

			</td>
        </tr>
		<tr>
			<th>주문검색</th>
			<td class='form-inline'>
				<select name='sopt' class='form-control'>
					<option value='all'<?php if ($search['sopt'] == 'all') echo " selected";?>>- 통합검색 -</option>
					<option value='o.orderNo'<?php if ($search['sopt'] == 'o.orderNo') echo " selected";?>>주문번호</option>
					<option value='name'<?php if ($search['sopt'] == 'name') echo " selected";?>>이름</option>
					<option value='m.memId'<?php if ($search['sopt'] == 'm.memId') echo " selected";?>>아이디</option>
					<option value='phone'<?php if ($search['sopt'] == 'phone') echo " selected";?>>휴대전화</option>
				</select>
				<input type='text' name='skey' value='<?=$search['skey']?>' class='form-control' size='30'>
			</td>
			<th>배송지주소입력여부</th>
            <td>
                <select name="delivery_address_set" id="" class="form-control">
                    <option value="all" <?php if($search['delivery_address_set'] == 'all') echo " selected"; ?>>전체</option>
                    <option value="entered" <?php if($search['delivery_address_set'] == 'entered') echo " selected"; ?>>입력</option>
                    <option value="unentered" <?php if($search['delivery_address_set'] == 'unentered') echo " selected"; ?>>미입력</option>
                </select>
            </td>
		</tr>
		<tr>
			<th>주문상태</th>
			<td colspan="4">
				<input type='checkbox' name='orderStatus[]' value='o1'<?php if (in_array('o1', $search['orderStatus'])) echo " checked";?>>입금대기&nbsp;&nbsp;
				<input type='checkbox' name='orderStatus[]' value='p1'<?php if (in_array('p1', $search['orderStatus'])) echo " checked";?>>결제완료&nbsp;&nbsp;
				<input type='checkbox' name='orderStatus[]' value='r1' <?php if(in_array('r1', $search['orderStatus'])) echo " checked"; ?>>환불 접수&nbsp;&nbsp;
				<input type='checkbox' name='orderStatus[]' value='r3' <?php if(in_array('r3', $search['orderStatus'])) echo " checked"; ?>>환불 완료&nbsp;&nbsp;
				<input type='checkbox' name='orderStatus[]' value='g1'<?php if (in_array('g1', $search['orderStatus'])) echo " checked";?>>상품준비중&nbsp;&nbsp; 
				<input type='checkbox' name='orderStatus[]' value='d1'<?php if (in_array('d1', $search['orderStatus'])) echo " checked";?>>배송중&nbsp;&nbsp;
				<input type='checkbox' name='orderStatus[]' value='d2'<?php if (in_array('d2', $search['orderStatus'])) echo " checked";?>>배송완료&nbsp;&nbsp;
				<input type='checkbox' name='orderStatus[]' value='s1'<?php if (in_array('s1', $search['orderStatus'])) echo " checked";?>>구매확정
			</td>
		</tr>
	</table>
	<div class='table-btn'>
		<input type='submit' class='btn btn-lg btn-black' value='검색하기'>
	</div>
	
	<div class="table-header search_list">
        <div class="pull-right">
            <div class="form-inline">
				<select class="search_view_list form-control" name='search_view_list' onchange="refresh();">
					<option value='500' <?php if($search['search_view_list'] == 500) echo "selected"; ?> >500개 보기</option>
					<option value='300' <?php if($search['search_view_list'] == 300) echo "selected"; ?>>300개 보기</option>
					<option value='200' <?php if($search['search_view_list'] == 200) echo "selected"; ?>>200개 보기</option>
					<option value='100' <?php if($search['search_view_list'] == 100) echo "selected"; ?>>100개 보기</option>
					<option value='90' <?php if($search['search_view_list'] == 90) echo "selected"; ?>>90개 보기</option>
					<option value='80' <?php if($search['search_view_list'] == 80) echo "selected"; ?>>80개 보기</option>
					<option value='70' <?php if($search['search_view_list'] == 70) echo "selected"; ?>>70개 보기</option>
					<option value='60' <?php if($search['search_view_list'] == 60) echo "selected"; ?>>60개 보기</option>
					<option value='50' <?php if($search['search_view_list'] == 50) echo "selected"; ?>>50개 보기</option>
					<option value='40' <?php if($search['search_view_list'] == 40) echo "selected"; ?>>40개 보기</option>
					<option value='30' <?php if($search['search_view_list'] == 30) echo "selected"; ?>>30개 보기</option>
					<option value='20' <?php if($search['search_view_list'] == 20) echo "selected"; ?>>20개 보기</option>
					<option value='10' <?php if($search['search_view_list'] == 10) echo "selected"; ?>>10개 보기</option>	
				</select>
            </div>
        </div>
    </div>
</form>

<div>
	<span>검색 <?=number_format($total)?>개/ 전체 <?=number_format($amount)?>개</span>
</div>

<form name="frmList" method="post" action="./wm_data_move.php" target="ifrmProcess">
<input type="hidden" name="mode" value="move">
<table class='table table-rows'>
	<thead>
	<tr>
		<th width='80'>주문일시</th>
		<th width='130'>주문번호</th>
		<th width='70'>주문상태</th>
		<th width='60'>보내는분</th>
		<th width='350'>선물상품</th>
		<th width='60'>선물받는분</th>
		<th width='350'>선물받는분 주소</th>
		<th width='80'>입력시한</th>
		<th width='150'>업데이트일시</th>
		<th></th>
	</tr>
	</thead>
	<tbody>
<?php 

if (gd_isset($list)) : ?>
<?php foreach ($list as $li) : 

	$disabled="disabled";
	if($li['orderStatus']=='p1' && empty($li['receiverAddress'])){
		$disabled="";
	}
?>
	<tr>
		<td align='center'><?=$li['regDt']?></td>
		<td align='center'>
			<a href="../order/order_view.php?orderNo=<?=$li['orderNo']?>" class="font-num" data-order-no="<?=$li['orderNo']?>" target='_blank'><?=$li['orderNo']?></a>
			<img src="/admin/gd_share/img/icon_grid_open.png" alt="팝업창열기" class="hand mgl5" border="0" onclick="javascript:order_view_popup('<?=$li['orderNo']?>', '');">
		</td>
		<td align='center'><?=$li['orderStatusStr']?></td>
		<td align='center'>
			<?=$li['orderName']?>
			<?php if ($li['memId']) : ?>
			<span onclick="window.open('../share/member_crm.php?popupMode=yes&memNo=<?=$li['memNo']?>','', 'width=1200, heigth=750, scrollbars=yes);">(<?=$li['memId']?>)</span>
			<?php endif; ?>
		</td>
		<td><?=$li['goodsNm']?></td>
		<td align='center'><?=$li['receiverName']?></td>
		<td>
			<?=$li['receiverZonecode']?"(".$li['receiverZonecode'].")":""?>
			<?=$li['receiverAddress']?>
			<?=$li['receiverAddressSub']?>
		</td>
		<td>
			<?= $li['deadline'] ?>
		</td>
		<td>
		<?php if ($li['giftUpdateStamp'] > 0) echo date("Y.m.d H:i", $li['giftUpdateStamp'])."(배송지)<br>"; ?>
		<?php if ($li['giftSmsStamp'] > 0) echo date("Y.m.d H:i", $li['giftSmsStamp'])."(SMS)"; ?>
		</td>
		
		<td>
			<?php if (in_array($li['orderStatus2'], ["p", "g", "d", "s"])) : ?>
			<span class='btn btn-white' onclick="window.open('<?=$li['giftUrl']?>', '', 'width=600, height=750, scrollbars=yes');">주소입력</span>
			<span class='btn btn-white js-clipboard' data-clipboard-text='<?=$li['giftUrl']?>' title='선물하기 URL'>URL 복사</span>
			<a class='btn btn-black' href='../order/indb_gift_order.php?mode=send_sms&orderNo=<?=$li['orderNo']?>' target='ifrmProcess' onclick="return confirm('정말 전송하시겠습니까?');">SMS로 URL전송</a>
			<?php endif; ?>
		</td>
	</tr>
<?php endforeach; ?>
<?php endif; ?>
	</tbody>
</table>

<div class='center'><?=$pagination?></div>

<script type="text/javascript">
	function refresh(){
		document.SelectList.submit();
	}

	function data_move(){
		
		if($("input:checkbox[name='chk[]']:checked").length<=0){
			alert("처리할 주문을 선택하세요.");
			return false;
		}

		document.frmList.submit();
	}
	

</script>