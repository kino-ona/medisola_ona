<div class="mobileapp-statistics">
    <input type="hidden" name="mode" id="mobileapp_mode" value="sale" />
    <input type="hidden" name="standardDate" id="mobileapp_standardDate" value="<?=$dateSection[0]?>" />

    <h2 class="section-header">
        <div class="row">
            <div class="col-xs-12">판매순위분석</div>
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
                    <td class="form-inline">
                        <div class="statistics-searchDateArea">
                            <div><input type="number" pattern="\d*" class="form-control input-sm" name="searchDate[]" id="mobileapp_searchDate1" value="<?=$dateSection[1]?>" /></div>
                            <div>-</div>
                            <div><input type="number" pattern="\d*" class="form-control input-sm" name="searchDate[]" id="mobileapp_searchDate2" value="<?=$dateSection[0]?>" /></div>
                        </div>
                        <button type="button" class="btn btn-md btn-inverse dateinterval mobileappDateSelector" data-interval="<?=$dateSection[1]?>">1주일</button>
                        <button type="button" class="btn btn-md btn-inverse dateinterval mobileappDateSelector" data-interval="<?=$dateSection[2]?>">15일</button>
                        <button type="button" class="btn btn-md btn-inverse dateinterval mobileappDateSelector" data-interval="<?=$dateSection[3]?>">한달</button>
                    </td>
                </tr>
                </tbody>
            </table>

            <div class="text-center mgb20 border-r-n statistics-search-btn-area">
                <input type="submit" value="검&nbsp;색" class="btn_submit btn btn-block-app btn-info statistics-search-btn" id="mobileapp_search"/>
            </div>
    </div>

    <div class="container-default overflow-h">
        <div class="row statistics-countArea">
            <div class="col-xs-5 form-control-static">
                <i class="icon-search"></i> 총 <strong class="text-primary"><span id="mobileapp_totalCount">0</span>건</strong> 검색
            </div>

            <div class="col-xs-7 text-right form-inline">
                <?= gd_mobileapp_select_box_by_page_view_count(10, null, null, 'form-control input-sm needsclick list-select-sort'); ?>
            </div>
        </div>

        <table class="table table-bordered table-condensed table2 statistics-listArea">
            <colgroup>
                <col style="width:15%;" />
                <col />
                <col style="width:20%;" />
                <col style="width:20%;" />
            </colgroup>
            <thead>
            <tr>
                <th>순위</th>
                <th>상품명</th>
                <th>매출금액</th>
                <th>구매수량</th>
            </tr>
            </thead>
            <tbody class="rowlink" id="mobileapp_listArea">
            </tbody>
        </table>
        <center><a id="mobileapp_moreBtn" class="btn btn-lg btn-block-app btn-default-gray border-r-n statistics-more-btn" href="javascript:;">더보기</a></center>
    </div>
</div>
