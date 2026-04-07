var wmLayer = {
   isAbsolute : false,
   wrapperClose : true, // 레이어 배경 클릭시 닫기 여부 
   popup : function(src, w, h, scrolling, isAbsolute, bgLayerHide, layerBorder) {
     $obj2 = $("#layer_popup_wrapper");
     $obj1 = $("#layer_popup");

     if (typeof src == "undefined" || src == "")
        return;

     if (typeof scrolling == "undefined")
         scrolling = false;

     var scroll = "no";
     if (scrolling)
         scroll = "yes";
	
	if (typeof bgLayerHide == "undefined" || bgLayerHide == "")
		bgLayerHide = false;
	
	if (typeof layerBorder == "undefined" || layerBorder == "")
		layerBorder = false;
	
     if ($obj1.length == 0) {
       var html = "<div id='layer_popup'>";
	    
		html += "<img src='/img/wm/icon/icon_close.gif' class='close_btn' style='position: absolute; z-index: 10; top: 20px; right: 30px; cursor: pointer;' onclick='wmLayer.close();'>";
	   
       html += "<iframe name='ifrmLayer' src='" + src + "' width='100%' height='100%' frameborder='0' scrolling='" + scroll + "'></iframe>";
       html += "</div>";
        $("body").prepend(html);
     }

     if ($obj2.length == 0)
        $("body").prepend("<div id='layer_popup_wrapper'></div>");


      $obj2 = $("#layer_popup_wrapper");
      $obj1 = $("#layer_popup");
	
	  if (bgLayerHide)
		  $obj2.hide();
	
	  if (typeof isAbsolute == "undefined" || isAbsolute == "")
		  isAbsolute = false; 
	  
	  this.isAbsolute = isAbsolute;
	  
	  if (isAbsolute) {
		var position = "absolute";  
		var st = $(window).scrollTop();
		var ypos = st + 100;
	  } else {
		var position = "fixed";
		var ypos = parseInt(($(window).height() - $obj1.height()) / 2);
	  }
	  
	  var xpos = parseInt(($(window).width() - $obj1.width()) / 2);
	
	  if (!bgLayerHide) {
		  $obj2.css({
			position : "fixed",
			width : "100%",
			height : "100%",
			background : "rgba(0,0,0,0.7)",
			top: 0,
			left: 0,
			zIndex : 1000,
			cursor: "pointer",
		  }).fadeIn();
	  }
	  
	  if (layerBorder)
		  $obj1.css("border", "1px solid #707070");
	  
      $obj1.css({
        position : position,
        backgroundColor : "#ffffff",
        width : w + "px",
        height : h + "px",
        left : xpos + "px",
        top : ypos + "px",
        zIndex : 1001,
      }).fadeIn();
	  
	/* 반응형 처리 */
	 if (wmLayer.responsive()) {
		 $obj1.css({
			position : "fixed",
			width : '100%',
			height : '100%',
			left : 0,
			top : 0
		});
	 }
	 /* 반응형 처리 END */
  },
  close : function() {
    $("#layer_popup_wrapper, #layer_popup").remove();
  },
  resize : function(w, h) {
     $obj = $("#layer_popup");
     if ($obj.length > 0) {
		 if (typeof w != "undefined" && w > 0) {
			 $obj.css("width", w + "px");
			 $obj.find("iframe").attr("width", w);
		 }
		 
		 if (typeof h != "undefined" && h > 0) {
			 $obj.css("height", h + "px");
			 $obj.find("iframe").attr("height", h);
		 }
		 
         var xpos = parseInt(($(window).width() - $obj.width()) / 2);
		 if (this.isAbsolute) {
			 $obj.css({left : xpos + "px"});
		 } else {
			 var ypos = parseInt(($(window).height() - $obj.height()) / 2);
			 $obj.css({
				left : xpos + "px",
				top : ypos + "px"
			 });
		 } // endif 
     }
  }, 
  /* 레이어 팝업 반응형 처리 */
  responsive : function() {
	  $obj = $("#layer_popup");
	  if ($obj.length > 0) {
		var layerWidth = $obj.width();
		var w = $(window).width();
		if (layerWidth >= w) {
			$obj.addClass("mobile");
			return true;
		} else {
			$obj.removeClass("mobile");
			return false;
		} // endif 
	 } // endif 
  }
};

$(function() {
    $(window).resize(function() {
        wmLayer.resize();
    });
	
    $("body").on("click", "#layer_popup_wrapper", function() {
		if (wmLayer.wrapperClose) 
			wmLayer.close();
    });
});