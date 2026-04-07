<div class='table-title'>상품 검색</div>
<form name='sfrm' method='get'>
  <table class='table table-cols'>
    <colgroup>
        <col class="width-md"/>
        <col>
        <col class="width-md"/>
        <col/>
    </colgroup>
    <tbody>
      <tr>
          <th>검색어</th>
          <td>
              <div class="form-inline">
                  <select class="form-control " id="sopt" name="sopt">
                    <option value="goodsNm"<?php if ($search['sopt'] == 'goodsNm') echo " selected";?>>상품명</option>
                    <option value="goodsNo"<?php if ($search['sopt'] == 'goodsNo') echo " selected";?>>상품코드</option>
                    <option value="goodsCd"<?php if ($search['sopt'] == 'goodsCd') echo " selected";?>>자체상품코드</option>
                    <option value="goodsSearchWord"<?php if ($search['sopt'] == 'goodsSearchWord') echo " selected";?>>검색 키워드</option>
                    <option value="__disable1" disabled="">==========</option>
                    <option value="makerNm"<?php if ($search['sopt'] == 'makerNm') echo " selected";?>>제조사</option>
                    <option value="originNm"<?php if ($search['sopt'] == 'originNm') echo " selected";?>>원산지</option>
                    <option value="goodsModelNo"<?php if ($search['sopt'] == 'goodsModelNo') echo " selected";?>>모델번호</option>
                    <option value="hscode"<?php if ($search['sopt'] == 'hscode') echo " selected";?>>HS코드</option>
                    <option value="__disable2" disabled="">==========</option>
                    <option value="memo"<?php if ($search['sopt'] == 'memo') echo " selected";?>>관리자 메모</option>
                    <option value="companyNm"<?php if ($search['sopt'] == 'companyNm') echo " selected";?>>공급사명</option>
                  </select>
                  <input type="text" name="skey" value="<?=$search['skey']; ?>" class="form-control">
                  &nbsp;&nbsp;
                  <input type='checkbox' name='isSubscription' value='1'<?php if ($search['isSubscription']) echo " checked";?>>정기결제 상품만 
              </div>
          </td>
      </tr>
      <tr>
        <th>기간검색</th>
        <td>
            <div class="form-inline">
              <select name="searchDateFl" class="form-control">
                <option value="regDt" <?=gd_isset($selected['searchDateFl']['regDt']); ?>>등록일</option>
                <option value="modDt" <?=gd_isset($selected['searchDateFl']['modDt']); ?>>수정일</option>
              </select>

              <div class="input-group js-datepicker">
                <input type="text" class="form-control width-xs" name="searchDate[]" value="<?=$search['searchDate'][0]; ?>" />
                <span class="input-group-addon"><span class="btn-icon-calendar"></span></span>
              </div>
              ~
              <div class="input-group js-datepicker">
                <input type="text" class="form-control width-xs" name="searchDate[]" value="<?=$search['searchDate'][1]; ?>" />
                <span class="input-group-addon"><span class="btn-icon-calendar"></span></span>
              </div>
              <?= gd_search_date($search['searchPeriod']) ?>
            </div>
          </td>
      </tr>
      <tr>
        <th>카테고리</th>
        <td class="contents">
          <div class="form-inline">
          <?=$cate->getMultiCategoryBox(null, $search['cateGoods']); ?>
          </div>
        </td>
      </tr>
    </tbody>
  </table>
  <div class='center'><input type='submit' value='검색하기' class='btn btn-lg btn-black'></div>
</form>
