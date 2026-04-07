let currentSlideIndex = 0;

jQuery(document).ready(function() {
	/* FIXED 상단 스크롤 */
	if( jQuery(window).scrollTop() == 0 ){
		jQuery('#mheader').addClass('fixed');
		if (darkSlideIndices.includes(currentSlideIndex)) {
			jQuery('.mh_sec01').addClass('bright_logo');
			jQuery('.mh_sec03').addClass('bright_logo');
			jQuery('.mh_sec02').addClass('bright_font_color');
		}
	} else {
		jQuery('#mheader').removeClass('fixed');
		jQuery('.mh_sec01').removeClass('bright_logo');
		jQuery('.mh_sec03').removeClass('bright_logo');
		jQuery('.mh_sec02').removeClass('bright_font_color');
	};
	jQuery(window).scroll(function(){
		if( jQuery(window).scrollTop() == 0 && !jQuery('#mheader').hasClass('mouse_on')){
			jQuery('#mheader').addClass('fixed');
			if (darkSlideIndices.includes(currentSlideIndex)) {
				jQuery('.mh_sec01').addClass('bright_logo');
				jQuery('.mh_sec03').addClass('bright_logo');
				jQuery('.mh_sec02').addClass('bright_font_color');
			}
		}else {
			jQuery('#mheader').removeClass('fixed');
			jQuery('.mh_sec01').removeClass('bright_logo');
			jQuery('.mh_sec03').removeClass('bright_logo');
			jQuery('.mh_sec02').removeClass('bright_font_color');
		};
	});

	jQuery('#mheader').mouseover(function(){
		jQuery(this).addClass('mouse_on')
		jQuery('#mheader').removeClass('fixed');
		jQuery('.mh_sec01').removeClass('bright_logo');
		jQuery('.mh_sec03').removeClass('bright_logo');
		jQuery('.mh_sec02').removeClass('bright_font_color');
	}).mouseout(function(){
		jQuery(this).removeClass('mouse_on')
		if (jQuery(window).scrollTop() == 0){
			jQuery('#mheader').addClass('fixed');
			if (darkSlideIndices.includes(currentSlideIndex)) {
				jQuery('.mh_sec01').addClass('bright_logo');
				jQuery('.mh_sec03').addClass('bright_logo');
				jQuery('.mh_sec02').addClass('bright_font_color');
			}
		}
	})


	/* 메인01 : 메인 비주얼 */
	let options = {};
	if ( $('.swiper_sec01.swiper-container .swiper-slide').length == 1 ) { // 메인비주얼 1개만 있을때
		options = {
			slidesPerView: 1,
			loop: true,
			autoplay: true,
			touchRatio: 0,
		}
		jQuery('.swiper_sec01').addClass('hide_nav')
	} else {
		options = {
			slidesPerView: 1,
			loop: true,
			effect : 'fade',
			fadeEffect: {
				crossFade: true
			},
			speed:1000,
			autoplay: {
				delay: 4500,
				disableOnInteraction: false,
			},
			pagination: {
				el: '.swiper-pagination-sec01',
				clickable: true,
			},
			navigation: {
				nextEl: '.swiper-button-next-sec01',
				prevEl: '.swiper-button-prev-sec01',
			},
			onAny: (eventName, ...args) => {
				if (['slideNextTransitionStart', 'slidePrevTransitionStart'].includes(eventName)) {
					currentSlideIndex = args[0].activeIndex;
					if (darkSlideIndices.includes(currentSlideIndex) && jQuery(window).scrollTop() == 0 && !jQuery('#mheader').hasClass('mouse_on')) {
						jQuery('.mh_sec01').addClass('bright_logo');
						jQuery('.mh_sec03').addClass('bright_logo');
						jQuery('.mh_sec02').addClass('bright_font_color');
					} else {
						jQuery('.mh_sec01').removeClass('bright_logo');
						jQuery('.mh_sec03').removeClass('bright_logo');
						jQuery('.mh_sec02').removeClass('bright_font_color');
					} 
				}
			}
		}
	}
	var swiper_sec01 = new Swiper('.swiper_sec01', options);


	/* 메인01 : 메인 공지사항 */
	var swiper_board = new Swiper('.swiper_board', {
		slidesPerView: 1,
		watchOverflow: 'true',
		loop: true,
		effect : 'fade',
		fadeEffect: {
			crossFade: true
		},
		speed:1000,
        autoplay: {
			delay: 7000,
			disableOnInteraction: false,
		},
        navigation: {
			nextEl: '.swiper-button-next-board',
			prevEl: '.swiper-button-prev-board',
        },
		on: {
			init : function(){
				if( jQuery('.swiper_board .swiper-slide').not('.swiper-slide-duplicate').length == 1 ){
					jQuery('.swiper_board').addClass('disabled')
				}
			}
		}
	});

	/* 메인04 : 영양 솔루션 */
	var swiper_sec04 = new Swiper('.swiper_sec04', {
		spaceBetween: 0,
		slidesPerView: 1,
		autoHeight : true,
		effect: 'fade',
		fadeEffect: { crossFade: true },
		pagination: {
			el: '.swiper-pagination04',
			clickable: true,
		},
	});

	jQuery(".swiper_sec04_text li").click(function(){
		amplitude.logEvent('home_section_item_click', {section_title: '맞춤형 식단 제안', item_type: 'button', item_name: $(this).text()});
		var idx = jQuery(this).index();
		jQuery(".swiper_sec04_text li").removeClass("swiper_over")
		jQuery(this).addClass("swiper_over")
		jQuery(".swiper-pagination04 > span").eq(idx).trigger("click")
	})
	if (jQuery('.swiper_sec04_text li').length < 5){
		jQuery('.swiper_sec04_text li').addClass('sec04_grid')
	}

	jQuery(".swiper_sec04 .swiper-wrapper").bind("transitionend", function(){
		jQuery(".swiper-pagination04 > span").each(function(i){
			if( jQuery(this).hasClass("swiper-pagination-bullet-active") ){
				jQuery(".swiper_sec04_text li").removeClass("swiper_over")
				jQuery(".swiper_sec04_text li").eq(i).addClass("swiper_over")
			}
		})
	});


	/* 메인05 : 가로 배너 */
	var swiper_sec05 = new Swiper('.swiper_sec05', {
		slidesPerView: 1,
		watchOverflow: 'true',
		loop: true,
		effect : 'fade',
		fadeEffect: {
			crossFade: true
		},
		speed:1000,
        autoplay: {
			delay: 7000,
			disableOnInteraction: false,
		},
        navigation: {
			nextEl: '.swiper-button-next-sec05',
			prevEl: '.swiper-button-prev-sec05',
        },
	});


	/* 메인06 : 탭 상품 */
	jQuery('.mm_sec06 .sec06_tab > div').click(function(){
		if( !jQuery(this).hasClass('open') ){
			var now_tab = jQuery(this).attr('class')
			jQuery('.mm_sec06 .sec06_tab > div').toggleClass('open');
			jQuery('.mm_sec06 .sec06_prd > div').toggleClass('open');
		}
	})

	jQuery('.mm_sec06 div.goods_tab_box').each(function() {
		jQuery(this).find('.num').each(function(index){
			jQuery(this).append(index+1);
		})
	});


	/* 메인08 : 리뷰 모음 */
	var swiper_sec08 = new Swiper('.swiper_sec08', {
		slidesPerView: 3,
		spaceBetween: 24,
		watchOverflow: 'true',
		speed:1000,
        scrollbar: {
          el: ".swiper-scrollbar-sec08",
		  dragSize: 384,
        },
		on: {
			init : function(){
				jQuery('.swiper_sec08 .swiper-slide .top .t02').each(function(){
					var r_content = jQuery(this).text();
					jQuery(this).text(r_content);
				});
			},
		},
	});

	jQuery('.swiper_sec08 .top .t02 p').each(function(){
		if(jQuery(this).html() == '<br>'){
			jQuery(this).remove();
		}
	});

	/* 메인10 : 분할 배너 */
	var sec10_ban = new Swiper('.sec10_ban', {
		slidesPerView: 1,
		watchOverflow: 'true',
		loop: true,
		speed:1000,
        autoplay: {
			delay: 7000,
			disableOnInteraction: false,
		},
        navigation: {
			nextEl: '.swiper-button-next-sec10',
			prevEl: '.swiper-button-prev-sec10',
        },
	});
	var sec10_text = new Swiper('.sec10_text', {
		slidesPerView: 1,
		watchOverflow: 'true',
		loop: true,
		speed:1000,
	});

	sec10_ban.controller.control = sec10_text;
	sec10_text.controller.control = sec10_ban;


	/* 메인05 : 타임세일 */
	/*var swiper_sec05 = new Swiper('.mm_sec05 .swiper-container', {
		slidesPerView: 3,
		spaceBetween: 24,
		watchOverflow: 'true',
		speed:1000,
        scrollbar: {
          el: ".mm_sec05 .swiper-scrollbar",
		  dragSize: 384,
        },
	});*/

});