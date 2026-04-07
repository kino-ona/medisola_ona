<?php

namespace Controller\Front\Member\Kakao;

use App;
use Bundle\Component\Policy\KakaoLoginPolicy;

/**
 * 카카오 로그인 회원 생년월일 복구 스크립트
 * - es_member.birthDt가 1990-01-01인 카카오 회원을 대상으로
 * - 카카오 Admin API로 실제 생년월일을 조회하여 업데이트
 * - 스킵된 회원은 es_member.birthFixFl = 'y'로 마킹하여 재조회 방지
 *
 * 실행: /member/kakao/kakao_birth_fix.php?key=medisola2025&limit=10
 * 파라미터:
 *   key     - 접근 키 (필수)
 *   limit   - 한 번에 처리할 인원 수 (기본: 10)
 *   execute - 'yes'면 실제 업데이트, 없으면 dry run
 *   reset   - 'yes'면 birthFixFl 플래그 초기화 후 처음부터 다시
 */
class KakaoBirthFixController extends \Controller\Front\Controller
{
    public function index()
    {
        // 간단한 접근 제한
        $key = \Request::get()->get('key');
        if ($key !== 'medisola2025') {
            echo 'Unauthorized';
            exit();
        }

        // dry run 모드 (기본: dry run)
        $dryRun = \Request::get()->get('execute') !== 'yes';

        // 페이징 파라미터
        $limit = (int) \Request::get()->get('limit') ?: 10;

        $db = App::load('DB');

        // birthFixFl 컬럼 추가 (없으면)
        $columnCheck = $db->query_fetch("SHOW COLUMNS FROM es_member LIKE 'birthFixFl'");
        if (empty($columnCheck)) {
            $db->query("ALTER TABLE es_member ADD COLUMN birthFixFl char(1) NOT NULL DEFAULT 'n' COMMENT '생년월일 복구 처리 여부'");
        }

        // 리셋 요청 시 플래그 초기화
        if (\Request::get()->get('reset') === 'yes') {
            $db->query("UPDATE es_member SET birthFixFl = 'n' WHERE birthFixFl = 'y'");
            echo '<pre>birthFixFl 플래그가 초기화되었습니다.</pre>';
        }

        // 카카오 Admin Key 조회
        $kakaoPolicy = new KakaoLoginPolicy();

        $ref = new \ReflectionClass($kakaoPolicy);
        $prop = $ref->getProperty('currentPolicy');
        $prop->setAccessible(true);
        $policyData = $prop->getValue($kakaoPolicy);

        $adminKey = $policyData['adminKey'] ?? '';

        if (empty($adminKey)) {
            echo '<pre>ERROR: 카카오 Admin Key를 찾을 수 없습니다.</pre>';
            exit();
        }

        echo '<pre>';
        echo "=== 카카오 회원 생년월일 복구 스크립트 ===\n";
        echo "모드: " . ($dryRun ? "DRY RUN (미리보기)" : "EXECUTE (실제 업데이트)") . "\n";
        echo "처리 단위: {$limit}명\n";
        echo "Admin Key: " . substr($adminKey, 0, 8) . "...\n\n";

        // 전체 대상 수 조회 (이미 처리된 회원 제외)
        $countSql = "SELECT COUNT(*) as cnt
                     FROM es_memberSns s
                     JOIN es_member m ON s.memNo = m.memNo
                     WHERE s.snsTypeFl = 'kakao'
                     AND m.birthDt = '1990-01-01'
                     AND m.birthFixFl = 'n'";
        $countRow = $db->query_fetch($countSql);
        $remaining = (int) $countRow[0]['cnt'];

        // 이미 처리(스킵)된 회원 수
        $skipCountRow = $db->query_fetch("SELECT COUNT(*) as cnt
                                          FROM es_memberSns s
                                          JOIN es_member m ON s.memNo = m.memNo
                                          WHERE s.snsTypeFl = 'kakao'
                                          AND m.birthDt = '1990-01-01'
                                          AND m.birthFixFl = 'y'");
        $totalSkipped = (int) $skipCountRow[0]['cnt'];

        // 미처리 회원만 조회
        $sql = "SELECT s.memNo, s.uuid, m.memId, m.memNm, m.birthDt
                FROM es_memberSns s
                JOIN es_member m ON s.memNo = m.memNo
                WHERE s.snsTypeFl = 'kakao'
                AND m.birthDt = '1990-01-01'
                AND m.birthFixFl = 'n'
                ORDER BY s.memNo ASC
                LIMIT {$limit}";

        $rows = $db->query_fetch($sql);
        $fetchCount = count($rows);

        echo "남은 대상: {$remaining}명 | 이번 조회: {$fetchCount}명 | 누적 스킵: {$totalSkipped}명\n";
        echo str_repeat('-', 80) . "\n";

        if ($fetchCount === 0) {
            echo "\n처리할 대상이 없습니다.\n";
            echo '</pre>';
            exit();
        }

        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $memNo = $row['memNo'];
            $uuid = $row['uuid'];
            $memId = $row['memId'];
            $memNm = $row['memNm'];

            echo "\n[memNo: {$memNo}] {$memNm} ({$memId}) - uuid: {$uuid}\n";

            // 카카오 Admin API 호출
            $birthInfo = $this->getKakaoBirth($adminKey, $uuid);

            if ($birthInfo === null) {
                echo "  → 생년월일 미제공 → 0000-00-00 으로 설정\n";
                $db->query("UPDATE es_member SET birthDt = '0000-00-00', birthFixFl = 'y' WHERE memNo = '{$memNo}'");
                $skipped++;
                continue;
            }

            $newBirthDt = $birthInfo['birthDt'];
            $calendarFl = $birthInfo['calendarFl'];

            echo "  → 카카오 생년월일: {$newBirthDt} ({$calendarFl})\n";

            if ($newBirthDt === '1990-01-01') {
                echo "  → 카카오에도 1990-01-01 → 1990-01-01 으로 설정\n";
                $db->query("UPDATE es_member SET birthDt = '1990-01-01', birthFixFl = 'y' WHERE memNo = '{$memNo}'");
                $skipped++;
                continue;
            }

            if (!$dryRun) {
                $escapedBirthDt = $db->escape($newBirthDt);
                $escapedCalendarFl = $db->escape($calendarFl);
                $db->query("UPDATE es_member SET birthDt = '{$escapedBirthDt}', calendarFl = '{$escapedCalendarFl}', birthFixFl = 'y' WHERE memNo = '{$memNo}'");
                echo "  → UPDATED!\n";
            } else {
                echo "  → (dry run) 업데이트 예정\n";
            }
            $updated++;

            // API 호출 간격 (카카오 rate limit 대응)
            usleep(100000); // 100ms
        }

        $afterRemaining = $remaining - $updated - $skipped;

        echo "\n" . str_repeat('=', 80) . "\n";
        echo "결과 요약:\n";
        echo "  이번 조회: {$fetchCount}명\n";
        echo "  업데이트" . ($dryRun ? " 예정" : " 완료") . ": {$updated}명\n";
        echo "  스킵: {$skipped}명\n";
        echo "  남은 대상: {$afterRemaining}명\n";
        echo "  누적 스킵: " . ($totalSkipped + $skipped) . "명\n";

        if ($afterRemaining > 0) {
            echo "\n다음 실행: ?key=medisola2025&limit={$limit}" . ($dryRun ? "" : "&execute=yes") . "\n";
        } else {
            echo "\n모든 대상 처리 완료!\n";
        }

        if ($dryRun) {
            echo "\n실제 업데이트: ?key=medisola2025&limit={$limit}&execute=yes\n";
        }

        echo "플래그 초기화: ?key=medisola2025&reset=yes\n";

        echo '</pre>';
        exit();
    }

    /**
     * 카카오 Admin API로 생년월일 조회
     */
    private function getKakaoBirth($adminKey, $uuid)
    {
        $url = 'https://kapi.kakao.com/v2/user/me';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: KakaoAK ' . $adminKey,
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'target_id_type' => 'user_id',
            'target_id' => $uuid,
            'property_keys' => '["kakao_account.birthyear","kakao_account.birthday","kakao_account.birthday_type"]',
        ]));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            echo "  → API Error (HTTP {$httpCode}): " . substr($response, 0, 200) . "\n";
            return null;
        }

        $data = json_decode($response, true);
        $account = $data['kakao_account'] ?? [];

        $birthYear = $account['birthyear'] ?? '';
        $birthday = $account['birthday'] ?? '';
        $birthType = $account['birthday_type'] ?? 'SOLAR';

        if (strlen($birthYear) !== 4 || strlen($birthday) !== 4) {
            echo "  → 생년월일 데이터 없음 (birthyear: '{$birthYear}', birthday: '{$birthday}')\n";
            return null;
        }

        $month = substr($birthday, 0, 2);
        $day = substr($birthday, 2, 2);
        $birthDt = "{$birthYear}-{$month}-{$day}";

        $calendarFl = (strtoupper($birthType) === 'SOLAR') ? 's' : 'l';

        return [
            'birthDt' => $birthDt,
            'calendarFl' => $calendarFl,
        ];
    }
}
