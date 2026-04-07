/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall5 to newer
 * versions in the future.
 *
 * @copyright ⓒ 2016, NHN godo: Corp.
 * @link http://www.godo.co.kr
 */

/**
 * 마이앱 팝업 처리
 */
$(function(){
    var myappconfig;

    $.ajax({
        type: 'GET',
        url: '../popup/popup_ps.php',
        data: {'mode': 'myapp_popup_open'},
        dataType: 'json',
        success: function (data) {
            myappconfig = data;
            // 고정 레이어 팝업
            if (data.popupType) {
                gd_myapp_fixed_popup(data);
            }

            // 팝업 링크
            if (myappconfig) {
                if (myappconfig.device === 'android') {
                    $("#linkButton").prop('href', 'intent://#Intent;scheme=' + myappconfig.appPkgName + '://action;package=' + myappconfig.appPkgName + ';end;');
                }
                else {
                    $(document).on('click', '#linkButton', function () {
                        var visitedAt = (new Date()).getTime(); // 방문 시간
                        setTimeout(function () {
                            if ((new Date()).getTime() - visitedAt < 2000)
                            {
                                location.href = myappconfig.appUrl;
                            }
                        }, 500);
                        setTimeout(function () {
                            location.href = myappconfig.appPkgName + "://action";
                        }, 0);
                    });
                }
            }
        }
    });
    // 오늘 하루 보이지 않음
    $(document).on('click', '#myapp_promote_popup_no_more_today', function() {
        gd_set_myapp_popup_cookie('myapp_promote_popup_no_more_today', 1);
        $('.ly_app_wrap').hide();
    });
});

/**
 * 고정 레이어 팝업
 *
 * @param data
 */
function gd_myapp_fixed_popup(data)
{
    var popupHtml = '';
    popupHtml +=
        '<div class="ly_app_wrap">' +
        '    <div class="ly_app_dim"></div>' +
        '    <div class="ly_app_cont">' +
        '        <div class="ly_app_box" style="text-align: center;">' +
        '            <a href="" target="_blank" class="img_app" id="linkButton"><img src="' + data.popupImage + '" alt="' + __('앱 설치하고 편하게 쇼핑하자 go! 앱 설치 고객님께만 드리는 모바일 앱 전용 혜택과 편리한 기능으로 더욱 쉽고 빠르게 쇼핑하세요! 설치하러 가기') + '"/></a>';
    if (data.checkDayUse) {
        popupHtml +=
        '            <div class="chk_today">' +
        '                <label class="check_label" for="myapp_promote_popup_no_more_today">' +
        '                    <div class="input_check_box">' +
        '                        <input type="checkbox" name="" value="" class="check-origin" id="myapp_promote_popup_no_more_today"">' +
        '                        <span class="check-clone"></span>' +
        '                    </div>' +
        '                    <span class="label_txt">' + __('오늘 하루 보이지 않음') + '</span>' +
        '                </label>' +
        '            </div>';
    }
    popupHtml +=
        '            <button class="btn_ly_close" onclick="$(\'.ly_app_wrap\').hide();">' + __('닫기') + '</button>' +
        '        </div>' +
        '    </div>' +
        '</div>';
    $('#wrap').after(popupHtml);
}

/**
 * 쿠키 세팅
 * 
 * @param name
 * @param value
 */
function gd_set_myapp_popup_cookie(name, value) {
    var path = location.host.substring(1).split(':');
    $.cookie(name, value, {path: '/', expires: 1, domain: path[0]});
    if (name === 'myapp_promote_popup_no_more_today') {
        $.removeCookie('myapp_popup_page_move', {path: '/', expires: 1, domain: path[0]});
    }
}
