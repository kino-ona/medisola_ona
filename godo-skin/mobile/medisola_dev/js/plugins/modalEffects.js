/**
 * modalEffects.js
 *
 * @author qnibus <qnibus@godo.co.kr>
 * @usage
 *  - 별도의 스크립트 설정없이 data-modal과 data-remote를 이용해 사용 가능하다.
 *  - 버튼양식 (필요에 따라  ly_setperspective 쓰는 효과가 있다 16~20번)
 *  <a href="[열고싶은 페이지 URL]" data-target="[#모달창 ID,.모달창 Class]" data-toggle="modal">링크</a>
 *
 *  - 모달양식(html_top.html 참조)
 *  <div class="lypop ly_effect_3" id="모달창 ID" class="모달창 class">
 *      <div class="ly_wrap">
 *          <h1>[제목]</h1>
 *          <div class="ly_cont">[내용]</div>
 *          <button class="ly_btn_close"><span class="sp">닫기</span></button>
 *      </div>
 *  </div>
 *
 *  - 자바스크립트 호출
 *  $('').modal(option);
 *  option
 *  - remote:
 *  - show:
 *  - params:
 */
;
(function($, window, undefined) {

    'use strict';

    $.Modal = function(options, element) {
        this.$el = $(element);
        this.init(options);
    };

    $.Modal.defaults = {
        remote: '',
        target: '',
        toggle: 'modal',
        params: '',
        cache: true,
        show: true
    };

    $.Modal.prototype = {
        init: function(options) {
            this.options = options;
            this.$wrapElement = this.$el.find('>.ly_wrap');
            this.$overlayClass = $('.ly_overlay');
        },
        config: function() {

        },
        hide: function() {
            var hideEvent = $.Event('hide.ps.modal');
            this.$el.trigger(hideEvent);
            if (hideEvent.isDefaultPrevented())
                return;

            var hideScrollFlag = false;
            if($('.ly_show').attr('id') == 'popupBoardView'){
                hideScrollFlag = true;
            }

            this.$el.removeClass('ly_show');

            $('body').css({overflow: 'inherit'});
            $('html').css({overflow:''});
            $('html, body').css({position: ''});
            
            $('.st_buy').show();
            $('.st-pusher').show();

            $(window).unbind('resize');
            if (this.$el.hasClass('ly_setperspective')) {
                $('html').removeClass('ly_perspective');
            }

            var hiddenEvent = $.Event('hidden.ps.modal');
            this.$el.trigger(hiddenEvent);
            if (hiddenEvent.isDefaultPrevented())
                return;

            if( hideScrollFlag == true && this.options.focus > 0){
                $('html, body').scrollTop($('#plus_reivew_focus_' + this.options.focus ).offset().top - 300);
            }

			
        },
        callRemote: function() {
            var self = this;
            var params = {
                url: this.options.remote,
                async: false
                //data: $.parseJSON(modal.data('params'))
            }

            if (typeof this.options.params == 'object' && this.options.params) {
                params.data = this.options.params;
            }

            if (this.options.type) {
                params.type = this.options.type;
            }

            if (this.options.remote) {
                var saveUrl = this.options.remote + (!_.isUndefined(params.data) ? '?' + $.param(this.options.params) : '');
                $.ajax(params).done(function(data) {
                    self.$el.find('.ly_wrap').html(data);
                    storage.saveVal('kCallRemoteUrl', saveUrl);
                });
            }
        },
        show: function() {
            var showEvent = $.Event('show.ps.modal');
            this.$el.trigger(showEvent);
            if (showEvent.isDefaultPrevented())
                return;

            if (!this.$el.find('.ly_wrap').length) {
                this.$el.wrapInner('<div class="ly_wrap"></div>');
                this.callRemote();
            } else {
                if (!this.options.cache) {
                    this.$el.find('.ly_wrap').empty();
                    this.callRemote();
                }
            }
            this.$wrapElement.width($(window).width());
            this.$wrapElement.height($(window).height());
            this.$wrapElement.find('.ly_ct').height($(window).height());
            this.$el.addClass('ly_show');

           $('html, body').css({overflow: 'hidden', position: 'fixed'});

            setTimeout(function() {
                // iOS10에서 본문창 스크롤해서 아래로 있는 경우 화면이 하얗게 안보이는 증상이 있어 무조건 상단으로 스크롤 잡아주도록 수정
                $('html body').scrollTop(0);
            }, 25);

            var self = this;

            if (this.$el.hasClass('ly_setperspective')) {
                setTimeout(function() {
                    $('html').addClass('ly_perspective');
                }, 25);
            }

            // add hash to location
            location.hash = this.$el.selector;

            var shownEvent = $.Event('shown.ps.modal');
            this.$el.trigger(shownEvent);
            if (shownEvent.isDefaultPrevented())
                return;
        }
    };

    $.fn.modal = function(option) {
        var options = $.extend({}, $.Modal.defaults, $(this).data(), typeof option == 'object' && option);
        var instance = $.data(this, 'ps.modal');
        if (instance) {
            instance.init();
        }
        else {
            instance = $.data(this, 'ps.modal', new $.Modal(options, this));
        }

        if (typeof option == 'string')
            instance[option]();
        else if (options.show)
            instance.show();

        $(document).one('click', '.ly_btn_close, .ly_overlay, .lys_btn_close', function(ev) {
            ev.stopPropagation();

            if ($('.ly_show').attr('id') == 'popupBoard' && $('#popupBoard > .ly_wrap').length > 0) {
                $('#popupBoard > .ly_wrap').remove();
            }

            if (location.hash != '') {
                var currentModalId = $('.ly_show').attr('id');
                if (currentModalId != 'popupCashReceiptRequest' && currentModalId != 'popupTaxInvoiceRequest') {
                    history.go(-1);//@todo 아이폰에서 스크롤을 내린 상태로 히스토리 백하는 경우 최상단으로 올라가는 증상 있음
                    //instance.history();
                }
                instance.hide();
            } else {
                instance.hide();
            }

        });

        return this;
    };
    $.fn.modal.Constructor = $.Modal

    // 태그를 찾아서 modal 함수를 적용시켜줌
    $(document).off('click.ps.modal.data-api', '[data-toggle="modal"]').on('click.ps.modal.data-api', '[data-toggle="modal"]', function(e) {
        e.stopPropagation();
        var $this = $(this);
        var href = _.isUndefined($this.attr('data-remote')) ? $this.attr('href') : $this.data('remote');
        var $target = $($this.attr('data-target') || (href && href.replace(/.*(?=#[^\s]+$)/, ''))); //strip for ie7
        var option = $target.data('ps.modal') ? 'toggle' : $.extend({remote: !/#/.test(href) && href}, $target.data(), $this.data());

        if ($this.is('a'))
            e.preventDefault();
        $target.modal(option);
    });

    // 주소창 #hash가 있을때
    $(window).hashchange(function(e) {
        $('.ly_pop').each(function(idx) {
            var target = '#' + $(this).attr('id');
            if (target == location.hash) {
                if (!$(target).hasClass('ly_show')) {
                    if ($(target).find('div').length) {
                        $(target).modal('show');
                    } else {
                        if (storage.loadVal('kCallRemoteUrl')) {
                            var saveUrl = storage.loadVal('kCallRemoteUrl');
                            $.each($('[data-target="' + target + '"]'), function(key, val){
                                if ($(val).attr('href') == saveUrl) {
                                    $(val).trigger('click.ps.modal.data-api');
                                    return false;
                                }
                            });
                        }
                    }
                }
            } else {
                // 열린창 있으면 닫아
                if ($(target).hasClass('ly_show')) {
                    //뒤로가기 클릭 후 닫기 버튼 클릭시 history.go() 실행되는 현상이 있어서 클릭 트리거 추가
                    if( $(target).attr('id') == 'popupBoardView'){
                        $('.ly_btn_close').trigger('click');
                    }else{
                        $(target).modal('hide');
                    }
                }

            }
        });
    });
    $(window).hashchange();


    $(document).on('focusin focusout', '.ly_show :input:not([type="button"], [type="submit"], [type="checkbox"], [type="radio"], [id="btnCouponApply"])', function(e){
        var exceptModalFocus = false;

        switch($('.ly_show').attr('id')) {
            case "popupRefund":
            case "popupBack":
            case "popupExchange":
            case "popupBoard":
            case "popupBoardView":
            case "popupBoardWrite":
            case "popupShipping":
            case "popupDeliveryMethod":
            case "popupEstimatedDeliveryDate":
                exceptModalFocus = true;
        }

        if($(this).attr('id') !='sch' && exceptModalFocus != true) {
            switch(e.type) {
                case "focusin":
                    $('.ly_show').css('position', 'absolute');
                    break;
                case "focusout":
                    $('.ly_show').css('position', 'fixed');
                    break;
            }
        }
    });

})(jQuery, window);
