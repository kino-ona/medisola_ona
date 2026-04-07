<div class='page-header js-affix'>
    <h3>결제카드 정보</h3>
</div>

<form method='post' action='../order/indb_subscription.php' target='ifrmProcess' autocomplete='off'>
<input type='hidden' name='mode' value='update_card_info'>
<input type='hidden' name='idx' value='<?=$idx?>'>
<table class='table table-cols'>
    <tr>
        <th width='120'>등록일시</th>
        <td><?=date("Y.m.d H:i:s", $regStamp)?></td>
    </tr>
    <tr>
        <th>회원명</th>
        <td><?=$memNm?><?=$memId?"(".$memId.")":""?></td>
    </tr>
    <tr>
        <th>결제KEY</th>
        <td><?=$payKey?></td>
    </tr>
    <tr>
        <th>결제비밀번호</th>
        <td><input type='password' name='password' class='form-control'></td>
    </tr>
    <tr>
        <th>메모</th>
        <td><input type='text' name='memo' class='form-control' value='<?=$memo?>'></td>
    </tr>
    <tr>
        <th>신청로그</th>
        <td>
            <textarea class='form-control' rows='8' readonly><?=$settleLog?></textarea>
        </td>
    </tr>
</table>
<div class='center'><input type='submit' value='수정하기' class='btn btn-lg btn-black'></div>
</form>