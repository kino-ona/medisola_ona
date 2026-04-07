<h2 class="section-header section-header1 mgb0"><span>알림</span> 상세설정</h2>
<div class="container-default">
    <table class="table table-bordered mg0">
        <colgroup>
            <col style="width: 33%;" />
            <col />
            <col />
        </colgroup>
        <tbody>
        <tr class="text-center">
            <th>수신여부</th>
            <td style="width: 33%;">
                <input id="order-alim1" class="radio" type="radio" name="order_alim" value="1" />
                <label for="order-alim1" class="radio-label mgb0">켜기</label>
            </td>
            <td style="width: 33%;">
                <input id="order_alim2" class="radio" type="radio" name="order_alim" value="0" checked />
                <label for="order_alim2" class="radio-label mgb0">끄기</label>
            </td>
        </tr>
        <tr class="text-center">
            <th>전송간격</th>
            <td colspan="2">
                <div class="form-group selectbox">
                    <label for="select-opt">10분</label>
                    <select name="send_term" id="send_term" class="form-control input-sm select-opt">
                        <option value="10" selected="selected">10분</option>
                        <option value="30">30분</option>
                        <option value="60">1시간</option>
                        <option value="120">2시간</option>
                    </select>
                </div>
            </td>
        </tr>
        <tr class="text-center">
            <th>알림일시중지</th>
            <td style="width: 33%;">
                <input id="stop_run_type1" class="radio" type="radio" name="stop_run_type" value="1" />
                <label for="stop_run_type1" class="radio-label mgb0">실행</label>
            </td>
            <td style="width: 33%;">
                <input id="stop_run_type2" class="radio" type="radio" name="stop_run_type" value="0" checked />
                <label for="stop_run_type2" class="radio-label mgb0">미실행</label>
            </td>
        </tr>
        <tr id="alarm_stop_area">
            <th>알림중지시간</th>
            <td colspan="2">
                <table style="width: 100%">
                    <colgroup>
                        <col style="width: 35%;" />
                        <col />
                        <col style="width: 35%;" />
                        <col />
                    </colgroup>
                    <tbody>
                    <tr>
                        <td>
                            <div class="form-group selectbox">
                                <label for="select-opt">0</label>
                                <select name="stop_stime" id="stop_stime" class="form-control input-sm select-opt">
                                    <?php for ($i = 0; $i < 24; $i++) { ?>
                                        <option value="<?php echo sprintf("%02d", $i); ?>"><?php echo $i; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </td>
                        <td>시 ~</td>
                        <td>
                            <div class="form-group selectbox">
                                <label for="select-opt">0</label>
                                <select name="stop_etime" id="stop_etime" class="form-control input-sm select-opt">
                                    <?php for ($i = 0; $i < 24; $i++) { ?>
                                        <option value="<?php echo sprintf("%02d", $i); ?>"><?php echo $i; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </td>
                        <td>시</td>
                    </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        </tbody>
    </table><!-- .table_typ1 -->
    <div class="text-center" style="margin-top: 5px;">
        <input type="hidden" id="godo5" value="T">
        <input type="hidden" id="device_uid" value="<?=$device_uid?>">
        <input type="hidden" id="shop_domain" value="<?=$shop_domain?>">
        <button id="updateConfig" class="btn_submit btn btn-block-app btn-info">저장</button>
    </div>

    <p class="description mgt20">
        수신체크 시 고객문의, 주문이 접수되면 푸시 알림메시지를 받습니다.
    </p>
</div><!-- .sec -->
