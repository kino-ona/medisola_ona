<?php
namespace Controller\Front;

/**
 * 사용자들이 모든 컨트롤러에 공통으로 사용할 수 있는 컨트롤러 Class
 * 컨트롤러에서 지원하는 메소드들을 사용할 수 있습니다.
 */
class CommonController
{
    protected $requestUri;
    protected $menuTree;

    public function __construct()
    {
        $this->requestUri = \Request::server()->get('REQUEST_URI');
        $this->menuTree = [
            (\Request::getRemoteAddress() == '183.98.27.142') ? [
                'id' => 'find_my_meal',
                'text' => [
                    'default' => '맞춤 식단 플랜'
                ],
                'url' => '/guide/diet_quiz.php?goodsNo=1000000445',
                'active' => false,
                'new' => true,
                'columns' => 0,
                'children' => null
            ] : null,
            // [
            //     'id' => 'morning_delivery',
            //     'text' => [
            //         'default' => '새벽배송',
            //         'find_my_meal' => '새벽배송'
            //     ],
            //     'url' => '/goods/goods_list.php?cateCd=037',
            //     'active' => false,
            //     'new' => false,
            //     'columns' => 1,
            //     'children' => [
            //         [
            //             'id' => 'nutrition_care',
            //             'text' => [
            //                 'default' => '프리미엄 영양케어',
            //                 'find_my_meal' => '영양케어 식단'
            //             ],
            //             'url' => '/goods/goods_view.php?goodsNo=1000000289',
            //             'active' => false,
            //             'new' => false,
            //         ],
            //         [
            //             'id' => 'diabetes_care',
            //             'text' => [
            //                 'default' => '프리미엄 당뇨케어',
            //                 'find_my_meal' => '당뇨케어 식단'
            //             ],
            //             'url' => '/goods/goods_view.php?goodsNo=1000000290',
            //             'active' => false,
            //             'new' => false,
            //         ],
            //         [
            //             'id' => 'grain_300',
            //             'text' => [
            //                 'default' => '그레인300'
            //             ],
            //             'url' => '/goods/goods_view.php?goodsNo=1000000291',
            //             'active' => false,
            //             'new' => false,
            //         ]
            //     ]
            // ],
            // (strtotime('2025-05-19 00:00:00') <= time() && time() <= strtotime('2025-07-31 23:59:59')) ? [
            //     'id' => 'balance_fit',
            //     'text' => [
            //         'default' => '밸런스FIT'
            //     ],
            //     'url' => '/goods/goods_list.php?cateCd=030',
            //     'active' => false,
            //     'new' => true,
            //     'columns' => 0,
            //     'children' => null
            // ] : null,
            [
                'id' => 'health_care',
                'text' => [
                    'default' => '건강케어',
                    'find_my_meal' => '건강케어'
                ],
                'url' => '/goods/goods_list.php?cateCd=038',
                'active' => false,
                'new' => false,
                'rows' => 6,
                'columns' => 2,
                'width' => 272,
                'children' => [
                    [
                        'id' => 'low_sugar_diet',
                        'text' => [
                            'default' => '한국형지중해식',
                            'find_my_meal' => '한국형 지중해식'
                        ],
                        'url' => '/goods/goods_view.php?goodsNo=1000000371',
                        'active' => false,
                        'new' => false,
                    ],
                    [
                        'id' => 'protein_20',
                        'text' => [
                            'default' => '고단백저당',
                            'find_my_meal' => '고단백 20'
                        ],
                        'url' => '/goods/goods_view.php?goodsNo=1000000372',
                        'active' => false,
                        'new' => false,
                    ],
                    [
                        'id' => 'sodium_650',
                        'text' => [
                            'default' => '나트륨650',
                            'find_my_meal' => '나트륨 650'
                        ],
                        'url' => '/goods/goods_view.php?goodsNo=1000000373',
                        'active' => false,
                        'new' => false,
                    ],
                    [
                        'id' => 'low_cholesterol',
                        'text' => [
                            'default' => '저콜레스테롤',
                            'find_my_meal' => '저콜레스테롤'
                        ],
                        'url' => '/goods/goods_view.php?goodsNo=1000000374',
                        'active' => false,
                        'new' => false,
                    ],
                    [
                        'id' => 'slow_aging',
                        'text' => [
                            'default' => '저속노화',
                            'find_my_meal' => '저속노화'
                        ],
                        'url' => '/goods/goods_view.php?goodsNo=1000000375',
                        'active' => false,
                        'new' => false,
                    ],
                    [
                        'id' => 'parents_care',
                        'text' => [
                            'default' => '부모님케어',
                            'find_my_meal' => '부모님케어'
                        ],
                        'url' => '/goods/goods_view.php?goodsNo=1000000376',
                        'active' => false,
                        'new' => false,
                    ],
                    [
                        'id' => 'new_parents',
                        'text' => [
                            'default' => '예비부모',
                            'find_my_meal' => '예비부모'
                        ],
                        'url' => '/goods/goods_view.php?goodsNo=1000000377',
                        'active' => false,
                        'new' => false,
                    ],
                    [
                        'id' => 'old_friendly',
                        'text' => [
                            'default' => '고령친화',
                            'find_my_meal' => '고령친화'
                        ],
                        'url' => '/goods/goods_view.php?goodsNo=1000000447',
                        'active' => false,
                        'new' => false,
                    ],
                    [
                        'id' => 'volume_up_550',
                        'text' => [
                            'default' => '볼륨업550',
                            'find_my_meal' => '볼륨업550',
                        ],
                        'url' => '/goods/goods_view.php?goodsNo=1000000365',
                        'active' => false,
                        'new' => false,
                    ],
                    [
                        'id' => 'running_crew',
                        'text' => [
                            'default' => '러닝크루550',
                            'find_my_meal' => '러닝크루550'
                        ],
                        'url' => '/goods/goods_view.php?goodsNo=1000000378',
                        'active' => false,
                        'new' => false,
                    ],
                    [
                        'id' => 'focus_550',
                        'text' => [
                            'default' => '포커스550',
                            'find_my_meal' => '포커스550',
                        ],
                        'url' => '/goods/goods_view.php?goodsNo=1000000407',
                        'active' => false,
                        'new' => false,
                    ],
                ]
            ],
            [
                'id' => 'disease_care',
                'text' => [
                    'default' => '질환케어',
                    'find_my_meal' => '질환케어'
                ],
                'url' => '/goods/goods_list.php?cateCd=039',
                'active' => false,
                'new' => false,
                'columns' => 1,
                'children' => [
                    [
                        'id' => 'diabetes_care',
                        'text' => [
                            'default' => '당뇨케어',
                            'find_my_meal' => '당뇨케어'
                        ],
                        'url' => '/goods/goods_view.php?goodsNo=1000000379',
                        'active' => false,
                        'new' => false,
                    ],
                    [
                        'id' => 'pregnant_diabetes',
                        'text' => [
                            'default' => '임신성당뇨',
                            'find_my_meal' => '임신성당뇨'
                        ],
                        'url' => '/goods/goods_view.php?goodsNo=1000000422',
                        'active' => false,
                        'new' => true,
                    ],
                    [
                        'id' => 'kidney_dialysis',
                        'text' => [
                            'default' => '신장케어',
                            'find_my_meal' => '신장케어'
                        ],
                        'url' => '/goods/goods_view.php?goodsNo=1000000380',
                        'active' => false,
                        'new' => false,
                    ],
                    [
                        'id' => 'kidney_care',
                        'text' => [
                            'default' => '신장튼튼',
                            'find_my_meal' => '신장튼튼'
                        ],
                        'url' => '/goods/goods_view.php?goodsNo=1000000381',
                        'active' => false,
                        'new' => false,
                    ],
                    [
                        'id' => 'cancer_care',
                        'text' => [
                            'default' => '암케어',
                            'find_my_meal' => '암케어'
                        ],
                        'url' => '/goods/goods_view.php?goodsNo=1000000382',
                        'active' => false,
                        'new' => false,
                    ],
                    [
                        'id' => 'pink_ribbon',
                        'text' => [
                            'default' => '핑크리본',
                            'find_my_meal' => '핑크리본'
                        ],
                        'url' => '/goods/goods_view.php?goodsNo=1000000383',
                        'active' => false,
                        'new' => false,
                    ]
                ]
            ],
            [
                'id' => 'supplements',
                'text' => [
                    'default' => '보충케어',
                    'find_my_meal' => '보충메뉴'
                ],
                'url' => '/goods/goods_list.php?cateCd=040',
                'active' => false,
                'new' => false,
                'rows' => 4,
                'columns' => 2,
                'width' => 230,
                'children' => [
                    [
                        'id' => 'porridge',
                        'text' => [
                            'default' => '523영양죽',
                            'find_my_meal' => null
                        ],
                        'url' => '/goods/goods_view.php?goodsNo=1000000430',
                        'active' => false,
                        'new' => true,
                    ],
                    [
                        'id' => 'tea_sparkling',
                        'text' => [
                            'default' => '523쉐이크',
                            'find_my_meal' => null
                        ],
                        'url' => '/goods/goods_view.php?goodsNo=1000000405',
                        'active' => false,
                        'new' => false,
                    ],
                    [
                        'id' => 'soup',
                        'text' => [
                            'default' => '마녀스프',
                            'find_my_meal' => '마녀스프'
                        ],
                        'url' => '/goods/goods_list.php?cateCd=040003',
                        'active' => false,
                        'new' => false,
                    ],
                    [
                        'id' => 'chickpea_rice_and_sides',
                        'text' => [
                            'default' => '저당반찬',
                            'find_my_meal' => '저당반찬'
                        ],
                        'url' => '/goods/goods_list.php?cateCd=040005',
                        'active' => false,
                        'new' => false,
                    ],
                    [
                        'id' => 'jango_gui',
                        'text' => [
                            'default' => '장어구이',
                            'find_my_meal' => null
                        ],
                        'url' => '/goods/goods_view.php?goodsNo=1000000225',
                        'active' => false,
                        'new' => false,
                    ],
                    // [
                    //     'id' => 'chickpea_rice_and_sides',
                    //     'text' => [
                    //         'default' => '칙피영양밥',
                    //         'find_my_meal' => null
                    //     ],
                    //     'url' => '/goods/goods_view.php?goodsNo=1000000341',
                    //     'active' => false,
                    //     'new' => false,
                    // ],
                    // [
                    //     'id' => 'warm_salad',
                    //     'text' => [
                    //         'default' => '웜샐러드',
                    //         'find_my_meal' => '웜 샐러드'
                    //     ],
                    //     'url' => '/goods/goods_list.php?cateCd=040001',
                    //     'active' => false,
                    //     'new' => false,
                    // ],
                    [
                        'id' => 'protein_3',
                        'text' => [
                            'default' => '프로틴3',
                            'find_my_meal' => '프로틴 3'
                        ],
                        'url' => '/goods/goods_view.php?goodsNo=1000000294',
                        'active' => false,
                        'new' => false,
                    ],
                    [
                        'id' => 'bakery',
                        'text' => [
                            'default' => '쌀베이글',
                            'find_my_meal' => '쌀베이글'
                        ],
                        'url' => '/goods/goods_view.php?goodsNo=1000000342',
                        'active' => false,
                        'new' => false,
                    ],
                ]
            ],
            [
                'id' => 'events',
                'text' => [
                    'default' => '이벤트'
                ],
                'url' => '/board/list.php?bdId=event&period=current',
                'active' => false,
                'new' => false,
                'columns' => 0,
                'children' => null
            ],
            [
                'id' => 'review_and_magazine',
                'text' => [
                    'default' => '스토리'
                ],
                'url' => '/board/plus_review_goods.php?bdId=goodsreview&sno=287',
                'active' => false,
                'new' => false,
                'columns' => 1,
                'children' => [
                    [
                        'id' => 'review',
                        'text' => [
                            'default' => '리뷰'
                        ],
                        'url' => '/board/plus_review_goods.php?bdId=goodsreview&sno=287',
                        'active' => false,
                        'new' => false,
                    ],
                    [
                        'id' => 'medisola_news',
                        'text' => [
                            'default' => '케어 스토리'
                        ],
                        'url' => '/board/list.php?bdId=solanews',
                        'active' => false,
                        'new' => false,
                    ],
                    [
                        'id' => 'medisola_magazine',
                        'text' => [
                            'default' => '푸드케어 레터'
                        ],
                        'url' => '/magazine/list.php',
                        'active' => false,
                        'new' => false,
                    ]
                ]
            ],
            [
                'id' => 'customer_service',
                'text' => [
                    'default' => '고객센터'
                ],
                'url' => '/board/list.php?bdId=notice',
                'active' => false,
                'new' => false,
                'columns' => 1,
                'children' => [
                    [
                        'id' => 'notice',
                        'text' => [
                            'default' => '공지사항'
                        ],
                        'url' => '/board/list.php?bdId=notice',
                        'active' => false,
                        'new' => false,
                    ],
                    [
                        'id' => 'monthly_meal_plan',
                        'text' => [
                            'default' => '이달의식단'
                        ],
                        'url' => '/board/list.php?bdId=calendar',
                        'active' => false,
                        'new' => false,
                    ],
                    [
                        'id' => 'solamate',
                        'text' => [
                            'default' => '메디쏠라앱'
                        ],
                        'url' => '/main/html.php?htmid=board/skin/meditalktalk/solamate',
                        'active' => false,
                        'new' => false,
                    ],
                    [
                        'id' => 'member_benefits',
                        'text' => [
                            'default' => '회원혜택'
                        ],
                        'url' => '/board/view.php?&bdId=members&sno=5',
                        'active' => false,
                        'new' => false,
                    ],
                    [
                        'id' => 'faq',
                        'text' => [
                            'default' => 'FAQ'
                        ],
                        'url' => '/service/faq.php',
                        'active' => false,
                        'new' => false,
                    ]
                ]
            ]
        ];

        $this->menuTree = array_filter($this->menuTree, function($item) {
            return $item !== null;
        });
    }
    
    public function index($controller)
    {
        // 커스텀 Meta 태그 설정
        if (strpos($this->requestUri, '/main') === 0) {
            $controller->setData('headerMeta', ['<meta name="robots" content="noindex">']);
        }
        
        // 정기결제 상품인 경우 연결된 일반 상품의 메뉴를 active로 표시
        $linkedGoodsNo = $this->getLinkedRegularGoodsNo();

        $this->menuTree = $this->setActiveMenuItems($this->menuTree, $linkedGoodsNo);
        $controller->setData('menuTree', $this->menuTree);

        // 웹앤모바일 정기결제 기능 추가 ================================================== START
        $obj = new \Component\Subscription\Subscription();
        if ($obj->applyFl) {
            $controller->setData('wmSubscription', true);
        }
        // 웹앤모바일 정기결제 기능 추가 ================================================== END


        if(\Request::getRemoteAddress() == '182.216.219.157') {
            $controller->setData('dev', true);
        }
    }

    /**
     * 정기결제 상품인 경우 연결된 일반 상품 번호를 조회
     *
     * @return int|null 연결된 일반 상품 번호 (없으면 null)
     */
    protected function getLinkedRegularGoodsNo()
    {
        // 상품 상세 페이지인지 확인
        if (strpos($this->requestUri, '/goods/goods_view.php') !== 0) {
            return null;
        }

        // goodsNo 파라미터 확인
        $goodsNo = \Request::get()->get('goodsNo');
        if (!$goodsNo) {
            return null;
        }

        // Subscription 컴포넌트 재사용 (안전한 parameterized query)
        $subscriptionObj = new \Component\Subscription\Subscription();
        return $subscriptionObj->getLinkedRegularGoodsNo($goodsNo);
    }

    protected function setActiveMenuItems(array $menuTree, $linkedGoodsNo = null): array
    {
        // 정기결제 상품이고 연결된 일반 상품이 있는 경우, 해당 상품의 URL로 메뉴 활성화
        $alternativeUri = $this->requestUri;
        if ($linkedGoodsNo) {
            $alternativeUri = '/goods/goods_view.php?goodsNo=' . $linkedGoodsNo;
        }

        return array_map(function($item) use ($alternativeUri) {
            $item['active'] = (strpos($this->requestUri, $item['url']) === 0) ||
                              (strpos($alternativeUri, $item['url']) === 0);

            if (!empty($item['children'])) {
                $item['children'] = array_map(function($child) use (&$item, $alternativeUri) {
                    $child['active'] = (strpos($this->requestUri, $child['url']) === 0) ||
                                       (strpos($alternativeUri, $child['url']) === 0);
                    $item['active'] = $item['active'] || $child['active'];
                    return $child;
                }, $item['children']);
            }

            return $item;
        }, $menuTree);
    }
}