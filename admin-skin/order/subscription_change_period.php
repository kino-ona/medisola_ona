<form method='post' action='../order/indb_subscription.php' target='ifrmProcess' autocomplete='off'>
<input type='hidden' name='mode' value='change_period'>
<input type='hidden' name='uid' value='<?=$uid?>'>
<div class='page-header js-affix'>
    <h3>배송주기변경</h3>
    <input type='submit' value='변경하기' class='btn btn-red'>
</div> 
<div style='color: red; border: 1px solid blue; padding: 10px; margin-bottom: 10px;'>
    배송주기를 변경하셔도 기존 등록된 결제일은 결제일정관리에서 수기로 변경하셔야 합니다.<br>
    배송주기는 자동연장을 하거나 일정을 추가 할때 새롭게 반영하실 수 있습니다.    
</div> 
<table class='table table-cols'>
    <tr>
        <th width='100'>배송주기</th>
        <td>
            <div style='margin-bottom: 6px;'>
            <?php for ($i = 1; $i <= 10; $i++) : ?>   
                <input type='radio' name='period' value='<?=$i?>_week'<?php if ($period == $i."_week") echo " checked";?>><?=$i?>주&nbsp;&nbsp;
            <?php endfor; ?>
            </div>
            <div>
             <?php for ($i = 1; $i <= 10; $i++) : ?>   
                <input type='radio' name='period' value='<?=$i?>_month'<?php if ($period == $i."_month") echo " checked";?>><?=$i?>달&nbsp;&nbsp;
            <?php endfor; ?>  
            </div>
        </td>
    </tr>
</table>
<div class='center'><input type='submit' value='변경하기' class='btn btn-lg btn-black' onclick="return confirm('정말 변경하시겠습니까?');"></div>
</form>