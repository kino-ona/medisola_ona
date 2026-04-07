jQuery(document).ready(function() {
console.log(jQuery('.price_box').text().trim())
	if( jQuery('.price_box').text().trim() == '' ){
		jQuery('.price_box').hide();
	}

	/* morenvy.com 상세페이지 관련상품 */
	var swiper_rel = new Swiper('.related_goods .swiper_widget', {
		slidesPerView: '2',
		watchOverflow: 'true',
		spaceBetween: 16,
        //autoplay: {
		//	delay: 7000,
		//	disableOnInteraction: false,
		//},
        scrollbar: {
          el: ".swiper-scrollbar-widget",
			dragSize : 120,
        },
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
	document.querySelector('body').classList.add('scroll');
}

//레이어 팝엽 닫기
function closeLayer( IdName ){
	var pop = dEI(IdName);
	pop.style.display = "none";
	document.querySelector('body').classList.remove('scroll');
}
