/**
 * Created by LeeNamJu on 2015-11-03.
 */

$(document).ready(function () {
    var gd_dom = function (bdId, sno, notice) {
        if($.trim(notice) === ''){
            var formWrite = $('.js_form_write');
        }
        else {
            var formWrite = $(".js_comment_area[data-bdid=" + bdId + "][data-sno=" + sno + "][data-notice=" + notice + "]").find('.js_form_write');
        }

        return {

            detail: $('.js_comment_area'),
            commentRow: function (memoSno, notice) {
                if($.trim(notice) === ''){
                    return this.detail.find('.js_data_comment_row[data-memosno=' + memoSno + ']');
                }
                else {
                    return this.detail.find('.js_data_comment_row[data-memosno=' + memoSno + '][data-notice=' + notice + ']');
                }
            },
            commentFormWrite: {
                element: formWrite,
                writerNm: formWrite.find('input[name=writerNm]'),
                password: formWrite.find('input[name=password]'),
                memo: formWrite.find('[name=memo]'),
                isSecret: formWrite.find('input[name=isSecretReply]'),

            },
            commentFormAction: function (memoSno, notice) {
                return {
                    element: this.commentRow(memoSno, notice).find('.js_action_form'),
                    writerNm: this.commentRow(memoSno, notice).find('input[name=writerNm]'),
                    password: this.commentRow(memoSno, notice).find('input[name=password]'),
                    memo: this.commentRow(memoSno, notice).find('[name=memo]'),
                    btn: this.commentRow(memoSno, notice).find('.js_comment_btn_action'),
                    isSecret: this.commentRow(memoSno).find('input[name=isSecretReply]'),
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
            case 'notice' :
                return $(target).closest('.js_comment_area').data(id);
                break;
            case 'memosno' :
            case 'memoauth' :
                return $(target).closest('.js_data_comment_row').data(id);
        }
    }

    var gd_is_valid_form = function (elObj) {
        var writerNmEl = elObj.find('input[name=writerNm]');
        var passwordEl = elObj.find('input[name=password]');
        var memoEl = elObj.find('[name=memo]');
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
                alert(__('댓글 내용을 입력해주세요.'));
                memoEl.focus();
                return false;
            }
        }

        return true;
    }

    /**
     * 댓글작성
     */
    $('body').on( 'click','.js_comment_btn_write', function (e) {
        var bdId = gd_get_data('bdId', this);
        var sno = gd_get_data('sno', this);
        var notice = gd_get_data('notice', this);
        var domRoot = gd_dom(bdId, sno, notice);
        var isSecret = 'n';

        var agreeElement = $(this).closest('.js_form_write').find('#infoCollectionAgreeWrite');
        if (agreeElement.length > 0) {
            if(agreeElement.is(':checked') == false) {
                alert(__('개인정보 수집항목에 동의해주세요.'));
                return;
            }
        }

        if (! gd_is_valid_form(domRoot.commentFormWrite.element)){
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
                if($('body').find('.js_board_'+bdId+'_view').length) {
                    var viewLink = gd_get_detail_area(bdId, sno, notice);

                    gd_view_goods_board($(viewLink).data('bdid'),$(viewLink).data('sno'),$(viewLink).data('auth'),$(viewLink).data('goodsno'),notice);
                } else {
                    location.reload();
                }
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
        var notice = gd_get_data('notice', this);
        var memoSno = gd_get_data('memosno', this);
        var auth = gd_get_data('memoauth', this);
        //console.log(memoSno+','+bdId+','+sno+','+notice);
        var commentFormAction = gd_dom(bdId, sno, notice).commentFormAction(memoSno, notice);
        //$('.js_action_form').show();
        // commentFormAction.init();
        if (auth === 'c' && commentFormAction.secretReply.val() === 'y') {
            passwordLayer.show();
            passwordLayer.btnEl.unbind('click').bind('click', function () {
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

            if (commentFormAction.element.find('#infoCollectionAgreeAction'+memoSno).length > 0) {
                if(commentFormAction.element.find('#infoCollectionAgreeAction'+memoSno).is(':checked') == false) {
                    alert(__('개인정보 수집항목에 동의해주세요.'));
                    return;
                }
            }

            if (! gd_is_valid_form(commentFormAction.element)) {
                return false;
            }

            if (commentFormAction.isSecret.is(':checked') || commentFormAction.isSecret.val() === 'y') {
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
                    if($('body').find('.js_board_'+bdId+'_view').length) {
                        var viewLink = gd_get_detail_area(bdId, sno, notice);
                        //console.log(viewLink);

                        gd_view_goods_board($(viewLink).data('bdid'),$(viewLink).data('sno'),$(viewLink).data('auth'),$(viewLink).data('goodsno'),notice);
                    } else {
                        location.reload();
                    }
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
     * 댓글답변
     */
    $('body').delegate('.js_comment_btn_reply', 'click', function () {
        var mode = 'reply'
        var bdId = gd_get_data('bdId', this);
        var sno = gd_get_data('sno', this);
        var notice = gd_get_data('notice', this);
        var isSecret = 'n';
        var memoSno = gd_get_data('memosno', this);
        var commentFormAction = gd_dom(bdId, sno, notice).commentFormAction(memoSno, notice);
        commentFormAction.init();
        commentFormAction.element.show();
        commentFormAction.writerNm.prop('readonly', false);
        commentFormAction.btn.unbind('click').bind('click', function () {
            if (commentFormAction.element.find('#infoCollectionAgreeAction'+memoSno).length > 0) {
                if(commentFormAction.element.find('#infoCollectionAgreeAction'+memoSno).is(':checked') == false) {
                    alert(__('개인정보 수집항목에 동의해주세요.'));
                    return;
                }
            }

            if (! gd_is_valid_form(commentFormAction.element)) {
                return false;
            }

            if (commentFormAction.isSecret.is(':checked') || commentFormAction.isSecret.val() === 'y') {
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
                    if($('body').find('.js_board_'+bdId+'_view').length) {
                        var viewLink = gd_get_detail_area(bdId, sno, notice);
                        gd_view_goods_board($(viewLink).data('bdid'),$(viewLink).data('sno'),$(viewLink).data('auth'),$(viewLink).data('goodsno'),notice);
                    } else {
                        location.reload();
                    }
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
        var notice = gd_get_data('notice', this);
        var memoSno = gd_get_data('memosno', this);
        var auth = gd_get_data('memoauth', this);

        if (auth == 'c') {
            passwordLayer.show();
            passwordLayer.btnEl.unbind('click').bind('click', function () {
                gd_memo_delete_ajax_process();
            });
            return;
        }
        if (!confirm(__('정말 삭제하시겠습니까?'))) {
            return false;
        }

        gd_memo_delete_ajax_process(memoSno);

        function gd_memo_delete_ajax_process() {
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
                    if($('body').find('.js_board_'+bdId+'_view').length) {
                        var viewLink = gd_get_detail_area(bdId, sno, notice);

                        gd_view_goods_board($(viewLink).data('bdid'),$(viewLink).data('sno'),$(viewLink).data('auth'),$(viewLink).data('goodsno'),notice);
                        passwordLayer.close();
                    } else {
                        location.reload();
                    }
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
            passwordLayer.btnEl.unbind('click').bind('click', function () {
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
                    commentContent.commentRow(memoSno).find('em').text(data['memo']);
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

    const gPageName = $("#h_board_title").text();

    if (!!gPageName) {
        amplitude.logEvent('post_view', {board_title: gPageName.replace(/\n|\t/g, ''), title: $("#h_title").text().replace(/\n|\t/g, ''), post_id: $("#h_post_id").text() });
    } else {
        amplitude.logEvent('post_view', {board_title: $('#titleArea a').text().replace(/\n|\t/g, ''), title: $('#boardContent h3').text().replace(/\n|\t/g, ''), post_id: $('.js_comment_area').data('sno') });
    }
});


//패스워드입력 레이어 창
var passwordLayer = {
    element: $('.js_password_layer'),
    btnEl: $('.js_password_layer').find('.js_submit'),
    inputEl : $('.js_password_layer').find('input[name=writerPw]'),
    value: function () {
        return $('.js_password_layer').find('input[name=writerPw]').val();
    },
    show: function () {
        this.element.removeClass('dn');
        $('#layerDim').removeClass('dn');
        //$('#scroll-left, #scroll-right').addClass('dim');
        $('html').addClass('oh-space');
    },
    close: function () {
        $('.cite_layer .close').trigger('click');
    }
}

function gd_btn_modify_write(bdId, sno, auth) {
    switch (auth) {
        case 'n' :
            alert('권한이 없습니다.');
            break;
        case 'y' :
            //location.href = boardData.modifyUrl.format(bdId, sno);

            var params = {
                bdId: bdId,
                mode: 'modify',
                sno: sno,
                gboard: 'r',
            };

            $('#popupBoard').modal({
                remote: '../board/write.php',
                cache: false,
                type : 'get',
                params: params,
                show: true
            });

            break;
        case 'c' :
            passwordLayer.show();
            passwordLayer.btnEl.unbind('click').bind('click', function () {
                $.ajax({
                    method: "POST",
                    url: "../board/board_ps.php",
                    data: {mode: 'modifyCheck', sno: sno, bdId: bdId, writerPw: passwordLayer.value(), gboard: 'r'},
                    dataType: 'json'
                }).success(function (data) {
                    if (data['result'] == 'ok') {
                        var params = {
                            oldPassword:passwordLayer.value()
                        };

                        $('#popupBoard').modal({
                            remote: '/board/write.php?bdId='+bdId+"&mode=modify&sno="+sno,
                            cache: false,
                            type : 'post',
                            params: params,
                            show: true
                        });
                    }
                    else {
                        alert(data['msg']);
                        return;
                    }
                    passwordLayer.close();
                }).error(function (e) {
                    alert(e.responseText);
                });
            });
            break;
    }
}

function gd_get_detail_area(bdId, sno, notice){
    if($.trim(notice) === ''){
        return $('.js_board_'+bdId+'_view').find('.js_'+bdId+'_detail_'+sno);
    }
    else {
        return $('.js_board_'+bdId+'_view').find('.js_'+bdId+'_detail_'+sno+'[data-notice='+notice+']');
    }
}
