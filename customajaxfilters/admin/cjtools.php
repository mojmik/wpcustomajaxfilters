<?php
namespace CustomAjaxFilters\Admin;
use \CustomAjaxFilters\Majax\MajaxWP as MajaxWP;

class CJtools {
    private $params;
    private $customPostType;
    public function __construct($customPostType) {  
        $this->customPostType=$customPostType;
    }

    public function setParam($name,$val) {
		$this->params[$name]=$val;
		return $this;
    }	
    
    public function updateImage($content,$id) {
        preg_match('/< *img[^>]*src *= *["\']?([^"\']*)/i', $content, $matches);	
        $imgSrc=$matches[1];
        $content=str_replace($imgSrc,"/mimgtools.php?id=$id",$content);
        $content=str_replace("'","''",$content);
        $fn=wp_get_upload_dir()["basedir"]."/mimgnfo-$id";
        file_put_contents($fn,$imgSrc); 
        return $content;
    }
    private function removeExtraSpaces($c) {
		$c=preg_replace('!\s+!', ' ', $c);
		$c=trim($c);
		return $c;
    }
    function mReplText($description) {
        $repl=[
            "krmivo"=>"žrádlo",
            "krmiva"=>"žrádlo",
            "pes "=>"pejsek ",
            "psy "=>"pejsky ",
            "psi "=>"pejsci ",
            "Psy"=>"Pejsky",
            "Pes"=>"Pejsek",
            "Psi"=>"Pejsci",
            "kočka "=>"kočička ",
            "kočky "=>"kočičky ",
            "Kočka"=>"Kočička",
            "Kočky"=>"Kočičky",
            "kočce "=>"kočičce ",
            "koťata "=>"koťátka ",
            "Koťáta "=>"Koťátka ",
            "malá plemena"=>"malé rasy",
            "plemen "=>"ras ",          
            "mohou "=>"můžou ",
            "surovin "=>"látek ",
            "surovin."=>"látek.",
            "jídlo "=>"krmivo ",
            "procento "=>"% ",
            "procento "=>"% ",
            ".S"=>". S",
            " A "=>" a ",
            ".P"=>". P",
            ".O"=>". O",
            ".U"=>". U",
            ".N"=>". N",
            ".C"=>". C",
            ".R"=>". R",
            ",s"=>", s",
            ",p"=>", p",
            ",o"=>", o",
            ",u"=>", u",
            ",n"=>", n",
            ",c"=>", c",
            ",r"=>", r"
        ];
        foreach ($repl as $s => $r) {
            $description=str_replace($s,$r,$description);
        }
        return $description; 
    }
    function ms_escape_string($data) {
        if ( !isset($data) or empty($data) ) return '';
        if ( is_numeric($data) ) return $data;

        $non_displayables = array(
            '/%0[0-8bcef]/',            // url encoded 00-08, 11, 12, 14, 15
            '/%1[0-9a-f]/',             // url encoded 16-31
            '/[\x00-\x08]/',            // 00-08
            '/\x0b/',                   // 11
            '/\x0c/',                   // 12
            '/[\x0e-\x1f]/'             // 14-31
        );
        foreach ( $non_displayables as $regex )
            $data = preg_replace( $regex, '', $data );
        $data = str_replace("'", "''", $data );
        return $data;
    }

    private function importReplacements($r) {
        $key="title";
        $r[$key]=$this->mReplText($r[$key]);
        $r[$key]=str_replace("<br />","&nbsp;",$r[$key]);
        $r[$key]=strip_tags($r[$key]);

        $titleSafe=str_replace("'","&#8242;",$r[$key]);

        $key="brand";
        $r[$key]=$this->mReplText($r[$key]);

        $key="description";
        $r[$key]=$this->mReplText($r[$key]);
        $r[$key]=str_replace("</li><li>","&nbsp;",$r[$key]);
        $r[$key]=str_replace("<br />","&nbsp;",$r[$key]);
        $r[$key]=str_replace("<br>","&nbsp;",$r[$key]);
        $r[$key]=strip_tags($r[$key]);
        $r[$key]=$this->ms_escape_string($r[$key]);
        $r[$key]=str_replace(", ",",",$r[$key]); //oprava carek
        $r[$key]=str_replace(",",", ",$r[$key]); //oprava carek  

        $buyurl=$r["buyurl"];

        $key="imageurl";      
        $r[$key]=str_replace("http://","https://",$r[$key]);

        $addDesc="<a title='".$titleSafe."' href='$buyurl' rel='nofollow'><img alt='".$titleSafe."' width=300 src='{$r["imageurl"]}' /></a><br />";
        $r[$key]=$addDesc.$r[$key];

        $key="price";      
        $r[$key]=str_replace(" CZK","",$r[$key]);
        $r[$key]=str_replace(" USD","",$r[$key]); 
        return $r;
    }
    public function createPostsFromTable($fields,$from=null,$to=null,$extras=[]) {
		global $wpdb;	
		$table=$this->params["tableName"];		
		$limit="";
		if (!empty($to)) {
			if (!$from) $from="0";
			$limit="LIMIT ".$from.",".($to-$from);
		}
		$results = $wpdb->get_results("SELECT * FROM $table $limit", ARRAY_A);	

		//get columns of csvtab
		//get columns of wp_mauta_fields
		$terms=[];
		foreach ($results as $r) {
            //create post
                $r=$this->importReplacements($r);

                $postArr=[];
                $postArr["post_title"]=$r["title"];
                $postArr["post_status"]="publish";
                $postArr["post_content"]=$r["description"];
				$postArr["post_type"]=$this->customPostType;
				$postId=wp_insert_post($postArr);
				echo json_encode(["result"=>"inserted {$postArr["post_title"]} $postId"]).PHP_EOL;
                
                                
				//all mauta_fields detected in csvtab are loaded
				foreach ($fields as $f) {
					$name=$f->name;
					$title=$f->title;
					$metaValue=$r[$title];
                    $createMeta=true;
                    
					//extras-apply filters like trim to all values
					if (!empty($extras[$name])) {
						foreach ($extras[$name] as $operation) {							
								if (!empty($operation["removeExtraSpaces"])) $metaValue=$this->removeExtraSpaces($metaValue);							
								if (!empty($operation["createSlug"])) { 
									$metaValue=$this->removeExtraSpaces($metaValue);
									$metaValueSlug=sanitize_title(str_replace(">","-",$metaValue));
									add_post_meta($postId,$operation["createSlug"],$metaValueSlug);
									$terms[$metaValueSlug]=$metaValue;
								}
						}
					}
					if (isset($metaValue) && $createMeta) {						
						add_post_meta($postId,$name,$metaValue);
					}
				}
										
			
		}

		//create category table 
		if (!empty($this->params["cjCatsTempTable"])) {			
			foreach ($terms as $term => $value) {
				//$row=["slug" => $term, "name" => $value, "postType" => $this->customPostType];
				$row=["name" => $value, "postType" => $this->customPostType];
				MajaxWP\MikDb::insertRow($this->params["cjCatsTempTable"],$row);
			}
		}
		
    }
    function getChildCategories($parentId) {
        return MajaxWP\MikDb::wpdbGetRows($this->params["cjCatsTable"],["id","path","counts"],[["name"=>"parent", "value" => $parentId ]]);
    }
    function getCatById($id) {
        $cats=MajaxWP\MikDb::wpdbGetRows($this->params["cjCatsTable"],"*",[["name"=>"id", "value" => $id ]]);    
        return $cats[0];
    }
    function getCatBySlug($slug) {
        $cats=MajaxWP\MikDb::wpdbGetRows($this->params["cjCatsTable"],"*",[["name"=>"slug", "value" => $slug ]]);    
        return $cats[0];
    }
    function getCats() {
        $cats=MajaxWP\MikDb::wpdbGetRows($this->params["cjCatsTable"],"*",[["name"=>"postType", "value" => $this->customPostType ]]);    
        return $cats;        
    }
    function getCatMeta($catPath,$exact=true) {
        $prefix=MajaxWP\MikDb::getWPprefix();		
        global $wpdb;
        $catMetaName=$this->params["catSlugMetaName"];
        if (!$exact) $catPath.="%";
        $query="
        SELECT post_title,pm2.meta_value,pm2.meta_id  
            FROM {$prefix}posts po LEFT JOIN {$prefix}postmeta pm1 ON ( pm1.post_id = ID),LEFT JOIN {$prefix}postmeta pm2 ON ( pm2.post_id = ID)  
                WHERE 
                    po.ID=pm1.post_id 
                    AND  po.ID=pm2.post_id
                    AND po.post_status like 'publish' 
                    AND po.post_type like '{$this->customPostType}' 
                    AND pm1.meta_key = '{$catMetaName}' 
                    AND pm1.meta_value LIKE '{$catPath}'
                    AND pm2.meta_key = 'brand'
         ";             
      return $wpdb->get_results($query,ARRAY_A);

      
    }
    function getPostsByCategory($catPath,$exact=true) {
        $prefix=MajaxWP\MikDb::getWPprefix();		
        global $wpdb;
        $catMetaName=$this->params["catSlugMetaName"];
        if (!$exact) $catPath.="%";
        $query="
        SELECT post_title,ID 
             FROM {$prefix}posts po LEFT JOIN {$prefix}postmeta pm1 ON ( pm1.post_id = ID) 
             WHERE po.ID=pm1.post_id
             AND po.post_status like 'publish' 
             AND po.post_type like '{$this->customPostType}' 
             AND pm1.meta_key = '{$catMetaName}' 
             AND pm1.meta_value LIKE '{$catPath}'
         ";             
      return $wpdb->get_results($query,ARRAY_A);
    }
    function getLngText($txt) {
 	    $isCZ=true;
        if ($isCZ) {
         if ($txt=="Search") return "Hledat";
         if ($txt=="GET SPECIAL DISCOUNT »") return "KOUPIT SE SLEVOU »"; 
         if ($txt=="More than") return "Více než";
         if ($txt=="great offers in category") return "skvělých nabídek v kategorii";
         if ($txt=="products") return "položek";
         if ($txt=="Enjoy") return "Užijte si";
         if ($txt=="Find") return "Najděte";
         if ($txt=="Choose- ") return "Vyberte si- ";
         if ($txt=="Shop- ") return "Nakupujte- ";
         if ($txt=="Eshops with") return "Eshopy nabízející";
         if ($txt=="Shops with") return "Obchody, kde najdete ";
         if ($txt=="more than") return "více než";
         if ($txt=="over") return "víc jak";
         if ($txt=="products") return "produktů";
         if ($txt=="offers") return "nabídek";
         if ($txt=="sales") return "zlevněných položek";
         if ($txt=="in") return "v";
         if ($txt=="by") return "od";
         if ($txt=="Also") return "Dále třeba";
         if ($txt=="and other brands") return "a dalších značek";
         if ($txt=="and other producers") return "a dalších výrobců";
         
        }
        else return $txt; 
    }
    function mRndTxt($texts) {
        return $this->getLngText($texts[array_rand($texts)]); 
    }
    function updateCatsDescription() {
        $cats=$this->getCats();
        $cnt=count($cats);
        foreach ($cats as $c) {
            $desc=$this->prepareCatDescription($c);
            MajaxWP\MikDb::wpdbUpdateRows($this->params["cjCatsTable"],[["name" => "desc", "value" => $desc]],[["name"=>"id","type" =>"%d", "value" => $c["id"] ]]);
        }
        return "cats description updated";
    }   
    function sanitizeSlug($slug) {
        $separator=(empty($this->params["catSep"])) ? ">" : $this->params["catSep"];    
        return sanitize_title(str_replace($separator,"-",$slug));
    }  
    function prepareCatDescription($cat) {
            $desc="#enjoymorethan#cntPosts#txtproducts#cats#txtby#brands.#prods#txtAlso#subcats.";
             
            $txtproducts="";
            $enjoy="";
            $txtby="";	 
            $subCatStr="";
      
              $subcats = $this->getChildCategories($cat["id"]);
              $cntSubCats=count($subcats);	
              $n=0;
              if ($cntSubCats > 3) $cntSubCats=3;
              foreach ($subcats as $subcat) {
               if ($n<$cntSubCats && ($subcat["id"] != $cat["id"])) {
                if ($subCatStr) { 
                 $subCatStr.=", "; 
                } 
                $subCatStr.="".$subcat["path"];
                $n++;
               } 		 
              }
              unset($subcats);
              if ($subCatStr) {
               $txtAlso =" ".$this->getLngText("Also");
               $subCatStr =" ".$subCatStr;	
              }
              else {
               $txtAlso = "";	
              }
              $desc=str_replace("#txtAlso",$txtAlso,$desc);    
              $desc=str_replace("#subcats",$subCatStr,$desc);
             
             $termPosts=$this->getPostsByCategory($cat["slug"],false);
                                                      
              $brandyArr=array();
              $prodNames=array();
              $brandyStr="";
              $prodStr="";
              $cntPosts=0;
              $catParent="";
              $subCatStr="";
              
              foreach ($termPosts as $mPost) {                 
               $brand=get_post_meta($mPost["ID"],"brand");
               $prodName=$mPost["post_title"];
               if (!in_array($brand[0],$brandyArr)) array_push($brandyArr,$brand[0]);      
               if (!in_array($prodName,$prodNames)) array_push($prodNames,$prodName);
               $cntPosts++;	
              }
              //file_put_contents(get_template_directory()."/adfeed.log"," updatetermsdesc2 cnt:$cntPosts ".$cat["path"]." proc:$d/$y now: ".getDateTime()." \n",FILE_APPEND);	
              unset($termPosts);
              
             $enjoy="";
             $enjoy=$this->mRndTxt(array("Enjoy","Find","Choose- ","Shop- ","Eshops with","Shops with"));
             
             if ($cntPosts>19) {
              if (strlen($cntPosts)>1) $cntPosts=str_pad(substr($cntPosts,0,1),strlen($cntPosts),"0");	    
              $cntPosts=" ".$cntPosts;
              $enjoy.=" ".$this->mRndTxt(array("more than","over"));
              $txtproducts=" ".$this->mRndTxt(array("products","offers","sales"));	   	   	   
             }	
             else {
               $cntPosts="";
               $txtproducts="";
             }
             $desc=str_replace("#cntPosts",$cntPosts,$desc);			   
             $desc=str_replace("#enjoymorethan",$enjoy,$desc);
             
             
              $desc=str_replace("#txtproducts",$txtproducts,$desc);
              shuffle($brandyArr);
              $cntBrandy=count($brandyArr);				
              if ($cntBrandy > 3) $cntBrandy=3;						
              for ($n=0;$n<$cntBrandy;$n++) {
               if ($brandyStr) $brandyStr.=", "; 
               $brandyStr.=$brandyArr[$n];
              }
              if ($brandyStr) { 
               $brandyStr=" ".$brandyStr." ".$this->mRndTxt(array("and other brands","and other producers"));
               $txtby=", ".$this->getLngText("by");	     
              }
              else {		
                $txtby="";
                $brandyStr="";
              }
              $desc=str_replace("#txtby",$txtby,$desc); 
              
              $desc=str_replace("#brands",$brandyStr,$desc);
              
              $cntProd=count($prodNames);
              shuffle($prodNames);
              if ($cntProd > 3) $cntProd=3;
              for ($n=0;$n<$cntProd;$n++) {
               if ($prodStr) $prodStr.=", "; 
               $prodStr.=$prodNames[$n];
              }
              if ($prodStr) {
                $prodStr=" ".$prodStr.".";
              }
              $desc=str_replace("#prods",$prodStr,$desc);
              
              
              if ($cat["parent"]) {
                   //echo "<h3>".($cat->parent)."</h3>";
                  $parentTerm=$this->getCatById( $cat["parent"]);
                  $catParent=$parentTerm["path"]." - ";
                  $kats=' "'.$catParent.$cat["path"].'"';
              }
              else {
                  $kats=' "'.$cat["path"].'"';
              }
              $desc=str_replace("#cats",$kats,$desc);		
              //$result=wp_update_term($cat["id"], $taxonomy, array('description' => $desc));
              return $desc;
       }
}