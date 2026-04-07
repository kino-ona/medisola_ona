<form method='post' action='../order/indb_subscription.php' target='ifrmProcess' autocomplete='off'>
<input type='hidden' name='mode' value='change_card'>
<input type='hidden' name='uid' value='<?=$uid?>'>
<div class='page-header js-affix'>
    <h3>결제카드 변경</h3>
    <input type='submit' value='변경하기' class='btn btn-red' onclick="return confirm('정말 변경하시겠습니까?');">
</div>
<table class='table table-cols'>
<tr>
    <th>결제카드</th>
    <td>
        <select name='idx_card' class='form-control'>
        <?php if (gd_isset($list)) : ?>
        <?php foreach ($list as $li) : ?>
            <option value='<?=$li['idx']?>'<?php if ($idx_card == $li['idx']) echo " selected";?>><?=$li['cardNm']?>(등록일 : <?=date("Y.m.d", $li['regStamp'])?>)</option>
        <?php endforeach; ?>
        <?php endif; ?>
        </select>
    </td>
</tr>
</table>
</form>