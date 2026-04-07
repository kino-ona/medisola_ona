jQuery(document).ready(function() {

	/* morenvy.com 타임세일. 정환 */
	//var today = new GetServerTime(); // 현재서버시간 each 밖으로 빼서 한번만 실행.
	var today = new Date(); // 컴퓨터시간

	jQuery( ".ec-base-product .layerDiscountPeriod .content p + p" ).each(function( index ) {
		var date_data = jQuery(this).text(); 
		var date_start_end = date_data.split(" ~ "); // 시작시간 끝시간으로 자름
		var start_time = new Date(date_start_end[0].replace(/-/g, "/")); 
		var end_time = new Date(date_start_end[1].replace(/-/g, "/")); 

		if (today < start_time)	{
			// 세일 시작 전
			TimerStart(jQuery(this).parents('.box').find( ".timesale_box" ),0,start_time, end_time);
			jQuery(this).parents('.box').find( ".timesale_box" ).css("display", "block");
		} else if ((start_time <= today ) && (today < end_time)) {
			// 타임세일 중
			TimerStart(jQuery(this).parents('.box').find( ".timesale_box" ),1,end_time);
			jQuery(this).parents('.box').find( ".timesale_box" ).css("display", "block");
		} else {
			jQuery(this).parents('.box').find( ".timesale_box" ).append(" 마감되었습니다.");
			jQuery(this).parents('.box').find( ".timesale_box" ).css("display", "block");
		}
	});

	//flag = 0 시작전,  flag = 1 마감전
	function TimerStart (obj, flag, end_time, end_time2) {
		if (flag == 0)
		{
			obj.countdown({until: end_time, 
				serverSync: today, compact:true,  padZeroes: true, 
				onExpiry: function() { window.location.reload();} , 
				layout: "<div class='untilEnd'>할인 시작까지<span>{dn}</span>일<span class='hour'>{hnn}</span> : <span>{mnn}</span> : <span>{snn}</span></div>"
			});
		} else {
			obj.countdown({until: end_time, 
				serverSync: today, compact:true,  padZeroes: true, 
				onExpiry: function() { obj.html(" 마감되었습니다."); }, 
				layout: "<div class='untilStart'>할인 종료까지<span>{dn}</span>일<span class='hour'>{hnn}</span> : <span>{mnn}</span> : <span>{snn}</span></div>"
			});
			
			/* 일수를 시간으로 변환 */
			/*
			setInterval(function(){
				jQuery('div.untilStart').each(function(){
					var day = jQuery(this).children('span').eq(0).text();
					var hour = jQuery(this).children('span').eq(1).text();
					console.log(day,hour)
					jQuery(this).children('span').eq(1).text(day*60 + hour);
				})
			},1000)
			*/
		}

	}


	/* 서버시간 가져오는 스크립트 
	function GetServerTime (param, formula) {
		var xmlHttp;
		if (window.XMLHttpRequest) { 
			xmlHttp = new XMLHttpRequest(); // upper IE7, Chrome, Firefox
			xmlHttp.open('HEAD',window.location.href.toString(),false);
			xmlHttp.setRequestHeader("Content-Type", "text/html");
			xmlHttp.send('');

			//return xmlHttp.getResponseHeader("Date");

		} else if (window.ActiveXObject) { 
			//Old versions of IE supported the ability to use ActiveX controls inside the browser. 

			xmlHttp = new ActiveXObject('Msxml2.XMLHTTP');
			xmlHttp.open('HEAD',window.location.href.toString(),false);
			xmlHttp.setRequestHeader("Content-Type", "text/html");
			xmlHttp.send('');
			//return xmlHttp.getResponseHeader("Date");
		}
		//return xmlHttp.getResponseHeader("Date");
		var serverTime = xmlHttp.getResponseHeader("Date");

		if(param!=null) {
			var ymd = new Date(serverTime); 
			param = param.toLowerCase().trim();

			if(formula !=null){

				if(!typeof formula=="number") {
					console.log("변수 formula는 number타입이어야 합니다.");
				} else if(param=="day"){
					ymd.setDate(ymd.getDate()+formula);
				} else if(param=="week"){
					ymd.setDate(ymd.getDate()+(7*formula));
				} else if(param=="month"){
					ymd.setMonth(ymd.getMonth()+formula);
				} else if(param=="year"){
					ymd.setFullYear(ymd.getFullYear()+formula);
				}
			}
			var month = '' + (ymd.getMonth() + 1),
			day = '' + ymd.getDate(), 
			year = ymd.getFullYear(); 

			if (month.length < 2) month = '0' + month; 
			if (day.length < 2) day = '0' + day;

			return [year, month, day].join('-');
		} else {
			return new Date(serverTime);
		}
	}
	*/
 });
