jQuery(document).ready(function() {
	/* 연구활동 페이지 */	
	var swiper_history = new Swiper('.swiper_history', {
		slidesPerView: 'auto',
		speed:1000,
        navigation: {
			nextEl: '.swiper-button-next-history',
			prevEl: '.swiper-button-prev-history',
        },
	});
});
