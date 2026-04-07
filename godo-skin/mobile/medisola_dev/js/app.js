var kFilterSaveSize = 'key_filter_save_size'; //상세필터내 사이즈 저장
var kFilterSaveSizeList = 'key_filter_save_size_list'; //상세필터내 사이즈 저장

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

$(function(){
	// 주소창 지우기

	$.cookie('gdUseSkin', 'v2');

	// Initialized key value
	if (_.isNull(storage.loadVal(kFilterSaveSizeList))) {
		storage.saveVal(kFilterSaveSize,'');
		storage.saveVal(kFilterSaveSizeList,'');
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
});
