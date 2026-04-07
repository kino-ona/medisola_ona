var boardUrl = {
    write: '../board/article_write.php?' + getUrlVars() + '&bdId={0}',
    view: '../board/article_view.php?' + getUrlVars() + '&bdId={0}&sno={1}',
    modify: '../board/article_write.php?' + getUrlVars() + '&bdId={0}&mode=modify&sno={1}',
    list: '../board/article_list.php?' + getUrlVars() + '&bdId={0}',
    reply: '../board/article_write.php?' + getUrlVars() + '&mode=reply&bdId={0}&sno={1}',
}
String.prototype.format = function () {
    var formatted = this;
    for (var arg in arguments) {
        formatted = formatted.replace("{" + arg + "}", arguments[arg]);
    }
    return formatted;
};

function getUrlVars(paramKey) {
    if (typeof paramKey == 'undefined') {
        paramKey = '';
    }
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    if (window.location.href.indexOf('?') < 0) {
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

function btnWrite(bdId) {
    location.href = boardUrl.write.format(bdId);
}

function btnDelete(bdId, sno, popupMode, bdReplyDelFl, isShow) {
    var confirmMsg = '';
    if (bdReplyDelFl == 'reply') {
        confirmMsg = '해당 글의 답변글도 함께 삭제됩니다.\n\r';
    }
    dialog_confirm(confirmMsg + '정말 삭제하시겠습니까?', function (result) {
            if (result) {
                $.ajax({
                    method: "POST",
                    url: "../board/article_ps.php",
                    data: {mode: 'delete', sno: sno, bdId: bdId , popupMode: popupMode, bdViewDel: 'y'},
                    dataType: 'text'
                }).success(function (data) {
                    if(popupMode == 'yes') {
                        dialog_alert(data, '알림');
                    } else {
                        $('body').append(data);
                        location.href = '../board/article_list.php?bdId=' + bdId + '&isShow='+isShow;
                    }
                }).error(function (e) {
                    alert(e.responseText);
                });
            }
        }
    );


    return;
}

function btnView(bdId, sno) {
    location.href = boardUrl.view.format(bdId, sno, getUrlVars());
}

function btnReplyWrite(bdId, sno) {
    location.href = boardUrl.reply.format(bdId, sno, getUrlVars());
}

function btnList(bdId) {
    location.href = boardUrl.list.format(bdId,getUrlVars());
}

function btnModifyWrite(bdId, sno) {
    location.href = boardUrl.modify.format(bdId, sno);
}

function btnMemoDelete(bdId, bdSno, sno, popupMode) {
    var confirmMsg = '';
    dialog_confirm(confirmMsg + '정말 삭제하시겠습니까?', function (result) {
            if (result) {
                $.ajax({
                    method: "POST",
                    url: "../board/memo_ps.php",
                    data: {mode: 'delete', sno: sno, bdId: bdId, bdSno: bdSno, popupMode: popupMode},
                    dataType: 'text'
                }).success(function (data) {
                    if(popupMode == 'yes') {
                        dialog_alert(data, '알림');
                    } else {
                        $('body').append(data);
                        location.href = '../board/article_list.php?isShow=n&listType=memo&bdId=' + bdId;
                    }
                }).error(function (e) {
                    alert(e.responseText);
                });
            }
        }
    );


    return;
}

function btnReport(bdId, sno, popupMode, listType, goodsNo) {
    if (_.isUndefined(listType)) {
        listType = 'board';
    }
    dialog_confirm('선택한 게시물을 신고해제 하시겠습니까?\r\n이 경우 기존 신고내역은 확인 불가합니다', function (result) {
            if (result) {
                $.ajax({
                    method: "POST",
                    url: "../board/article_ps.php",
                    data: {mode: 'report', sno: sno, bdId: bdId, popupMode:popupMode, listType: listType, goodsNo: goodsNo},
                    dataType: 'text'
                }).success(function (data) {
                    if(popupMode == 'yes') {
                        dialog_alert(data, '알림');
                    } else {
                        $('body').append(data);
                        location.href = '../board/article_list.php?isShow=n&listType='+listType+'&bdId=' + bdId;
                    }
                }).error(function (e) {
                    alert(e.responseText);
                });
            }
        }
    );
    return;
}