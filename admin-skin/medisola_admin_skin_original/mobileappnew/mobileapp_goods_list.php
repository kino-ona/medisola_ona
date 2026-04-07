<div class="mobileapp-goods-list">
    <input type="hidden" name="standardDate" id="mobileapp_standardDate" value="<?=$dateSection[0]?>" />

    <h2 class="section-header">
        <div class="row">
            <div class="col-xs-6">
                상품검색
            </div>
            <div class="col-xs-6 text-right">
                <a id="mobileapp_resetBtn" class="btn btn-sm btn-default border-r-n gList-resetBtn" href="javascript:;">초기화</a>
            </div>
        </div>
    </h2>
    <div class="container-default mobileapp-upper-layout">
        <table class="mobileapp-upper-table">
            <colgroup>
                <col />
                <col />
            </colgroup>
            <tbody>
            <tr>
                <td>
                    <div class="col-xs-4 gList-padding0">
                        <div class="form-group selectbox">
                            <label for="select-opt">통합검색</label>
                            <?= gd_select_box('mobileapp_key', 'key', $combineSearch, null, '', null, null, 'form-control input-sm mgb5 select-opt'); ?>
                        </div>
                    </div>
                    <div class="col-xs-8 gList-lpadding5">
                        <input type="text" name="keyword" id="mobileapp_keyword" value="" class="form-control input-sm"/>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="form-inline">
                    <div class="gList-searchDateArea">
                        <div><input type="number" pattern="\d*" class="form-control input-sm" placeholder="등록일" name="searchDate[]" id="mobileapp_searchDate1" value="" /></div>
                        <div>-</div>
                        <div><input type="number" pattern="\d*" class="form-control input-sm" placeholder="등록일" name="searchDate[]" id="mobileapp_searchDate2" value="" /></div>
                    </div>
                    <div class="gList-searchDateBtnArea">
                        <button type="button" class="btn btn-md btn-inverse dateinterval mobileappDateSelector" data-interval="<?=$dateSection[0]?>">오늘</button>
                        <button type="button" class="btn btn-md btn-inverse dateinterval mobileappDateSelector" data-interval="<?=$dateSection[1]?>">1주일</button>
                        <button type="button" class="btn btn-md btn-inverse dateinterval mobileappDateSelector" data-interval="<?=$dateSection[2]?>">15일</button>
                        <button type="button" class="btn btn-md btn-inverse dateinterval mobileappDateSelector" data-interval="<?=$dateSection[3]?>">한달</button>
                    </div>
                    <div class="description">
                        YYYYMMDD 형태로 입력하세요.
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <?php echo $cate->getMultiCategoryBox('cateGoods', '', 'style="width:100%; margin: 2px 0 2px 0;"'); ?>
                </td>
            </tr>
            <tr>
                <td>
                    <table width="100%" class="gList-displayButtonArea">
                        <tr>
                            <td style="padding-bottom: 0px !important;">
                                <input id="mobileapp_goodsDisplayFl1" class="radio" type="radio" name="goodsDisplayFl" value="" checked="checked" />
                                <label for="mobileapp_goodsDisplayFl1" class="radio-label mgb0">전체</label>
                            </td>
                            <td style="padding-bottom: 0px !important;">
                                <input id="mobileapp_goodsDisplayFl2" class="radio" type="radio" name="goodsDisplayFl" value="y" />
                                <label for="mobileapp_goodsDisplayFl2" class="radio-label mgb0">노출함</label>
                            </td>
                            <td style="padding-bottom: 0px !important;">
                                <input id="mobileapp_goodsDisplayFl3" class="radio" type="radio" name="goodsDisplayFl" value="n" />
                                <label for="mobileapp_goodsDisplayFl3" class="radio-label mgb0">노출안함</label>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            </tbody>
        </table>

        <div class="text-center mgb20 border-r-n gList-search-btn-area">
            <input type="submit" value="검&nbsp;색" class="btn_submit btn btn-block-app btn-info gList-search-btn" id="mobileapp_search"/>
        </div>
    </div>

    <div class="container-default overflow-h">
        <div class="row">
            <div class="col-xs-5 form-control-static">
                <i class="icon-search"></i> 총 <strong class="text-primary"><span id="mobileapp_totalCount">0</span>개</strong> 검색
            </div>

            <div class="col-xs-7 text-right form-inline">
                <?= gd_mobileapp_select_box_by_page_view_count(10, null, null, 'form-control input-sm needsclick list-select-sort'); ?>
            </div>
        </div>

        <table class="table table-condensed table2">
            <colgroup>
                <col />
                <col style="width:30%;" />
                <col style="width:22%;" />
            </colgroup>
            <tbody class="rowlink" id="mobileapp_goodsListArea" data-mobile-goods-view-path="<?= $mobileGoodsViewPath; ?>"></tbody>
        </table>
        <center><a id="mobileapp_moreGoodsList" class="btn btn-lg btn-block-app btn-default-gray border-r-n gList-more-btn" href="javascript:;">더보기</a></center>
    </div>
</div>
