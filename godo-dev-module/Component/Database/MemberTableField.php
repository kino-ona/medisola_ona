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
namespace Component\Database;

/**
 * Class MemberTableField
 * @package Bundle\Component\Database
 * @author  yjwee <yeongjong.wee@godo.co.kr>
 */
class MemberTableField extends \Bundle\Component\Database\MemberTableField
{
    public function tableMemberGroup()
    {
        $arrField = parent::tableMemberGroup();
        //210929 디자인위브 mh 추가
//        $arrField[] = ['val' => 'selfCode', 'typ' => 's', 'def' => null]; // 등급별 입력 가능한 코드
        
        return $arrField;
    }
}