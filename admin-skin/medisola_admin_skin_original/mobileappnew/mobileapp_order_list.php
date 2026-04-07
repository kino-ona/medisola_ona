<div class="mobileapp-order-list">
    <h2 class="section-header">
        <div class="row">
            <div class="col-xs-8" style="padding: 0 0 0 0; !important;">
                주문통합리스트
            </div>
            <div class="col-xs-4 text-right" style>
                <a id="mobileapp_resetBtn" class="btn btn-sm btn-default border-r-n oList-resetBtn" href="javascript:;">초기화</a>
            </div>
        </div>
    </h2>
    <div class="container-default mobileapp-upper-layout">
            <table class="mobileapp-upper-table">
                <colgroup>
                    <col style="" />
                    <col />
                </colgroup>
                <tbody>
                <tr>
                    <td>
                        <div class="form-group selectbox">
                            <label for="select-opt"> =주문상태= </label>
                            <?= gd_select_box('statusMode', 'statusMode', $search['stateSearch'], null, $search['stateSearchSelected'], null, null, 'form-control input-sm mgb5 select-opt'); ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="col-xs-6 oList-padding0">
                            <div class="form-group selectbox">
                                <label for="select-opt"> =통합검색= </label>
                                <?= gd_select_box('key', 'key', $search['combineSearch'], null, $search['combineSearchSelected'], null, null, 'form-control input-sm mgb5 select-opt'); ?>
                            </div>
                        </div>
                        <div class="col-xs-6 oList-lpadding5">
                            <input type="text" name="keyword" id="keyword" value="<?=$keyword?>" class="form-control input-sm"/>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="form-inline">
                        <div class="oList-entryDtArea">
                            <div> <input type="number" placeholder="주문일" class="form-control input-sm" name="treatDate[]" id="mobileapp_treatDate1" value="<?=$dateSection[4]?>" /></div>
                            <div>-</div>
                            <div><input type="number" placeholder="주문일" class="form-control input-sm" name="treatDate[]" id="mobileapp_treatDate2" value="<?=$dateSection[5]?>" /></div>
                        </div>
                        <div class="oList-searchDateBtnArea">
                            <button type="button" class="btn btn-md btn-inverse dateinterval mobileappDateSelector" data-interval="<?=$dateSection[0]?>">오늘</button>
                            <button type="button" class="btn btn-md btn-inverse dateinterval mobileappDateSelector" data-interval="<?=$dateSection[1]?>">1주일</button>
                            <button type="button" class="btn btn-md btn-inverse dateinterval mobileappDateSelector" data-interval="<?=$dateSection[2]?>">15일</button>
                            <button type="button" class="btn btn-md btn-inverse dateinterval mobileappDateSelector" data-interval="<?=$dateSection[3]?>">한달</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="form-group selectbox" style="background-color:#fff;">
                            <label for="select-opt"> =결제방법= </label>
                            <?= gd_select_box('settleKind', 'settleKind', $search['settleKind'], null, '', null, 'style="background-color:#fff;"', 'form-control input-sm mgb5 select-opt'); ?>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>

            <div class="text-center mgb20 border-r-n oList-search-btn-area">
                <input type="submit" value="검색" class="btn_submit btn btn-block-app btn-info oList-search-btn" id="mobileapp_search"/>
            </div>
    </div>

    <div class="container-default overflow-h">
        <div class="row">
            <div class="col-xs-5 form-control-static">
                <i class="icon-search"></i> 총 <strong class="text-primary"><span id="mobileapp_totalCount">0</span>건</strong> 검색
            </div>

            <div class="col-xs-7 text-right form-inline">
                <?= gd_mobileapp_select_box_by_page_view_count(10, null, null, 'form-control input-sm needsclick list-select-sort'); ?>
            </div>
        </div>

        <table class="table-order">
            <colgroup>
                <col>
                <col style="width:10%;">
                <col style="width:30%;">
            </colgroup>
            <tbody class="rowlink" id="mobileapp_orderListArea">
            </tbody>
        </table>
        <center id="moreDisplay"><a id="mobileapp_moreOrderList" class="btn btn-lg btn-block-app btn-default-gray border-r-n oList-more-btn" href="javascript:;">더보기</a></center>
        <input type="hidden" id="nowCount" value="0">
    </div>
</div>
