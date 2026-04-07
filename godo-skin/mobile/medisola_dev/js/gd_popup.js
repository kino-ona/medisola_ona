/**
 * 팝업 처리 스크립트
 * @author Shin Donggyu <artherot@godo.co.kr>
 */
$(function(){
    $.ajax({
        type: 'GET',
        url: '../popup/popup_ps.php',
        data: {'mode':'popupOpen','currentUrl':encodeURIComponent(location.href)},
        dataType: 'json',
        async: true
    }).done(function(data) {
        if (_.isUndefined(data.error)) {
            $.each(data, function (key, value) {
                value.popupKindFl = "layer"; //모바일은 팝업 고정
                if (value.mobilePopupSkin == 'top') {
                    gd_main_popup_top(value);
                } else {
                    gd_main_popup_layer(value, value.mobilePopupKindFl);
                }
            });
        }
    });
});

/**
 * 일반팝업창
 */
function gd_main_popup_window(v)
{
    var property = new Array();
    property.push('scrollbars=no, toolbar=no');
    if (v.popupSizeW != '') {
        property.push('width='+v.popupSizeW);
    }
    if (v.popupSizeH != '') {
        property.push('height='+v.popupSizeH);
    }
    if (v.popupPositionT != '') {
        property.push('top='+v.popupPositionT);
    }
    if (v.popupPositionL != '') {
        property.push('left='+v.popupPositionL);
    }
    var property = property.join(',');
    var win = window.open('../popup/popup.php?sno='+v.sno, v.popupCode, property);
    if (win) {
        win.focus();
    }
}

/**
 * 고동레이어 & 이동레이어
 */
function gd_main_popup_layer(v, type)
{
    if (v.mobileSizeTypeW == '%') {
        v.mobilePopupSizeW = ($(window).width() * (Number(v.mobilePopupSizeW) / 100)) - 100;
    }
    if (v.mobileSizeTypeH == '%') {
        v.mobilePopupSizeH = ($(window).height() * (Number(v.mobilePopupSizeH) / 100)) - 102;
    }
    var cssAttr = {'position':'absolute','overflow':'hidden','z-index':'2000'};
    if (v.mobilePopupSizeW != null && v.mobilePopupSizeW != '') {
        cssAttr.width = (v.mobilePopupSizeW+100)+'px';
    }

    v.mobilePopupSizeH = v.mobilePopupSizeH+118;


    if (v.mobilePopupSizeH != null && v.mobilePopupSizeH != '') {
        cssAttr.height = v.mobilePopupSizeH+'px';
    }
    if (v.mobilePopupPositionT != null && v.mobilePopupPositionT != '') {
        cssAttr.top = v.mobilePopupPositionT+'px';
    } else {
        cssAttr.top = '0px';
    }
    if (v.mobilePopupPositionL != null && v.mobilePopupPositionL != '') {
        cssAttr.left = v.mobilePopupPositionL+'px';
    } else {
        cssAttr.left = '0px';
    }

    cssAttr.paddingBottom = '10px';

    var ele = $('<div />').attr('id', v.popupCode).css(cssAttr);
    $.ajax({
        type: 'GET'
        , url: '../popup/popup.php?sno='+v.sno
        , async: false
        , success: function(data) {
            ele.append(data);
        }
    });
    ele.appendTo('body');
    if (type == 'move') {
        ele.css('cursor','move').draggable();
    }
}

/**
 * 상단 고정 레이어
 */
function gd_main_popup_top(v)
{
    var cssAttr = {'z-index':'1000', 'position':'absolute', 'top':'0', 'left':'0', 'width':'100%', 'background':'#fff'};
    var ele = $('<div />').attr('id', v.popupCode).css(cssAttr);
    $.ajax({
        type: 'GET'
        , url: '../popup/popup.php?sno='+v.sno
        , async: false
        , success: function(data) {
            ele.append(data);
        }
    });
    ele.prependTo('.top_area');
}

function gd_popup_top_cookie (code) {
    $('#' + code).find('input[name="todayUnSee"]').trigger('click');
}

function gd_hide_top_popup(type, code, t) {
    var hideHeight = maxHeight = minusHeight = 0;
    $('div[id^="popupCode_"]:visible .mobile_top_banner').each(function(){
        if (code != $(this).parent().parent().prop('id')) {
            if ($(this).outerHeight(true) > maxHeight) {
                maxHeight = $(this).outerHeight(true);
            }
        } else {
            hideHeight = $(this).outerHeight(true);
        }
    });

    if (hideHeight > maxHeight && (hideHeight - maxHeight) > 0) {
        minusHeight = hideHeight - maxHeight;
    }

    if (minusHeight > 0) {
        $('.top_area').height($('.top_area').height() - minusHeight);
    }

    var head_top = Number($('#header_wrap').css('top').replace('px', '')) - $('#' + code).height();
    headTotalHeight = head_top;
    $('#header_wrap').css('top', head_top + 'px');

    switch (type) {
        case '1':
            gd_popup_cookie(code, t);
            break;
        case '2':
            $('#' + code).hide();
            break;
    }
}

var headHeight = headTotalHeight = 0;
$(window).scroll(function(){
    var headTop = headTotalHeight - headHeight;
    if (headTop > $(window).scrollTop()) {
        $('#header_wrap').css('top', headTotalHeight - $(window).scrollTop() + 'px');
    } else {
        $('#header_wrap').css('top', '0px');
    }
});
