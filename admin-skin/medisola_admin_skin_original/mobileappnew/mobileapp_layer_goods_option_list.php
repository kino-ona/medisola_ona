<style>
    .gRegister-layer-goods-option { width: 100%; }
    .gRegister-layer-goods-option tr th,
    .gRegister-layer-goods-option tr td {
        height: 40px !important;
        text-align: center;
        border: 1px solid #ddd;
        padding: 3px !important;
    }
    .gRegister-layer-goods-option thead { background-color: #f0f0f0; }
    .gRegister-layer-goods-option button { border-radius: 0px !important; }
    .description { margin-top: 3px; }
</style>
<div style="max-height:400px;overflow-x:hidden;overflow-y:auto">
    <table class="gRegister-layer-goods-option">
    <thead>
    <tr>
        <th style="width: 50%;">옵션 관리명</th>
        <th>옵션표시</th>
        <th>선택</th>
    </tr>
    </thead>
    <tbody>
    <?php
    if (is_array($data)) {
        $arrOptionDisplay    = array('s' => '일체형', 'd' => '분리형', 'm' => '멀티형');
        foreach ($data as $key => $val) {
            $arrOptionName    = explode(STR_DIVISION,$val['optionName']);
    ?>
    <tr>
        <td><?=$val['optionManageNm'];?></td>
        <td><?=$arrOptionDisplay[$val['optionDisplayFl']];?></td>
        <td><button type="button" class="btn btn-danger btn-sm mobileapp_favoriteOption" data-sno="<?=$val['sno'];?>">선택</button></td>
    </tr>
    <?php
        }
    }
    ?>
    </tbody>
    </table>

    <div class="description">
        모바일에서는 옵션 개수가 2개 이하인 자주쓰는 옵션만 선택이 가능합니다.
    </div>
</div>
