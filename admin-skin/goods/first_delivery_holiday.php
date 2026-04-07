<form method='post' action='../goods/first_delivery_ps.php' target='ifrmProcess' autocomplete='off'>
<input type='hidden' name='mode' value='register_holiday'>
<div class="page-header js-affix">
    <h3><?=end($naviMenu->location); ?> </h3>
</div>

<div class='table-title'>공휴일 등록</div>
<table class='table table-cols'>
    <tr>
        <th width='140'>공휴일</th>
        <td class='form-inline' width='200'>
            <div class="input-group js-datepicker">
               <input type="text" class="form-control width-xs" name="date">
                <span class="input-group-addon"><span class="btn-icon-calendar"></span></span>
            </div>
        </td>
        <th width='140'>메모</th>
        <td>
            <input type='text' name='memo' class='form-control'>
        </td>
    </tr>
</table>
<div class='center'><input type='submit' value='등록하기' class='btn btn-lg btn-black'></div>
</form>
<div class='table-title'>
	공휴일 리스트
	<div style='display:inline-block;'>
	<form method='post' id="frm_year" autocomplete = 'off'>
		<select name="year" class='form-control' id="year" style='cursor:pointer;'>
			<option>년도검색</option>
			<option value="all" <?php if($selectYear == 'all') echo "selected"?>>전체</option>
			<?php foreach($years as $key => $value){ ?>
			<option value="<?=$value?>" <?php if($value == $selectYear) echo "selected"?>><?=$value?>년</option>
			<?php } ?>
		</select>
	</form>	
	</div>
</div>
<form method='post' action='../goods/first_delivery_ps.php' target='ifrmProcess' autocomplete='off'>
<input type='hidden' name='mode' value='delete_holiday'>
<table class='table table-rows'>
    <tr>
        <th width='20' class='center'><input type='checkbox' class='js-checkall' data-target-name='idx'></th>
        <th class='center' width='150'>공휴일</th>
        <th class='center'>메모</th>
    </tr>
<?php if (gd_isset($list)) : ?>
<?php foreach ($list as $li) : ?>
    <tr>
        <td align='center'><input type='checkbox' name='idx[]' value='<?=$li['datestamp']?>'></td>
        <td align='center'><?=date("Y.m.d", $li['datestamp'])?></td>
        <td><?=$li['memo']?></td>
    </tr>
<?php endforeach; ?>
<?php endif;?>
</table>
<div class='table-action' style='padding-left: 10px;'>
    <input type='submit' value='삭제하기' class='btn btn-black' onclick="return confirm('정말 삭제하시겠습니까?');">
</div> <!-- table-action -->
</form>

<script type='text/javascript'>
	$(function(){
		$('#year').change(function(){
			if($(this).val() != '년도검색'){
				$('#frm_year').submit();
			}
		});
	});
</script>