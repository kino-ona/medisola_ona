<?php

namespace Component\Medisola;

use Exception;

class MedisolaAuthorizeApi
{
    const SESSION_APP_AUTHORIZE = 'medisola_authorize';
    const PRODUCT_ISSUE_CODE_URL = 'https://zcndqpmzgyljpfrbraot.supabase.co/functions/v1/issue_code';
    const STAGING_ISSUE_CODE_URL = 'https://fafanhszgewcmcegyxgr.supabase.co/functions/v1/issue_code';
    const REDIRECT_URIS = [
        'https://solamate.co.kr/',
        'mdsl://solamate.co.kr/',
        'https://webtest.solamate.co.kr/',
        'mdsl://webtest.solamate.co.kr/',
        ];
    const MEDISOLA_API_KEY = 'test-edge-function-admin-api-key';

    public function verifyParam($responseType, $clientId, $redirectUri) {
        if($responseType != 'code') {
            throw new \Exception('bad request: response_type=' . $responseType); 
        }
        if($clientId !== 'medisola' && $clientId !== 'medisola-staging' && $clientId !== 'medisola-dev') {
            throw new \Exception('bad request: client_id=' . $clientId); 
        }
        foreach(self::REDIRECT_URIS as $uri) {
            if(strpos($redirectUri, $uri) === 0) {
                return;
            }
        }
        if($clientId === 'medisola-dev' && (strpos($redirectUri, 'http://localhost:') === 0 || strpos($redirectUri, 'mdsl://localhost:') === 0)) {
            // 앱 로컬 환경 테스트에서 통과하기 위한 코드
            return;
        }
        throw new \Exception('bad request: redirectUri=' . $redirectUri); 
    }

    public function issueCode($memNo, $clientId, $devIssueCodeUri)
    {
        switch ($clientId) {
            case 'medisola':
                $url = self::PRODUCT_ISSUE_CODE_URL;
                break;
            case 'medisola-staging':
                $url = self::STAGING_ISSUE_CODE_URL;
                break;
            case 'medisola-dev':
                // 알고 있는 issue_code_url로 지정 못하도록 방지
                if(strpos($devIssueCodeUri, self::PRODUCT_ISSUE_CODE_URL) !== false || strpos($devIssueCodeUri, self::STAGING_ISSUE_CODE_URL) !== false) {
                    throw new \Exception('invalid devIssueCodeUri: ' . $devIssueCodeUri);
                }
                $url = $devIssueCodeUri;
                break;
            default:
                throw new \Exception('unsupported clientId: ' . $clientId);
        }
        $payload = [
            'mem_no' => $memNo,
        ];
        
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . self::MEDISOLA_API_KEY,
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new \Exception('cURL error: ' . $error);
        }

        $obj = json_decode($response);
        $code = $obj->code;

        if(empty($code)) {
            throw new \Exception("can't issue a code"); 
        }
        return $code;
    }
}
