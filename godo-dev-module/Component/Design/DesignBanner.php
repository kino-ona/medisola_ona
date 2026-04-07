<?php

namespace Component\Design;

use Component\Validator\Validator;
use Component\Database\DBTableField;
use Component\Page\Page;
use Component\Category\CategoryAdmin;
use Framework\Utility\ArrayUtils;
use Globals;
use Request;
use Message;
use UserFilePath;
use FileHandler;
use DirectoryIterator;

class DesignBanner extends \Bundle\Component\Design\DesignBanner
{
  /**
   * 움직이는 배너 정보 저장
   * @param array $postValue 저장할 정보
   * @return int sno
   * @throws \Exception
   */
  public function saveSliderBannerData(array $postValue)
  {
    // 기본 테이터 체크
    $dataCheck = true;
    if (empty($postValue['bannerDeviceType']) === true) {
      $dataCheck = false;
    }
    if (empty($postValue['skinName']) === true) {
      $dataCheck = false;
    }
    if (empty($postValue['bannerTitle']) === true) {
      $dataCheck = false;
    }
    if (empty($postValue['bannerUseFl']) === true) {
      $dataCheck = false;
    }
    if (empty($postValue['bannerSize']['width']) === true || empty($postValue['bannerSize']['height']) === true) {
      $dataCheck = false;
    }
    if ($postValue['mode'] === 'modifySliderBanner' && empty($postValue['sno']) === true) {
      $dataCheck = false;
    }

    if ($dataCheck === false) {
      throw new \Exception(sprintf(__('%s 항목이 잘못 되었습니다.'), '움직이는 배너 정보'));
    }

    if (empty($postValue['bannerPeriodOutputFl']) === true) {
      $postValue['bannerPeriodOutputFl'] = 'n';
    }

    // 배너 코드
    if (empty($postValue['bannerCode']) === true) {
      $tmpBannerCode = microtime(true);
      $postValue['bannerCode'] = \Encryptor::checksum($tmpBannerCode);
    }

    // 날짜 처리
    if ($postValue['bannerPeriodOutputFl'] === 'y') {
      $tmpDataS = explode(' ', $postValue['bannerPeriodSDateY']);
      $tmpDataE = explode(' ', $postValue['bannerPeriodEDateY']);
      $postValue['bannerPeriodSDate'] = $tmpDataS[0];
      $postValue['bannerPeriodSTime'] = $tmpDataS[1] . ':00';
      $postValue['bannerPeriodEDate'] = $tmpDataE[0];
      $postValue['bannerPeriodETime'] = $tmpDataE[1] . ':00';
    } else {
      $postValue['bannerPeriodSDate'] = '';
      $postValue['bannerPeriodSTime'] = '';
      $postValue['bannerPeriodEDate'] = '';
      $postValue['bannerPeriodETime'] = '';
    }
    unset($postValue['bannerPeriodSDateY'], $postValue['bannerPeriodSTimeY'], $postValue['bannerPeriodEDateY'], $postValue['bannerPeriodETimeY']);

    // 배너 설정
    $postValue['bannerSliderConf'] = json_encode($postValue['bannerSliderConf']);

    // 버튼 설정
    $bannerButtonConf['side']['useFl'] = gd_isset($postValue['sideButton']['useFl'], 'y');
    $bannerButtonConf['side']['activeColor'] = gd_isset($postValue['sideButton']['activeColor'], '#ffffff');
    $bannerButtonConf['page']['useFl'] = gd_isset($postValue['pageButton']['useFl'], 'y');
    $bannerButtonConf['page']['activeColor'] = gd_isset($postValue['pageButton']['activeColor'], '#ffffff');
    $bannerButtonConf['page']['inactiveColor'] = gd_isset($postValue['pageButton']['inactiveColor'], '#ffffff');
    $bannerButtonConf['page']['size'] = gd_isset($postValue['pageButton']['size'], '8');
    $bannerButtonConf['page']['radius'] = gd_isset($postValue['pageButton']['radius'], '100');
    $postValue['bannerButtonConf'] = json_encode($bannerButtonConf);

    // 배너 사이즈
    $postValue['bannerSize'] = json_encode($postValue['bannerSize']);

    // insert 인 경우 미리 저장
    if ($postValue['mode'] == 'registerSliderBanner') {
      $postValue['bannerInfo'] = '{}';
      $arrBind = $this->db->get_binding(DBTableField::tableDesignSliderBanner(), $postValue, 'insert');
      $this->db->set_insert_db(DB_DESIGN_SLIDER_BANNER, $arrBind['param'], $arrBind['bind'], 'y');
      $postValue['sno'] = $this->db->insert_id();
      unset($arrBind, $postValue['bannerInfo']);
    }

    // 배너 이미지 폴더
    if (empty($postValue['bannerFolder']) === true) {
      $postValue['bannerFolder'] = 'slider_' . $postValue['bannerCode'];
    }

    // 배너 이미지 경로 설정
    $checkBannerPath = UserFilePath::data('skin', $postValue['bannerDeviceType'], $postValue['skinName'], $this->bannerPathDefault, $postValue['bannerFolder']);

    // 폴더 생성
    if (FileHandler::isDirectory($checkBannerPath) === false) {
      $result = FileHandler::makeDirectory($checkBannerPath, 0707);
      if ($result !== true) {
        throw new \Exception(__('파일 저장중에 오류가 발생하여 실패되었습니다.'));
      }
    }

    // 배너 이미지 저장
    $imgKey = 0;
    foreach (Request::files()->get('bannerImageFile')['name'] as $fKey => $fVal) {
      if (Request::files()->get('bannerImageFile')['error'][$fKey] === '0' && Request::files()->get('bannerImageFile')['size'][$fKey] > 0) {
        // 새로운 이미지명 생성 (한글의 경우 문제가 생기는 부분이 있어서 이미지명을 전체적으로 변경함)
        $tmpExt = FileHandler::getFileInfo($fVal)->getExtension();
        $bannerImage = md5($fVal) . '_' . mt_rand(10000, 99999) . '.' . $tmpExt;

        // 복사할 이미지명
        $tmpImageFile = $checkBannerPath . DS . $bannerImage;

        // 이미지 화일 저장
        if (FileHandler::isExists($tmpImageFile)) {
          $result = FileHandler::delete($tmpImageFile);
          if ($result !== true) {
            throw new \Exception(__('파일 저장중에 오류가 발생하여 실패되었습니다.'));
          }
        }
        $result = FileHandler::move(Request::files()->get('bannerImageFile')['tmp_name'][$fKey], $tmpImageFile);
        if ($result !== true) {
          throw new \Exception(__('파일 저장중에 오류가 발생하여 실패되었습니다.'));
        }

        // 계정용량 갱신 - 스킨
        gd_set_du('skin');
      }
      // 기존 이미지명
      else {
        $bannerImage = $postValue['bannerImage'][$fKey];
      }

      if (Request::files()->get('bannerNavActiveImageFile')['error'][$fKey] === '0' && Request::files()->get('bannerNavActiveImageFile')['size'][$fKey] > 0) {
        $bannerNavActiveImageFile = Request::files()->get('bannerNavActiveImageFile')['name'][$fKey];;
        $tmpExt = FileHandler::getFileInfo($bannerNavActiveImageFile)->getExtension();
        $bannerNavActiveImage = md5($bannerNavActiveImageFile) . '_' . mt_rand(10000, 99999) . '.' . $tmpExt;

        // 복사할 이미지명
        $tmpImageFile = $checkBannerPath . DS . $bannerNavActiveImage;

        // 이미지 화일 저장
        if (FileHandler::isExists($tmpImageFile)) {
          $result = FileHandler::delete($tmpImageFile);
          if ($result !== true) {
            throw new \Exception(__('파일 저장중에 오류가 발생하여 실패되었습니다.'));
          }
        }
        $result = FileHandler::move(Request::files()->get('bannerNavActiveImageFile')['tmp_name'][$fKey], $tmpImageFile);
        if ($result !== true) {
          throw new \Exception(__('파일 저장중에 오류가 발생하여 실패되었습니다.'));
        }

        // 계정용량 갱신 - 스킨
        gd_set_du('skin');
      } else {
        if (in_array($postValue['bannerNavActiveImage'][$fKey], $postValue['bannerNavActiveDel']) === true) {
          $bannerNavActiveImage = '';
        } else {
          $bannerNavActiveImage = $postValue['bannerNavActiveImage'][$fKey];
        }
      }

      if (Request::files()->get('bannerNavInactiveImageFile')['error'][$fKey] === '0' && Request::files()->get('bannerNavInactiveImageFile')['size'][$fKey] > 0) {
        $bannerNavInactiveImageFile = Request::files()->get('bannerNavInactiveImageFile')['name'][$fKey];;
        $tmpExt = FileHandler::getFileInfo($bannerNavInactiveImageFile)->getExtension();
        $bannerNavInactiveImage = md5($bannerNavInactiveImageFile) . '_' . mt_rand(10000, 99999) . '.' . $tmpExt;

        // 복사할 이미지명
        $tmpImageFile = $checkBannerPath . DS . $bannerNavInactiveImage;

        // 이미지 화일 저장
        if (FileHandler::isExists($tmpImageFile)) {
          $result = FileHandler::delete($tmpImageFile);
          if ($result !== true) {
            throw new \Exception(__('파일 저장중에 오류가 발생하여 실패되었습니다.'));
          }
        }
        $result = FileHandler::move(Request::files()->get('bannerNavInactiveImageFile')['tmp_name'][$fKey], $tmpImageFile);
        if ($result !== true) {
          throw new \Exception(__('파일 저장중에 오류가 발생하여 실패되었습니다.'));
        }

        // 계정용량 갱신 - 스킨
        gd_set_du('skin');
      } else {
        if (in_array($postValue['bannerNavInactiveImage'][$fKey], $postValue['bannerNavInactiveDel']) === true) {
          $bannerNavInactiveImage = '';
        } else {
          $bannerNavInactiveImage = $postValue['bannerNavInactiveImage'][$fKey];
        }
      }

      // 이미지 명이 있는 경우에만 정보 추가
      if (empty($bannerImage) === false) {
        // 이미지 정보 설정
        $postValue['bannerInfo'][$imgKey]['bannerImage'] = $bannerImage;
        $postValue['bannerInfo'][$imgKey]['bannerLink'] = $postValue['bannerLink'][$fKey];
        $postValue['bannerInfo'][$imgKey]['bannerTarget'] = $postValue['bannerTarget'][$fKey];
        $postValue['bannerInfo'][$imgKey]['bannerImageAlt'] = $postValue['bannerImageAlt'][$fKey];
        $postValue['bannerInfo'][$imgKey]['bannerBadgeText'] = $postValue['bannerBadgeText'][$fKey];
        $postValue['bannerInfo'][$imgKey]['bannerMainTitle'] = $postValue['bannerMainTitle'][$fKey];
        $postValue['bannerInfo'][$imgKey]['bannerSubtitle'] = $postValue['bannerSubtitle'][$fKey];
        $postValue['bannerInfo'][$imgKey]['bannerButtonTitle'] = $postValue['bannerButtonTitle'][$fKey];
        $postValue['bannerInfo'][$imgKey]['bannerForegroundColor'] = $postValue['bannerForegroundColor'][$fKey];

        $postValue['bannerInfo'][$imgKey]['bannerNavActiveImage'] = $bannerNavActiveImage;
        $postValue['bannerInfo'][$imgKey]['bannerNavActiveW'] = $postValue['bannerNavActiveW'][$fKey];
        $postValue['bannerInfo'][$imgKey]['bannerNavActiveH'] = $postValue['bannerNavActiveH'][$fKey];
        $postValue['bannerInfo'][$imgKey]['bannerNavInactiveImage'] = $bannerNavInactiveImage;
        $postValue['bannerInfo'][$imgKey]['bannerNavInactiveW'] = $postValue['bannerNavInactiveW'][$fKey];
        $postValue['bannerInfo'][$imgKey]['bannerNavInactiveH'] = $postValue['bannerNavInactiveH'][$fKey];

        $postValue['bannerInfo'][$imgKey]['bannerImageUseFl'] = $postValue['bannerImageUseFl_' . $postValue['checkKey'][$imgKey]];
        $postValue['bannerInfo'][$imgKey]['bannerImagePeriodFl'] = $postValue['bannerImagePeriodFl_' . $postValue['checkKey'][$imgKey]];
        $postValue['bannerInfo'][$imgKey]['bannerImagePeriodSDate'] = $postValue['bannerImagePeriodSDate'][$fKey];
        $postValue['bannerInfo'][$imgKey]['bannerImagePeriodEDate'] = $postValue['bannerImagePeriodEDate'][$fKey];
        $imgKey++;
      }
    }

    unset($postValue['bannerImage'], $postValue['bannerLink'], $postValue['bannerTarget'], $postValue['bannerImageAlt']);
    unset($postValue['bannerNavActiveImage'], $postValue['bannerNavActiveW'], $postValue['bannerNavActiveH'], $postValue['bannerNavInactiveImage'], $postValue['bannerNavInactiveW'], $postValue['bannerNavInactiveH']);

    // 배너 정보
    $postValue['bannerInfo']['bannerFolder'] = $postValue['bannerFolder']; // 이미지 정보에 저장 경로 세팅
    $postValue['bannerInfo'] = json_encode($postValue['bannerInfo']);

    // 저장
    $arrBind = $this->db->get_binding(DBTableField::tableDesignSliderBanner(), $postValue, 'update');
    $this->db->bind_param_push($arrBind['bind'], 'i', $postValue['sno']);
    $this->db->set_update_db(DB_DESIGN_SLIDER_BANNER, $arrBind['param'], 'sno = ?', $arrBind['bind']);
    unset($arrBind);

    // garbage image 삭제
    $this->_deleteSliderBannerGarbageImage($postValue['bannerInfo'], $postValue['bannerDeviceType'], $postValue['skinName']);

    return $postValue['sno'];
  }

  /**
   * 움직이는 배너 garbage image 삭제
   * @param string $getImageData 이미지 정보 json
   * @param string $bannerDeviceType 디바이스 타입
   * @param string $skinName 스킨명
   * @return boolean
   * @throws \Exception
   */
  private function _deleteSliderBannerGarbageImage($getImageData, $bannerDeviceType, $skinName)
  {
    $tmp = json_decode($getImageData, true);

    // 배너 이미지 경로 설정
    $bannerFolder = $tmp['bannerFolder'];
    $bannerFolder = UserFilePath::data('skin', $bannerDeviceType, $skinName, $this->bannerPathDefault, $bannerFolder);
    unset($tmp['bannerFolder']);

    // 현재 저장된 배너 이미지 배열
    $setBannerImageData = [];
    foreach ($tmp as $imageInfo) {
      if (empty($imageInfo['bannerImage']) === false) {
        $setBannerImageData[] = $imageInfo['bannerImage'];
      }
      if (empty($imageInfo['bannerNavActiveImage']) === false) {
        $setBannerImageData[] = $imageInfo['bannerNavActiveImage'];
      }
      if (empty($imageInfo['bannerNavInactiveImage']) === false) {
        $setBannerImageData[] = $imageInfo['bannerNavInactiveImage'];
      }
    }
    if (empty($setBannerImageData) === true) {
      return true;
    }

    // 저장된 폴더에서 비교 후 삭제
    foreach (new DirectoryIterator($bannerFolder) as $fileInfo) {
      if ($fileInfo->isFile() === true && $fileInfo->isDot() === false) {
        if (in_array($fileInfo->getFilename(), $setBannerImageData) === false) {
          FileHandler::delete($fileInfo->getPathname());
        }
      }
    }
    return true;
  }
}
