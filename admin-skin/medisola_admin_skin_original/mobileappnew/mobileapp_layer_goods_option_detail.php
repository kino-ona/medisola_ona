<style>
    .gRegister-layer-option-detail { width: 100%; }
    .gRegister-layer-option-detail tr th,
    .gRegister-layer-option-detail tr td {
        height: 40px !important;
        text-align: center;
        border: 1px solid #ddd;
        padding: 3px !important;
    }
    .gRegister-layer-option-detail tr th {
        background-color: #f0f0f0;
        width: 60px;
    }
    .gRegister-layer-option-detail tbody { border: 1px solid #ddd; }
    .description { margin-top: 3px; }
</style>
<div style="max-height:400px;overflow-x:hidden;overflow-y:auto">
    <table class="gRegister-layer-option-detail">
        <input type="hidden" id="mobileapp_layer_detailMode" name="mobileapp_layer_detailMode" value="<?= $paramData['detailMode']; ?>" />
        <input type="hidden" id="mobileapp_layer_thisID" name="mobileapp_layer_thisID" value="<?= $paramData['thisID']; ?>" />
        <input type="hidden" id="mobileapp_layer_optionID" name="mobileapp_layer_optionID" value="<?= $paramData['optionID']; ?>" />
        <input type="hidden" id="mobileapp_layer_stockID" name="mobileapp_layer_stockID" value="<?= $paramData['stockID']; ?>" />
    <tbody>
    <tr>
        <th>옵션가</th>
        <td><input type="number" pattern="\d*" name="mobileapp_layer_optionPrice" step="any" class="form-control input-sm" value="<?= gd_money_format($paramData['optionValue'], false); ?>" /></td>
    </tr>
    <tr>
        <th>재고량</th>
        <td><input type="number" pattern="\d*" name="mobileapp_layer_stockCnt" step="any" class="form-control input-sm" value="<?= $paramData['stockValue']; ?>" <?= $disabled['mobileapp_layer_stockCnt']; ?>/></td>
    </tr>
    </tbody>
    </table>

    <div class="description">
        <div>옵션가는 상품의 판매가 기준, 추가 또는 차감될 옵션별 금액이 있는 경우에만 입력합니다.</div>
        <div style="color: red;">판매가에 추가될 옵션가는 양수, 차감될 옵션가는 음수(마이너스)로 입력 합니다.</div>
    </div>

    <div class="text-center mgt20 overflow-h">
        <div class="row">
            <div class="col-xs-8" style="padding-right: 5px;">
                <button type="button" class="btn btn-lg btn-info border-r-n" id="mobileapp_saveDetailOption" style="width:100%; background-color: #fa2828 !important; border-color: #fa2828 !important; color: white;">저&nbsp;장</button>
            </div>
            <div class="col-xs-4" style="padding-left: 0px;">
                <button type="button" class="btn btn-lg btn-default-gray border-r-n" data-dismiss="modal" style="width:100%;">닫 기</button>
            </div>
        </div>
    </div>
</div>
