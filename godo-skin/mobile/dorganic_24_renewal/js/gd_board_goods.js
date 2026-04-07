/**
 * 상품상세 상품 리뷰&문의 페이징
 * @param type qa or review
 * @param queryStirng
 */

function gd_view_goods_board(bdId,sno,auth,goodsNo,notice) {
    if (auth == 'n') {
        $(this).closest("li").removeClass("selected");
        alert(__('권한이 없습니다.'));
        return;
    }
    else if (auth == 'c') {
        passwordLayer.show();
        passwordLayer.btnEl.unbind('click').bind('click', function () {
            $.ajax({
                method: "get",
                url: "goods_board_view.php",
                data: {sno: sno, bdId: bdId, writerPw: passwordLayer.value(), goodsNo: goodsNo},
                dataType: 'text',
                cache: false,
                async: true,
            }).success(function (data) {
                passwordLayer.close();
                commentAreaEl.element(bdId, sno, notice).html(data).promise().done(function(){
                    $(this).find('.js_comment_area').attr('data-notice', notice);
                    $(this).find('.js_comment_area').find('.js_data_comment_row').attr('data-notice', notice);
                });
                var commentAreaElReverse = commentAreaEl.elementReverse(bdId, sno, notice);
                if(commentAreaElReverse.length > 0) {
                    if ($.trim(commentAreaElReverse.html()).length > 0) {
                        commentAreaElReverse.html(data).promise().done(function(){
                            $(this).find('.js_comment_area').attr('data-notice', gd_notice_reverse(notice));
                            $(this).find('.js_comment_area').find('.js_data_comment_row').attr('data-notice', gd_notice_reverse(notice));
                        });
                    }
                }
            }).error(function (e) {
                alert('fail');
                alert(e.responseText);
            });
        });
    }  else if (auth == 'y') {

        $.ajax({
            method: "GET",
            url: "../goods/goods_board_view.php",
            data: {sno: sno, bdId: bdId, gboard: 'y', goodsNo: goodsNo},
            dataType: 'text',
            cache: false,
            async: true,
        }).success(function (data) {
            commentAreaEl.element(bdId, sno, notice).html(data).promise().done(function(){
                $(this).find('.js_comment_area').attr('data-notice', notice);
                $(this).find('.js_comment_area').find('.js_data_comment_row').attr('data-notice', notice);
            });
            var commentAreaElReverse = commentAreaEl.elementReverse(bdId, sno, notice);
            if(commentAreaElReverse.length > 0) {
                if ($.trim(commentAreaElReverse.html()).length > 0) {
                    commentAreaElReverse.html(data).promise().done(function(){
                        $(this).find('.js_comment_area').attr('data-notice', gd_notice_reverse(notice));
                        $(this).find('.js_comment_area').find('.js_data_comment_row').attr('data-notice', gd_notice_reverse(notice));
                    });
                }
            }
        }).error(function (e) {
            alert('fail');
            alert(e.responseText);
        });
    }

}

//패스워드입력 레이어 창
var passwordLayer = {
    element: $('.js_password_layer'),
    btnEl: $('.js_password_layer').find('.js_submit'),
    inputEl : $('.js_password_layer').find('input[name=writerPw]'),
    value: function () {
        return $('.js_password_layer').find('input[name=writerPw]').val();
    },
    show: function () {
        this.element.removeClass('hidden');
        $('html').addClass('oh-space');
    },
    close: function () {
        $('.js_password_layer').addClass('hidden');
    }
}
var commentAreaEl = {
    element: function(bdId, sno, notice){
        if($.trim(notice) === ''){
            return $('.js_'+bdId+'_' + sno);
        }
        else {
            return $('.js_'+bdId+'_' + sno + '[data-notice=' + notice + ']');
        }
    },
    elementReverse:  function(bdId, sno, notice){
        if($.trim(notice) === ''){
            return '';
        }
        else {
            return $('.js_'+bdId+'_' + sno + '[data-notice=' + gd_notice_reverse(notice) + ']');
        }
    }
}

var gd_notice_reverse = function (notice) {
    var reverseArray = {'y':'n', 'n':'y'};

    if($.trim(notice) === ''){
        return '';
    }
    else {
        return reverseArray[notice];
    }
}

//페이징
function goGoodsAjaxPage(bdId, queryStirng) {
    var ajaxDataEl = '';
    var page = gd_get_query_variable(queryStirng, 'page');
    var goodsNo = gd_get_query_variable(queryStirng, 'goodsNo');
    if (bdId == 'goodsreview') {
        ajaxDataEl = $('#ajax-goods-review-list');
    }
    else if (bdId == 'goodsreview') {
        ajaxDataEl = $('#ajax-goods-qa-list');
    }
    else {
        alert('error illgal board id');
        return;
    }
    $.ajax({
        method: "GET",
        cache: false,
        url: "./goods_board_list.php",
        data: {bdId: bdId, goodsNo: goodsNo, page: page , gboard : 'y'},
        dataType: 'text'
    }).success(function (data) {
        ajaxDataEl.hide();
        ajaxDataEl.fadeIn('fast');
        ajaxDataEl.html(data);
    }).error(function (e) {
        alert(e.responseText);
    });
}

function gd_get_parameter_by_name(name) {
    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
        results = regex.exec(location.search);
    return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}
