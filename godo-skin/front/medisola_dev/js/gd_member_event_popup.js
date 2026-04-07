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
 * 회원정보 이벤트 팝업
 */
$(function(){
    var memNo;
    $.ajax({
        type: 'GET',
        url: '../popup/popup_ps.php',
        data: {'mode': 'memberEventPopupOpen'},
        dataType: 'json',
        success: function (data) {
            // 고정 레이어 팝업
            for (var i in data) {
                var domainFl = (data[i].domainFl != 'kr') ? '/'+data[i].domainFl +'/' : '/';
                memNo = data[i].memNo;

                // 로그인 완료시 노출
                if (data[i].loginDisplayFl === 'y') {
                    var thisScript =  $('script[src*=gd_member_event_popup]');
                    var pageName = 'n';

                    if (thisScript.length > 0) {
                        pageName = thisScript.attr('src').split("pageName=")[1];
                    }

                    if (pageName === domainFl + 'member/login_ps.php') {
                        gd_member_event_fixed_popup(data[i]);
                        data[i].mainDisplayFl = 'n';
                    }
                }

                // 마이페이지 노출
                if (data[i].mypageDisplayFl === 'y' && window.location.pathname === domainFl + 'mypage/index.php') {
                    gd_member_event_fixed_popup(data[i]);
                }

                // 메인페이지 노출
                if (data[i].mainDisplayFl === 'y' && (window.location.pathname === '/' || window.location.pathname === domainFl + 'main/index.php')) {
                    gd_member_event_fixed_popup(data[i]);
                }
            }
        }
    });

    // 이벤트 참여 버튼 클릭시
    $(document).on('click', '.btn_event', function() {
        if ($(this).data('eventtype') === 'modify') {
            location.href = '../mypage/my_page_password.php';
        } else if ($(this).data('eventtype') === 'life') {
            var params = [];
            params.push({name: "mode", value: "applyMemberLifeEvent"});
            params.push({name: "memNo", value: memNo});
            var $ajax = $.ajax('../member/member_ps.php', {type: "post", data: params});
            $ajax.done(function (data) {
                alert(data);
                $('#memberEventPopup_life').hide();
            });
        }
    });
});

/**
 * 고정 레이어 팝업
 *
 * @param data
 */
function gd_member_event_fixed_popup(data)
{
    var popupHtml = '';
    var benefitInfo = '';

    // 혜택지급 내용
    if (data.benefitType === 'coupon' || data.benefitType === 'mileage') {
        benefitInfo = (data.benefitType === 'coupon') ? ' [' + data.couponNm + ']쿠폰' : parseInt(data.benefitMileage) + '원 마일리지';
    }

    popupHtml +=
        '<div id="memberEventPopup_'+ data.eventType +'" class="main_popup_layer" style="position: absolute; /*overflow: hidden;*/ z-index: 300; width: 406px; height: 472px; top: '+ data.popupPositionT +'px; left: '+ data.popupPositionL +'px; padding-bottom: 10px;">' +
        '    <div id="memberEventPopup_'+ data.eventType +'_form" class="sys_pop">' +
        '        <div class="box">' +
        '            <div class="view">';

    // 팝업 내용
    if (data.popupContentType === 'direct') {
        popupHtml += data.popupContent;
    } else {
        var popupConentsImage = (data.eventType === 'modify') ? '/admin/gd_share/img/member/member_modify_event_' + data.domainFl + '_02.png' : '/admin/gd_share/img/member/member_life_event_' + data.domainFl + '_02.png';
        var eventBtnName = (data.eventType === 'modify') ? '회원정보 수정하기' : '평생회원 전환하기';
        popupHtml +=
            '                <p><img src="'+ popupConentsImage +'" class="js-smart-img"></p>';

        if (data.benefitType != 'manual') {
            var eventTypeName = (data.eventType === 'modify') ? '지금 회원정보 수정 시' : '지금 평생회원으로 전환 시';
            popupHtml +=
                '                <p class="text_event">' + __(eventTypeName) + '<span class="text_red">'+ benefitInfo +'</span> ' + __('제공') + '!</p>';
        }
    }

    if (data.popupContentType === 'default') {
        popupHtml +=
            '            </div>' +
            '            <div class="event_btn_wrap">' +
            '                <a href="javascript:void(0);" class="btn_event" data-eventtype="' + data.eventType + '"><span>' + __(eventBtnName) + '</span></a>' +
            '            </div>';
    }

    // 오늘하루 보이지 않음
    if (data.todayUnSeeFl === 'y') {
        popupHtml +=
            <!-- 오늘 하루 보이지 않음 : start -->
            '            <div class="check">' +
            '                <span class="form_element">' +
            '                    <label for="todayUnSee_memberEventPopup_'+ data.eventType +'" class="check_s">' + __('오늘 하루 보이지 않음') + '</label>' +
            '                    <input type="checkbox" id="todayUnSee_memberEventPopup_'+ data.eventType +'" class="checkbox" onclick="gd_popup_cookie(\'memberEventPopup_'+ data.eventType +'\', this);">' +
            '                </span>' +
            '            </div>';
            <!-- 오늘 하루 보이지 않음 : end -->
    }

    popupHtml +=
        '            <button type="button" class="close" title="' + __('닫기') + '" onclick="$(\'#memberEventPopup_'+ data.eventType +'\').hide();">' + __('닫기') + '</button>' +
        '        </div>' +
        '    </div>' +
        '</div>';
    $('#wrap').after(popupHtml);
}
