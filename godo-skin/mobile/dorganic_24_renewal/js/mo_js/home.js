/**
 * Medisola Home - Mobile
 *
 * 현재 레이아웃 순서:
 *   SEC 1: Hero (MP4 동영상 - 인라인 스크립트)
 *   SEC 2: 원플레이트밀 소개
 *   SEC 3: 슬라이더 배너 (위젯 자체 JS)
 *   SEC 4: 맞춤형 식단 선택형 메뉴 (위젯 자체 JS)
 *   SEC 5: 이 달의 집중 케어 (위젯 자체 JS)
 *   SEC 6: 푸드케어 레터 Magazine (위젯 자체 JS)
 *   SEC 7: 케어 스토리
 *   SEC 8: Instagram (위젯 자체 JS)
 *
 * 아래 Swiper 초기화는 이전 레이아웃에서 사용하던 코드입니다.
 * 현재 레이아웃에서는 각 위젯이 자체 JS를 포함하고 있어
 * 해당 DOM 요소가 없으면 자동으로 무시됩니다.
 */
jQuery(document).ready(function($) {

    /* ═══════════════════════════════════════
       Legacy: Hero Banner Swiper
       (현재 Hero는 MP4 video 사용)
       ═══════════════════════════════════════ */
    var heroOptions = {};
    if ($('.home-hero .swiper_sec01 .swiper-slide').length === 1) {
        heroOptions = {
            slidesPerView: 1,
            loop: true,
            autoplay: true,
            touchRatio: 0,
        };
        $('.home-hero .swiper_sec01').addClass('hide_nav');
    } else {
        heroOptions = {
            spaceBetween: 0,
            loop: true,
            slidesPerView: 1,
            autoplay: {
                delay: 4500,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination-sec01',
                clickable: true,
            },
        };
    }
    var heroSwiper = new Swiper('.home-hero .swiper_sec01', heroOptions);


    /* ═══════════════════════════════════════
       Legacy: 공지사항 Swiper
       (현재 레이아웃에서 미사용)
       ═══════════════════════════════════════ */
    var noticeSwiper = new Swiper('.home-hero__notice .swiper_board', {
        slidesPerView: 1,
        watchOverflow: true,
        loop: true,
        effect: 'fade',
        fadeEffect: { crossFade: true },
        speed: 1000,
        autoplay: {
            delay: 7000,
            disableOnInteraction: false,
        },
        navigation: {
            nextEl: '.swiper-button-next-board',
            prevEl: '.swiper-button-prev-board',
        },
        on: {
            init: function() {
                if ($('.home-hero__notice .swiper_board .swiper-slide').not('.swiper-slide-duplicate').length === 1) {
                    $('.home-hero__notice .swiper_board').addClass('disabled');
                }
            }
        }
    });


    /* ═══════════════════════════════════════
       Legacy: Products 탭 번호 매기기
       (현재 레이아웃에서 미사용)
       ═══════════════════════════════════════ */
    $('.home-products .tab_box').siblings('ul').each(function() {
        $(this).find('.num').each(function(index) {
            $(this).append(index + 1);
        });
    });


    /* ═══════════════════════════════════════
       Legacy: Review 캐러셀 Swiper
       (현재 레이아웃에서 미사용)
       ═══════════════════════════════════════ */
    var reviewSwiper = new Swiper('.home-review__carousel .swiper_sec08', {
        slidesPerView: 1.2,
        spaceBetween: 24,
        watchOverflow: true,
        speed: 1000,
        scrollbar: {
            el: '.swiper-scrollbar-sec08',
            dragSize: 120,
        },
        on: {
            init: function() {
                $('.home-review__carousel .swiper_sec08 .swiper-slide .top .t02').each(function() {
                    var content = $(this).text();
                    $(this).text(content);
                });
            },
        },
    });


    /* ═══════════════════════════════════════
       Legacy: News 동기화 캐러셀 Swiper
       (현재 레이아웃에서 미사용)
       ═══════════════════════════════════════ */
    var newsBanSwiper = new Swiper('.home-news__image .sec10_ban', {
        slidesPerView: 1,
        spaceBetween: 24,
        watchOverflow: true,
        loop: true,
        speed: 1000,
        autoplay: {
            delay: 7000,
            disableOnInteraction: false,
        },
        pagination: {
            el: '.swiper-pagination-sec10',
            type: 'custom',
            renderCustom: function(swiper, current, total) {
                var pad = function(n) { return n > 9 ? n : '0' + n; };
                return '<span class="current">' + pad(current) + '</span>' +
                       '<img src="/data/skin/mobile/medisola_dev/img/mimg/slash.svg" />' +
                       '<span class="total">' + pad(total) + '</span>';
            }
        },
    });

    var newsTextSwiper = new Swiper('.home-news__text .sec10_text', {
        slidesPerView: 1,
        watchOverflow: true,
        loop: true,
        speed: 1000,
    });

    newsBanSwiper.controller.control = newsTextSwiper;
    newsTextSwiper.controller.control = newsBanSwiper;

});
