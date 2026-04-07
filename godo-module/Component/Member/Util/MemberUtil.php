<?php
/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall5 to newer
 * versions in the future.
 *
 * @copyright ⓒ 2016, NHN godo: Corp.
 * @link      http://www.godo.co.kr
 */

namespace Component\Member\Util;

use App;
use Component\Database\DBTableField;
use Component\Validator\Validator;
use Cookie;
use Exception;

/**
 * Class MemberUtil
 * @package Component\Member\Util
 * @author  yjwee
 * @method static MemberUtil getInstance
 */
class MemberUtil extends \Bundle\Component\Member\Util\MemberUtil
{

    // 추가내용 (2020.03.25)
    /**
     * 휴대폰 중복 확인. 이미 해당 휴대폰을 사용 중인 아이디일 경우 중복되지 않은 것으로 판단한다.
     *
     * @static
     *
     * @param string $memId
     * @param string $cellPhone
     *
     * @return bool true 중복된 휴대폰, false 중복되지 않거나 해당 아이디가 사용 중인 휴대폰
     * @throws Exception
     */
    public static function overlapCellPhone($memId, $cellPhone)
    {
        if (Validator::required($memId) === false) {
            throw new Exception(sprintf(__('%s은(는) 필수 항목 입니다.'), __('아이디')));
        }

        $fields = DBTableField::getFieldTypes('tableMember');
        $strSQL = 'SELECT memId FROM ' . DB_MEMBER . ' where memId != ? and cellPhone = ?';
        $arrBind = [];
        $db = App::load('DB');
        $db->bind_param_push($arrBind, $fields['memId'], $memId);
        $db->bind_param_push($arrBind, $fields['cellPhone'], $cellPhone);

        return MemberUtil::isGreaterThanNumRows($strSQL, $arrBind, 0);
    }

    public static function getDistinctJoinedViaWithCount() {
		$arrBind = [];

		$strSQL = "
            SELECT joinedVia, COUNT(joinedVia) AS count FROM `es_member` WHERE joinedVia IS NOT NULL AND joinedVia != '' GROUP BY joinedVia
		";

        $db = App::load('DB');
		$result = $db->query_fetch($strSQL, $arrBind, false);

        return $result;
    }

    /**
     * 쿠키를 포함한 로그아웃 처리
     * SNS 자동 로그인 쿠키도 함께 삭제
     */
    public static function logoutWithCookie()
    {
        parent::logoutWithCookie();
        
        // SNS 자동 로그인 쿠키 삭제
        if (Cookie::has('GD5ATL_SNS')) {
            // Cookie::del()은 환경에 따라 path/domain이 다르게 적용되어 삭제가 실패할 수 있어
            // path 를 '/'로 고정하여 만료시각을 과거로 내려 확실히 제거한다.
            Cookie::set('GD5ATL_SNS', '', time() - 42000, '/');
        }
    }

    /**
     * 자동 로그인 쿠키 저장 (기간 20일로 확장)
     */
    public static function saveCookieByLogin(\Framework\Object\SimpleStorage $storage)
    {
        $saveId = $storage->get('saveId', 'n');
        $saveAutoLogin = $storage->get('saveAutoLogin', 'n');
        $cookieData = [];
        if ($saveAutoLogin == 'y') {
            $cookieData[self::COOKIE_LOGIN_FLAG] = self::KEY_AUTO_LOGIN;
            $cookieData[self::COOKIE_LOGIN_ID] = \Encryptor::encrypt($storage->get('loginId'));
            $cookieData[self::COOKIE_LOGIN_PW] = \Encryptor::encrypt($storage->get('loginPwd'));
            Cookie::set(self::COOKIE_LOGIN, json_encode($cookieData), (3600 * 24 * 20));
        } else if ($saveId == 'y' && $saveAutoLogin != 'y') {
            $cookieData[self::COOKIE_LOGIN_FLAG] = self::KEY_SAVE_LOGIN_ID;
            $cookieData[self::COOKIE_LOGIN_ID] = \Encryptor::encrypt($storage->get('loginId'));
            Cookie::set(self::COOKIE_LOGIN, json_encode($cookieData), (3600 * 24 * 20));
        } else {
            Cookie::del(self::COOKIE_LOGIN);
        }
    }

}
