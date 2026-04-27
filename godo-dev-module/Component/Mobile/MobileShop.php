<?php

namespace Component\Mobile;

/**
 * 모바일샵 설정 - 커스텀 모바일 페이지 리디렉트 추가
 */
class MobileShop extends \Bundle\Component\Mobile\MobileShop
{
    /**
     * 커스텀 모바일 페이지 목록
     * 프레임워크 app.mobilepagelist에 없는 커스텀 페이지를 여기에 추가
     */
    private $customMobilePages = [
        ['page' => 'diet_quiz.php',         'folder' => 'guide'],
        ['page' => 'diet_consultation.php', 'folder' => 'guide'],
        ['page' => 'brand_hall.php',        'folder' => 'guide'],
        ['page' => 'list.php',              'folder' => 'magazine'],
        ['page' => 'view.php',              'folder' => 'magazine'],
    ];

    /**
     * 모바일 페이지 리스트 - 커스텀 페이지 포함
     *
     * @param string $pageNm   페이지 이름
     * @param string $folderNm 폴더명
     *
     * @return boolean 모바일 페이지 있는지의 여부
     */
    public function mobilePageList($pageNm, $folderNm)
    {
        foreach ($this->customMobilePages as $val) {
            if ($val['page'] == $pageNm && $val['folder'] == $folderNm) {
                return true;
            }
        }

        return parent::mobilePageList($pageNm, $folderNm);
    }
}
