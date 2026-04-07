-- ================================================================
-- 영양 정보 관리 어드민 메뉴 등록
-- ================================================================
-- 실행 전 반드시 아래 쿼리로 부모 메뉴 번호를 확인하세요:
--
--   SELECT adminMenuNo, adminMenuName, adminMenuCode
--   FROM es_adminMenu
--   WHERE adminMenuCode = 'addGoods' AND adminMenuDepth = 2;
--
-- 조회된 adminMenuNo 값을 아래 INSERT의 adminMenuParentNo에 넣으세요.
-- ================================================================

-- Step 1: 부모 메뉴(추가상품 관리) 번호 확인
SELECT adminMenuNo, adminMenuName, adminMenuCode
FROM es_adminMenu
WHERE adminMenuCode = 'addGoods' AND adminMenuDepth = 2;

-- Step 2: 현재 가장 큰 adminMenuNo 확인 (새 번호 생성용)
SELECT adminMenuNo FROM es_adminMenu ORDER BY adminMenuNo DESC LIMIT 1;

-- Step 3: 영양 정보 관리 메뉴 추가
-- adminMenuNo: Step 2 결과의 다음 번호로 설정 (예: godo00402 → 'medi00001')
INSERT INTO es_adminMenu (
  adminMenuNo,
  adminMenuType,
  adminMenuProductCode,
  adminMenuCode,
  adminMenuName,
  adminMenuUrl,
  adminMenuDepth,
  adminMenuParentNo,
  adminMenuSort,
  adminMenuDisplayType,
  adminMenuEcKind
) VALUES (
  'ms00006',  -- ← Step 2 결과를 참고하여 중복되지 않는 번호로 설정
  'd',
  'godomall',
  'nutritionInfo',
  '영양 정보 관리',
  '/goods/nutrition_info_list',
  3,
  'godo00085',  -- 본사(d) 추가상품 관리 메뉴
  100,
  'y',
  'a'
);
