<div class="mobileapp-member-list">
    <input type="hidden" name="standardDate" id="mobileapp_standardDate" value="<?=$dateSection[0]?>" />

    <h2 class="section-header">
        <div class="row">
            <div class="col-xs-6">
                회원검색
            </div>
            <div class="col-xs-6 text-right">
                <a id="mobileapp_resetBtn" class="btn btn-sm btn-default border-r-n mList-resetBtn" href="javascript:;">초기화</a>
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
                        <div class="col-xs-4 mList-padding0">
                            <div class="form-group selectbox">
                                <label for="select-opt">통합검색</label>
                                <?= gd_select_box('mobileapp_key', 'key', $combineSearch, null, '', null, null, 'form-control input-sm mgb5 select-opt'); ?>
                            </div>
                        </div>
                        <div class="col-xs-8 mList-lpadding5">
                            <input type="text" name="keyword" id="mobileapp_keyword" value="" class="form-control input-sm"/>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="form-group selectbox">
                            <label for="select-opt">=회원등급선택=</label>
                            <?= gd_mobileapp_select_box_by_group_list(null, '=회원등급선택=', null, 'form-control input-sm mgb5 select-opt'); ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="form-inline">
                        <div class="mList-entryDtArea">
                            <div> <input type="number" pattern="\d*" class="form-control input-sm" placeholder="회원가입일" name="entryDt[]" id="mobileapp_entryDt1" value="" /></div>
                            <div>-</div>
                            <div><input type="number" pattern="\d*" class="form-control input-sm" placeholder="회원가입일" name="entryDt[]" id="mobileapp_entryDt2" value="" /></div>
                        </div>
                        <div class="mList-searchDateBtnArea">
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
                </tbody>
            </table>

            <div class="text-center mgb20 border-r-n mList-search-btn-area">
                <input type="submit" value="검&nbsp;색" class="btn_submit btn btn-block-app btn-info mList-search-btn" id="mobileapp_search"/>
            </div>
    </div>

    <div class="container-default overflow-h">
        <div class="row">
            <div class="col-xs-6 form-control-static">
                <i class="icon-search"></i> 총 <strong class="text-primary"><span id="mobileapp_totalCount">0</span>건</strong> 검색
            </div>

            <div class="col-xs-6 text-right form-inline">
                <?= gd_mobileapp_select_box_by_page_view_count(10, null, null, 'form-control input-sm needsclick list-select-sort'); ?>
            </div>
        </div>

        <table class="table table-bordered table-condensed table2">
            <colgroup>
                <col>
                <col style="width:30%;">
                <col style="width:22%;">
            </colgroup>
            <thead>
            <tr>
                <th>이름/아이디</th>
                <th>등급</th>
                <th>가입일</th>
            </tr>
            </thead>
            <tbody class="rowlink" id="mobileapp_memberListArea">
            </tbody>
        </table>
        <center><a id="mobileapp_moreMemberList" class="btn btn-lg btn-block-app btn-default-gray border-r-n mList-more-btn" href="javascript:;">더보기</a></center>
    </div>
</div>
