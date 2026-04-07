<style>
    .table-cols .require {min-width: 120px}
</style>
<div class="table-title">
    배너이미지 등록
</div>
<form id="frmBanner" name="frmBanner" target="ifrmProcess" action="./goods_ps.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="mode" value="banner_register" />
    <input type="hidden" name="goodsNo" value="<?= $goodsNo ?>">
    <table class="table table-cols">
        <colgroup>
            <col>
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th class="require">배너</th>
            <td>
                <div style="float:left;">
                    <input type="file" name="bannerImage1" value="" class="form-control" required/>
                    <div style="padding-top:5px;"><?php if (empty($data['iconImage']) === false) {
                            echo gd_htmlspecialchars_slashes($data['iconImage'], 'add');
                        } ?>
                    </div>
                </div>            </td>
        </tr>
        <tr>
            <th class="require">배너팝업</th>
            <td>
                <div class="form-inline">
                    <div style="float:left;">
                        <input type="file" name="bannerImage2" value="" class="form-control" required/>
                        <div style="padding-top:5px;"><?php if (empty($data['iconImage']) === false) {
                                echo gd_htmlspecialchars_slashes($data['iconImage'], 'add');
                            } ?>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
    <div class="table-btn">
        <button type="submit" class="btn btn-sm btn-black">등록하기</button>
    </div>
</form>
