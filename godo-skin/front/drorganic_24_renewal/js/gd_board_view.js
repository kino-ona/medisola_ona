/**
 * Created by LeeNamJu on 2015-11-03.
 */

$(document).ready(function () {
    $('img.js_smart_img').each(function () {
        $(this).css('max-width', '100%');
    });

    var gd_dom = function (bdId, sno) {
        var formWrite = $('.js_form_write');
        return {
            //  dataRow: $(".js-data-row[data-bdid=" + bdId + "][data-sno=" + sno + "]"),
            detail: $('.js_comment_area'),
            commentRow: function (memoSno) {
                return this.detail.find('.js_data_comment_row[data-memosno=' + memoSno + ']');
            },
            commentFormWrite: {
                element: formWrite,
                writerNm: formWrite.find('input[name=writerNm]'),
                password: formWrite.find('input[name=password]'),
                memo: formWrite.find('textarea[name=memo]'),
                isSecret: formWrite.find('input[name=isSecretReply]'),
            },
            commentFormAction: function (memoSno) {
                return {
                    element: this.commentRow(memoSno).find('.js_action_form'),
                    writerNm: this.commentRow(memoSno).find('input[name=writerNm]'),
                    password: this.commentRow(memoSno).find('input[name=password]'),
                    memo: this.commentRow(memoSno).find('textarea[name=memo]'),
                    isSecret: this.commentRow(memoSno).find('input[name=isSecretReply]'),
                    btn: this.commentRow(memoSno).find('.js_comment_btn_action'),
                    secretReply: this.commentRow(memoSno).find('input[name=secretReply]'),
                    init: function () {
                        this.writerNm.val(null);
                        this.password.val(null);
                        this.memo.val(null);
                    },
                }
            }
        }
    }

    var gd_get_data = function (id, target) {
        id = id.toLowerCase();
        switch (id) {
            case 'bdid' :
            case 'sno' :
                return $(target).closest('.js_comment_area').data(id)
                break;
            case 'memosno' :
            case 'memoauth' :
                return $(target).closest('.js_data_comment_row').data(id);
        }
    }

    var gd_is_valid_form = function (elObj) {
        var writerNmEl = elObj.find('input[name=writerNm]');
        var passwordEl = elObj.find('input[name=password]');
        var memoEl = elObj.find('textarea[name=memo]');
        if (writerNmEl.length > 0) {
            if (writerNmEl.val() == '') {
                alert(__('이름을 입력해주세요.'));
                writerNmEl.focus();
                return false;
            }
        }

        if (passwordEl.length > 0) {
            if (passwordEl.val() == '') {
                alert(__('비밀번호를 입력해주세요.'));
                passwordEl.focus();
                return false;
            }
        }

        if (memoEl.length > 0) {
            if (memoEl.val() == '') {
                alert(__('댓글내용을 입력해주세요.'));
                memoEl.focus();
                return false;
            }
        }

        return true;
    }

    /**
     * 댓글작성
     */
    $('body').delegate('.js_comment_btn_write', 'click', function (e) {
        var bdId = gd_get_data('bdId', this);
        var sno = gd_get_data('sno', this);
        var domRoot = gd_dom(bdId, sno);
        var isSecret = 'n';

        if ($('#infoCollectionAgreeWrite').length > 0) {
            if($('#infoCollectionAgreeWrite').is(':checked') == false) {
                alert(__('개인정보 수집항목에 동의해주세요.'));
                return;
            }
        }

        if (!gd_is_valid_form(domRoot.commentFormWrite.element)) {
            return false;
        }

        if (domRoot.commentFormWrite.isSecret.is(':checked') || domRoot.commentFormWrite.isSecret.val() === 'y' ) {
            isSecret = 'y';
        }

        $.ajax({
            method: "POST",
            url: "../board/memo_ps.php",
            data: {
                mode: 'write',
                bdSno: sno,
                bdId: bdId,
                memo: domRoot.commentFormWrite.memo.val(),
                writerNm: domRoot.commentFormWrite.writerNm.val(),
                writerPw: domRoot.commentFormWrite.password.val(),
                isSecret: isSecret,
            },
            dataType: 'json'
        }).success(function (data) {
            if (data['result'] == 'ok') {
                alert(data['msg']);
                location.reload();
            }
            else {
                alert(data['msg']);
                return;
            }
        }).error(function (e) {
            alert(e.responseText);
        });
    });


    /**
     * 댓글 수정
     */
    $('body').delegate('.js_comment_btn_modify', 'click', function () {
        var mode = 'modify'
        var bdId = gd_get_data('bdId', this);
        var sno = gd_get_data('sno', this);
        var memoSno = gd_get_data('memosno', this);
        var auth = gd_get_data('memoauth', this);
        var commentFormAction = gd_dom(bdId, sno).commentFormAction(memoSno);

        if (auth === 'c' && commentFormAction.secretReply.val() === 'y') {
            passwordLayer.show();
            passwordLayer.btn.unbind('click').bind('click', function () {
                checkReplyPassWord();
            });
        } else {
            showModifyArea();
        }

        function checkReplyPassWord() {
            $.ajax({
                method: "POST",
                url: "../board/memo_ps.php",
                data: {
                    mode: 'checkPassWord',
                    bdSno: sno,
                    sno: memoSno,
                    bdId: bdId,
                    writerPw: passwordLayer.value(),
                },
                dataType: 'json'
            }).success(function (data) {
                if (data['result'] === 'ok') {
                    writerPw = passwordLayer.value();
                    passwordLayer.close();
                    showModifyArea(writerPw);
                }
                else {
                    alert(data['msg']);
                    return;
                }
            }).error(function (e) {
                alert(e.responseText);
            });
        }

        function showModifyArea(writerPw) {
            commentFormAction.init();
            commentFormAction.element.show();
            commentFormAction.writerNm.prop('readonly', true);

            $.ajax({
                method: "POST",
                url: "../board/memo_ps.php",
                data: {
                    mode: 'getMemo',
                    bdId: bdId,
                    bdSno: sno,
                    sno: memoSno,
                    writerPw: writerPw,
                },
                dataType: 'json'
            }).success(function (data) {
                if (data['result'] == 'ok') {
                    commentFormAction.writerNm.val(data.writerNm);
                    commentFormAction.memo.val(data.memo);
                }
                else {
                    alert(data['msg']);
                    return;
                }
            });
        }

        commentFormAction.btn.unbind('click').bind('click', function () {
            var isSecret = 'n';

            if ($('#infoCollectionAgreeAction').length > 0) {
                if($('#infoCollectionAgreeAction').is(':checked') == false) {
                    alert(__('개인정보 수집항목에 동의해주세요.'));
                    return;
                }
            }

            if (!gd_is_valid_form(commentFormAction.element)) {
                return false;
            }

            if ($('#secretReplyModify').is(":checked") || commentFormAction.isSecret.val() === 'y') {
                isSecret = 'y';
            }

            $.ajax({
                method: "POST",
                url: "../board/memo_ps.php",
                data: {
                    mode: mode,
                    bdSno: sno,
                    sno: memoSno,
                    bdId: bdId,
                    writerNm: commentFormAction.writerNm.val(),
                    writerPw: commentFormAction.password.val(),
                    memo: commentFormAction.memo.val(),
                    isSecret: isSecret,
                },
                dataType: 'json'
            }).success(function (data) {
                if (data['result'] == 'ok') {
                    alert(data['msg']);
                    location.reload();
                }
                else {
                    alert(data['msg']);
                    return;
                }
            }).error(function (e) {
                alert(e.responseText);
            });
        });
    });

    /**
     * 댓글답글
     */
    $('body').delegate('.js_comment_btn_reply', 'click', function () {
        var mode = 'reply'
        var bdId = gd_get_data('bdId', this);
        var sno = gd_get_data('sno', this);
        var isSecret = 'n';
        var memoSno = gd_get_data('memosno', this);
        var commentFormAction = gd_dom(bdId, sno).commentFormAction(memoSno);
        commentFormAction.init();
        commentFormAction.element.show();
        commentFormAction.writerNm.prop('readonly', false);
        commentFormAction.btn.unbind('click').bind('click', function () {
            if ($('#infoCollectionAgreeAction').length > 0) {
                if($('#infoCollectionAgreeAction').is(':checked') == false) {
                    alert(__('개인정보 수집항목에 동의해주세요.'));
                    return;
                }
            }

            if (!gd_is_valid_form(commentFormAction.element)) {
                return false;
            }

            if ($('#secretReplyModify').is(":checked") || commentFormAction.isSecret.val() === 'y') {
                isSecret = 'y';
            }

            $.ajax({
                method: "POST",
                url: "../board/memo_ps.php",
                data: {
                    mode: mode,
                    bdId: bdId,
                    bdSno: sno,
                    sno: memoSno,
                    writerNm: commentFormAction.writerNm.val(),
                    writerPw: commentFormAction.password.val(),
                    memo: commentFormAction.memo.val(),
                    isSecret: isSecret,
                },
                dataType: 'json'
            }).success(function (data) {
                if (data['result'] == 'ok') {
                    alert(data['msg']);
                    location.reload();
                }
                else {
                    alert(data['msg']);
                    return;
                }
            }).error(function (e) {
                alert(e.responseText);
            });
        });
    });


    /**
     * 댓글삭제
     */
    $('body').delegate('.js_comment_btn_delete', 'click', function () {
        var bdId = gd_get_data('bdId', this);
        var sno = gd_get_data('sno', this);
        var memoSno = gd_get_data('memosno', this);
        var auth = gd_get_data('memoauth', this);

        if (auth == 'c') {
            passwordLayer.show();
            passwordLayer.btn.unbind('click').bind('click', function () {
                memoDeleteAjaxProcess();
            });
            return;
        }
        if (!confirm(__('정말 삭제하시겠습니까?'))) {
            return false;
        }

        memoDeleteAjaxProcess(memoSno);

        function memoDeleteAjaxProcess() {
            $.ajax({
                method: "POST",
                url: "../board/memo_ps.php",
                data: {
                    mode: 'delete',
                    bdSno: sno,
                    sno: memoSno,
                    bdId: bdId,
                    writerPw: passwordLayer.value(),
                },
                dataType: 'json'
            }).success(function (data) {
                if (data['result'] == 'ok') {
                    alert(data['msg']);
                    location.reload();
                }
                else {
                    alert(data['msg']);
                    return;
                }
            }).error(function (e) {
                alert(e.responseText);
            });
        }
    });

    /**
     * 비밀댓글확인
     */
    $('body').delegate('.js_comment_btn_secret', 'click', function () {
        var bdId = gd_get_data('bdId', this);
        var sno = gd_get_data('sno', this);
        var memoSno = gd_get_data('memosno', this);
        var auth = gd_get_data('memoauth', this);

        var commentContent = gd_dom(bdId, sno);

        if (auth == 'c') {
            passwordLayer.show();
            passwordLayer.btn.unbind('click').bind('click', function () {
                viewSecretMemoAjaxProcess();
            });
            return;
        }

        function viewSecretMemoAjaxProcess() {
            $.ajax({
                method: "POST",
                url: "../board/memo_ps.php",
                data: {
                    mode: 'getSecretMemo',
                    bdSno: sno,
                    sno: memoSno,
                    bdId: bdId,
                    writerPw: passwordLayer.value(),
                },
                dataType: 'json'
            }).success(function (data) {
                if (data['result'] == 'ok') {
                    commentContent.commentRow(memoSno).find('p > em').text(data['memo']);
                    passwordLayer.close();
                }else {
                    alert(data['msg']);
                    return;
                }
            }).error(function (e) {
                alert(e.message);
            });
        }
    });

    if (window.self !== window.top) {
        if (window.parent.amplitude) {
            window.parent.amplitude.logEvent('post_view', {board_title: $('.board_zone_tit').text().replace(/\n|\t/g, ''), title: $('.board_view_tit h3').text().replace(/\n|\t/g, ''), post_id: $('.js_comment_area').data('sno') });
        }
    } else {
        if (window.amplitude) {
            window.amplitude.logEvent('post_view', {board_title: $('.board_zone_tit').text().replace(/\n|\t/g, ''), title: $('.board_view_tit h3').text().replace(/\n|\t/g, ''), post_id: $('.js_comment_area').data('sno') });
        }
    }
})
;

//패스워드입력 레이어 창
var passwordLayer = {
    element: $('#lyPassword_view'),
    btn: $('#lyPassword_view').find('.js_submit'),
    value: function () {
        return $('#lyPassword_view').find('input[name=writerPw]').val();
    },
    show: function () {
        this.element.removeClass('dn');
        this.element.currentCenter();
        $('#layerDim').removeClass('dn');
        $('#scroll-left, #scroll-right').addClass('dim');
        $('.pasword_layer .ly_cont .text').focus();
    },
    close: function () {
        $('.password_layer .ly_close').trigger('click');
    }
}

function gd_btn_modify_write(bdId, sno, auth) {
    switch (auth) {
        case 'n' :
            alert(__('권한이 없습니다.'));
            break;
        case 'y' :
            location.href = boardData.modifyUrl.format(bdId, sno);
            break;
        case 'c' :
            passwordLayer.show();

            $('input[name=writerPw]').keydown(function(e){
                if(e.keyCode == 13) {
                    passwordLayer.btn.trigger('click');
                }
            });

            passwordLayer.btn.unbind('click').bind('click', function () {
                $.ajax({
                    method: "POST",
                    url: "../board/board_ps.php",
                    data: {mode: 'modifyCheck', sno: sno, bdId: bdId, writerPw: passwordLayer.value()},
                    dataType: 'json'
                }).success(function (data) {
                    if (data['result'] == 'ok') {
                        var formHtml = "<form method='post' action='" + boardData.modifyUrl.format(bdId, sno) + "' id='frmModify'>";
                        formHtml += '<input type="hidden" name="bdId" value="' + bdId + '"/>';
                        formHtml += '<input type="hidden" name="sno" value="' + sno + '"/>';
                        formHtml += '<input type="hidden" name="oldPassword" value="' + passwordLayer.value() + '"/>';
                        $('body').append(formHtml);
                        $('#frmModify').submit();
                    }
                    else {
                        alert(data['msg']);
                        return;
                    }
                }).error(function (e) {
                    alert(e.responseText);
                });
            });
            break;
    }
}
