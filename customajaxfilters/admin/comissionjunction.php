<?php
namespace CustomAjaxFilters\Admin;
use \CustomAjaxFilters\Majax\MajaxWP as MajaxWP;

class ComissionJunction {
 private $cjCols; 
 private $mapping;
 private $basePage;
 private $brandsSlug;
 private $categorySlug;
 private $categorySeparator;
 private $typeSlug;
 public function __construct() {          
     $this->brandsSlug="brands";
     $this->categorySlug="category";
     $this->categorySeparator=" > ";
     $this->typeSlug="mauta_cj_type";
     $this->tableNameCustomCategories="cj_categories";
     $this->initCJcols();
     
 }
 public function addShortCodes() {
    add_shortcode('cjcategories', [$this,'outCategoriesTree'] );    
 }
 private function initCJcols() {
   $this->cjCols=[
       "id" => ["sql" => "int(11) NOT NULL AUTO_INCREMENT", "primary" => true],
       "buyurl" => ["sql" => "varchar(1000) NOT NULL", "csvPos" => "LINK", "mautaname" => "buyurl"],
       "shopurl" => ["sql" => "varchar(500) NOT NULL", "csvPos" => "PROGRAM_NAME", "mautaname" => "shopurl"],
       "imageurl" => ["sql" => "varchar(500) NOT NULL", "csvPos" => "IMAGE_LINK", "mautaname" => "imageurl"],
       "title" => ["sql" => "varchar(100) NOT NULL", "csvPos" => "TITLE", "mautaname" => "title"],
       "kw" => ["sql" => "TEXT NOT NULL", "mautaname" => "kw"],
       "type" => ["sql" => "TEXT NOT NULL", "csvPos" => "PRODUCT_TYPE", "mautaname" => "type", 
            "extra" => ["removeExtraSpaces" => true, "createSlug" => $this->getTypeSlug()] 
        ],
       "availability" => ["sql" => "TEXT NOT NULL", "csvPos" => "AVAILABILITY", "mautaname" => "availability"],
       "description" => ["sql" => "TEXT NOT NULL", "csvPos" => "DESCRIPTION", "mautaname" => "description"],
       "price" => ["sql" => "TEXT NOT NULL", "csvPos" => "PRICE", "mautaname" => "price"],
       "views" => ["sql" => "int(11) NOT NULL", "mautaname" => "views"],
       "tran" => ["sql" => "TINYINT(1) NOT NULL", "mautaname" => "tran"],
       "brand" => ["sql" => "varchar(100) NOT NULL", "csvPos" => "BRAND", "mautaname" => "brand"],
       "gender" => ["sql" => "varchar(100) NOT NULL", "csvPos" => "GENDER", "mautaname" => "gender"],
       "gtin" => ["sql" => "varchar(100) NOT NULL", "csvPos" => "GTIN", "mautaname" => "gtin"],
       "mpn" => ["sql" => "varchar(100) NOT NULL", "csvPos" => "MPN", "mautaname" => "mpn"],
       "shipping" => ["sql" => "varchar(100) NOT NULL", "csvPos" => "SHIPPING(COUNTRY:REGION:SERVICE:PRICE)", "mautaname" => "shipping"]
   ];  
   $this->mapping=[
       "post" => [
        "post_title" => "%1|title",
        "post_content" => "%1|description"
       ],
       "meta" => []
   ];   
 }

 public function getWPmapping() {
     return $this->mapping;
 }
 public function getFieldsExtras() {
     $extras=array();
     foreach ($this->cjCols as $key => $val) {
         if (!empty($val["extra"])) $extras[$key]["extra"]=$val["extra"];
     }
     return $extras;
 }
 public function getMautaFields() {
    foreach ($this->cjCols as $key => $val) {
        if (!empty($val["mautaname"])) {
         $recRow["name"]=$val["mautaname"];
         $recRow["compare"]=empty($val["compare"]) ? "=" : $val["compare"];
         $recRow["displayorder"]=empty($val["displayorder"]) ? "1" : $val["displayorder"];
         $recRow["filterorder"]=empty($val["filterorder"]) ? "1" : $val["filterorder"];
         $rows[]=$recRow;
        }        
    }
    return $rows;
 }
 function produceRecord($row,$addInfo=[]) {
    $recRow=[];
    foreach ($this->cjCols as $key => $val) {
        if (!empty($val["csvPos"])) {
         $recRow[$key]=$row[$val["csvPos"]];
        }        
    }
    foreach ($addInfo as $key => $val) {
        $recRow[$key]=$val;
    }
    return $recRow;
 }
 function createCjTables($mainTab,$catTab,$drop=true) {     
        /*   
        global $wpdb;  
        $charset_collate = $wpdb->get_charset_collate();        
        if ($drop) $wpdb->query( "DROP TABLE IF EXISTS {$tableName}");
        //table for cj import
        $query="CREATE TABLE `".$tableName."` (";
        foreach ($this->cjCols as $key => $val) {
            if (!empty($val["sql"])) $query.="`$key` ".$val["sql"].",";
        }
        $query.="PRIMARY KEY  (`id`) ) $charset_collate;";      
        $wpdb->get_results($query);
        */
        $args=[];
        if ($drop) $args["drop"]=true;
        MajaxWP\MikDb::createTable($mainTab,$this->cjCols,$args);

        //table for custom categories
        $customCatsCols=[
            "id" => ["sql" => "int(11) NOT NULL AUTO_INCREMENT", "primary" => true],
            "slug" => ["sql" => "text"],
            "name" => ["sql" => "text"],
            "desc" => ["sql" => "text"],
            "counts" => ["sql" => "int(11)"],
            "postType" => ["sql" => "text"],
            "flag" => ["sql" => "int(2)"],
        ];
        
        MajaxWP\MikDb::createTableIfNotExists($catTab,$customCatsCols,["drop" => false]);
  }
  function handleRewriteRules($basePage=[]) {    
    $this->basePage=$basePage;
    add_filter( 'query_vars', [$this,'mautacj_query_vars'] );
    add_action('init', [$this,'mauta_rewrite_rule'], 10, 0);
    add_filter( 'redirect_canonical', [$this,'disable_canonical_redirect_for_front_page'] );
  }
  function mautacj_query_vars( $vars ) {
    $vars[] = 'mik';
    $vars[] = 'mikcat';
    $vars[] = 'mikbrand';
    $vars[] = 'mikorder';
    return $vars;
  }
  function mauta_rewrite_rule() {        
    $mikBrandy=$this->brandsSlug;
    $mikCatSlug=$this->categorySlug;
    $page=$this->basePage["link"];
    $pageId="page_id={$this->basePage["id"]}&";
    //$page="";
    $phpScript="index.php"; //always index.php for wp
    
    add_rewrite_rule( "^$page"."$mikCatSlug/([^/]*)/$mikBrandy/([^/]*)/([^/]*)/?", $phpScript.'?'.$pageId.'mikcat=$matches[1]&mikbrand=$matches[2]&mikorder=$matches[3]','top' );
    add_rewrite_rule( "^$page"."$mikCatSlug/([^/]*)/$mikBrandy/([^/]*)/?", $phpScript.'?'.$pageId.'mikcat=$matches[1]&mikbrand=$matches[2]','top' );
    add_rewrite_rule( "^$page"."$mikCatSlug/([^/]*)/([^/]*)/?", $phpScript.'?'.$pageId.'mikcat=$matches[1]&mikorder=$matches[2]','top' );
    add_rewrite_rule( "^$page"."$mikCatSlug/([^/]*)/?", $phpScript.'?'.$pageId.'mikcat=$matches[1]','top' );
  }
  function disable_canonical_redirect_for_front_page( $redirect ) {
    //https://wordpress.stackexchange.com/questions/185169/using-add-rewrite-rule-to-redirect-to-front-page  
    //so that something.com/categoryslug/categoryname did not redirect to something.com
    if ( is_page() && $front_page = get_option( 'page_on_front' ) ) {
        if ( is_page( $front_page ) )
            $redirect = false;
    }
    return $redirect;
   }
   function findCategoryParent($cats,$id,$level) { //categories array, thought parent category id
    $parentName=$cats[$id]["name"];
    foreach ($cats as $key => $c) {
        if ($c["name"] == $parentName && $level==0) return $key;
        if ($c["name"] == $parentName && $level>0) return $this->findCategoryParent($cats,$key,$level-1);
    }
    if ($level==0) return $id;
   }
   function findCategory($cats,$parent,$level,$name) {
    foreach ($cats as $key => $c) {
        if ($level==0) {
            if ($c["name"] == $name) return $key;
        }
        else {
            if ($c["name"] == $name && $c["parent"]==$parent) return $key;
        }
    }
    return false;
   }
   public function getCategoryCol() {
       return $this->cjCols["type"]["mautaname"];
   }
   public function getTypeSlug() {
    return $this->typeSlug;
   }
   public function getCatsTabName() {
    return $this->tableNameCustomCategories;
   }
   function countPostsInCats($cats,$postType) {
    global $wpdb;
    $categoryCol=$this->getCategoryCol();
    foreach ($cats as $key => $c) {   
     $catPath=$c["path"];
     /*
     list all posts with specific category
            SELECT post_title,post_name,post_content,pm1.meta_value
                        FROM wp_posts LEFT JOIN wp_postmeta pm1 ON ( pm1.post_id = ID)  
                        WHERE post_id=id 
                        AND post_status like 'publish' 
                        AND post_type like 'cj'	
			AND meta_key = 'type'	
			AND meta_value LIKE 'Ratanové doplňky > Ostatní'
     */

     /*
        SELECT COUNT(post_title) 
            FROM wp_posts LEFT JOIN wp_postmeta pm1 ON ( pm1.post_id = ID) 
            WHERE post_id=id 
            AND post_status like 'publish' 
            AND post_type like 'cj' 
            AND meta_key = 'type' 
            AND meta_value LIKE 'Ratanový nábytek > Křesla'
     */

       $query="
       SELECT COUNT(post_title) as cnt
            FROM {$wpdb->prefix}posts po LEFT JOIN {$wpdb->prefix}postmeta pm1 ON ( pm1.post_id = ID) 
            WHERE po.ID=pm1.post_id
            AND po.post_status like 'publish' 
            AND po.post_type like '{$postType}' 
            AND pm1.meta_key = '{$categoryCol}' 
            AND pm1.meta_value LIKE '{$catPath}%'
        ";
            
     $cnt=$wpdb->get_var($query);
     $cats[$key]["postsCount"]=$cnt;
    }
    return $cats;
   }
   function createCategories($postType) {
    global $wpdb;    
    $categoriesSorted=array();

    //varianta natahavani z post_meta
    /*
    $categoryCol=$this->getCategoryCol();	
    $query="SELECT DISTINCT(`meta_value`) AS category FROM ".$wpdb->prefix."postmeta AS pm, ".$wpdb->prefix."posts AS po 
	WHERE pm.meta_key like '{$categoryCol}' AND po.post_status = 'publish' 
    AND po.post_type = '{$postType}'";
    */    
    $catTabName=MajaxWP\MikDb::getTablePrefix().$this->getCatsTabName();    
    $query="SELECT DISTINCT(`name`) AS category FROM `{$catTabName}` WHERE `postType`='{$postType}' AND `flag`='1';";
  

    $categories = $wpdb->get_results($query);	 
    $catId=0;    
    foreach ($categories as $c) {           
        $thisCatArr=explode($this->categorySeparator,$c->category);
        $parent="";
        $prevCat="";
        $n=0;
        $thisCatPath="";        
        foreach ($thisCatArr as $cat) {
            if ($n>0) { 
                $thisCatPath.=$this->categorySeparator;    
                $parent=$prevCat;
                $parent=$this->findCategoryParent($categoriesSorted,$prevCat,$n-1);
            }
            else $parent=null;            
            $cat=trim($cat);  
            $thisCatPath.=$cat;          
            $existingCat=$this->findCategory($categoriesSorted,$parent,$n,$cat);
            if ($existingCat===false) {
                $categoriesSorted[$catId] = ["name" => $cat, "parent" => $parent, "level" => $n, "path" => $thisCatPath];
                $prevCat=$catId;
                $catId++;
            }
            else {                 
                $prevCat=$existingCat;
            }
            
            $n++;
        }
        $catTabName=MajaxWP\MikDb::getTablePrefix().$this->getCatsTabName();    
        MajaxWP\MikDb::clearTable($catTabName,["flag='2'"]);	
        $catsFinal=[];
        foreach ($categoriesSorted as $c) {
            $row=["slug" => sanitize_title($c["path"]), 
                    "name" => $c["path"], 
                    "postType" => $postType, 
                    "flag" => "2",
                    "counts"=>$c["postsCount"] 
                ];
            MajaxWP\MikDb::insertRow($catTabName,$row);
            $catsFinal[]=$row;
        }
        //write to cache
        MajaxWP\Caching::addCache("sortedcats".$postType,$catsFinal,"sortedcats".$postType); 

        return $catsFinal;  
    }

    /*
    SELECT DISTINCT(`meta_value`) AS category FROM ".$wpdb->prefix."postmeta AS pm, ".$wpdb->prefix."posts AS po 
	WHERE pm.meta_key like '{$categoryCol}' AND po.post_status = 'publish' 
    AND po.post_type = '{$postType}'
    */

    //count counts of posts in categories
    $categoriesSorted=$this->countPostsInCats($categoriesSorted,$postType);
    return $categoriesSorted;
   }
   function getCategoriesArr($postType) {
    global $wpdb;
    //during posts import flag 1 are being created, than categories are created
    $cacheOff=true;
    $catTabName=MajaxWP\MikDb::getTablePrefix().$this->getCatsTabName();    

    /* load from table */
	$query = "SELECT * FROM `{$catTabName}` WHERE `flag`='2' AND `postType`='$postType' ORDER BY `name`";
    $catsFinal=$wpdb->get_results($query, ARRAY_A);
    if (!empty($catsFinal) && count($catsFinal)>0) return $catsFinal;
    
    /* load from cache */
    if (!$cacheOff) {
        $catsFinal=MajaxWP\Caching::getCachedJson("sortedcats".$postType);
        if ($catsFinal!==false) {
            return $catsFinal;
        }   
    }
    
    $catsFinal=$this->createCategories($postType);    
      	
      

    return $catsFinal;
   }


   function getPermaLink($category,$brand="") {
    $mikBrandy=$this->brandsSlug;
    $mikCatSlug=$this->categorySlug;
    $page=$this->basePage["link"];
    $catSlug=sanitize_title($category);
    $link="{$page}/{$mikCatSlug}/{$catSlug}/";
    if (!empty($brand)) $link.="{$mikBrandy}/{$brand}";
    return $link;
   }
   function outParentCategory($cats,$thisId) {
    //recursively output category branch
    $out="
    <ul>
        <li><a href='".$this->getPermaLink($cats[$thisId]["path"])."'>{$cats[$thisId]["name"]} ({$cats[$thisId]["postsCount"]})</a>";
        foreach ($cats as $key => $c) {
            if ($c["parent"]===$thisId) { 
                $out.=$this->outParentCategory($cats,$key);
            }
        }
    $out.="
        </li>
    </ul>"; 
    return $out;
   }
   function outCategoriesTree($atts=[]) {
    if (!empty($atts["posttype"])) $cats=$this->getCategoriesArr($atts["posttype"]);

    foreach ($cats as $key => $c) {
        if ($c["parent"]===null) echo $this->outParentCategory($cats,$key);
    }
   }
}
