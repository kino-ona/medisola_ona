jQuery(document).ready(function() {
	/* morenvy.com 상세페이지 추가이미지 */
	var swiper_detail = new Swiper('.swiper_detail', {
		slidesPerView: 4,
		spaceBetween: 24,
		watchOverflow: 'true',
        navigation: {
			nextEl: '.swiper-button-next-detail',
			prevEl: '.swiper-button-prev-detail',
        },
	});

	/* morenvy.com 상세페이지 관련상품 */
	var swiper_rel = new Swiper('.detail_explain_box .swiper_widget', {
		slidesPerView: 4,
		spaceBetween: 24,
		watchOverflow: 'true',
        autoplay: {
			delay: 7000,
			disableOnInteraction: false,
		},
		scrollbar: {
			el: '.detail_explain_box .swiper-scrollbar-widget',
			draggable: true,
			dragSize: 300
		}
	});

	/* morenvy.com 상세페이지 상품정보 따라다니기 */
	var sc_start = jQuery('.item_info_box').outerHeight(true) + jQuery('.item_info_box').offset().top;
	jQuery(window).scroll(function() {
		var detailArea_height = jQuery('.item_photo_info_sec').outerHeight(true);
		if (jQuery(this).scrollTop() > sc_start) {
			jQuery('.item_photo_info_sec').css('height' , detailArea_height);
			jQuery(".tab_cate").addClass("onfixed");
			jQuery(".tab_cate .item_info_box").mCustomScrollbar({
				autoDraggerLength: false,
				utoDraggerLength: false,
				scrollButtons:{enable:true},
				theme:"rounded-dark",
				scrollbarPosition:"inside"
			});
		} else {
			jQuery('.item_photo_info_sec').css('height' , 'auto');
			jQuery(".tab_cate").removeClass("onfixed");
			jQuery(".tab_cate .item_info_box").mCustomScrollbar("stop");
		}


	});

	var displaystatus = "none"; // 바로선택 오픈상태
	jQuery("#tab_cate_title").click(function() {
		if (displaystatus == "none") {
			jQuery(".tab_cate").addClass("up");
			displaystatus = "display";
		} else {
			jQuery(".tab_cate").removeClass("up");
			displaystatus = "none";
		}
	});
})

//Element ID 불러쓰기
function dEI(elementID){
	return document.getElementById(elementID);
}

//레이어 팝업 열기
function openLayer(IdName){
	var pop = dEI(IdName);
	pop.style.display = "flex";
}

//레이어 팝엽 닫기
function closeLayer( IdName ){
	var pop = dEI(IdName);
	pop.style.display = "none";
}
