<style>
    .table-cols .require {min-width: 120px}
</style>
<div class="table-title">
    상품 아이콘 직접 등록
</div>
<form id="frmIcon" name="frmIcon" target="ifrmProcess" action="./goods_ps.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="mode" value="self_register" />
    <input type="hidden" name="goodsNo" value="<?= $goodsNo ?>">
    <table class="table table-cols">
        <colgroup>
            <col>
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th class="require">아이콘 이름</th>
            <td>
                <label title="아이콘 이름을 넣어주세요. 태그는 &quot;사용할 수 없습니다!&quot;"><input type="text" name="iconNm" value="<?= $data['iconNm']; ?>" class="form-control width-lg js-maxlength" maxlength="30"/></label>
            </td>
        </tr>
        <tr>
            <th class="require">아이콘 이미지</th>
            <td>
                <div class="form-inline">
                    <div style="padding:10px;border:1px solid #AEAEAE;float:left;text-align:center;display:table-cell; vertical-align:middle;margin-right:10px;">
                        <?php if (empty($data['iconImage']) === false) {
                            echo gd_html_image(UserFilePath::icon('goods_icon', $data['iconImage'])->www(), $data['iconNm']);
                        } ?>
                    </div>
                    <div style="float:left;">
                        <input type="file" name="iconImage" value="" class="form-control"/>
                        <div style="padding-top:5px;"><?php if (empty($data['iconImage']) === false) {
                                echo gd_htmlspecialchars_slashes($data['iconImage'], 'add');
                            } ?></div>
                    </div>
                </div>
                <div style="clear:both;padding-bottom:10px;"></div>
                <div class="notice-info">아이콘 이미지 사이즈는 작게 해서 올려 주세요. 해당 이미지 크기 그대로 출력이 됩니다.</div>
            </td>
        </tr>
        </tbody>
    </table>
    <div class="table-btn">
        <button type="button" class="btn btn-sm btn-black" onclick="self_register();">직접 등록하기</button>
    </div>
</form>

<div class="table-title">
    상품 아이콘 선택 등록
</div>
<form id="frmIcon2" name="frmIcon" target="ifrmProcess" action="./goods_ps.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="mode" value="check_register" />
    <input type="hidden" name="goodsNo" value="<?= $goodsNo ?>">
    <table class="table table-cols">
        <td>
            <?php foreach ($goodsIcon as $key => $val) {
                echo '<label class="nobr checkbox-inline"><input type="checkbox" name="goodsIconCd[]" value="' . $val['iconCd'] . '" ' . gd_isset($checked['goodsIconCd'][$val['iconCd']]) . ' /> ' . gd_html_image(UserFilePath::icon('goods_icon', $val['iconImage'])->www(), $val['iconNm']) . '</label>' . chr(10);
            } ?>
        </td>
    </table>
    <div class="table-btn">
        <button type="button" class="btn btn-sm btn-black" onclick="check_register();">선택 등록하기</button>
    </div>
</form>

<script>
    function self_register()
    {
        $("#frmIcon").submit();
    }

    function check_register()
    {
        $("#frmIcon2").submit();
    }
</script>