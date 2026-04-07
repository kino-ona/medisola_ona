<?php if($wmSubscription) { ?>
<form method='post' action='../goods/indb_subscription.php' target='ifrmProcess' autocomplete='off'>
<input type='hidden' name='mode' value='register_holiday'>
<div class='page-header js-affix'>
    <h3><?=end($naviMenu->location)?></h3>
    <input type='submit' value='등록' class='btn btn-red'>
</div> <!-- page-header -->

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
<div class='table-title'>공휴일 리스트</div>
<form method='post' action='../goods/indb_subscription.php' target='ifrmProcess' autocomplete='off'>
<input type='hidden' name='mode' value='delete_holiday'>
<table class='table table-rows'>
    <tr>
        <th width='20' class='center'><input type='checkbox' class='js-checkall' data-target-name='stamp'></th>
        <th class='center' width='150'>공휴일</th>
        <th class='center'>메모</th>
    </tr>
<?php if (gd_isset($list)) : ?>
<?php foreach ($list as $li) : ?>
    <tr>
        <td align='center'><input type='checkbox' name='stamp[]' value='<?=$li['stamp']?>'></td>
        <td align='center'><?=date("Y.m.d", $li['stamp'])?></td>
        <td><?=$li['memo']?></td>
    </tr>
<?php endforeach; ?>
<?php endif;?>
</table>
<div class='table-action' style='padding-left: 10px;'>
    <input type='submit' value='삭제하기' class='btn btn-black' onclick="return confirm('정말 삭제하시겠습니까?');">
</div> <!-- table-action -->
</form>
<div class="notice-info">등록된 공휴일의 날짜가 지난 경우 노출되지 않습니다.</div>
<?php } ?>