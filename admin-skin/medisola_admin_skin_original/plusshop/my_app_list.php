<div class="page-header js-affix">
    <h3><?=end($naviMenu->location); ?>
    </h3>
</div>

<style media="screen">
    #menu .plus {    background: url("/admin/gd_share/image/icon_menu_plusshop.png") center 0px no-repeat;}
    .plusshop-top{position: relative;width: 929px;padding: 5px 0 0 0;}
    .plusshop-top .nav.nav-tabs{overflow: inherit;}
    .plusshop-top .nav.nav-tabs > li > a {padding: 10px 35px 10px 35px}
    .plusshop-top .nav-tabs > li.active a{border-bottom:1px solid #ffffff;}
    .plusshop-top .sel-box{position: absolute;left: 341px;top: 9px;background: url("/admin/gd_share/image/ico_bar.png") no-repeat left 3px;}
    .plusshop-top .sel-box{padding: 0 0 0 20px;}


    .myapp-list{width: 929px;margin: 35px 0 0 0;}
    .myapp-list table td.width-mdd {width: 140px !important;}
    .myapp-list table td.cont{padding: 20px 30px 20px 20px; }
    .myapp-list table td.cont{}

    .myapp-list table td.cont .myapp-top{overflow: hidden;position: relative;}
    .myapp-list table td.cont .myapp-img{position: absolute;left: 0;top: 50%;margin: -35px 0 0 0;}
    .myapp-list table td.cont .myapp-cont{position: relative;overflow: hidden;padding: 0 0 0 104px;min-height: 70px;}
    .myapp-list table td.cont .myapp-cont h4{font-size: 14px; font-weight: bold; color: #222222;margin: 5px 0 0 0;text-align: left}

    .myapp-list table td.cont .grade {height: 15px;overflow:hidden;margin: 11px 0 3px 0;}
    .myapp-list table td.cont .grade .grade-bg {float: left;display:block;width: 85px;height:15px;	background: url('/admin/gd_share/image/star.png') repeat left bottom;}
    .myapp-list table td.cont .grade .grade-bg span {display: block;width:85px;height: 15px;background: url('/admin/gd_share/image/star.png') repeat left top;}
    .myapp-list table td.cont .grade .point {float: left;font-size: 11px;color: #999999;margin: 0 0 0 4px;}

    .myapp-list table td.cont .myapp-cont strong{position: absolute;right: 0;top: 50%;margin: -10px 0 0 0;font-size: 14px; color: #222222;}

    .myapp-list table td.cont ul{overflow: hidden;margin: 6px 0 0 0;}
    .myapp-list table td.cont ul li{float: left;width: 140px;}
    .myapp-list table td.cont .tit{float:left;font-size: 11px; color: #4a4a4a;padding: 0 0 0 5px;background: url("/admin/gd_share/image/ico_dot.png") no-repeat left 8px;font-weight: normal;}
    .myapp-list table td.cont .con{float:left;font-size: 11px; color: #6f6f6f;padding: 0 0 0 7px;background: url("/admin/gd_share/image/ico_bar02.png") no-repeat left 4px;margin: 0 0 0 5px}
    .myapp-list table td.cont dt:first-child{margin: 0;}

    .myapp-list table td a.btn-buy{display: inline-block;width: 80px; height: 28px;line-height: 26px;font-size: 11px;font-weight: bold;color: #ffffff;background: #fa2828 url("/admin/gd_share/image/ico_v.png") no-repeat 10px 9px;text-align: center;text-indent: 12px;text-decoration: none;}
    .myapp-list table td a.btn-buy:hover{background-color: #ca1717}
    .myapp-list table td a.btn-un{display: inline-block;width: 80px; height: 28px;line-height: 24px;font-size: 11px;color: #fa2828;background: url("/admin/gd_share/image/ico_caution.png") no-repeat 14px 7px;text-align: center;text-indent: 12px;border: 1px solid #fa2828;text-decoration: none;}
    .myapp-list table td a.btn-un:hover{border: 1px solid #ca1717;color: #ca1717;}
    .myapp-list table td a.btn-fun{display: inline-block;width: 80px; height: 28px;line-height: 24px;font-size: 11px;color: #444444;background: url("/admin/gd_share/image/ico_fun.png") no-repeat 10px 7px;text-align: center;text-indent: 16px;border: 1px solid #cccccc;text-decoration: none;}
    .myapp-list table td a.btn-fun:hover{border: 1px solid #666666;}
    .myapp-list table td a.btn-skin{display: inline-block;width: 80px; height: 28px;line-height: 24px;font-size: 11px;color: #444444;background:url("/admin/gd_share/image/ico_skin.png") no-repeat 10px 8px;text-align: center;text-indent: 16px;border: 1px solid #cccccc;margin: 5px 0 0 0;text-decoration: none;}
    .myapp-list table td a.btn-skin:hover{border: 1px solid #666666;}

    .myapp-list table td a.btn-al{display: inline-block;width: 80px; height: 28px;line-height: 24px;font-size: 11px;color: #ff6d0b;background:url("/admin/gd_share/image/icon_bell.png") no-repeat 10px 8px;text-align: center;text-indent: 16px;border: 1px solid #ff6d0b;margin: 5px 0 0 0;text-decoration: none;}
    .myapp-list table td a.btn-al:hover{border: 1px solid #c55308;color: #c55308}

    .myapp-list table td a:first-child{margin: 0;}

    .plusshop .text-center{width: 929px;margin: 0px 0 0 0;}

    .myapp-list.empty{border: 1px solid #efefef;text-align: center;margin: 10px 0 0 0;padding: 30px 0 40px 0}
    .myapp-list.empty strong{display: inline-block;font-size: 16px; color: #222222;margin: 10px 0 0 0;}
    .myapp-list.empty p{font-size: 12px; color: #333333;margin: 6px 0 0 0;}
</style>

<div class="col-xs-12 plusshop" style="border-top-width: 15px; border-top-style: solid; border-color:white;">
    <div class="plusshop-top">
        <ul class="nav nav-tabs mgb0" role="tablist">
            <li role="presentation" class="sort_by_all active">
                <a href="javascript:ajaxProcess(1, '', '', '');">홈</a>
            </li>
            <li role="presentation" class="sort_by_is_unlimited ">
                <a href="javascript:ajaxProcess(1, 'is_unlimited', '', $('#type').val());">무제한형</a>
            </li>
            <li role="presentation" class="sort_by_is_limited ">
                <a href="javascript:ajaxProcess(1, 'is_limited', '', $('#type').val());">기간제형</a>
            </li>
        </ul>
        <div class="sel-box">
            <select name="reGuidePeriodItem" id="reGuidePeriodItem" class="form-control" data-target-name="reGuidePeriod" data-target-value="3">
                <option data-target-number="2, 12, 1" value="" selected="selected">분류</option>
            </select>
        </div>
    </div>
    <div style="display: none;">
        <input type=text id="page" value="1"/>
        <input type=text id="sort_by" value="" />
        <input type=text id="is_free" value="" />
        <input type=text id="type"value="" />
        <input type=text id="total"value="{app_count}" />
    </div>
    <div class="myapp-list">
        <table class="table table-rows table-fixed" id="my_list">
            <tbody>
            </tbody>
        </table>
    </div>
    <div id="empty_list" class="myapp-list empty" style="display: none;" >
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
    $(function() {
        init();
        drawPager();
        activeEffect();
        changeCategory();
    });

    function init() {
        ajaxProcess(1, '', '', '');
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

    function activeEffect(page, sort_by, is_free, type) {
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
        settingSort(page, sort_by, is_free, type);
        $.ajax({
            type: 'GET',
            url: '/plusshop/app/list.php',
            data: {
                page: page,
                sort_by: sort_by,
                is_free: is_free,
                type: type,
                is_my_app: true
            },
            success: function(response) {
                if (typeof(response) !== 'object') {
                    response = JSON.parse(response);
                }

                if ($('#reGuidePeriodItem option').length < 2) {
                    $.each(response.categories, function(index, category) {
                        var option = $('<option></option>').val(category).text(category);
                        $('#reGuidePeriodItem').append(option);
                    });
                }

                $('#my_list').html('');
                var total = response.total;
                total > 0 ? $('#empty_list').hide() : $('#empty_list').show();

                $.each(response.list, function (index, value) {
                    drawList(value);
                });

                drawPager(page, sort_by, is_free, type, total);
                activeEffect(page, sort_by, is_free, type);
            },
            error: function(error) {
                console.log('ajax_error : ' + error);
            }
        });
    }

    function drawList(app){
        var skin_btn, setting_btn, installed_btn, not_installed_btn, paused_btn, expire_date = '';

        if (app.is_installed == true && app.type == 'i' && app.is_paused == false && app.skin_url != '') {
            skin_btn = $('<a/>')
                .addClass('btn-skin')
                .attr('onclick', 'show_popup("' + app.skin_url + '");')
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

            if(app.is_paused == true) {
                $(setting_btn).css({ 'pointer-events' : 'none' });
            }
        } else {
            if (app.is_installed == true && app.is_paused == true) {
                paused_btn = $('<a/>')
                    .addClass('btn-un')
                    .attr('disabled', 'disabled')
                    .append(
                        '미사용'
                    )
            } else {
                setting_btn = '-';
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



        if (app.is_purchased == true && app.is_installed == true) {
            installed_btn = '설치완료';
        }

        if (app.expire_date != '') {
            expire_date = '사용기간 무제한'; //app.expire_date;
        } else {
            expire_date = '사용기간 무제한';
        }

        $('#my_list').append(
            $('<tr/>')
                .addClass('center')
                .append(
                    $('<td/>')
                        .addClass('cont')
                        .append(
                            $('<div/>')
                                .addClass('myapp-top')
                                .append(
                                    $('<div/>')
                                        .addClass('myapp-img')
                                        .append(
                                            $('<img/>')
                                                .attr('src', app.images[0])
                                                .height('70px')
                                                .width('84px')
                                        )
                                )
                                .append(
                                    $('<div/>')
                                        .addClass('myapp-cont')
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
                                        .append(
                                            $('<ul/>')
                                                .append(
                                                    $('<li/>')
                                                        .append(
                                                            $('<span/>')
                                                                .addClass('tit')
                                                                .append(
                                                                    '앱분류'
                                                                )
                                                        )
                                                        .append(
                                                            $('<span/>')
                                                                .addClass('con')
                                                                .append(
                                                                    app.category
                                                                )
                                                        )
                                                )
                                                .append(
                                                    $('<li/>')
                                                        .append(
                                                            $('<span/>')
                                                                .addClass('tit')
                                                                .append(
                                                                    '버전'
                                                                )
                                                        )
                                                        .append(
                                                            $('<span/>')
                                                                .addClass('con')
                                                                .append(
                                                                    app.version
                                                                )
                                                        )
                                                )
                                        )
                                )
                        )

                )
                .append(
                    $('<td/>')
                        .addClass('width-mdd')
                        .append(not_installed_btn)
                        .append(installed_btn)
                )
                .append(
                    $('<td/>')
                        .addClass('width-mdd')
                        .append(setting_btn)
                        .append(skin_btn)
                        .append(paused_btn)
                )
                .append(
                    $('<td/>')
                        .addClass('width-mdd')
                        .append(expire_date)
                )
        );
    }

    function openWindow(url) {
        window.open(url);
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
