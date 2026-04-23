jQuery(document).ready(function () {
    new WOW().init();

    /* 사이드바 */
    jQuery(".mh_sec01 .mh_all").click(function () {
        amplitude.logEvent('gnb_menu_drawer_click');
        jQuery(".m_side").show(500).addClass("open");
    });

    jQuery("#wrap .m_side .ms_top .btnClose").click(function () {
        jQuery(".m_side").removeClass("open");
    });
    
    /* 사이드바 - 1:1 영양 상담 배너 */
    jQuery("#wrap .m_side .ms_cate .health_care").addClass("selected");

    jQuery("#wrap .m_side .ms_banner a").click(function () {
        jQuery(".personalized").show(500).addClass("open");
    });

    jQuery("#wrap .personalized .personalized_footer .btn_close").click(function () {
        jQuery(".personalized").removeClass("open");
    });

    jQuery("#wrap .bg").click(function () {
        jQuery(".personalized").removeClass("open");
    })

    jQuery("#wrap .m_side .ms_cate ul a.cate").click(function () {
        jQuery(this).parent("li").addClass("selected");
        jQuery(this).next("ul").show();
        jQuery(this).parents("li").siblings().children("ul").hide();
        jQuery(this).parents("li").siblings().removeClass("selected");
    });

    /* 상단 고정 */
    var last_scrollTop = 0;
    var mh_height = jQuery("#mheader .mh_sec01").innerHeight();
    var mh_height02 = jQuery("#mheader .mh_sec02").innerHeight();

    jQuery(".mh_empty").css("height", mh_height + mh_height02);

    var sc = jQuery(window).scrollTop();
    if (sc > mh_height02) {
        // jQuery("#mheader").addClass("fixed");
        // jQuery(".mh_empty").css("height", mh_height + mh_height02);
        jQuery("#mheader.fixed .mh_sec02").slideUp("fast");
    }

    jQuery(window).scroll(function () {
        var sc = jQuery(window).scrollTop();
        if (sc > mh_height02) {
            // jQuery("#mheader").addClass("fixed");
            // jQuery(".mh_empty").css("height", mh_height + mh_height02);

            if (sc > last_scrollTop) {
                jQuery("#mheader.fixed .mh_sec02").slideUp("fast");
            } else {
                jQuery("#mheader.fixed .mh_sec02").slideDown("fast");
            }
        } else {
            // jQuery("#mheader").removeClass("fixed");
            jQuery(".mh_sec02").slideDown("fast");
        }

        last_scrollTop = sc;
    });

    /* morenvy.com 검색창 문구 */
    jQuery("#mheader .mh_sec03 #frmSearchTop legend").each(function () {
        var searchheader_text = jQuery(this).text();
        jQuery("#mheader .mh_sec03 #frmSearchTop input#sch").attr(
            "placeholder",
            searchheader_text
        );
    });

    /* 상단03 : 검색 */
    jQuery(".mh_sec01 .mh_search .open").click(function () {
        
        amplitude.logEvent('gnb_search_click');

        jQuery(".mh_sec03").slideDown("300", function () {
            jQuery(this).addClass("open");
        });
        jQuery("#mheader").addClass("search");
        jQuery("#mheader.search .mh_sec01 .mh_search .open").hide(300);
        jQuery("#mheader.search .mh_sec01 .mh_search .close").show(300);
        jQuery("#mheader.search #dimmedSlider").show(300);

        jQuery("#mheader.search #dimmedSlider").click(function () {
            jQuery(".mh_sec03").slideUp("300", function () {
                jQuery(this).removeClass("open");
            });
            jQuery("#mheader.search .mh_sec01 .mh_search .open").show(300);
            jQuery("#mheader.search .mh_sec01 .mh_search .close").hide(300);
            jQuery("#mheader.search #dimmedSlider").hide(300);
            setTimeout(function () {
                jQuery("#mheader").removeClass("search");
            }, 500);
        });
    });
    jQuery(".mh_sec01 .mh_search .close").click(function () {
        jQuery(".mh_sec03").slideUp("300", function () {
            jQuery(this).removeClass("open");
        });
        jQuery("#mheader.search .mh_sec01 .mh_search .open").show(300);
        jQuery("#mheader.search .mh_sec01 .mh_search .close").hide(300);
        jQuery("#mheader.search #dimmedSlider").hide(300);
        setTimeout(function () {
            jQuery("#mheader").removeClass("search");
        }, 500);
    });

    if (jQuery("#foooter").text() == "") {
        jQuery("#footer").hide();
    }

    /* 분류페이지 */
    var list_recmd = new Swiper(".recommend_prd_list .swiper_widget", {
        slidesPerView: 1.2,
        spaceBetween: 24,
        watchOverflow: "true",
        speed: 1000,
        scrollbar: {
            el: ".swiper-scrollbar-widget",
            dragSize: 120,
        },
    });

    var personalized_banner = new Swiper(".personalized_content .swiper-container", {
        slidesPerView: 1,
        spaceBetween: 8,
        watchOverflow: "true",
        speed: 1000,
        pagination: {
            el: '.personalized_content .swiper-pagination',
            clickable: true,
        },
    });

    jQuery(".recommend_prd_list .m_prd").each(function (index) {
        jQuery(this)
            .find(".num")
            .each(function () {
                jQuery(this).append(index + 1);
            });
    });

    /* Fixed SNS */
    jQuery(".mf_quick").click(function () {
        jQuery(this).toggleClass("open");
    });
});
