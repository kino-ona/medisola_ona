/**
 * Created by conan kim (kmakugo@gmail.com) on 2025-2-6.
 */
$(document).ready(function () {
  // 폼체크
  $('#frmDeliveryStatus').validate({
      submitHandler: function (form) {
          if ($('input[name*=statusCheck]:checked').length < 1) {
              dialog_alert('선택된 회차 배송이 없습니다.');
              return false;
          }

          // 선택여부 확인
          if ($('#deliveryStatusTop').length && $('#frmDeliveryStatus > input[name=mode]').val() == 'combine_delivery_status_change') {
              if (_.isEmpty($('#deliveryStatusTop option:selected').val())) {
                  alert('변경하려는 배송상태를 선택해주세요.');
                  return false;
              }
          }

          // 주문번호 & 총 주문 횟수 저장(관리자 로그)
          if($('input[name="changeOrderNo"]').length > 0 && $('input[name="changeOrderCnt"]').length > 0) {
              $('input[name="changeOrderNo"]').val($('input[name*=statusCheck]:checked').eq(0).val().split('||')[0]);
              $('input[name="changeOrderCnt"]').val($('input[name*=statusCheck]:checked').length);
          }
          form.target = 'ifrmProcess';
          form.submit();
      }
  });

  // 선택 일괄배송 일괄변경 선택 처리
  $('#deliveryStatusTop, #deliveryStatusBottom').change(function (e) {
      var chkStatus = $(this).val().substr(0, 1);

      $('input#deliveryStatus').val($(this).val());
      $('select#deliveryStatusTop').val($(this).val());
      $('select#deliveryStatusBottom').val($(this).val());

      $('input[name*=statusCheck]:checked').each(function (idx) {
          // 에스크로 체크 후 배송 등록 여부를 체크
          var $checkbox = $(this);
          if ($(this).is('[name="statusCheck[p][]"]') || $(this).is('[name="statusCheck[g][]"]')) {
              $checkbox.prop('disabled', false);

              // 배송 처리를 선택하는 경우
              if (chkStatus == 'd') {
                  if ($(this).siblings('input[name*=escrowCheck]').val() == 'en') {
                      $checkbox.prop('disabled', true);
                  }
              }
          }
      });
  });

  // 선택주문 일괄 송장변경 처리
  if ($('.js-save-invoice-d').length > 0) {
      $('.js-save-invoice-d').click(function (e) {
          $('#frmDeliveryStatus > input[name=mode]').val('combine_delivery_invoice_change');
          $.validator.setDefaults({dialog: false});
          if ($('input[name*=statusCheck]:checked').length > 0) {
              BootstrapDialog.confirm({
                  type: BootstrapDialog.TYPE_DANGER,
                  title: '일괄 송장 변경',
                  message: '선택된 ' + $('input[name*=statusCheck]:checked').length + '개의 회차배송 송장번호를 정말로 저장 하시겠습니까?',
                  closable: false,
                  callback: function (result) {
                      if (result) {
                          $('#frmDeliveryStatus').submit();
                          $('#frmDeliveryStatus > input[name=mode]').val('combine_delivery_status_change');
                      }
                  }
              });
          } else {
              $('#frmDeliveryStatus').submit();
              $('#frmDeliveryStatus > input[name=mode]').val('combine_delivery_status_change');
          }
      });
  }

  if($("input[name='invoiceIndividualUnset[]']").length > 0){
      $("input[name='invoiceIndividualUnset[]']").click(function () {
          var thisCheckBox = $(this);
          if(thisCheckBox.attr('data-combine-prevent') == true){
              alert("공급사가 다르거나 배송방식이 달라 주문별 배송정보 등록이 불가합니다.");
              $(this).prop('checked', false);
              return;
          }

          if($(this).prop('checked') === true){
              BootstrapDialog.confirm({
                  type: BootstrapDialog.TYPE_DANGER,
                  title: '정보',
                  message: '상품별 송장번호가 등록되어 있습니다.<br />개별등록해제 후 주문별 송장번호를 등록하시겠습니까?<br /><br /><span style="color: red;">(주문별 송장 등록시 개별 등록된 송장번호도 수정됩니다.)</span>',
                  closable: false,
                  callback: function (result) {
                      if (result) {
                          thisCheckBox.closest('.js-invoice-unset-area').addClass("display-none");
                          thisCheckBox.closest('td').find(".js-invoice-area").removeClass("display-none");
                          thisCheckBox.closest('td').find("input[name*='invoiceIndividualUnsetFl']").val(thisCheckBox.val());
                      }
                      else {
                          thisCheckBox.prop('checked', false);
                          thisCheckBox.closest('td').find("input[name*='invoiceIndividualUnsetFl']").val('');
                      }
                  }
              });
          }
          else {
              thisCheckBox.closest('td').find("input[name*='invoiceIndividualUnsetFl']").val('');
          }
      });
  }
});