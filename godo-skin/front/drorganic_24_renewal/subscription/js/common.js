var wm = {};

/* 레이어 팝업 관련 */
wm.layer = {
    popup: function (src, w, h, scrolling) {
        $obj2 = $("#layer_popup_wrapper");
        $obj1 = $("#layer_popup");

        if (typeof src == "undefined" || src == "")
            return;

        if (typeof scrolling == "undefined")
            scrolling = false;

        var scroll = "no";
        if (scrolling)
            scroll = "yes";

        if ($obj1.length == 0) {
            var html = "<div id='layer_popup' style='border-radius: 20px;'>"; // 디자인 추가
            html += "<iframe name='ifrmLayer' src='" + src + "' width='100%' height='100%' frameborder='0' scrolling='" + scroll + "'></iframe>";
            html += "</div>";
            $("body").prepend(html);
        }

        if ($obj2.length == 0)
            $("body").prepend("<div id='layer_popup_wrapper'></div>");


        $obj2 = $("#layer_popup_wrapper");
        $obj1 = $("#layer_popup");

        var xpos = parseInt(($(window).width() - w) / 2);
        var ypos = parseInt(($(window).height() - h) / 2);

        $obj2.css({
            position: "fixed",
            width: "100%",
            height: "100%",
            background: "rgba(0,0,0,0.7)",
            top: 0,
            left: 0,
            zIndex: 1000,
            cursor: "pointer"
        }).fadeIn();

        $obj1.css({
            position: "fixed",
            backgroundColor: "#ffffff",
            width: w + "px",
            height: h + "px",
            left: xpos + "px",
            top: ypos + "px",
            zIndex: 1001
        }).fadeIn();
    },
    close: function () {
        $("#layer_popup_wrapper, #layer_popup").remove();
    }

};

/* 결제 카드 관련 */
wm.card = {
    register: function (w, h) {
        if (typeof w == "undefined" || w == "")
            w = 400;

        if (typeof h == "undefined" || h == "")
            h = 500;

        wm.layer.popup("/subscription/card_register.php", w, h);
    },
    callback: function (pass) {
        if (typeof pass == "undefined" || pass == "") {
            alert("비밀번호를 등록해 주세요.");
            return;
        }

        if (pass.length < 6) {
            alert("비밀번호를 모두 선택하세요.");
            return;
        }

        if (typeof pgRegisterCallback == 'function')
            pgRegisterCallback(pass);
    },
    callback_order : function (pass) {
        if (typeof pass == "undefined" || pass == "") {
            alert("비밀번호를 등록해 주세요.");
            return;
        }

        if (pass.length < 6) {
            alert("비밀번호를 모두 선택하세요.");
            return;
        }

        if (typeof pgRegisterCallbackOrder == 'function')
            pgRegisterCallbackOrder(pass);
    },
    register_order : function(w, h) {
        if (typeof w == "undefined" || w == "")
            w = 400;

        if (typeof h == "undefined" || h == "")
            h = 500;

        wm.layer.popup("/subscription/card_register_order.php", w, h);
    },
    callback_register : function (pass) {
        if (typeof pass == "undefined" || pass == "") {
            alert("비밀번호를 등록해 주세요.");
            return;
        }

        if (pass.length < 6) {
            alert("비밀번호를 모두 선택하세요.");
            return;
        }

        if (typeof pgRegisterCallback == 'function') {
            pgRegisterCallback(pass);
        }
    }
};

/* 이벤트 처리 */
$(function () {
    $("body").on("click", "#layer_popup_wrapper", function () {
        wm.layer.close();
    });

    $(".js_add_card_btn").click(function () {
        var w = $(this).data("width");
        var h = $(this).data("height");
        wm.card.register(w, h);
    });
});