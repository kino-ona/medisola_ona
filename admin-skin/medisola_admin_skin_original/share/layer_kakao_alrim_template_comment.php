<div>
    <div class="mgt10"></div>
    <div>
        <table class="table table-cols no-title-line">
            <colgroup>
                <col class="width-md"/>
                <col/>
                <col class="width-md"/>
                <col/>
                <col class="width-xs"/>
            </colgroup>
            <tr>
                <th>문의/답변 로그</th>
                <td>
                    <div class="row pdt5">
                        <div class="pdl15">
                            <label class="width100p">
                                <?php
                                $sCommentLog = '';
                                if (count($data) > 0) {
                                    foreach ($data as $oData) {
                                        $aData = get_object_vars($oData);
                                        if ($aData['status'] == 'INQ' || $aData['status'] == 'REP' || $aData['status'] == 'REJ') {
                                            $sCommentLog .= date('Y-m-d H:i:s', strtotime($aData['createdAt'])) . "\r\n";
                                            $sCommentLog .= '---------------------------------------------' . "\r\n";
                                            if ($aData['status'] == 'INQ') {
                                                $sCommentLog .= '작성자: 솔루션운영자' . "\r\n";
                                                $sCommentLog .= '상태: 문의' . "\r\n";
                                            } else {
                                                $sCommentLog .= '작성자: 카카오톡' . "\r\n";
                                                $sCommentLog .= '상태: 답변' . "\r\n";
                                            }
                                            $sCommentLog .= '내용: ' . $aData['content'] . "\r\n";
                                            $sCommentLog .= '---------------------------------------------' . "\r\n\r\n\r\n";
                                        }
                                    }
                                }
                                ?>
                                <textarea id="commentLog" name="commentLog" rows="13" class="form-control width100p" data-close="true" readonly><?php echo $sCommentLog; ?></textarea>
                            </label>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</div>
<div>
    <div class="notice-info">
        템플릿 검수에 대한 문의사항은 <a href="https://cs.kakao.com/requests?category=481&locale=ko&node=46235&service=159 " target="_blank" class="btn-link">카카오톡 고객센터</a>로 문의하시기 바랍니다
    </div>
    <div class="mgt30"></div>
    <div class="text-center">
        <input type="button" id="kakao_close" value="닫기" class="btn btn-white btn-hf" />
    </div>
    <div class="mgt10"></div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        // 레이어 창 닫기
        $("#kakao_close").click(function () {
            $('.close').trigger('click');
        });
    });
</script>

