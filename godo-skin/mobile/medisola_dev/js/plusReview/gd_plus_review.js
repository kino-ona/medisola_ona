$(function () {
    if (location.pathname === '/main/index.php' || location.pathname === '/') {
        gd_layer_plus_review_write();
    }

    $('body').on('click','#popupSelectGoods .js_layer_close',function(e){
        e.stopPropagation();
        $('.js_select_confirm').off('click');
        $('#popupSelectGoods').empty();
        $('#popupSelectGoods').hide();
    })
});


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

function gd_popup_plus_review_write(orderNo,sno,goodsNo) {
    var url = '../board/plus_review_mypage.php?orderNo='+orderNo+'&orderGoodsNo='+sno;
    if (!_.isEmpty(goodsNo)) {
        url += '&goodsNo=' + parseInt(goodsNo);
    }
    location.href = url;
}

function gd_layer_plus_review_write() {
    var thisScript =  $('script[src*=gd_plus_review]');
    var isPopup = 'n';
    if (thisScript.length > 0) {
        isPopup = thisScript.attr('src').split("popup=")[1];
    }
    if (isPopup == 'y') {
        $.ajax({
            type: 'get',
            url: '../board/plus_review_popup.php',
            data: {'mode': 'popupOpen'},
            dataType: 'text',
            async: true
        }).done(function (data) {
            if (data == 'empty') {
                return;
            }
            var html = '<div id="plusReviewPopup">' + data + '</div>';
            $('body').append(html);
            if ($('body #layerDim').length < 1) {
                var layerDim = '<div id="layerDim" class="dn zidx110"></div>';
                $('body').append(layerDim);
            }
            $('#layerDim').removeClass('dn');
            $('#layerDim').bind('click', function () {
                $(this).remove();
                $('.js-pr-popup-close').trigger('click');
            })
        });
    }
}