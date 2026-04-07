var boardData = {
    writeUrl: '../board/write.php?' + gd_get_url_vars() + '&bdId={0}',
    viewUrl: '../board/view.php?' + gd_get_url_vars() + '&bdId={0}&sno={1}',
    modifyUrl: '../board/write.php?' + gd_get_url_vars() + '&bdId={0}&mode=modify&sno={1}',
    listUrl: '../board/list.php?' + gd_get_url_vars() + '&bdId={0}',
    replyUrl: '../board/write.php?' + gd_get_url_vars() + '&mode=reply&bdId={0}&sno={1}',
}

var gboard = gd_get_url_vars('gboard');


function gd_get_url_vars(paramKey) {
    if (typeof paramKey == 'undefined') {
        paramKey = '';
    }
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    if(window.location.href.indexOf('?')<0) {
        return '';
    }
    for (var i = 0; i < hashes.length; i++) {
        hash = hashes[i].split('=');
        key = hash[0];
        val = hash[1];
        if (paramKey != '') {
            if (key == paramKey) {
                return val;
            }
        }

        if (key == 'sno' || key == 'mode' || key == 'bdId') {
            continue;
        }

        vars.push(hashes[i]);
    }

    if (paramKey != '') {
        return '';
    }

    return vars.join('&');
}

function gd_btn_write(bdId) {
    location.href = boardData.writeUrl.format(bdId);
}

function gd_btn_delete(bdId, sno, auth) {
    switch (auth) {
        case 'n' :
            alert(__('권한이 없습니다.'));
            return;
            break;
        case 'y' :
            if (confirm(__('정말 삭제하시겠습니까?'))) {
                gd_delete_ajax_process();
            }
            break;
        case 'c' :
            passwordLayer.show();
            passwordLayer.btnEl.unbind('click').bind('click', function () {
                gd_delete_ajax_process();
            });
            break;
    }

    function gd_delete_ajax_process() {
        if(typeof gboard == 'undefined') {
            gboard = '';
        }

        $.ajax({
            method: "POST",
            url: "../board/board_ps.php",
            data: {mode: 'delete', sno: sno, bdId: bdId, writerPw: passwordLayer.value(),gboard:gboard},
            dataType: 'json'
        }).success(function (data) {
            if (data['result'] == 'ok') {
                alert(data['msg']);
                if(gboard == 'y') {
                   //opener.gd_load_goods_board_list(bdId);
                    opener.location.reload();
                    self.close();
                    return;
                }

                // 상품상세의 상품후기에서 글 삭제시 상품상세로 이동
                if (self.location.pathname === '/goods/goods_view.php') {
                    var goodsHash = '';
                    if (bdId === 'goodsreview') {
                        goodsHash = '#detail-review';
                    } else if (bdId === 'goodsqa') {
                        goodsHash = '#detail-qna';
                    }
                    self.location.href = self.location.href.split('#')[0] + goodsHash;
                    self.location.reload();
                } else {
                    location.href = boardData.listUrl.format(bdId);
                }
            }
            else if (data['result'] == 'confirmPassword') {
                passwordLayer.show();
            }
            else {
                alert(data['msg']);
                return;
            }
        }).error(function (e) {
            alert(e.responseText);
        });
    }

    return;
}

function gd_btn_view(bdId, sno, auth, bdTitle, title, index) {

    amplitude.logEvent('board_post_click', {board_title: bdTitle, post_id: sno, title: title, index: index});

    switch (auth) {
        case 'n' :
            alert(__('권한이 없습니다.'));
            break;
        case 'y' :
            location.href = boardData.viewUrl.format(bdId, sno);
            break;
        case 'c' :
            passwordListLayer.show();
            passwordListLayer.inputEl.bind('keydown', function () {
                if (event.keyCode == 13) {
                    passwordListLayer.btnEl.trigger('click');
                }
            });
            passwordListLayer.btnEl.unbind('click').bind('click', function () {
                if (passwordListLayer.inputEl.val() == '') {
                    alert(__('비밀번호를 입력해주세요.'));
                    return false;
                }

                var form= passwordListLayer.element.closest('form');
                form.attr('method', 'post');
                form.attr('action', boardData.viewUrl.format(bdId, sno));
                form.submit();
            });
            break;
    }
}

function gd_btn_reply_write(bdId, sno) {
    $('#popupBoard').modal({
        remote: boardData.replyUrl.format(bdId, sno),
        cache: false,
        type : 'get',
        show: true
    });
}

function gd_btn_list(bdId) {
    location.href = boardData.listUrl.format(bdId);
}

function gd_recommend(bdId, sno) {

    $.get('../board/board_ps.php', {
        'mode': 'recommend',
        'bdId': bdId,
        'sno': sno
    }, function (data, status) {
        if (status == 'success') {
            alert(data['message']);
            $('#recommendCount').find('strong').html(data['recommendCount']);
        }
        else {
            alert('request fail. ajax status (' + status + ')');
        }
    }, 'json');
}
