<?php if($wmSubscription) { ?>
<div class='page-header js-affix'>
    <h3><?=end($naviMenu->location)?></h3>
</div>

<div class='table-title'>결제카드 검색</div>
<form method='get' action='' autocomplete='off'>
<table class='table table-cols'>
    <tr>
        <th width='100'>등록일</th>
        <td class='form-inline' width='530'>
            <div class="input-group js-datepicker">
               <input type="text" class="form-control width-xs" name="searchDate[]" value="<?=$search['searchDate'][0]; ?>" />
               <span class="input-group-addon"><span class="btn-icon-calendar"></span></span>
            </div>
             ~
            <div class="input-group js-datepicker">
               <input type="text" class="form-control width-xs" name="searchDate[]" value="<?=$search['searchDate'][1]; ?>" />
               <span class="input-group-addon"><span class="btn-icon-calendar"></span></span>
           </div>
           <?= gd_search_date($search['searchPeriod']) ?>
        </td>
        <th width='100'>등록회원</th>
        <td>
            <input type='text' name='memNm' value='<?=$search['memNm']?>' class='form-control'>
        </td>
    </tr>
    <tr>
        <th>카드명</th>
        <td colspan='3'>
            <input type='text' name='cardNm' class='form-control' value='<?=$search['cardNm']?>'>
        </td>
    </tr>
</table>
<div class='center' style='margin-bottom: 20px;'><input type='submit' value='검색하기' class='btn btn-lg btn-black'></div>
</form>

<div class='table-title'>결제카드 리스트</div>

<form method='post' action='../order/indb_subscription.php' target='ifrmProcess' autocomplete='off'>
<table class='table table-rows'>
    <tr>
        <th width='20' class='center'><input type='checkbox' class='js-checkall' data-target-name='idx'></th>
        <th width='120' class='center'>등록일시</th>
        <th width='120' class='center'>회원명</th>
        <th width='150' class='center'>카드명</th>
        <th class='center'>특이사항</th>
        <th width='150' class='center'>비밀번호변경</th>
        <th width='80' class='center'>자세히</th>
    </tr>
<?php if (gd_isset($list)) : ?>
<?php foreach ($list as $li) : ?>
    <tr>
        <td align='center'><input type='checkbox' name='idx[]' value='<?=$li['idx']?>'></td>
        <td align='center'><?=date("Y.m.d H:i", $li['regStamp'])?></td>
        <td align='center'>
            <?=$li['memNm']?>
            <?=$li['memId']?"<br>(".$li['memId'].")":""?>
        </td>
        <td align='center'><?=$li['cardNm']?></td>
        <td><input type='text' name='memo[<?=$li['idx']?>]' value='<?=$li['memo']?>' class='form-control'></td>
        <td><input type='password' name='password[<?=$li['idx']?>]' class='form-control'></td>
        <td align='center'>
            <span class='btn btn-white' onclick="window.open('../order/subscription_card_info.php?idx=<?=$li['idx']?>', '', 'width=1100,height=600,scrollbars=yes');">자세히</span>
        </td>
    </tr>
<?php endforeach; ?>
<?php endif; ?>
</table>
<div class='table-action form-inline' style='padding-left: 10px;'>
    <select name='mode' class='form-control'>
        <option value='update_card_list'>수정</option>
        <option value='delete_card_list'>삭제</option>
    </select>
    <input type='submit' value='처리하기' class='btn btn-black' onclick="return confirm('정말 처리하시겠습니까?');">
</div>
</form>

<div class='center'><?=$pagination?></div>
<?php } ?>