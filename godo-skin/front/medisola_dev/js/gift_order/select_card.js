/**
* 선물하기 주문서 관련 
*
* @author webnmobile
*/
$(function() {
	/* 카드 유형 선택 */
	$("body").on("click", ".cardType", function() {
		var cardType = $(this).val();
		$.ajax({
			url : "../gift_order/ajax_select_card.php",
			type : "post",
			dataType : "json",
			data : { cardType : cardType },
			success : function (data) {
				if (data) {
					if (data.error == '1') {
						alert(data.message);
					} else {
						if (data.cards.length > 0) {
							var complied = _.template($("#cardTypeTemplate").html());
							var addHtml = "";
							var width = (100 / data.cards.length) + "%";
							data.cards.forEach(function(el) {
								if(cardType == "선택 안함"){
									addHtml += complied({
										imageUrl : "",
										height: '0px',
									});
								}else{
									addHtml += complied({
										//uid : el.uid,
										imageUrl : el.imageUrl,
										height: '350px;'
										//width : width,
									});
								}
							});
						} // endif 

						//$(".card_tabs").html(addHtml);
						$(".card_contents").html(addHtml);
						$(".cardImage").eq(0).click();
					} // endif 
				} // endif 
					
			},
			error : function (res) {
				console.log(res);
			}
		});
	});
	
	/* 카드이미지 선택 */
	$("body").on("click", ".cardImage", function() {
		var imageUrl = $(this).val();
		$(".card_contents .images").css({
			backgroundImage : 'url(' + imageUrl + ')',
		});
	});
});