<div class="mobileapp-goods-register">
    <form name="mobileapp_goods_register_form" id="mobileapp_goods_register_form" action="./mobileapp_goods_ps.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="mode" id="mobileapp_mode" value="<?= $mode; ?>" />
        <input type="hidden" name="goodsNo" value="<?= $goodsNo; ?>" />
        <input type="hidden" name="optionN[sno][0]" value="<?=gd_isset($data['option'][0]['sno']);?>"/>
        <input type="hidden" name="optionN[optionNo][0]" value="<?=gd_isset($data['option'][0]['optionNo']); ?>"/>

        <!-- 기본 정보 -->
        <div class="mobileapp_part_default">
            <h2 class="section-header gRegister-Title" id="mobileapp_tab_default">
                <div class="row">
                    <div class="col-xs-9">
                        기본정보
                    </div>
                    <div class="col-xs-3 text-right gRegister-tab-arrow">
                        <img src="/admin/gd_share/img/mobileapp/icon/icon_arrow02_up.png" border="0" />
                    </div>
                </div>
            </h2>

            <div>
                <div class="container-default">
                    <table class="table table-bordered table1 gRegister-list-table">
                        <colgroup>
                            <col style="width: 30%;">
                            <col>
                        </colgroup>
                        <tbody>
                        <tr>
                            <th>카테고리</th>
                            <td colspan="2">
                                <?php
                                if(!$mode || $mode === 'register'){
                                    //등록
                                    echo $cate->getMultiCategoryBox('cateGoods', '', 'style="width:100%; margin: 2px 0 2px 0;"');
                                }
                                else {
                                    //수정
                                    echo $data['categoryName'];
                                }
                                ?>
                            </td>
                        </tr>
                        <tr class="text-center">
                            <th style="width: 30%;">PC쇼핑몰</th>
                            <td colspan="2">
                                <table class="gRegister-radio-box">
                                    <tr>
                                        <td>
                                            <input id="mobileapp_goodsDisplayFl1" class="radio" type="radio" name="goodsDisplayFl" value="y" <?= gd_isset($checked['goodsDisplayFl']['y']); ?> />
                                            <label for="mobileapp_goodsDisplayFl1" class="radio-label mgb0">노출함</label>
                                        </td>
                                        <td>
                                            <input id="mobileapp_goodsDisplayFl2" class="radio gRegister-radio" type="radio" name="goodsDisplayFl" value="n" <?= gd_isset($checked['goodsDisplayFl']['n']); ?> />
                                            <label for="mobileapp_goodsDisplayFl2" class="radio-label gRegister-radio-label mgb0">노출안함</label>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr class="text-center">
                            <th style="width: 30%;">모바일쇼핑몰</th>
                            <td colspan="2">
                                <table class="gRegister-radio-box">
                                    <tr>
                                        <td>
                                            <input id="mobileapp_goodsDisplayMobileFl1" class="radio" type="radio" name="goodsDisplayMobileFl" value="y" <?= gd_isset($checked['goodsDisplayMobileFl']['y']); ?> />
                                            <label for="mobileapp_goodsDisplayMobileFl1" class="radio-label mgb0">노출함</label>
                                        </td>
                                        <td>
                                            <input id="mobileapp_goodsDisplayMobileFl2" class="radio gRegister-radio" type="radio" name="goodsDisplayMobileFl" value="n" <?= gd_isset($checked['goodsDisplayMobileFl']['n']); ?> />
                                            <label for="mobileapp_goodsDisplayMobileFl2" class="radio-label gRegister-radio-label mgb0">노출안함</label>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <span class="gRegister-require">*</span>
                                상품명
                            </th>
                            <td colspan="2">
                                <input type="text" name="goodsNm" class="form-control input-sm" value="<?= gd_isset($data['goodsNm']); ?>" pattern=".{1,250}" maxlength="250" />

                            </td>
                        </tr>
                        <tr>
                            <th>정가</th>
                            <td colspan="2">
                                <input type="number" pattern="\d*" name="fixedPrice" class="form-control input-sm" step="any" value="<?= gd_money_format($data['fixedPrice'], false); ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th>판매가</th>
                            <td colspan="2">
                                <input type="number" pattern="\d*" name="goodsPrice" class="form-control input-sm" step="any" value="<?= gd_money_format($data['goodsPrice'], false); ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th>원본 이미지</th>
                            <td colspan="2">
                                <?php
                                if(!$mode || $mode === 'register'){
                                    ?>
                                    <img src="<?= $no_image; ?>" class="gRegister-inputFileBtn" border="0" id="mobileapp_imageOriginal" />
                                    <input type="hidden" name="mobileapp_imageOriginal" value="" id="mobileapp_imageOriginal_input" />
                                    <?php
                                } else {
                                    echo $image['detail']['thumb'][0];
                                }
                                ?>

                            </td>
                        </tr>
                        <?php if(!$mode || $mode === 'register'){ ?>
                            <tr>
                                <th colspan="3" class="gRegister-thIntegrate">
                                    <div class="description">
                                        원본 이미지만 등록 후 저장 시 개별 이미지(확대, 상세, 썸네일, 리스트, 심플 이미지)는 자동으로 리사이징하여 등록됩니다.
                                    </div>
                                </th>
                            </tr>
                            <tr>
                                <th colspan="3" class="gRegister-thIntegrate">
                                    <textarea name="goodsDescription" rows="5" placeholder="상품 상세 설명을 입력해주세요 (텍스트)" style="width:100%;"><?= gd_isset($data['goodsDescription']); ?></textarea>
                                </th>
                            </tr>
                            <tr>
                                <th>상품 상세 설명<br />이미지</th>
                                <td colspan="2">
                                    <table>
                                        <tr>
                                            <td>
                                                <img src="<?= $no_image; ?>" class="gRegister-inputFileBtn" border="0" id="mobileapp_goodsDescription_1" />
                                                <input type="hidden" name="mobileapp_goodsDescription[]" value="" id="mobileapp_goodsDescription_1_input" />
                                            </td>
                                            <td>
                                                <img src="<?= $no_image; ?>" class="gRegister-inputFileBtn" border="0" id="mobileapp_goodsDescription_2"/>
                                                <input type="hidden" name="mobileapp_goodsDescription[]" value="" id="mobileapp_goodsDescription_2_input" />
                                            </td>
                                            <td>
                                                <img src="<?= $no_image; ?>" class="gRegister-inputFileBtn" border="0" id="mobileapp_goodsDescription_3" />
                                                <input type="hidden" name="mobileapp_goodsDescription[]" value="" id="mobileapp_goodsDescription_3_input" />
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <th colspan="3" class="gRegister-thIntegrate">
                                    <div class="description">
                                        상품 상세 설명에 입력된 텍스트와 이미지는 가운데 정렬로 상품 상세 페이지에 노출됩니다. (텍스트 위, 이미지 아래 쪽에 위치)
                                    </div>
                                </th>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- 기본 정보 -->

        <!-- 판매 정보 -->
        <div class="mobileapp_part_sale">
            <h2 class="section-header gRegister-Title" id="mobileapp_tab_sale">
                <div class="row">
                    <div class="col-xs-9">
                        판매정보
                    </div>
                    <div class="col-xs-3 text-right gRegister-tab-arrow">
                        <img src="/admin/gd_share/img/mobileapp/icon/icon_arrow02.png" border="0" />
                    </div>
                </div>
            </h2>

            <div style="display: none;">
                <div class="container-default">
                    <table class="table table-bordered table1 gRegister-list-table">
                        <colgroup>
                            <col style="width: 30%;">
                            <col>
                        </colgroup>
                        <tbody>
                        <tr class="text-center">
                            <th style="width: 30%;">판매 재고</th>
                            <td colspan="2">
                                <table class="gRegister-radio-box">
                                    <tr>
                                        <td>
                                            <input id="mobileapp_stockFl1" class="radio gRegister-radio" type="radio" name="stockFl" value="n" <?= gd_isset($checked['stockFl']['n']); ?> />
                                            <label for="mobileapp_stockFl1" class="radio-label gRegister-radio-label mgb0">무한정 판매</label>
                                        </td>
                                        <td>
                                            <input id="mobileapp_stockFl2" class="radio gRegister-radio" type="radio" name="stockFl" value="y" <?= gd_isset($checked['stockFl']['y']); ?> />
                                            <label for="mobileapp_stockFl2" class="radio-label gRegister-radio-label mgb0">재고량 따름</label>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <th>상품 재고</th>
                            <td colspan="2">
                                <input type="number" pattern="\d*" name="stockCnt" class="form-control input-sm <?= $disabled['stockCnt']; ?>" value="<?= gd_isset($data['totalStock']); ?>" />
                            </td>
                        </tr>
                        <tr class="text-center">
                            <th style="width: 30%;">품절 상태</th>
                            <td colspan="2">
                                <table class="gRegister-radio-box">
                                    <tr>
                                        <td>
                                            <input id="mobileapp_soldOutFl1" class="radio" type="radio" name="soldOutFl" value="n" <?= gd_isset($checked['soldOutFl']['n']); ?> />
                                            <label for="mobileapp_soldOutFl1" class="radio-label mgb0">정상</label>
                                        </td>
                                        <td>
                                            <input id="mobileapp_soldOutFl2" class="radio gRegister-radio" type="radio" name="soldOutFl" value="y" <?= gd_isset($checked['soldOutFl']['y']); ?> />
                                            <label for="mobileapp_soldOutFl2" class="radio-label gRegister-radio-label mgb0">품절(수동)</label>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- 판매 정보 -->

        <!-- 옵션 설정 -->
        <div class="mobileapp_part_option" data-strDivision="<?= STR_DIVISION; ?>" data-realOptionCnt="<?= $data['optionCnt']; ?>">
            <h2 class="section-header gRegister-Title" id="mobileapp_tab_option">
                <div class="row">
                    <div class="col-xs-9">
                        옵션정보
                        <?php
                        if((int)$data['optionCnt'] > 0){
                            echo "<span>(사용함)</span>";
                        }
                        ?>
                    </div>
                    <div class="col-xs-3 text-right gRegister-tab-arrow">
                        <img src="/admin/gd_share/img/mobileapp/icon/icon_arrow02.png" border="0" />
                    </div>
                </div>
            </h2>

            <div style="display: none;">
                <div class="container-default">
                    <table class="table table-bordered table1 gRegister-list-table">
                        <colgroup>
                            <col style="width: 30%;">
                            <col>
                        </colgroup>
                        <tbody>
                        <tr class="text-center">
                            <th style="width: 30%;">옵션 사용</th>
                            <td colspan="2">
                                <table class="gRegister-radio-box">
                                    <tr>
                                        <td>
                                            <input id="mobileapp_optionFl1" class="radio" type="radio" name="optionFl" value="y" <?= gd_isset($checked['optionFl']['y']); ?> />
                                            <label for="mobileapp_optionFl1" class="radio-label mgb0">사용함</label>
                                        </td>
                                        <td>
                                            <input id="mobileapp_optionFl2" class="radio gRegister-radio" type="radio" name="optionFl" value="n" <?= gd_isset($checked['optionFl']['n']); ?> />
                                            <label for="mobileapp_optionFl2" class="radio-label gRegister-radio-label mgb0">사용안함</label>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        </tbody>
                        <tbody id="mobileapp_option_display_area">
                        <tr>
                            <th>자주쓰는 옵션</th>
                            <td colspan="2">
                                <div class="more-button half-radius" align="center">
                                    <a href="javascript:;" class="btn btn-md btn-block-app btn-default-gray more border-r-n" id="mobileapp_add_option" title="자주쓰는 옵션 선택">자주쓰는 옵션 선택</a>
                                </div>
                            </td>
                        </tr>
                        <tr class="text-center">
                            <th style="width: 30%;">옵션 노출 방식</th>
                            <td colspan="2">
                                <table class="gRegister-radio-box">
                                    <tr>
                                        <td>
                                            <input id="mobileapp_optionDisplayFl1" class="radio" type="radio" name="optionY[optionDisplayFl]" value="s" <?=gd_isset($checked['optionDisplayFl']['s']); ?> />
                                            <label for="mobileapp_optionDisplayFl1" class="radio-label mgb0">일체형</label>
                                        </td>
                                        <td>
                                            <input id="mobileapp_optionDisplayFl2" class="radio" type="radio" name="optionY[optionDisplayFl]" value="d" <?=gd_isset($checked['optionDisplayFl']['d']); ?> />
                                            <label for="mobileapp_optionDisplayFl2" class="radio-label mgb0">분리형</label>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" class="gRegister-thIntegrate">
                                <?=gd_select_box('mobileapp_optionY_optionCnt', 'optionY[optionCnt]', gd_array_change_key_value(range(1, $option_limit)), '개', gd_isset($data['optionCnt']), '=옵션 개수=', ''); ?>
                                <div class="description">
                                    옵션 개수 2개 이상의 등록/수정은 PC로 가능합니다.
                                </div>
                                <div id="mobileapp_optionArea">
                                    <?php
                                    if((int)$data['optionCnt'] > 0){
                                        foreach($data['optionName'] as $key => $value){
                                            $thisOptionValue = implode(",", $data['option']['optVal'][$key+1]);
                                            ?>
                                            <div class='gRegister-option-input-area'>
                                                <input type='text' id='mobileapp_optionName_<?= $key; ?>' name='optionY[optionName][]' class='form-control input-sm gRegister-optionName' value='<?= $value; ?>' placeholder='옵션명을 입력해주세요. ex)사이즈' input-type='optionName' />
                                                <input type='text' id='mobileapp_optionValue_<?= $key; ?>' name='optionY[optionValue][<?= $key; ?>][]' class='form-control input-sm gRegister-option-selector' value='<?= $thisOptionValue; ?>' placeholder='옵션값을 입력해주세요. ex)XL' style='margin-top: 2px !important;' />
                                            </div>
                                            <?php
                                        }
                                    }
                                    ?>
                                </div>
                            </td>
                        </tr>
                        <tr class="gRegister-display-add">
                            <th colspan="3" class="gRegister-thIntegrate">
                                <div class="description">
                                    옵션값은 엔터키로 구분
                                </div>
                            </th>
                        </tr>
                        <tr class="gRegister-display-add">
                            <th colspan="3" class="gRegister-thIntegrate">
                                <a href="javascript:;" class="btn btn-lg btn-block-app btn-default-gray more border-r-n" id="mobileapp_option_adjust" title="옵션 가격 설정 적용">옵션 가격 설정 적용</a>

                                <div id="mobileapp_detail_option_area" style="display: none;">
                                    <?php
                                    if((int)$data['optionCnt'] > 0){
                                        $optionNameCount = count($data['optionName']);
                                        if($optionNameCount === 2){
                                            $optColspan = 5;
                                        }
                                        else if($optionNameCount === 1){
                                            $optColspan = 4;
                                        }
                                        else {
                                            $optColspan = 3;
                                        }

                                        echo "<table class='table table-bordered table1 gRegister-option-detail'><colgroup><col /><col /><col /><col /><col /></colgroup>";
                                        echo "<thead><tr><th class='gRegister-option-detail-title' colspan='".$optColspan."'>옵션 가격/재고 설정";
                                        echo "<div><button type='button' class='btn btn-default-gray btn-sm gRegister-optionDetail-all-btn'>일괄적용</button></div>";
                                        echo "</th></tr>";

                                        foreach($data['optionName'] as $key => $value){
                                            echo "<th>".$value."</th>";
                                        }
                                        echo "<th>노출</th><th>품절</th><th>상세</th></thead>";
                                        echo "<tbody>";

                                        foreach($data['option'] as $key => $value) {
                                            if ($key === 'optVal') {
                                                continue;
                                            }

                                            $optionPriceID = "mobileapp_optionPrice_" . $key;
                                            $stockCntID = "mobileapp_stockCnt_" . $key;
                                            $insertBtnID = "mobileapp_insertBtn_" . $key;

                                            //옵션이 2개 이상이어서 노출되지 않을시 값유지
                                            $optionTextArray = array();
                                            for($i=1; $i<=5; $i++){
                                                if(trim($value['optionValue'.$i]) !== '') {
                                                    $optionTextArray[] = $value['optionValue'.$i];
                                                }
                                            }
                                            $optionValueText = implode(STR_DIVISION, $optionTextArray);

                                            $optionViewFl = ($value['optionViewFl'] === 'y') ? "checked='checked'" : "";
                                            $optionSellFl = ($value['optionSellFl'] === 'n') ? "checked='checked'" : "";
                                            if((int)$value['optionPrice'] > 0 || (int)$value['stockCnt'] > 0){
                                                $buttonAttr = array(
                                                    'class' => 'btn-default-gray',
                                                    'ment' => '수정',
                                                );
                                            }
                                            else {
                                                $buttonAttr = array(
                                                    'class' => 'btn-danger',
                                                    'ment' => '입력',
                                                );
                                            }

                                            echo "<tr>";
                                            echo "<td>".$value['optionValue1']."</td>";
                                            if(trim($value['optionValue2']) !== '') {
                                                echo "<td>".$value['optionValue2']."</td>";
                                            }
                                            echo "<td>";
                                            echo "<input type='hidden' name='optionY[optionValueText][]' value='".$optionValueText."' />";
                                            echo "<input id='mobileapp_option_optionViewFlApply_".$key."' type='checkbox' value='y' name='optionY[optionViewFl][".$key."]' ".$optionViewFl." />";
                                            echo "</td>";
                                            echo "<td><input id='mobileapp_option_optionSellFl_".$key."' type='checkbox' value='n' name='optionY[optionSellFl][".$key."]' ".$optionSellFl." /></td>";
                                            echo "<td>";
                                            echo "<button type='button' class='btn ".$buttonAttr['class']." btn-sm gRegister-optionDetail-btn' id='".$insertBtnID."' data-optionID='".$optionPriceID."' data-stockID='".$stockCntID."'>".$buttonAttr['ment']."</button>";

                                            //옵션매입가
                                            echo "<input type='hidden' name='optionY[optionCostPrice][]'  value='".gd_isset($value['optionCostPrice'])."' />";
                                            //옵션가
                                            echo "<input type='hidden' id='".$optionPriceID."' name='optionY[optionPrice][]'  value='".gd_isset($value['optionPrice'])."' />";
                                            //재고량
                                            echo "<input type='hidden' id='".$stockCntID."' name='optionY[stockCnt][]' value='".gd_isset($value['stockCnt'])."' />";
                                            //자체 옵션코드
                                            echo "<input type='hidden' name='optionY[optionCode][]'  value='".gd_isset($value['optionCode'])."' />";
                                            //메모
                                            echo "<input type='hidden' name='optionY[optionMemo][]'  value='".gd_isset($value['optionMemo'])."' />";
                                            echo "</td>";
                                            echo "</tr>";
                                        }

                                        echo "</tbody>";
                                        echo "</table>";
                                    }
                                    ?>
                                </div>
                            </th>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- 옵션 설정 -->

        <div class="text-center mgt20 overflow-h">
            <?php
            if(!$mode || $mode === 'register') {
                ?>
                <div class="gRegister-register-button-area">
                    <input type="submit" value="등&nbsp;록" class="btn btn-lg btn-info">
                </div>
                <?php
            }
            else {
                ?>
                <div class="row gRegister-modify-button-area">
                    <div class="col-xs-8">
                        <input type="submit" value="저&nbsp;장" class="btn btn-lg btn-info">
                    </div>
                    <div class="col-xs-4">
                        <a href="javascript:;" class="btn btn-lg btn-block-app btn-default-gray more border-r-n" id="mobileapp_list_btn" title="닫 기">닫 기</a>
                    </div>
                </div>
                <?php
            }
            ?>

        </div>

    </form>
</div>
