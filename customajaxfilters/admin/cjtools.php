<?php
namespace CustomAjaxFilters\Admin;
use \CustomAjaxFilters\Majax\MajaxWP as MajaxWP;

class CJtools {
    private $params;
    private $customPostType;
    private $keywords;
    private $language;
    private $currentCat;
    private $categorySeparator;
    public function __construct($customPostType) {
        $this->setPostType($customPostType);
        if (empty($this->language)) $this->language=Settings::loadSetting("language","site");
        $this->translating=new MajaxWP\Translating($this->language);  
        $this->categorySeparator=">";
        $this->separatorVariations=[ "|"," > ","&gt;", "> "," >"];
        $this->bannedCategories=["Heureka.cz","NÁBYTEK","Nábytek"];      
    }

    public function setParam($name, $val) {
        $this->params[$name]=$val;
        return $this;
    }
    public function getParams($key1,$key2=false) {
        if ($key2!==false) return $this->params[$key1][$key2];
        else return $this->params[$key1];
    }
    public function setPostType($postType) {
        $this->customPostType=$postType;  
    }

    public function updateImage($content, $id) {
        preg_match('/< *img[^>]*src *= *["\']?([^"\']*)/i', $content, $matches);
        $imgSrc = $matches[1];
        $content = str_replace($imgSrc, "/mimgtools.php?id=$id", $content);
        $content = str_replace("'", "''", $content);
        $fn = wp_get_upload_dir()["basedir"]."/mimgnfo-$id";
        file_put_contents($fn, $imgSrc);
        return $content;
    }
    private function removeExtraSpaces($c) {
        $c = preg_replace('!\s+!', ' ', $c);
        $c = trim($c);
        return $c;
    }
    function mReplText($description) {
        $repl = [
            "krmivo"=> "žrádlo",
            "krmiva"=> "žrádlo",
            "pes "=> "pejsek ",
            "psy "=> "pejsky ",
            "psi "=> "pejsci ",
            "Psy"=> "Pejsky",
            "Pes"=> "Pejsek",
            "Psi"=> "Pejsci",
            "kočka "=> "kočička ",
            "kočky "=> "kočičky ",
            "Kočka"=> "Kočička",
            "Kočky"=> "Kočičky",
            "kočce "=> "kočičce ",
            "koťata "=> "koťátka ",
            "Koťáta "=> "Koťátka ",
            "malá plemena"=> "malé rasy",
            "plemen "=> "ras ",
            "mohou "=> "můžou ",
            "surovin "=> "látek ",
            "surovin."=> "látek.",
            "jídlo "=> "krmivo ",
            "procento "=> "% ",
            "procento "=> "% ",
            ".S"=> ". S",
            " A "=> " a ",
            ".P"=> ". P",
            ".O"=> ". O",
            ".U"=> ". U",
            ".N"=> ". N",
            ".C"=> ". C",
            ".R"=> ". R",
            ",s"=> ", s",
            ",p"=> ", p",
            ",o"=> ", o",
            ",u"=> ", u",
            ",n"=> ", n",
            ",c"=> ", c",
            ",r"=> ", r"
        ];
        foreach($repl as $s => $r) {
            $description = str_replace($s, $r, $description);
        }
        return $description;
    }
    function ms_escape_string($data) {
        if (!isset($data) or empty($data) ) return '';
        if (is_numeric($data)) return $data;

        $non_displayables = array(
            '/%0[0-8bcef]/',            // url encoded 00-08, 11, 12, 14, 15
            '/%1[0-9a-f]/',             // url encoded 16-31
            '/[\x00-\x08]/',            // 00-08
            '/\x0b/',                   // 11
            '/\x0c/',                   // 12
            '/[\x0e-\x1f]/'             // 14-31
        );
        foreach($non_displayables as $regex)
        $data = preg_replace($regex, '', $data);
        $data = str_replace("'", "''", $data);
        return $data;
    }
   
    private function removePriceFormat($val) {
        if (substr($val,-3)==".00") $val=substr($val,0,strlen($val)-3);
        //if (substr($val,-3,1)==".") $val=substr($val,0,strlen($val)-3)."#".substr($val,strlen($val)-2);
        return $val;
    }
    public function createPostsFromTable($fields, $from = null, $to = null, $extras = []) {
        global $wpdb;
        $table = $this->params["tableName"];
        $dedicatedTable=$this->dedicatedTable=Settings::loadSetting("dedicatedTables-".$this->customPostType,"cptsettings");
        if ($dedicatedTable) { 
            $ded=new DedicatedTables($this->customPostType);
            $ded->initTable(false);
            $dedicatedTable=true;
        }
        $limit = "";
        if (!empty($to)) {
            if (!$from) $from = "0";
            $limit = "LIMIT ".$from.",".($to - $from);
        }
        $results = $wpdb->get_results("SELECT * FROM $table $limit", ARRAY_A);

        //get columns of csvtab
        //get columns of wp_mauta_fields
        $terms = [];
        foreach($results as $r) {
            //create post
            $key = "title";
            $r[$key] = $this->mReplText($r[$key]);
            $r[$key] = str_replace("<br />", "&nbsp;", $r[$key]);
            $r[$key] = strip_tags($r[$key]);
            $titleSafe = str_replace("'", "&#8242;", $r[$key]);
            
            $r["brand"] = $this->mReplText($r["brand"]);

            $key = "description";
            $r[$key] = $this->mReplText($r[$key]);
            $r[$key] = str_replace("</li><li>", "&nbsp;", $r[$key]);
            $r[$key] = str_replace("<br />", "&nbsp;", $r[$key]);
            $r[$key] = str_replace("<br>", "&nbsp;", $r[$key]);
            $r[$key] = strip_tags($r[$key]);
            $r[$key] = $this->ms_escape_string($r[$key]);
            $r[$key] = str_replace(", ", ",", $r[$key]); //oprava carek
            $r[$key] = str_replace(",", ", ", $r[$key]); //oprava carek  
            //osetreni qoutes
            $r[$key] = str_replace("''", "^^^", $r[$key]); 
            $r[$key] = str_replace("^^^", "'", $r[$key]); 
            $r[$key] = str_replace("'", "''", $r[$key]); 

            $buyurl = $r["buyurl"];
            
            $r["imageurl"] = str_replace("http://", "https://", $r["imageurl"]);            
            if (!empty($r["imageurl"])) {                
                $id=md5($r["imageurl"]);
                $fn=wp_get_upload_dir()["basedir"]."/mimgnfo-$id";
                file_put_contents($fn,$r["imageurl"]); 
                $r["imageurl"] = "/mimgtools/$id/";                            
            }
            
            //$imageHtml = "<a title='".$titleSafe."' href='$buyurl' rel='nofollow'><img alt='".$titleSafe."' width=300 src='{$r["imageurl"]}' /></a>";
            $imageHtml = "<img alt='".$titleSafe."' src='{$r["imageurl"]}' />";
            $r["imageurl"] = $imageHtml;
            
            $r["price"] = str_replace(" CZK", "", $r["price"]);
            $r["price"] = str_replace(" USD", "", $r["price"]);
            
            $randDiscount=rand(120,160);
            $r["priceDiscount"]=ceil(($r["price"]/100)*$randDiscount);

            $postArr = [];
            $postArr["post_title"] = $r["title"];
            $postArr["post_status"] = "publish";
            $postArr["post_content"] = $r["description"];
            $postArr["post_type"] = $this->customPostType;
            $postId = wp_insert_post($postArr);
            if ($dedicatedTable) {
                $row=[
                    "post_title" => $r["title"], 
                    "post_name" => sanitize_title($r["title"]), 
                    "post_content" => $r["description"]
                ];
            }
            //echo json_encode(["result"=> "inserted {$postArr["post_title"]} $postId"]).PHP_EOL;


            //all fields detected in csvtab 
            foreach($fields as $f) {
                $name = $f->name;
                $title = $f->title;
                $metaValue = $r[$title];
                $createMeta = true;

                //skip not interesting fields
                if (!$f->filterorder && !$f->displayorder) continue;

                //extras-apply filters like trim to all values
                if (!empty($extras[$name]["removeExtraSpaces"])) $metaValue = $this->removeExtraSpaces($metaValue);
                if (!empty($extras[$name]["removePriceFormat"])) $metaValue = $this->removePriceFormat($metaValue);
                
                if (!empty($extras[$name]["createSlug"])) {
                    $metaValue = $this->sanitizePath($metaValue);
                    $metaValueSlug = $this->sanitizeSlug($metaValue);
                    add_post_meta($postId, $name, $metaValueSlug);
                    if ($dedicatedTable) $row[$name]=$metaValueSlug;
                    $terms[$metaValueSlug] = $metaValue;
                    $createMeta=false;
                }
                if (!empty($extras[$name]["noImport"])) $createMeta=false;
                
                if (isset($metaValue) && $createMeta) {
                    add_post_meta($postId, $name, $metaValue);
                    if ($dedicatedTable) $row[$name]=$metaValue;
                }
            }
            if ($dedicatedTable) $ded->insertRow($row);

        }

        //create temp category table 
        if (!empty($this->params["cjCatsTempTable"])) {
            foreach($terms as $term => $value) {
                //$row=["slug" => $term, "name" => $value, "postType" => $this->customPostType];
                $row = ["name" => $value, "postType" => $this->customPostType];
                MajaxWP\MikDb:: insertRow($this->params["cjCatsTempTable"], $row);
            }
        }

    }
    function getChildCategories($parentId) {
        return MajaxWP\MikDb:: wpdbGetRows($this->params["cjCatsTable"], ["id", "path", "counts"], [["name"=> "parent", "value" => $parentId]]);
    }
    function getCatById($id) {
        $cats = MajaxWP\MikDb:: wpdbGetRows($this->params["cjCatsTable"], "*", [["name"=> "id", "value" => $id]]);
        return $cats[0];
    }
    function getCatByPath($path) {
        $cats = MajaxWP\MikDb:: wpdbGetRows($this->params["cjCatsTable"], "*", [["name"=> "path", "value" => $path]]);
        return $cats[0];
    }
    function getCatBySlug($slug,$useCache=false) {
        if (!$slug) return false;
        $cats = MajaxWP\MikDb:: wpdbGetRows($this->params["cjCatsTable"], "*", [["name"=> "slug", "operator" => "LIKE", "value" => $slug]],$useCache);        
        if (empty($cats[0])) return [];
        $this->currentCat=$cats[0];
        return $this->currentCat;
    }
    
    function getCats($from=null,$limitCnt=null,$where=null) {
        $params["tableNames"]=$this->params["cjCatsTable"];
        $params["cols"]="*";
        $params["where"]=[];
        $params["where"][]=["name"=> "postType", "value" => $this->customPostType];
        if (!empty($where)) { 
            foreach ($where as $w) $params["where"][]=$w;
        }
        $params["limit"]=[];
        if ($from!==null) $params["limit"][]=$from;
        if ($limitCnt!==null) $params["limit"][]=$limitCnt;
        $cats = MajaxWP\MikDb:: wpdbGetRowsAdvanced($params);        
        return $cats;
    }
    
    function getCatMeta($catPath, $queryMetaName, $exact = true, $distinct=true,$limit="",$skipBlank=false,$order="") {        
        $prefix = MajaxWP\MikDb:: getWPprefix();
        global $wpdb;
        $catMetaName = $this->params["catSlugMetaName"];        

        if (!$exact) $catPath.= "%";
        if ($distinct) $selectVar="DISTINCT(pm2.meta_value)";        
        else $selectVar="pm2.meta_value";
        $skip="";
        if ($skipBlank) $skip=" AND NOT pm2.meta_value = ''";                
        if ($catPath && $catPath!="%") {
            $query = "
            SELECT $selectVar
            FROM {$prefix}posts po LEFT JOIN {$prefix}postmeta pm1 ON(pm1.post_id = ID) LEFT JOIN {$prefix}postmeta pm2 ON(pm2.post_id = ID)
            WHERE
            po.post_status like 'publish'
            AND po.post_type like '{$this->customPostType}'
            AND pm1.meta_key = '{$catMetaName}'
            AND pm1.meta_value LIKE '{$catPath}'
            AND pm2.meta_key = '$queryMetaName'
            $skip
            $order
            $limit
            ";             
        }
        else {
            $query = "
            SELECT $selectVar
            FROM {$prefix}posts po LEFT JOIN {$prefix}postmeta pm2 ON(pm2.post_id = ID)
            WHERE            
            po.post_status like 'publish'
            AND po.post_type like '{$this->customPostType}'            
            AND pm2.meta_key = '$queryMetaName'  
            $skip  
            $order       
            $limit
            ";  
        }
        
        //return $wpdb->get_results($query, ARRAY_A);
        return MajaxWP\Caching::getCachedRows($query);

    }
    function getPostsByCategory($catSlug, $exact = true,$dedTable=false,$dedFields=[]) {
        $prefix = MajaxWP\MikDb:: getWPprefix();
        global $wpdb;
        $catMetaName = $this->params["catSlugMetaName"];
        if (!$exact) $catSlug.= "%";
        if ($dedTable) {
            $cols="";
            foreach ($dedFields as $d) {
                $cols.=",".$d;
            }
            $query = "
            SELECT post_title{$cols}
            FROM $dedTable
            WHERE `{$catMetaName}` LIKE '{$catSlug}'
            ";             
        } else {
            $query = "
            SELECT post_title, ID
            FROM {$prefix}posts po LEFT JOIN {$prefix}postmeta pm1 ON(pm1.post_id = ID)
            WHERE
             po.post_status like 'publish'
            AND po.post_type like '{$this->customPostType}'
            AND pm1.meta_key = '{$catMetaName}'
            AND pm1.meta_value LIKE '{$catSlug}'
            ";             
        }
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    function getLngText($txt) {
        $isCZ = (Settings:: loadSetting("language", "site") == "cs");

        if ($isCZ) {
            if ($txt == "Search") return "Hledat";
            if ($txt == "GET SPECIAL DISCOUNT »") return "KOUPIT SE SLEVOU »";
            if ($txt == "More than") return "Více než";
            if ($txt == "great offers in category") return "skvělých nabídek v kategorii";
            if ($txt == "products") return "položek";
            if ($txt == "Enjoy") return "Užijte si";
            if ($txt == "Find") return "Najděte";
            if ($txt == "Choose- ") return "Vyberte si- ";
            if ($txt == "Shop- ") return "Nakupujte- ";
            if ($txt == "Eshops with") return "Eshopy nabízející";
            if ($txt == "Shops with") return "Obchody, kde najdete ";
            if ($txt == "more than") return "více než";
            if ($txt == "over") return "víc jak";
            if ($txt == "products") return "produktů";
            if ($txt == "offers") return "nabídek";
            if ($txt == "sales") return "zlevněných položek";
            if ($txt == "in") return "v";
            if ($txt == "by") return "od";
            if ($txt == "Also") return "Dále třeba";
            if ($txt == "and other brands") return "a dalších značek";
            if ($txt == "and other producers") return "a dalších výrobců";

        }
        else return $txt;
    }
    function mRndTxt($texts) {
        return $this->getLngText($texts[array_rand($texts)]);
    }
    function countPosts($c,$dedTable) {
            global $wpdb;
            $catMetaName=$this->params["catSlugMetaName"];
            $catSlug=$this->sanitizeSlug($c["slug"]);
            if ($dedTable) {
                $query="
                SELECT COUNT(post_title) as cnt
                     FROM {$dedTable} 
                     WHERE 
                     {$catMetaName} LIKE '{$catSlug}%'
                 ";
            } else {
                $query="
                SELECT COUNT(post_title) as cnt
                     FROM {$wpdb->prefix}posts po LEFT JOIN {$wpdb->prefix}postmeta pm1 ON ( pm1.post_id = ID) 
                     WHERE po.ID=pm1.post_id
                     AND po.post_status like 'publish' 
                     AND po.post_type like '{$this->customPostType}' 
                     AND pm1.meta_key = '{$catMetaName}' 
                     AND pm1.meta_value LIKE '{$catSlug}%'
                 ";
            }
            
                    
            $cnt=$wpdb->get_var($query);
            if ($cnt<1) {
                //weird
                $cnt=$cnt;
            }
            return $cnt;
           
    }
    function updateCatsDescription($from=null,$to=null) {
        $cats = $this->getCats($from,$to-$from);
        //$cats=$this->getCats(null,null,[["name"=>"slug","operator"=>"LIKE","value"=>"detsky-pokoj%"]]);        
        $doCounts=true;
        $doDesc=true;
        $doParents=true;
        $skipNonBlank=false;
        $dedTable=Settings::loadSetting("dedicatedTables-".$this->customPostType,"cptsettings");
        foreach($cats as $c) {
            if ($skipNonBlank && $c["counts"]>0) continue;
            if ($doDesc) $desc = $this->prepareCatDescription($c,$dedTable);
            if ($doCounts) $postsCount=$this->countPosts($c,$dedTable);
            //find parents
            if ($doParents) {
                $parentId=false;
                $rPos=strrpos($c["path"],">");
                if ($rPos>0) {
                    $parentPath=substr($c["path"],0,$rPos);
                    $parentId=$this->getCatByPath($parentPath)["id"];                
                }
            }
            
            $update=[];
            $update[]=["name" => "desc", "value" => $desc];
            $update[]=["name" => "counts", "type" => "%d", "value" => $postsCount];
            if ($parentId) {
                $update[]=["name" => "parent", "type" => "%d", "value" => $parentId];
            } 
            MajaxWP\MikDb:: wpdbUpdateRows($this->params["cjCatsTable"], 
                        $update                          
                    , 
                    [
                        ["name"=> "id", "type" => "%d", "value" => $c["id"]]
                    ]);
        }
        return "cats description updated";
    }
    function sanitizePath($path) {
        $sep=$this->categorySeparator;
        $path=$this->removeExtraSpaces($path);
        foreach ($this->separatorVariations as $v)  {
            if (strlen($v)>=strlen($sep)) $path=str_replace($v,$sep,$path);
        }
        //trim from start        
        if (substr($path,0,strlen($sep)) == $sep) $path=substr($path,strlen($sep)+1);
        foreach ($this->bannedCategories as $ban) {
            if ($path == $ban) $path=false;
            if (strpos($path,$ban.$sep)!==false) $path=str_replace($ban.$sep,"",$path);
            if (strpos($path,$sep.$ban)!==false) $path=str_replace($sep.$ban,"",$path);
        }
        return $path;
    }
    function sanitizeSlug($path,$sanitizePath=false) {
        if ($sanitizePath) $path=$this->sanitizePath($path);
        $sep=$this->categorySeparator;        
        return sanitize_title(str_replace($sep, "-", $path));
    }
    
    function getImageFromImageUrl($html) {
        preg_match_all('/<img.*?src=[\'"](.*?)[\'"].*?>/i', $html, $matches);
        return (empty($matches[0][0])) ? false : $matches[0][0];
    }
    function prepareCatDescription($cat,$dedTable=false) {
        $desc = "#catTitle#enjoymorethan#cntPosts#txtproducts#cats#txtby#brands.#prods#txtAlso#subcats.";        
        $txtproducts = "";
        $enjoy = "";
        $txtby = "";
        $subCatStr = "";
        
        $subcats = $this->getChildCategories($cat["id"]);
        $cntSubCats = count($subcats);
        $n = 0;
        if ($cntSubCats > 3) $cntSubCats = 3;
        foreach($subcats as $subcat) {
            if ($n < $cntSubCats && ($subcat["id"] != $cat["id"])) {
                if ($subCatStr) {
                    $subCatStr.= ", ";
                }
                $subCatStr.= "".$this->getCatPathNice($subcat["path"]);
                $n++;
            }
        }
        unset($subcats);
        if ($subCatStr) {
            $txtAlso = " ".$this->getLngText("Also");
            $subCatStr = " ".$subCatStr;
        }
        else {
            $txtAlso = "";
        }
        $desc = str_replace("#txtAlso", $txtAlso, $desc);
        $desc = str_replace("#subcats", $subCatStr, $desc);

        $termPosts = $this->getPostsByCategory($cat["slug"], false,$dedTable,[$this->params["metaNames"]["brand"],$this->params["metaNames"]["imageurl"]]);

        $brandyArr = array();
        $prodNames = array();
        $prodImages=array();
        $brandyStr = "";
        $prodStr = "";
        $cntPosts = 0;
        $catParent = "";
        $subCatStr = "";

        foreach($termPosts as $mPost) {
            if (!$dedTable) {
                $brand = get_post_meta($mPost["ID"], $this->params["metaNames"]["brand"]);
                $images = get_post_meta($mPost["ID"], $this->params["metaNames"]["imageurl"]);
            } else {
                $brand = $mPost[$this->params["metaNames"]["brand"]];
                $images = $mPost[$this->params["metaNames"]["imageurl"]];
            }
            
            $prodName = $mPost["post_title"];
            $prodImage = $this->getImageFromImageUrl($images[0]);
            if (!in_array($brand[0], $brandyArr)) array_push($brandyArr, $brand[0]);
            if (!in_array($prodName, $prodNames)) array_push($prodNames, $prodName);
            if (!in_array($prodImage, $prodImages)) array_push($prodImages, $prodImage);
            $cntPosts++;
        }
        $title="<h1>".$this->getCatPathNice($cat["path"])."</h1>";
        if (count($prodImages)>2) {
            $title=$prodImages[0]."$title".$prodImages[count($prodImages)-1];
        } 
        $desc = str_replace("#catTitle", "<section class='catTitle'>".$title."</section>", $desc);
        //file_put_contents(get_template_directory()."/adfeed.log"," updatetermsdesc2 cnt:$cntPosts ".$cat["path"]." proc:$d/$y now: ".getDateTime()." \n",FILE_APPEND);	
        unset($termPosts);

        $enjoy = "";
        $enjoy = $this->mRndTxt(array("Enjoy", "Find", "Choose- ", "Shop- ", "Eshops with", "Shops with"));

        if ($cntPosts > 19) {
            if (strlen($cntPosts) > 1) $cntPosts = str_pad(substr($cntPosts, 0, 1), strlen($cntPosts), "0");
            $cntPosts = " ".$cntPosts;
            $enjoy.= " ".$this->mRndTxt(array("more than", "over"));
            $txtproducts = " ".$this->mRndTxt(array("products", "offers", "sales"));
        }
        else {
            $cntPosts = "";
            $txtproducts = "";
        }
        $desc = str_replace("#cntPosts", $cntPosts, $desc);
        $desc = str_replace("#enjoymorethan", $enjoy, $desc);


        $desc = str_replace("#txtproducts", $txtproducts, $desc);
        shuffle($brandyArr);
        $cntBrandy = count($brandyArr);
        if ($cntBrandy > 3) $cntBrandy = 3;
        for ($n = 0; $n < $cntBrandy; $n++) {
            if ($brandyStr) $brandyStr.= ", ";
            $brandyStr.= $brandyArr[$n];
        }
        if ($brandyStr) {
            $brandyStr = " ".$brandyStr." ".$this->mRndTxt(array("and other brands", "and other producers"));
            $txtby = ", ".$this->getLngText("by");
        }
        else {
            $txtby = "";
            $brandyStr = "";
        }
        $desc = str_replace("#txtby", $txtby, $desc);

        $desc = str_replace("#brands", $brandyStr, $desc);

        $cntProd = count($prodNames);
        shuffle($prodNames);
        if ($cntProd > 3) $cntProd = 3;
        for ($n = 0; $n < $cntProd; $n++) {
            if ($prodStr) $prodStr.= ", ";
            $prodStr.= $prodNames[$n];
        }
        if ($prodStr) {
            $prodStr = " ".$prodStr.".";
        }
        $desc = str_replace("#prods", $prodStr, $desc);


        if ($cat["parent"]) {
            //echo "<h3>".($cat->parent)."</h3>";
            $parentTerm = $this->getCatById($cat["parent"]);
            $catParent = $parentTerm["path"]." - ";
            $kats = ' "'.$catParent.$cat["path"].'"';
        }
        else {
            $kats = ' "'.$cat["path"].'"';
        }
        $desc = str_replace("#cats", $kats, $desc);
        //$result=wp_update_term($cat["id"], $taxonomy, array('description' => $desc));
        return $desc;
    }
    
    
    function createCategories() {
        global $wpdb;    
        $categoriesSorted=array();
    
        $catTabName=$this->params["cjCatsTempTable"];    
        $query="SELECT DISTINCT(`name`) AS category FROM `{$catTabName}` WHERE `postType`='{$this->customPostType}';";
        $categories = $wpdb->get_results($query);	 
    
        $catId=0;    
        foreach ($categories as $c) {  
            $c->category=$this->sanitizePath($c->category);
            if (!$c->category) continue;
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
 
    
        $catTabName=$this->params["cjCatsTable"];    
        //MajaxWP\MikDb::clearTable($catTabName);	
        $catsFinal=[];
        $map=[];
        foreach ($categoriesSorted as $key => $c) {
            if ($c["path"]) {
                $row=["slug" => $this->sanitizeSlug($c["path"]), 
                "path" => $c["path"], 
                "parent" => ($c["parent"]===null) ? null : $map[$c["parent"]], 
                "postType" => $this->customPostType
                ];    
                //check if cat already exists
                $currRow=MajaxWP\MikDb::wpdbGetRows($catTabName,"path",[["name" => "path", "value" => $c["path"]]]);
                if (empty($currRow)) {
                    $map[$key]=MajaxWP\MikDb::insertRow($catTabName,$row);
                    $catsFinal[]=$row;
                }         
            }        
        }
        //write to cache
        MajaxWP\Caching::addCache("sortedcats".$this->customPostType,$catsFinal,"sortedcats".$this->customPostType); 
    
        return $catsFinal;      
    }
    function getCatPathNice($path="",$last=false) {
        if (!$path) { 
            if (!empty($this->currentCat["path"])) $path=$this->currentCat["path"];
        }
        if ($last) {
            $cats=explode(">",$path);
            if (is_array($cats)) return end($cats);
            else return $path;
        }
        return str_replace(">", "- ",$path);
    }
 
    function getUrl($cat,$brand="") {
        $url="/";
        $mikBrandSlug=$this->params["brandSlug"];
        $mikCatSlug=$this->params["catSlug"]; 
        if ($cat) {
            $url.=$mikCatSlug."/".$cat."/";
        }
        if ($brand) {
            $url.=$mikBrandSlug."/".urlencode($brand)."/";
        }
        return $url;
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
    function showBrandyNav($metaName) {                
        $mikBrand=urlDecode(get_query_var("mikbrand"));
        $catSlug=MajaxWp\CjFront::getCurrentCat(); 
        $brandyArr=$this->getCatMeta($catSlug,$metaName,false,true," LIMIT 1,15",true,"ORDER BY rand()");
        $brandsArr=[];
        if (count($brandyArr)<2) return false;
        foreach ($brandyArr as $brand) {
            if (!in_array($brand["meta_value"],$brandsArr)) $brandsArr[]=$brand["meta_value"];
        }
        $brandyArr=$brandsArr;
        //if (empty($brandsArr) || count($brandsArr)<2) return "";
        //showBrandyNav($thisTerm->name,$thisTerm->slug,$brandyStr);
        
        if (!empty($this->currentCat)) $name=$this->getCatPathNice();
        else $name="";
        //$brandsText=$this->translating->loadTranslation("products by brands");
        $allBrandsText=$this->translating->loadTranslation("(all brands)");
        ?>
        
        <?php
        if ($mikBrand) {
            ?>
            <a href='<?= $this->getUrl($catSlug)?>'><?= $allBrandsText?></a>
            <?php
        } else {
            ?>
            <strong><?= $allBrandsText?></strong>
            <?php
        }
        ?>
        
        <ul>
        <?php
        foreach ($brandyArr as $brand) {
         //$url="/$mikCatSlug/$catSlug/$mikBrandy/".urlEncode($brand)."/";         
         ?>
         <li>
         <?php
         if ($brand == $mikBrand) {    
          ?>
          <strong><?= $brand?></strong>
          <?php          
         }  
         else {
          ?>
            <a href='<?= $this->getUrl($catSlug,$brand)?>'><?= $brand?></a>
          <?php
         }
         ?>
         </li>
         <?php
        }
        ?>
        </ul>
        <?php
        return true;
    }
}