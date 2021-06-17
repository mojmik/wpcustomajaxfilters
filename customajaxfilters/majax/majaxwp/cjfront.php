<?php
namespace CustomAjaxFilters\Majax\MajaxWP;
use \CustomAjaxFilters\Admin as MajaxAdmin;

Class CjFront {
	private static $cj;
    private static $cjCat;
    private static $currentPostType;
    private static $currentCat;
	function __construct() {

    }
    public static function getCJ($postType="") {
        if (empty(CjFront::$cj)) CjFront::$cj=new MajaxAdmin\ComissionJunction(["postType" => $postType]);
        if ($postType && CjFront::$currentPostType != $postType) { 
            CjFront::$currentPostType=$postType;
            CjFront::$cj->setPostType(CjFront::$currentPostType);
        }
        return CjFront::$cj;
    }
    public static function getCat($catSlug="") {        
        if (empty(CjFront::$cjCat)) {
            $cj=CjFront::getCJ();
            if (!$catSlug) { 
                $catSlug=CjFront::getCurrentCat();
            }
            CjFront::$cjCat=$cj->getCjTools()->getCatBySlug($catSlug,true);
            if (!CjFront::$currentCat) CjFront::$currentCat=$catSlug;
        }
        return CjFront::$cjCat;
    }
    public static function getCurrentCat() {
        if (!CjFront::$currentCat) CjFront::$currentCat=get_query_var("mikcat");
        return CjFront::$currentCat;
    }
}