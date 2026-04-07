-- category 필드 확장: VARCHAR(50) → VARCHAR(255)
-- 복수 카테고리 지원 (한글 직접 저장, 콤마 구분: 해산물,육류,샐러드)

ALTER TABLE es_addGoods
  MODIFY COLUMN category VARCHAR(255) DEFAULT NULL
  COMMENT '카테고리 (복수 가능, 콤마 구분: 해산물,육류,샐러드)';

-- 기존 영문 카테고리 데이터를 한글로 변환
UPDATE es_addGoods SET category = '해산물' WHERE category = 'seafood';
UPDATE es_addGoods SET category = '육류' WHERE category = 'meat';
UPDATE es_addGoods SET category = '식단백' WHERE category = 'plant-protein';
UPDATE es_addGoods SET category = '샐러드' WHERE category = 'salad';
UPDATE es_addGoods SET category = '국/찌개' WHERE category = 'soup';
UPDATE es_addGoods SET category = '반찬' WHERE category = 'side';
UPDATE es_addGoods SET category = '간식' WHERE category = 'snack';
UPDATE es_addGoods SET category = '음료' WHERE category = 'drink';

-- 콤마 구분된 복수 카테고리 변환 (예: seafood,meat → 해산물,육류)
UPDATE es_addGoods
  SET category = REPLACE(
    REPLACE(
      REPLACE(
        REPLACE(
          REPLACE(
            REPLACE(
              REPLACE(
                REPLACE(category,
                  'seafood', '해산물'),
                'meat', '육류'),
              'plant-protein', '식단백'),
            'salad', '샐러드'),
          'soup', '국/찌개'),
        'side', '반찬'),
      'snack', '간식'),
    'drink', '음료')
  WHERE category LIKE '%,%';

-- 기존 인덱스는 유지 (성능 테스트 후 필요시 재생성)
-- DROP INDEX idx_category ON es_addGoods;
-- CREATE INDEX idx_category ON es_addGoods (category(100));
