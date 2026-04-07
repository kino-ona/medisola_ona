<div class="page-header js-affix">
    <h3><?=end($naviMenu->location); ?> </h3>
    <div class="btn-group">
        <input type="button" value="저장" class="btn btn-red" id="batchSubmit"/>
    </div>
</div>

<?php include($goodsSearchFrm); ?>

<form id="frmBatch" name="frmBatch" action="../goods/goods_ps.php"  target="ifrmProcess" method="post">
    <input type="hidden" name="mode" value="early_delivery_goods">

    <table class="table table-rows">
        <thead>
        <tr>
            <th class="center" width='20'><input type="checkbox" class="js-checkall" data-target-name="arrGoodsNo[]"></th>
            <th class='center'>상품유형</th>
            <th>새벽배송조회URL</th>
            <th class="center" width='100'>상품코드</th>
            <th class="center" width='80'>이미지</th>
            <th>상품명</th>
            <th class="width-xs">판매가</th>
            <th class="width-xs">노출상태</th>
            <th class="width-xs">퍈매상태</th>
            <th class="width-4xs">재고</th>
        </tr>


        </thead>
        <tbody>
        <?php
        if (gd_isset($data) && count($data) > 0 ) {
            foreach ($data as $key => $val) {
                ?>
                <tr>
                    <td class="center">
                        <input type="checkbox" name="arrGoodsNo[]" value="<?=$val['goodsNo']; ?>"/>
                    </td>
                    <td align='center'>
                        <select name='useEarlyDelivery[<?=$val['goodsNo']?>]' class='useEarlyDelivery form-control'>
                            <option value='0'<?=$val['useEarlyDelivery'] == 0 ?" selected":"";?>>일반상품</option>
                            <option value='1'<?=$val['useEarlyDelivery'] == 1 ?" selected":"";?>>새벽배송상품</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="earlyDeliveryUrl[<?=$val['goodsNo']?>]" class="earlyDeliveryUrl form-control" value="<?=$val['earlyDeliveryUrl']?>">
                    </td>
                    <td class="center"><?=$val['goodsNo']; ?></td>
                    <td class="center">
                        <div>
                            <?=gd_html_goods_image($val['goodsNo'], $val['imageName'], $val['imagePath'], $val['imageStorage'], 40, $val['goodsNm'], '_blank'); ?>
                        </div>
                    </td>
                    <td>
                        <a class="text-blue hand" onclick="goods_register_popup('<?=$val['goodsNo']; ?>' <?php if(gd_is_provider() === true) { echo ",'1'"; } ?>);"><?=$val['goodsNm']; ?></a>
                    </td>
                    <td align="center">
                        <!-- 판매가 -->
                        <?= gd_currency_display($val['goodsPrice']) ?>
                    </td>
                    <td align="center">
                        <!--노출상태-->
                        <p>PC | <?= $val['goodsDisplayFl'] == 'y' ? '노출함' : '노출안함' ?></p>
                        <p>모바일 | <?= $val['goodsDisplayMobileFl'] == 'y' ? '노출함' : '노출안함' ?></p>
                    </td>

                    <td align="center">
                        <!--노출상태-->
                        <p>PC | <?= $val['goodsSellFl'] == 'y' ? '판매함' : '판매안함' ?></p>
                        <p>모바일 | <?= $val['goodsDisplayMobileFl'] == 'y' ? '판매함' : '판매안함' ?></p>
                    </td>
                    <td align="center">
                        <!--재고-->
                        <?= $val['totalStock'] == 0 ? '∞' : $val['totalStock'] ?>
                    </td>

                </tr>
                <?php
            }
        }
        ?>
        </tbody>
    </table>
    <div class='table-action form-inline'>
        <div class='pull-left'>
            <span class="action-title">선택된 상품 </span><input type='submit' value='저장하기' class='btn btn-black'
                                                            onclick="return confirm('정말 저장하시겠습니까?');">
            <div style="display: inline-block; vertical-align: -4px;margin-left: 15px;">
                <span class="">검색된 전체 상품</span>
                <select name="searchAllList1" id="searchAllList1" class="form-control">
                    <option value="">전체</option>
                    <option value="0">일반상품</option>
                    <option value="1">새벽배송상품</option>
                </select>
                <input type="text" class="form-control" id="searchAllList2" name="searchAllList2">
                <div class="btn-group">
                    <button type="button" class="btn btn-black btnTotalProcess">일괄변경</button>
                </div>
            </div>
        </div>
        </div>
    </div>
    <div class="center"><?=$page->getPage();?></div>
</form>

<script type="text/javascript">

    const btnTotalProcess = document.querySelector(".btnTotalProcess");

    btnTotalProcess.addEventListener('click', function() {

        //검색된 전체 상품 셀렉트 박스
        const searchAllList = document.querySelector("#searchAllList1"),
            searchAllListValue = searchAllList.options[searchAllList.selectedIndex].value;

        //검색된 전체 상품 텍스트 박스
        const searchAllList2 = document.querySelector("#searchAllList2"),
            searchAllListValue2 = searchAllList2.value;

        //현재리스트 체크박스
        const allCheckBox = document.querySelectorAll("input[name='arrGoodsNo[]']");

        if(!searchAllListValue && !searchAllListValue2) {
            alert("일괄적용할 값을 선택해주세요.");
            return false;
        }

        allCheckBox.forEach(el => {
            el.checked = true;
        })

        $(".useEarlyDelivery").val(searchAllListValue).prop("selected", true);
        $(".earlyDeliveryUrl").val(searchAllListValue2);
        frmBatch.submit();


    });


    $(document).ready(function(){
        $( "#batchSubmit" ).click(function() {
            $("#frmBatch").submit();
        });
    });

</script>