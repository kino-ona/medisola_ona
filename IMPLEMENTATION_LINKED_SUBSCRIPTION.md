# 정기결제 상품 연결 기능 구현 내역

## 개요
일반 상품과 정기결제 상품을 양방향으로 연결하여, 상품 상세 페이지에서 탭으로 결제 방식을 전환할 수 있는 기능 구현

## 구현 날짜
2025-11-05

## 데이터베이스 변경사항

### 필드 추가
- **테이블**: `es_goods`
- **컬럼**: `linkedSubscriptionGoodsNo` INT(11) DEFAULT 0
- **설명**: 연결된 정기결제 상품번호
- **마이그레이션**: ✅ 완료

## 백엔드 구현 (godo-module)

### 1. 데이터베이스 필드 정의
**파일**: `/godo-module/Component/Database/DBTableField.php:55`
```php
$arrField[] = ['val' => 'linkedSubscriptionGoodsNo', 'typ' => 'i', 'def' => 0, 'name' => '연결된 정기결제 상품번호'];
```

### 2. 상품 상세 페이지 데이터 로딩 (양방향 링크)

#### Front Controller
**파일**: `/godo-module/Controller/Front/Goods/GoodsViewController.php:41-56`
```php
// 일반 상품 <-> 정기 결제 상품 연결 로직
$db = \App::load('DB');

if ($goodsView['isSubscription'] == 0 && $goodsView['linkedSubscriptionGoodsNo'] > 0) {
    // 일반 상품 → 정기 결제 상품 링크 (Forward)
    $this->setData('linkedSubscriptionGoodsNo', $goodsView['linkedSubscriptionGoodsNo']);
} elseif ($goodsView['isSubscription'] == 1) {
    // 정기 결제 상품 → 일반 상품 찾기 (Reverse)
    $sql = "SELECT goodsNo FROM " . DB_GOODS .
           " WHERE linkedSubscriptionGoodsNo = '{$goodsView['goodsNo']}' AND delFl='n' LIMIT 1";
    $result = $db->fetch($sql);
    if ($result) {
        $this->setData('linkedRegularGoodsNo', $result['goodsNo']);
    }
}
```

#### Mobile Controller
**파일**: `/godo-module/Controller/Mobile/Goods/GoodsViewController.php:41-56`
- Front Controller와 동일한 로직

### 3. 정기결제 상품 일괄 관리
**파일**: `/godo-module/Controller/Admin/Goods/IndbSubscriptionController.php:55-71`
```php
foreach ($in['goodsNo'] as $goodsNo) {
    $isSubscription = $in['isSubscription'][$goodsNo]?1:0;
    $linkedSubscriptionGoodsNo = isset($in['linkedSubscriptionGoodsNo'][$goodsNo]) ? (int)$in['linkedSubscriptionGoodsNo'][$goodsNo] : 0;

    $sql = "UPDATE " . DB_GOODS . "
            SET isSubscription='{$isSubscription}',
                linkedSubscriptionGoodsNo='{$linkedSubscriptionGoodsNo}'
            WHERE goodsNo='{$goodsNo}'";
    $db->query($sql);
}
```

### 4. 개별 상품 등록/수정 저장
- **자동 처리**: GoDoMall의 `DBTableField` 자동 바인딩 시스템이 처리
- **관련 파일**: `Bundle\Component\Goods\GoodsAdmin::saveInfoGoods()`
- **추가 코드 불필요**: DBTableField 정의만으로 자동 저장됨

## 프론트엔드 구현 (godo-skin)

### Desktop - drorganic_24_renewal

#### 일반 상품 페이지
**파일**: `/godo-skin/front/drorganic_24_renewal/goods/goods_view.html:2207-2213`
```html
{* 일반 상품 <-> 정기 결제 상품 전환 탭 *}
<!--{ ? linkedSubscriptionGoodsNo > 0 }-->
<div class="purchase_type_tabs">
    <a href="javascript:void(0);" class="tab active">일반 결제</a>
    <a href="goods_view.php?goodsNo={=linkedSubscriptionGoodsNo}" class="tab">정기 결제</a>
</div>
<!--{ / }-->
```

#### 정기결제 상품 페이지
**파일**: `/godo-skin/front/drorganic_24_renewal/goods/goods_view_subscription.html:1570-1576`
```html
<!--{ ? linkedRegularGoodsNo > 0 }-->
<div class="purchase_type_tabs">
    <a href="goods_view.php?goodsNo={=linkedRegularGoodsNo}" class="tab">일반 결제</a>
    <a href="javascript:void(0);" class="tab active">정기 결제</a>
</div>
<!--{ / }-->
```

#### CSS 스타일
**파일**: `/godo-skin/front/drorganic_24_renewal/css/mdetail.css:230-255`
```css
.purchase_type_tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    border-bottom: 2px solid #e0e0e0;
}

.purchase_type_tabs .tab {
    flex: 1;
    padding: 15px;
    background: #f5f5f5;
    border: none;
    cursor: pointer;
    font-size: 16px;
    color: #666;
    transition: all 0.3s;
    font-weight: 400;
    text-decoration: none;
    display: block;
    text-align: center;
}

.purchase_type_tabs .tab.active {
    background: #fff;
    color: #333;
    font-weight: bold;
    border-bottom: 3px solid #00a0e9;
    pointer-events: none;
}

.purchase_type_tabs .tab:hover:not(.active) {
    background: #ebebeb;
}
```

### Mobile - dorganic_24_renewal

#### 일반 상품 페이지
**파일**: `/godo-skin/mobile/dorganic_24_renewal/goods/goods_view.html:1001-1007`
- Desktop과 동일한 HTML 구조

#### 정기결제 상품 페이지
**파일**: `/godo-skin/mobile/dorganic_24_renewal/goods/goods_view_subscription.html:612-618`
- Desktop과 동일한 HTML 구조

#### CSS 스타일
**파일**: `/godo-skin/mobile/dorganic_24_renewal/css/mdetail.css:112-145`
```css
.purchase_type_tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 15px;
    border-bottom: 2px solid #e0e0e0;
}

.purchase_type_tabs .tab {
    flex: 1;
    padding: 12px;
    background: #f5f5f5;
    border: none;
    cursor: pointer;
    font-size: 14px;
    color: #666;
    transition: all 0.3s;
    font-weight: 400;
    text-decoration: none;
    display: block;
    text-align: center;
}

.purchase_type_tabs .tab.active {
    background: #fff;
    color: #333;
    font-weight: bold;
    border-bottom: 3px solid #00a0e9;
    pointer-events: none;
}

.purchase_type_tabs .tab:hover:not(.active) {
    background: #ebebeb;
}
```

## 어드민 구현 (admin-skin)

### 1. 정기결제 상품 일괄 관리
**파일**: `/admin-skin/goods/subscription_goods.php:23,39-50`
```php
<th width='150' class='center'>연결된 정기결제 상품</th>

<td class='center' nowrap>
  <?php if ($li['isSubscription'] == 0) { ?>
    <input type='number' name='linkedSubscriptionGoodsNo[<?=$li['goodsNo']?>]'
           value='<?=$li['linkedSubscriptionGoodsNo']?>'
           style='width: 80px; text-align: center;' placeholder='상품번호'>
    <?php if ($li['linkedSubscriptionGoodsNo'] > 0) { ?>
      <a href='javascript:goods_register_popup("<?=$li['linkedSubscriptionGoodsNo']?>");'
         class='btn btn-sm btn-default' title='연결된 상품 보기'>
        <i class='fa fa-external-link'></i>
      </a>
    <?php } ?>
  <?php } else { ?>
    <span style='color: #999;'>-</span>
  <?php } ?>
</td>
```

### 2. 개별 상품 등록/수정 (Override 방식)

**원본 파일** (참고용, 수정하지 않음):
- `/admin-skin/medisola_admin_skin_original/goods/goods_register.php`

**Override 파일** (실제 수정):
- `/admin-skin/goods/goods_register.php:3566-3579`

```php
<tr>
    <th class="input_title r_space" nowrap="nowrap">연결된 정기결제 상품</th>
    <td colspan="3">
        <input type="number" name="linkedSubscriptionGoodsNo"
               value="<?=gd_isset($data['linkedSubscriptionGoodsNo'], 0); ?>"
               class="form-control width-lg" placeholder="상품번호 입력"
               min="0" style="display: inline-block; width: 150px;"/>
        <span class="help-block" style="display: inline-block; margin-left: 10px; color: #999;">
            일반 상품인 경우, 연결할 정기결제 상품번호를 입력하세요. (정기결제 상품은 입력 불가)
        </span>
        <?php if ($data['mode'] == 'modify' && !empty($data['linkedSubscriptionGoodsNo']) && $data['linkedSubscriptionGoodsNo'] > 0) { ?>
            <a href="javascript:goods_register_popup('<?=$data['linkedSubscriptionGoodsNo']?>');"
               class="btn btn-sm btn-default" title="연결된 상품 보기" style="margin-left: 5px;">
                <i class="fa fa-external-link"></i> 상품 보기
            </a>
        <?php } ?>
    </td>
</tr>
```

**위치**: 상품코드 필드와 상품명 필드 사이

## 주요 기술 사항

### 1. 양방향 링크 구현
- **Forward Link**: 일반 상품 → 정기결제 상품
  - `linkedSubscriptionGoodsNo` 필드 직접 조회
- **Reverse Link**: 정기결제 상품 → 일반 상품
  - SQL 역방향 조회: `WHERE linkedSubscriptionGoodsNo = {현재상품번호}`

### 2. UI/UX 개선
- **버튼 → 앵커 태그 변경**: 모바일 호환성 향상
- **pointer-events: none**: 활성 탭 클릭 방지
- **반응형 디자인**: Desktop/Mobile 별도 스타일

### 3. Admin Override 패턴
- 원본 파일(`medisola_admin_skin_original`)은 수정하지 않음
- 상위 폴더에 동일 경로로 복사하여 override
- 업데이트 시 원본과 비교 가능

## 관리자 사용 방법

### 방법 1: 개별 상품 등록/수정
1. 상품 관리 > 상품 등록/수정
2. "연결된 정기결제 상품" 필드에 상품번호 입력
3. 저장

### 방법 2: 일괄 관리
1. 상품 관리 > 정기결제 상품 관리
2. "연결된 정기결제 상품" 컬럼에 상품번호 입력
3. 저장

### 방법 3: 수정 시 연결 상품 확인
- "상품 보기" 버튼 클릭으로 팝업에서 연결된 상품 확인

## 고객 사용 경험

1. 일반 상품 페이지 접속
2. 옵션 선택 영역 위에 "일반 결제" / "정기 결제" 탭 표시
3. "정기 결제" 탭 클릭 시 연결된 정기결제 상품 페이지로 이동
4. 정기결제 상품에서도 반대로 "일반 결제" 탭으로 이동 가능

## 주의사항

1. **정기결제 상품은 linkedSubscriptionGoodsNo 필드 사용 불가**
   - 일반 상품에서만 설정 가능

2. **양방향 링크 원칙**
   - 일반 상품 A → 정기결제 상품 B 연결 시
   - 정기결제 상품 B에서 자동으로 일반 상품 A로 역링크 생성

3. **Admin Override 규칙**
   - `medisola_admin_skin_original/` 폴더는 절대 수정하지 않음
   - 커스터마이징은 항상 `/admin-skin/` 상위 폴더에서 수행

## 파일 구조 요약

```
godo-module/
├── Component/Database/DBTableField.php (필드 정의)
├── Controller/Front/Goods/GoodsViewController.php (데이터 로딩)
├── Controller/Mobile/Goods/GoodsViewController.php (데이터 로딩)
└── Controller/Admin/Goods/IndbSubscriptionController.php (일괄 저장)

godo-skin/
├── front/drorganic_24_renewal/
│   ├── goods/goods_view.html (일반 상품 탭)
│   ├── goods/goods_view_subscription.html (정기결제 상품 탭)
│   └── css/mdetail.css (탭 스타일)
└── mobile/dorganic_24_renewal/
    ├── goods/goods_view.html (일반 상품 탭)
    ├── goods/goods_view_subscription.html (정기결제 상품 탭)
    └── css/mdetail.css (탭 스타일)

admin-skin/
├── goods/
│   ├── goods_register.php (개별 상품 관리 - Override)
│   └── subscription_goods.php (일괄 관리)
└── medisola_admin_skin_original/goods/
    └── goods_register.php (참고용 원본)
```

## 테스트 체크리스트

- [ ] 일반 상품에서 정기결제 상품 번호 설정
- [ ] 상품 상세 페이지에서 탭 표시 확인
- [ ] 탭 클릭 시 정상 이동 확인
- [ ] 정기결제 상품에서 역방향 링크 표시 확인
- [ ] 모바일 환경에서 탭 동작 확인
- [ ] Admin에서 "상품 보기" 버튼 동작 확인
- [ ] 정기결제 상품 일괄 관리 페이지 동작 확인
