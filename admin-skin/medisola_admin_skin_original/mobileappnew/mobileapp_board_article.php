<div class="mobileapp-board-view">
    <h2 class="section-header" id="bdNm" style="padding-left: 10px;"><?= $bdNm; ?></h2>
    <div class="container-default">
        <table class="table-board-article" style="width: 100%;">
            <colgroup>
                <col>
                <col width="5%">
            </colgroup>
            <tbody id="article_area">
            </tbody>
        </table>
        <input type="hidden" id="bdId" value="<?= $bdId; ?>">
        <input type="hidden" id="sno" value="<?= $sno; ?>">
        <input type="hidden" id="nowCount" value="">
        <input type="button" id="getArticle" style="display:none;">
        <center id="moreDisplay"><a id="mobileapp_moreArticleList" class="btn btn-lg btn-block-app btn-default-gray border-r-n" href="javascript:;">더보기</a></center>
    </div>

    <!-- Modal start -->
    <div class="modal fade modal-center-board" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-80">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title text-center" id="myModalLabel">게시글 답변</h4>
                </div>
                <div class="modal-body overflowY-auto">
                    <h2 class="section-header section-header1 bView-Title" style="margin-top:0px"><?= $bdNm; ?></h2>
                    <ul class="pd-modify">
                        <li id="orgContent">
                        </li>
                    </ul>
                    <h2 class="section-header section-header1 bView-Title" style="margin-top:0px">게시글 답변</h2>
                    <ul class="pd-modify">
                        <li class="form-group selectbox" id="selectBoxReplyTemplate">
                        </li>
                        <li>
                            <input type="text" id="answerSubject" class="invoice" placeholder="답변 제목을 입력해주세요."/>
                        </li>
                        <li>
                            <textarea id="answerContents" class="invoice" style="height:90px;" placeholder="답변 내용을 입력해주세요."/></textarea>
                        </li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <div class="text-center overflow-h">
                        <div class="pull-left" style="width:80%; padding: 0 5px 0 0;">
                            <input type="hidden" id="replyStatus" value="3">
                            <input type="hidden" id="replySno" value="">
                            <button type="button" id="saveReply" class="btn btn-lg btn-info border-r-n" style="width:100%; color:white; background-color:#fa2828; position: relative;">저장</button>
                        </div>
                        <div class="pull-right" style="width:20%;">
                            <button type="button" class="btn btn-lg btn-inverse gd-btn-list btn_type1 border-r-n" data-dismiss="modal" style="width:100%; position: relative;">닫기</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal end -->
</div>
