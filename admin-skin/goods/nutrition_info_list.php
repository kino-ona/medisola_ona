<style>
.ni-wrap { overflow-x: auto; }
.ni-table { table-layout: fixed; border-collapse: collapse; width: max-content; min-width: 100%; }
.ni-table th, .ni-table td { border: 1px solid #ddd; padding: 2px 4px; font-size: 12px; vertical-align: middle; white-space: nowrap; }
.ni-table thead th { background: #f5f5f5; position: sticky; top: 0; z-index: 2; text-align: center; font-weight: bold; }
.ni-table thead th .unit { font-weight: normal; color: #999; font-size: 10px; display: block; }
.ni-table .col-no { width: 50px; text-align: center; position: sticky; left: 0; background: #fff; z-index: 3; }
.ni-table .col-cd { width: 80px; position: sticky; left: 50px; background: #fff; z-index: 3; }
.ni-table .col-nm { width: 200px; overflow: hidden; text-overflow: ellipsis; position: sticky; left: 130px; background: #fff; z-index: 3; }
.ni-table thead th.col-no,
.ni-table thead th.col-cd,
.ni-table thead th.col-nm { z-index: 4; background: #f5f5f5; }
.ni-table tbody tr:hover .col-no,
.ni-table tbody tr:hover .col-cd,
.ni-table tbody tr:hover .col-nm { background: #f9f9f9; }
.ni-table .col-num { width: 70px; }
.ni-table .col-txt { width: 120px; }
.ni-table .col-desc { width: 180px; }
.ni-table .col-sel { width: 180px; }
.ni-table input[type="number"],
.ni-table input[type="text"],
.ni-table select:not([multiple]) { width: 100%; border: 1px solid transparent; background: transparent; padding: 2px 4px; font-size: 12px; box-sizing: border-box; outline: none; }
.ni-table input[type="number"] { text-align: right; }
.ni-table input:focus, .ni-table select:not([multiple]):focus { border-color: #2563EB; background: #fff; }
.ni-table td.modified { background: #FFFDE7 !important; }
.ni-table td.modified input, .ni-table td.modified select { background: #FFFDE7; }
.ni-table tbody tr:hover { background: #f9f9f9; }
.ni-toolbar { margin: 10px 0; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; display: flex; align-items: center; gap: 15px; }
.ni-toolbar .changed-count { font-weight: bold; color: #E53E3E; }
.ni-toolbar .btn-save { background: #2563EB; color: #fff; border: none; padding: 6px 20px; border-radius: 4px; cursor: pointer; font-size: 13px; }
.ni-toolbar .btn-save:hover { background: #1D4ED8; }
.ni-toolbar .btn-save:disabled { background: #ccc; cursor: not-allowed; }
.ni-save-status { margin-left: auto; font-size: 12px; color: #666; }

/* 카테고리 선택기 (체크박스 + 칩) */
.category-selector { position: relative; min-height: 40px; }
.category-chips { min-height: 28px; display: flex; flex-wrap: wrap; gap: 4px; padding: 4px; background: #fff; border: 1px solid #ddd; border-radius: 3px; cursor: pointer; }
.category-chips:hover { border-color: #2563EB; }
.category-chips.empty:before { content: '카테고리 선택...'; color: #999; font-size: 12px; }
.category-chip { display: inline-flex; align-items: center; gap: 4px; padding: 2px 6px; background: #2563EB; color: #fff; border-radius: 3px; font-size: 11px; white-space: nowrap; }
.category-chip .remove { cursor: pointer; font-weight: bold; opacity: 0.8; }
.category-chip .remove:hover { opacity: 1; }
.category-dropdown { display: none; position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #2563EB; border-radius: 3px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); z-index: 10; max-height: 200px; overflow-y: auto; }
.category-dropdown.active { display: block; }
.category-dropdown label { display: block; padding: 6px 10px; cursor: pointer; font-size: 12px; }
.category-dropdown label:hover { background: #f0f7ff; }
.category-dropdown input[type="checkbox"] { margin-right: 6px; }
</style>

<div class="page-header js-affix">
    <h3><?=end($naviMenu->location); ?></h3>
</div>

<!-- 검색 폼 -->
<form id="frmSearchNutrition" method="get" class="js-form-enter-submit">
    <div class="table-title gd-help-manual">영양 정보 검색</div>
    <div class="search-detail-box">
        <table class="table table-cols">
            <colgroup>
                <col class="width-md"/>
                <col/>
                <col class="width-md"/>
                <col/>
            </colgroup>
            <tbody>
            <tr>
                <th>상품 메뉴 필터</th>
                <td colspan="3">
                    <div class="form-inline">
                        <input type="text" name="goodsNo" value="<?=gd_isset($search['goodsNo'], '1000000445')?>" class="form-control width-sm" placeholder="상품번호 (goodsNo)"/>
                        <span class="text-muted" style="font-size:12px;">해당 상품의 추가상품 "메뉴" 항목만 표시</span>
                    </div>
                </td>
            </tr>
            <tr>
                <th>검색어</th>
                <td colspan="3">
                    <div class="form-inline">
                        <select name="key" class="form-control">
                            <?php foreach ($searchKeyOptions as $k => $v) { ?>
                                <option value="<?=$k?>" <?=($search['key'] ?? '') === $k ? "selected='selected'" : ''?>><?=$v?></option>
                            <?php } ?>
                        </select>
                        <input type="text" name="keyword" value="<?=gd_isset($search['keyword'])?>" class="form-control" placeholder="상품명 또는 상품코드"/>
                    </div>
                </td>
            </tr>
            <tr>
                <th>카테고리</th>
                <td>
                    <select name="category" class="form-control">
                        <?php foreach ($categoryOptions as $k => $v) { ?>
                            <option value="<?=$k?>" <?=($search['category'] ?? '') === $k ? "selected='selected'" : ''?>><?=$v?></option>
                        <?php } ?>
                    </select>
                </td>
                <th>음식 스타일</th>
                <td>
                    <select name="foodStyle" class="form-control">
                        <?php foreach ($foodStyleOptions as $k => $v) { ?>
                            <option value="<?=$k?>" <?=($search['foodStyle'] ?? '') === $k ? "selected='selected'" : ''?>><?=$v?></option>
                        <?php } ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th>질환케어</th>
                <td colspan="3">
                    <input type="text" name="diseaseType" value="<?=gd_isset($search['diseaseType'])?>" class="form-control width-sm" placeholder="예: 당뇨, 고혈압"/>
                </td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="table-btn">
        <input type="submit" value="검색" class="btn btn-lg btn-black"/>
        <input type="button" value="초기화" class="btn btn-lg btn-white" onclick="location.href='./nutrition_info_list.php'"/>
    </div>

    <div class="table-header">
        <div class="pull-left">
            검색 <strong><?=number_format($page->recode['total'])?></strong>개 /
            전체 <strong><?=number_format($page->recode['amount'])?></strong>개
        </div>
        <div class="pull-right form-inline">
            <?=gd_select_box('pageNum', 'pageNum', gd_array_change_key_value([20, 50, 100, 200, 500]), '개 보기', Request::get()->get('pageNum') ?: 50, null); ?>
        </div>
    </div>
</form>

<!-- 툴바 -->
<div class="ni-toolbar">
    <span>변경된 항목: <span class="changed-count" id="changedCount">0</span>개</span>
    <button type="button" class="btn-save" id="btnBulkSave" disabled>변경사항 저장</button>
    <span class="ni-save-status" id="saveStatus"></span>
</div>

<!-- 스프레드시트 테이블 -->
<div class="ni-wrap">
    <table class="ni-table" id="nutritionTable">
        <thead>
        <tr>
            <th class="col-no">No</th>
            <th class="col-cd">상품코드</th>
            <th class="col-nm">상품명</th>
            <th class="col-num">칼로리<span class="unit">kcal</span></th>
            <th class="col-num">단백질<span class="unit">g</span></th>
            <th class="col-num">탄수화물<span class="unit">g</span></th>
            <th class="col-num">당<span class="unit">g</span></th>
            <th class="col-num">지방<span class="unit">g</span></th>
            <th class="col-num">포화지방<span class="unit">g</span></th>
            <th class="col-num">트랜스지방<span class="unit">g</span></th>
            <th class="col-num">나트륨<span class="unit">mg</span></th>
            <th class="col-num">오메가3<span class="unit">mg</span></th>
            <th class="col-num">콜레스테롤<span class="unit">mg</span></th>
            <th class="col-num">식이섬유<span class="unit">g</span></th>
            <th class="col-num">중량<span class="unit">g</span></th>
            <th class="col-txt">질환케어</th>
            <th class="col-txt">영양태그</th>
            <th class="col-sel">카테고리</th>
            <th class="col-sel">음식스타일</th>
            <th class="col-sel">메뉴타입</th>
            <th class="col-txt">주재료</th>
            <th class="col-txt">알러지</th>
            <th class="col-desc">설명</th>
        </tr>
        </thead>
        <tbody>
        <?php
        if (gd_isset($data)) {
            $idx = $page->idx;
            foreach ($data as $row) {
        ?>
            <tr data-sno="<?=$row['addGoodsNo']?>">
                <td class="col-no"><?=number_format($idx--)?></td>
                <td class="col-cd" title="<?=gd_isset($row['goodsCd'])?>"><?=gd_isset($row['goodsCd'])?></td>
                <td class="col-nm" title="<?=gd_isset($row['goodsNm'])?>"><?=gd_isset($row['goodsNm'])?></td>
                <td class="col-num"><input type="number" step="0.1" data-field="nutrition_calories" data-orig="<?=gd_isset($row['nutrition_calories'])?>" value="<?=gd_isset($row['nutrition_calories'])?>"/></td>
                <td class="col-num"><input type="number" step="0.1" data-field="nutrition_protein" data-orig="<?=gd_isset($row['nutrition_protein'])?>" value="<?=gd_isset($row['nutrition_protein'])?>"/></td>
                <td class="col-num"><input type="number" step="0.1" data-field="nutrition_carbs" data-orig="<?=gd_isset($row['nutrition_carbs'])?>" value="<?=gd_isset($row['nutrition_carbs'])?>"/></td>
                <td class="col-num"><input type="number" step="0.1" data-field="nutrition_sugar" data-orig="<?=gd_isset($row['nutrition_sugar'])?>" value="<?=gd_isset($row['nutrition_sugar'])?>"/></td>
                <td class="col-num"><input type="number" step="0.1" data-field="nutrition_fat" data-orig="<?=gd_isset($row['nutrition_fat'])?>" value="<?=gd_isset($row['nutrition_fat'])?>"/></td>
                <td class="col-num"><input type="number" step="0.1" data-field="nutrition_saturated_fat" data-orig="<?=gd_isset($row['nutrition_saturated_fat'])?>" value="<?=gd_isset($row['nutrition_saturated_fat'])?>"/></td>
                <td class="col-num"><input type="number" step="0.1" data-field="nutrition_trans_fat" data-orig="<?=gd_isset($row['nutrition_trans_fat'])?>" value="<?=gd_isset($row['nutrition_trans_fat'])?>"/></td>
                <td class="col-num"><input type="number" step="0.1" data-field="nutrition_sodium" data-orig="<?=gd_isset($row['nutrition_sodium'])?>" value="<?=gd_isset($row['nutrition_sodium'])?>"/></td>
                <td class="col-num"><input type="number" step="0.1" data-field="nutrition_omega3" data-orig="<?=gd_isset($row['nutrition_omega3'])?>" value="<?=gd_isset($row['nutrition_omega3'])?>"/></td>
                <td class="col-num"><input type="number" step="0.1" data-field="nutrition_cholesterol" data-orig="<?=gd_isset($row['nutrition_cholesterol'])?>" value="<?=gd_isset($row['nutrition_cholesterol'])?>"/></td>
                <td class="col-num"><input type="number" step="0.1" data-field="nutrition_fiber" data-orig="<?=gd_isset($row['nutrition_fiber'])?>" value="<?=gd_isset($row['nutrition_fiber'])?>"/></td>
                <td class="col-num"><input type="number" step="0.1" data-field="product_weight" data-orig="<?=gd_isset($row['product_weight'])?>" value="<?=gd_isset($row['product_weight'])?>"/></td>
                <td class="col-txt"><input type="text" data-field="disease_type" data-orig="<?=gd_isset($row['disease_type'])?>" value="<?=gd_isset($row['disease_type'])?>" placeholder="콤마 구분"/></td>
                <td class="col-txt"><input type="text" data-field="nutrition_tags" data-orig="<?=gd_isset($row['nutrition_tags'])?>" value="<?=gd_isset($row['nutrition_tags'])?>" placeholder="콤마 구분"/></td>
                <td class="col-sel">
                    <div class="category-selector">
                        <div class="category-chips empty" tabindex="0"></div>
                        <div class="category-dropdown">
                            <label><input type="checkbox" value="해산물"> 해산물</label>
                            <label><input type="checkbox" value="육류"> 육류</label>
                            <label><input type="checkbox" value="식단백"> 식단백</label>
                            <label><input type="checkbox" value="샐러드"> 샐러드</label>
                            <label><input type="checkbox" value="국/찌개"> 국/찌개</label>
                            <label><input type="checkbox" value="반찬"> 반찬</label>
                            <label><input type="checkbox" value="간식"> 간식</label>
                            <label><input type="checkbox" value="음료"> 음료</label>
                        </div>
                        <select data-field="category" data-orig="<?=gd_isset($row['category'])?>" multiple style="display:none;">
                            <option value="해산물">해산물</option>
                            <option value="육류">육류</option>
                            <option value="식단백">식단백</option>
                            <option value="샐러드">샐러드</option>
                            <option value="국/찌개">국/찌개</option>
                            <option value="반찬">반찬</option>
                            <option value="간식">간식</option>
                            <option value="음료">음료</option>
                        </select>
                    </div>
                </td>
                <td class="col-sel">
                    <select data-field="food_style" data-orig="<?=gd_isset($row['food_style'])?>">
                        <option value="">-</option>
                        <option value="한식" <?=gd_isset($row['food_style'])==='한식'?"selected":""?>>한식</option>
                        <option value="양식" <?=gd_isset($row['food_style'])==='양식'?"selected":""?>>양식</option>
                        <option value="일식" <?=gd_isset($row['food_style'])==='일식'?"selected":""?>>일식</option>
                        <option value="중식" <?=gd_isset($row['food_style'])==='중식'?"selected":""?>>중식</option>
                        <option value="에스닉" <?=gd_isset($row['food_style'])==='에스닉'?"selected":""?>>에스닉</option>
                    </select>
                </td>
                <td class="col-sel">
                    <select data-field="meal_type" data-orig="<?=gd_isset($row['meal_type'])?>">
                        <option value="">-</option>
                        <option value="밥류" <?=gd_isset($row['meal_type'])==='밥류'?"selected":""?>>밥류</option>
                        <option value="덮밥" <?=gd_isset($row['meal_type'])==='덮밥'?"selected":""?>>덮밥</option>
                        <option value="면류" <?=gd_isset($row['meal_type'])==='면류'?"selected":""?>>면류</option>
                        <option value="파스타" <?=gd_isset($row['meal_type'])==='파스타'?"selected":""?>>파스타</option>
                        <option value="스테이크" <?=gd_isset($row['meal_type'])==='스테이크'?"selected":""?>>스테이크</option>
                        <option value="베이글" <?=gd_isset($row['meal_type'])==='베이글'?"selected":""?>>베이글</option>
                    </select>
                </td>
                <td class="col-txt"><input type="text" data-field="main_ingredients" data-orig="<?=gd_isset($row['main_ingredients_display'])?>" value="<?=gd_isset($row['main_ingredients_display'])?>" placeholder="콤마 구분"/></td>
                <td class="col-txt"><input type="text" data-field="allergens" data-orig="<?=gd_isset($row['allergens_display'])?>" value="<?=gd_isset($row['allergens_display'])?>" placeholder="콤마 구분"/></td>
                <td class="col-desc"><input type="text" data-field="description" data-orig="<?=gd_isset($row['description'])?>" value="<?=gd_isset($row['description'])?>"/></td>
            </tr>
        <?php
            }
        } else {
        ?>
            <tr>
                <td colspan="23" style="text-align:center; padding:30px;">검색된 정보가 없습니다.</td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<!-- 페이지네이션 -->
<div class="center"><?=$page->getPage()?></div>

<script type="text/javascript">
$(document).ready(function() {
    var changedRows = {};

    // pageNum 변경 시 폼 제출
    $('select[name="pageNum"]').change(function() {
        var form = $('#frmSearchNutrition');
        if (form.find('input[name="pageNum"]').length === 0) {
            form.append('<input type="hidden" name="pageNum"/>');
        }
        form.find('input[name="pageNum"]').val($(this).val());
        form.submit();
    });

    // 셀 값 변경 감지
    $('#nutritionTable').on('change input', 'input, select', function() {
        var $el = $(this);
        var $td = $el.closest('td');
        var $tr = $el.closest('tr');
        var sno = $tr.data('sno');
        var field = $el.data('field');
        var origVal = ($el.data('orig') !== undefined && $el.data('orig') !== null) ? String($el.data('orig')) : '';

        // Multi-select 처리: 배열 → 콤마 구분 문자열로 변환
        var newVal;
        if ($el.is('select[multiple]')) {
            var selectedVals = $el.val() || [];
            newVal = selectedVals.join(',');
        } else {
            newVal = String($el.val());
        }

        if (newVal !== origVal) {
            $td.addClass('modified');
            if (!changedRows[sno]) {
                changedRows[sno] = {};
            }
            changedRows[sno][field] = newVal;
        } else {
            $td.removeClass('modified');
            if (changedRows[sno]) {
                delete changedRows[sno][field];
                if (Object.keys(changedRows[sno]).length === 0) {
                    delete changedRows[sno];
                }
            }
        }

        updateChangedCount();
    });

    function updateChangedCount() {
        var count = Object.keys(changedRows).length;
        $('#changedCount').text(count);
        $('#btnBulkSave').prop('disabled', count === 0);
    }

    // 일괄 저장
    $('#btnBulkSave').click(function() {
        var count = Object.keys(changedRows).length;
        if (count === 0) return;

        if (!confirm(count + '개 상품의 영양 정보를 저장하시겠습니까?')) return;

        var rows = [];
        $.each(changedRows, function(sno, fields) {
            var row = { addGoodsNo: sno };
            $.extend(row, fields);
            rows.push(row);
        });

        $('#btnBulkSave').prop('disabled', true);
        $('#saveStatus').text('저장 중...');

        $.ajax({
            url: './nutrition_info_ps.php',
            type: 'POST',
            data: {
                mode: 'bulkSave',
                rows: JSON.stringify(rows)
            },
            dataType: 'json',
            success: function(res) {
                if (res.code === 200) {
                    $.each(changedRows, function(sno, fields) {
                        var $tr = $('#nutritionTable tr[data-sno="' + sno + '"]');
                        $.each(fields, function(field, val) {
                            var $el = $tr.find('[data-field="' + field + '"]');
                            $el.data('orig', val);
                            $el.closest('td').removeClass('modified');
                        });
                    });
                    changedRows = {};
                    updateChangedCount();
                    $('#saveStatus').text(res.message);
                    setTimeout(function() { $('#saveStatus').text(''); }, 3000);
                } else {
                    alert(res.message || '저장 중 오류가 발생했습니다.');
                    $('#saveStatus').text('');
                }
                $('#btnBulkSave').prop('disabled', Object.keys(changedRows).length === 0);
            },
            error: function() {
                alert('서버 통신 오류가 발생했습니다.');
                $('#saveStatus').text('');
                $('#btnBulkSave').prop('disabled', false);
            }
        });
    });

    // 페이지 이탈 경고
    $(window).on('beforeunload', function() {
        if (Object.keys(changedRows).length > 0) {
            return '저장하지 않은 변경사항이 있습니다. 페이지를 벗어나시겠습니까?';
        }
    });

    // Ctrl+S 단축키로 저장
    $(document).on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            if (Object.keys(changedRows).length > 0) {
                $('#btnBulkSave').click();
            }
        }
    });

    // Excel 붙여넣기: 포커스된 셀부터 탭/줄바꿈 기준으로 여러 셀에 값 채우기
    $('#nutritionTable').on('paste', 'input, select', function(e) {
        var clipData = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
        if (!clipData) return;

        // 탭이나 줄바꿈이 포함된 경우만 멀티셀 붙여넣기 처리
        if (clipData.indexOf('\t') === -1 && clipData.indexOf('\n') === -1) return;

        e.preventDefault();

        var rows = clipData.replace(/\r\n/g, '\n').replace(/\r/g, '\n').replace(/\n$/, '').split('\n');
        var $startTd = $(this).closest('td');
        var $startTr = $startTd.closest('tr');
        var startColIdx = $startTd.index();

        // 편집 가능한 td 목록 (col-no, col-cd, col-nm 제외 = index 3부터)
        var $allRows = $('#nutritionTable tbody tr[data-sno]');
        var startRowIdx = $allRows.index($startTr);

        for (var r = 0; r < rows.length; r++) {
            var rowIdx = startRowIdx + r;
            if (rowIdx >= $allRows.length) break;

            var $tr = $allRows.eq(rowIdx);
            var cols = rows[r].split('\t');

            for (var c = 0; c < cols.length; c++) {
                var colIdx = startColIdx + c;
                var $td = $tr.children('td').eq(colIdx);
                if ($td.length === 0) break;

                var $input = $td.find('input, select');
                if ($input.length === 0) continue;

                var val = $.trim(cols[c]);

                if ($input.is('select')) {
                    // select의 경우 option text 또는 value로 매칭
                    var matched = false;
                    $input.find('option').each(function() {
                        if ($(this).val() === val || $(this).text() === val) {
                            $input.val($(this).val());
                            matched = true;
                            return false;
                        }
                    });
                    if (!matched) continue;
                } else {
                    $input.val(val);
                }

                // 변경 감지 트리거
                $input.trigger('change');
            }
        }
    });

    // 카테고리 선택기 초기화 (체크박스 + 칩 방식)
    function initCategorySelector($selector) {
        var $chips = $selector.find('.category-chips');
        var $dropdown = $selector.find('.category-dropdown');
        var $select = $selector.find('select[data-field="category"]');
        var $checkboxes = $dropdown.find('input[type="checkbox"]');

        // 초기값 설정
        var origVal = $select.data('orig') || '';
        if (origVal) {
            var selectedValues = origVal.split(',').map(function(v) { return v.trim(); });
            $checkboxes.each(function() {
                if (selectedValues.indexOf($(this).val()) !== -1) {
                    $(this).prop('checked', true);
                }
            });
            $select.val(selectedValues);
            renderChips();
        }

        // 칩 렌더링
        function renderChips() {
            var selected = [];
            $checkboxes.filter(':checked').each(function() {
                selected.push($(this).val());
            });

            if (selected.length === 0) {
                $chips.addClass('empty').html('');
            } else {
                $chips.removeClass('empty').empty();
                selected.forEach(function(val) {
                    var $chip = $('<span class="category-chip">')
                        .append($('<span>').text(val))
                        .append($('<span class="remove">').html('&times;').data('value', val));
                    $chips.append($chip);
                });
            }
        }

        // 칩 영역 클릭 → 드롭다운 토글
        $chips.on('click', function(e) {
            if (!$(e.target).hasClass('remove')) {
                $dropdown.toggleClass('active');
            }
        });

        // 칩 제거 버튼
        $chips.on('click', '.remove', function(e) {
            e.stopPropagation();
            var val = $(this).data('value');
            $checkboxes.filter('[value="' + val + '"]').prop('checked', false).trigger('change');
        });

        // 체크박스 변경 → 칩 + select 업데이트
        $checkboxes.on('change', function() {
            var selected = [];
            $checkboxes.filter(':checked').each(function() {
                selected.push($(this).val());
            });
            $select.val(selected).trigger('change');
            renderChips();
        });

        // 외부 클릭 시 드롭다운 닫기
        $(document).on('click', function(e) {
            if (!$selector[0].contains(e.target)) {
                $dropdown.removeClass('active');
            }
        });
    }

    // 모든 카테고리 선택기 초기화
    $('#nutritionTable .category-selector').each(function() {
        initCategorySelector($(this));
    });
});
</script>
