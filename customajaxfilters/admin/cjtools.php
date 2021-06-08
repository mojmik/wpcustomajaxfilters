<?php
namespace CustomAjaxFilters\Admin;
use \CustomAjaxFilters\Majax\MajaxWP as MajaxWP;

class CJtools {
    private $params;
    private $customPostType;
    private $keywords;
    private $language;
    private $currentCat;
    public function __construct($customPostType) {
        $this->customPostType=$customPostType;
        if (empty($this->language)) $this->language=Settings::loadSetting("language","site");
        $this->translating=new MajaxWP\Translating($this->language);        
    }

    public function setParam($name, $val) {
        $this->params[$name]=$val;
        return $this;
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
            echo json_encode(["result"=> "inserted {$postArr["post_title"]} $postId"]).PHP_EOL;


            //all mauta_fields detected in csvtab are loaded
            foreach($fields as $f) {
                $name = $f->name;
                $title = $f->title;
                $metaValue = $r[$title];
                $createMeta = true;

                //extras-apply filters like trim to all values
                if (!empty($extras[$name])) {
                    foreach($extras[$name] as $operation) {
                        if (!empty($operation["removeExtraSpaces"])) $metaValue = $this->removeExtraSpaces($metaValue);
                        if (!empty($operation["removePriceFormat"])) $metaValue = $this->removePriceFormat($metaValue);
                        
                        if (!empty($operation["createSlug"])) {
                            $metaValue = $this->removeExtraSpaces($metaValue);
                            $metaValueSlug = sanitize_title(str_replace(">", "-", $metaValue));
                            add_post_meta($postId, $name, $metaValueSlug);
                            $terms[$metaValueSlug] = $metaValue;
                        }
                        
                    }
                }
                if (isset($metaValue) && $createMeta) {
                    add_post_meta($postId, $name, $metaValue);
                }
            }


        }

        //create category table 
        if (!empty($this->params["cjCatsTempTable"])) {
            foreach($terms as $term => $value) {
                //$row=["slug" => $term, "name" => $value, "postType" => $this->customPostType];
                $row = ["name" => $value, "postType" => $this->customPostType];
                MajaxWP\MikDb:: insertRow($this->params["cjCatsTempTable"], $row);
            }
        }

    }
    function getCategories() {
        return MajaxWP\MikDb:: wpdbGetRows($this->params["cjCatsTable"], ["id", "path", "slug", "counts", "parent"]);
    }
    function getChildCategories($parentId) {
        return MajaxWP\MikDb:: wpdbGetRows($this->params["cjCatsTable"], ["id", "path", "counts"], [["name"=> "parent", "value" => $parentId]]);
    }
    function getCatById($id) {
        $cats = MajaxWP\MikDb:: wpdbGetRows($this->params["cjCatsTable"], "*", [["name"=> "id", "value" => $id]]);
        return $cats[0];
    }
    function getCatBySlug($slug) {
        if (!$slug) return false;
        $cats = MajaxWP\MikDb:: wpdbGetRows($this->params["cjCatsTable"], "*", [["name"=> "slug", "value" => $slug]]);
        if (empty($cats[0])) return [];
        $this->currentCat=$cats[0];
        return $this->currentCat;
    }
    
    function getCats() {
        $cats = MajaxWP\MikDb:: wpdbGetRows($this->params["cjCatsTable"], "*", [["name"=> "postType", "value" => $this->customPostType]]);
        return $cats;
    }
    function getCatMeta($catPath, $queryMetaName, $exact = true, $distinct=true) {        
        $prefix = MajaxWP\MikDb:: getWPprefix();
        global $wpdb;
        $catMetaName = $this->params["catSlugMetaName"];        

        if (!$exact) $catPath.= "%";
        if ($distinct) $selectVar="DISTINCT(pm2.meta_value)";
        else $selectVar="pm2.meta_value";
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
            ";  
        }
        
        //return $wpdb->get_results($query, ARRAY_A);
        return MajaxWP\Caching::getCachedRows($query);

    }
    function getPostsByCategory($catSlug, $exact = true) {
        $prefix = MajaxWP\MikDb:: getWPprefix();
        global $wpdb;
        $catMetaName = $this->params["catSlugMetaName"];
        if (!$exact) $catSlug.= "%";
        $query = "
        SELECT post_title, ID
        FROM {$prefix}posts po LEFT JOIN {$prefix}postmeta pm1 ON(pm1.post_id = ID)
        WHERE po.ID = pm1.post_id
        AND po.post_status like 'publish'
        AND po.post_type like '{$this->customPostType}'
        AND pm1.meta_key = '{$catMetaName}'
        AND pm1.meta_value LIKE '{$catSlug}'
        ";             
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
    function updateCatsDescription($from=0,$to=0) {
        $cats = $this->getCats();
        $cnt = count($cats);
        if ($to>0) {
            $cats=array_slice($cats,$from,$to-$from);	
        }
        
        foreach($cats as $c) {
            $desc = $this->prepareCatDescription($c);
            MajaxWP\MikDb:: wpdbUpdateRows($this->params["cjCatsTable"], [["name" => "desc", "value" => $desc]], [["name"=> "id", "type" => "%d", "value" => $c["id"]]]);
        }
        return "cats description updated";
    }
    function sanitizeSlug($slug) {
        $separator = (empty($this->params["catSep"])) ? ">" : $this->params["catSep"];
        return sanitize_title(str_replace($separator, "-", $slug));
    }
    function mGoRound($prices) {
        foreach($prices as & $price) {
            if ($price > 10000) $price = round($price, -3);
            else if ($price > 1000) $price = round($price, -2);
            else if ($price > 100) $price = round($price,-1);
        }
        return $prices;
    }
    function getImageFromImageUrl($html) {
        preg_match_all('/<img.*?src=[\'"](.*?)[\'"].*?>/i', $html, $matches);
        return (empty($matches[0][0])) ? false : $matches[0][0];
    }
    function prepareCatDescription($cat) {
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

        $termPosts = $this->getPostsByCategory($cat["slug"], false);

        $brandyArr = array();
        $prodNames = array();
        $prodImages=array();
        $brandyStr = "";
        $prodStr = "";
        $cntPosts = 0;
        $catParent = "";
        $subCatStr = "";

        foreach($termPosts as $mPost) {
            $brand = get_post_meta($mPost["ID"], $this->params["metaNames"]["brand"]);
            $images = get_post_meta($mPost["ID"], $this->params["metaNames"]["imageurl"]);
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
    function getAjaxMore($catId,$taxTerms,$priceFrom=0,$priceTo=0) {   
        /* 
        global $hpPrice;
        if (isCZ()) $more="Další..";
        else $more="More..";
        if ($priceTo>0) $out='[ajax_load_more id="6946518025" button_label="'.$more.'" loading_style="green" container_type="div" css_classes="mlistingajax" post_type="hp_listing" posts_per_page="6" taxonomy_terms="'.$taxTerms.'" taxonomy="hp_listing_category"  taxonomy_operator="IN"  meta_key="'.$hpPrice.'" meta_value="'.$priceFrom.','.$priceTo.'" meta_compare="BETWEEN" meta_type="DECIMAL" orderby="meta_value_num"]';
        else $out='<!-- wp:hivepress/listings {"number":"3","category":"'.$catId.'","order":"random"} /--><p>[ajax_load_more id="6946518025" button_label="'.$more.'" loading_style="green" container_type="div" css_classes="mlistingajax" post_type="hp_listing" posts_per_page="6" taxonomy_terms="'.$taxTerms.'" taxonomy="hp_listing_category"  taxonomy_operator="IN"]</p>';
        return $out;      
      */
      //not implemented 
      return "";
    }
    function createCatPages() {
        global $hpPrice;
        global $hpBrand;
        $mKeywords = $this->keywords;

        if (Settings:: loadSetting("language", "site") == "cs") {
            $text[1] = "Nakupujte online";
            $text[2] = "s obrovským výběrem produktů a neuvěřitelnými cenami";
            $text[3] = "Od známých značek jako třeba";
            $text[4] = "a mnoho dalších položek";
            $text[5] = "jako například";
            $text[6] = "za méně než ";
            $text[7] = ",- Kč";
        }
            else {
            $text[1] = "Online shopping for";
            $text[2] = "with great number of products and special prices";
            $text[3] = "Offered by famous brands like";
            $text[4] = "and many more products";
            $text[5] = "such as";
            $text[6] = "under $";
            $text[7] = "";
        }
        //vzit kategorie, ktery maji pres 100 postu
        $createdCatPages = 0;
        $terms = $this->getCategories();
        for ($n = 0; $n < count($terms); $n++) {
            $posts_array = $this->getPostsByCategory($terms[$n]["slug"],false);
            $cntPosts = count($posts_array);

            if (is_array($mKeywords)) {
                $obsahujeKW = 0;
                $hledejKW = 1;
                for ($y = 0; $y < count($mKeywords); $y++) {
                    if (strpos(" ".strtolower($terms[$n]["name"]), strtolower($mKeywords[$y])) > 0) $obsahujeKW = 1;
                }
            }

            if (!$terms[$n]["parent"] && $createdCatPages < 10 && $cntPosts > 200 && ((empty($hledejKW) || ($hledejKW && $obsahujeKW) ))) {
                echo "<br />cnt2: ".$terms[$n]["parent"].", ".$cntPosts." slug:".$terms[$n]["slug"]."-";
                //vytvorime stranky
                $cenaNejnizsi = 0;
                $cenaNejvyssi = 0;
                $prumer = 0;
                for ($y = 0; $y < $cntPosts; $y++) {
                    $postId = $posts_array[$y]["ID"];
                    $cenaArr = get_post_meta($postId, "price");
                    $cena = $cenaArr[0];
                    $cenyArr[$y] = $cena;
                    $prumer += $cena;
                    if ($cena < $cenaNejnizsi || $cenaNejnizsi == 0) $cenaNejnizsi = $cena;
                    if ($cena > $cenaNejvyssi || $cenaNejvyssi == 0) $cenaNejvyssi = $cena;
                }

                if ($cntPosts > 0) $prumer = $prumer / $cntPosts;
                $diff = $cenaNejvyssi - $cenaNejnizsi;

                $cenaArrB = array();
                $cenaArrB[0] = round($cenaNejnizsi + $diff / 4);
                $cenaArrB[1] = round($cenaNejnizsi + 2 * $diff / 4);
                $cenaArrB[2] = round($cenaNejnizsi + 3 * $diff / 4);
                $cenaArrB = $this->mGoRound($cenaArrB);

                $cntTitles = array();
                $postsTitles = array();
                $vendors = array();

                for ($y = 0; $y < $cntPosts; $y++) {
                    $cena = $cenyArr[$y];
                    for ($c = 0; $c < count($cenaArrB); $c++) {
                        $postsTitles[$c]="";
                        $cntTitles[$c]=0;
                        $vendors[$c]=array();
                    }
                    for ($c = 0; $c < count($cenaArrB); $c++) {
                        if ($cena < $cenaArrB[$c] && (empty($cntTitles[$c]) || $cntTitles[$c] < 5)) {
                            if ($postsTitles[$c]) $postsTitles[$c].= ", ";
                            $postsTitles[$c].= $posts_array[$y]["post_title"];
                            $cntTitles[$c]++;
                            $vendorArr = get_post_meta($postId, "brand");
                            $vendor = $vendorArr[0];
                            if ($vendor) {
                                if (!in_array($vendor, $vendors[$c])) array_push($vendors[$c], $vendor);
                            }
                        }
                    }
                }


                //$ajaxMore=getAjaxMore($catId,$taxTerms,$priceUnder=0);
                $ajaxMore = $this->getAjaxMore($terms[$n]["id"], $terms[$n]["slug"]);
                $parentTitle = $terms[$n]["path"];
                $post_details = array(
                    'post_title'    => $parentTitle,
                    'post_content'  => "<p>".$text[1]." ".$terms[$n]["path"]." ".$text[2].".</p>".$ajaxMore,
                    'post_status'   => 'publish',
                    'post_author'   => 1,
                    'post_type' => 'page'
                );
                $parentExistuje = get_page_by_title($parentTitle);
                if ($parentExistuje) {
                    echo "<br />stranka uz existuje, preskakuju";
                    continue;
                }
                else {
                    echo "<br />stranka neexistuje, vytvarim";
                }

                $parentId = wp_insert_post($post_details);
                $createdCatPages++;
                //insertSpecPagesInfo($parentId,$parentTitle,0,$cntPosts);

                for ($c = 0; $c < count($cenaArrB); $c++) {
                    $brandy = "";
                    for ($b = 0; $b < count($vendors[$c]); $b++) {
                        if ($b > 0) $brandy.= ", ";
                        $brandy.= $vendors[$c][$b];
                    }
                    if ($brandy) $brandy = ". ".$text[3]." $brandy.";
                    $under = " ".$text[6].$cenaArrB[$c].$text[7];
                    $pageTitle = $terms[$n]["path"].$under;
                    $priceFrom = 0;
                    if ($c > 0) $priceFrom = $cenaArrB[$c - 1];
                    $priceTo = $cenaArrB[$c];
                    $ajaxMore = $this->getAjaxMore($terms[$n]["id"], $terms[$n]["slug"], $priceFrom, $priceTo);
                    if ($postsTitles[$c]) $postsTitles[$c] = "<p><strong>".$terms[$n]["path"]."</strong> $brandy ".$text[5]." ".$postsTitles[$c]." ..".$text[4].$under."</p>".$ajaxMore;
                    $post_details = array(
                        'post_title'    => $pageTitle,
                        'post_content'  => $postsTitles[$c],
                        'post_status'   => 'publish',
                        'post_author'   => 1,
                        'post_parent'   => $parentId,
                        'post_type' => 'page'
                    );
                    $childId = wp_insert_post($post_details);
                    //insertSpecPagesInfo($childId,$pageTitle,$parentId,$cntPosts);
                }
            }
        }
        //vzit ceny
        echo "done createcatpages";
    }
    function getCatPathNice($path="") {
        if (!$path) { 
            if (!empty($this->currentCat["path"])) $path=$this->currentCat["path"];
        }
        return str_replace(">", "- ",$path);
    }
    function getThisCat() {                
        if (empty($this->currentCat)) { 
            $cjCat=get_query_var("mikcat");
            if ($cjCat) $this->getCatBySlug($cjCat);            
        }                
        if (!empty($this->currentCat["slug"])) return $this->currentCat["slug"];
        else return false;
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
    function showBrandyNav($metaName) {                
        $mikBrand=urlDecode(get_query_var("mikbrand"));
        $catSlug=$this->getThisCat();
        $brandyArr=$this->getCatMeta($catSlug,$metaName,false);
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