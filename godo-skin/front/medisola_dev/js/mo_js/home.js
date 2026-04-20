/**
 * Medisola Home - Desktop
 *
 * 현재 레이아웃 순서:
 *   SEC 1: Hero (MP4 가로 동영상 - 인라인 스크립트)
 *   SEC 2: 원플레이트밀 소개
 *   SEC 3: 슬라이더 배너 (위젯 자체 JS)
 *   SEC 4: 맞춤형 식단 선택형 메뉴 (위젯 자체 JS)
 *   SEC 5: 이 달의 집중 케어 (위젯 자체 JS)
 *   SEC 6: 푸드케어 레터 Magazine (위젯 자체 JS)
 *   SEC 7: 케어 스토리
 *   SEC 8: Instagram (위젯 자체 JS)
 */
jQuery(document).ready(function($) {

	/* ═══════════════════════════════════════
			Fixed Header: 스크롤 투명 처리
			Hero가 동영상(어두운 배경)이므로 스크롤 최상단에서
			헤더를 투명으로 전환합니다.
			═══════════════════════════════════════ */
	function applyVideoHeader() {
			// $('.mh_sec01').addClass('bright_logo');
			// $('.mh_sec03').addClass('bright_logo');
			// $('.mh_sec02').addClass('bright_font_color');
	}

	function removeVideoHeader() {
			$('.mh_sec01').removeClass('bright_logo');
			$('.mh_sec03').removeClass('bright_logo');
			$('.mh_sec02').removeClass('bright_font_color');
	}

	if ($(window).scrollTop() === 0) {
			$('#mheader').addClass('fixed');
			applyVideoHeader();
	}

	$(window).scroll(function() {
			if ($(window).scrollTop() === 0 && !$('#mheader').hasClass('mouse_on')) {
					$('#mheader').addClass('fixed');
					applyVideoHeader();
			} else {
					$('#mheader').removeClass('fixed');
					removeVideoHeader();
			}
	});

	$('#mheader').mouseover(function() {
			$(this).addClass('mouse_on');
			$('#mheader').removeClass('fixed');
			removeVideoHeader();
	}).mouseout(function() {
			$(this).removeClass('mouse_on');
			if ($(window).scrollTop() === 0) {
					$('#mheader').addClass('fixed');
					applyVideoHeader();
			}
	});

	/* ═══════════════════════════════════════
			SEC 1: Hero 메인 배너 Swiper
			═══════════════════════════════════════ */
	var $heroEl = $('.home-hero .swiper_sec01');
	if ($heroEl.length && typeof Swiper !== 'undefined') {
			var slideReal = $heroEl.find('> .swiper-wrapper > .swiper-slide').length;
			var heroOptions;
			if (slideReal <= 1) {
					heroOptions = {
						slidesPerView: 1,
						loop: true,
						autoplay: true,
						touchRatio: 0,
					};
					$heroEl.addClass('hide_nav');
			} else {
				heroOptions = {
						// slidesPerView: 1.25,
						slidesPerView: 'auto',
						centeredSlides: true,
						spaceBetween: 8,
						loop: true,
						loopedSlides: slideReal,
						speed: 600,
						watchOverflow: true,
						autoplay: {
							delay: 4500,
							disableOnInteraction: false,
						},
						pagination: {
							el: $heroEl.find('.swiper-pagination-sec01')[0],
							clickable: true,
							type: 'custom',
							renderCustom: function(swiper) {
								var pad = function(n) {
												return n < 10 ? '0' + n : '' + n;
								};
								var totalShown = parseInt(swiper.el.getAttribute('data-hero-total') || String(slideReal), 10) || slideReal;
								var cur = typeof swiper.realIndex === 'number' ? swiper.realIndex + 1 : 1;
								return '<span class="home-hero__pager"><span class="current">' + pad(cur) + '</span>' +
												'<span class="sep"> | </span><span class="total">' + pad(totalShown) + '</span></span>';
							},
						},
				};
			}
			new Swiper($heroEl[0], heroOptions);
	}

	/* ═══════════════════════════════════════
			SEC 3: 추천 메뉴(includeWidget) Swiper
			═══════════════════════════════════════ */
	function initRecommendSwiper() {
			if (typeof Swiper === 'undefined') return;

			$('.home-recommend .item_hl_tab_type .goods_tab_cont .goods_tab_box').each(function() {
					var $box = $(this);
					var $list = $box.children('ul').first();
					if (!$list.length) return;
					var $slides = $list.children('li');

					$box.addClass('home-recommend__swiper swiper-container');
					$list.addClass('swiper-wrapper');
					$slides.addClass('swiper-slide');
					$slides.css({
							width: '307px',
							flexShrink: '0'
					});

					var swiper = $box.data('recommendSwiper');
					if (swiper) {
							swiper.update();
							return;
					}

					swiper = new Swiper(this, {
							slidesPerView: 'auto',
							spaceBetween: 16,
							freeMode: true,
							allowTouchMove: true,
							touchRatio: 1,
							watchOverflow: true,
							observer: true,
							observeParents: true
					});
					$box.data('recommendSwiper', swiper);
			});
	}

	initRecommendSwiper();
	$(document).on('click', '.home-recommend .item_hl_tab_type .goods_tab_tit a', function() {
			setTimeout(initRecommendSwiper, 0);
	});


	/* ═══════════════════════════════════════
			SEC 7: 케어 스토리 Swiper
			PC에서는 3개 슬라이드 동시 노출
			═══════════════════════════════════════ */
	if ($('.home-care-story__swiper').length && typeof Swiper !== 'undefined') {
			new Swiper('.home-care-story__swiper', {
					slidesPerView: 2,
					spaceBetween: 32,
					loop: true,
					speed: 800,
					autoplay: {
							delay: 5000,
							disableOnInteraction: false,
					},
					navigation: {
							nextEl: '.swiper-button-next-care',
							prevEl: '.swiper-button-prev-care',
					},
			});
	}

});
