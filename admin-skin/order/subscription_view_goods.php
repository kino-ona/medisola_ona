<style>
.goodsimg img { width: 100px; }
</style>
<form method='post' action='../order/indb_subscription.php' target='ifrmProcess' autocomplete='off'>
<input type='hidden' name='mode' value='change_goods_option'>
<div class='page-header js-affix'>
    <h3>주문상품보기</h3>
</div>
<table class='table table-rows'>
<tr>
	<th width='20' class='center'><input type='checkbox' class='js-checkall' data-target-name='idx'></th>
	<th colspan='2' class='center'>주문상품</th>
	<th width='80' class='center'>주문수량</th>
</tr>
<?php if (gd_isset($items)) : ?>
<?php foreach ($items as $it) : 
			$goods = $it['goods'];
			$optionSno = explode(INT_DIVISION, $it['optionSno']);
?>
<tr>
	<td align='center'><input type='checkbox' name='idx[]' value='<?=$it['idx']?>'></td>
	<td width='100' class='goodsimg'><?=$goods['images'][0]?></td>
	<td>
		<div style='margin-bottom: 5px;'><?=$goods['goodsNm']?></div>
	   <?php if ($goods['optionFl'] == 'y' && $goods['option']) : ?>
		<select name='optionSno[<?=$it['idx']?>][]' class='form-control'>
		<?php foreach ($goods['option'] as $o) : ?>
			<option value='<?=$o['sno']?>'<?php if (in_array($o['sno'], $optionSno)) echo " selected";?>><?=$o['optionValue']?></option>
		<?php endforeach; ?>
		</select>
	   <?php endif; ?>
	   <?php if ($it['selectedOptTxt']) : ?>
		<?=implode("/",$it['selectedOptTxt'])?>
	   <?php endif; ?>
	</td>
	<td align='center'><?=$it['goodsCnt']?></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</table>
<div class='table-action' style='padding-left: 10px;'>
	<input type='submit' value='옵션변경' class='btn btn-white' onclick="return confirm('정말 변경하시겠습니까?');">
</div>
</form>