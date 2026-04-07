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
      $('.mh_sec01').addClass('bright_logo');
      $('.mh_sec03').addClass('bright_logo');
      $('.mh_sec02').addClass('bright_font_color');
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
