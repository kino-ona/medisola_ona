/**
 * 팝업 처리 스크립트
 * @author Shin Donggyu <artherot@godo.co.kr>
 */
$(function(){
    $.ajax({
        type: 'GET',
        url: '../popup/multi_popup_ps.php',
        data: {'mode':'popupOpen','currentUrl':encodeURIComponent(location.href)},
        dataType: 'json',
        async: true
    }).done(function(data) {
        if (_.isUndefined(data.error)) {
            $.each(data, function (key, value) {
                value.popupKindFl = "layer"; //모바일은 팝업 고정
                main_multi_popup_layer(value, value.popupKindFl);
            });
        }
    });
});

/**
 * 일반팝업창
 */
function main_multi_popup_window(v)
{
    var property = new Array();
    property.push('scrollbars=no, toolbar=no');
    if (v.popupSizeW != '') {
        property.push('width='+(v.popupSizeW+60));
    }
    if (v.popupSizeH != '') {
        property.push('height='+(v.popupSizeH+192));
    }
    if (v.popupPositionT != '') {
        property.push('top='+v.popupPositionT);
    }
    if (v.popupPositionL != '') {
        property.push('left='+v.popupPositionL);
    }
    var property = property.join(',');
    var win = window.open('../popup/multi_popup.php?sno='+v.sno, v.popupCode, property);
    if (win) {
        win.focus();
    }
}

/**
 * 고동레이어 & 이동레이어
 */
function main_multi_popup_layer(v, type)
{
    console.log(v);
    var cssAttr = {'position':'absolute','overflow':'hidden','z-index':'2000'};
    if (v.popupSizeW != null && v.popupSizeW != '') {
        cssAttr.width = (v.popupSizeW+60)+'px';
    }

    v.popupSizeH = v.popupSizeH+192;


    if (v.popupSizeH != null && v.popupSizeH != '') {
        cssAttr.height = v.popupSizeH+'px';
    }
    if (v.popupPositionT != null && v.popupPositionT != '') {
        cssAttr.top = v.popupPositionT+'px';
    } else {
        cssAttr.top = '0px';
    }
    if (v.popupPositionL != null && v.popupPositionL != '') {
        cssAttr.left = v.popupPositionL+'px';
    } else {
        cssAttr.left = '0px';
    }

    var ele = $('<div />').attr('id', v.popupCode).css(cssAttr);
    $.ajax({
        type: 'GET'
        , url: '../popup/multi_popup.php?sno='+v.sno
        , async: false
        , success: function(data) {
            ele.append(data);
        }
    });
    ele.appendTo('body');
    if (type == 'move') {
        ele.drags();
    }
}

(function($) {
    $.fn.drags = function(opt) {

        opt = $.extend({handle:"",cursor:"move"}, opt);

        if(opt.handle === "") {
            var $el = this;
        } else {
            var $el = this.find(opt.handle);
        }

        return $el.css('cursor', opt.cursor).on("mousedown", function(e) {
            if(opt.handle === "") {
                var $drag = $(this).addClass('draggable');
            } else {
                var $drag = $(this).addClass('active-handle').parent().addClass('draggable');
            }
            var z_idx = $drag.css('z-index'),
                drg_h = $drag.outerHeight(),
                drg_w = $drag.outerWidth(),
                pos_y = $drag.offset().top + drg_h - e.pageY,
                pos_x = $drag.offset().left + drg_w - e.pageX;
            $drag.css('z-index', 1000).parents().on("mousemove", function(e) {
                $('.draggable').offset({
                    top:e.pageY + pos_y - drg_h,
                    left:e.pageX + pos_x - drg_w
                }).on("mouseup", function() {
                    $(this).removeClass('draggable').css('z-index', z_idx);
                });
            });
            e.preventDefault(); // disable selection
        }).on("mouseup", function() {
            if(opt.handle === "") {
                $(this).removeClass('draggable');
            } else {
                $(this).removeClass('active-handle').parent().removeClass('draggable');
            }
        });

    }
})(jQuery);
