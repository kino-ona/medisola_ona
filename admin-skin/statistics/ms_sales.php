<div class="page-header js-affix">
  <h3><?php echo end($naviMenu->location); ?></h3>
</div>

<?php
$columns = [];
foreach ($data[0] as $key => $val) {
  // skip if the key is starting with _
  if (substr($key, 0, 1) == '_') {
    continue;
  }
  $columns[] = "{title : '<b>" . $key . "</b>', columnName : '" . $key . "', align : 'right', width : 100, editOption: {type: 'normal'}}";
}
?>
<form id="frmSearchBase" method="get">
  <div class="table-title gd-help-manual">집계 조건 설정 <span class="notice-danger">데이터는 2시간마다 집계되므로 주문데이터와 약 1시간~2시간의 데이터 오차가 있을 수 있습니다.</span></div>
  <table class="table table-cols">
    <colgroup>
      <col class="width-md" />
      <col />
    </colgroup>
    <tbody>
      <tr>
        <th>기간검색</th>
        <td>
          <div class="form-inline">
            <div class="input-group js-datepicker">
              <input type="text" class="form-control width-xs" name="searchDate[]" value="<?= $searchDate[0]; ?>" />
              <span class="input-group-addon"><span class="btn-icon-calendar"></span></span>
            </div>
            ~
            <div class="input-group js-datepicker">
              <input type="text" class="form-control width-xs" name="searchDate[]" value="<?php echo $searchDate[1]; ?>" />
              <span class="input-group-addon"><span class="btn-icon-calendar"></span></span>
            </div>

            <div class="btn-group js-dateperiod-mixed" data-toggle="buttons" data-target-name="searchDate[]">
              <label class="btn btn-white btn-sm hand <?= $active['searchPeriod']['1']; ?>">
                <input type="radio" name="searchPeriod" value="1" <?= $checked['searchPeriod']['1']; ?>>전일
              </label>
              <label class="btn btn-white btn-sm hand <?= $active['searchPeriod']['7']; ?>">
                <input type="radio" name="searchPeriod" value="7" <?= $checked['searchPeriod']['7']; ?>>7일
              </label>
              <label class="btn btn-white btn-sm hand <?= $active['searchPeriod']['15']; ?>">
                <input type="radio" name="searchPeriod" value="15" <?= $checked['searchPeriod']['15']; ?>>15일
              </label>
              <label class="btn btn-white btn-sm hand <?= $active['searchPeriod']['30']; ?>">
                <input type="radio" name="searchPeriod" value="30" <?= $checked['searchPeriod']['30']; ?>>1개월
              </label>
              <label class="btn btn-white btn-sm hand <?= $active['searchPeriod']['90']; ?>">
                <input type="radio" name="searchPeriod" value="90" <?= $checked['searchPeriod']['90']; ?>>3개월
              </label>
              <label class="btn btn-white btn-sm hand <?= $active['searchPeriod']['180']; ?>">
                <input type="radio" name="searchPeriod" value="180" <?= $checked['searchPeriod']['180']; ?>>6개월
              </label>
              <label class="btn btn-white btn-sm hand <?= $active['searchPeriod']['365']; ?>">
                <input type="radio" name="searchPeriod" value="365" <?= $checked['searchPeriod']['365']; ?>>12개월
              </label>
            </div>
          </div>
        </td>
      </tr>
      <tr>
        <th>회원 구분</th>
        <td>
          <div class="checkbox">
            <input type="checkbox" name="splitFirstOrder" value="y" id="splitFirstOrder" <?= $checked['splitFirstOrder']; ?>>
            <label for="splitFirstOrder" style="padding-left: 0px;">첫주문/재주문 구분 (회원 주문만 해당)</label>
          </div>
        </td>
      </tr>
    </tbody>
  </table>
  <div class="table-btn">
    <input hidden name="tabName" value="<?= $tabName; ?>">
    <button type="submit" class="btn btn-lg btn-black">검색</button>
  </div>
</form>

<ul class="nav nav-tabs mgb30" role="tablist">
  <li role="presentation" <?= $tabName == 'member' ? 'class="active"' : '' ?>>
    <a href="../statistics/ms_sales.php<?= $queryString ?>tabName=member">회원구분별 집계</a>
  </li>
  <li role="presentation" <?= $tabName == 'week' ? 'class="active"' : '' ?>>
    <a href="../statistics/ms_sales.php<?= $queryString ?>tabName=week">N주식단별 집계</a>
  </li>
  <li role="presentation" <?= $tabName == 'goods' ? 'class="active"' : '' ?>>
    <a href="../statistics/ms_sales.php<?= $queryString ?>tabName=goods">상품별 집계</a>
  </li>
</ul>


<div class="table-action mgt30 mgb0">
  <div class="pull-right">
    <button type="button" class="btn btn-white btn-icon-excel btn-excel">엑셀 다운로드</button>
  </div>
</div>

<div class="code-html js-excel-data">
  <div id="grid"></div>
</div>

<script type="text/javascript" class="code-js">
  var grid = new tui.Grid({
    el: $('#grid'),
    autoNumbering: false,
    columnFixCount: 2,
    headerHeight: 40,
    displayRowCount: <?= $displayLimit; ?>,
    minimumColumnWidth: 20,
    columnModelList: [
      <?= implode(',', $columns); ?>
    ]
  });
  grid.setRowList(<?= json_encode($data); ?>);

  //    grid.use('Net', {
  //        el: $('#grid'),
  //        initialRequest: true,
  //        readDataMethod: 'GET',
  //        perPage: 500,
  //        enableAjaxHistory: true,
  //        api: {
  //            readData: '/sample',
  //            downloadExcel: '/download/excel',
  //            downloadExcelAll: '/download/excelAll'
  //        }
  //    });
  // 엑셀다운로드
  $('.btn-excel').click(function() {
    grid.setDisplayRowCount('<?= $count ?>');
    statistics_excel_download();
    grid.setDisplayRowCount('<?= $displayLimit; ?>');
  });
</script>
<script type="text/javascript" src="<?= PATH_ADMIN_GD_SHARE ?>script/statistics.js"></script>