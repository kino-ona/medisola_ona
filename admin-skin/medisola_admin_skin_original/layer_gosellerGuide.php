<div class="ly_setting ly_goseller <?=($indexFl == 'y') ? '':'sub_type'?>" <?=($gosellerData['displayFl'] == 'true') ? '' : 'style="display:none;"'?>>
    <div class="setting_header">
        <h4>자주 묻는 질문 동영상 가이드</h4>
        <p>자주 묻는 질문들은 유튜브채널 고셀러TV에서<br />동영상으로 쉽고 빠르게 확인해보세요.</p>
        <a class="btn_line_round" href="https://www.youtube.com/channel/UCt6kS7eTgvpp3bg8O88rSNg" target="_blank">고셀러TV 바로가기</a>
        <span class="btn_close"><img src="../admin/gd_share/img/icon_ly_close.png" alt="닫기 버튼"/></span>
    </div>
    <!-- //setting_header -->
    <div class="inner">
        <div class="cont_wrap">
            <div class="setting_cont">
                <div class="search_goseller">
                    <div>
                        <input type="text" placeholder="검색하기" id="goseller_search_box" />
                        <a class="btn_goseller_erase" href="#"><img src="../admin/gd_share/img/icon_goseller_erase.png" /></a>
                    </div>
                    <a class="btn_goseller_search" href="#"><img src="../admin/gd_share/img/icon_goseller_search.png" id="goseller_search_btn" /></a>
                </div>
                <div class="setting_box">
                    <div class="no_result" style="display:none;">
                        <img src="../admin/gd_share/img/icon_no_result.png" alt="" />
                        <p>검색된 동영상이 없습니다.</p>
                    </div>
                    <!-- 리스트 노출-->
                    <ul class="setting_list">
                        <?php foreach($gosellerData['list'] as $key => $val) {?>
                            <li class="video_list">
                                <div class="input_area">
                                    <img src="<?=$val['img']?>" />
                                </div>
                                <div class="text_area">
                                    <a href="<?=$val['contentUrl']?>" target="_blank">
                                    <span class="txt">
                                        <strong><span id="video_menu">[<?=$val['menu']?>]</span> <?=$val['title']?></strong>
                                        <span class="s_txt"><?=$val['description']?></span>
                                    </span>
                                    </a>
                                </div>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $(function(){
        // 검색버튼 클릭시
        $("#goseller_search_btn").on('click',function (){
            var gosellerSearchWord = $("#goseller_search_box").val().toUpperCase();
            // 검색어 유지를 위해 로컬스토리지에 저장
            localStorage.setItem("gosellerSearchWord", gosellerSearchWord);

            $(".video_list").hide();
            $(".no_result").hide();

            $.each($(".video_list"), function () {
                var v_list = $(this).find('.txt').text().toUpperCase();
                var menuTitle = $(this).find('#video_menu').text().toUpperCase();

                // 메뉴명을 제외한 콘텐츠 제목+추가설명
                v_list = v_list.replace(menuTitle, '');

                if (v_list.match(gosellerSearchWord)) {
                    $(this).show();
                }
            });

            if ($(".video_list:visible").length === 0) {
                $(".no_result").show();
            }
        });

        // 검색어 입력후 엔터시
        $('#goseller_search_box').keyup(function(e) {
            if (e.keyCode == 13) {
                $('#goseller_search_btn').trigger('click');
            }
        });

        // open_layer
        $('.btn_goseller').on('click',function(){
            if (!localStorage.getItem('gosellerSearchWord')) {
                $("#goseller_search_box").prop('value','');
                $("#goseller_search_btn").trigger('click');
                $(".no_result").hide();
            }

            $('.ly_goseller').show();
            var data = {
                'mode': 'saveGosellerDisplayConfig',
                'key': 'displayFl',
                'val': 'true'
            }
            $.ajax('/base/layer_goseller_guide_ps.php', {type: "post", data: data});
        });

        // goseller 검색 인풋 텍스트 지우기 버튼 211201 고셀러TV 추가
        $('#goseller_search_box').focusin(function() {
            $('.btn_goseller_erase').css({'display':'inline-block'});
        });

        $('#goseller_search_box').focusout(function() {
            if ($('#goseller_search_box').val() == '') {
                $('.btn_goseller_erase').css({'display':'none'});
            } else {
                $('.btn_goseller_erase').css({'display':'inline-block'});
            }
        });

        // goseller 검색 인풋 텍스트 삭제 211201 고셀러TV 추가
        $('.btn_goseller_erase').on('click',function(){
            $(this).closest('div').find('input').val("");
            $(this).css({'display':'none'});
        });

        // close_layer
        $('.btn_close').on('click',function(){
            if (localStorage.getItem('gosellerSearchWord')) {
                localStorage.removeItem('gosellerSearchWord');
            }
            $('.ly_goseller').hide();
            var data = {
                'mode': 'saveGosellerDisplayConfig',
                'key': 'displayFl',
                'val': 'false'
            }
            $.ajax('/base/layer_goseller_guide_ps.php', {type: "post", data: data});
        });

        // 검색어 유지
        if (localStorage.getItem('gosellerSearchWord')) {
            $("#goseller_search_box").attr('value',localStorage.getItem('gosellerSearchWord'));
            $("#goseller_search_btn").trigger('click');
        }
    });
</script>
<!-- // 211201 고셀러TV 추가 -->

<?php if($indexFl == 'y') { ?>
    <div class="gnbAnchor_wrap2">
        <span class="btn_goseller js-popover" data-html="true" data-content="자주 묻는 질문 동영상 가이드" data-placement="left"><img src="../admin/gd_share/img/button_goseller.png" alt="자주 묻는 질문 동영상 가이드"/></span><!-- 211201 고셀러TV 추가 -->
    </div>
<?php } ?>