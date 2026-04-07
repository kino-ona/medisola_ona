<div class="mobileapp-member-view">
    <h2 class="section-header mView-Title">
        회원정보
    </h2>

    <div class="container-default">
        <table class="table table-bordered table1 mView-list-table">
            <form name="mobileapp_member_view_form" id="mobileapp_member_view_form" >
            <input type="hidden" name="memNo" id="mobileapp_memNo" value="<?= $data['memNo']; ?>" />
            <colgroup>
                <col style="width: 30%;">
                <col>
            </colgroup>
            <tbody>
            <tr>
                <th>회원구분</th>
                <td colspan="2">
                    <?= $data['memberFl']; ?>
                </td>
            </tr>
            <tr class="text-center">
                <th style="width: 30%;">승인</th>
                <td colspan="2">
                    <table class="mView-radio-box">
                        <tr>
                            <td>
                                <input id="mobile-open-1" class="radio" type="radio" name="appFl" checked="checked" value="y" <?= $checked['appFl']['y']; ?> />
                                <label for="mobile-open-1" class="radio-label mgb0">승인</label>
                            </td>
                            <td>
                                <input id="mobile-open-2" class="radio mView-radio" type="radio" name="appFl" value="n" <?= $checked['appFl']['n']; ?> />
                                <label for="mobile-open-2" class="radio-label mView-radio-label mgb0">미승인</label>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <th>이름</th>
                <td colspan="2">
                    <?= $data['memNm']; ?>
                </td>
            </tr>
            <tr>
                <th>아이디</th>
                <td colspan="2">
                    <?= $data['memId']; ?>
                </td>
            </tr>
            <tr>
                <th>회원가입일</th>
                <td colspan="2">
                    <?= $data['entryDt']; ?>
                </td>
            </tr>
            <tr>
                <th>전화번호</th>
                <td colspan="2">
                    <a href="tel:<?= $data['phone']; ?>"><?= $data['phone']; ?></a>
                </td>
            </tr>
            <tr>
                <th>휴대폰</th>
                <td colspan="2">
                    <a href="tel:<?= $data['cellPhone']; ?>"><?= $data['cellPhone']; ?></a>
                </td>
            </tr>
            <tr>
                <th>이메일</th>
                <td colspan="2">
                    <?= $data['emailComplete']; ?>
                </td>
            </tr>
            <tr>
                <th>주소</th>
                <td colspan="2">
                    <div>
                        (<?= $data['zonecode']; ?>)&nbsp;
                        <?= $data['address']; ?>
                    </div>
                    <div>
                        <?= $data['addressSub']; ?>
                    </div>
                </td>
            </tr>
            <tr>
                <th>총 구매금액</th>
                <td colspan="2">
                    <?= gd_currency_display($data['saleAmt']); ?>
                </td>
            </tr>
            </tbody>

            </form>
        </table>
    </div>

    <div class="mView-partition"></div>

    <h2 class="section-header mView-Title-order">
        주문내역
    </h2>
    <div class="container-default overflow-h">
        <table class="table table-bordered table-condensed table2 mView-list-table">
            <colgroup>
                <col style="width:35%;" />
                <col style="width:45%;" />
                <col style="width:20%;" />
            </colgroup>
            <thead>
            <tr>
                <th>주문번호</th>
                <th>주문상품</th>
                <th>처리상태</th>
            </tr>
            </thead>
            <tbody class="rowlink" id="mobileapp_memberViewOrderArea">
            <?php
            if (empty($orderData) === false && is_array($orderData)) {
                foreach ($orderData as $valueData) {
            ?>
                <tr class="lists text-center" data-orderNo="<?= $valueData['orderNo']; ?>">
                    <td><?= $valueData['orderNo']; ?></td>
                    <td><?= $valueData['orderGoodsNm']; ?></td>
                    <td><?= $orderStatusRange[$valueData['orderStatus']]; ?></td>
                </tr>
            <?php
                }
            }
            else {
            ?>
                <tr class="lists text-center">
                    <td colspan="3">최근 구매내역이 없습니다.</td>
                </tr>
            <?php
            }
            ?>
            </tbody>
        </table>
        <center><a id="mobileapp_moreMemberViewOrder" class="btn btn-lg btn-block-app btn-default-gray border-r-n mView-more-btn" href="javascript:;" data-memId="<?= $data['memId']; ?>" data-treatDate1="<?= $treatDate[0];?>" data-treatDate2="<?= $treatDate[1];?>">더보기</a></center>
    </div>

    <div class="container-default mView-footer-btn-area">
        <center>
        <div class="row">
           <div class="col-xs-8">
               <button type="button" class="btn btn-lg btn-info border-r-n mView-footer-save-btn" id="mobileapp_memberViewModifyBtn">저&nbsp;장</button>
           </div>
            <div class="col-xs-4">
                <button type="button" class="btn btn-lg btn-default-gray border-r-n" id="mobileapp_memberViewListBtn">목&nbsp;록</button>
            </div>
        </div>
        </center>
    </div>
</div>
