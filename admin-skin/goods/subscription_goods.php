<?php if($wmSubscription) { ?>
<div class='page-header js-affix'>
    <h3><?=end($naviMenu->location)?></h3>
</div>
<?php include $goodsSearchFrm; ?>
<div class='table-title'>상품목록</div>
<div class="table-header">
  <div class="pull-left">
    검색 <strong><?=number_format($page->recode['total']);?></strong>개 /
    전체 <strong><?=number_format($page->recode['amount']);?></strong>개
  </div> <!-- pull-left -->
</div> <!-- table-header -->
<form method='post' action='../goods/indb_subscription.php' target='ifrmProcess' autocomplete='off'>
<input type='hidden' name='mode' value='update_goods_config_list'>
<table class='table table-rows'>
  <thead>
  <tr>
    <th width='20' class='center'><input type='checkbox' class='js-checkall' data-target-name='goodsNo'></th>
    <th width='60' class='center'>정기 결제상품</th>
    <th width='100' class='center'>상품코드</th>
    <th width='40' class='center'>이미지</th>
    <th class='center'>상품명</th>
    <th width='150' class='center'>연결된 정기결제 상품</th>
  </tr>
  </thead>
  <tbody>
<?php if (gd_isset($list)) : ?>
<?php foreach ($list as $key => $li) : ?>
<tr>
  <td class='center' nowrap>
    <input type='checkbox' name='goodsNo[]' value='<?=$li['goodsNo']?>'>
  </td>
  <td class='center' nowrap>
    <input type='checkbox' name='isSubscription[<?=$li['goodsNo']?>]' value='1'<?php if ($li['isSubscription']) echo " checked";?>> 정기결제
  </td>
  <td class='center' nowrap><?=$li['goodsNo']?></td>
  <td class='center' nowrap><?=$li['images'][0]?></td>
  <td><span onclick="goods_register_popup('<?=$li['goodsNo']?>');" style='cursor: pointer;'><?=$li['goodsNm']?></span></td>
  <td class='center' nowrap>
    <?php if ($li['isSubscription'] == 0) { ?>
      <input type='number' name='linkedSubscriptionGoodsNo[<?=$li['goodsNo']?>]' value='<?=$li['linkedSubscriptionGoodsNo']?>' style='width: 100px; text-align: center;' placeholder='상품번호'>
      <?php if ($li['linkedSubscriptionGoodsNo'] > 0) { ?>
        <a href='javascript:goods_register_popup("<?=$li['linkedSubscriptionGoodsNo']?>");' class='btn btn-sm btn-default' title='연결된 상품 보기'>
          상품 보기
        </a>
      <?php } ?>
    <?php } else { ?>
      <span style='color: #999;'>-</span>
    <?php } ?>
  </td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
   </tbody>
</table>
<div class='table-action form-inline' style='padding-left: 10px;'>
    <input type='submit' value='수정하기' class='btn btn-black' onclick="return confirm('정말 수정하시겠습니까?');">
</div> <!-- table-action -->
<div class="text-center"><?=$page->getPage();?></div>
</form>
<?php } ?>