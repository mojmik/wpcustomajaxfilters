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
 private $dbPrefix;
 private $typeSlug;
 private $postType;
 public function __construct($args=[]) {          
     $this->brandsSlug="brands";
     $this->categorySlug="category";
     $this->categorySeparator=" > ";
     $this->typeSlug="mauta_cj_type";     
     if (!empty($args["prefix"])) $this->dbPrefix=$args["prefix"];
     else $this->dbPrefix=MajaxWP\MikDb::getTablePrefix();
     if (!empty($args["postType"])) $this->setPostType($args["postType"]);
     
     $this->initCJcols();
     
 }
 private function setPostType($postType) {
    $this->postType=$postType;
    $this->tableNames=[
        "main" => $this->dbPrefix."".$this->postType."_cj_import",
        "tempCats" => $this->dbPrefix."".$this->postType."_cj_tempcats",
        "cats" => $this->dbPrefix."".$this->postType."_cj_cats"
 ];
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
 function createCjTables() {     
        //table for storing cj import from csv
        MajaxWP\MikDb::createTable($this->getTabName("main"),$this->cjCols,["drop" => true]);

        //table for custom categories
        $customCatsCols=[
            "id" => ["sql" => "int(11) NOT NULL AUTO_INCREMENT", "primary" => true],
            "name" => ["sql" => "text"],
            "postType" => ["sql" => "text"],
        ];        
        MajaxWP\MikDb::createTable($this->getTabName("tempCats"),$customCatsCols,["drop" => true]);

        //table for custom categories
        $customCatsCols=[
            "id" => ["sql" => "int(11) NOT NULL AUTO_INCREMENT", "primary" => true],
            "slug" => ["sql" => "text"],
            "path" => ["sql" => "text"],
            "parent" => ["sql" => "text"],
            "postType" => ["sql" => "text"],
            "desc" => ["sql" => "text"],
            "counts" => ["sql" => "int(11)"]            
        ];        
        MajaxWP\MikDb::createTableIfNotExists($this->getTabName("cats"),$customCatsCols,["drop" => false]);

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
   private function getTabName($type) {
    return $this->tableNames[$type];
   }
   public function getTempCatsTabName() {
       return  $this->getTabName("tempCats");
   }
   public function getMainTabName() {
    return  $this->getTabName("main");
}

   function countPostsInCats($cats) {
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
            AND po.post_type like '{$this->postType}' 
            AND pm1.meta_key = '{$categoryCol}' 
            AND pm1.meta_value LIKE '{$catPath}%'
        ";
            
     $cnt=$wpdb->get_var($query);
     $cats[$key]["postsCount"]=$cnt;
    }
    return $cats;
   }
   function createCategories() {
    global $wpdb;    
    $categoriesSorted=array();

    //varianta natahavani z post_meta
    /*
    $categoryCol=$this->getCategoryCol();	
    $query="SELECT DISTINCT(`meta_value`) AS category FROM ".$wpdb->prefix."postmeta AS pm, ".$wpdb->prefix."posts AS po 
	WHERE pm.meta_key like '{$categoryCol}' AND po.post_status = 'publish' 
    AND po.post_type = '{$this-postType}'";
    */    

    $catTabName=$this->getTabName("tempCats");    
    $query="SELECT DISTINCT(`name`) AS category FROM `{$catTabName}` WHERE `postType`='{$this->postType}';";
  

    $categories = $wpdb->get_results($query);	 
    $catId=0;    
    foreach ($categories as $c) {           
        $thisCatArr=explode($this->categorySeparator,$c->category);
        $parent=null;
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
    }

    /*
    SELECT DISTINCT(`meta_value`) AS category FROM ".$wpdb->prefix."postmeta AS pm, ".$wpdb->prefix."posts AS po 
	WHERE pm.meta_key like '{$categoryCol}' AND po.post_status = 'publish' 
    AND po.post_type = '{$this->postType}'
    */

    //count counts of posts in categories
    $categoriesSorted=$this->countPostsInCats($categoriesSorted,$this->postType);

    $catTabName=$this->getTabName("cats");    
    MajaxWP\MikDb::clearTable($catTabName);	
    $catsFinal=[];
    $map=[];
    foreach ($categoriesSorted as $key => $c) {
        $row=["slug" => sanitize_title($c["path"]), 
                "path" => $c["path"], 
                "parent" => ($c["parent"]===null) ? null : $map[$c["parent"]], 
                "postType" => $this->postType, 
                "counts"=>$c["postsCount"] 
            ];        
        $map[$key]=MajaxWP\MikDb::insertRow($catTabName,$row);
        $catsFinal[]=$row;
    }
    //write to cache
    MajaxWP\Caching::addCache("sortedcats".$this->postType,$catsFinal,"sortedcats".$this->postType); 

    return $catsFinal;      
   }
   function getCategoriesArr() {
    global $wpdb;
    //during posts import flag 1 are being created, than categories are created
    $cacheOff=true;
    $catTabName=$this->getTabName("cats");    
        
    if (!$cacheOff) {
        /* load from cache */
        $catsFinal=MajaxWP\Caching::getCachedJson("sortedcats".$this->postType);
        if ($catsFinal!==false) {
            return $catsFinal;
        }   
    } else {
        /* load from table */
        $query = "SELECT * FROM `{$catTabName}` WHERE `postType`='$this->postType' ORDER BY `path`";
        $catsFinal=$wpdb->get_results($query, ARRAY_A);
        if (!empty($catsFinal) && count($catsFinal)>0) return $catsFinal;
    }
    
    $catsFinal=$this->createCategories();    
      	
      

    return $catsFinal;
   }


   function getPermaLink($category,$brand="",$sanitize=false) {
    $mikBrandy=$this->brandsSlug;
    $mikCatSlug=$this->categorySlug;
    $page=$this->basePage["link"];
    $catSlug=($sanitize) ? sanitize_title($category) : $category;
    $link="{$page}/{$mikCatSlug}/{$catSlug}/";
    if (!empty($brand)) $link.="{$mikBrandy}/{$brand}";
    return $link;
   }
   function getCatById($cats,$id) {
    foreach ($cats as $c) {
        if ($c["id"]==$id) return $c;
    }
   }
   function outParentCategory($cats,$thisCat) {
    //recursively output category branch    
    $out="
    <ul>
        <li><a href='".$this->getPermaLink($thisCat["slug"])."'>{$thisCat["path"]} ({$thisCat["counts"]})</a>";
        foreach ($cats as $key => $c) {
            if ($c["parent"]===$thisCat["id"]) { 
                $out.=$this->outParentCategory($cats,$c);
            }
        }
    $out.="
        </li>
    </ul>"; 
    return $out;
   }
   function outCategoriesTree($atts=[]) {
       
    if (!empty($atts["posttype"])) { 
        $this->setPostType($atts["posttype"]);
        $cats=$this->getCategoriesArr();
    }

    foreach ($cats as $c) {
        if (!$c["parent"]) echo $this->outParentCategory($cats,$c);
    }
   }
}
