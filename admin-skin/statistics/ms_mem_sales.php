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
                    <input type="text" class="form-control width-xs" name="searchDate[]" value="<?= $searchDate[0]; ?>"/>
                    <span class="input-group-addon"><span class="btn-icon-calendar"></span></span>
                </div>
                ~
                <div class="input-group js-datepicker">
                    <input type="text" class="form-control width-xs" name="searchDate[]" value="<?php echo $searchDate[1]; ?>"/>
                    <span class="input-group-addon"><span class="btn-icon-calendar"></span></span>
                </div>

                <div class="btn-group js-dateperiod-statistics" data-toggle="buttons" data-target-name="searchDate[]">
                    <label class="btn btn-white btn-sm hand <?= $active['searchPeriod']['1']; ?>">
                        <input type="radio" name="searchPeriod" value="1" <?= $checked['searchPeriod']['1']; ?> >전일
                    </label>
                    <label class="btn btn-white btn-sm hand <?= $active['searchPeriod']['7']; ?>">
                        <input type="radio" name="searchPeriod" value="7" <?= $checked['searchPeriod']['7']; ?> >7일
                    </label>
                    <label class="btn btn-white btn-sm hand <?= $active['searchPeriod']['15']; ?>">
                        <input type="radio" name="searchPeriod" value="15" <?= $checked['searchPeriod']['15']; ?> >15일
                    </label>
                    <label class="btn btn-white btn-sm hand <?= $active['searchPeriod']['30']; ?>">
                        <input type="radio" name="searchPeriod" value="30" <?= $checked['searchPeriod']['30']; ?> >1개월
                    </label>
                    <label class="btn btn-white btn-sm hand <?= $active['searchPeriod']['90']; ?>">
                        <input type="radio" name="searchPeriod" value="90" <?= $checked['searchPeriod']['90']; ?> >3개월
                    </label>
                </div>
            </div>
        </td>
      </tr>
      <tr>
            <th>N주차 식단</th>
            <td class="contents">
                <div class="form-inline">
                    <select id="nWeek" name="nWeek" class="form-control">
                        <option value="1" <?= $nWeek == 1 ? "selected" : "" ?>> 1 주 </option>
                        <option value="2" <?= $nWeek == 2 ? "selected" : "" ?>> 2 주 </option>
                        <option value="4" <?= $nWeek == 4 ? "selected" : "" ?>> 4 주 </option>
                        <option value="8" <?= $nWeek == 8 ? "selected" : "" ?>> 8 주 </option>
                    </select>
                    이상 식단만 집계
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
    <a href="../statistics/ms_mem_sales.php<?= $queryString ?>tabName=member">회원별</a>
  </li>
  <li role="presentation" <?= $tabName == 'contact' ? 'class="active"' : '' ?>>
    <a href="../statistics/ms_mem_sales.php<?= $queryString ?>tabName=contact">주문연락처별</a>
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

    // statistics.js를 참고해서 POST를 거치지 않고 js에서 바로 다운로드되도록 처리함.
    // POST 25MB 제한 우회와 POST시 데이터 교환 시간 제거하여 더 빠른 다운로드 사용자 경험 제공.
    try {
      let targetId = ".js-excel-data";
      let headHtml = "";
      let excelName = $(".nav-tabs .active").text().replace(/\s+/gu, '');
      let rsideRowspan = $(targetId).find(".tui-grid-rside-area .tui-grid-head-area table tbody tr").length;

      $(targetId).find(".tui-grid-rside-area .tui-grid-head-area table tbody tr").each(function() {
          let lsideHeadHtml = $(targetId).find(".tui-grid-lside-area .tui-grid-head-area table tbody tr").eq($(this).index()).html();
          if(lsideHeadHtml) {
              if(rsideRowspan > 1 && $(this).index() =='0') lsideHeadHtml = lsideHeadHtml.replace(/rowspan="1"/gi, "rowspan='"+rsideRowspan+"'");
              headHtml +="<tr>"+lsideHeadHtml+$(this).html()+"</tr>";
          }
          else headHtml +="<tr>"+$(this).html()+"</tr>";
      });

      let bodyHtml = "";
      $(targetId).find(".tui-grid-lside-area .tui-grid-body-area table tbody tr").each(function() {
          let rsideBodyHtml = $(targetId).find(".tui-grid-rside-area .tui-grid-body-area table tbody tr").eq($(this).index()).html();
          bodyHtml +="<tr>"+$(this).html()+rsideBodyHtml+"</tr>";
      });
      let html = "<style>td{mso-number-format:'\@';}</style><table border='1'>"+headHtml+bodyHtml+"</table>";
      
      let header = `
      <html xmlns="http://www.w3.org/1999/xhtml" lang="ko" xml:lang="ko">
      <head>
        <title>Excel Down</title>
        <meta http-equiv="Content-Type" content="text/html; charset=' . SET_CHARSET . '" />
        <style>
          br{mso-data-placement:same-cell;}
          .xl31{mso-number-format:"0_\)\;\\\(0\\\)";}
          .xl24{mso-number-format:"\@";}
          .title{font-weight:bold; background-color:#F6F6F6; text-align:center;}
        </style>
      </head>
      <body>
      `;

      let footer = `</body></html>`;
      let all = header + html + "</table>" + footer;

      const blob = new Blob([all], { type: "text/plain;charset=utf-8" });
      const url = URL.createObjectURL(blob);
      try {
        const a = document.createElement("a");
        a.href = url;
        a.download = `${excelName}.xls`;
        document.body.appendChild(a);
        a.click();
  
        document.body.removeChild(a);
      } finally {
        URL.revokeObjectURL(url);
      }
    } catch(e) {
      window.alert(e);
    }
    grid.setDisplayRowCount('<?= $displayLimit; ?>');
  });
</script>
<script type="text/javascript" src="<?= PATH_ADMIN_GD_SHARE ?>script/statistics.js"></script>