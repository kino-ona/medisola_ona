jQuery(document).ready(function() {
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
			spaceBetween: 0,
			loop:true,
			slidesPerView: 1,
			autoplay: {
				delay: 4500,
				disableOnInteraction: false,
			},
			pagination: {
				el: '.swiper-pagination-sec01',
				clickable: true,
			},
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


	/* 메인04 : 태그 배너 */
	var swiper_sec04 = new Swiper('.swiper_sec04', {
		slidesPerView: 1,
		spaceBetween : 24,
		watchOverflow: 'true',
		loop: true,
		speed:1000,
        autoplay: {
			delay: 7000,
			disableOnInteraction: false,
		},
        pagination: {
			el: ".swiper-pagination-sec04",
			type: "custom",
			renderCustom: function (swiper, current, total) {
				var m_current = current;
				var m_total = total;
				if( m_total > 9 && m_current > 9) {				
					return '<span class="current">' + current + '</span><img src="/data/skin/mobile/medisola_dev/img/mimg/slash.svg" /><span class="total">' + total + "</span>"; 
				} else if ( m_total > 9 && m_current<=9 ){				
					return '<span class="current">0' + current + '</span><img src="/data/skin/mobile/medisola_dev/img/mimg/slash.svg" /><span class="total">' + total + "</span>"; 
				} else {		
					return '<span class="current">0' + current + '</span><img src="/data/skin/mobile/medisola_dev/img/mimg/slash.svg" /><span class="total">' + '0' + total + "</span>"; 
				}
			}
        },
	});


	/* 메인05 : 롤링 배너 */
	var swiper_sec05 = new Swiper('.swiper_sec05', {
		slidesPerView: 'auto',
		spaceBetween : 24,
		watchOverflow: 'true',
		loop: true,
		speed:1000,
        autoplay: {
			delay: 7000,
			disableOnInteraction: false,
		},
		pagination: {
			el: '.swiper-pagination-sec05',
			clickable: true,
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

	jQuery('.sec06_prd .tab_box').siblings('ul').each(function() {
		jQuery(this).find('.num').each(function(index){
			jQuery(this).append(index+1);
		})
	});


	/* 메인08 : 리뷰 모음 */
	var swiper_sec08 = new Swiper('.swiper_sec08', {
		slidesPerView: 1.2,
		spaceBetween: 24,
		watchOverflow: 'true',
		speed:1000,
        scrollbar: {
          el: ".swiper-scrollbar-sec08",
			dragSize : 120,
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

	jQuery('.swiper_sec09 .top .t02 p').each(function(){
		if(jQuery(this).html() == '<br>'){
			jQuery(this).remove();
		}
	});



	/* 메인10 : 분할 배너 */
	var sec10_ban = new Swiper('.sec10_ban', {
		slidesPerView: 1,
        spaceBetween : 24,
		watchOverflow: 'true',
		loop: true,
		speed:1000,
        autoplay: {
			delay: 7000,
			disableOnInteraction: false,
		},
        pagination: {
			el: ".swiper-pagination-sec10",
			type: "custom",
			renderCustom: function (swiper, current, total) {
				var m_current = current;
				var m_total = total;
				if( m_total > 9 && m_current > 9) {				
					return '<span class="current">' + current + '</span><img src="/data/skin/mobile/medisola_dev/img/mimg/slash.svg" /><span class="total">' + total + "</span>"; 
				} else if ( m_total > 9 && m_current<=9 ){				
					return '<span class="current">0' + current + '</span><img src="/data/skin/mobile/medisola_dev/img/mimg/slash.svg" /><span class="total">' + total + "</span>"; 
				} else {		
					return '<span class="current">0' + current + '</span><img src="/data/skin/mobile/medisola_dev/img/mimg/slash.svg" /><span class="total">' + '0' + total + "</span>"; 
				}
			}
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
	




	/* 메인04 : 키워드 */
	/* jQuery('section.mm_sec04 .keyword .box svg').click(function(){
		jQuery('section.mm_sec04 .keyword').toggleClass('open')
		jQuery('section.mm_sec04 .keyword ul').slideToggle()
	}) */


	/* 메인05 : 타임세일 */
	/* var swiper_5 = new Swiper('.mm_sec05 .swiper-container', {
		slidesPerView: 1.2,
		spaceBetween: 24,
		watchOverflow: 'true',
		speed:1000,
        scrollbar: {
			el: ".mm_sec05 .swiper-scrollbar",
			dragSize : 120,
        },
	}); */

});