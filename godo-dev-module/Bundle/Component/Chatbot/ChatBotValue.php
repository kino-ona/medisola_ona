<?php

namespace Bundle\Component\Chatbot;
use App;
use Component\Member\MemberDAO;
use Framework\Object\SimpleStorage;
use Globals;
use Session;

class ChatBotValue
{
    /**
     * 생성자
     */
    public function __construct()
    {
        $mallType = '';
        switch (Globals::get('gLicense')['ecKind']){
            case 'standard':
                $mallType = 'gst_' . Globals::get('gLicense')['godosno'];
                break;
            case 'pro':
                $mallType = 'gpro_' . Globals::get('gLicense')['godosno'];
                break;
            case 'pro_plus':
                $mallType = 'gprop_' . Globals::get('gLicense')['godosno'];
                break;
        }
        $this->shopData['shopNo'] = $mallType;       //샵번호
        $this->shopData['solutionCode'] = Globals::get('gLicense')['ecKind'];       // 샵유형
        $this->shopData['mainDomain'] = gd_home_uri();   // url
        $this->shopData['imsiDomain'] = App::getInstance('request')->getDefaultHost();
        $this->shopData['adminId'] = Session::get('manager.managerId');
        $this->shopData['isSuper'] = Session::get('manager.isSuper');
    }
    /**
     * 데이터 가져오기
     *
     * @return array
     */
    public function getData()
    {
        return $this->shopData;
    }
}