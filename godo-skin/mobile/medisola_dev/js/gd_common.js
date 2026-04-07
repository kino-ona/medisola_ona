/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall5 to newer
 * versions in the future.
 *
 * 공용 스크립트 및 프로토타입 정의
 *
 * @copyright ⓒ 2016, NHN godo: Corp.
 * @link http://www.godo.co.kr
 */
// 멀티상점 변수 기본처리
if (typeof gdCurrencyDecimalFormat === 'undefined') {
    gdCurrencyDecimal = 0;
    gdCurrencyDecimalFormat = 0;
}

// IE9에서 console 객체가 없어 console 객체가 없는 경우 log로 사용하도록 처리
if (!window.console) console = {log: function() {}};

// IE8 이하에서 Array.indexOf 지원하지 않는 경우에 대한 대응
if (typeof Array.prototype.indexOf !== 'function') {
    Array.prototype.indexOf = function (ele) {
        return $.inArray(ele, this);
    };
}

// IE8 이하에서 String.trim 지원하지 않는 경우에 대한 대응
if (typeof String.prototype.trim !== 'function') {
    String.prototype.trim = function(){
        return $.trim(this);
    };
}

// @qnibus bugfix. toFixed 사용시 무조건 반올림 처리하는 부분으로 인해 고도몰5의 정책과 맞지 않아서 toFixed 대신 사용해야 함
if (typeof Number.prototype.toRealFixed !== 'function') {
    Number.prototype.toRealFixed = function (digits, format) {
        if (typeof digits === 'undefined') {
            digits = gdCurrencyDecimal;
        }
        if (typeof format === 'undefined') {
            format = gdCurrencyDecimalFormat;
        }

        _value = this.valueOf();
        if(/[9]{9}/g.test(_value)) _value = _value.toFixed(9);
        return numeral(Math.floor( _value * Math.pow(10, digits)) / Math.pow(10, digits)).format('0,' + format);
    };
}

var kFilterSaveSize = 'key_filter_save_size'; //상세필터내 사이즈 저장
var kFilterSaveSizeList = 'key_filter_save_size_list'; //상세필터내 사이즈 저장

/**
 * DOM 로드
 */
$(document).ready(function () {
    // ios에서만 제공되는 비표준 이벤트로 ios10에서 viewport가 작동되지 않는 버그 픽스 (메인만 적용 처리)
    $(document).on('gesturestart', function (event) {
        if (window.location.pathname == '/' || window.location.pathname == '/main/index.php') {
            event.preventDefault();
        }
    });

    // jQuery Validator 기본값 설정
    $.validator.setDefaults({
        onfocusout: false,
        onclick: false,
        onkeyup: false,
        errorPlacement: function (error, element) {
            // do nothing
        },
        invalidHandler: function (form, validator) {
            var errors = validator.numberOfInvalids();
            if (errors) {
                alert(validator.errorList[0].message);
                validator.errorList[0].element.focus();
            }
        },
        submitHandler: function (form) {
            form.submit();
        }
    });



    // Initialized key value
    if (_.isNull(storage.loadVal(kFilterSaveSizeList))) {
        storage.saveVal(kFilterSaveSize,'');
        storage.saveVal(kFilterSaveSizeList,'');
    }


    // 배송추적
    $(document).on('click', '.btn-delivery-trace', function(e) {
        e.preventDefault();
        gd_make_layer_popup('../share/delivery_trace.php?invoiceCompanySno=' + $(this).data('invoice-company-sno') + '&invoiceNo=' + $(this).data('invoice-no'), __('배송추적'));
    });

    // 체크박스 선택 시 라벨 클래스Add/Remove
    if ($(':checkbox.checkbox').length > 0) {
        $(':checkbox.checkbox').click(function (e) {
            var $target = $(e.target);
            var $label = $target.closest('p').find('label');
            if ($target.prop('checked')) {
                $label.addClass('on');
            } else {
                $label.removeClass('on');
            }
        });
    }

    // Define Common Dom
    var commonDom = {
        body: $('body'),
        header: $('#header_wrap'),
        searchForm: $('#searchForm'),
        keyword: $('#sch'),
        searchBn: $('.bn_srch'),
        searchClearBn: $('#frmSearchTop .bn_wrg'),
        favoriteButton: $('.ft_baro'),
        slideHeader: $('.slide_header'),
        brandSlide: $('#blind_brand'),
        layerShare: $('#header .ly_share'),
        layerZzim: $('#header .ly_zzim'),
        recentKeywordDeleteBn: $('.js_recent_keyword_delete'),
        recentKeywordAllDeleteBn: $('.js_recent_all_delete')
    };

    $('.disabled').on('click', function(e){
        if($(this).parent('li').length && !$(this).parent('li').hasClass('lstbx') && !$(this).is('div')) {
            e.stopPropagation();
            e.preventDefault();
        }
    });

    // 필터옵션 강제 높이 조정 (공간이 좁을 경우)
    var layerResize = {
        detailInfo: function() {
            if($('.cont_detail .ly_info').length) {
                var winHeight = $(window).height();
                var filHeight = $('.cont_detail .ly_info')[0].scrollHeight;
                var contHeight = winHeight - 213;
                if(filHeight > contHeight && $('.cont_detail .ly_info').is(':visible')) {
                    $('.cont_detail .ly_info').css({overflowY:'scroll',height:contHeight});
                    $('html,body').css({overflow:'hidden'});
                } else {
                    $('.cont_detail .ly_info').css({overflowY:'auto',height:'auto'});
                    $('html,body').css({overflow:''});
                }
            }
        }
    };

    // 상품상세 플로팅 메뉴 강제 높이 조정 (공간이 좁을 경우)
    $(document).bind('click', '.btn_info', function(e){
        layerResize.detailInfo();
    });

    $(window).on('orientationchange resize', function(e) {
        if (e.type != 'resize') {

        } else {
            layerResize.detailInfo();
        }

        if($(".js_goods_description").length) {
            $(".js_goods_description img").css("max-width",$(window).width()-20);
        }

        if($(".js_goods_detail_infotext").length) {
            $(".js_goods_detail_infotext img").css("max-width",$(window).width()-40);
        }

    });

    // 체크박스 전체 선택해제
    $('.js_check_all' ).on('click', function() {
        $( '.js_check' ).prop( 'checked', this.checked );
    });
    $('.js_check').on('click', function(){
        $('.js_check').each(function(idx){
            if($(this).prop('checked') == false) {
                $('.js_check_all').prop('checked', false);
                return false;
            }
        });
    });

    // 체크박스 전체 선택 이벤트
    if ($(':checkbox.gd_checkbox_all').length > 0) {
        // 이벤트 중복 실행을 막아준다.
        $(':checkbox.gd_checkbox_all').off('click');
        $(':checkbox.gd_checkbox_all').click(function (e) {
            var $target = $(e.target);
            var targetName = $target.data('target-name');
            var targetId = $target.data('target-id');
            var targetFormName = $target.data('target-form');
            if (typeof targetFormName == 'undefined') targetFormName = "";
            if (_.isUndefined(targetId)) {
                $(targetFormName + ' :checkbox[name="' + targetName + '"]').prop('checked', !$target.prop('checked')).trigger('click');
            } else {
                $(targetFormName + ' :checkbox[id*="' + targetId + '"]').prop('checked', !$target.prop('checked')).trigger('click');
            }
        });
    }

    // 뒤로 이동
    $(document).on('click', '.js_bn_back', function(e){
        e.preventDefault();
        if(document.referrer.indexOf(document.location.hostname) != -1) {
//			if(location.hash.indexOf('#') != -1) history.go(-2);
            history.go(-1);
        } else {
            location.href = '/';
        }
    });

    $(document).on('click', '#header_wrap .sub_top .js_page_reload', function(e){
        e.preventDefault();
        window.location.reload();
    });

    // 스크롤시 TOP 버튼 출력
    var rememberPositionY = 0;


    // 검색버튼 클릭
    commonDom.searchBn.click(function(e){
        $('#popupSearch').modal({
            show: true
        });
        commonDom.keyword.focus();
    });

    // 검색 텍스트박스
    commonDom.keyword
        .click(function(e){
            if($(this).val().length > 0) {
                $(this).focus().val($(this).val());//키워드가 있는 경우 마지막 문자로 이동
            } else $(this).focus();
        })
        .on("focus", function(e) {
            // e.stopPropagation();
        })
        .on("blur", function(e) {

        })
        .on("keyup", function(e) {// 키보드입력
            var str = $(this).val();
            if(str.length > 0)
                commonDom.searchClearBn.show();
            else
                commonDom.searchClearBn.hide();
        });

    // 검색 클리어버튼
    commonDom.searchClearBn.click(function(e){
        commonDom.keyword.val('');
        commonDom.keyword.trigger('click');
        commonDom.keyword.trigger('keyup');
    });

    // 최근검색어 삭제
    commonDom.recentKeywordDeleteBn.click(function(e){
        e.stopPropagation();
        $self = $(this);
        $.post('../goods/goods_ps.php', {
            'mode': 'delete_recent_keyword',
            'keyword': $(this).data('recent-keyword')
        }, function (data, status) {
            // 값이 없는 경우 성공
            if (status == 'success' && data == '') {
                if ($self.closest('ul').find('li').length == 1) {
                    $self.closest('li').remove();
                    $('.js_recent_all_delete').remove();
                    $('.recent_list ul.latest_search_list').append('<li class="no-data"><a>' + __('최근 검색어가 없습니다.') + '</a></li>');
                } else {
                    $self.closest('li').remove();
                }
            } else {
                console.log('request fail. ajax status (' + status + ')');
            }
        });
    });

    // 최근검색어 전체 삭제
    commonDom.recentKeywordAllDeleteBn.click(function(e){
        e.stopPropagation();
        $.post('../goods/goods_ps.php', {
            'mode': 'delete_recent_all_keyword'
        }, function (data, status) {
            // 값이 없는 경우 성공
            if (status == 'success' && data == '') {
                $('.recent_list ul.latest_search_list li').remove();
                $('.js_recent_all_delete').remove();
                $('.recent_list ul.latest_search_list').append('<li class="no-data"><a>' + __('최근 검색어가 없습니다.') + '</a></li>');
            } else {
                console.log('request fail. ajax status (' + status + ')');
            }
        });
    });

    // 패스워드 인증 레이어창 닫기 클릭
    $('.cite_layer .close').click(
        function() {
            $('input[name="writerPw"]').val('');
            $(this).parent().parent().addClass('dn');
            $('#layerDim').addClass('dn');
            //$('html').removeClass('oh-space');
            //$('#scroll-left, #scroll-right').removeClass('dim');
            return false;
        }
    );

    $("ul.category li button").click(function (e) {
        if ($(this).data('key')) location.href = "../goods/goods_list.php?cateCd=" + $(this).data('key');
    });


    $("ul.brand li button").click(function (e) {
        if ($(this).data('key')) location.href = "../goods/goods_list.php?brandCd=" + $(this).data('key');
    });

    // 복사 기능
    if ($('.gd_clipboard').length) {
        var clipboard = new Clipboard('.gd_clipboard');
        clipboard.on('success', function (e) {
            var title = $(e.trigger).attr('title') == undefined ? '' : $(e.trigger).attr('title');
            alert(__('[%1$s] 정보를 클립보드에 복사했습니다.%2$sCtrl+V를 이용해서 사용하세요.', [title, '\n']));
            e.clearSelection();
        });
        clipboard.on('error', function (e) {
            console.error('Action:', e.action);
            console.error('Trigger:', e.trigger);
        });
    }


    //검색어
    $("#frmSearchTop").validate({
        submitHandler: function (form) {
            if ($("#frmSearchTop input[name='adUrl']").val() && $("#frmSearchTop input[name='keyword']").val() == '') document.location.href = $("#frmSearchTop input[name='adUrl']").val();
            else form.submit();
        },
        rules: {
            keyword: {
                required: function () {

                    if ($("#frmSearchTop input[name='adUrl']").val()) {
                        return false;
                    }
                    else {
                        return true;
                    }
                }
            }
        },
        messages: {
            keyword: {
                required: __('검색어를 입력하세요.')
            }
        }
    });

    // 최근 검색어
    var search_tab_len = $('.latest_search_tab').length;
    if (search_tab_len == 1) {
        $('.latest_search_tab').addClass('on');
        $('.latest_search_list_box').removeClass('dn');
    }
    $('.latest_search_tab').click(function(){
        var index = $(this).index('.latest_search_tab');

        $('.latest_search_tab').removeClass('on');
        $(this).addClass('on');

        $('.latest_search_list_box').addClass('dn');
        $('.latest_search_list_box:eq(' + index + ')').removeClass('dn');
    });

    // 즐겨찾기 (안드로이드 계열인 경우만 즐겨찾기 보이고 나머지 제거)
    if ($('.js_add_favorite').length > 0) {
        if (/Android/i.test(navigator.userAgent) === true) {
            $('.js_add_favorite').show();
            // 데이터 타이틀에 클릭 엘리먼트에 data-title="{=gMall.companyNm}" 이걸 넣어줄 것
            $('.js_add_favorite').click(function(e) {
                e.preventDefault();
                gd_add_favorite_launcher($(this).data('title'));
            });
        } else {
            $('.js_add_favorite').remove();
        }
    }

    // 미확인 입금자 팝업
    $('#ghostDepositorBanner').click(function (e) {
        var url = '/service/ghost_depositor.php';
        $('#popupGhostDepositor').modal({
            remote: url,
            cache: false,
            type : 'GET',
            show: true
        });
    });
});


// 포커스 함수  오버라이드
function focus() {
    $('.js_auto_focus').focus();
}
$(focus);

$.fn.quickChange = function(handler) {
    return this.each(function() {
        var self = this;
        self.qcindex = self.selectedIndex;
        var interval;
        function handleChange() {
            if (self.selectedIndex != self.qcindex) {
                self.qcindex = self.selectedIndex;
                handler.apply(self);
            }
        }
        $(self).focus(function() {
            interval = setInterval(handleChange, 100);
        }).blur(function() { window.clearInterval(interval); })
            .change(handleChange); //also wire the change event in case the interval technique isn't supported (chrome on android)
    });
};

// url 파라미터값 변경
function gd_replace_url_param(url, paramName, paramValue){
    var pattern = new RegExp('\\b(' + paramName + '=).*?(&|$)');

    if (paramValue == null) {
        paramValue = '';
    }

    if (url.search(pattern) >= 0) {
        return url.replace(pattern,'$1' + paramValue + '$2');
    }

    return url + (url.indexOf('?') > 0 ? '&' : '?') + paramName + '=' + paramValue
}

/**
 * 동적 스크립트 바인딩 (스크립트 로딩 후 메서드 실행되도록 처리)
 *
 * @author Jong-tae Ahn
 * @param number
 * @param places
 * @param symbol
 * @param thousand
 * @param decimal
 * @returns {string}
 */
function add_script(url, callback) {
    var done = false; // 스크립트 로딩 여부
    var head = document.getElementsByTagName("head")[0] || document.documentElement;
    var script = document.createElement("script");

    script.charset = 'UTF-8';
    script.src = url;
    script.onload = script.onreadystatechange = function () {
        if (!done && (!this.readyState || this.readyState === "loaded" || this.readyState === "complete")) {
            done = true;
            callback();

            // IE에서 메모리 누수 방지를 위한 처리
            script.onload = script.onreadystatechange = null;
            if (head && script.parentNode) {
                head.removeChild(script);
            }
        }
    };

    // Use insertBefore instead of appendChild  to circumvent an IE6 bug.
    // This arises when a base node is used (#2709 and #4378).
    head.insertBefore(script, head.firstChild);
}

/**
 * 메일 도메인 선택
 */
function gd_select_email_domain(name,select) {
    if (typeof select === 'undefined') {
        select = 'emailDomain';
    }
    var $email = $(':text[name=' + name + ']');
    var $emailDomain = $('select[id='+select+']');
    $emailDomain.on('change', function (e) {
        var emailValue = $email.val();
        var indexOf = emailValue.indexOf('@');
        if (indexOf == -1) {
            if ($emailDomain.val() === 'self') {
                $email.val(emailValue + '@');
            } else {
                $email.val(emailValue + '@' + $emailDomain.val());
            }
            $email.trigger('focusout');
        } else {
            if ($emailDomain.val() == 'self') {
                $email.val(emailValue.substring(0, indexOf + 1));
                $email.focus();
            } else {
                $email.val(emailValue.substring(0, indexOf + 1) + $emailDomain.val());
                $email.trigger('focusout');
            }
        }
    });
}

/**
 * 팝업창 Cookie 컨트롤
 * @param string name 팝업창 이름 (코드_창종류)
 * @param object elemnt elemnt
 * @return
 */
function gd_popup_cookie(name, elemnt) {
    if (elemnt.checked === true) {
        $.cookie(name, 'true', {path: '/', expires: 1});
        var popupKind = name.split('_');
        if (popupKind[1] == 'window') {
            setTimeout('self.close()');
        } else {
            setTimeout("$('#" + name + "').hide()");
        }
    } else {
        $.cookie(name, null);
    }
    return;
}

/**
 * 윈도우팝업 호출
 * @param array options 창정보
 * @return object Window 개체
 */
function gd_popup(options) {
    if (!options.width) options.width = 500;
    if (!options.height) options.height = 415;
    var status = new Array();
    $.each(options, function (i, v) {
        if ($.inArray(i, ['url', 'target']) == '-1') {
            status.push(i + '=' + v);
        }
    });
    var status = status.join(',');
    var win = window.open(options.url, options.target, status);
    return win;
}


/**
 * 도로명 주소 찾기 (팝업)
 *
 * @author artherot
 * @param string zoneCodeID zonecode input ID
 * @param string addrID address input ID
 * @param string zipCodeID zipcode input ID
 */
function gd_postcode_search(zoneCodeID, addrID, zipCodeID) {
    var url = '../share/postcode_search.php?zoneCodeID=' + zoneCodeID + '&addrID=' + addrID + '&zipCodeID=' + zipCodeID;
    gd_make_layer_popup(url, '');
}

/**
 * 모달창 안에서 별도의 모달을 띄우는 경우 사용해야 하며
 * 본창에서 모달을 띄우는 경우 modalEffect.js를 이용해서 처리해야 합니다.
 *
 * @param url
 * @param title
 */
function gd_make_layer_popup(url, title){
    var checkedVal = url.indexOf('cpid=kcp_'); // kcp 본인인증창인지 확인하기 위한 값
    var height = $('body').prop('scrollHeight');
    var fontSize = url ? 20 : 28;
    var _height = url ? 42 : 50;
    var margin = url ? 0 : '10px 0 0 0';
    var padding = 0;
    if (title) {
        padding = '42px 0 0 0';
    }
    if (!$('#layerSearch').length) {
        $('<div>', {
            css: {
                position: 'absolute',
                top: '0',
                left: '0',
                width: '100%',
                zIndex: '1001'
            },
            id: 'layerSearch'
        }).appendTo('body');
    }
    $('<div>', {
        css: {
            position: 'absolute',
            top: '0',
            left: '0',
            width: '100%',
            height: height + 'px',
            background: '#fff'
        },
        id: 'layerSearchArea'
    }).appendTo('#layerSearch');

    $('<div>', {
        css: {
            width: '100%',
            height: '100%',
            background: '#fff'
        },
        id: 'layerSearchInner'
    }).appendTo('#layerSearchArea')

    $('<iframe>', {
        css: {
            width: '100%',
            overflow: 'scroll',
            background: '#fff',
            padding: padding,
            height: '100%'
        },
        id: 'layerSearchFrame',
        name: 'layerSearchFrame',
        src: url,
        frameborder: 0
    }).appendTo('#layerSearchInner');
    if (title) {
        $('<div>', {
            class: 'ly_head',
            css: {
                height: _height + 'px',
            }
        }).appendTo('#layerSearch');

        $('<h1>', {
            html: title,
            class: 'h_tit',
            css: {
                'font-size': fontSize + 'px',
                'margin': margin
            }
        }).appendTo('#layerSearch .ly_head');

        if (checkedVal == -1) {
            $('<button>', {
                type: 'button',
                html: __('닫기'),
                css: {
                    position: 'absolute',
                    top: 0,
                    right: 0,
                    padding: '10px 10px 10px 10px'
                },
                class: 'lys_btn_close lys_close_btn',
                onclick: 'gd_layer_search_close()'
            }).appendTo('#layerSearch .ly_head');
        } else {
            $('<button>', {
                type: 'button',
                html: __('닫기'),
                css: {
                    position: 'absolute',
                    top: 0,
                    right: 0,
                    padding: '10px 10px 10px 10px'
                },
                class: 'lys_btn_close lys_close_btn',
                onclick: 'gd_layer_cancel_confirm()'
            }).appendTo('#layerSearch .ly_head');
        }
    }

    $('html, body').scrollTop(0);
}

function gd_layer_search_close()
{
    $("meta[name='viewport']").attr({"content":"user-scalable=yes, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, width=device-width"});
    $('#layerSearch').remove();

    // 마이앱 추가
    if ($("input[name='isMyapp']").val() && $(".adult_guest").css('display') == 'none') {
        parent.location.reload();
    }
}

function gd_layer_cancel_confirm()
{
    if ( !confirm("인증을 취소하시겠습니까?") ) {
        return false;
    } else {
        $("meta[name='viewport']").attr({"content":"user-scalable=yes, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, width=device-width"});
        $('#layerSearch').remove();

        // 마이앱 추가
        if ($("input[name='isMyapp']").val() && $(".adult_guest").css('display') == 'none') {
            parent.location.reload();
        }
    }
}

function gd_frame_check(){
    return $('#layerSearchFrame').length;
}


/**
 * 쿼리스트링값 찾기
 * @param query
 * @param variable
 * @returns {string}
 */
function gd_get_query_variable(query, variable) {
    var vars = query.split('&');
    for (var i = 0; i < vars.length; i++) {
        var pair = vars[i].split('=');
        if (decodeURIComponent(pair[0]) == variable) {
            return decodeURIComponent(pair[1]);
        }
    }
    console.log('Query variable %s not found', variable);
}

/**
 * 스트링 치환 메소드
 * @returns {String}
 */
String.prototype.format = function () {
    var formatted = this;
    for (var arg in arguments) {
        formatted = formatted.replace("{" + arg + "}", arguments[arg]);
    }
    return formatted;
};

function gd_resize_frame(obj) {
    var iframeHeight =
        (obj).contentWindow.document.body.clientHeight;
    (obj).height = iframeHeight + 80;

}


/**
 * 네이버앱을 이용한 안드로이드 전용
 * 바탕화면 즐겨찾기 추가하기
 *
 * @see 211 line
 * @param title 즐겨찾기 제목
 * @author Jong-tae Ahn <qnibus@godo.co.kr>
 * @document https://developers.naver.com/docs/utils/mobileapp
 */
function gd_add_favorite_launcher(title) {
    // 안드로이드가 아닌 경우 실행하지 않는다.
    if (!/Android/i.test(navigator.userAgent)) {
        return false;
    }

    // 홈버튼 추가에 필요한 설정 초기화
    var favoriteData = {
        iframe: $('#ifrmAddFavoriteLauncher'),
        defaultIconName: 'defaultShopIcon.png',
        title: encodeURI(title),
        code: 'nstore',
        icon: '',
    };

    // link의 아이폰 아이콘 url 가져오기
    $('link').each(function(idx){
        if ($(this).prop('rel') === 'apple-touch-icon-precomposed') {
            // prop으로 href를 가져오는 경우 fullurl을 가져오며 https인 경우 http로 전환시킨다.
            favoriteData.icon = $(this).prop('href').replace('https', 'http');
            return false;
        }
    });

    // 아이콘이 없는 경우 네이버앱에서 메시지 없이 오류가 발생하기 때문에 반드시 아이콘 URL을 넣어 처리해야 한다.
    if (favoriteData.icon !== '') {
        // 앱설치 페이지 혹은 바탕화면 추가하기 (기획에 요청해서 메시지 변경하기)
        alert(__('%1$s을(를) 홈화면에 추가합니다.%2$s네이버앱이 없는 고객님께서는 네이버앱 설치페이지로 이동됩니다.', [decodeURI(favoriteData.title), '\n\n']));

        // 네이버앱 연결 url scheme (Intent로 하지 않는 경우 네이버 앱이 없을때 자동으로 이동하지 않는다)
        // var scheme = 'naversearchapp://addshortcut?url=' + encodeURI(document.domain) + '&icon=' + favoriteData.icon + '&title=' + favoriteData.title + '&serviceCode=' + favoriteData.code + '&version=7';
        var scheme = 'intent://addshortcut?url=' + encodeURI(gdGlobalHomeUri) + '&icon=' + favoriteData.icon + '&title=' + favoriteData.title + '&serviceCode=' + favoriteData.code + '&version=7#Intent;scheme=naversearchapp;action=android.intent.action.VIEW;category=android.intent.category.BROWSABLE;package=com.nhn.android.search;end';

        if (favoriteData.iframe.length > 0) {
            favoriteData.iframe.attr('src', scheme);
        } else {
            location.href = scheme;
        }
    }
}

/**
 회원 가입 생일/결혼기념일 날짜 설정
 */
$.fn.initSelectDateFormat = function () {
    var years, months, days, yearsHtml, monthsHtml, daysHtml;
    years = this;
    months = this.next('select');
    days = months.next('select');

    yearsHtml = [];
    if (years.length < 1) {
        return;
    }
    var fullYear = new Date().getFullYear();
    for (var y = 1900; y <= fullYear; y++) {
        yearsHtml[y] = '<option value="' + y + '">' + y + '</option>';
    }
    years.append(yearsHtml.join(''));
    if (months.length > 0) {
        monthsHtml = [];
        for (var m = 1; m < 13; m++) {
            monthsHtml[m] = '<option value="' + m + '">' + m + '</option>';
        }
        months.append(monthsHtml.join(''));
    }

    if (days.length > 0) {
        $(years, 'body').change(function () {
            updateNumberOfDays();
        }).trigger('change');
        $(months, 'body').change(function () {
            updateNumberOfDays();
        }).trigger('change');
    }

    function updateNumberOfDays() {
        days.html('');
        var daysDate = new Date(years.val(), months.val(), 0).getDate();
        daysHtml = [];
        for (var d = 1; d < daysDate + 1; d++) {
            daysHtml[d] = '<option value="' + d + '">' + d + '</option>';
        }
        days.append(daysHtml.join(''));
    }
};

/**
 * PG 관련 영수증 보기
 *
 * @author artherot
 * @param string modeStr 카드, 현금영수증 종류 (card, cash)
 * @param string orderNo 주문 번호
 */
function gd_pg_receipt_view(modeStr, orderNo) {
    // 각 PG별 영수증 팝업창
    $.post('../share/show_receipt.php', {
        mode: modeStr,
        orderNo: orderNo
    }, function (data) {
        var infoData = data;
        if (typeof infoData['error'] == 'undefined') {
            gd_make_layer_popup(infoData['url'], __('결제영수증 조회'));
        }
        else {
            alert(infoData['error']);
        }
    }, 'json');
}

/**
 * 회원다운로드 쿠폰 링크 다운 받기
 *
 * @param couponNo
 */
function couponLinkDown(couponNo) {
    var params = {
        mode: "couponDownLink",
        couponCode: couponNo
    };
    $.ajax({
        method: "POST",
        async: false,
        cache: false,
        url: "../mypage/coupon_link_down.php",
        data: params,
        success: function (data) {
            result = JSON.parse(data);
            alert(result['msg']);
        },
        error: function (data) {
            alert(result['msg']);
        }
    });
}

/**
 * 비회원 개인정보 수집항목 동의 링크
 */
function gd_redirect_collection_agree(){
    window.open('../service/private.php');
}

/*** 스토리지 지원 여부 ***/
function supports_html5_storage() {
    try {
        return 'localStorage' in window && window['localStorage'] !== null;
    } catch (e) {
        return false;
    }
}

/*** 세션 스토리지 저장 ***/
function gd_save_session(control_key, control_value) {
    if (!supports_html5_storage()) {
        gd_create_cookie(control_key, control_value, 7);
    } else {
        sessionStorage[control_key] = control_value;
    }
};

/*** 세션 스토리지 로드 ***/
function gd_load_session(control_key) {
    var control_value;
    if (!supports_html5_storage()) {
        control_value = gd_read_cookie(control_key);
    } else {
        control_value = sessionStorage[control_key];
    }
    return control_value;
};

/*** 로컬 스토리지 저장 ***/
function saveVal(control_key, control_value) {
    if (!supports_html5_storage()) {
        createCookie(control_key, control_value, 7);
    } else {
        localStorage.setItem(control_key, control_value);
    }
};

/*** 로컬 스토리지 로드 ***/
function loadVal(control_key) {
    var control_value;
    if (!supports_html5_storage()) {
        control_value = readCookie(control_key);
    } else {
        control_value = localStorage.getItem(control_key);
    }
    return control_value;
};

/*** 쿠키 생성 ***/
function gd_create_cookie(name, value, days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        var expires = "; expires=" + date.toGMTString();
    } else
        var expires = "";
    document.cookie = name + "=" + value  + "; path=/; expires=" + expires + ";";
};

/*** 쿠키 호출 ***/
function gd_read_cookie(name) {
    var result = "";
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) {
            result = c.substring(nameEQ.length, c.length);
        }
    }
    return result;
}

var gdAjaxUpload = {
    isSuccess : false,
    upload : function(data) {
        var formData = new FormData();
        for (var k in data.params){
            if (data.params.hasOwnProperty(k)) {
                formData.append(k, data.params[k]);
            }
        }
        if(data.onbeforeunload){
            window.onbeforeunload = data.onbeforeunload;
            data.formObj.on("submit", function () {
                window.onbeforeunload = null;
            });
        }

        if(data.formObj.find('[name=uploadType][value=ajax]').length < 1) {
            data.formObj.append('<input type="hidden"  name="uploadType" value="ajax" >');
        }
        var index = data.thisObj.closest('form').find('input:file').index(data.thisObj);
        formData.append('uploadFile', data.thisObj[0].files[0]);

        $.ajax({
            url: data.actionUrl,
            type: 'POST',
            data: formData,
            async: false,
            cache: false,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function (returnData) {
                returnData['index'] = index;
                if(returnData.result == 'ok') {
                    gdAjaxUpload.isSuccess = true;
                    if ($('input[name="uploadFileNm[' + index + ']"]').length == 0) {
                        data.formObj.append("<input type='hidden' name='uploadFileNm[" + index + "]' value='" + returnData.uploadFileNm + "'>");
                        data.formObj.append("<input type='hidden' name='saveFileNm[" + index + "]' value='" + returnData.saveFileNm + "'>");
                    }
                    else if(returnData.result == 'cancel'){
                        if ($("input[name='uploadFileNm[" + index + "]']").length >0) {
                            $("input[name='uploadFileNm[" + index + "]']").remove();
                            $("input[name='saveFileNm[" + index + "]']").remove();
                        }
                    }
                    else {
                        $("input[name='uploadFileNm[" + index + "]']").val(returnData.uploadFileNm);
                        $("input[name='saveFileNm[" + index + "]']").val(returnData.saveFileNm);
                    }
                    if(typeof data.successAfter == 'function') {
                        data.successAfter(returnData);
                    }
                }
                else {
                    gdAjaxUpload.isSuccess = false;
                    if (returnData.errorMsg) {
                        alert(returnData.errorMsg);
                    }
                    if(typeof data.failAfter == 'function') {
                        data.failAfter(returnData);
                    }
                }
            }
        });
    }
}

/**
 * 기준화폐 환율변환
 *
 * @param price 금액
 * @param isFormat 포맷여부
 * @returns {*}
 */
function gd_money_format(price, isFormat) {
    var convertPrice = fx.convert(price).toRealFixed();
    if (typeof isFormat !== 'undefined') {
        if (isFormat) {
            return numeral().unformat(convertPrice);
        }
    }

    return convertPrice;
}

/**
 * 추가화폐 환율변환
 *
 * @param price 금액
 * @param isFormat 포맷여부
 * @returns {*}
 */
function gd_add_money_format(price, isFormat) {
    var convertPrice = fx.convert(price, {to: gdCurrencyAddCode}).toRealFixed(gdCurrencyAddDecimal, gdCurrencyAddDecimalFormat);
    if (typeof isFormat !== 'undefined') {
        if (isFormat) {
            return numeral().unformat(convertPrice);
        }
    }

    return convertPrice;
}

/**
 * Remove all component goods if the parent goods is not checked
 * In other words, all component goods should be checked if the parent goods is checked
 */
function removeUncheckedComponentGoods() {
    var checkedValues = [];
    $('input[name*="orderGoodsNo"]:checkbox:checked').each(function () {
        checkedValues.push($(this).val());
    });

    $('input[name*="orderGoodsNo"][data-parent-order-goods-sno]').each(function () {
        var parentOrderGoodsSno = $(this).data('parent-order-goods-sno');
        if (!checkedValues.includes(parentOrderGoodsSno.toString())) {
            $(this).remove();
        }
    });
}