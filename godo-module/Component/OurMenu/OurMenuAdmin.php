<?php

namespace Component\OurMenu;

use Component\Database\DBTableField;
use Bundle\Component\Storage\Storage;
use Framework\Object\SimpleStorage;
use Framework\Utility\ArrayUtils;
use Globals;
use Request;
use Session;
use UserFilePath;

class OurMenuAdmin extends OurMenu
{

    public function saveOurMenu(&$arrData, &$files)
    {
        if (ArrayUtils::isEmpty($files['ourMenuImage']) === false) {
            $file = $files['ourMenuImage'];
            if ($file['error'] == 0 && $file['size']) {
//                echo '<div >'. 'size: '. $file['size'] . '</div>';
                $saveFileName = substr(md5(microtime()), 0, 8) . rand(100, 999);
                $this->storage()->upload($file['tmp_name'], $saveFileName);
//                echo '<div >'. 'saveFileName: '. $saveFileName . '</div>';
                $arrData['imageUrlWeb'] = $saveFileName;
                $arrData['imageUrlMob'] = $saveFileName;
            }
        }
        $ourMenuId = $arrData['id'];
        switch (substr($arrData['mode'], 0, 6)) {
            case 'insert' :
            {
                // 저장
                $arrBind = [];
                $strSQL = "INSERT INTO medisol_ourMenu SET `name` = ? , `title` = ? , `description` = ? , `imageUrlWeb` = ? , `imageUrlMob` = ? , `link` = ? , `tags` = ? , `sortPosition` = ? , `displayChannel` = ?";
                $this->db->bind_param_push($arrBind, 's', $arrData['name']);
                $this->db->bind_param_push($arrBind, 's', $arrData['title']);
                $this->db->bind_param_push($arrBind, 's', $arrData['description']);
                $this->db->bind_param_push($arrBind, 's', $arrData['imageUrlWeb']);
                $this->db->bind_param_push($arrBind, 's', $arrData['imageUrlMob']);
                $this->db->bind_param_push($arrBind, 's', $arrData['link']);
                $this->db->bind_param_push($arrBind, 's', $arrData['tags']);
                $this->db->bind_param_push($arrBind, 'i', $arrData['sortPosition']);
                $this->db->bind_param_push($arrBind, 's', $arrData['displayChannel']);
//            $this->db->begin_tran();
                $this->db->bind_query($strSQL, $arrBind);
                // 등록된 쿠폰고유번호
                $ourMenuId = $this->db->insert_id();
                $this->checkMenuType();
                break;
            }
            case 'modify' :
            {
                // 수정
                $arrBind = [];
                $strSQL = "UPDATE medisol_ourMenu SET `name` = ? , `title` = ? , `description` = ? , `imageUrlWeb` = ? , `imageUrlMob` = ? , `link` = ? , `tags` = ? , `sortPosition` = ? , `displayChannel` = ? WHERE `id` = ?";
                $this->db->bind_param_push($arrBind, 's', $arrData['name']);
                $this->db->bind_param_push($arrBind, 's', $arrData['title']);
                $this->db->bind_param_push($arrBind, 's', $arrData['description']);
                $this->db->bind_param_push($arrBind, 's', $arrData['imageUrlWeb']);
                $this->db->bind_param_push($arrBind, 's', $arrData['imageUrlMob']);
                $this->db->bind_param_push($arrBind, 's', $arrData['link']);
                $this->db->bind_param_push($arrBind, 's', $arrData['tags']);
                $this->db->bind_param_push($arrBind, 'i', $arrData['sortPosition']);
                $this->db->bind_param_push($arrBind, 's', $arrData['displayChannel']);
                $this->db->bind_param_push($arrBind, 'i', $arrData['id']);
                $this->db->bind_query($strSQL, $arrBind);

                break;
            }
        }
    }

    public function getTotalCount()
    {
        $countArraybind = [];
        $strSQL = 'SELECT count(*) as cnt FROM medisol_ourMenu';
        $total = $this->db->query_fetch($strSQL, $countArraybind, false)['cnt'];
        return $total;
    }

    public function getOurMenuAdminList($addSaveTypeWhere = '')
    {
        $DB_OUR_MENU = 'medisol_ourMenu';
        $getValue = Request::get()->toArray();

        $sort['fieldName'] = gd_isset($getValue['sort']['name']);
        $sort['sortMode'] = gd_isset($getValue['sort']['mode']);
        if (empty($sort['fieldName']) || empty($sort['sortMode'])) {
            $sort['fieldName'] = 'om.sortPosition';
            $sort['sortMode'] = 'asc';
        } else {
            $sort['fieldName'] = 'om' . $sort['fieldName'];
        }

        // 레이어에서 자바스크립트 페이징 처리시 사용되는 구문
        if (gd_isset($getValue['pagelink'])) {
            $getValue['page'] = (int)str_replace('page=', '', preg_replace('/^{page=[0-9]+}/', '', gd_isset($getValue['pagelink'])));
        }
        gd_isset($getValue['page'], 1);
        gd_isset($getValue['pageNum'], 10);

        $page = \App::load('\\Bundle\\Component\\Page\\Page', $getValue['page']);
        $page->page['list'] = $getValue['pageNum'];

        $this->arrWhere[] = '1';
        $amountSql = 'SELECT count(id) FROM ' . $DB_OUR_MENU . ' WHERE 1' . $addSaveTypeWhere;

        list($page->recode['amount']) = $this->db->fetch($amountSql, 'row');
        $page->setPage();
        $page->setUrl(\Request::getQueryString());

        $this->db->strField = "om.*";
        $this->db->strWhere = implode(' AND ', gd_isset($this->arrWhere));
        $this->db->strOrder = $sort['fieldName'] . ' ' . $sort['sortMode'];
        $query = $this->db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . $DB_OUR_MENU . ' as om ' . implode(' ', $query);
//        \Logger::debug($strSQL, $this->arrBind);

//        echo '<div >' . 'strSQL: ' . $strSQL . '</div>';
        $data = $this->db->query_fetch($strSQL, $this->arrBind);
//        echo '<div >' . 'data: ' . $data . '</div>';
        $getData['data'] = gd_htmlspecialchars_stripslashes(gd_isset($data));


        $getData['sort'] = $sort;
        $getData['search'] = gd_htmlspecialchars($this->search);
        $getData['checked'] = $this->checked;

        return $getData;
    }



}