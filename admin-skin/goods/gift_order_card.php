<div class='page-header js-affix'>
	<h3><?=end($naviMenu->location)?></h3>
</div>
<form method='post' action='../goods/indb_gift_order.php' target='ifrmProcess' enctype="multipart/form-data" autocomplete='off'>
<input type='hidden' name='mode' value='register_card'>
<table class='table table-cols'>
	<tr>
		<th width='100'>카드유형</th>
		<td width='200'>
			<select name='cardType' class='form-control'>
				<option value=''>- 유형 선택 -</option>
			<?php if (gd_isset($cfg['cardTypes'])) : ?>
			<?php foreach ($cfg['cardTypes'] as $cardType) : ?>
				<option value='<?=$cardType?>'><?=$cardType?></option>
			<?php endforeach; ?>
			<?php endif; ?>
			</select>
		</td>
		<th width='100'>이미지</th>
		<td>
			<input type='file' name='file[]' multiple>
		</td>
	</tr>
</table>
<div class='table-btn'>
	<input type='submit' value='등록하기' class='btn btn-lg btn-black'>
</div>
</form>
<form method='post' action='../goods/indb_gift_order.php' target='ifrmProcess' enctype="multipart/form-data" autocomplete='off'>
<div class='table-title'>카드 이미지 목록</div>
<table class='table table-rows'>
	<thead>
	<tr>
        <th width='30' class='center'>
            <input type='checkbox' class='js-checkall' data-target-name='uid'>
        </th>
		<th width='100'>카드유형</th>
		<th width='100'>이미지</th>
		<th>이미지 변경</th>
	</tr>
	</thead>
	<tbody>
<?php if (gd_isset($list)) : ?>
<?php foreach ($list as $cardType => $_list) : ?>
<?php foreach ($_list as $k => $li) : ?>
	<tr>
        <td align='center' style='vertical-align: top;'>
            <input type='checkbox' name='uid[]' value='<?=$li['uid']?>'>
            <input type='hidden' name='filename[<?=$li['uid']?>]' value='<?=$cardType?>_<?=$li['filename']?>'>
        </td>
		<?php if ($k == 0) : ?>
		<td rowspan='<?=count($_list)?>' align='center' style='vertical-align: top;'><?=$cardType?></td>
		<?php endif; ?>

		<td style='vertical-align: top;'>
			<a href='<?=$li['imageUrl']?>' target='_blank'><img src='<?=$li['imageUrl']?>' width='100'></a>
		</td>
		<td style='vertical-align: top;'>
			<input type='file' name='file[<?=$li['uid']?>]'>
		</td>
	</tr>
<?php endforeach; ?>
<?php endforeach; ?>
<?php endif; ?>
	</tbody>
</table>
<div class='table-action'>
	<div class='pull-left form-inline'>
		<select name='mode' class='form-control'>
			<option value='update_card'>수정</option>
			<option value='delete_card'>삭제</option>
		</select>
		<input type='submit' value='처리하기' class='btn btn-black' onclick="return confirm('정말 처리하시겠습니까?');">
	</div>
</div>
</form>