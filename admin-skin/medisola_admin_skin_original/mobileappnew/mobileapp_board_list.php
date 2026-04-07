<div class="mobileapp-order-view">
    <h2 class="section-header" style="padding-left: 10px">게시판 리스트</h2>
    <div class="container-default">
        <table class="table table-bordered" style="margin-bottom: 5px;">
            <colgroup>
                <col style="width: 50%;">
                <col style="width: 30%;">
                <col>
            </colgroup>
            <tbody id="boardList">
            <tr>
                <th style="background-color: #f8f8f8;">이름</th>
                <th style="background-color: #f8f8f8;">신규/전체글</th>
                <th style="background-color: #f8f8f8;">미답변</th>
            </tr>
            <?php
            if (isset($data) && is_array($data)) {
                foreach ($data as $val) {
                    $bdQuestionCnt = $val['bdQuestionCnt'];
                    if(is_numeric($bdAnswerCnt)) {
                        $bdQuestionCnt = number_format($val['bdQuestionCnt']);
                    }
            ?>
            <tr class="text-center" data-sno="<?=$val['sno']; ?>">
                <td style="height:40px; !important;"><?=$val['bdNm']; ?></td>
                <td><strong><span style="color:#ec534b;"><?=number_format($val['bdNewListCnt']); ?></span></strong> / <?=number_format($val['bdListCnt']); ?></span></td>
                <td>
                <?php if (is_numeric($bdQuestionCnt)) { ?>
                    <strong><span style="color:#ec534b"><?=$bdQuestionCnt; ?></span></strong>
                <?php } else { ?>
                    -
                <?php } ?>
                </td>
            </tr>
            <?php
                }
            }
            ?>
            </tbody>
        </table>
        <div class="description" style="margin-left: 5px">신규게시물 수는 게시판 등록의 "NEW 아이콘 효력시간" 에 설정된 시간을 기준으로 표시됩니다.</div>
    </div>
</div>