<style>
    .sortable > li {height: 100px; margin: 0 5px; display: inline-block}
    .sortable > li > label {height: 100%}
    .sortable > li > label > div {height: 100%}
    .sortable > li > label > div img {height: 80%;margin-top: 4px;}
</style>
<div class="page-header js-affix">
    <h3><?=end($naviMenu->location); ?> </h3>
</div>

<?php include($goodsSearchFrm); ?>

<form id="frmBatch" name="frmBatch" action="../goods/goods_ps.php"  target="ifrmProcess" method="post">
    <input type="hidden" name="mode" value="g">

    <table class="table table-rows">
        <thead>
        <tr>
            <th class="center" width='100'>상품코드</th>
            <th class="center" width='80'>이미지</th>
            <th width="600">상품명</th>
            <th>상품상세배너</th>
        </tr>


        </thead>
        <tbody>
        <?php
        if (gd_isset($data) && count($data) > 0 ) {
            foreach ($data as $key => $val) {
                ?>
                <tr>
                    <td class="center"><?=$val['goodsNo']; ?></td>
                    <td class="center">
                        <div>
                            <?=gd_html_goods_image($val['goodsNo'], $val['imageName'], $val['imagePath'], $val['imageStorage'], 40, $val['goodsNm'], '_blank'); ?>
                        </div>
                    </td>
                    <td>
                        <a class="text-blue hand"
                           onclick="goods_register_popup('<?= $val['goodsNo']; ?>' <?php if (gd_is_provider() === true) {
                               echo ",'1'";
                           } ?>);"><?= $val['goodsNm']; ?></a>
                    </td>
                    <td>
                        <button type="button" onclick="goods_banner_popup('<?= $val['goodsNo']; ?>')"
                                class="btn btn-gray btn-sm">배너등록
                        </button>
                        <button type="button" onclick="goods_banner_delete('<?= $val['goodsNo']; ?>')"
                                class="btn btn-gray btn-sm">선택된 배너 삭제
                        </button>
                        <?php if ($val['goodsBanner']){ ?>
                        <div>
                            <ul class="sortable" id="<?= $val['goodsNo'] ?>">
                                <?php foreach ($val['goodsBanner'] as $key2 => $val2) { ?>
                                    <li class="wm" id="<?= $val2['sno'] ?>">
                                        <label class="nobr">
                                            <input type="checkbox" name="sno[]" value="<?= $val2['sno'] ?>">
                                            <div><a href="/data/icon/goods_view_banner/<?= $val2['bannerImage2'] ?>"
                                                    target="_blank"><?= gd_html_image(UserFilePath::icon('goods_view_banner', $val2['bannerImage1'])->www(), $val2['bannerImage1']); ?></a>
                                            </div>
                                        </label>
                                    </li>
                                <?php }
                                }
                                ?>
                            </ul>
                        </div>
                    </td>
                </tr>
                <?php
            }
        }
        ?>
        </tbody>
    </table>
    <div class="center"><?=$page->getPage();?></div>
</form>

<script type="text/javascript">
    function goods_banner_popup(goodsNo, isProvider, page) {
        $.get('/goods/layer_goods_banner_register.php?popupMode=yes&goodsNo=' + goodsNo, function () {
            BootstrapDialog.show({
                title: '상품상세 배너 설정',
                size: BootstrapDialog.SIZE_NORMAL,
                message: arguments[0]
            });
        });
    };
    function goods_banner_delete(goodsNo) {
        var chkCnt = $('input[name*="sno"]:checked').length;
        if (chkCnt == 0) {
            alert('선택된 이미지가 없습니다.');
            return;
        }

        dialog_confirm('선택한 ' + chkCnt + '개 이미지를 정말로 삭제하시겠습니까?', function (result) {
            if (result) {
                $('#frmBatch input[name=\'mode\']').val('delete_banner');
                $('#frmBatch').attr('method', 'post');
                $('#frmBatch').attr('action', './goods_ps.php');
                $('#frmBatch').submit();
            }
        });

    };
    $(function(){
        $( ".sortable" ).sortable({
            items: $('.sortable > li'),
            revert: true,

            stop: function() {
                var goodsNo = $(this).attr('id');
                var wm = [];

                $('#'+goodsNo).children('li').each(function (){
                    var sno = $(this).attr('id');
                    var sort = $(this).index() + 1;
                    wm.push(sno+'|'+sort);
                })

                $.ajax({
                    type: 'POST'
                    , url: './goods_ps.php'
                    , async: false
                    , data: 'mode=change_banner_sort&goodsNo='+goodsNo+'&data='+wm
                    , dataType: JSON
                    , success: function(response) {
                    },
                    error: function (error) {
                    }
                })

            }
        });
        $( ".sortable > li" ).on('mouseup', function (){

        });
    })

</script>