<style media="screen">
    #menu .plus {    background: url("/admin/gd_share/image/icon_menu_plusshop.png") center 0px no-repeat;}
    #menu .ban{margin: 100px 0 0 0;text-align: center;}
    .plusshop-top{position: relative;width: 929px;padding: 5px 0 0 0;}
    .plusshop-top .nav.nav-tabs{overflow: inherit;}
    .plusshop-top .nav.nav-tabs > li > a {padding: 10px 35px 10px 35px}
    .plusshop-top .nav-tabs > li.active a{border-bottom:1px solid #ffffff;}
    .plusshop-top .sel-box{position: absolute;left: 341px;top: 9px;background: url("/admin/gd_share/image/ico_bar.png") no-repeat left 3px;}
    .plusshop-top .sel-box{padding: 0 0 0 20px;}

    .sort-type{width: 929px;margin: 25px 0 0 0;}
    .sort-type ul{overflow: hidden;}
    .sort-type ul li{float: left;}
    .sort-type ul li.ico-bar{padding: 0; margin: 0 10px 0 10px;}
    .sort-type ul li:first-child{margin: 0;}
    .sort-type ul li.on{background: url("/admin/gd_share/image/ico_v02.png") no-repeat left 5px;padding: 0 0 0 12px;}
    .sort-type ul li a{display: inline-block; font-size: 12px;color: #656565;text-decoration: none;}
    .sort-type ul li.on a{border-bottom: 1px solid #444444;font-weight: bold;}
    .sort-type ul li a:hover{border-bottom: 1px solid #444444;}

    .plus-list{overflow: hidden;width: 929px;}
    .plus-list ul{width: 949px;overflow: hidden;margin: -10px 0 0 -20px;}
    .plus-list ul li{float: left;width: 454px;height: 222px;padding: 20px 20px 20px 20px;overflow: hidden;border: 1px solid #cccccc;margin: 20px 0 0 20px;}
    .plus-list ul li .plus-top{overflow: hidden;position: relative;}
    .plus-list ul li .plus-img{position: absolute;left: 0; top: 0;}
    .plus-list ul li .plus-cont{padding: 0 0 0 104px;min-height: 70px;overflow: hidden;}
    .plus-list ul li .plus-cont h4{font-size: 16px; font-weight: bold; color: #222222;margin: 3px 0 0 0;}

    .plus-list .grade {position: relative;height: 27px;overflow:hidden;padding: 9px 0 3px 0;}
    .plus-list .grade .grade-bg {float: left;display:block;width: 85px;height:15px;	background: url('/admin/gd_share/image/star.png') repeat left bottom;}
    .plus-list .grade .grade-bg span {display: block;width:85px;height: 15px;background: url('/admin/gd_share/image/star.png') repeat left top;}
    .plus-list .grade .point {float: left;font-size: 11px;color: #999999;margin: 0 0 0 4px;}

    .plus-list ul li .plus-cont strong{position: absolute;right: 0;top: 7px;font-size: 16px; color: #222222;}
    .plus-list ul li .plus-text{clear: both;height: 57px;font-size: 12px; color: #333333;margin: 15px 0 0 0;line-height: 1.6em;}
    .plus-list ul li .plus-bottom{overflow: hidden;margin: 8px 0 0 0;}
    .plus-list ul li .plus-bottom span{float:left;display: inline-block;width: 180px; height: 28px;line-height: 26px;font-size: 12px; color: #919191;background: url("/admin/gd_share/image/ico_com.png") no-repeat left 7px;padding: 0 0 0 20px;}

    .plus-list ul li .plus-bottom a.btn-buy{float: right;display: inline-block;width: 80px; height: 28px;line-height: 26px;font-size: 11px;font-weight: bold;color: #ffffff;background: #fa2828 url("/admin/gd_share/image/ico_v.png") no-repeat 10px 9px;text-align: center;text-indent: 12px;text-decoration: none;}
    .plus-list ul li .plus-bottom a.btn-buy:hover{background-color: #ca1717}
    .plus-list ul li .plus-bottom a.btn-un{float: right;display: inline-block;width: 80px; height: 28px;line-height: 24px;font-size: 11px;color: #fa2828;background: url("/admin/gd_share/image/ico_caution.png") no-repeat 14px 7px;text-align: center;text-indent: 12px;border: 1px solid #fa2828;text-decoration: none;}
    .plus-list ul li .plus-bottom a.btn-un:hover{border: 1px solid #ca1717;color: #ca1717;}
    .plus-list ul li .plus-bottom a.btn-fun{float: right;display: inline-block;width: 80px; height: 28px;line-height: 24px;font-size: 11px;color: #444444;background: url("/admin/gd_share/image/ico_fun.png") no-repeat 10px 7px;text-align: center;text-indent: 16px;border: 1px solid #cccccc;margin: 0 0 0 5px;text-decoration: none;}
    .plus-list ul li .plus-bottom a.btn-fun:hover{border: 1px solid #666666;}
    .plus-list ul li .plus-bottom a.btn-skin{float: right;display: inline-block;width: 80px; height: 28px;line-height: 24px;font-size: 11px;color: #444444;background:url("/admin/gd_share/image/ico_skin.png") no-repeat 10px 8px;text-align: center;text-indent: 16px;border: 1px solid #cccccc;margin: 0 0 0 5px;text-decoration: none;}
    .plus-list ul li .plus-bottom a.btn-skin:hover{border: 1px solid #666666;}
    .text-center{width: 929px;margin: 20px 0 0 0;}

    .plus-list.empty{border: 1px solid #efefef;text-align: center;margin: 10px 0 0 0;padding: 30px 0 40px 0}
    .plus-list.empty strong{display: inline-block;font-size: 16px; color: #222222;margin: 10px 0 0 0;}
    .plus-list.empty p{font-size: 12px; color: #333333;margin: 6px 0 0 0;}
</style>

<div class="page-header js-affix">
    <h3><?=end($naviMenu->location); ?>
    </h3>
</div>

<div class="col-xs-12 plusshop">
    <div class="plusshop-top" style="border-top-width: 15px; border-top-style: solid; border-color:white;">
        <ul class="nav nav-tabs mgb0" role="tablist">
            <li role="presentation" class="sort_by_all ">
                <a href="javascript:ajaxProcess(1, '', '', '');" >홈</a>
            </li>
            <li role="presentation" class="sort_by_purchase_count ">
                <a href="javascript:ajaxProcess($('#page').val(), 'purchase_count', $('#is_free').val(), $('#type').val());" >인기순위</a>


            </li>
            <li role="presentation" class="sort_by_release_date ">
                <a href="javascript:ajaxProcess($('#page').val(), 'release_date', $('#is_free').val(), $('#type').val());">신규출시</a>
            </li>
        </ul>
        <div class="sel-box">
            <select name="reGuidePeriodItem" id="reGuidePeriodItem" class="form-control" data-target-name="reGuidePeriod" data-target-value="3">
                <option data-target-number="2, 12, 1" value="" selected="selected">분류</option>
            </select>
        </div>
        <div style="display: none;">
            <input type=text id="page" value="1"/>
            <input type=text id="sort_by" value="" />
            <input type=text id="is_free" value="" />
            <input type=text id="type"value="" />
            <input type=text id="total"value="{app_count}" />
        </div>
    </div>
    <div class="sort-type">
        <ul>
            <li class="is_free_all "><a href="javascript:ajaxProcess(1, $('#sort_by').val(), '', $('#type').val());">전체</a></li>
            <li class="ico-bar"><img src="/admin/gd_share/image/ico_bar02.png"></li>
            <li class="is_free_n "><a href="javascript:ajaxProcess(1, $('#sort_by').val(), 'n', $('#type').val());">유료 앱</a></li>
            <li class="ico-bar"><img src="/admin/gd_share/image/ico_bar02.png"></li>
            <li class="is_free_y"><a href="javascript:ajaxProcess(1, $('#sort_by').val(), 'y', $('#type').val());">무료 앱</a></li>
        </ul>
    </div>
    <div class="plus-list">
        <ul>
        </ul>
    </div>
    <div id="empty_list" class="plus-list empty" style="display: none;">
        <div class="img"><img src="/admin/gd_share/image/img_caution.png"></div>
        <strong>검색된 결과가 없습니다.</strong>
        <p>쉽고 편리한 쇼핑몰 운영을 위한 앱을 제공할 수 있도록 준비중 입니다.</p>
    </div>
</div>
<div class="text-center">
    <ul class="pagination pagination-sm ps_plusshop_list"></ul>
</div>

<script src="<?=PATH_ADMIN_GD_SHARE?>/script/jquery/validation/require.js"></script>
<script src="<?=PATH_ADMIN_GD_SHARE?>/script/pager.js"></script>
<script type="text/javascript">

    $(function(){
        init();
        drawPager();
        activeEffect();
        changeCategory();
    });

    function init() {
        ajaxProcess(1, '', '', '');
    }

    function openWindow(url) {
        window.open(url);
    }

    function activeEffect(page, sort_by, is_free, type) {

        if(is_free=='') {
            $('.is_free_all').addClass('on');
            $('li').not('.is_free_all').removeClass('on');
        } else {
            $('.is_free_' + is_free).addClass('on');
            $('li').not('.is_free_' + is_free).removeClass('on');
        }

        if(sort_by=='') {
            $('.sort_by_all').addClass('active');
            $('li').not('.sort_by_all').removeClass('active');
        } else {
            $('.sort_by_' + sort_by).addClass('active');
            $('li').not('.sort_by_' + sort_by).removeClass('active');
        }

        if (type != '') {
            $("#reGuidePeriodItem").val(type);
        }
    }

    function ajaxProcess(page, sort_by, is_free, type) {
        var url = '/plusshop/app/list.php';
        var method = 'GET';

        settingSort(page, sort_by, is_free, type);
        $.ajax({
            type: method,
            url: url,
            data: {
                page: page,
                sort_by: sort_by,
                is_free: is_free,
                type: type
            },
            success : function(response) {
                if (typeof(response) !== 'object') {
                    response = JSON.parse(response);
                }

                if ($('#reGuidePeriodItem option').length < 2) {
                    $.each(response.categories, function(index, category) {
                        var option = $('<option></option>').val(category).text(category);
                        $('#reGuidePeriodItem').append(option);
                    });
                }

                $('.plus-list ul').html('');
                var total = response.total;
                total > 0 ? $('#empty_list').hide() : $('#empty_list').show();

                $.each(response.list, function (index, value) {
                    drawList(value);
                });

                drawPager(page, sort_by, is_free, type, total);
                activeEffect(page, sort_by, is_free, type);
            },
            error : function(error) {
                console.log('ajax_error : ' + error);
            }
        });
    }

    function settingSort(page, sort_by, is_free, type) {
        $('#page').val(page);
        $('#sort_by').val(sort_by);
        $('#is_free').val(is_free);
        $('#type').val(type);
    }

    function changeCategory() {
        $('#reGuidePeriodItem').change(function() {
            ajaxProcess(1, $('#sort_by').val(), $('#is_free').val(), $(this).val());
        });
    }

    function drawList(app){
        var skin_btn, setting_btn, not_installed_btn, not_purchase_btn, paused_btn= '';

        if (app.type == 'i' && app.is_installed == true && app.is_paused == false && app.skin_url != '') {
            skin_btn = $('<a/>')
                .addClass('btn-skin')
                .attr('onclick', 'show_popup("' + app.skin_url + '?popupMode=yes");')
                .append(
                    '스킨설정'
                )
        }

        if (app.is_installed == true && app.setting_url != '' && app.is_paused == false) {
            setting_btn = $('<a/>')
                .addClass('btn-fun')
                .attr('href', 'javascript:openWindow("' + app.setting_url + '");')
                .append(
                    '설정관리'
                )
        } else {
            if (app.is_installed == true && app.is_paused == true) {
                paused_btn = $('<a/>')
                    .addClass('btn-un')
                    .attr('disabled', 'disabled')
                    .append(
                        '미사용'
                    )
            }
        }

        if (app.is_purchased == true && app.is_installed == false) {
            not_installed_btn = $('<a/>')
                .addClass('btn-un')
                .attr('disabled', 'disabled')
                .append(
                    '미설치'
                )
        }

        if (app.is_purchased == false) {
            not_purchase_btn = $('<a/>')
                .addClass('btn-buy')
                .attr('href', 'javascript:openWindow("' + app.purchase_url + '");')
                .append(
                    '구매하기'
                )
        }


        $('.plus-list ul').append(
            $('<li/>')
                .append(
                    $('<div/>')
                        .addClass('plus-top')
                        .append(
                            $('<div/>')
                                .addClass('plus-img')
                                .append(
                                    $('<img/>')
                                        .attr('src', app.images[0])
                                        .height('70px')
                                        .width('84px')
                                )
                        )
                        .append(
                            $('<div/>')
                                .addClass('plus-cont')
                                .append(
                                    $('<h4/>')
                                        .append(
                                            $('<a/>')
                                                .attr('href', 'javascript:openWindow("' + app.purchase_url + '");')
                                                .append(app.name)
                                        )
                                )
                                .append(
                                    $('<div/>')
                                        .addClass('grade')
                                        .append(
                                            $('<span/>')
                                                .addClass('grade-bg')
                                                .append(
                                                    $('<span/>')
                                                        .width(app.grade+'%')
                                                )
                                        )
                                        .append(
                                            $('<span/>')
                                                .addClass('point')
                                                .append(
                                                    '('+app.review_count+')'
                                                )
                                        )
                                        .append(
                                            $('<strong/>')
                                                .append(
                                                    app.sale_price + '원'
                                                )
                                        )
                                )
                        )
                )
                .append(
                    $('<div/>')
                        .addClass('plus-text')
                        .append(
                            app.detail
                        )
                )
                .append(
                    $('<div/>')
                        .addClass('plus-bottom')
                        .append(
                            $('<span/>')
                                .append(
                                    app.developer_name
                                )
                        )
                        .append(skin_btn)
                        .append(setting_btn)
                        .append(not_installed_btn)
                        .append(not_purchase_btn)
                        .append(paused_btn)
                )
        );
    }

    function drawPager(page, sort_by, is_free, type, total) {
        (function($) {
            require(['PSModule.pager'], function (pager) {
                pager.load("pager", total , 10, 10, page, function goToPage(no) {
                    ajaxProcess(no, sort_by, is_free, type);
                });
            });
        })(jQuery);
    }

    function show_popup(url) {
        win = popup({
            url: url,
            target: '',
            width: 1500,
            height: 680,
            scrollbars: 'yes',
            resizable: 'yes',
            left: 80,
            top: 80
        });
        win.focus();
        return win;
    };
</script>
