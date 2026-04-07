<div class="page-header js-affix">
    <h3><?=end($naviMenu->location); ?> </h3>
    <div class="btn-group">
        <input type="button" value="저장" class="btn btn-red" id="batchSubmit"/>
    </div>
</div>

<?php include($goodsSearchFrm); ?>

<form id="first" method='post' action='../goods/first_delivery_ps.php' target="ifrmProcess" autocomplete='off'>
	<input type='hidden' name='mode' value='update_goods_config'>
	<table class='table table-rows'>
		<thead>
		<tr>
			<th width='20' class='center'><input type='checkbox' class='js-checkall' data-target-name='goodsNo'></th>
			<th width='100' class='center'>상품코드</th>
			<th width='80' class='center'>이미지</th>
			<th class='center'>상품명</th>
			<th width='100' class='center' nowrap>첫배송일 사용여부</th>
			<th width='100' class='center' nowrap>첫배송일 선택 가능 요일</th>
			<th width='100' class='center' nowrap>요일별 첫배송일 선택 가능 날짜</th>
			<th width='100' class='center' nowrap>선택가능 첫배송일 횟수</th>
			<th width='100' class='center' nowrap>판매가</th>

		</tr>
		<?php if (gd_isset($data)) : ?>
		<?php foreach ($data as $key => $li) : ?>
		<tr>
			<td align='center' nowrap >
				<input type='checkbox' name='goodsNo[]' value='<?=$li['goodsNo']?>'>
			</td>
			<td class='center' nowrap ><?=$li['goodsNo']?></td>
			<td class='center' nowrap >
				<div>
					<?=gd_html_goods_image($li['goodsNo'], $li['imageName'], $li['imagePath'], $li['imageStorage'], 80, $li['goodsNm'], '_blank'); ?>
				</div>
			</td>
			<td><span onclick="goods_register_popup('<?=$li['goodsNo']?>');" style='cursor: pointer;'><?=$li['goodsNm']?></span></td>
			<td class='center' nowrap >
				<select name='firstDelivery[<?=$li['goodsNo']?>]' class='form-control' style='display: inline-block;'>
					<option value='n'<?=($li['firstData']['useFirst'])?"":" selected";?>>미사용</option>
					<option value='y'<?=($li['firstData']['useFirst'])?" selected":"";?>>사용</option>
				</select>
			</td>
			<td class='center' nowrap >
				<div>
					<div style='display:inline-flex; align-items: center; justify-content: center; padding: 0 5px;'>
						<label for="mon_<?=$li['goodsNo']?>" style='margin-bottom : 0; padding-right: 3px;'>월</label>
						<input type="checkbox" name="yoil[<?=$li['goodsNo']?>][]" id="mon_<?=$li['goodsNo']?>" value="mon" <?php if($li['firstData']['yoil']){ foreach($li['firstData']['yoil'] as $key_ => $value_){ if($value_ == 'mon'){ ?> checked="checked" <?php }}} ?>>
					</div>
					<div style='display:inline-flex; align-items: center; justify-content: center; padding: 0 5px;'>
						<label for="tue_<?=$li['goodsNo']?>" style='margin-bottom : 0; padding-right: 3px;'>화</label>
						<input type="checkbox" name="yoil[<?=$li['goodsNo']?>][]" id="tue_<?=$li['goodsNo']?>" value="tue" <?php if($li['firstData']['yoil']){ foreach($li['firstData']['yoil'] as $key_ => $value_){ if($value_ == 'tue'){ ?> checked="checked" <?php }}} ?>>
					</div>
					<div style='display:inline-flex; align-items: center; justify-content: center; padding: 0 5px;'>
						<label for="wed_<?=$li['goodsNo']?>" style='margin-bottom : 0; padding-right: 3px;'>수</label>
						<input type="checkbox" name="yoil[<?=$li['goodsNo']?>][]" id="wed_<?=$li['goodsNo']?>" value="wed" <?php if($li['firstData']['yoil']){ foreach($li['firstData']['yoil'] as $key_ => $value_){ if($value_ == 'wed'){ ?> checked="checked" <?php }}} ?>>
					</div>
					<div style='display:inline-flex; align-items: center; justify-content: center; padding: 0 5px;'>
						<label for="thu_<?=$li['goodsNo']?>" style='margin-bottom : 0; padding-right: 3px;'>목</label>
						<input type="checkbox" name="yoil[<?=$li['goodsNo']?>][]" id="thu_<?=$li['goodsNo']?>" value="thu" <?php if($li['firstData']['yoil']){ foreach($li['firstData']['yoil'] as $key_ => $value_){ if($value_ == 'thu'){ ?> checked="checked" <?php }}} ?>>
					</div>
					<div style='display:inline-flex; align-items: center; justify-content: center; padding: 0 5px;'>
						<label for="fri_<?=$li['goodsNo']?>" style='margin-bottom : 0; padding-right: 3px;'>금</label>
						<input type="checkbox" name="yoil[<?=$li['goodsNo']?>][]" id="fri_<?=$li['goodsNo']?>" value="fri" <?php if($li['firstData']['yoil']){ foreach($li['firstData']['yoil'] as $key_ => $value_){ if($value_ == 'fri'){ ?> checked="checked" <?php }}} ?>>
					</div>
				</div>
			</td>
			<td class='center' nowrap >
				<div>
					<ul>
						<li style='display:flex; margin: 5px 5px;'>월 + <input type="text" class="form-control" name="yoilNextDay_mon[<?=$li['goodsNo']?>]" value="<?=$li['firstData']['yoilNextDay'][0]?>" style='margin-left: 5px;'>일</li>
						<li style='display:flex; margin: 5px 5px;'>화 + <input type="text" class="form-control" name="yoilNextDay_tue[<?=$li['goodsNo']?>]" value="<?=$li['firstData']['yoilNextDay'][1]?>" style='margin-left: 5px;'>일</li>
						<li style='display:flex; margin: 5px 5px;'>수 + <input type="text" class="form-control" name="yoilNextDay_wed[<?=$li['goodsNo']?>]" value="<?=$li['firstData']['yoilNextDay'][2]?>" style='margin-left: 5px;'>일</li>
						<li style='display:flex; margin: 5px 5px;'>목 + <input type="text" class="form-control" name="yoilNextDay_thu[<?=$li['goodsNo']?>]" value="<?=$li['firstData']['yoilNextDay'][3]?>" style='margin-left: 5px;'>일</li>
						<li style='display:flex; margin: 5px 5px;'>금 + <input type="text" class="form-control" name="yoilNextDay_fri[<?=$li['goodsNo']?>]" value="<?=$li['firstData']['yoilNextDay'][4]?>" style='margin-left: 5px;'>일</li>
						<li style='display:flex; margin: 5px 5px;'>토 + <input type="text" class="form-control" name="yoilNextDay_sat[<?=$li['goodsNo']?>]" value="<?=$li['firstData']['yoilNextDay'][5]?>" style='margin-left: 5px;'>일</li>
						<li style='display:flex; margin: 5px 5px;'>일 + <input type="text" class="form-control" name="yoilNextDay_sun[<?=$li['goodsNo']?>]" value="<?=$li['firstData']['yoilNextDay'][6]?>" style='margin-left: 5px;'>일</li>
					</ul>
				</div>
			</td>
			<td class='center' nowrap >
				<input type="text" name="firstCnt[<?=$li['goodsNo']?>]" class="form-control" value="<?=$li['firstData']['firstCnt']?>">
			</td>
			<td class='center' nowrap >
				<span><?=number_format($li['goodsPrice']);?>원</span>
			</td>


		</tr>
		<?php endforeach; ?>
		<?php endif; ?>
	</table>
	<div class='table-action form-inline' style='padding-left: 10px;'>
		<span style='margin-right: 10px;'>선택한 상품</span><input type='submit' value='저장하기' class='btn btn-black' onclick="return confirm('정말 저장하시겠습니까?');">
	</div> <!-- table-action -->
	<div class="text-center"><?=$page->getPage();?></div>
</form>

<script type="text/javascript">
	$(function(){
		$('#batchSubmit').click(function(){
			$('#first').submit();
			
		});
	});
</script>