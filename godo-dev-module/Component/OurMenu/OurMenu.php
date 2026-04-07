<?php

namespace Component\OurMenu;

use Component\Database\DBTableField;
use Bundle\Component\Storage\Storage;
use Framework\Object\SimpleStorage;
use Framework\Utility\ArrayUtils;
use Globals;
use Request;
use Session;

class OurMenu
{
    protected $db = null;

    /**
     * @var array arrBind
     */
    protected $arrBind = [];
    protected $arrWhere = [];
    protected $checked = [];
    protected $selected = [];
    protected $search = [];

    protected $storage;
    protected $fieldTypes;
    protected $resultStorage;

//    public $DB_OUR_MENU = 'medisol_ourMenu';


    public function __construct()
    {
        if (!is_object($this->db)) {
            $this->db = \App::load('DB');
        }

        $this->fieldTypes['ourMenu'] = DBTableField::getFieldTypes('tableOurMenu');

        // 파일 핸들링을 위한 클래스 정의(메뉴이미지)
        $this->storage = null;
        $globals = \App::getInstance('globals');
    }

    public function checkMenuType()
    {
        return true;
    }

    protected function storage()
    {
        if ($this->storage == null) {
            $this->storage = Storage::disk(Storage::PATH_CODE_ETC);
        }
        return $this->storage;
    }

    public function getOurMenuImageData($ourMenuImageNm)
    {
        $ourMenuImagePath = $this->storage()->getHttpPath($ourMenuImageNm);

        return $ourMenuImagePath;
    }

    public function getOurMenuInfo($ourMenuId = null, $arrBind = null, $dataArray = false)
    {
        $DB_OUR_MENU = 'medisol_ourMenu';
        if ($ourMenuId) {
            $this->db->strWhere = " om.id = ?";
            $this->db->bind_param_push($arrBind, 'i', $ourMenuId);
        }
        $query = $this->db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . $DB_OUR_MENU . ' as om ' . implode(' ', $query);
        $getData = $this->db->query_fetch($strSQL, $arrBind);


        if (count($getData) == 1) {
            return gd_htmlspecialchars_stripslashes($getData[0]);
        }
        return gd_htmlspecialchars_stripslashes($getData);
    }

    public function getOurMenus()
    {
        $mallBySession = SESSION::get(SESSION_GLOBAL_MALL);
        $DB_OUR_MENU = 'medisol_ourMenu';
        $sort['fieldName'] = 'om.sortPosition';
        $sort['sortMode'] = 'asc';

        $this->arrWhere[] = " om.displayChannel != 'none' ";
        $this->db->strField = "om.*";
        $this->db->strWhere = implode(' AND ', gd_isset($this->arrWhere));
        $this->db->strOrder = $sort['fieldName'] . ' ' . $sort['sortMode'];
        $query = $this->db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . $DB_OUR_MENU . ' as om ' . implode(' ', $query);

//        echo '<div >' . 'strSQL: ' . $strSQL . '</div>';
        $data = $this->db->query_fetch($strSQL, $this->arrBind);


//        return $data;
        if (count($data) == 1) {
            return gd_htmlspecialchars_stripslashes($data[0]);
        }
        return gd_htmlspecialchars_stripslashes($data);


    }
}