<form method='post' action='../goods/indb_subscription.php' target='ifrmProcess' autocomplete='off'>
<input type='hidden' name='mode' value='update_goods_config'>
<input type='hidden' name='goodsNo' value='<?=$goodsNo?>'>
<div class='page-header js-affix'>
    <h3>정기결제설정</h3>
    <input type='submit' value='설정 저장' class='btn btn-red'>
</div>
<table class='table table-cols'>
<?php /*
    <tr>
        <th width='140'>선택가능 결제주기</th>
        <td>
            <div style='margin-bottom: 5px;'>
            <?php for ($i = 1; $i <= 10; $i++) : ?>
                <input type='checkbox' name='period[]' value='<?=$i?>_week'<?php if (@in_array($i."_week", $period)) echo " checked";?>><?=$i?>주&nbsp;&nbsp;
            <?php endfor; ?>
            </div>
            <div>
            <?php for ($i = 1; $i <= 11; $i++) : ?>
                <input type='checkbox' name='period[]' value='<?=$i?>_month'<?php if (@in_array($i."_month", $period)) echo " checked";?>><?=$i?>달&nbsp;&nbsp;
            <?php endfor; ?>
            </div>
        </td>
    </tr>
    <tr>
        <th>선택가능 결제횟수</th>
        <td><input type='text' name='deliveryEa' value='<?=implode(",", $deliveryEa)?>' class='form-control' placeholder='여러개 입력시 콤마(,)로 구분하여 숫자 입력'></td>
    </tr>
    */ ?>
    <tr>
        <th width='140'>결제회차별 할인율</th>
        <td>
            <input type='text' name='discount' value='<?=implode(",", $discount)?>' class='form-control' placeholder='여러개 입력시 콤마(,)로 구분하여 숫자 입력'>
        </td>
    </tr>
</table>
<div class='center'><input type='submit' value='설정 저장' class='btn btn-lg btn-black'></div>
</form>