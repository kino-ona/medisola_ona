<div class="page-header js-affix">
    <h3><?=end($naviMenu->location); ?> </h3>
    <div class="btn-group">
        <input type="button" value="저장" class="btn btn-red" id="batchSubmit"/>
    </div>
</div>

<?php include($goodsSearchFrm); ?>

<form id="frmBatch" name="frmBatch" action="../goods/indb_gift_order.php"  target="ifrmProcess" method="post">
<input type="hidden" name="mode" value="goods_set">
<table class="table table-rows">
    <thead>
    <tr>
		<th class="center" width='20'><input type="checkbox" class="js-checkall" data-target-name="arrGoodsNo[]"></th>
		<th class="center" width='100'>번호</th>
        <th class="center" width='100'>상품코드</th>
        <th class="center" width='80'>이미지</th>
        <th>상품명</th>
		<th width='100' class='center'>선물하기
	</tr>
	<tr style="background: #f9f9f9;">
		<td colspan='5'>
			<span class="select-goods">선택한 상품</span>
			<button type="button" class="btn btn-black btn-18 mgl20" onclick="setAll()" > 일괄적용</button> 
			<div style='display:inline-block; font-size: 11px; font-weight: bold; margin-left: 10px;'><span>선물하기로 적용하고 싶은 상품들을 체크 하여 사용여부 체크 후 일괄적용 버튼을 클릭하세요</span></div>
		</td>
		<td align='center'>
			<select class='useGiftAll form-control'>
				<option value='0'>미사용</option>
				<option value='1'>사용</option>
			</select>
		</td>
	</tr> 
	</thead>
	<tbody>
<?php
if (gd_isset($data) && count($data) > 0 ) {
	foreach ($data as $key => $val) {
	?>
	<tr>
		<td class="center">
			<input type="checkbox" name="arrGoodsNo[]" value="<?=$val['goodsNo']; ?>"/>
		</td>
        <td class="center"><?=number_format($page->idx--); ?></td>
        <td class="center"><?=$val['goodsNo']; ?></td>
		<td class="center">
			<div>
				<?=gd_html_goods_image($val['goodsNo'], $val['imageName'], $val['imagePath'], $val['imageStorage'], 40, $val['goodsNm'], '_blank'); ?>
             </div>
        </td>
        <td>
			<a href="./goods_register.php?goodsNo=<?=$val['goodsNo']; ?>" target="_blank" class="btn-blue"><span class="emphasis_text"><?=$val['goodsNm']; ?></span></a>
        </td>
		<td align='center'>
			<select name='useGift[<?=$val['goodsNo']?>]' class='useGift form-control'>
				<option value='0'<?=$val['useGift']?"":" selected";?>>미사용</option>
				<option value='1'<?=$val['useGift']?" selected":"";?>>사용</option>
			</select>
		</td>
	</tr>
<?php
	}
}
?>
	</tbody>
</table>
<div class='table-action'>
	<div class='pull-left'>
		<input type='submit' value='저장하기' class='btn btn-black' onclick="return confirm('정말 저장하시겠습니까?');">
	</div>
</div>
<div class="center"><?=$page->getPage();?></div>
</form>

<script type="text/javascript">
$(document).ready(function(){
	$( "#batchSubmit" ).click(function() {
		$("#frmBatch").submit();
	});
});

function setAll()
{
	$list = $('input[name="arrGoodsNo[]"]:checked');
	if ($list.length == 0) {
		$.warnUI('항목 체크', "선택된 상품이 없습니다.");
		return false;
	}
	
	$.each($list, function() {
		$(this).closest("tr").find(".useGift").val($(".useGiftAll").val()).change();
	});
}
</script>