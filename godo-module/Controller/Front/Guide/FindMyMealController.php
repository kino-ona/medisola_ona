<?php
namespace Controller\Front\Guide;

use Globals;
use Session;
use Response;
use Request;

/**
 * 테스트용
 */
class FindMyMealController extends \Controller\Front\Controller
{
    /**
     * {@inheritdoc}
     */
    public function index()
    {

        $menuTree = $this->getData('menuTree');

        $this->setData(array(
            "categories" => [
                [
                    'id' => 'health_care',
                    'name' => [
                        'title' => $this->findValueByIds($menuTree, 'text', 'health_care')['find_my_meal'],
                        'description' => [
                            '건강할 때 시작하는',
                            '<strong>PREVENTIVE</strong> NUTRITION CARE'
                        ]
                    ],
                    'description' => [
                        '한국인에 필요한 영양 원리를 기반으로',
                        '생애주기에 따른 맞춤형 식단을 제안해요.',
                        '영양이 강조된 뉴트리션 케어와 목적달성을 위한 챌린지케어를 도전해보세요.'
                    ],
                    'tags' => ['냉동'],
                    'image' => PATH_SKIN . 'img/guide/find_my_meal/health_care_background.png',
                    'meals' => [
                        [
                            'title' => 'Nutritional Care',
                            'items' => [
                                [
                                    'id' => 'low_sugar_diet',
                                    'title' => $this->findValueByIds($menuTree, 'text', 'health_care', 'low_sugar_diet')['find_my_meal'],
                                    'description' => [
                                        '<strong>평균 당류 2g!</strong> 당은 낮추고',
                                        '메디쏠라만의 영양원리를 담은 저당관리 식단'
                                    ],
                                    'img' => PATH_SKIN . 'img/guide/find_my_meal/low_sugar_image.png',
                                    'url' => $this->findValueByIds($menuTree, 'url', 'health_care', 'low_sugar_diet'),
                                ],
                                [
                                    'id' => 'protein_20',
                                    'title' => $this->findValueByIds($menuTree, 'text', 'health_care', 'protein_20')['find_my_meal'],
                                    'description' => [
                                        '한국인에 꼭 필요한 영양 설계는 기본,',
                                        '단백질 함량까지 20g으로 맞춘 고단백 식단'
                                    ],
                                    'img' => PATH_SKIN . 'img/guide/find_my_meal/protein_20_image.png',
                                    'url' => $this->findValueByIds($menuTree, 'url', 'health_care', 'protein_20'),
                                ],
                                [
                                    'id' => 'sodium_650',
                                    'title' => $this->findValueByIds($menuTree, 'text', 'health_care', 'sodium_650')['find_my_meal'],
                                    'description' => [
                                        '<strong>평균 나트륨 650mg 이하</strong>로 칼로리와',
                                        '필수지방산 비율을 생각해 영양 설계한 식단'
                                    ],
                                    'img' => PATH_SKIN . 'img/guide/find_my_meal/sodium_650_image.png',
                                    'url' => $this->findValueByIds($menuTree, 'url', 'health_care', 'sodium_650'),
                                ],
                                [
                                    'id' => 'low_cholesterol',
                                    'title' => $this->findValueByIds($menuTree, 'text', 'health_care', 'low_cholesterol')['find_my_meal'],
                                    'description' => [
                                        '<strong>저콜레스테롤, 저포화지방, 저당</strong> 법적 기준 충족 식단'
                                    ],
                                    'img' => PATH_SKIN . 'img/guide/find_my_meal/low_cholesterol_image.png',
                                    'url' => $this->findValueByIds($menuTree, 'url', 'health_care', 'low_cholesterol'),
                                ]
                            ]
                        ],
                        [
                            'title' => 'Challenge Care',
                            'items' => [
                                [
                                    'id' => 'slow_aging',
                                    'title' => $this->findValueByIds($menuTree, 'text', 'health_care', 'slow_aging')['find_my_meal'],
                                    'description' => [
                                        '한국인을 위한 한국형 지중해식단을 기반으로 한',
                                        '신체 나이를  늦추기 위한 챌린지 <strong>슬로우에이징 식단</strong>'
                                    ],
                                    'img' => PATH_SKIN . 'img/guide/find_my_meal/slow_aging_image.png',
                                    'url' => $this->findValueByIds($menuTree, 'url', 'health_care', 'slow_aging'),
                                ],
                                [
                                    'id' => 'parents_care',
                                    'title' => $this->findValueByIds($menuTree, 'text', 'health_care', 'parents_care')['find_my_meal'],
                                    'description' => [
                                        '오메가-3에 오메가 3 : 6비율까지 맞춘',
                                        '<strong>액티브 시니어</strong>를 위한 케어식단'
                                    ],
                                    'img' => PATH_SKIN . 'img/guide/find_my_meal/parents_care_image.png',
                                    'url' => $this->findValueByIds($menuTree, 'url', 'health_care', 'parents_care'),
                                ],
                                [
                                    'id' => 'new_parents',
                                    'title' => $this->findValueByIds($menuTree, 'text', 'health_care', 'new_parents')['find_my_meal'],
                                    'description' => [
                                        '균형잡힌 영양에 칼로리까지 고려한',
                                        '건강한 임신과 출산을 위한 예비 부모를 위한 식단'
                                    ],
                                    'img' => PATH_SKIN . 'img/guide/find_my_meal/soon_to_be_parents_image.png',
                                    'url' => $this->findValueByIds($menuTree, 'url', 'health_care', 'new_parents'),
                                ],
                                [
                                    'id' => 'running_crew',
                                    'title' => $this->findValueByIds($menuTree, 'text', 'health_care', 'running_crew')['find_my_meal'],
                                    'description' => [
                                        '운동 전/후 <strong>회복과 에너지 보충</strong>을 위한',
                                        '최상의 러닝을 위한 영양루틴 식단'
                                    ],
                                    'img' => PATH_SKIN . 'img/guide/find_my_meal/running_crew_image.png',
                                    'url' => $this->findValueByIds($menuTree, 'url', 'health_care', 'running_crew'),
                                ],
                            ]
                        ]
                    ]
                ],
                [
                    'id' => 'disease_care',
                    'name' => [
                        'title' => $this->findValueByIds($menuTree, 'text', 'disease_care')['find_my_meal'],
                        'description' => [
                            '건강이 걱정될 때 관리하는',
                            'MEDICAL NUTRITION CARE'
                        ]
                    ],
                    'description' => [
                        '식품의약품 안전처의 기준을 기반으로하는 허가된 permission care와',
                        '메디쏠라 임상을 통해 증명된 proven care',
                        '건강이 걱정될 때 믿을 수 있는 전문가들이 검증한 식단을 선택하세요.'
                    ],
                    'tags' => ['냉동'],
                    'image' => PATH_SKIN . 'img/guide/find_my_meal/disease_care_background.png',
                    'meals' => [
                        [
                            'title' => 'Proven Care',
                            'items' => [
                                [
                                    'id' => 'kidney_care',
                                    'title' => $this->findValueByIds($menuTree, 'text', 'disease_care', 'kidney_care')['find_my_meal'],
                                    'description' => [
                                        '미리 챙기는 신장 건강 식단',
                                        '국내 신장질환자 대상 식단 <strong>임상중재 연구 증명</strong>'
                                    ],
                                    'img' => PATH_SKIN . 'img/guide/find_my_meal/good_for_kidney_image.png',
                                    'url' => $this->findValueByIds($menuTree, 'url', 'disease_care', 'kidney_care')
                                ],
                                [
                                    'id' => 'pink_ribbon',
                                    'title' => $this->findValueByIds($menuTree, 'text', 'disease_care', 'pink_ribbon')['find_my_meal'],
                                    'description' => [
                                        '유방암 치료 후 재발 및 전이 방지 식단',
                                        '국내 유방암환자 대상 식단 <strong>임상중재 연구 증명</strong>'
                                    ],
                                    'img' => PATH_SKIN . 'img/guide/find_my_meal/pink_ribbon_image.png',
                                    'url' => $this->findValueByIds($menuTree, 'url', 'disease_care', 'pink_ribbon')
                                ],
                            ],
                        ],
                        [
                            'title' => 'Permission Care',
                            'items' => [
                                [
                                    'id' => 'diabetes_care',
                                    'title' => $this->findValueByIds($menuTree, 'text', 'disease_care', 'diabetes_care')['find_my_meal'],
                                    'description' => [
                                        '식품의약품안전처의 기준에 따라',
                                        '당뇨환자용으로 인증 받은 <strong>특수의료용도식품</strong>'
                                    ],
                                    'img' => PATH_SKIN . 'img/guide/find_my_meal/diabetes_care_image.png',
                                    'url' => $this->findValueByIds($menuTree, 'url', 'disease_care', 'diabetes_care')
                                ],
                                [
                                    'id' => 'kidney_dialysis',
                                    'title' => $this->findValueByIds($menuTree, 'text', 'disease_care', 'kidney_dialysis')['find_my_meal'],
                                    'description' => [
                                        '식품의약품안전처의 기준에 따라',
                                        '신장질환자용으로 인증 받은 <strong>특수의료용도식품</strong>'
                                    ],
                                    'img' => PATH_SKIN . 'img/guide/find_my_meal/kidney_care_image.png',
                                    'url' => $this->findValueByIds($menuTree, 'url', 'disease_care', 'kidney_dialysis')
                                ],
                                [
                                    'id' => 'cancer_care',
                                    'title' => $this->findValueByIds($menuTree, 'text', 'disease_care', 'cancer_care')['find_my_meal'],
                                    'description' => [
                                        '식품의약품안전처의 기준에 따라',
                                        '암환자용으로 인증 받은 <strong>특수의료용도식품</strong>'
                                    ],
                                    'img' => PATH_SKIN . 'img/guide/find_my_meal/cancer_care_image.png',
                                    'url' => $this->findValueByIds($menuTree, 'url', 'disease_care', 'cancer_care')
                                ],
                            ]
                        ]
                    ]
                ],
                [
                    'id' => 'morning_delivery',
                    'name' => [
                        'title' => $this->findValueByIds($menuTree, 'text', 'morning_delivery')['find_my_meal'],
                    ],
                    'description' => [
                        '메디쏠라의 프리미엄 식단',
                        '주문 후 쉐프들이 직접 조리하여 신선하고 프리미엄한 맛을 즐길 수 있습니다.',
                        '*서울, 경기 지역만 신선하게 배송 가능합니다. 양해 부탁드려요.'
                    ],
                    'tags' => ['냉장', "주 2회 배송"],
                    'image' => PATH_SKIN . 'img/guide/find_my_meal/morning_delivery_background.png',
                    'meals' => [
                        [
                            'title' => null,
                            'items' => [
                                [
                                    'id' => 'nutrition_care',
                                    'title' => $this->findValueByIds($menuTree, 'text', 'morning_delivery', 'nutrition_care')['find_my_meal'],
                                    'description' => [
                                        '매달 영양사가 새로 구성하는 영양 맞춤형',
                                        '매주 월 / 목요일 2회 새벽마다 집앞에서 만나는',
                                        '메디쏠라 시그니처 식단'
                                    ],
                                    'img' => PATH_SKIN . 'img/guide/find_my_meal/nutrition_care_image.png',
                                    'url' => $this->findValueByIds($menuTree, 'url', 'morning_delivery', 'nutrition_care')
                                ]
                            ],
                        ],
                        [
                            'title' => null,
                            'items' => [
                                [
                                    'id' => 'diabetes_care',
                                    'title' => $this->findValueByIds($menuTree, 'text', 'morning_delivery', 'diabetes_care')['find_my_meal'],
                                    'description' => [
                                        '식품의약품안전처의 기준에 따라',
                                        '당뇨환자용으로 인증 받은 <strong>특수의료용도식품</strong>'
                                    ],
                                    'img' => PATH_SKIN . 'img/guide/find_my_meal/for_diabetes_patients_image.png',
                                    'url' => $this->findValueByIds($menuTree, 'url', 'morning_delivery', 'diabetes_care')
                                ],
                            ]
                        ]
                    ]
                ],
                [
                    'id' => 'supplements',
                    'name' => [
                        'title' => $this->findValueByIds($menuTree, 'text', 'supplements')['find_my_meal'],
                    ],
                    'description' => [
                        '부족한 영양은 채우고, 간편하게 하루 필요한 영양을',
                        '섭취할 수 있는 다양한 건강 보충 메뉴를 제안해요.'
                    ],
                    'tags' => ['냉동'],
                    'image' => PATH_SKIN . 'img/guide/find_my_meal/supplements_background.png',
                    'meals' => [
                        [
                            'title' => null,
                            'items' => [
                                [
                                    'id' => 'soup',
                                    'title' => '마녀스프',
                                    'description' => [
                                        '각종 야채와 단백질을 더해 <strong>낮은 칼로리,</strong>',
                                        '<strong>높은 식이섬유 함량</strong>을 자랑하는 마녀스프'
                                    ],
                                    'img' => PATH_SKIN . 'img/guide/find_my_meal/witches_soup_image.png',
                                    'url' => '/goods/goods_view.php?goodsNo=1000000293'
                                ],
                                [
                                    'id' => 'low_sugar_sides',
                                    'title' => $this->findValueByIds($menuTree, 'text', 'supplements', 'chickpea_rice_and_sides')['find_my_meal']['sides'],
                                    'description' => [
                                        '<strong>불필요한 당은 최소화</strong>하고',
                                        '간편하게 챙기는 저당 단품 요리'
                                    ],
                                    'img' => PATH_SKIN . 'img/guide/find_my_meal/low_sugar_side_dishes_image.png',
                                    'url' => $this->findValueByIds($menuTree, 'url', 'supplements', 'chickpea_rice_and_sides')
                                ],
                                [
                                    'id' => 'chickpea_rice',
                                    'title' => $this->findValueByIds($menuTree, 'text', 'supplements', 'chickpea_rice_and_sides')['find_my_meal']['rice'],
                                    'description' => [
                                        '최상급 엑스트라버진 올리브오일을 더해',
                                        '<strong>오메가-3 지방산이 풍부</strong>한 칙피영양밥'
                                    ],
                                    'img' => PATH_SKIN . 'img/guide/find_my_meal/nutritious_rice_image.png',
                                    'url' => $this->findValueByIds($menuTree, 'url', 'supplements', 'chickpea_rice_and_sides')
                                ]
                            ],
                        ],
                        [
                            'title' => null,
                            'items' => [
                                [
                                    'id' => 'warm_salad',
                                    'title' => $this->findValueByIds($menuTree, 'text', 'supplements', 'warm_salad')['find_my_meal'],
                                    'description' => [
                                        '전자레인지에서 냉동상태 그대로',
                                        '따뜻하게 즐기는 <strong>쟁여두는 샐러드</strong>'
                                    ],
                                    'img' => PATH_SKIN . 'img/guide/find_my_meal/warm_salad_image.png',
                                    'url' => $this->findValueByIds($menuTree, 'url', 'supplements', 'warm_salad')
                                ],
                                [
                                    'id' => 'protein_3',
                                    'title' => $this->findValueByIds($menuTree, 'text', 'supplements', 'protein_3')['find_my_meal'],
                                    'description' => [
                                        '단백질 보충이 필요한 순간',
                                        '간편하게 즐기는 <strong>고단백, 저당</strong> 영양 간식'
                                    ],
                                    'img' => PATH_SKIN . 'img/guide/find_my_meal/protein3_side_image.png',
                                    'url' => $this->findValueByIds($menuTree, 'url', 'supplements', 'protein_3')
                                ],
                                [
                                    'id' => 'bakery',
                                    'title' => $this->findValueByIds($menuTree, 'text', 'supplements', 'bakery')['find_my_meal'],
                                    'description' => [
                                        '최적의 황금 영양비율로 설계한',
                                        '<strong>고단백 & 저트랜스지방</strong> 샌드위치'
                                    ],
                                    'img' => PATH_SKIN . 'img/guide/find_my_meal/side_walnut_bread_image.png',
                                    'url' => $this->findValueByIds($menuTree, 'url', 'supplements', 'bakery')
                                ],
                            ]
                        ]
                    ]
                ],
            ],
        ));
    }

    protected function findValueByIds(array $menuTree, string $key, string ...$ids) {
        $currentId = array_shift($ids);
        if ($currentId === null) {
            return null;
        }

        foreach ($menuTree as $item) {
            if ($item['id'] === $currentId) {
                if (empty($ids)) {
                    return $item[$key];
                }

                if (!empty($item['children'])) {
                    $result = $this->findValueByIds($item['children'], $key, ...$ids);
                    if ($result !== null) {
                        return $result;
                    }
                }
            }
        }

        return null;
    }

    protected function findUrlByIds(array $menuTree, string ...$ids) {
        $currentId = array_shift($ids);
        if ($currentId === null) {
            return null;
        }

        foreach ($menuTree as $item) {
            if ($item['id'] === $currentId) {
                if (empty($ids)) {
                    return $item['url'];
                }

                if (!empty($item['children'])) {
                    $result = $this->findUrlByIds($item['children'], ...$ids);
                    if ($result !== null) {
                        return $result;
                    }
                }
            }
        }

        return null;
    }

    protected function findUrlByTexts(array $menuTree, string ...$texts) {
        $currentText = array_shift($texts);
        if ($currentText === null) {
            return null;
        }

        foreach ($menuTree as $item) {
            if ($item['text'] === $currentText) {
                if (empty($texts)) {
                    return $item['url'];
                }
                
                if (!empty($item['children'])) {
                    $result = $this->findUrlByTexts($item['children'], ...$texts);
                    if ($result !== null) {
                        return $result;
                    }
                }
            }
        }
        
        return null;
    }
}