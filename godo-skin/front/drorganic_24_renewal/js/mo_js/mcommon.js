jQuery(document).ready(function() {
	new WOW().init();

	/* FIXED 상단 스크롤 */
	var s_left = jQuery(window).scrollLeft();
	jQuery('#mheader').css('margin-left',-s_left);
	jQuery(window).scroll(function(){
		s_left = jQuery(window).scrollLeft();
		jQuery('#mheader').css('margin-left',-s_left);
	});


	/* 상단 국가선택 */
	jQuery('#mheader .mh_sec03 .mh_lang li').each(function(){
		var lang = jQuery(this).children('a').text().toUpperCase();
		jQuery(this).children('a').text(lang);

		if( jQuery(this).hasClass('selected') ){
			jQuery('#mheader .mh_sec03 .mh_lang .current span').text(lang);
		}
	})

	/* 상단04 : 검색 */
	jQuery('.mh_sec03 .mh_search .search').click(function(){
		
		amplitude.logEvent('gnb_search_click');

		jQuery('.mh_sec04').slideDown('300',function(){
			jQuery(this).addClass('open')
		});
		jQuery('#mheader').addClass('search');
		jQuery('.mh_dimmed').show('300');
	})
	jQuery('.mh_sec03 .mh_search .close').click(function(){
		
		jQuery('.mh_sec04').slideUp('300',function(){
			jQuery(this).removeClass('open')
		});
		jQuery('#mheader').removeClass('search');
		jQuery('.mh_dimmed').hide('300');
	})



	/* Fixed SNS */
	jQuery('.mf_quick').click(function(){
		jQuery(this).toggleClass('open');
	});

	/* morenvy.com 추천상품, 신상품 순위 */
	jQuery('.best_item_view .item_photo_box .num').each(function(index) {
		jQuery(this).append(index+1);
	});
	



	/* 분류 : 카테고리 */
	/*jQuery('.menuCategory li').each(function(){
		if( jQuery(this).children('ul').length != 0 ){
			jQuery(this).addClass('Child')
		}
		if( jQuery(this).hasClass('selected') && jQuery(this).parent('ul').parent('li').length != 0){
			jQuery(this).parent('ul').prev('div').children('.more').trigger('click');
		}
	})
	jQuery('.menuCategory li.Child > div span.more').click(function(){
		jQuery(this).parent('div').toggleClass('open').next('ul').slideToggle();
	})
	setTimeout(function(){
		jQuery('.menuCategory li.selected').each(function(){
			if( jQuery(this).parent('ul').parent('li').length != 0 ){
				jQuery(this).parents('ul').prev('div').children('.more').trigger('click');
			}
		})
	},300)*/

	/* morenvy.com 상단 : 전체카테고리 */
	jQuery('#mheader #mcategory .all_cate').mouseover(function(){
		jQuery(this).children('.cate_list').stop().fadeIn(200);
	}).mouseout(function(){
		jQuery(this).children('.cate_list').stop().fadeOut(200);
	});

	/* 분류 : 추천상품 */	
	var swiper_best = new Swiper('.best_item_view .swiper_widget', {
		slidesPerView: 3,
		spaceBetween: 24,
		watchOverflow: 'true',
		speed:1000,
		scrollbar: {
		  el: ".best_item_view .swiper-scrollbar-widget",
		  dragSize: 384,
		},
	});


	/* Path 현재 카테고리 강조 */
	jQuery('.path li').not('.displaynone').last().addClass('now');


	/* 게시판 */
	jQuery('.xans-board-listpackage .xans-board-list li .contents').each(function(){
		var content = jQuery(this).text();
		jQuery(this).html(content)
	})



	
	cal_percent (); //할인율
	prd_opt();

	jQuery('.more_btn').click(function(){
		setTimeout(function(){
			cal_percent (); //할인율
			prd_opt();
		},500)
	})
	
});


/* morenvy.com 할인율 */
function cal_percent () {
	jQuery("[rel='판매가']").each(function(){
		var m_custom = jQuery(this).siblings("[rel='소비자가']").find('.m_item').text().replace(/[^0-9]/g,'');  //소비자가
		var m_price = jQuery(this).find('.m_item').text().replace(/[^0-9]/g,''); // 판매가
		var m_sale = '';
		if (jQuery(this).siblings("[rel='할인판매가']").find('#span_product_price_sale').length != 0 ){
			m_sale =  jQuery(this).siblings("[rel='할인판매가']").find('#span_product_price_sale').html().split('<')[0].replace(/[^0-9]/g,''); //상세페이지 할인판매가
		} else {
			m_sale = jQuery(this).siblings("[rel='할인판매가']").find('.m_item').text().replace(/[^0-9]/g,''); //상품목록 할인판매가
		}
		if( typeof m_sale == "undefined" || m_sale == "" || m_sale == null ) { //할인판매가가 공백일 때
			m_sale = m_price
		}

		/* 할인율 계산 */
		if(jQuery(this).siblings("[rel='할인판매가']").length != 0 && m_price != m_sale){
			//var c_sale = Math.round(  ( m_custom - m_sale ) / m_custom * 100 );  //소비자가와 할인판매가
			var p_sale = Math.round(  ( m_price - m_sale ) / m_price * 100 ); //판매가와 할인판매가

			jQuery(this).parents('li').find('.sale_box').text(p_sale + '%').insertBefore(jQuery(this).siblings("[rel='할인판매가']")); // 상품목록 할인율 출력
			jQuery(this).parents('.xans-product-detail').find('.price_box .sale_box').show().text(p_sale + '%'); // 상세페이지 할인율 출력

			/* 상품목록 */
			jQuery(this).addClass('through');
			jQuery(this).siblings("[rel='소비자가']").hide();
			jQuery(this).siblings("[rel='할인판매가']").addClass('msale');

			/* 상세페이지 */
			jQuery(this).parents('.xans-product-detail').find('.sale').addClass('msale');
			jQuery(this).parents('.xans-product-detail').find('.price').addClass('through');
			jQuery(this).parents('.xans-product-detail').find('.custom').hide();

		} else if (jQuery(this).siblings("[rel='소비자가']").length != 0){
			if (m_custom != m_price){
				var sale_cnt = Math.round(  ( m_custom - m_price ) / m_custom * 100 ); //소비자가와 판매가

				jQuery(this).parents('li').find('.sale_box').text(sale_cnt + '%').insertBefore(this); // 상품목록 할인율 출력
				jQuery(this).parents('.xans-product-detail').find('.price_box .sale_box').show().text(sale_cnt + '%'); // 상세페이지 할인율 출력

				/* 상품목록 */
				jQuery(this).addClass('msale');
				jQuery(this).siblings("[rel='소비자가']").addClass('through');

				/* 상세페이지 */
				jQuery(this).parents('.xans-product-detail').find('.price').addClass('msale');
				jQuery(this).parents('.xans-product-detail').find('.custom').addClass('through');
			} else {
				/* 상품목록 */
				jQuery(this).addClass('msale');
				jQuery(this).siblings("[rel='소비자가']").hide();

				/* 상세페이지 */
				jQuery(this).parents('.xans-product-detail').find('.price').addClass('msale');
				jQuery(this).parents('.xans-product-detail').find('.custom').hide();
			}
		} else {
			/* 상품목록 */
			jQuery(this).addClass('msale');
			
			/* 상세페이지 */
			jQuery(this).parents('.xans-product-detail').find('.price').addClass('msale');
			jQuery(this).parents('.xans-product-detail').find('.custom').hide();
		}
	})

};


function prd_opt () {
	jQuery('.ec-base-product .prdList > li').each(function(){
		/* morenvy.com 상품진열 장바구니 사용안할시 숨김 */
		if (jQuery(".pro_icon .mcart > .ec-admin-icon", this).length == 1) {
		} else {
			jQuery('.pro_icon .mcart', this).hide();
		}
		/* morenvy.com 상품진열 옵션미리보기 사용안할시 숨김 */
		if (jQuery(".pro_icon .moption > a", this).length == 1) {
			jQuery(this).addClass('a')
		} else {
			jQuery('.pro_icon .moption', this).hide();
		}
		/* morenvy.com 상품진열 관심상품 사용안할시 숨김 */
		if (jQuery(".pro_icon .mwish > .ec-product-listwishicon", this).length == 1) {
		} else {
			jQuery('.pro_icon .mwish', this).hide();
		}
	});

	/* 품절 아이콘 텍스트로 대체 */
	jQuery('.soldout_icon').each(function(){
		if( jQuery(this).children().length != 0 ){
			jQuery(this).text('SOLD OUT').addClass('on')
			jQuery(this).siblings('.pro_icon').hide();
		}
	});
}