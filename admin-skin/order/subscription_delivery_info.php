<form method='post' action='../order/indb_subscription.php' target='ifrmProcess' autocomplete='off'>
<input type='hidden' name='mode' value='update_delivery_info'>
<input type='hidden' name='uid' value='<?=$uid?>'>
<div class='page-header js-affix'>
    <h3>배송정보 변경</h3>
    <input type='submit' value='수정' class='btn btn-red'>
</div>
<div class='table-title'>주문자 정보</div>
<table class='table table-cols'>
    <tr>
        <th width='90'>주문자명</th>
        <td colspan='3'><input type='text' name='orderName' value='<?=$orderName?>' class='form-control'></td>
    </tr>
    <tr>
        <th>유선전화</th>
        <td width='280' class='form-inline'>
            <input type='text' name='orderPhone[]' value='<?=$orderPhone[0]?>' size='4' class='form-control'> -
            <input type='text' name='orderPhone[]' value='<?=$orderPhone[1]?>' size='4' class='form-control'> - 
            <input type='text' name='orderPhone[]' value='<?=$orderPhone[2]?>' size='4' class='form-control'>
        </td>
        <th width='90'>휴대전화</th>
        <td class='form-inline'>
            <input type='text' name='orderCellPhone[]' value='<?=$orderCellPhone[0]?>' size='4' class='form-control'> -
            <input type='text' name='orderCellPhone[]' value='<?=$orderCellPhone[1]?>' size='4' class='form-control'> - 
            <input type='text' name='orderCellPhone[]' value='<?=$orderCellPhone[2]?>' size='4' class='form-control'>
        </td>
    </tr>
    <tr>
        <th>주소</th>
        <td colspan='3'>
            <div class="form-inline " style='margin-bottom: 5px;'>
                <span title="우편번호를 입력해주세요!">
                    <input type="text" size="6" maxlength="5" name="orderZonecode" class="form-control js-number" data-number="5" value="<?=$orderZonecode?>"/>
                    <input type="hidden" name="orderZipcode" value="<?=$orderZipcode?>"/>
                    <span id="zipcodeText" class="number">(<?=$orderZipcode?>)</span>
                    <input type="button" onclick="postcode_search('orderZonecode', 'orderAddress', 'orderZipcode');" value="우편번호찾기" class="btn btn-gray btn-sm"/>
                </span>
            </div>
            <div class="form-inline">
                <span title="주소를 입력해주세요!">
                    <input type="text" name="orderAddress" id="orderAddress"
                            class="form-control" style='margin-bottom: 5px; width: 100%; display: block;'
                             value="<?=$orderAddress?>"/>
                </span>
                <span title="상세주소를 입력해주세요!">
                    <input type="text" name="orderAddressSub" id="orderAddressSub"
                             class="form-control" style='width: 100%; display: block;'
                             value="<?=$orderAddressSub?>"/>
                 </span>
            </div> 
        </td>
    </tr>
</table>

<div class='table-title'>받는분 정보</div>
<table class='table table-cols'>
    <tr>
        <th width='90'>받는분</th>
        <td colspan='3'><input type='text' name='receiverName' value='<?=$receiverName?>' class='form-control'></td>
    </tr>
    <tr>
        <th>유선전화</th>
        <td width='280' class='form-inline'>
            <input type='text' name='receiverPhone[]' value='<?=$receiverPhone[0]?>' size='4' class='form-control'> -
            <input type='text' name='receiverPhone[]' value='<?=$receiverPhone[1]?>' size='4' class='form-control'> - 
            <input type='text' name='receiverPhone[]' value='<?=$receiverPhone[2]?>' size='4' class='form-control'>
        </td>
        <th width='90'>휴대전화</th>
        <td class='form-inline'>
            <input type='text' name='receiverCellPhone[]' value='<?=$receiverCellPhone[0]?>' size='4' class='form-control'> -
            <input type='text' name='receiverCellPhone[]' value='<?=$receiverCellPhone[1]?>' size='4' class='form-control'> - 
            <input type='text' name='receiverCellPhone[]' value='<?=$receiverCellPhone[2]?>' size='4' class='form-control'>
        </td>
    </tr>
    <tr>
        <th>주소</th>
        <td colspan='3'>
            <div class="form-inline " style='margin-bottom: 5px;'>
                <span title="우편번호를 입력해주세요!">
                    <input type="text" size="6" maxlength="5" name="receiverZonecode" class="form-control js-number" data-number="5" value="<?=$receiverZonecode?>">
                    <input type="hidden" name="receiverZipcode" value="<?=$receiverZipcode?>">
                    <span id="zipcodeText" class="number">(<?=$receiverZipcode?>)</span>
                    <input type="button" onclick="postcode_search('receiverZonecode', 'receiverAddress', 'receiverZipcode');" value="우편번호찾기" class="btn btn-gray btn-sm">
                </span>
            </div>
            <div class="form-inline">
                <span title="주소를 입력해주세요!">
                    <input type="text" name="receiverAddress" id="receiverAddress"
                            class="form-control" style='margin-bottom: 5px; width: 100%; display: block;'
                             value="<?=$receiverAddress?>">
                </span>
                <span title="상세주소를 입력해주세요!">
                    <input type="text" name="receiverAddressSub" id="receiverAddressSub"
                             class="form-control" style='width: 100%; display: block;'
                             value="<?=$receiverAddressSub?>">
                 </span>
            </div> 
        </td>
    </tr>
    <tr>
        <th>배송메세지</th>
        <td colspan='3'><input type='text' name='orderMemo' value='<?=$orderMemo?>' class='form-control'></td>
    </tr>
</table>
<div class='center'><input type='submit' class='btn btn-lg btn-black' value='수정하기'></div>
</form>