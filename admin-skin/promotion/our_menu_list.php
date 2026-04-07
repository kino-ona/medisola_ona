<div class="page-header js-affix">
    <h3><?php echo end($naviMenu->location); ?></h3>
    <div class="btn-group">
        <a href="our_menu_regist.php" class="btn btn-red-line">우리메뉴 등록</a>
    </div>
</div>

<form id="frmCouponList" action="../promotion/our_menu_ps.php" method="post">
    <input type="hidden" name="mode" value="deleteCouponList"/>
    <table class="table table-rows promotion-coupon-list">
        <thead>
        <tr>
            <th>ID</th>
            <th>메뉴명</th>
            <th>메뉴타이틀</th>
            <th>메뉴설명</th>
            <th>정렬순서</th>
            <th>이동링크</th>
            <th>이미지경로</th>
            <th>노출채널</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php
        if (empty($data) === false && is_array($data)) {
            foreach ($data as $key => $val) {

                ?>
                <tr class="text-center">
                    <td><?= $val['id'] ?></td>
                    <td><?= $val['name'] ?></td>
                    <td><?= $val['title'] ?></td>
                    <td><?= $val['description'] ?></td>
                    <td><?= $val['sortPosition'] ?></td>
                    <td><?= $val['link'] ?></td>
                    <td><?= $val['imageUrlWeb'] ?></td>
                    <td><?= $val['displayChannel'] ?></td>
                    <td>
                        <a href="our_menu_regist.php?id=<?= $val['id'] ?>&ypage=<?= $page->page['now'] ?>" class="btn btn-sm btn-white">수정</a>
                    </td>
                </tr>
                <?php
            }
        } else {
            ?>
            <tr>
                <td colspan="14" class="no-data">
                    검색된 메뉴가 없습니다.
                </td>
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>
    <div><?= $error; ?></div>
</form>

<div class="center"><?= $page->getPage(); ?></div>

<script type="text/javascript">
    <!--
    $(document).ready(function () {
        $('#frmSearchCoupon').validate({
            submitHandler: function (form) {
                form.submit();
            }
        });

    });
    //-->
</script>
