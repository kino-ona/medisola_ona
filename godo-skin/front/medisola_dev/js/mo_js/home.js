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
			SEC 3: 추천 메뉴 가로 스크롤 + 버튼 네비게이션
			═══════════════════════════════════════ */
	/*
	// ── Swiper 방식 (보류) ──────────────────────────────────────
	function setupRecommendSwiperMarkup($box) { ... }
	function initRecommendSwiperForBox($box) { ... }
	function initRecommendSwiper() { ... }
	// ────────────────────────────────────────────────────────────
	*/

	// function initRecommendScrollForBox($box) {
	// 		if ($box.data('recScrollReady')) return;

	// 		// ul을 스크롤 뷰포트 div로 감싸기
	// 		var $list = $box.children('ul').first();
	// 		if (!$list.length) return;
	// 		$list.wrap('<div class="rec-scroll-viewport"></div>');
	// 		var $viewport = $box.find('.rec-scroll-viewport');

	// 		// 컨트롤을 goods_tab_box 안에 추가
	// 		var $controls = $(
	// 				'<div class="rec-scroll-controls">' +
	// 						'<button class="rec-scroll-prev" aria-label="이전"></button>' +
	// 						'<div class="rec-scroll-track"><div class="rec-scroll-thumb"></div></div>' +
	// 						'<button class="rec-scroll-next" aria-label="다음"></button>' +
	// 				'</div>'
	// 		);
	// 		$box.append($controls);
	// 		$box.data('recScrollReady', true);
	// 		$box.data('recScrollControls', $controls);
	// 		$box.data('recScrollViewport', $viewport);
	// 		updateRecommendScrollUI($box);
	// }

	// function updateRecommendScrollUI($box) {
	// 		var $viewport = $box.data('recScrollViewport');
	// 		var $controls = $box.data('recScrollControls');
	// 		if (!$viewport || !$controls) return;

	// 		var scrollEl   = $viewport[0];
	// 		var scrollLeft = scrollEl.scrollLeft;
	// 		var maxScroll  = scrollEl.scrollWidth - scrollEl.clientWidth;
	// 		var trackWidth = $controls.find('.rec-scroll-track').width();

	// 		if (maxScroll > 0) {
	// 				var ratio     = scrollEl.clientWidth / scrollEl.scrollWidth;
	// 				var thumbW    = Math.max(trackWidth * ratio, 30);
	// 				var thumbLeft = (scrollLeft / maxScroll) * (trackWidth - thumbW);
	// 				$controls.find('.rec-scroll-thumb').css({ width: thumbW + 'px', left: thumbLeft + 'px' });
	// 		} else {
	// 				$controls.find('.rec-scroll-thumb').css({ width: '100%', left: 0 });
	// 		}

	// 		$controls.find('.rec-scroll-prev').toggleClass('is-disabled', scrollLeft <= 0);
	// 		$controls.find('.rec-scroll-next').toggleClass('is-disabled', scrollLeft >= maxScroll - 1);
	// }

	// // 버튼 클릭: rec-scroll-viewport를 스크롤
	// $(document).on('click', '.rec-scroll-prev, .rec-scroll-next', function() {
	// 		var $box      = $(this).closest('.goods_tab_box');
	// 		var $viewport = $box.data('recScrollViewport');
	// 		if (!$viewport) return;
	// 		var step = 307 + 16;
	// 		var dir  = $(this).hasClass('rec-scroll-prev') ? -1 : 1;
	// 		$viewport[0].scrollBy({ left: dir * step, behavior: 'smooth' });
	// });

	// // 스크롤 시 UI 갱신
	// $(document).on('scroll', '.rec-scroll-viewport', function() {
	// 		updateRecommendScrollUI($(this).closest('.goods_tab_box'));
	// });

	// // 탭 클릭: 활성 탭만 컨트롤 표시
	// $(document).on('click', '.home-recommend .item_hl_tab_type .goods_tab_tit a', function() {
	// 		var tabIdx = $(this).closest('li').index();
	// 		setTimeout(function() {
	// 				var $allBoxes = $('.home-recommend .item_hl_tab_type .goods_tab_cont .goods_tab_box');

	// 				// 비활성 탭 컨트롤 숨김 (controls가 goods_tab_box 안에 있어 자동 처리되지만 명시적으로 동기화)
	// 				$allBoxes.each(function() {
	// 						var $c = $(this).data('recScrollControls');
	// 						if ($c) $c.toggle($(this).is(':visible'));
	// 				});

	// 				// 활성 탭 초기화 및 UI 갱신
	// 				var $box = $allBoxes.eq(tabIdx);
	// 				initRecommendScrollForBox($box);
	// 				updateRecommendScrollUI($box);
	// 		}, 100);
	// });

	// // 페이지 로드 시 활성 탭(.on)만 초기화
	// $('.home-recommend .item_hl_tab_type .goods_tab_cont .goods_tab_box.on').each(function() {
	// 		initRecommendScrollForBox($(this));
	// });


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
