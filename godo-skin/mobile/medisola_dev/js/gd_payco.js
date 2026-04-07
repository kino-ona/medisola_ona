/**
 * Created by yjwee on 2016-05-24.
 */
$(document).ready(function () {
    var $paycoLoginBtn = $('.js_btn_payco_login');

    if ($paycoLoginBtn.length > 0) {
        $paycoLoginBtn.click(function () {
            var returnUrl = $(':hidden[name=returnUrl]').val();
            if (typeof returnUrl === "undefined") {
                returnUrl = "";
            }
            var saveAutoLogin = $('input[name="saveAutoLogin"]').is(':checked') ? 'y' : 'n';
            var url = '../member/payco/payco_login.php?paycoType=' + $paycoLoginBtn.data('payco-type') + '&returnUrl=' + returnUrl + '&saveAutoLogin=' + saveAutoLogin;

            if (navigator.userAgent.indexOf('GDWebViewApp') > -1) {
                location.href = url;
            }
            else {
                gd_make_layer_popup(url, __('페이코 로그인'));
            }
        });
    }

    var $snsConnectBtn = $('.js_btn_sns_connect');

    if ($snsConnectBtn.length > 0) {
        $snsConnectBtn.click(function () {
            if ($(this).data('sns') == 'payco') {
                var url = '../member/payco/payco_connect.php';

                if (navigator.userAgent.indexOf('GDWebViewApp') > -1) {
                    location.href = url;
                }
                else {
                    gd_make_layer_popup(url, __('페이코 로그인'));
                }
            }
        });
    }

    if (typeof paycoProfile !== 'undefined' && paycoProfile !== null) {
        if (paycoProfile.sexCode) {
            var $sexFl = $(':radio[name="sexFl"]');
            if ($sexFl.length > 0) {
                var $checkedSexFl = $sexFl.filter('*[value="' + paycoProfile.sexCode.toLowerCase() + '"]');
                $checkedSexFl.prop('checked', true);
                $checkedSexFl.next('label').addClass('on');
            }
        }
        if (paycoProfile.id) {
            $('#memId').val(paycoProfile.id);
            var $email = $('#email');
            if ($email.length > 0) {
                $email.val(paycoProfile.id);
            }
        }
        if (paycoProfile.mobileId) {
            if (_.isUndefined(paycoProfile.id)) {
                $('#memId').val(paycoProfile.mobileId);
            }
            var $cellPhone = $('#cellPhone');
            if ($cellPhone.length > 0) {
                $cellPhone.val(paycoProfile.mobileId);
            }
        }
        if (paycoProfile.name) {
            $('input[name="memNm"]').val(paycoProfile.name);
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

    if (typeof snsConnection !== 'undefined') {
        var $disconnectBtn = $('.js_btn_sns_disconnect');

        if ($disconnectBtn.length > 0) {
            $disconnectBtn.click(function () {
                if ($(this).data('sns') == 'payco') {
                    if (confirm(__('계정 연결을 해제하시겠습니까?'))) {
                        var $ajax = $.ajax('../member/payco/payco_disconnect.php');
                        $ajax.done(function (response) {
                            alert(response.message);
                            if (response.url) {
                                window.location.href = response.url;
                            }
                        });
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
