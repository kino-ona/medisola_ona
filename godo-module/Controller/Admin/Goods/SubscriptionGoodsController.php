<?php

namespace Controller\Admin\Goods;

use App;
use Request;

class SubscriptionGoodsController extends \Controller\Admin\Controller
{
    public function index()
    {
        $this->callMenu('goods', 'subscription', 'subscription_goods');

        $obj = new \Component\Subscription\Subscription();
        if ($obj->applyFl) {
            $cate = App::load('\\Component\\Category\\CategoryAdmin');
            $gobj = App::load("\Component\Goods\Goods");
            $db = App::load('DB');

            $list = $q = [];
            $conds = "";
            $get = Request::get()->toArray();

            if ($get['sopt'] && $get['skey'])
                $q[] = "a.{$get['sopt']} LIKE '%{$get['skey']}%'";

            if ($get['isSubscription'])
                $q[] = "a.isSubscription='1'";

            $get['searchDateFl'] = $get['searchDateFl'] ? $get['searchDateFl'] : "regDt";

            if ($get['searchDate'][0]) {
                $sstamp = strtotime($get['searchDate'][0]);
                $q[] = "a.{$get['searchDateFl']} >= '" . date("Y-m-d H:i:s", $sstamp) . "'";
            }

            if ($get['searchDate'][1]) {
                $estamp = strtotime($get['searchDate'][1]) + (60 * 60 * 24);
                $q[] = "a.{$get['searchDateFl']} <= '" . date("Y-m-d H:i:s", $estamp) . "'";
            }

            $cateCd = "";
            if ($get['cateGoods']) {
                foreach ($get['cateGoods'] as $c) {
                    if ($c)
                        $cateCd = $c;
                }
            }

            if ($cateCd)
                $q[] = "gl.cateCd LIKE '{$cateCd}%'";


            if ($q)
                $conds = " AND " . implode(" AND ", $q);

            $page = $get['page'] ? $get['page'] : 1;
            $limit = 50;
            $sql = "SELECT COUNT(*) as cnt FROM " . DB_GOODS . " WHERE delFl='n'";
            $tmp = $db->query_fetch($sql);
            $amount = $tmp[0]['cnt'];

            $sql = "SELECT COUNT(DISTINCT(a.goodsNo)) as cnt FROM " . DB_GOODS . " AS a
              LEFT JOIN " . DB_GOODS_LINK_CATEGORY . " AS gl ON a.goodsNo = gl.goodsNo WHERE a.delFl='n'{$conds}";

            $tmp = $db->query_fetch($sql);
            $total = $tmp[0]['cnt'];

            $offset = ($page - 1) * $limit;
            $sql = "SELECT DISTINCT(a.goodsNo), a.optionFl FROM " . DB_GOODS . " AS a
              LEFT JOIN " . DB_GOODS_LINK_CATEGORY . " AS gl ON a.goodsNo = gl.goodsNo
            WHERE a.delFl='n'{$conds} ORDER BY a.goodsNo desc LIMIT {$offset}, {$limit}";
            $cateList = [];
            if ($tmp = $db->query_fetch($sql)) {
                foreach ($tmp as $t) {
                    if ($t['optionFl'] == 'y') {
                        $row = $db->fetch("SELECT COUNT(*) as cnt FROM " . DB_GOODS_OPTION . " WHERE goodsNo='{$t['goodsNo']}'");
                        if (empty($row['cnt']))
                            continue;
                    }


                    $goods = $gobj->getGoodsView($t['goodsNo']);
                    $images = [];
                    if ($imageList = $gobj->getGoodsImage($goods['goodsNo'], 'list')) {
                        foreach ($imageList as $li) {
                            $image = gd_html_goods_image($goods['goodsNo'], $li['imageName'], $goods['imagePath'], $goods['imageStorage'], 40, $goods['goodsNm'], '_blank');
                            $images[] = $image;
                        }
                    }

                    $goods['images'] = $images;

                    $list[] = $goods;
                }
            }

            $page = App::load("\Component\Page\Page", $page, $total, $amount, $limit);
            $page->setUrl(http_build_query($get));
            $this->setData("list", $list);
            $this->setData("page", $page);
            $this->setData("search", $get);
            $this->setData("cate", $cate);
            $this->addScript([
                'jquery/jquery.multi_select_box.js',
            ]);
            $this->getView()->setDefine("goodsSearchFrm", Request::getDirectoryUri() . "/goodsSearchForm2.php");

            $this->setData('wmSubscription', true);
        }
    }
}