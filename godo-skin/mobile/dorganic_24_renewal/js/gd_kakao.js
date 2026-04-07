/**
 * Create by sojeong
 */

$(document).ready(function () {
    var $kakaoLoginBtn = $('.js_btn_kakao_login');

    if($kakaoLoginBtn.length > 0) {
        $kakaoLoginBtn.click(function () {
            var url ='';
            var kakaoUrl = $kakaoLoginBtn.data('return-url');
            var returnUrl =  $(':hidden[name=returnUrl]').val();
            var autoSave = $('input[name="saveAutoLogin"]').is(':checked') ? 'y' : 'n';

            if(kakaoUrl !== 'undefined'){
                returnUrl = kakaoUrl;
            }
            if($kakaoLoginBtn.data('kakao-type') !== 'undefined'){
                url = '../member/kakao/kakao_login.php?kakaoType=' + $kakaoLoginBtn.data('kakao-type') + '&returnUrl=' + returnUrl + '&saveAutoLogin=' + autoSave;
            }else {
                url = '../member/kakao/kakao_login.php';
            }
            location.href = url;
        });
    }

    if(typeof kakaoProfile !== 'undefined') {
        if(kakaoProfile.nickname){
            $('input[name="memNm"]').val(kakaoProfile.nickname);
        }
        if(kakaoProfile.email){
            $('input[name="email"]').val(kakaoProfile.email);
        }

        var $memPw = $('input[name="memPw"]');
        if ($memPw.length > 0) {
            $memPw.addClass('ignore');
        }
        var $memPwRe = $('input[name="memPwRe"]');
        if ($memPwRe.length > 0) {
            $memPwRe.addClass('ignore');
        }
    }

    var $snsConnectBtn = $('.js_btn_sns_connect');

    if ($snsConnectBtn.length > 0) {
        $snsConnectBtn.click(function () {
            if ($(this).data('sns') == 'kakao') {
                location.href = '../member/kakao/kakao_login.php?kakaoType=connect';
            }
        });
    }

    if (typeof snsConnection !== 'undefined') {
        var $disconnectBtn = $('.js_btn_sns_disconnect');

        if ($disconnectBtn.length > 0) {
            $disconnectBtn.click(function () {
                if($(this).data('sns') == 'kakao') {
                    if (confirm(__('계정 연결을 해제하시겠습니까?'))) {
                        location.href = $(this).data('kakao-url');
                    }
                }
            });
        }
    }
});

function confirmJoin(message, location) {
    if (confirm(message)) {
        window.location.href = location;
    }
}