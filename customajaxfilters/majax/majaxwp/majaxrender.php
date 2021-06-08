<?php
namespace CustomAjaxFilters\Majax\MajaxWP;
use \CustomAjaxFilters\Admin as MajaxAdmin;

use stdClass;

Class MajaxRender {		
	private $postType;
	private $htmlElements;
	private $subType;
	private $siteSettings;
	private $translating;
	private $postRowsLimit; //rows limit (static show)
	private $additionalFilters;
	private $fixFilters;
	private $totalPages;

	function __construct($preparePost=false,$atts=[]) {			
			$this->siteSettings["language"]="";				
			if (!empty($atts)) { 
				if (isset($atts["type"])) $this->setPostType($atts["type"]);
				if (isset($atts["typ"])) $this->subType=$atts["typ"];	
				if (isset($atts["language"])) $this->siteSettings["language"]=$atts["language"];							
			} 
			if (empty($this->siteSettings["language"])) $this->siteSettings["language"]=MajaxAdmin\Settings::loadSetting("language","site");
			if (empty($this->siteSettings["clickAction"])) $this->siteSettings["clickAction"]=MajaxAdmin\Settings::loadSetting("clickAction","site");
			$this->translating=new Translating($this->siteSettings["language"]);			
			$this->htmlElements=new MajaxHtmlElements($this->translating);			
			$this->htmlElements->setPostType($this->postType);
			//init custom fields			
			if ($preparePost) { 
				$this->loadFields();
			}
			$this->postRowsLimit=9;
			$this->additionalFilters=[];			
			$this->fixFilters=[];
	}

		
	public function getRowsLimit() {
		return $this->postRowsLimit;
	}
		
	function loadFields() {
		//$this->logWrite("cpt!@ {$this->getPostType()}");
		$this->fields=new CustomFields();
		$this->fields->prepare($this->getPostType());
		$this->fields->loadPostedValues();			
		ImageCache::loadImageCache($this->getPostType());		
	}
	function getPostType() {			
		return $this->postType; 	
	}
	function setPostType($cpt="") {		
		if ($cpt) $this->postType=$cpt;	
		else $this->postType=filter_var($_POST['type'], FILTER_SANITIZE_STRING); 	
		Caching::setPostType($this->getPostType());		
	}

	
	
	function showStaticContent($atts = []) {		
		//[majaxstaticcontent type="cj" cj="1"]
		$this->loadFields();				
		if (isset($this->subType)) { 		 
			$this->fields->setFixFilter("mauta_typ",$this->subType);					
		}
		$postId=isset($_GET['id']) ? filter_var($_GET['id'], FILTER_SANITIZE_STRING) : "";
		$aktPage=filter_input( INPUT_GET, "aktPage", FILTER_SANITIZE_NUMBER_INT );
		$showCjCat=false;
		if (!empty($atts["cj"])) {
			$cj=new MajaxAdmin\ComissionJunction(["postType" => $this->postType]);
			$cjBrand=urlDecode(get_query_var("mikbrand"));
			$cjCat=get_query_var("mikcat");
	
			$exactCategoryMatch="%";
			//ted jeste aby to filtrovalo i podle brandu			
			if (!$postId) {
				if ($cjCat) { 
					$this->fixFilters[]=["name" =>  $cj->getTypeSlug(), "filter" => $cjCat.$exactCategoryMatch];
					
					$thisCat=$cj->getCjTools()->getCatBySlug($cjCat);
					$desc=$thisCat["desc"];
					$cntRows=$thisCat["counts"];
					$showCjCat=true;
					
				}
				if ($cjBrand) {
					$this->fixFilters[]=["name" =>  $cj->getMautaFieldName("brand"), "filter" => $cjBrand];	
					$this->additionalFilters[]="brand";
				}
			}			
			/*
			echo "<br />brand: ".urlDecode(get_query_var("mikbrand"));
			echo "<br />cat: ".get_query_var("mikcat");
			*/
			//tohle pujde do widgetu
			//echo $cj->getCjTools()->showBrandyNav();
		} 
		$this->htmlElements->showMainPlaceHolderStatic(true,$this->postType);
		
		//get results
		if ($postId) { 
			$query=$this->produceSQL($postId);
			$this->htmlElements->showIdSign();
		}
		else $query=$this->produceSQL(null,$aktPage*$this->postRowsLimit);
		$rows=Caching::getCachedRows($query);	

		//tady se berou cntRows pro celou kategorii, ale muzou tabm byt treba jeste filtry na brand, takze je to potreba spocitat
		if (!$postId && (empty($cntRows) || (count($this->additionalFilters)>0))) { 
			//$cntRows=count($rows);
			$query=$this->produceSQL(null,null,true);	
			$rowsCount=Caching::getCachedRows($query);
			$cntRows=$rowsCount[0]["cnt"];
		}
		if ($postId) $cntRows=count($rows);
		$excerpt=($cntRows>1) ? true : false;
		if ($cntRows>1) $templateName="multi";
		else $templateName="single";

		if ($cntRows>1 && $showCjCat) {
			echo $this->htmlElements->getHtml("cat-header","cat",["desc" => $desc],true);
		}
		$n=0;

		

		foreach ($rows as $row) {			
			$n++;
			$metaMisc=$this->buildInit();
			$item=[];
			$item=$this->buildItem($row,[],0,$excerpt);		
			$this->htmlElements->showPost("s$n",$row["post_name"],$row["post_title"],$item["image"],$item["content"],$metaMisc["misc"],$item["meta"],$templateName);
		}
		if ($cntRows>$this->postRowsLimit) { 			 
			 $this->htmlElements->showPagination($this->getPagination($cntRows,$aktPage,$this->postRowsLimit));			 
		} else {
			$this->totalPages=1;
		}
		if ($cntRows<1) {
			$html=$this->htmlElements->getHtml("noluck","post",[],true);
			echo $html;
		}		
		$this->htmlElements->showMainPlaceHolderStatic(false);		
		$this->htmlElements->showFixFields($this->fixFilters);
		$this->initVals=[];
		$this->initVals[]=["name" => "totalPages", "val" => $this->totalPages];
		$this->initVals[]=["name" => "totalRows", "val" => $cntRows];
		$this->initVals[]=["name" => "staticPages", "val" => "1"];
		$this->htmlElements->showInitValsForAjax($this->initVals);
	}
	function showStaticForm($atts = []) {								
		$mForm=new MajaxForm($this->getPostType());		
		$this->htmlElements->showMainPlaceHolderStatic(true,$this->getPostType());
		$this->htmlElements->getHtml("contactForm","form",["title"=>"contact title","type" => "dotaz"]);
		//$mForm->printForm("majaxContactForm",$title);
		$this->htmlElements->showMainPlaceHolderStatic(false);
	}
	function showFormFilled($templateName) {
		$mForm=new MajaxForm($this->getPostType());
		$mForm->setTemplate($this->htmlElements,"contactFormMessage");
		echo json_encode($mForm->runForm()).PHP_EOL;
	}
	function showFormFields($type) {
		$mForm=new MajaxForm($type);
		echo json_encode($mForm->renderForm()).PHP_EOL;			
	}
	
	function printFilters($atts = []) {		
		 $this->loadFields();
		//prints filter, run by shortcode majaxfilter					
		 $this->htmlElements->showFilters($this->postType,$this->fields->getFieldsFiltered());		 		 
	}	
	function printContent($atts = []) {	
		//prints content, run by shortcode majaxcontent		
		if (!empty($atts["showSomePostsForStart"]) && $atts["showSomePostsForStart"])	{
			$this->showStaticContent($atts);
		}	
		else $this->htmlElements->showMainPlaceHolder();		
	}	
	function addToStr($sep,$add,$str) {
		if ($str) $str.=$sep.$add;
		else $str=$add;
		return $str;
	}
	function produceSQL($id=null,$from=null,$countOnly=false,$postAll=false) {
		$mType = $this->getPostType();
		$col="";
		$filters="";
		$colSelect="";
		$this->fields->setFixFilters($this->fixFilters);
		foreach ($this->fields->getFieldsFilteredOrDisplayed() as $field) {			
			$fieldName=$field->outName();		
			$col.=$this->getSqlFilterMeta($fieldName);
			$colSelect.=$this->getSqlSelectMeta($fieldName);
			$filter=$field->getFieldFilterSQL();
			if ($filter && !$field->typeIs("select")) {
				$filters=$this->addToStr(" AND ",$filter,$filters);				
			}
			if (strpos($fieldName,"cena")!==false) { 
				$orderBy="cast(pm1.`".$fieldName."` AS unsigned)";
				$orderDir='ASC';
			}
		}
		if (empty($orderBy)) { 
			$orderBy="post_title";
			$orderDir="ASC";
		}
		$additionalMetas=["_thumbnail_id"];
		foreach ($additionalMetas as $fieldName) {			
			$col.=$this->getSqlFilterMeta($fieldName);
			$colSelect.=$this->getSqlSelectMeta($fieldName);
		}

		if ($id) $filters=$this->addToStr(" AND ","post_name like '$id'",$filters);
		if ($filters) $filters=" WHERE $filters";	
		$limit="";	
		if (!$postAll) {
		 if ($from) $limit=" LIMIT $from,".$this->postRowsLimit;	
		 else $limit=" LIMIT ".$this->postRowsLimit;	
		}
		//customSearch
		$customSearch="";
		if (!empty($_GET['mSearch'])) {
			$this->additionalFilters[]="mSearch";
			$contentSearch=filter_var($_GET['mSearch'], FILTER_SANITIZE_STRING); 	
			if ($contentSearch) $customSearch=" AND post_content like '%$contentSearch%' ";
		}
		if ($countOnly) {
			$query="
			SELECT count(*) as cnt  FROM
			(SELECT post_title,post_name,post_content 
				$col
				FROM wp_posts LEFT JOIN wp_postmeta pm1 ON ( pm1.post_id = ID) 
				WHERE
				post_status like 'publish' 
				AND post_type like '$mType'		
				$customSearch	
				GROUP BY ID
				) AS pm1
				$filters
				ORDER BY $orderBy $orderDir
			";
		} else {
			$query=
			"
			SELECT post_title,post_name,post_content{$colSelect}  FROM
			(SELECT post_title,post_name,post_content 
				$col
				FROM wp_posts LEFT JOIN wp_postmeta pm1 ON ( pm1.post_id = ID) 
				WHERE 
				post_status like 'publish' 
				AND post_type like '$mType'		
				$customSearch	
				GROUP BY ID
				) AS pm1
				$filters
				ORDER BY $orderBy $orderDir
				$limit
			";
		}
		
		return $query;
	}
	function produceSQLWithAttachments($id="") {
		//grabs post,metas and external related tables (attachments)
		$mType = $this->getPostType();
		$col="";
		$filters="";
		$colSelect="";

		foreach ($this->fields->getFieldsFilteredOrDisplayed() as $field) {			
			$fieldName=$field->outName();		
			$col.=$this->getSqlFilterMeta($fieldName);
			$colSelect.=$this->getSqlSelectMeta($fieldName);
			$filter=$field->getFieldFilterSQL();
			if ($filter && !$field->typeIs("select")) {
				$filters=$this->addToStr(" AND ",$filter,$filters);				
			}
			if (strpos($fieldName,"cena")!==false) { 
				$orderBy="cast(pm1.`".$fieldName."` AS unsigned)";
				$orderDir='ASC';
			}
		}
		if (!$orderBy) { 
			$orderBy="post_title";
			$orderDir="ASC";
		}
		$additionalMetas=["_thumbnail_id"];
		foreach ($additionalMetas as $fieldName) {			
			$col.=$this->getSqlFilterMeta($fieldName);
			$colSelect.=$this->getSqlSelectMeta($fieldName);
		}

		if ($id) $filters=$this->addToStr(" AND ","post_name like '$id'",$filters);
		if ($filters) $filters=" WHERE $filters";
		$query=
		"
		SELECT post_title,post_name,post_content{$colSelect}  FROM
		(SELECT post_title,post_name,post_content 
			$col
			FROM wp_posts LEFT JOIN wp_postmeta pm1 ON ( pm1.post_id = ID) 
			WHERE post_id=id 
			AND post_status like 'publish' 
			AND post_type like '$mType'			
			GROUP BY ID
			) AS pm1
			$filters
			ORDER BY $orderBy $orderDir
		";
		return $query;
	}
	
	function getSqlFilterMeta($fieldName) {
		return ",MAX(CASE WHEN pm1.meta_key = '$fieldName' then pm1.meta_value ELSE NULL END) as `$fieldName`";
	}
	function getSqlSelectMeta($fieldName) {
		return ",pm1.`$fieldName`";
	}
	
	function filterMetaSelects($rows) {
		$outRows=[];
		$fields=$this->fields->getFieldsOfType("select");	

		foreach ($rows as $row) {
			$skip=false;
			foreach ($fields as $field) {				
				$skip=false;
				
				if (!$field->isInSelect($row[$field->outName()])) {					
					$skip=true;
					break;
				}
				
			}
			if (!$skip) $outRows[]=$row;
		}
		return $outRows;
	}		
	
	function buildItem($row,$addFields=[],$getJson=1,$excerpt=false) {
		$ajaxItem=new MajaxItem();
		$ajaxItem->addField("title",$row["post_title"])
		->addField("name",$row["post_name"])
		->addField("content",$row["post_content"]);
		if (isset($row["ID"])) $ajaxItem->addField("id",$row["ID"]);
		if (isset($row["slug"])) $ajaxItem->addField("url",$row["slug"]);		
		$ajaxItem->addField("image",ImageCache::getImageUrlFromId($row["_thumbnail_id"]));
		if ($excerpt) $ajaxItem->shrinkField("content");
		foreach ($addFields as $key => $value) {
			$ajaxItem->addField($key,$value);
		}
		foreach ($this->fields->getFieldsDisplayed() as $field) {
		 $ajaxItem->addMeta($field->outName(),$row[$field->outName()]);
		}	

		$out=$ajaxItem->expose($getJson);
		return $out;					
	}
	function buildInit($templateName="") {
		$row=[];
		$row["title"]="buildInit";

		foreach ($this->fields->getFieldsFilteredOrDisplayed() as $field) {	
			$fieldName=$field->outName();							
			$row["misc"][$fieldName]["icon"]=$field->icon;
			$row["misc"][$fieldName]["fieldformat"]=$field->fieldformat;
			$row["misc"][$fieldName]["min"]=$field->valMin;
			$row["misc"][$fieldName]["max"]=$field->valMax;			
			$row["misc"][$fieldName]["displayorder"]=$field->displayOrder;	
			$row["misc"][$fieldName]["title"]=$field->title;	
			$row["misc"][$fieldName]["type"]=$field->type;	
			$row["misc"][$fieldName]["htmlTemplate"]=$field->htmlTemplate;	
		}

		foreach ($this->fields->getFieldsVirtual() as $field) {		
			$fieldName=$field->outName();	
			$row["misc"][$fieldName]["icon"]=$field->icon;
			$row["misc"][$fieldName]["fieldformat"]=$field->fieldformat;
			$row["misc"][$fieldName]["min"]=$field->valMin;
			$row["misc"][$fieldName]["max"]=$field->valMax;			
			$row["misc"][$fieldName]["displayorder"]=$field->displayOrder;	
			$row["misc"][$fieldName]["title"]=$field->title;	
			$row["misc"][$fieldName]["type"]=$field->type;	
			$row["misc"][$fieldName]["htmlTemplate"]=$field->htmlTemplate;	
			$row["misc"][$fieldName]["virtVal"]=$field->virtVal;	//first character .. # - clone value from other field, ! - fix value
		}
		//$row["misc"]["neco"]["virtVal"]="#mauta_cenaden"; //first character .. # - clone value from other field, ! - fix value
		//$row["misc"]["neco"]["title"]="Cena bez dph";
		if ($templateName<>"") {
			$row["htmltemplate"][$templateName]=$this->htmlElements->loadTemplate($templateName);	
			$row["htmltemplate"][$templateName]=$this->htmlElements->translateTemplate($row["htmltemplate"][$templateName]);
		}
		$row["mainClass"]="majaxOutDynamic";
		$row["totalPages"]=$this->totalPages;
		$row["language"]=$this->siteSettings["language"];
		return $row;	
	}
	function buildCounts($rows,$cachedJson) {
		$out=[];
		$c=[];	
		if ($cachedJson) {
			$out=$cachedJson;
		}
		else {
			$out[]=["meta_key" => "clearall", "meta_value" => "clearall", "count" => "0", "post_title" => "" ];
			if (!empty($rows) && count($rows)>0) {
				

				foreach ($rows as $row) {
					foreach ($this->fields->getFieldsFiltered() as $field) {			
						$val=$row[$field->outName()];
						if (empty($c[$field->outName()][$val])) $c[$field->outName()][$val]=0;
						$c[$field->outName()][$val]++;
					}	
					
				}
				foreach ($this->fields->getFieldsFiltered() as $field) {			
					$fieldName=$field->outName();		
					if (!empty($c[$fieldName])) {
						foreach ($c[$fieldName] as $val => $cnt) {	
							//$this->logWrite("iter:{$fieldName} {$val} {$cnt} ");				
								$m["meta_key"]=$fieldName;
								$m["meta_value"]=$val;
								$m["count"]=$cnt;
								$m["post_title"]="counts";
								$out[]=$m;
						}
					}				
				}	
			}
			
			$out[]=["meta_key" => "endall", "meta_value" => "endall", "count" => "0", "post_title" => "" ];
	
			$this->logWrite("json out:".json_encode($out));
		}		
		return $out;
	}
	function getPagination($cntTotal,$aktPage,$cntPerPage) {
		$row=[];
		$row["title"]="pagination";
		$pages=ceil($cntTotal/$cntPerPage);				
		$this->totalPages=$pages;
		if ($pages<=0) return $row;
		for ($n=0;$n<$pages;$n++) {			
			if ($n==$aktPage) $row[$n] = "2";
			else $row[$n] = "1";
		}		
		return $row;
	}	
	function showRows($rows,$delayBetweenPostsSeconds=0.5,$custTitle="",$limit=9,$aktPage=0,$miscAction="",$sliceArray=false) {
		$n=0;	
		$totalRows=count($rows);
		$showPosts=true;
		if ($miscAction=="contactFilled") $showPosts=false;
		$templateName="single"; //default template name
		$excerpt=false;
		if ($totalRows>1 || $aktPage>0) { 
			$templateName="multi";
			$excerpt=true;
		}

		if ($custTitle != "majaxcounts") {
			if ($totalRows<1)	 {
				$this->sendBlankResponse();
			}
			$pagination=$this->getPagination($totalRows,$aktPage,$limit);
			if ($sliceArray) $rows=array_slice($rows,$aktPage*$limit,$limit);		
		}
		
		foreach ($rows as $row) {
			if ($custTitle=="majaxcounts") { 
				$row["title"]=$custTitle;
				$this->logWrite("countitem ".json_encode($row));
				echo json_encode($row).PHP_EOL;								
			}
			else {
				 if ($n==0) { //first row
					 //buildinit - fields description and html template for posts
					echo json_encode($this->buildInit($templateName)).PHP_EOL;						
					if ($miscAction) { 
						if ($this->siteSettings["clickAction"]=="form") {
							//send form
							$mForm=new MajaxForm($this->getPostType(),$row["post_title"],$this->translating);
							if ($miscAction=="action") {
								$mForm->setTemplate($this->htmlElements,"defaultForm","form");								
								echo json_encode($mForm->renderForm()).PHP_EOL;	
							}
							if ($miscAction=="contactFilled") {	
								$mForm->setTemplate($this->htmlElements,"contactFormMessage");													
								echo json_encode($mForm->runForm()).PHP_EOL;	
							}						
						}						
					}					
				 }
				 if ($showPosts) {
					echo $this->buildItem($row,["templateName"=>$templateName],1,$excerpt).PHP_EOL;					
				 }
				 
				 if ($n==count($rows)-1) { 
					 //last row
					echo json_encode($pagination).PHP_EOL;						
				 }
				 
			} 
			flush();
			ob_flush();
			session_write_close();
			if ($delayBetweenPostsSeconds>0) usleep($delayBetweenPostsSeconds*1000000);	
			$n++;
		}	
		//exit;	
	}
	private function createResponse() {
		$response=new StdClass();
		return $response;
	}

	function sendBlankResponse() {
		$response=$this->createResponse();
		$response->title="empty";	
		$response->content="Sorry, no results.";
		echo json_encode($response).PHP_EOL;
		flush();
		ob_flush();		  
		exit;
	}
	public function showSearchBox() {
		$lastSearch=isset($_GET["mSearch"]) ? $_GET["mSearch"] : "";
		$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		//$actual_link = "http://ukeacz:8081/category/ratan-na-zahradu/";
		//$actual_link = "#";
		?>
		<form role="search" method="get" class="search-form" action="<?= $actual_link?>">
				<label>
					<span class="screen-reader-text">Vyhledávání</span>
					<input type="search" class="search-field" placeholder="Hledat …" value="<?= $lastSearch?>" name="mSearch">					
				</label>
				<input type="submit" class="search-submit" value="Hledat">
		</form>
		<?php
	}

	function logWrite($val,$file="log.txt") {
	 file_put_contents(plugin_dir_path( __FILE__ ) . $file,date("d-m-Y h:i:s")." ".$val."\n",FILE_APPEND | LOCK_EX);
	}
}