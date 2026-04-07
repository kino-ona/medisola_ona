<form id="frmOurMenu" name="frmOurMenu" action="our_menu_ps.php" method="post" class="content_form"
      enctype="multipart/form-data">
    <input type="hidden" name="mode" value="<?= $ourMenuData['mode']; ?>"/>
    <input type="hidden" name="ypage" value="<?= $ypage; ?>"/>
    <input type="hidden" name="totalCount" value="<?= $totalCount; ?>"/>
    <input type="hidden" name="id" value="<?= $ourMenuData['id']; ?>"/>

    <div class="page-header js-affix">
        <h3><?php echo end($naviMenu->location); ?></h3>
        <input type="submit" value="저장" class="btn btn-red"/>
    </div>

    <h5 class="table-title gd-help-manual">메뉴 정보 입력</h5>
    <table class="table table-cols">
        <colgroup>
            <col class="width-md"/>
            <col/>
            <col/>
            <col/>
        </colgroup>
        <tbody>
        <tr>
            <th>
                노출 채널
            </th>
            <td colspan="3">
                <label class="radio-inline">
                    <input type="radio" name="displayChannel" value="all" <?= $checked['displayChannel']['all'] ?> />전체
                </label>
                <label class="radio-inline">
                    <input type="radio" name="displayChannel" value="web" <?= $checked['displayChannel']['web'] ?> />웹
                </label>
                <label class="radio-inline">
                    <input type="radio" name="displayChannel" value="mob" <?= $checked['displayChannel']['mob'] ?> />모바일
                </label>
                <label class="radio-inline">
                    <input type="radio" name="displayChannel" value="none" <?= $checked['displayChannel']['none'] ?> />비노출
                </label>
            </td>
        </tr>

        <tr>
            <th class="require">
                메뉴명
            </th>
            <td colspan="3">
                <input type="text" name="name" value="<?= $ourMenuData['name'] ?>" class="form-control width-xl"
                       maxlength="200"/>
            </td>
        </tr>
        <tr>
            <th class="require">
                메뉴 타이틀
            </th>
            <td colspan="3">
                <input type="text" name="title" value="<?= $ourMenuData['title'] ?>" class="form-control width-xl"
                       maxlength="200"/>
            </td>
        </tr>
        <tr>
            <th>
                메뉴 설명
            </th>
            <td colspan="3">
                <input type="text" name="description" value="<?= $ourMenuData['description'] ?>"
                       class="form-control width100p" maxlength="200"/>
            </td>
        </tr>

        <tr>
            <th>
                이동 링크
            </th>
            <td colspan="3">
                <input type="text" name="link" value="<?= $ourMenuData['link'] ?>" class="form-control width100p"
                       maxlength="200"/>
            </td>
        </tr>

        <tr>
            <th>
                태그("," 로 구분지어서 입력하세요.)
            </th>
            <td colspan="3">
                <input type="text" name="tags" value="<?= $ourMenuData['tags'] ?>" class="form-control width100p"
                       maxlength="200"/>
            </td>
        </tr>
        <tr>
            <th>
                노출순서
            </th>
            <td colspan="3">
                <input type="number" step="0.1" name="sortPosition" value="<?= $ourMenuData['sortPosition'] ?>"
                       class="form-control width100p" maxlength="20"/>
            </td>
        </tr>

        <tr>
            <th>
                메뉴노출이미지
            </th>
            <td colspan="3">
                <div class="radio form-inline reg-ourmenuimage pdt10">
                    <input type="file" name="ourMenuImage" class="form-control"/>
                    <input type="hidden" name="imageUrlWeb" value="<?= $ourMenuData['imageUrlWeb'] ?>" />
                    <input type="hidden" name="imageUrlMob" value="<?= $ourMenuData['imageUrlMob'] ?>" />
                    <div style="max-width: 100px;">
                        <?php
                        if ($ourMenuData['ourMenuImage']) {
                            echo '<img src="' . $ourMenuData['ourMenuImage'] . '" alt="메뉴이미지" style="width: 100%;"/>';
                        }
                        ?>
                    </div>

                </div>
            </td>
        </tr>

        </tbody>
    </table>

</form>

<script type="text/javascript">
    /**
     * 출석체크, 회원정보수정 이벤트 신규쿠폰 등록 시 등록 후 호출되는 함수
     *
     * @param string couponEventType 자동발급쿠폰 종류
     */
    function unload_callback(couponEventType) {
        <?php
        if(gd_isset($callback, '') != ''){?>
        var callback = window.opener.<?=$callback?>;
        if ($.isFunction(callback)) {
            callback(couponEventType);
        }
        <?php }
        ?>
    }

</script>