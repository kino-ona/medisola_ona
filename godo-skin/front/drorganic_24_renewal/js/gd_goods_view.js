var gd_goods_view = function () {
    var setOptionFl = "n";
    var setOptNo = "";
    var setOptionTextFl = "n";
    var setOptionDisplayFl = "s";
    var setAddGoodsFl = "n";
    var setIntDivision = "";
    var setStrDivision = "";
    var setMileageUseFl = "n";
    var setCouponUseFl = "n";
    var setMinOrderCnt = 1;
    var setMaxOrderCnt = 0;
    var setStockFl = "n";
    var setSalesUnit = 1;
    var setDecimal = 0;
    var setGoodsPrice = 0;
    var setGoodsNo = "";
    var setMileageFl = "";
    var setControllerName = "";
    var setCartTabFl = "n";
    var setTemplate = "";
    var setFixedSales = "option";
    var setFixedOrderCnt = "option";
    var setGoodsNm = "";
    var setOptionPriceFl = "";
    var setStockCnt = 0;
    var setOriginalMinOrderCnt = 0;

    /**
     * 최소수량 체크
     *
     * @param string keyNo 상품 배열 키값
     */
    this.input_count_change = function (inputName, goodsFl) {
        var frmId = "#" + $(inputName).closest("form").attr("id");
        if ($(inputName).val() == "") {
            $(inputName).val("0");
        }
        $(inputName).val(
            $(inputName)
                .val()
                .replace(/[^0-9\-]/g, "")
        );

        var itemNo = $(inputName).data("key");
        if (itemNo) {
            var optionDisplay = itemNo.split(setIntDivision);
            var optionDisplay = optionDisplay[0];
        } else {
            var optionDisplay = "0";
        }

        if (
            $(
                "#option_display_item_" +
                    optionDisplay +
                    " input[name='couponApplyNo[]']"
            ).val()
        ) {
            alert(
                __(
                    "쿠폰이 적용된 상품은 수량변경을 할 수 없습니다.\n쿠폰취소 후 수량을 변경해주세요."
                )
            );
            $(inputName).val($(inputName).data("value"));
            return false;
        }

        if (goodsFl) {
            var nowCnt = parseInt(
                $("input.goodsCnt" + setOptNo + "_" + itemNo).val()
            );
            var minCnt = parseInt(setMinOrderCnt);
            var maxCnt = parseInt(setMaxOrderCnt);
            var stockFl = setStockFl;
            var setStock = parseInt($(inputName).data("stock"));
            if (
                ((setStock > 0 && maxCnt == 0) || setStock <= maxCnt) &&
                stockFl == "y"
            ) {
                maxCnt = parseInt(setStock);
            }

            var salesUnit = parseInt(setSalesUnit);
        } else {
            var nowCnt = parseInt($(inputName).val());
            var minCnt = 1;
            var salesUnit = 1;

            if ($(inputName).data("stock-fl") == "1") {
                var maxCnt = parseInt($(inputName).data("stock"));
            } else {
                var maxCnt = 0;
            }
        }

        if (parseInt(nowCnt % salesUnit) > 0) {
            alert(
                setGoodsNm + __("%s개 단위로 묶음 주문 상품입니다.", salesUnit)
            );
            $(inputName).val($(inputName).data("value"));
            return false;
        }

        if (
            nowCnt < minCnt &&
            minCnt != 0 &&
            minCnt != "" &&
            typeof minCnt != "undefined"
        ) {
            alert(setGoodsNm + __("최소수량은 %1$s이상입니다.", minCnt));
            $(inputName).val($(inputName).data("value"));
            return false;
        }

        if (
            nowCnt > maxCnt &&
            maxCnt != 0 &&
            maxCnt != "" &&
            typeof maxCnt != "undefined"
        ) {
            if (parseInt(maxCnt % salesUnit) > 0) {
                alert(
                    setGoodsNm +
                        __(
                            "%1$s최대 주문 가능 수량을 확인해주세요.",
                            setGoodsNm
                        )
                );
                $(inputName).val($(inputName).data("value"));
                return false;
            } else {
                alert(setGoodsNm + __("최대수량은 %1$s이하입니다.", maxCnt));
                $(inputName).val($(inputName).data("value"));
                return false;
            }
        }

        $(inputName).data("value", $(inputName).val());

        if (setCartTabFl == "y") {
            $(
                "#frmCartTabViewLayer input[class='" +
                    $(inputName).attr("class") +
                    "']"
            ).val($(inputName).val());
        }

        this.goods_calculate(frmId, goodsFl, itemNo, $(inputName).val());
    };

    /**
     * 수량 변경하기
     *
     * @param string inputName input box name
     * @param string modeStr up or dn
     * @param integer minCnt 최소수량
     * @param integer maxCnt 최대수량
     * @param boolean allowZero 수량 감소시 0개 허용 여부 (구성품 수량 변경만 해당)
     * @param integer componentMaxSelection 구성품 총량 숫자 제한 (구성품 수량 변경만 해당)
     */
    this.count_change = function (
        inputName,
        goodsFl,
        allowZero = false,
        componentMaxSelection
    ) {
        var isComponentGoodsCountChange = componentMaxSelection > 0;
        var localMinOrderCnt = allowZero ? 0 : setMinOrderCnt;

        var frmId = "#" + $(inputName).closest("form").attr("id");
        var tmpStr = $(inputName).val().split(setStrDivision);
        var modeStr = tmpStr[0];
        var itemNo = tmpStr[1];
        var optionDisplay = itemNo.split(setIntDivision);

        if (
            !isComponentGoodsCountChange &&
            $(
                "#option_display_item_" +
                    optionDisplay[0] +
                    " input[name='couponApplyNo[]']"
            ).val()
        ) {
            alert(
                __(
                    "쿠폰이 적용된 상품은 수량변경을 할 수 없습니다.\n쿠폰취소 후 수량을 변경해주세요."
                )
            );
            return false;
        }
        $("button.goods_cnt").attr("disabled", true);
        $("button.add_goods_cnt").attr("disabled", true);
        var minCnt = parseInt(localMinOrderCnt);
        var maxCnt = parseInt(setMaxOrderCnt);
        var nowCnt = parseInt(
            $(inputName).closest("span.count").find("input").val()
        );
        var nowCntChangeFl = false;

        var limitCnt = parseInt(
            $(inputName).closest("span.count").find("input").data("limitCnt")
        );

        let salesUnit = 1;
        if (goodsFl) {
            salesUnit = parseInt(setSalesUnit);

            // 최소 수량 체크
            if (minCnt == 0 || minCnt == "" || typeof minCnt == "undefined") {
                minCnt = 1;
            }

            if (nowCnt < minCnt) {
                nowCnt = parseInt(minCnt);
                nowCntChangeFl = true;
            }

            // 최대 수량 체크
            if (maxCnt == 0 || maxCnt == "" || typeof maxCnt == "undefined") {
                var maxCntChk = false;
            } else {
                var maxCntChk = true;
                maxCnt = parseInt(maxCnt);
            }

            var stockFl = setStockFl;
            var setStock = parseInt(
                $(inputName).closest("span.count").find("input").data("stock")
            );
            if (
                ((setStock > 0 && maxCnt == 0) || setStock <= maxCnt) &&
                stockFl == "y"
            ) {
                maxCnt = setStock;
                var maxCntChk = true;
            }
        } else {
            salesUnit = 1;

            if (
                $(inputName)
                    .closest("span.count")
                    .find("input")
                    .data("stock-fl") == "1"
            ) {
                var maxCnt = parseInt(
                    $(inputName)
                        .closest("span.count")
                        .find("input")
                        .data("stock")
                );
                var maxCntChk = true;
            } else {
                var maxCnt = 0;
            }
        }

        // 골라 담기 구성 상품 총 선택 개수 제한
        var componentGoodsCntSum = 0;

        if (isComponentGoodsCountChange) {
            $('[name*="componentGoodsCnt"]').each(function () {
                var value = parseInt($(this).val(), 10) || 0; // Convert value to integer, default to 0 if NaN
                componentGoodsCntSum += value;
            });
            maxCnt = componentMaxSelection - (componentGoodsCntSum - nowCnt);
            maxCntChk = true;
            if (componentMaxSelection > 20 && componentMaxSelection % 2 === 0) {
                salesUnit = 2;
            }
        }

        // 골라 담기 구성 상품 개별 선택 개수 제한
        if (limitCnt > 0 && maxCnt > limitCnt) {
            maxCnt = limitCnt;
            maxCntChk = true;
        }

        var firstHiddenAddPrice = $(".hidden_add_price select[name^='addGoodsInput']").first();
        const addedPriceOptionExist = firstHiddenAddPrice.length > 0;

        if (isNaN(nowCnt) === true) {
            var thisCnt = minCnt;
        } else {
            if (modeStr == "up") {
                if (
                    (maxCntChk === true && nowCnt + salesUnit > maxCnt) ||
                    nowCntChangeFl
                ) {
                    if (isComponentGoodsCountChange && componentGoodsCntSum >= componentMaxSelection) {
                        alert(__("더 이상 고를 수 없습니다.\n다른 메뉴 선택 개수를 줄인 후 추가해 주세요."));
                        var thisCnt = nowCnt;
                    } else if (limitCnt > 0 && limitCnt == maxCnt) {
                        const itemNoArray = itemNo.split('||');
                        const addGoodsNo = itemNoArray[1];
                        if (addedPriceOptionExist) {
                            // 해당 메뉴의 프리미엄 배수 가져오기
                            const targetInput = $(inputName).closest("span.count").find('input[name*="componentGoodsCnt"]');
                            const premiumMultiplier = parseInt(targetInput.data("premium-multiplier"), 10) || 1;
                            const addedPriceValue = firstHiddenAddPrice?.val()?.split(setStrDivision)[0]?.split(setIntDivision)[1] || 1000;
                            const premiumPrice = premiumMultiplier * Number(addedPriceValue);
                            const premiumPriceText = Number(premiumPrice).toLocaleString();
                            
                            if (confirm(__("이 메뉴는 %1$s개 이상 선택 시 개 당 %2$s원의 추가 금액이 발생합니다. 추가할까요?", [limitCnt + 1, premiumPriceText]))) {
                                var thisCnt = nowCnt + salesUnit;
                            } else {
                                var thisCnt = nowCnt;
                            }
                        } else {
                            alert(
                                __(
                                    "이 메뉴는 %1$s개 까지만 고를 수 있습니다.",
                                    limitCnt
                                )
                            );
                            var thisCnt = nowCnt;
                        }
                    }
                } else {
                    var thisCnt = nowCnt + salesUnit;
                }
            } else if (modeStr == "dn") {
                if (nowCnt > minCnt) {
                    var thisCnt = nowCnt - salesUnit;
                } else {
                    var thisCnt = nowCnt;
                }
            }
        }

        var goodsCntInput = $(inputName).closest("span.count").find("input");
        if (setOptNo != "" && setOptNo != undefined) {
            goodsCntInput = $(inputName)
                .closest("span.count")
                .find('input[type="text"]');
        }
        $(goodsCntInput).val(thisCnt);
        $(goodsCntInput).focus();
        $(goodsCntInput).data("value", thisCnt);

        if (thisCnt == 0) {
            $(goodsCntInput).addClass("disabled");
        } else {
            $(goodsCntInput).removeClass("disabled");
        }

        if (setCartTabFl == "y") {
            $(
                "#frmCartTabViewLayer input[class='" +
                    $(goodsCntInput).attr("class") +
                    "']"
            ).val(thisCnt);
        }

        if (isComponentGoodsCountChange && addedPriceOptionExist) {
            var totalOverflowedCount = 0;

            firstHiddenAddPrice.find('option:eq(1)').prop('selected', true); // select first option item except '선택'
            var addedPrice = firstHiddenAddPrice?.val()?.split(setStrDivision)[0]?.split(setIntDivision)[1];

            $('[name*="componentGoodsCnt"]').each(function (index) {
                var value = parseInt($(this).val(), 10) || 0; 
                var limitCnt = parseInt($(this).data("limit-cnt"), 10) || 0;
                var premiumMultiplier = parseInt($(this).data("premium-multiplier"), 10) || 1; // 기본값 1배수
                
                if (limitCnt > 0) {
                    const componentGoodsAddedPrice = $(this).closest('dl').find('input[name^="componentGoodsAddedPrice"]');
                    if (value > limitCnt) {
                        const overflowedCount = (value - limitCnt);
                        // 프리미엄 배수에 따라 실제 표시될 가격 계산
                        const premiumPrice = premiumMultiplier * Number(addedPrice);
                        componentGoodsAddedPrice.val(overflowedCount * premiumPrice);
                        
                        // add_goods 수량 계산: 직접 배수 적용
                        totalOverflowedCount += (overflowedCount * premiumMultiplier);
                    } else {
                        componentGoodsAddedPrice.val(0);
                    }
                }
            });

            setControllerName.add_goods_select(firstHiddenAddPrice, totalOverflowedCount, true);
        }

        this.goods_calculate(frmId, goodsFl, itemNo, thisCnt);

        if (isComponentGoodsCountChange) {
            this.guide_component_selection_count(); 
            
            const parentDL = goodsCntInput.closest('dl');

            const componentGoodsNoInput = parentDL.find('input[name^="componentGoodsNo"]');
            const componentGoodsNoValue = componentGoodsNoInput.val();
            const componentGoodsNameInput = parentDL.find('input[name^="componentGoodsName"]');
            const componentGoodsNameValue = componentGoodsNameInput.val();

            amplitude.logEvent('selectmenu_menu_click', {
                menu_id: componentGoodsNoValue,
                menu_name: componentGoodsNameValue,
                product_name: $('.headingArea h2').text().replace(/\n|\t/g, ''),
                product_id: setGoodsNo,
                click_type: modeStr === 'up' ? 'plus' : 'minus',
                qty : nowCnt,
                new_qty : thisCnt,
            });
        }
    };

    this.guide_component_selection_count = function () {
        const allowedCount = $("[id^='option_display_item_']:first")
            .find('input[name="allowedCount[]"]')
            .val();

        let totalItemCount = 0;

        $('[class*="componentGoodsCnt_"]').each(function () {
            totalItemCount += parseInt($(this).val()) || 0;
        });

        var addedPriceTag = $(".disabled_add_goods input[name^='add_goods_total_price']").first();
        var addedPriceSum = addedPriceTag.val();
        if(addedPriceSum > 0) {
            $(".countGuide").html(
                __("%1$s식 중 %2$s식 선택하였습니다. (+%3$s원)", [
                    allowedCount,
                    totalItemCount,
                    Number(addedPriceSum).toLocaleString(),
                ])
            );
        } else {
            $(".countGuide").html(
                __("%1$s식 중 %2$s식 선택하였습니다.", [
                    allowedCount,
                    totalItemCount,
                ])
            );
        }

        const allSelected = allowedCount == totalItemCount;

        $(".completeButton:first").css(
            "pointer-events",
            allSelected ? "auto" : "none"
        );

        if (allSelected) {
            $(".completeButton").removeClass("disabled");
        } else {
            $(".completeButton").addClass("disabled");
        }

        $(".completeButton .title:first").html(
            allSelected
                ? __("메뉴 선택 완료")
                : __("%1$s 개 더 선택해 주세요.", allowedCount - totalItemCount)
        );
        this.display_selected_component_goods();
    };

    /**
     * 옵션에 따른 가격 출력
     *
     * @param integer optionNo 상품 배열 키값 (기본 0)
     */
    this.option_price_display = function (inputName) {
        // 구매불가 상품 가격 미출력
        if (
            $('input[name="orderPossible"]').length &&
            $('input[name="orderPossible"]').val() === "n"
        ) {
            return false;
        }
        var frmId = "#" + $(inputName).closest("form").attr("id");

        if (setOptNo != "" && setOptNo != undefined) {
            if (
                $(
                    frmId + ' select[name="optionSnoInput' + setOptNo + '"]'
                ).val() == "0"
            ) {
                $("[id^=option_display_item_" + setOptNo + "] button").prop(
                    "disabled",
                    true
                );
                $("[id^=option_display_item_" + setOptNo + "] input").prop(
                    "disabled",
                    true
                );
                $(
                    "#relateGoodsList input[name*='optionSno" +
                        setOptNo +
                        "[]']"
                ).remove();
                return;
            }
        }

        if (setOptionTextFl == "y") {
            if (!this.option_text_valid(frmId)) {
                if (setOptNo == "" || setOptNo == undefined) {
                    if (setOptionDisplayFl == "s") {
                        $(
                            frmId +
                                ' select[name="optionSnoInput' +
                                setOptNo +
                                '"]'
                        ).val("");
                    } else {
                        $(frmId + ' select[name*="optionNo_"]').val("");
                        $(frmId + ' select[name*="optionNo_"]').trigger(
                            "chosen:updated"
                        );
                    }

                    alert(
                        __(
                            "%1$s선택한 옵션의 필수 텍스트 옵션 내용을 먼저 입력해주세요.",
                            setGoodsNm
                        )
                    );
                    return false;
                }
            }
            $(frmId + ' input[name*="optionTextInput' + setOptNo + '"]').val(
                ""
            );
        }

        if (setAddGoodsFl == "y") {
            if (!this.add_goods_valid(frmId)) {
                if (setOptNo == "" || setOptNo == undefined) {
                    if (setOptionDisplayFl == "s") {
                        $(
                            frmId +
                                ' select[name="optionSnoInput' +
                                setOptNo +
                                '"]'
                        ).val("");
                    } else {
                        $(frmId + ' select[name*="optionNo_"]').val("");
                        $(frmId + ' select[name*="optionNo_"]').trigger(
                            "chosen:updated"
                        );
                    }

                    alert(
                        __(
                            "%1$s선택한 옵션의 필수 추가 상품 먼저 선택해주세요.",
                            setGoodsNm
                        )
                    );
                    return false;
                }
            }
        }

        if (
            $(frmId + " input[name='selectGoodsFl']").length &&
            $(frmId + " input[name='selectGoodsFl']").val()
        ) {
            $(frmId + " table.option_display_area tbody").remove();
        }

        if (setOptionDisplayFl == "s") {
            if (
                $(
                    frmId +
                        ' select[name="optionSnoInput' +
                        setOptNo +
                        '"] option:selected'
                ).val() != ""
            ) {
                var valTmp = $(
                    frmId +
                        ' select[name="optionSnoInput' +
                        setOptNo +
                        '"] option:selected'
                ).val();
                if (setOptNo == "")
                    $(frmId + ' select[name="optionSnoInput' + setOptNo + '"]')
                        .val("")
                        .trigger("chosen:updated");
            }
        } else if (setOptionDisplayFl == "d") {
            var valTmp = $(
                frmId + ' input[name="optionSnoInput' + setOptNo + '"]'
            ).val();
            $(frmId + ' select[name*="optionNo_"]').val("");
            $(frmId + ' select[name*="optionNo_"]')
                .not(":eq(0)")
                .attr("disabled", true);
            $(frmId + ' select[name*="optionNo_"]').trigger("chosen:updated");
        }

        if (typeof valTmp == "undefined") return false;

        var arrTmp = new Array();
        var arrTmp = valTmp.split(setStrDivision);
        var optionName = arrTmp[1].trim();
        var optionInput = arrTmp[0];
        var optionSellCodeValue = arrTmp[2];
        var optionDeliveryCodeValue = arrTmp[3];
        var arrTmp = optionInput.split(setIntDivision);

        if (optionSellCodeValue != "" && optionSellCodeValue != undefined) {
            optionSellCodeValue = "[" + optionSellCodeValue + "]";
        }
        if (
            optionDeliveryCodeValue != "" &&
            optionDeliveryCodeValue != undefined
        ) {
            optionDeliveryCodeValue = "[" + optionDeliveryCodeValue + "]";
        }

        if (setMileageUseFl == "y" && arrTmp[2]) {
            $(frmId + ' input[name="set_goods_mileage' + setOptNo + '"]').val(
                parseFloat(arrTmp[2].trim())
            );
        }

        if (arrTmp[3]) {
            $(frmId + ' input[name="set_goods_stock' + setOptNo + '"]').val(
                parseFloat(arrTmp[3].trim())
            );
        }

        var optionPrice = arrTmp[1].trim();
        var optionStock = parseFloat(arrTmp[3].trim());
        var displayOptionkey = arrTmp[0] + "_" + $.now();

        amplitude.logEvent('product_option_select', {title: '옵션', value: optionName });

        if (
            $(frmId + " tr.optionKey" + setOptNo + "_" + arrTmp[0]).length &&
            (setOptNo == "" || setOptNo == undefined) &&
            setOptionTextFl != "y"
        ) {
            alert(__("이미 선택된 옵션입니다"));
            return false;
        } else {
            var addHtml = "";
            var complied = _.template(
                $("#optionTemplate" + setTemplate).html()
            );
            if (setOptNo != "" && setOptNo != undefined) {
                complied = _.template(
                    $("#optionTemplateRelated" + setOptNo + setTemplate).html()
                );
            }
            addHtml += complied({
                displayOptionkey: displayOptionkey,
                optionSno: arrTmp[0],
                optionName: optionName,
                optionPrice: optionPrice,
                optionStock: optionStock,
                optionSellCodeValue: optionSellCodeValue,
                optionDeliveryCodeValue: optionDeliveryCodeValue,
            });

            if (setOptNo != "" && setOptNo != undefined) {
                $("[id^=option_display_item_" + setOptNo + "]").remove();
            }

            $(frmId + " table.option_display_area" + setOptNo).append(addHtml);

            // 상품 옵션가 표시 설정
            if (setOptionPriceFl == "y" && optionPrice) {
                if (optionPrice > 0) var addPlus = "+";
                else var addPlus = "";

                var optionDisplayTextPrice =
                    " (" +
                    addPlus +
                    gdCurrencySymbol +
                    gd_money_format(optionPrice) +
                    gdCurrencyString +
                    ")";
                $(
                    "[id^=option_display_item_" +
                        displayOptionkey +
                        "] .cart_tit > span"
                )
                    .eq(0)
                    .append(optionDisplayTextPrice);
            }

            $(
                frmId +
                    " tbody#option_display_item_" +
                    displayOptionkey +
                    " tr.optionKey" +
                    setOptNo +
                    "_" +
                    arrTmp[0] +
                    " .goods_cnt"
            ).on("click", function () {
                setControllerName.count_change(this, 1);
            });

            $(
                frmId +
                    " tr.optionKey" +
                    setOptNo +
                    "_" +
                    arrTmp[0] +
                    " button.delete_goods"
            ).on("click", function () {
                setControllerName.remove_option($(this).data("key"));
            });

            if (setCartTabFl == "y") {
                var addHtml = "";
                var complied = _.template($("#optionTemplateCartTab").html());
                addHtml += complied({
                    displayOptionkey: displayOptionkey,
                    optionSno: arrTmp[0],
                    optionName: optionName,
                    optionPrice: optionPrice,
                    optionStock: optionStock,
                    optionSellCodeValue: optionSellCodeValue,
                    optionDeliveryCodeValue: optionDeliveryCodeValue,
                });

                $("#frmCartTabViewLayer table.option_display_area").append(
                    addHtml
                );

                // 상품 옵션가 표시 설정
                if (setOptionPriceFl == "y" && optionPrice) {
                    if (optionPrice > 0) var addPlus = "+";
                    else var addPlus = "";

                    var optionDisplayTextPrice =
                        " (" +
                        addPlus +
                        gdCurrencySymbol +
                        gd_money_format(optionPrice) +
                        gdCurrencyString +
                        ")";
                    $(
                        "#frmCartTabViewLayer tr.optionKey" +
                            setOptNo +
                            "_" +
                            arrTmp[0] +
                            " .cart_tit > span"
                    )
                        .eq(0)
                        .append(optionDisplayTextPrice);
                }

                $(
                    "#frmCartTabViewLayer tr.optionKey" +
                        setOptNo +
                        "_" +
                        arrTmp[0] +
                        " .goods_cnt"
                ).on("click", function () {
                    var datakey = $(this).val().split(setStrDivision);
                    $("#option_display_item_" + datakey[1])
                        .find('button[class="' + $(this).attr("class") + '"]')
                        .click();
                });

                $(
                    "#frmCartTabViewLayer tr.optionKey" +
                        setOptNo +
                        "_" +
                        arrTmp[0] +
                        " button.delete_goods"
                ).on("click", function () {
                    $(
                        "#frmView #" +
                            $(this).data("key") +
                            " button.delete_goods"
                    ).click();
                });

                $("#frmCartTabViewLayer div.option_total_display_area").show();
            }

            this.goods_calculate(frmId, 1, displayOptionkey, setMinOrderCnt);
            if (setCouponUseFl == "y") {
                if (
                    typeof gd_open_layer !== "undefined" &&
                    $.isFunction(gd_open_layer)
                ) {
                    gd_open_layer();
                }
            }

            $(frmId + " div.option_total_display_area").show();
            $(frmId + " div.end_price").show();
        }
    };

    this.option_select = function (inputName, thisKey, nextVal, stockViewFl) {
        var frmId = "#" + $(inputName).closest("form").attr("id");

        // 무한정 판매 여부
        var stockFl = setStockFl;

        // 옵션의 개수
        var optionCnt = $(
            frmId + ' input[name="optionCntInput' + setOptNo + '"]'
        ).val();

        // 옵션 가격 출력 여부
        var optionPriceFl = "y";

        // 옵션 가격이 다른 경우 출력 여부
        var optionPriceDiffFl = "y";

        // 기본 상품 가격
        var defaultGoodsPrice = parseFloat(setGoodsPrice);

        // 선택된 옵션
        var optionVal = new Array();
        for (var i = 0; i <= thisKey; i++) {
            optionVal[i] = $(
                frmId + ' select[name="optionNo' + setOptNo + "_" + i + '"]'
            ).val();
            // 선택값이 없는경우 disabled 처리
            if (optionVal[i] == "") {
                for (var j = i + 1; j <= optionCnt; j++) {
                    if (j != 0) {
                        $(
                            frmId +
                                ' select[name="optionNo' +
                                setOptNo +
                                "_" +
                                j +
                                '"]'
                        ).attr("disabled", true);
                        if (setOptNo != "" && setOptNo != undefined) {
                            $(
                                frmId +
                                    ' select[name="optionNo' +
                                    setOptNo +
                                    "_" +
                                    j +
                                    '"]'
                            ).val("");
                            $(
                                frmId +
                                    ' select[name="optionNo' +
                                    setOptNo +
                                    "_" +
                                    j +
                                    '"]'
                            ).trigger("chosen:updated");
                            $(
                                "[id^=option_display_item_" +
                                    setOptNo +
                                    "] button"
                            ).prop("disabled", true);
                            $(
                                "[id^=option_display_item_" +
                                    setOptNo +
                                    "] input"
                            ).prop("disabled", true);
                            $(
                                "#relateGoodsList input[name*='optionSno" +
                                    setOptNo +
                                    "[]']"
                            ).remove();
                        }
                    }
                }
                return true;
            }
        }

        $.post(
            "../goods/goods_ps.php",
            {
                mode: "option_select",
                optionVal: optionVal,
                optionKey: thisKey,
                goodsNo: setGoodsNo,
                mileageFl: setMileageFl,
            },
            function (data) {
                if (typeof data.optionSno == "string") {
                    //분리형 옵션 - 타임세일 할인율 적용된 옵션가격 설정.
                    var optionInfo = data.optionSno.split("||");
                    optionInfo[1] = data.optionPrice[0];
                    var optionSno = optionInfo.join("||");

                    $(
                        frmId + " input[name='optionSnoInput" + setOptNo + "']"
                    ).val(
                        optionSno +
                            setStrDivision +
                            optionVal.join("/") +
                            setStrDivision +
                            data.stockCodeValue +
                            setStrDivision +
                            data.deliveryCodeValue
                    );

                    if ($(frmId).data("form") == "cart") {
                        gd_carttab_option_price_display();
                    } else {
                        setControllerName.option_price_display(
                            $(
                                frmId +
                                    " input[name='optionSnoInput" +
                                    setOptNo +
                                    "']"
                            )
                        );
                    }
                    return true;
                } else {
                    if (setOptNo != "" && setOptNo != undefined) {
                        $(
                            'select[name="optionSnoInputCnt' + setOptNo + '"]'
                        ).prop("disabled", false);
                        $(
                            'select[name="optionSnoInputCnt' + setOptNo + '"]'
                        ).trigger("chosen:updated");
                    }
                    $(
                        frmId + " input[name='optionSnoInput" + setOptNo + "']"
                    ).val("");
                }

                for (var i = 0; i <= optionCnt; i++) {
                    if (i <= data.nextKey) {
                        $(
                            frmId +
                                ' select[name="optionNo' +
                                setOptNo +
                                "_" +
                                i +
                                '"]'
                        ).attr("disabled", false);
                        if (setOptNo != "" && setOptNo != undefined) {
                            $(
                                "[id^=option_display_item_" +
                                    setOptNo +
                                    "] button"
                            ).prop("disabled", true);
                            $(
                                "[id^=option_display_item_" +
                                    setOptNo +
                                    "] input"
                            ).prop("disabled", true);
                            $(
                                "#relateGoodsList input[name*='optionSno" +
                                    setOptNo +
                                    "[]']"
                            ).remove();
                        }
                        if (i == data.nextKey) {
                            $(
                                frmId +
                                    ' select[name="optionNo' +
                                    setOptNo +
                                    "_" +
                                    i +
                                    '"]'
                            ).html("");
                            var addSelectOption = "";
                            for (var j = 0; j < data.nextOption.length; j++) {
                                if (data.optionViewFl[j] == "y") {
                                    if (j == 0) {
                                        // 옵션 선택명
                                        addSelectOption +=
                                            '<option value="">= ' +
                                            nextVal +
                                            " " +
                                            __("선택");

                                        // 마지막 옵션의 경우 가격, 재고 출력
                                        if (
                                            parseInt(data.nextKey) + 1 ==
                                            parseInt(optionCnt)
                                        ) {
                                            if (
                                                optionPriceFl == "y" &&
                                                optionPriceDiffFl == "y"
                                            ) {
                                                addSelectOption +=
                                                    " : " + __("가격");
                                            }
                                            if (
                                                stockFl == "y" &&
                                                stockViewFl == "y"
                                            ) {
                                                addSelectOption +=
                                                    " : " + __("재고");
                                            }
                                        }

                                        addSelectOption += " =</option>";
                                    }

                                    // 옵션값
                                    addSelectOption +=
                                        '<option value="' +
                                        data.nextOption[j] +
                                        '"';

                                    //다중 선택형 이라면
                                    if (
                                        setOptNo != "" &&
                                        setOptNo != undefined
                                    ) {
                                        addSelectOption +=
                                            ' style="white-space:nowrap; overflow-y: hidden; overflow-x:visible;" ';
                                    }

                                    // 재고 체크 (품절이면 disabled 처리) : 분리형 옵션 Disabled 표기 여부가 't'인 경우
                                    if (
                                        data.optionDivisionDisabledMark ==
                                            "t" &&
                                        ((stockFl == "y" &&
                                            setStockCnt <
                                                setOriginalMinOrderCnt) ||
                                            (stockFl == "y" &&
                                                setFixedOrderCnt == "option" &&
                                                data.stockCnt[j] <
                                                    setMinOrderCnt) ||
                                            (stockFl == "y" &&
                                                data.stockCnt[j] == 0) ||
                                            data.optionSellFl[j] == "n" ||
                                            data.optionSellFl[j] == "t")
                                    ) {
                                        addSelectOption +=
                                            ' disabled="disabled"';
                                    }
                                    // 마지막 옵션의 경우 재고 체크 (품절이면 disabled 처리) : 구버전 레거시 조건
                                    else if (
                                        parseInt(data.nextKey) + 1 ==
                                            parseInt(optionCnt) &&
                                        ((stockFl == "y" &&
                                            setStockCnt <
                                                setOriginalMinOrderCnt) ||
                                            (stockFl == "y" &&
                                                setFixedOrderCnt == "option" &&
                                                data.stockCnt[j] <
                                                    setMinOrderCnt) ||
                                            (stockFl == "y" &&
                                                data.stockCnt[j] == 0) ||
                                            data.optionSellFl[j] == "n" ||
                                            data.optionSellFl[j] == "t")
                                    ) {
                                        addSelectOption +=
                                            ' disabled="disabled"';
                                    }

                                    // 옵션값
                                    addSelectOption += ">" + data.nextOption[j];

                                    // 마지막 옵션의 경우 재고 체크 및 가격
                                    if (
                                        parseInt(data.nextKey) + 1 ==
                                        parseInt(optionCnt)
                                    ) {
                                        // 가격 출력여부
                                        if (
                                            parseInt(data.optionPrice[j]) != "0"
                                        ) {
                                            if (data.optionPrice[j] > 0) {
                                                var addPlus = "+";
                                            } else {
                                                var addPlus = "";
                                            }

                                            if (
                                                optionPriceFl == "y" &&
                                                optionPriceDiffFl == "y"
                                            ) {
                                                addSelectOption +=
                                                    " : " +
                                                    addPlus +
                                                    gd_money_format(
                                                        data.optionPrice[
                                                            j
                                                        ].toString()
                                                    ) +
                                                    "";
                                            } else if (
                                                optionPriceFl == "y" &&
                                                optionPriceDiffFl == "n" &&
                                                defaultGoodsPrice !=
                                                    parseFloat(
                                                        data.optionPrice[j]
                                                    )
                                            ) {
                                                addSelectOption +=
                                                    " (" +
                                                    addPlus +
                                                    gd_money_format(
                                                        data.optionPrice[
                                                            j
                                                        ].toString()
                                                    ) +
                                                    ")";
                                            }
                                        }

                                        // 재고 체크
                                        if (
                                            typeof data.stockCodeValue !==
                                                "undefined" &&
                                            data.optionSellFl[j] == "t" &&
                                            data.stockCodeValue[j] != "" &&
                                            data.stockCodeValue[j] != null
                                        ) {
                                            addSelectOption +=
                                                " [" +
                                                data.stockCodeValue[j] +
                                                "]";
                                        } else if (
                                            typeof data.stockCodeValue !==
                                                "undefined" &&
                                            ((stockFl == "y" &&
                                                data.stockCnt[j] == 0) ||
                                                data.optionSellFl[j] == "n" ||
                                                (data.sellStopFl == "y" &&
                                                    dta.sellStopStock >=
                                                        data.stockCnt[j]))
                                        ) {
                                            addSelectOption +=
                                                " [" +
                                                data.stockCodeValue["n"] +
                                                "]";
                                        } else if (
                                            stockFl == "y" &&
                                            stockViewFl == "y"
                                        ) {
                                            addSelectOption +=
                                                " : " +
                                                numeral(
                                                    data.stockCnt[j]
                                                ).format() +
                                                __("개");
                                        }

                                        // 배송 상태 설정
                                        if (
                                            typeof data.deliveryCodeValue !==
                                                "undefined" &&
                                            data.deliveryCodeValue[j] != "" &&
                                            data.deliveryCodeValue[j] != null
                                        ) {
                                            addSelectOption +=
                                                "[" +
                                                data.deliveryCodeValue[j] +
                                                "]";
                                        }
                                    } else if (
                                        data.optionDivisionDisabledMark == "t"
                                    ) {
                                        // 분리형 옵션 Disabled 표기 여부가 't'인 경우
                                        // 재고 체크
                                        if (
                                            typeof data.stockCodeValue !==
                                                "undefined" &&
                                            data.optionSellFl[j] == "t" &&
                                            data.stockCodeValue[j] != "" &&
                                            data.stockCodeValue[j] != null
                                        ) {
                                            addSelectOption +=
                                                " [" +
                                                data.stockCodeValue[j] +
                                                "]";
                                        } else if (
                                            typeof data.stockCodeValue !==
                                                "undefined" &&
                                            ((stockFl == "y" &&
                                                data.stockCnt[j] == 0) ||
                                                data.optionSellFl[j] == "n")
                                        ) {
                                            addSelectOption +=
                                                " [" +
                                                data.stockCodeValue["n"] +
                                                "]";
                                        }
                                    }

                                    // 옵션값
                                    addSelectOption += "</option>";
                                }
                            }

                            $(
                                frmId +
                                    ' select[name="optionNo' +
                                    setOptNo +
                                    "_" +
                                    i +
                                    '"]'
                            ).html(addSelectOption);
                        }
                    } else {
                        if (i != 0) {
                            $(
                                frmId +
                                    ' select[name="optionNo' +
                                    setOptNo +
                                    "_" +
                                    i +
                                    '"]'
                            ).attr("disabled", true);
                        }
                    }

                    $(
                        frmId +
                            ' select[name="optionNo' +
                            setOptNo +
                            "_" +
                            i +
                            '"]'
                    ).trigger("chosen:updated");
                }
            },
            "json"
        );
    };

    /**
     * 옵션 삭제
     *
     * @param optionId 삭제 옵션 아이디
     */
    this.remove_option = function (optionId) {
        var frmId =
            "#" +
            $("#" + optionId)
                .closest("form")
                .attr("id");

        $("#" + optionId).remove();
        if (
            typeof gd_total_calculate !== "undefined" &&
            $.isFunction(gd_total_calculate)
        ) {
            gd_total_calculate();
        }

        var optionCnt = $("[id*='option_display_item_']").length;
        if (optionCnt == 0) {
            $(frmId + " div.option_total_display_area").hide();
            $(frmId + " div.end_price").hide();
        }

        if (setCartTabFl == "y") {
            $("#frmCartTabViewLayer ." + optionId).remove();
        }

        // 옵션을 여러개 생성 후 가운데 옵션을 삭제 했을 때 배열 순서 재정의
        if (setOptionTextFl == "y") {
            $(
                frmId + " [id*='option_display_item_" + setOptNo + "']"
            ).each(function (key, div) {
                $(div)
                    .find("input[name*='optionText[']")
                    .each(function (_, input) {
                        var newName = $(input)
                            .attr("name")
                            .replace(/\[(\s*?.*?)*?\]/, "[" + key + "]");
                        $(input).attr("name", newName);
                    });
            });
        }
    };

    /**
     * 텍스트 옵션 선택
     */
    this.option_text_select = function (inputName) {
        var frmId = "#" + $(inputName).closest("form").attr("id");
        var optionText = "";
        var optionTextPrice = 0;
        var optionTextTotalPrice = 0;
        var optionTextSno = "";
        var optionTextKey = "";

        var displayOptionDisplay = $(
            frmId + " [id*='option_display_item_" + setOptNo + "']"
        )
            .last()
            .attr("id");

        if (displayOptionDisplay) {
            if (setOptNo != "" && setOptNo != undefined) {
                if (
                    $(frmId + " #" + displayOptionDisplay + " tr.check")
                        .length == 0
                ) {
                    displayOptionDisplay = displayOptionDisplay + "_0";
                }
            }

            var checkKey = $(frmId + " #" + displayOptionDisplay + " tr.check")
                .attr("class")
                .replace("check", "")
                .trim();
            var displayOptionkey = displayOptionDisplay.replace(
                "option_display_item_",
                ""
            );

            //if (setOptionFl == 'y') {
            var optionItemNo =
                $(frmId + " [id*='option_display_item_']").length - 1;
            //} else {
            //    var optionItemNo = 0;
            //}

            if (
                $(
                    frmId + " .option_text_display_" + displayOptionkey
                ).html() !== "" &&
                setTemplate != "Layer"
            ) {
                var optionTextInputCnt = $(
                    frmId + " input[name*='optionTextInput_']"
                ).length;
                var emCnt = $(frmId + " [id*='option_display_item_']")
                    .last()
                    .find("em").length;
                if (optionTextInputCnt <= emCnt) {
                    optionItemNo++;
                    var addHtml = "";
                    var complied = _.template(
                        $("#optionTemplate" + setTemplate).html()
                    );
                    var $lastDiv = $(
                        frmId +
                            " [id*='option_display_item_" +
                            setOptNo +
                            "']"
                    ).last();
                    var optionSno = $lastDiv
                        .find("input[name='optionSno[]']")
                        .val();
                    var optionName = $lastDiv
                        .find(".cart_tit span")
                        .first()
                        .text();
                    var optionPrice = $lastDiv
                        .find("input[name*='option_price_']")
                        .last()
                        .val();
                    var optionStock = $lastDiv
                        .find("input[name*='goodsCnt']")
                        .attr("data-stock");
                    var optionSellCodeValue = "";
                    var optionDeliveryCodeValue = "";

                    displayOptionkey = optionSno + "_" + $.now();
                    addHtml += complied({
                        displayOptionkey: displayOptionkey,
                        optionSno: optionSno,
                        optionName: optionName,
                        optionPrice: optionPrice,
                        optionStock: optionStock,
                        optionSellCodeValue: optionSellCodeValue,
                        optionDeliveryCodeValue: optionDeliveryCodeValue,
                    });
                    $(frmId + " table.option_display_area").append(addHtml);

                    $(
                        frmId +
                            " #option_display_item_" +
                            displayOptionkey +
                            "  .goods_cnt"
                    ).on("click", function (e) {
                        setControllerName.count_change(this, 1);
                    });

                    $(
                        frmId +
                            " #option_display_item_" +
                            displayOptionkey +
                            "  button.delete_goods"
                    ).on("click", function (e) {
                        setControllerName.remove_option($(this).data("key"));
                    });

                    this.goods_calculate(
                        frmId,
                        1,
                        displayOptionkey,
                        setMinOrderCnt
                    );
                    if (setCouponUseFl == "y") {
                        if (
                            typeof gd_open_layer !== "undefined" &&
                            $.isFunction(gd_open_layer)
                        ) {
                            gd_open_layer();
                        }
                    }

                    if (setCartTabFl == "y") {
                        var addHtml = "";
                        var complied = _.template(
                            $("#optionTemplateCartTab").html()
                        );
                        addHtml += complied({
                            displayOptionkey: displayOptionkey,
                            optionSno: optionSno,
                            optionName: optionName,
                            optionPrice: optionPrice,
                            optionStock: optionStock,
                            optionSellCodeValue: optionSellCodeValue,
                            optionDeliveryCodeValue: optionDeliveryCodeValue,
                        });

                        $(
                            "#frmCartTabViewLayer table.option_display_area"
                        ).append(addHtml);

                        // 상품 옵션가 표시 설정
                        if (setOptionPriceFl == "y" && optionPrice) {
                            if (optionPrice > 0) var addPlus = "+";
                            else var addPlus = "";

                            var optionDisplayTextPrice =
                                " (" +
                                addPlus +
                                gdCurrencySymbol +
                                gd_money_format(optionPrice) +
                                gdCurrencyString +
                                ")";
                            $(
                                "#frmCartTabViewLayer tr.optionKey" +
                                    setOptNo +
                                    "_" +
                                    optionSno +
                                    " .cart_tit > span"
                            )
                                .eq(0)
                                .append(optionDisplayTextPrice);
                        }

                        $(
                            "#frmCartTabViewLayer tr.optionKey" +
                                setOptNo +
                                "_" +
                                optionSno +
                                " .goods_cnt"
                        ).on("click", function () {
                            var datakey = $(this).val().split(setStrDivision);
                            $("#option_display_item_" + datakey[1])
                                .find(
                                    'button[class="' +
                                        $(this).attr("class") +
                                        '"]'
                                )
                                .click();
                        });

                        $(
                            "#frmCartTabViewLayer tr.optionKey" +
                                setOptNo +
                                "_" +
                                optionSno +
                                " button.delete_goods"
                        ).on("click", function () {
                            $(
                                "#frmView #" +
                                    $(this).data("key") +
                                    " button.delete_goods"
                            ).click();
                        });

                        $(
                            "#frmCartTabViewLayer div.option_total_display_area"
                        ).show();
                    }
                }
            }

            $(frmId + " input[name*='optionTextInput" + setOptNo + "']").each(
                function (key) {
                    if ($(this).val()) {
                        var optionTextLimit = $(
                            frmId +
                                ' input[name="optionTextLimit' +
                                setOptNo +
                                "_" +
                                key +
                                '"]'
                        ).val();
                        if ($(this).val().length > optionTextLimit) {
                            $(this).val(
                                $(this).val().substring(0, optionTextLimit)
                            );
                        }

                        var optionValue = $(this)
                            .val()
                            .replace(/'/g, "&#39;")
                            .replace(/"/g, "&quot;");
                        optionTextPrice = parseFloat(
                            $(this).next("input").val()
                        );
                        optionTextSno +=
                            '<input type="hidden"  value="' +
                            optionValue +
                            '" name="optionText' +
                            setOptNo +
                            "[" +
                            optionItemNo +
                            "][" +
                            $(this).prev("input").val() +
                            ']">';
                        var optionTextNm = $(
                            frmId + " span.optionTextNm_" + key
                        ).text();
                        var optionDisplayText =
                            optionTextNm + " : " + optionValue;

                        // 상품 옵션가 표시 설정
                        if (setOptionPriceFl == "y") {
                            if (optionTextPrice > 0) var addPlus = "+";
                            else var addPlus = "";
                            var optionDisplayTextPrice =
                                " <b>(" +
                                addPlus +
                                gdCurrencySymbol +
                                gd_money_format(optionTextPrice) +
                                gdCurrencyString +
                                ")</b>";
                            optionDisplayText =
                                optionDisplayText + optionDisplayTextPrice;
                        }

                        optionTextSno +=
                            '<em class="text_type_cont">' +
                            optionDisplayText +
                            "</em>";

                        for (var i = 0, m = optionValue.length; i < m; i++) {
                            optionTextKey += optionValue.charCodeAt(i);
                        }
                    } else {
                        optionTextSno += "";
                        optionTextPrice = 0;
                    }

                    optionTextTotalPrice += optionTextPrice;
                }
            );

            var tmpStr = displayOptionkey.split("_");

            if (
                $(
                    frmId +
                        " tr.optionKey" +
                        setOptNo +
                        "_" +
                        tmpStr[0] +
                        optionTextKey
                ).length
            ) {
                alert(__("이미 선택된 옵션입니다"));
                return false;
            } else {
                /*if (optionTextKey) {
                    $('#' + displayOptionDisplay + ' div.' + checkKey +' .name strong').addClass('btm-txt');
                } else {
                    $('#' + displayOptionDisplay + ' div.' + checkKey + ' .name strong').removeClass('btm-txt');
                }*/

                optionText =
                    optionTextSno +
                    '<input type="hidden" value="' +
                    optionTextTotalPrice +
                    '" name="optionTextPriceSum' +
                    setOptNo +
                    '[]" /><input type="hidden" value="' +
                    optionTextTotalPrice +
                    '" name="option_text_price_' +
                    displayOptionkey +
                    '" /></div>';

                $(
                    "#option_text_display" + setOptNo + "_" + displayOptionkey
                ).html(optionText);
                if (setOptNo != "" && setOptNo != undefined) {
                    $('span[id*="option_text_display' + setOptNo + '"]').html(
                        optionText
                    );
                } else {
                    $("#" + displayOptionDisplay + " div." + checkKey).attr(
                        "class",
                        "check optionKey_" + tmpStr[0] + optionTextKey
                    );
                }
                $("#" + displayOptionDisplay + " tbody." + checkKey).attr(
                    "class",
                    "check optionKey" +
                        setOptNo +
                        "_" +
                        tmpStr[0] +
                        optionTextKey
                );

                if (setCartTabFl == "y") {
                    $(
                        "#frmCartTabViewLayer .option_text_display_" +
                            displayOptionkey
                    ).html(optionText);
                    $(
                        "#frmCartTabViewLayer ." +
                            displayOptionDisplay +
                            " tbody." +
                            checkKey
                    ).attr(
                        "class",
                        "this optionKey" +
                            setOptNo +
                            "_" +
                            tmpStr[0] +
                            optionTextKey
                    );
                }

                var goodsCnt = $(
                    frmId + " input.goodsCnt_" + displayOptionkey
                ).val();
                this.goods_calculate(frmId, 1, displayOptionkey, goodsCnt);
            }
        } else {
            alert(__("옵션을 먼저 선택해주세요"));
            $(frmId + " input[name*='optionTextInput']").val("");
            return false;
        }
    };

    /**
     * 텍스트 옵션 유효성체크
     */
    this.option_text_valid = function (frmId) {
        if ($(frmId + ' input[name="optionSno' + setOptNo + '[]"]').length) {
            var returnFl = true;

            $(frmId + ' input[name="optionSno' + setOptNo + '[]"]').each(
                function (key) {
                    $(
                        frmId +
                            " input[name*='optionTextInput" +
                            setOptNo +
                            "']"
                    ).each(function (textKey) {
                        var optionTextSno = $(
                            frmId +
                                ' input[name="optionTextSno' +
                                setOptNo +
                                "_" +
                                textKey +
                                '"]'
                        ).val();
                        var optionText = $(
                            frmId +
                                ' input[name="optionText' +
                                setOptNo +
                                "[" +
                                key +
                                "][" +
                                optionTextSno +
                                ']"]'
                        );
                        if (setOptNo != "" && setOptNo != undefined) {
                            optionText = $(
                                frmId +
                                    ' input[name*="optionText' +
                                    setOptNo +
                                    '"]'
                            );
                        }

                        if (
                            $(
                                frmId +
                                    ' input[name="optionTextMust' +
                                    setOptNo +
                                    "_" +
                                    textKey +
                                    '"]'
                            ).val() == "y"
                        ) {
                            if (optionText.length == "0") {
                                $(
                                    frmId +
                                        ' input[name="optionTextInput' +
                                        setOptNo +
                                        "_" +
                                        textKey +
                                        '"]'
                                ).focus();
                                returnFl = false;
                            }
                        }
                    });
                }
            );
            return returnFl;
        } else return true;
    };

    /**
     * 텍스트 옵션 재고 체크
     */
    this.option_text_cnt_valid = function (frmId) {
        var checkOption = [];
        var checkOptionCnt = false;
        $(frmId + ' input[name*="goodsCnt[]"]').each(function (index) {
            var stock = $(this).data("stock");
            var _key = 0;
            if (setOptionFl == "y") {
                _key = $(this).data("key");
                _key = _key.split("_");
                _key = _key[0];
            }
            if (checkOption[_key] > 0)
                checkOption[_key] =
                    parseFloat(checkOption[_key]) + parseFloat($(this).val());
            else checkOption[_key] = parseFloat($(this).val());
            if (checkOption[_key] > stock) {
                checkOptionCnt = stock;
                return true;
            }
        });
        return checkOptionCnt;
    };

    this.add_goods_select = function (inputName, count = 1, forcibly = false) {
        var frmId = "#" + $(inputName).closest("form").attr("id");
        var selAddGoods = $(inputName).data("key");

        if ($(frmId + " select[name='addGoodsInput" + setOptNo + selAddGoods + "']").val() != "" ) {
            var displayOptionDisplay = $(frmId + " [id*='option_display_item_" + setOptNo + "']").last().attr("id");

            if (displayOptionDisplay) {
                var displayOptionkey = displayOptionDisplay.replace("option_display_item_", "");
                if (setOptNo != "" && setOptNo != undefined) {
                    var tmp = displayOptionkey.split("_");
                    displayOptionkey = tmp[0] + "_" + selAddGoods;
                }
                var addGoods = $(frmId + " select[name='addGoodsInput" + setOptNo + selAddGoods + "']");

                if (addGoods.val() == "0") {
                    $("input[name^='addGoodsNo" + setOptNo + "']").remove();
                    $("[id^=add_goods_display_item_" + displayOptionkey + '] input[type="text"]').attr("disabled", true);
                    $("[id^=add_goods_display_item_" + displayOptionkey + "] button").attr("disabled", true);
                    return;
                }
                var arrTmp = new Array();
                var arrTmp = addGoods.val().split(setStrDivision);
                var addGoodsName = arrTmp[1].trim();
                var addGoodsimge = decodeURIComponent(arrTmp[2].trim());
                var addGoodsGroup = arrTmp[3].trim();
                var addGoodsValue = arrTmp[0];
                var addGoodsStockFl = arrTmp[4];
                var addGoodsStock = arrTmp[5];
                var arrTmp = addGoodsValue.split(setIntDivision);
                var displayAddGoodsKey = arrTmp[0];
                const priceAddedGoodsName = addGoodsName.startsWith("선택 메뉴 추가 금액") ? this.generatePriceAddedGoodsName() : null;

                // 골라담기 구성품일 경우 optionIndex
                var optionIndex = $(frmId + " #" + displayOptionDisplay).siblings("[id*='option_display_item_']").index();

                if(optionIndex == -1) {
                    // 골라담기가 아닐 경우 옵션의 optionIndex
                    optionIndex = $("#" + displayOptionDisplay).index('tbody');
                }

                if (setOptNo != "" && setOptNo != undefined) {
                    optionIndex = 0;
                }

                if ($(frmId + " #add_goods_display_item_" + displayOptionkey + "_" + displayAddGoodsKey).length &&
                    (setOptNo == "" || setOptNo == undefined)) 
                {
                    if (forcibly) {
                        setControllerName.remove_add_goods(
                            displayOptionkey,
                            displayAddGoodsKey
                        );
                    } else {
                        alert(__("이미 선택된 추가상품 입니다."));
                        return false;
                    }
                } 

                if (count == 0) {
                    setControllerName.remove_add_goods(
                        displayOptionkey,
                        displayAddGoodsKey
                    );
                    return true;
                }

                var addHtml = "";
                var complied = _.template(
                    $("#addGoodsTemplate" + setTemplate).html()
                );
                if (setOptNo != "" && setOptNo != undefined) {
                    complied = _.template(
                        $(
                            "#addGoodsTemplateRelated" +
                                setOptNo +
                                setTemplate
                        ).html()
                    );
                }
                addHtml += complied({
                    displayOptionkey: displayOptionkey,
                    displayAddGoodsKey: displayAddGoodsKey,
                    addGoodsimge: addGoodsimge,
                    addGoodsGroup: addGoodsGroup,
                    optionIndex: optionIndex,
                    optionSno: arrTmp[0],
                    addGoodsName: addGoodsName,
                    addGoodsStockFl: addGoodsStockFl,
                    addGoodsStock: addGoodsStock,
                    addGoodsPrice: parseFloat(arrTmp[1].trim()),
                    count: count,
                    priceAddedGoodsName: priceAddedGoodsName,
                });

                if (setOptNo != "" && setOptNo != undefined) {
                    $(
                        "[id^=add_goods_display_item_" +
                            displayOptionkey +
                            "]"
                    ).remove();
                    $(
                        "[id^=add_goods_display_area_" +
                            displayOptionkey +
                            "]"
                    ).append(addHtml);
                } else {
                    $(frmId + " #option_display_item_" + displayOptionkey).append(addHtml);
                }

                // 추가가격은 수량 변경 등 못하게 disable 시킴
                if(forcibly) {
                    $(
                        frmId +
                            " #add_goods_display_item_" +
                            displayOptionkey +
                            "_" +
                            displayAddGoodsKey
                    ).addClass('disabled_add_goods');
                }

                $(
                    frmId +
                        " #add_goods_display_item_" +
                        displayOptionkey +
                        "_" +
                        displayAddGoodsKey +
                        " .add_goods_cnt"
                ).on("click", function () {
                    setControllerName.count_change(this, 0);
                });

                $(
                    frmId +
                        " #add_goods_display_item_" +
                        displayOptionkey +
                        "_" +
                        displayAddGoodsKey +
                        " button.delete_add_goods"
                ).on("click", function () {
                    setControllerName.remove_add_goods(
                        displayOptionkey,
                        displayAddGoodsKey
                    );
                });

                if (setCartTabFl == "y") {
                    var addHtml = "";
                    var complied = _.template(
                        $("#addGoodsTemplateCartTab").html()
                    );
                    addHtml += complied({
                        displayOptionkey: displayOptionkey,
                        displayAddGoodsKey: displayAddGoodsKey,
                        addGoodsimge: addGoodsimge,
                        addGoodsGroup: addGoodsGroup,
                        optionIndex: optionIndex,
                        optionSno: arrTmp[0],
                        addGoodsName: addGoodsName,
                        addGoodsPrice: parseFloat(arrTmp[1].trim()),
                        count: count,
                        priceAddedGoodsName: priceAddedGoodsName,
                    });

                    $(
                        "#frmCartTabViewLayer .option_display_item_" +
                            displayOptionkey
                    ).append(addHtml);

                    $(
                        "#frmCartTabViewLayer .add_goods_display_item_" +
                            displayOptionkey +
                            "_" +
                            displayAddGoodsKey +
                            " .add_goods_cnt"
                    ).on("click", function () {
                        $(
                            "#add_goods_display_item_" +
                                displayOptionkey +
                                "_" +
                                displayAddGoodsKey
                        )
                            .find(
                                'button[class="' +
                                    $(this).attr("class") +
                                    '"]'
                            )
                            .click();
                    });

                    $(
                        "#frmCartTabViewLayer .add_goods_display_item_" +
                            displayOptionkey +
                            "_" +
                            displayAddGoodsKey +
                            " button.delete_add_goods"
                    ).on("click", function () {
                        $(
                            "#add_goods_display_item_" +
                                displayOptionkey +
                                "_" +
                                displayAddGoodsKey +
                                " button.delete_add_goods"
                        ).click();
                    });
                }

                var itemNo =
                    displayOptionkey + setIntDivision + displayAddGoodsKey;

                this.goods_calculate(frmId, 0, itemNo, count);

                if (setCouponUseFl == "y") {
                    if (
                        typeof gd_open_layer !== "undefined" &&
                        $.isFunction(gd_open_layer)
                    ) {
                        gd_open_layer();
                    }
                }

                addGoods.val("");
                
            } else {
                alert(__("옵션을 먼저 선택해주세요."));
                $(frmId + " select[name*='addGoodsInput']").val("");
                return false;
            }
        }
    };

    /**
     * 추가상품 유효성검사
     */
    this.add_goods_valid = function (frmId) {
        if (
            $(frmId + ' input[name="addGoodsInputMustFl' + setOptNo + '[]"]')
                .length
        ) {
            var checkMustCnt = $(
                frmId + ' input[name="addGoodsInputMustFl' + setOptNo + '[]"]'
            ).length;
            if (
                $(frmId + ' input[name="optionSno' + setOptNo + '[]"]').length
            ) {
                var totalOptionCnt = 0; // 총 옵션수
                var mustCnt = 0; // 옵션별 필수상품수
                var haveToCnt = 0; // 옵션1개별 필수체크 통과한 수
                $(
                    frmId + " [id*='option_display_item_" + setOptNo + "']"
                ).each(function (key) {
                    var itemId = this.id;

                    $(
                        frmId +
                            ' input[name="addGoodsInputMustFl' +
                            setOptNo +
                            '[]"]'
                    ).each(function () {
                        var addGoodsValue = this.value;

                        var items = $(
                            "#" +
                                itemId +
                                " input[name='addGoodsNo" +
                                setOptNo +
                                "[" +
                                key +
                                "][]']"
                        );
                        if (setOptNo != "" && setOptNo != undefined) {
                            items = $(
                                "input[name='addGoodsNo" +
                                    setOptNo +
                                    "[" +
                                    key +
                                    "][]']"
                            );
                        }
                        items.each(function () {
                            var group = $(this).data("group");

                            if (addGoodsValue == group) {
                                mustCnt++;
                                return false;
                            }
                        });
                    });

                    if (
                        mustCnt ==
                        $(
                            frmId +
                                ' input[name="addGoodsInputMustFl' +
                                setOptNo +
                                '[]"]'
                        ).length
                    ) {
                        // 옵션 1개 별로 필수 체크 통과하면 haveToCnt증가
                        haveToCnt++;
                    }

                    mustCnt = 0; // 옵션 1개별로 필수 체크 초기화
                    totalOptionCnt++; // 옵션별 카운트
                });

                if (totalOptionCnt == haveToCnt) {
                    //옵션수와 필수체크통과한 옵션수가 동일하면 통과
                    return true;
                } else {
                    return false;
                }
            } else return true;
        } else return true;
    };

    /**
     * 추가상품 삭제
     */
    this.remove_add_goods = function (optionId, addGoodsId) {
        $("#add_goods_display_item_" + optionId + "_" + addGoodsId).remove();

        var addGoodsCnt = $(
            "tbody[id='option_display_item_" + optionId + "']"
        ).find("tbody[id*='add_goods_display_item_']").length;

        if (addGoodsCnt == 0)
            $("tbody[id='option_display_item_" + optionId + "']")
                .find(".add")
                .remove();

        var setAddGoodsPrice = 0;

        $(
            "#option_display_item_" +
                optionId +
                " input[name*='add_goods_total_price']"
        ).each(function () {
            setAddGoodsPrice += parseFloat($(this).val());
        });

        $(
            "#option_display_item_" +
                optionId +
                " input[name='addGoodsPriceSum[]']"
        ).val(setAddGoodsPrice);

        if (setCartTabFl == "y") {
            $(
                "#frmCartTabViewLayer .add_goods_display_item_" +
                    optionId +
                    "_" +
                    addGoodsId
            ).remove();
        }
        if (
            typeof gd_total_calculate !== "undefined" &&
            $.isFunction(gd_total_calculate)
        )
            gd_total_calculate();
    };

    this.remove_component_option = function () {
        $("[id^='component_option_display_item_']").remove();
        $("[id^='option_display_item_']").remove(); // Inner component_option_display_item_

        if (
            typeof gd_total_calculate !== "undefined" &&
            $.isFunction(gd_total_calculate)
        ) {
            gd_total_calculate();
        }
    };

    this.default_component_options = function () {
        if (
            !confirm(
                __(
                    "메뉴 구성을 추천 메뉴로 초기화 합니다. 계속 진행하시겠습니까?"
                )
            )
        ) {
            return;
        }

        this.resetAddedPrice();

        const selectedOptionName = $("#selectedComponentOptionName").val() || "";
        const mealsMatch = selectedOptionName.match(/(\d+)식/);
        const weeksMatch = selectedOptionName.match(/(\d+)주/);
        const totalMeals = mealsMatch ? parseInt(mealsMatch[1], 10) : 10;
        const totalWeeks = weeksMatch ? parseInt(weeksMatch[1], 10) : 1;
        const allowedMenuSelectableCount = Math.floor(totalMeals / totalWeeks) || 10;

        const items = $("[id^='component_goods_display_item_']");
        var allowedCount = Number($('input[name="allowedCount[]"]').val());

        if (allowedCount < 1) {
            console.error(
                "최대 구매 수량 세팅에 오류가 있습니다.",
                allowedCount
            );
            return;
        }

        var maxSelection = Math.min(items.length, allowedMenuSelectableCount);
        const baseCount = Math.floor(allowedCount / maxSelection);
        // const restIndex = allowedCount % maxSelection;
        const restIndex = 0;

        let incrementor = 0;
        items.each(function (index, item) {
            let defaultCount = 0;
            const inputTag = $(item).find("span.count input");

            if (!inputTag.data("outOfStock")) {
                defaultCount =
                    (incrementor < maxSelection ? baseCount : 0) +
                    (incrementor < restIndex ? 1 : 0);
            }
            
            inputTag.val(defaultCount);
            inputTag.data("value", defaultCount);
            if (defaultCount == 0) {
                inputTag.addClass("disabled");
            } else {
                inputTag.removeClass("disabled");
            }

            if (!inputTag.data("outOfStock")) {
                incrementor++;
            }
        });
        this.guide_component_selection_count();
        gd_total_calculate();
    };

    this.empty_component_options = function () {
        if (
            !confirm(
                __(
                    "선택하신 메뉴가 모두 0개로 초기화 됩니다. 계속 진행하시겠습니까?​"
                )
            )
        ) {
            return;
        }

        this.resetAddedPrice();

        const items = $("[id^='component_goods_display_item_']");

        items.each(function (index, item) {
            const inputTag = $(item).find("span.count input");
            inputTag.val(0);
            inputTag.data("value", 0);
            inputTag.addClass("disabled");
        });
        this.guide_component_selection_count();
        gd_total_calculate();
    };

    this.component_option_price_display = function (inputName) {
        // 구매불가 상품 가격 미출력
        if (
            $('input[name="orderPossible"]').length &&
            $('input[name="orderPossible"]').val() === "n"
        ) {
            return false;
        }

        let allowedMenuSelectableCount = 10; // 동적으로 재계산됨

        var frmId = "#" + $(inputName).closest("form").attr("id");

        if (setOptNo != "" && setOptNo != undefined) {
            if (
                $(
                    frmId + ' select[name="optionSnoInput' + setOptNo + '"]'
                ).val() == "0"
            ) {
                $(
                    "[id^=component_option_display_item_" +
                        setOptNo +
                        "] button"
                ).prop("disabled", true);
                $(
                    "[id^=component_option_display_item_" + setOptNo + "] input"
                ).prop("disabled", true);
                $(
                    "#relateGoodsList input[name*='optionSno" +
                        setOptNo +
                        "[]']"
                ).remove();
                return;
            }
        }

        if (setOptionTextFl == "y") {
            if (!this.option_text_valid(frmId)) {
                if (setOptNo == "" || setOptNo == undefined) {
                    if (setOptionDisplayFl == "s") {
                        $(
                            frmId +
                                ' select[name="optionSnoInput' +
                                setOptNo +
                                '"]'
                        ).val("");
                    } else {
                        $(frmId + ' select[name*="optionNo_"]').val("");
                        $(frmId + ' select[name*="optionNo_"]').trigger(
                            "chosen:updated"
                        );
                    }

                    alert(
                        __(
                            "%1$s선택한 옵션의 필수 텍스트 옵션 내용을 먼저 입력해주세요.",
                            setGoodsNm
                        )
                    );
                    return false;
                }
            }
            $(frmId + ' input[name*="optionTextInput' + setOptNo + '"]').val(
                ""
            );
        }

        if (setAddGoodsFl == "y") {
            if (!this.add_goods_valid(frmId)) {
                if (setOptNo == "" || setOptNo == undefined) {
                    if (setOptionDisplayFl == "s") {
                        $(
                            frmId +
                                ' select[name="optionSnoInput' +
                                setOptNo +
                                '"]'
                        ).val("");
                    } else {
                        $(frmId + ' select[name*="optionNo_"]').val("");
                        $(frmId + ' select[name*="optionNo_"]').trigger(
                            "chosen:updated"
                        );
                    }

                    alert(
                        __(
                            "%1$s선택한 옵션의 필수 추가 상품 먼저 선택해주세요.",
                            setGoodsNm
                        )
                    );
                    return false;
                }
            }
        }

        if (
            $(frmId + " input[name='selectGoodsFl']").length &&
            $(frmId + " input[name='selectGoodsFl']").val()
        ) {
            $(frmId + " table.option_display_area tbody").remove();
        }

        if (setOptionDisplayFl == "s") {
            if (
                $(
                    frmId +
                        ' select[name="optionSnoInput' +
                        setOptNo +
                        '"] option:selected'
                ).val() != ""
            ) {
                var valTmp = $(
                    frmId +
                        ' select[name="optionSnoInput' +
                        setOptNo +
                        '"] option:selected'
                ).val();
                if (setOptNo == "")
                    $(frmId + ' select[name="optionSnoInput' + setOptNo + '"]')
                        .val("")
                        .trigger("chosen:updated");
            }
        } else if (setOptionDisplayFl == "d") {
            var valTmp = $(
                frmId + ' input[name="optionSnoInput' + setOptNo + '"]'
            ).val();
            $(frmId + ' select[name*="optionNo_"]').val("");
            $(frmId + ' select[name*="optionNo_"]')
                .not(":eq(0)")
                .attr("disabled", true);
            $(frmId + ' select[name*="optionNo_"]').trigger("chosen:updated");
        }

        if (typeof valTmp == "undefined") return false;

        var arrTmp = new Array();
        var arrTmp = valTmp.split(setStrDivision);
        var optionName = arrTmp[1].trim();
        var optionInput = arrTmp[0];
        var optionSellCodeValue = arrTmp[2];
        var optionDeliveryCodeValue = arrTmp[3];
        var arrTmp = optionInput.split(setIntDivision);

        // Check if the option is trying to be changed or not
        const prevSelectedOptionName = $("#selectedComponentOptionName").val();
        const curSelectedOptionName = optionName;

        if (
            prevSelectedOptionName == curSelectedOptionName &&
            curSelectedOptionName != null
        ) {
            this.show_component_goods_select();
            return;
        }

        const tryingChange =
            prevSelectedOptionName !== curSelectedOptionName &&
            curSelectedOptionName !== undefined;

        if (tryingChange) {
            let menuSelectionAdjusted = false;

            if (prevSelectedOptionName != null) {
                const match = prevSelectedOptionName.match(/(\d+)식/);
                let allowedCount = 0;
                if (match) {
                    allowedCount = parseInt(match[1], 10);
                } else {
                    console.error(
                        "옵션 이름에 (N식) 형태의 구문이 필요합니다. 현재 옵션 이름 : ",
                        this.dataset.option_name
                    );
                }
                const prevWeeksMatch = prevSelectedOptionName.match(/(\d+)주/);
                const prevWeeks = prevWeeksMatch ? parseInt(prevWeeksMatch[1], 10) : 1;
                const prevMealsPerWeek = Math.floor(allowedCount / prevWeeks) || 10;
                const menuCountItems = $('input[name^="componentGoodsCnt["]');

                const maxSelection = Math.min(
                    menuCountItems.length,
                    prevMealsPerWeek
                );
                const baseCount = Math.floor(allowedCount / maxSelection);
                // const restIndex = allowedCount % maxSelection;
                const restIndex = 0;

                let incrementor = 0;
                for (var i = 0; i < menuCountItems.length; i++) {
                    let defaultCount = 0;
                    var item = menuCountItems.eq(i);

                    if (!item.data("outOfStock")) {
                        defaultCount =
                            (incrementor < maxSelection ? baseCount : 0) +
                            (incrementor < restIndex ? 1 : 0);
                    }
                    if (defaultCount !== item.data("value")) {
                        menuSelectionAdjusted = true;
                        break;
                    }

                    if (!item.data("outOfStock")) {
                        incrementor++;
                    }
                }

                if (menuSelectionAdjusted) {
                    if (
                        !confirm(
                            __(
                                "기간을 변경하면 선택하신 메뉴 구성이 초기화 됩니다. 계속 진행 하시겠습니까?​"
                            )
                        )
                    ) {
                        return;
                    }
                }
            }
        }
        // Check if the option is trying to be changed or not

        this.remove_component_option();

        const match = optionName.match(/(\d+)식/);
        let allowedCount = 0;
        if (match) {
            allowedCount = parseInt(match[1], 10);
        } else {
            console.error(
                "옵션 이름에 (N식) 형태의 구문이 필요합니다. 현재 옵션 이름 : ",
                optionText.option_name
            );
            return;
        }

        const weeksMatch = optionName.match(/(\d+)주/);
        const totalWeeks = weeksMatch ? parseInt(weeksMatch[1], 10) : 1;
        allowedMenuSelectableCount = Math.floor(allowedCount / totalWeeks) || 10;

        if (optionSellCodeValue != "" && optionSellCodeValue != undefined) {
            optionSellCodeValue = "[" + optionSellCodeValue + "]";
        }
        if (
            optionDeliveryCodeValue != "" &&
            optionDeliveryCodeValue != undefined
        ) {
            optionDeliveryCodeValue = "[" + optionDeliveryCodeValue + "]";
        }

        if (setMileageUseFl == "y" && arrTmp[2]) {
            $(frmId + ' input[name="set_goods_mileage' + setOptNo + '"]').val(
                parseFloat(arrTmp[2].trim())
            );
        }

        if (arrTmp[3]) {
            $(frmId + ' input[name="set_goods_stock' + setOptNo + '"]').val(
                parseFloat(arrTmp[3].trim())
            );
        }

        var optionPrice = arrTmp[1].trim();
        var optionStock = parseFloat(arrTmp[3].trim());
        var displayOptionkey = arrTmp[0] + "_" + $.now();

        if (
            $(frmId + " tr.optionKey" + setOptNo + "_" + arrTmp[0]).length &&
            (setOptNo == "" || setOptNo == undefined) &&
            setOptionTextFl != "y"
        ) {
            console.error("이미 선택된 옵션입니다.");
        } else {
            var addHtml = "";
            var complied = _.template(
                $("#componentOptionTemplate" + setTemplate).html()
            );
            if (setOptNo != "" && setOptNo != undefined) {
                complied = _.template(
                    $(
                        "#componentOptionTemplateRelated" +
                            setOptNo +
                            setTemplate
                    ).html()
                );
            }
            addHtml += complied({
                displayOptionkey: displayOptionkey,
                optionSno: arrTmp[0],
                optionName: optionName,
                optionPrice: optionPrice,
                optionStock: optionStock,
                optionSellCodeValue: optionSellCodeValue,
                optionDeliveryCodeValue: optionDeliveryCodeValue,
                allowedCount: allowedCount,
                displayOptionName: optionName,
            });

            if (setOptNo != "" && setOptNo != undefined) {
                $(
                    "[id^=component_option_display_item_" + setOptNo + "]"
                ).remove();
                $(
                    '[id^="option_display_item_' + setOptNo + '"]' // inner component_option_display_item_
                ).remove();
            }

            $(frmId + " .component_display_area" + setOptNo).append(addHtml);
            
            // ---- 만약 template을 연 채로 (hidden 클래스 미 할당) 시작할 경우만 아래 코드가 있어야 함. -> hidden클래스 추가시 아래 코드 제거
            $("#layerDim").removeClass("dn");
            amplitude.logEvent('selectmenu_popup_view', {
                product_name: $('.headingArea h2').text().replace(/\n|\t/g, ''),
                product_id: setGoodsNo,
                optionName: optionName,
                open_type: 'implicit'
            });
            // ---- 여기까지
            
            // 상품 옵션가 표시 설정
            if (setOptionPriceFl == "y" && optionPrice) {
                if (optionPrice > 0) var addPlus = "+";
                else var addPlus = "";

                var optionDisplayTextPrice =
                    " (" +
                    addPlus +
                    gdCurrencySymbol +
                    gd_money_format(optionPrice) +
                    gdCurrencyString +
                    ")";
                $(
                    "[id^=component_option_display_item_" +
                        displayOptionkey +
                        "] .cart_tit > span"
                )
                    .eq(0)
                    .append(optionDisplayTextPrice);
            }

            // FIXME: Currently the count of item is not allowed. If want to allow this, uncomment below.
            // $(
            //     frmId +
            //         " tbody#component_option_display_item_" +
            //         displayOptionkey +
            //         " tr.optionKey" +
            //         setOptNo +
            //         "_" +
            //         arrTmp[0] +
            //         " .goods_cnt"
            // ).on("click", function () {
            //     setControllerName.count_change(this, 1);
            // });

            $(frmId + " div.header button.delete_goods").on(
                "click",
                function (e) {
                    $("[id^='component_option_display_item_']").addClass(
                        "hidden"
                    );
                    $("#layerDim").addClass("dn");

                    amplitude.logEvent('selectmenu_close_button_click', {
                        product_name: $('.headingArea h2').text().replace(/\n|\t/g, ''), 
                        product_id: setGoodsNo
                    });
                }
            );

            $(frmId + " div.footer div.completeButton").on(
                "click",
                function (e) {
                    $("[id^='component_option_display_item_']").addClass(
                        "hidden"
                    );
                    $("#layerDim").addClass("dn");
                    
                    amplitude.logEvent('selectmenu_done_button_click', {
                        product_name: $('.headingArea h2').text().replace(/\n|\t/g, ''), 
                        product_id: setGoodsNo
                    });
                }
            );

            $(frmId + " div.header button.default_components").on(
                "click",
                (e) => {
                    setControllerName.default_component_options();
                    amplitude.logEvent('selectmenu_default_click', {
                        product_name: $('.headingArea h2').text().replace(/\n|\t/g, ''), 
                        product_id: setGoodsNo
                    });
                }
            );

            $(frmId + " div.header button.empty_components").on(
                "click",
                (e) => {
                    setControllerName.empty_component_options();
                    amplitude.logEvent('selectmenu_empty_click', {
                        product_name: $('.headingArea h2').text().replace(/\n|\t/g, ''), 
                        product_id: setGoodsNo
                    });
                }
            );

            if (setCartTabFl == "y") {
                var addHtml = "";
                var complied = _.template($("#optionTemplateCartTab").html());
                addHtml += complied({
                    displayOptionkey: displayOptionkey,
                    optionSno: arrTmp[0],
                    optionName: optionName,
                    optionPrice: optionPrice,
                    optionStock: optionStock,
                    optionSellCodeValue: optionSellCodeValue,
                    optionDeliveryCodeValue: optionDeliveryCodeValue,
                    allowedCount: allowedCount,
                    displayOptionName: optionName,
                });

                $("#frmCartTabViewLayer .component_display_area").append(
                    addHtml
                );

                // 상품 옵션가 표시 설정
                if (setOptionPriceFl == "y" && optionPrice) {
                    if (optionPrice > 0) var addPlus = "+";
                    else var addPlus = "";

                    var optionDisplayTextPrice =
                        " (" +
                        addPlus +
                        gdCurrencySymbol +
                        gd_money_format(optionPrice) +
                        gdCurrencyString +
                        ")";
                    $(
                        "#frmCartTabViewLayer tr.optionKey" +
                            setOptNo +
                            "_" +
                            arrTmp[0] +
                            " .cart_tit > span"
                    )
                        .eq(0)
                        .append(optionDisplayTextPrice);
                }

                $(
                    "#frmCartTabViewLayer tr.optionKey" +
                        setOptNo +
                        "_" +
                        arrTmp[0] +
                        " .goods_cnt"
                ).on("click", function () {
                    var datakey = $(this).val().split(setStrDivision);
                    $("#component_option_display_item_" + datakey[1])
                        .find('button[class="' + $(this).attr("class") + '"]')
                        .click();
                });

                $(
                    "#frmCartTabViewLayer tr.optionKey" +
                        setOptNo +
                        "_" +
                        arrTmp[0] +
                        " button.delete_goods"
                ).on("click", function () {
                    $(
                        "#frmView #" +
                            $(this).data("key") +
                            " button.delete_goods"
                    ).click();
                });

                $("#frmCartTabViewLayer div.option_total_display_area").show();
            }

            // Add all component goods ////////////////////////////////////////////////////////////
            const allOptions = $("select[name='addGoodsInput0'] option");

            var maxSelection = Math.min(
                allOptions.length,
                allowedMenuSelectableCount
            );
            const baseCount = Math.floor(allowedCount / maxSelection);
            // const restIndex = allowedCount % maxSelection;
            const restIndex = 0;

            let totalItemCount = 0;
            let incrementor = 0;

            let defaultStamp = '';
            let defaultComponent = '';
            allOptions.each(function (index) {
                if ($(this).val()) {
                    let defaultCount = 0;
                    var arrTmp = $(this).val().split(setStrDivision);
                    const componentGoodsOutOfStock = arrTmp[9];

                    if (componentGoodsOutOfStock != 'y') {
                        defaultCount =
                            (incrementor < maxSelection ? baseCount : 0) +
                            (incrementor < restIndex ? 1 : 0);
                    }
                    setControllerName.component_goods_select(
                        $(this).parent(),
                        $(this),
                        defaultCount,
                        allowedCount,
                        true
                    );
                    totalItemCount += defaultCount;
                    
                    if (componentGoodsOutOfStock != 'y') {
                        incrementor++;
                    }

                    if(defaultCount > 0) {
                        const goodsNo = $(this).val().split(setIntDivision)[0].trim();
                        const goodsName = $(this).val().split(setStrDivision)[1].trim();
                        defaultStamp +=  goodsNo + '|' + defaultCount + ',';
                        defaultComponent += goodsName + ',';
                    }
                }
            });
            
            $('input[name="defaultStamp[]"]').val(defaultStamp.slice(0, -1));
            $('input[name="defaultComponent[]"]').val(defaultComponent.slice(0, -1));

            this.guide_component_selection_count();
            // End of Add all component goods

            //
            $(".img_blox.wm_open_layer").click(this.openAddGoodsPopup);
            //////////////////////////////////////////////////////////////

            this.goods_calculate(frmId, 1, displayOptionkey, setMinOrderCnt);
            if (setCouponUseFl == "y") {
                if (
                    typeof gd_open_layer !== "undefined" &&
                    $.isFunction(gd_open_layer)
                ) {
                    gd_open_layer();
                }
            }

            $(frmId + " div.option_total_display_area").show();
            $(frmId + " div.end_price").show();
        }
    };

    this.show_component_goods_select = function () {
        const menuSelectionModal = $("[id^='component_option_display_item_']");
        if (!menuSelectionModal.length) {
            alert(__("기간을 먼저 선택해 주세요."));
            return;
        }
        menuSelectionModal.removeClass("hidden");
        $("#layerDim").removeClass("dn");
        amplitude.logEvent('selectmenu_popup_view', {product_name: $('.headingArea h2').text().replace(/\n|\t/g, ''), product_id: setGoodsNo, option_name: $('#selectedComponentOptionName').val(), open_type: 'explicit'})
    };

    this.component_goods_select = function (
        inputName,
        componentOptionGoods,
        count,
        maxSelection,
        asBulk = false
    ) {
        var frmId = "#" + $(inputName).closest("form").attr("id");
        var selAddGoods = $(inputName).data("key");

        if (
            $(
                frmId +
                    " select[name='addGoodsInput" +
                    setOptNo +
                    selAddGoods +
                    "']"
            ).val() != "" ||
            !!componentOptionGoods
        ) {
            var displayOptionDisplay = $(
                frmId +
                    " div[id*='component_option_display_item_" +
                    setOptNo +
                    "']"
            )
                .last()
                .attr("id");

            if (displayOptionDisplay) {
                var displayOptionkey = displayOptionDisplay.replace(
                    "component_option_display_item_",
                    ""
                );
                if (setOptNo != "" && setOptNo != undefined) {
                    var tmp = displayOptionkey.split("_");
                    displayOptionkey = tmp[0] + "_" + selAddGoods;
                }

                const componentGoods =
                    componentOptionGoods ??
                    $(
                        frmId +
                            " select[name='addGoodsInput" +
                            setOptNo +
                            selAddGoods +
                            "']"
                    );

                if (componentGoods.val() == "0") {
                    $("input[name^='addGoodsNo" + setOptNo + "']").remove();
                    $(
                        "[id^=component_goods_display_item_" +
                            displayOptionkey +
                            '] input[type="text"]'
                    ).attr("disabled", true);
                    $(
                        "[id^=component_goods_display_item_" +
                            displayOptionkey +
                            "] button"
                    ).attr("disabled", true);
                    return;
                }

                var arrTmp = new Array();
                var arrTmp = componentGoods.val().split(setStrDivision);
                var componentGoodsName = arrTmp[1].trim();
                var componentGoodsImage = decodeURIComponent(arrTmp[2].trim());
                var componentGoodsGroup = arrTmp[3].trim();
                var componentGoodsValue = arrTmp[0];
                var componentGoodsStockFl = arrTmp[4];
                var componentGoodsStock = arrTmp[5];
                const limitCnt = arrTmp[6];
                const premiumMultiplier = parseInt(arrTmp[7], 10) || 1; // 기본값 1배수
                const tags = arrTmp[8];
                const componentGoodsOutOfStock = arrTmp[9];


                var arrTmp = componentGoodsValue.split(setIntDivision);
                var displayComponentGoodsKey = arrTmp[0];
                var optionIndex = $(
                    frmId + " #" + displayOptionDisplay
                ).index();
                if (setOptNo != "" && setOptNo != undefined) {
                    optionIndex = 0;
                }

                // goodsDescription에서 첫 번째 이미지 url 찾기
                const goodsDescription = $(
                    frmId + " #goods_description_" + displayComponentGoodsKey
                ).html();
                const match = goodsDescription.match(
                    /<img[^>]*src\s*=\s*['"]\\&quot;([^'"]*)\\&quot;['"][^>]*>/
                );
                const firstImgSrc = match ? match[1] : null;
                // goodsDescription에서 첫 번째 이미지 url 찾기

                if (
                    $(
                        frmId +
                            " #component_goods_display_item_" +
                            displayOptionkey +
                            "_" +
                            displayComponentGoodsKey
                    ).length &&
                    (setOptNo == "" || setOptNo == undefined)
                ) {
                    alert(__("이미 선택된 추가상품 입니다."));
                    return false;
                } else {
                    var addHtml = "";
                    var complied = _.template(
                        $("#componentGoodsTemplate" + setTemplate).html()
                    );
                    if (setOptNo != "" && setOptNo != undefined) {
                        complied = _.template(
                            $(
                                "#componentGoodsTemplateRelated" +
                                    setOptNo +
                                    setTemplate
                            ).html()
                        );
                    }

                    const _optName = $("#selectedComponentOptionName").val() || "";
                    const _weeksMatch = _optName.match(/(\d+)주/);
                    const _mealsMatch = _optName.match(/(\d+)식/);
                    const _totalMeals = _mealsMatch ? parseInt(_mealsMatch[1], 10) : 10;
                    const _totalWeeks = _weeksMatch ? parseInt(_weeksMatch[1], 10) : 1;
                    const _mealsPerWeek = Math.floor(_totalMeals / _totalWeeks) || 10;
                    const limitCntAmplifier =
                        Math.floor(maxSelection / _mealsPerWeek) || 1;

                    addHtml += complied({
                        displayOptionkey: displayOptionkey,
                        displayComponentGoodsKey: displayComponentGoodsKey,
                        componentGoodsImage: componentGoodsImage,
                        componentGoodsGroup: componentGoodsGroup,
                        optionIndex: optionIndex,
                        optionSno: arrTmp[0],
                        componentGoodsName: componentGoodsName,
                        componentGoodsStockFl: componentGoodsStockFl,
                        componentGoodsStock: componentGoodsStock,
                        componentGoodsOutOfStockDisabled: componentGoodsOutOfStock == 'y' ? 'out_of_stock' : '',
                        limitCnt: limitCnt * limitCntAmplifier,
                        premiumMultiplier: premiumMultiplier,
                        tags: tags,
                        componentGoodsPrice: parseFloat(arrTmp[1].trim()),
                        optionCount: count,
                        goodsDescriptionImage: firstImgSrc,
                    });

                    if (setOptNo != "" && setOptNo != undefined) {
                        $(
                            "[id^=component_goods_display_item_" +
                                displayOptionkey +
                                "]"
                        ).remove();
                        $(
                            "[id^=component_goods_display_area_" +
                                displayOptionkey +
                                "]"
                        ).append(addHtml);
                    } else {
                        // filter setting
                        const componentOptionFilterArea = $(
                            frmId +
                                " #component_option_display_item_" +
                                displayOptionkey +
                                " .filter"
                        );

                        const processedTag = processTags(tags);
                        const prevTags = processTags(
                            componentOptionFilterArea.data("tags")
                        );
                        const selectedTagStrings = processTags(
                            componentOptionFilterArea.data("selectedTags")
                        );

                        const tagSet = new Set([...prevTags, ...processedTag]);

                        const tagArray = Array.from(tagSet);

                        componentOptionFilterArea.data(
                            "tags",
                            tagArray.join(", ")
                        );

                        const sortingOrder = ["전체", "저당","오메가3↑","단백질20↑","저콜레스테롤","해산물","육류","밥류","면류","한식","양식"];
                        tagArray.sort((x, y) => {
                            const indexX = sortingOrder.indexOf(x);
                            const indexY = sortingOrder.indexOf(y);
                            
                            if (indexX === -1) return 1;
                            if (indexY === -1) return -1;
                            return indexX - indexY;
                        });

                        const tagList = tagArray.map((tag) => {
                            const li = $("<li>")
                                .text(tag)
                                .addClass(
                                    selectedTagStrings.includes(tag)
                                        ? "filter_button selected"
                                        : "filter_button"
                                )
                                .click(function () {
                                    if (tag === "전체") {
                                        $(".filter_button").removeClass(
                                            "selected"
                                        );
                                        $(this).addClass("selected");
                                    } else {
                                        $(this).toggleClass("selected");
                                        const hasAnySelected = $(".filter_button").hasClass(
                                            "selected"
                                        );
                                        if (!hasAnySelected) {
                                            $(
                                                ".filter_button:contains('전체')"
                                            ).addClass("selected");
                                        } else if (
                                            selectedTagStrings.includes("전체")
                                        ) {
                                            $(
                                                ".filter_button:contains('전체')"
                                            ).removeClass("selected");
                                        }
                                    }

                                    const selectedTags = $(
                                        ".filter_button.selected"
                                    )
                                        .map(function () {
                                            return $(this).text();
                                        })
                                        .get();

                                    componentOptionFilterArea.data(
                                        "selectedTags",
                                        selectedTags.join(",")
                                    );

                                    $(".view_order_component_goods")
                                        .children()
                                        .removeClass("disabled");

                                    $(".view_order_component_goods").each(
                                        function () {
                                            const selectedTagArray =
                                                processTags(
                                                    componentOptionFilterArea.data(
                                                        "selectedTags"
                                                    )
                                                );
                                            const hasCommonElement =
                                                selectedTagArray.some((item) => item === "전체" ||
                                                    processTags(
                                                        $(this).data("tags")
                                                    ).includes(item)
                                                );
                                            if (
                                                hasCommonElement
                                            ) {
                                                $(this)
                                                    .children()
                                                    .removeClass("disabled");
                                            } else {
                                                $(this)
                                                    .children()
                                                    .addClass("disabled");
                                            }
                                        }
                                    );

                                    amplitude.logEvent('selectmenu_tag_click', {
                                        tag_label: tag,
                                        product_name: $('.headingArea h2').text().replace(/\n|\t/g, ''),
                                        product_id: setGoodsNo,
                                        click_type: $(this).hasClass('selected') ? 'check' : 'uncheck',
                                    })
                                });

                            return li;
                        });

                        componentOptionFilterArea.html(tagList);
                        // filter setting

                        $(
                            frmId +
                                " #component_option_display_item_" +
                                displayOptionkey +
                                " .component"
                        ).append(addHtml);
                    }

                    $(
                        frmId +
                            " #component_goods_display_item_" +
                            displayOptionkey +
                            "_" +
                            displayComponentGoodsKey +
                            " .component_goods_cnt"
                    ).on("click", function () {
                        setControllerName.count_change(
                            this,
                            0,
                            true,
                            maxSelection
                        );
                    });

                    if (count == 0) {
                        $(
                            frmId +
                                " #component_goods_display_item_" +
                                displayOptionkey +
                                "_" +
                                displayComponentGoodsKey +
                                " .text"
                        ).addClass("disabled");
                    }

                    $(
                        frmId +
                            " #component_goods_display_item_" +
                            displayOptionkey +
                            "_" +
                            displayComponentGoodsKey +
                            " button.delete_add_goods"
                    ).on("click", function () {
                        setControllerName.remove_component_goods(
                            displayOptionkey,
                            displayComponentGoodsKey
                        );
                    });

                    if (setCartTabFl == "y") {
                        var addHtml = "";
                        var complied = _.template(
                            $("#componentGoodsCartTabTemplate").html()
                        );
                        addHtml += complied({
                            displayOptionkey: displayOptionkey,
                            displayComponentGoodsKey: displayComponentGoodsKey,
                            componentGoodsImage: componentGoodsImage,
                            componentGoodsGroup: componentGoodsGroup,
                            optionIndex: optionIndex,
                            optionSno: arrTmp[0],
                            componentGoodsName: componentGoodsName,
                            componentGoodsPrice: parseFloat(arrTmp[1].trim()),
                        });

                        $(
                            "#frmCartTabViewLayer .component_option_display_item_" +
                                displayOptionkey
                        ).append(addHtml);

                        $(
                            "#frmCartTabViewLayer .component_goods_display_item_" +
                                displayOptionkey +
                                "_" +
                                displayComponentGoodsKey +
                                " .component_goods_cnt"
                        ).on("click", function () {
                            $(
                                "#component_goods_display_item_" +
                                    displayOptionkey +
                                    "_" +
                                    displayComponentGoodsKey
                            )
                                .find(
                                    'button[class="' +
                                        $(this).attr("class") +
                                        '"]'
                                )
                                .click();
                        });

                        $(
                            "#frmCartTabViewLayer .component_goods_display_item_" +
                                displayOptionkey +
                                "_" +
                                displayComponentGoodsKey +
                                " button.delete_add_goods"
                        ).on("click", function () {
                            $(
                                "#component_goods_display_item_" +
                                    displayOptionkey +
                                    "_" +
                                    displayComponentGoodsKey +
                                    " button.delete_add_goods"
                            ).click();
                        });
                    }

                    var itemNo =
                        displayOptionkey +
                        setIntDivision +
                        displayComponentGoodsKey;

                    this.goods_calculate(frmId, 0, itemNo, 1, asBulk);
                    this.display_selected_component_goods();

                    if (setCouponUseFl == "y") {
                        if (
                            typeof gd_open_layer !== "undefined" &&
                            $.isFunction(gd_open_layer)
                        ) {
                            gd_open_layer();
                        }
                    }
                }
            } else {
                alert(__("옵션을 먼저 선택해주세요."));
                return false;
            }
        }
    };

    this.display_selected_component_goods = function () {
        const items = $("[id^='component_goods_display_item_']");
        const displayOptionName = $("#selectedComponentOptionName").val();

        const defaultStamp = $('input[name="defaultStamp[]"]').val();
        let modifiedStamp = '';
        const selectedItems = items
            .map(function (index, item) {
                const countTag = $(item).find("span.count input");
                const count = countTag.val();
                const goodsNameTag = $(item).find(
                    'input[name^="componentGoodsName["][name$="][]"]'
                );
                const goodsName = goodsNameTag.val();

                const goodsNoTag = $(item).find(
                    'input[name^="componentGoodsNo["][name$="][]"]'
                );
                const goodsNo = Math.floor(goodsNoTag.val());

                const addedPriceTag = $(item).find(
                    'input[name^="componentGoodsAddedPrice["][name$="][]"]'
                );
                
                const addedPrice = parseFloat(addedPriceTag.val());

                return { goodsName, count, goodsNo, addedPrice };
            })
            .get()
            .filter((item) => Number(item.count) > 0);

        const stampStrings = selectedItems.map((item) => item.goodsNo + "|" + item.count);
        // const isDefaultStamp = (!defaultStamp || (defaultStamp === stampStrings.join(',')));
        const isDefaultStamp = false;

        const selectedItemTexts = selectedItems.map(
            (item) =>item.goodsName + " (" + item.count + (item.addedPrice > 0 ? `개 +${item.addedPrice.toLocaleString()}원)` : "개)")
        );

        const totalCount = selectedItems.reduce(
            (acc, item) => Number(item.count) + acc,
            0
        );

        // option_display_item_ is inner component_option_display_item_
        if (isDefaultStamp) {
            $("[id^='option_display_item_'] .title").html(displayOptionName + ' / ' + '기본구성으로 선택하였습니다.');
            
            $("[id^='option_display_item_'] #menus").html(
                selectedItemTexts.join(", ")
            );
            $('input[name="isDefaultComponents[]"]').val('1');
        } else if (selectedItemTexts.length > 0) {
            const title = displayOptionName + ' / ' + "총 " + selectedItems.length + "개 메뉴 " + totalCount + "식을 선택하였습니다.";
            $("[id^='option_display_item_'] .title").html(title);

            $("[id^='option_display_item_'] #menus").html(
                selectedItemTexts.join(", ")
            );
            $('input[name="isDefaultComponents[]"]').val('0');
        } else {
            $("[id^='option_display_item_'] .title").html("메뉴선택");
            $("[id^='option_display_item_'] #menus").html(
                "선택된 메뉴가 없습니다."
            );
        }
    };

    /**
     * 상품 가격 계산
     * @param integer goodsFl 1: 상품 0:추가상품
     * @param integer itemNo 선택상품명
     * @param integer goodsCnt 상품 개수
     */
    this.goods_calculate = function (frmId, goodsFl, itemNo, goodsCnt, asBulk = false) {
        var goodsPrice = parseFloat(
            $(frmId + ' input[name="set_goods_price' + setOptNo + '"]').val()
        );

        if (goodsFl) {
            var optionTextPrice = 0;
            if (setOptionTextFl == "y") {
                if (
                    $(
                        frmId +
                            ' input[name="option_text_price' +
                            setOptNo +
                            "_" +
                            itemNo +
                            '"]'
                    ).length
                ) {
                    optionTextPrice = parseFloat(
                        $(
                            frmId +
                                ' input[name="option_text_price' +
                                setOptNo +
                                "_" +
                                itemNo +
                                '"]'
                        ).val()
                    );
                }
            }

            var optionPrice = parseFloat(
                $(
                    frmId +
                        ' input[name="option_price' +
                        setOptNo +
                        "_" +
                        itemNo +
                        '"]'
                ).val()
            );
            var optionTotalPrice = optionTextPrice + optionPrice;

            $(frmId + " .option_price_display_" + setOptNo + itemNo).html(
                gd_money_format(
                    ((optionTotalPrice + goodsPrice) * goodsCnt).toFixed(
                        setDecimal
                    )
                )
            );

            if (setCartTabFl == "y") {
                $("#frmCartTabViewLayer .option_price_display_" + itemNo).html(
                    gd_money_format(
                        ((optionTotalPrice + goodsPrice) * goodsCnt).toFixed(
                            setDecimal
                        )
                    )
                );
            }

            if (setOptNo == "" || setOptNo == undefined) {
                $(
                    "#option_display_item_" +
                        itemNo +
                        ' input[name="optionPriceSum[]"]'
                ).val((optionPrice * goodsCnt).toFixed(setDecimal));
                $(
                    "#option_display_item_" +
                        itemNo +
                        ' input[name="optionTextPriceSum[]"]'
                ).val((optionTextPrice * goodsCnt).toFixed(setDecimal));
                $(
                    "#option_display_item_" +
                        itemNo +
                        " input[name='goodsPriceSum[]']"
                ).val((goodsPrice * goodsCnt).toFixed(setDecimal));
            } else {
                $(
                    "#option_display_item_" +
                        setOptNo +
                        "_" +
                        itemNo +
                        ' input[name="optionPriceSum' +
                        setOptNo +
                        '[]"]'
                ).val((optionPrice * goodsCnt).toFixed(setDecimal));
                $(
                    "#option_display_item_" +
                        setOptNo +
                        "_" +
                        itemNo +
                        ' input[name="optionTextPriceSum' +
                        setOptNo +
                        '[]"]'
                ).val((optionTextPrice * goodsCnt).toFixed(setDecimal));
                $(
                    "#option_display_item_" +
                        setOptNo +
                        "_" +
                        itemNo +
                        " input[name='goodsPriceSum" +
                        setOptNo +
                        "[]']"
                ).val((goodsPrice * goodsCnt).toFixed(setDecimal));
            }
        } else if (itemNo.split(setIntDivision).length > 1) {
            var tmpStr = itemNo.split(setIntDivision);
            itemNo = tmpStr[0];
            var addGoodsItemNo = tmpStr[1];
            var addGoodsPrice = parseFloat(
                $(
                    frmId +
                        ' input[name="add_goods_price' +
                        setOptNo +
                        "_" +
                        itemNo +
                        "_" +
                        addGoodsItemNo +
                        '"]'
                ).val()
            );
            var addGoodsTotalPrice = parseFloat(addGoodsPrice * goodsCnt);

            if (setOptNo == "" || setOptNo == undefined) {
                $(
                    frmId +
                        " .add_goods_price_display_" +
                        itemNo +
                        "_" +
                        addGoodsItemNo
                ).html(gd_money_format(addGoodsTotalPrice.toFixed(setDecimal)));
            } else {
                $(
                    frmId +
                        " .add_goods_price_display_" +
                        setOptNo +
                        "_" +
                        itemNo +
                        "_" +
                        addGoodsItemNo
                ).html(gd_money_format(addGoodsTotalPrice.toFixed(setDecimal)));
            }

            if (setCartTabFl == "y") {
                $(
                    "#frmCartTabViewLayer .add_goods_price_display_" +
                        itemNo +
                        "_" +
                        addGoodsItemNo
                ).html(gd_money_format(addGoodsTotalPrice.toFixed(setDecimal)));
            }

            if (setOptNo == "" || setOptNo == undefined) {
                $(
                    "#add_goods_display_item_" +
                        itemNo +
                        "_" +
                        addGoodsItemNo +
                        ' input[name*="add_goods_total_price"]'
                ).val(addGoodsTotalPrice);
            } else {
                $('input[name*="add_goods_total_price' + setOptNo + '"]').val(
                    addGoodsTotalPrice
                );
            }

            var setAddGoodsPrice = 0;
            if (setOptNo == "" || setOptNo == undefined) {
                $(
                    "#option_display_item_" +
                        itemNo +
                        " input[name*='add_goods_total_price']"
                ).each(function () {
                    setAddGoodsPrice += parseFloat($(this).val());
                });
                $(
                    "#option_display_item_" +
                        itemNo +
                        " input[name='addGoodsPriceSum[]']"
                ).val(setAddGoodsPrice);
            } else {
                $(
                    "#option_display_item_" +
                        setOptNo +
                        "_" +
                        itemNo +
                        " input[name*='add_goods_total_price" +
                        setOptNo +
                        "']"
                ).each(function () {
                    setAddGoodsPrice += parseFloat($(this).val());
                });
                $(
                    "#option_display_item_" +
                        setOptNo +
                        "_" +
                        itemNo +
                        " input[name='addGoodsPriceSum" +
                        setOptNo +
                        "[]']"
                ).val(setAddGoodsPrice);
            }
        }

        if (setOptNo == "" || setOptNo == undefined) {
            if (
                typeof gd_total_calculate !== "undefined" &&
                $.isFunction(gd_total_calculate)
                && !asBulk
            ) {
                gd_total_calculate();
            }
        } else {
            var funcName = "total_calculate_" + setOptNo;
            window[funcName]();
        }
    };

    this.component_goods_valid = function (frmId) {
        const items = $("[id^='component_goods_display_item_']");

        const selectedItems = items
            .map(function (index, item) {
                const countTag = $(item).find("span.count input");
                const count = countTag.val();
                return { count };
            })
            .get()
            .filter((item) => Number(item.count) > 0);

        const totalCount = selectedItems.reduce(
            (acc, item) => Number(item.count) + acc,
            0
        );

        const allowedCount = $("[id^='option_display_item_']:first")
            .find('input[name="allowedCount[]"]')
            .val();

        return allowedCount == totalCount;
    };

    this.init = function (param) {
        setOptionTextFl = param.setOptionTextFl;
        setOptNo = param.setOptNo == undefined ? "" : param.setOptNo;
        setOptionDisplayFl = param.setOptionDisplayFl;
        setAddGoodsFl = param.setAddGoodsFl;
        setIntDivision = param.setIntDivision;
        setStrDivision = param.setStrDivision;
        setMileageUseFl = param.setMileageUseFl;
        setCouponUseFl = param.setCouponUseFl;
        setStockFl = param.setStockFl;
        setDecimal = param.setDecimal;
        setOptionFl = param.setOptionFl;
        setGoodsPrice = param.setGoodsPrice;
        setGoodsNo = param.setGoodsNo;
        setMileageFl = param.setMileageFl;
        setControllerName = param.setControllerName;
        setFixedSales = param.setFixedSales;
        setFixedOrderCnt = param.setFixedOrderCnt;
        setOptionPriceFl = param.setOptionPriceFl;
        setStockCnt = parseInt(param.setStockCnt);
        setOriginalMinOrderCnt = parseInt(param.setMinOrderCnt);
        if (param.setGoodsNm != "" && typeof param.setGoodsNm != "undefined") {
            setGoodsNm = param.setGoodsNm + ": ";
        } else {
            setGoodsNm = "";
        }
        if (param.setTemplate) {
            setTemplate = param.setTemplate;
        }
        if (setFixedOrderCnt == "option") {
            setMinOrderCnt = parseInt(param.setMinOrderCnt);
            setMaxOrderCnt = parseInt(param.setMaxOrderCnt);
        }
        if (
            setFixedSales != "goods" ||
            (setFixedSales == "goods" &&
                setOptionFl == "n" &&
                setOptionTextFl == "n")
        ) {
            setSalesUnit = parseInt(param.setSalesUnit);
            if (setSalesUnit > setMinOrderCnt) {
                setMinOrderCnt = parseInt(param.setSalesUnit);
            }
        }
    };

    this.initCartTab = function (cartTabFl) {
        setCartTabFl = cartTabFl;
    };

    this.max_length_alert = function (inputName) {
        var el = $(inputName);
        var textLength = el.val().length;
        var maxLength = el.attr("maxlength");
        if (textLength >= maxLength) {
            alert(maxLength - 1 + __("자 이상 등록할 수 없습니다."));
        }
    };

    this.enterKey = function (inputName) {
        if (event.keyCode == 13) $(inputName).focusout();
    };

    this.resetAddedPrice = function () {
        var firstHiddenAddPrice = $(".hidden_add_price select[name^='addGoodsInput']").first();
        if (firstHiddenAddPrice.length) {
            firstHiddenAddPrice.find('option:eq(1)').prop('selected', true); // select first option item except '선택'
            setControllerName.add_goods_select(firstHiddenAddPrice, 0, true);
        }
    }

    this.generatePriceAddedGoodsName = function () {
        var result = [];
        $("input[name='componentGoodsAddedPrice[0][]']").each(function () {
            var addedPrice = parseFloat($(this).val());
            if (addedPrice > 0) {
                var goodsName = $(this)
                    .closest("dl")
                    .find("input[name='componentGoodsName[0][]']")
                    .val();
                
                // 차등 가격 정보 추가 (배수 시스템)
                var premiumMultiplier = parseInt($(this)
                    .closest("dl")
                    .find("input[name*='componentGoodsCnt']")
                    .data("premium-multiplier"), 10) || 1;
                
                // 기본 add_goods 가격 가져오기
                var baseAddedPrice = $(".hidden_add_price select[name^='addGoodsInput']:first")?.val()?.split(setStrDivision)[0]?.split(setIntDivision)[1] || 1000;
                var premiumPrice = premiumMultiplier * Number(baseAddedPrice);

                // uncomment below when you want to show premium price
                // var priceText = " +" + Number(premiumPrice).toLocaleString() + "원";
                // result.push(goodsName + priceText);
                result.push(goodsName);
            }
        });
        return result.join(", ");        
    };

    this.openAddGoodsPopup = function () {
        let imgUrl = $(this).data("image");
        if (imgUrl == null) {
            const description = $(this).data("description").replace(/\\/g,'');
            const match = description.match(
                /<img[^>]*src\s*=\s*['"]([^'"]*)['"][^>]*>/
            );            
            const firstImgSrc = match ? match[1] : null;
            imgUrl = firstImgSrc;
        } else {
            const parentDL = $(this).closest('dl');

            const componentGoodsNoInput = parentDL.find('input[name^="componentGoodsNo"]');
            const componentGoodsNoValue = componentGoodsNoInput.val();
            const componentGoodsNameInput = parentDL.find('input[name^="componentGoodsName"]');
            const componentGoodsNameValue = componentGoodsNameInput.val();
            const componentGoodsCntInput = parentDL.find('input[name^="componentGoodsCnt"]');
            const componentGoodsCntValue = componentGoodsCntInput.val();

            amplitude.logEvent('selectmenu_menu_click', {
                menu_id: componentGoodsNoValue,
                menu_name: componentGoodsNameValue,
                product_name: $('.headingArea h2').text().replace(/\n|\t/g, ''),
                product_id: setGoodsNo,
                click_type: "detail",
                qty : componentGoodsCntValue,
            });
        }

        if (!imgUrl) return;
        $("#lyZoom2").removeClass("dn");
        $("#layerDim").removeClass("dn");
        $("#lyZoom2 .wm_wrap_cont > img").attr("src", imgUrl);
    };
};

function processTags(data) {
    if (!data) return [];
    return data
        .split(",")
        .map((item) => item.trim())
        .filter((item) => !!item);
}
