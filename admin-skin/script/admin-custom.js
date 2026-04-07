/**
 * 추가 스크립트 - 추가적인 javascript 는 여기에 작성을 해주세요.
 *
 */

// 기간설정 버튼 액션
function init_ets_date_time_picker() {
  if ($(".js-eta-dateperiod").length) {
    $(".js-eta-dateperiod label").click(function (e) {
      var startDate = "",
        endDate = "",
        period = $(this).children('input[type="radio"]').val(),
        elements = $("input[name*='" + $(this).closest(".js-eta-dateperiod").data("target-name") + "']"),
        inverse = $("input[name*='" + $(this).closest(".js-eta-dateperiod").data("target-inverse") + "']"),
        format = $(elements[0]).parent().data("DateTimePicker").format();

      var isInverse = period.startsWith('-') ? true : false;

      // 달력 일 기준 변경(관리자로그)
      if ($(this).data("type") == "calendar") {
        startDate = period.substring(0, 4) + "-" + period.substring(4, 6) + "-" + period.substring(6, 8);
        endDate = moment().format(format);
      } else {
        if (inverse.length) {
          period = "-" + period;
        }
        if (inverse.length || isInverse) {
          startDate = moment().hours(23).minutes(59).seconds(0).subtract(period, "days").format(format);
        } else {
          startDate = moment().hours(0).minutes(0).seconds(0).subtract(period, "days").format(format);
        }

        // 주문/배송 > 송장일괄등록 등록일 검색시 현재시간까지 검색
        if ($(".js-datetimepicker").length && $('input[name="searchPeriod"]').length) {
          endDate = moment().format(format);
        } else {
          endDate = moment().hours(0).minutes(0).seconds(0).format(format);
        }
      }
      
      if (inverse.length || isInverse) {
        $(elements[1]).val(startDate);
        $(elements[0]).val(moment(endDate).subtract(1, 'years').format(format));
      } else {
        $(elements[0]).val(startDate);
        $(elements[1]).val(endDate);
      }
    });

    // 버튼 활성 초기화
    $.each($(".js-eta-dateperiod"), function (idx) {
      const dateSpanInputs = $("input[name*='" + $(this).data("target-name") + "']"),
        format = $(dateSpanInputs[0]).parent().data("DateTimePicker").format();

      if ($(".js-datetimepicker").length && $('input[name="searchPeriod"]').length) {
        var now = moment().format(format);
      } else {
        var now = moment().hours(0).minutes(0).seconds(0).format(format);
      }

      if (dateSpanInputs.data("init") != "n") {
        if (dateSpanInputs.length && dateSpanInputs.val() != "") {
          const dateStartInputVal = $(dateSpanInputs[0]).val();
          const thisYearDateStartInputVal = moment(dateStartInputVal).add(1, 'years').format("YYYY-MM-DD");
          const dateEndInputVal = $(dateSpanInputs[1]).val();
          const nowDateVal = moment(now).format("YYYY-MM-DD");

          let interval = null;
          if ( dateEndInputVal === nowDateVal) {
            interval = moment(dateEndInputVal).diff(moment(dateStartInputVal), "days");
          } else if ( dateStartInputVal === nowDateVal) {
            interval = moment(dateStartInputVal).diff(moment(dateEndInputVal), "days");
          } else if ( thisYearDateStartInputVal === nowDateVal) {
            interval = moment(thisYearDateStartInputVal).diff(moment(dateEndInputVal), "days");
          }

          if (interval !== null) {
            $(this).find('label input[type="radio"][value="' + interval + '"]').trigger("click");
          }
        } else {
          const $this = $(this);
          let activeRadio = $this.find('label input[type="radio"][value="-2"]');
          if (activeRadio.length < 1) {
            activeRadio = $this.find('label input[type="radio"][value="-2"]');
          }
          activeRadio.trigger("click");
        }
      }
    });
  }
}

/**
 * Mixed date period handler for statistics (일 단위 + 월 단위 혼합)
 * Used in: ms_sales.php
 * - 전일/7일/15일: Days-based calculation
 * - 1개월~12개월: Month-based calculation (N months ago 1st ~ current month last day)
 */
function init_mixed_date_period() {
  if ($('.js-dateperiod-mixed').length) {
    $('.js-dateperiod-mixed label').click(function (e) {
      var $startDate = '',
          $endDate = '',
          $period = $(this).children('input[type="radio"]').val(),
          $elements = $('input[name*=\'' + $(this).closest('.js-dateperiod-mixed').data('target-name') + '\']'),
          $format = $($elements[0]).parent().data('DateTimePicker').format();

      if ($period >= 0) {
        // 일 단위 계산 (전일, 7일, 15일)
        if ($period == 1 || $period == 7 || $period == 15) {
          if ($period == 1) {
            // 전일: 어제 ~ 어제
            $startDate = moment().hours(0).minutes(0).seconds(0).subtract(1, 'days').format($format);
            $endDate = moment().hours(0).minutes(0).seconds(0).subtract(1, 'days').format($format);
          } else {
            // 7일, 15일: N일 전 ~ 오늘
            $startDate = moment().hours(0).minutes(0).seconds(0).subtract($period, 'days').format($format);
            $endDate = moment().hours(0).minutes(0).seconds(0).format($format);
          }
        }
        // 월 단위 계산 (1개월, 3개월, 6개월, 12개월)
        else if ($period == 30 || $period == 90 || $period == 180 || $period == 365) {
          var monthsToSubtract = 0;

          if ($period == 30) {        // 1개월: 이번 달 1일 ~ 말일
            monthsToSubtract = 0;
          } else if ($period == 90) {  // 3개월: 3개월 전 1일 ~ 이번 달 말일
            monthsToSubtract = 2;
          } else if ($period == 180) { // 6개월: 6개월 전 1일 ~ 이번 달 말일
            monthsToSubtract = 5;
          } else if ($period == 365) { // 12개월: 12개월 전 1일 ~ 이번 달 말일
            monthsToSubtract = 11;
          }

          // N개월 전의 1일
          $startDate = moment().subtract(monthsToSubtract, 'months').startOf('month').format($format);
          // 이번 달 말일
          $endDate = moment().endOf('month').format($format);
        }
      }

      $($elements[0]).val($startDate);
      $($elements[1]).val($endDate);
    });

    // 버튼 활성 초기화
    $.each($('.js-dateperiod-mixed'), function (idx) {
      var $elements = $('input[name*=\'' + $(this).data('target-name') + '\']');

      if ($elements.data('init') != 'n') {
        if ($elements.length && $elements.val() != '') {
          // 날짜가 이미 설정되어 있으면 유지 (버튼 자동 선택 안함)
        } else {
          // 기본값: 1개월 버튼 클릭
          $(this).find('label input[type="radio"][value="30"]').trigger('click');
        }
      }
    });
  }
}

// Document ready - initialize custom date period handlers
$(function() {
  init_mixed_date_period();
});

$(document).ajaxComplete(function (event, xhr, settings) {
  if (settings.global_complete !== false) {
    // 회차배송 날짜/시간픽커 초기화
    $(function () {
      setTimeout(function () {
        init_ets_date_time_picker();
        init_mixed_date_period();
      }, 500);
    });
  }
});
