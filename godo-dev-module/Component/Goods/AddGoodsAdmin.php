<?php

namespace Component\Goods;

use Component\Storage\Storage;
use Component\Validator\Validator;
use Exception;

/**
 * 추가 상품 관련 관리자 클래스 커스텀
 * @author Conan Kim <kmakugo@gmail.com>
 */
class AddGoodsAdmin extends \Bundle\Component\Goods\AddGoodsAdmin
{
    // 이미지 저장 경로
    private $imagePath;

    /**
     * saveInfoAddGoods
     *
     * @param $arrData
     * @return string
     * @throws Except
     */
    public function saveInfoAddGoods($arrData)
    {
        $arrData = $this->handleSubImageData($arrData);

        return parent::saveInfoAddGoods($arrData);
    }

    /**
     * sub image file data handling
     * @param array $arrData
     * @return $arrData
     */
    private function handleSubImageData($arrData) {

        // 추가상품명 체크
        if (Validator::required(gd_isset($arrData['goodsNm'])) === false) {
            throw new \Exception(__('추가상품명은 필수 항목입니다.'), 500);
        }

        // addGoodsNo 처리
        if ($arrData['mode'] == 'register' || $arrData['mode'] == 'register_ajax') {
            $arrData['addGoodsNo'] = $this->getNewAddGoodsno();
        } else {
            // addGoodsNo 체크
            if (Validator::required(gd_isset($arrData['addGoodsNo'])) === false) {
                throw new \Exception(__('추가상품번호은 필수 항목입니다.'), 500);
            }
        }
        $this->goodsNo = $arrData['addGoodsNo'];

        if (empty($arrData['imagePath'])) {
            $this->imagePath = $arrData['imagePath'] = DIR_ADDGOODS_IMAGE . $arrData['addGoodsNo'] . '/';
        } else {
            $this->imagePath = $arrData['imagePath'];
        }

        if ($arrData['subImgData'] && $arrData['imageStorage'] != 'url') {
            if (gd_file_uploadable($arrData['subImgData'], 'image')) {
                $imageExt = strrchr( $arrData['subImgData']['name'], '.');
                $saveImageName =  $arrData['addGoodsNo'].'_sub_'.rand(1,100) .  $imageExt; 
                $targetImageFile = $this->imagePath . $saveImageName;
                $tmpImageFile = $arrData['subImgData']['tmp_name'];

                Storage::disk(Storage::PATH_CODE_ADD_GOODS,$arrData['imageStorage'])->upload($tmpImageFile,$targetImageFile);

                $arrData['subImageNm'] = $saveImageName;

                // 계정용량 갱신 - 추가상품
                gd_set_du('add_goods');
            }
        }

        if ($arrData['subImageDelFl'] === 'y' && $arrData['subImageNm'] && $arrData['imageStorage'] != 'url' && $arrData['imagePath']) {
            Storage::disk(Storage::PATH_CODE_ADD_GOODS,$arrData['imageStorage'])->delete($arrData['imagePath'] . $arrData['subImageNm']);
            // 계정용량 갱신 - 추가상품
            gd_set_du('add_goods');
        }

        if ($arrData['subImageDelFl'] == 'y') $arrData['subImageNm'] = '';

        return $arrData;
    }

     /**
     * Override getNewAddGoodsno
     */
    private function getNewAddGoodsno()
    {
        $data = $this->getInfoAddGoods(null, 'if(max(addGoodsNo) > 0, (max(addGoodsNo) + 1), ' . DEFAULT_CODE_ADD_GOODSNO . ') as newAddGoodsNo');
        return $data['newAddGoodsNo'];
    }
}
