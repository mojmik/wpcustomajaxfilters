<?php
namespace CustomAjaxFilters\Majax\MajaxWP;

use stdClass;

Class MajaxWPRender {	


	function __construct($loadFields=true) {		
		
			//init custom fields
			$this->fields=new CustomFields();
						
			//kdyz uz mam nacteny pole
			$forceReload=false;
			$loadValues=false;
			if (!$loadFields || $forceReload) {	
				//preloading hardcoded fields
				$this->fields->addField(new CustomField("mauta_kategorie","vetsi;mensi;dodavky","select","Kategorie","=",false,false,"mauta"));
				$this->fields->addField(new CustomField("mauta_znacka","---;Škoda;VW;Mercedes Benz;Hyundai;FIAT;Opel;Renault","select","Znacka","=",false,false,"mauta"));
				$this->fields->addField(new CustomField("mauta_cenaden","","NUMERIC","Cena - den",">",false,false,"mauta"));
				$this->fields->addField(new CustomField("mauta_automat","","bool","Automat","=",false,false,"mauta"));
				if ($forceReload) $this->fields->saveToSQL();
				if ($loadValues) echo $this->fields->readValues();
			}
			else {
				//loading meta fields from db
				$this->fields->loadFromSQL();
			}					
	}

	function regShortCodes() {		
		add_shortcode('majaxfilter', [$this,'majax_print_filter'] );
		add_shortcode('majaxcontent', [$this,'majax_print_content'] );
	}
	
	function initFields() {
		echo $this->fields->initFields();
	}
	function majax_print_filter($atts = []) {
		 $atts = array_change_key_case( (array) $atts, CASE_LOWER );
		 if (isset($atts["type"])) $type=$atts["type"]; //we load postType from shortcode attribute		
		//prints filter, run by shortcode majaxfilter	
		ob_start();		
		?>
		<form>
			<div class='majaxfiltercontainer'>			
					<input type='hidden' name='type' value='<?= $type?>' />
				<?php		
				foreach ($this->fields->getList() as $fields) {
				  ?> <div class='majaxfilterbox'> <?php  
							echo $fields->outFieldFilter();	
				  ?> </div> <?php
				}
				?>			
			</div>
		</form>
		<?php
		 return ob_get_clean();
	}	
	function majax_print_content($atts = []) {	
		//prints content, run by shortcode majaxcontent		
		ob_start();
		?>
		<div id="majaxmain" class="majaxmain">
		 <?php
		  //ajax content comes here
		 ?>
		</div> <?php
		 return ob_get_clean();
	}
	function buildQuery() {  
	  $catSlug = $_POST['category'];
	  $mType = filter_var($_POST['type'], FILTER_SANITIZE_STRING); 	
	  $hivePress=false;
	  if ($hivePress) {
		$postTypeDefault="hp_listing";  
		$taxonomy="hp_listing_category";
	  }	  
	  
	  $metaQuery["relation"] = 'AND';
	  
	  foreach ($this->fields->getList() as $field) {
		  $filter = $field->getFieldFilter();			  
		  if ($filter) { 		
		    $metaQuery[] = $filter;
		    $this->logWrite("name: {$field->name} filter: ".$filter." - ".$_POST[$field->name]);   		   
		  } 
	  }
	
	  $wpQuery=[	
		'posts_per_page' => 8,
		'orderby' => 'menu_order', 
		'order' => 'desc',
	  ];
	  if ($catSlug) { 
	   $wpQuery["taxonomy_terms"]=$catSlug;  	   	  
	   $wpQuery["taxonomy"]=$taxonomy;  	   
	  }
	  if ($mType) { 
	    $wpQuery["post_type"]=$mType;  
	  }
	  else if ($postTypeDefault) {		
		$wpQuery["post_type"]=$postTypeDefault;    
	  }	 
	  $wpQuery["meta_query"]=$metaQuery;  
	  $this->logWrite("query: ".json_encode($wpQuery));
	  return $wpQuery;
	}
	function buildQuerySQL() {	
		//get all posts and their metas			
		$limit=" LIMIT 10";		
		$limit=""; //need all rows for counts
		$mType = filter_var($_POST['type'], FILTER_SANITIZE_STRING); 
		$col="";
		$filters="";
		$colSelect="";
		foreach ($this->fields->getList() as $field) {			
			$fieldName=$field->outName();		
			$col.=",MAX(CASE WHEN pm1.meta_key = '$fieldName' then pm1.meta_value ELSE NULL END) as $fieldName";			
			$colSelect.=",PM1.$fieldName";
			$filter=$field->getFieldFilterSQL();
			if ($filter) {
				if ($filters) $filters.=" AND ";
				$filters.=$filter;
			}

		}
		if ($filters) $filters=" WHERE $filters";
		$query=
		"
		SELECT post_title,post_content{$colSelect}  FROM
		(SELECT post_title,post_content 
			$col
			FROM wp_posts LEFT JOIN wp_postmeta pm1 ON ( pm1.post_id = ID) 
			WHERE post_id=id 
			AND post_status like 'publish' 
			AND post_type like '$mType'			
			GROUP BY ID, post_title
			) AS PM1
			$filters
			$limit
		";
		$this->logWrite("queryitem {$query}");

		return $query;
	}	
	function buildItem($row) {
		$ajaxItem=new MajaxItem();
		$ajaxItem->addField("title",$row["post_title"])->addField("id",$row["ID"])
		->addField("content",$row["post_content"])->addField("url",$row["slug"]);
		foreach ($this->fields->getList() as $field) {
		 $ajaxItem->addMeta($field->outName(),$row[$field->outName()]);
		}	
		$out=$ajaxItem->expose();
		$this->logWrite($out);
		return $out;					
	}
	function buildInit() {
		$row=[];
		$row["buildInit"]=1;

		foreach ($this->fields->getList() as $field) {								
			$row[$field->outName()]["icon"]=$field->icon;
		}
		return $row;	
	}
	function buildCounts($rows) {
		$out=[];
		$c=[];
		$out[]=["meta_key" => "clearall", "meta_value" => "clearall", "count" => "0", "post_title" => "" ];

		foreach ($rows as $row) {
			foreach ($this->fields->getList() as $field) {			
				$val=$row[$field->outName()];
				$c[$field->outName()][$val]++;
			}	
			
		}
		foreach ($this->fields->getList() as $field) {			
			$fieldName=$field->outName();						
			foreach ($c[$fieldName] as $val => $cnt) {	
				$this->logWrite("iter:{$fieldName} {$val} {$cnt} ");				
					$m["meta_key"]=$fieldName;
					$m["meta_value"]=$val;
					$m["count"]=$cnt;
					$m["post_title"]="counts";
					$out[]=$m;
			}
		}	
		
		$out[]=["meta_key" => "endall", "meta_value" => "endall", "count" => "0", "post_title" => "" ];

		$this->logWrite("count meta rows:".count($out));
		return $out;
	}
	function showRows($rows,$delayBetweenPostsSeconds=0.5,$custTitle="",$limit=10) {
		$n=0;		
		foreach ($rows as $row) {
			if ($limit>0 && $n>$limit) break;
			if ($custTitle=="majaxcounts") { 
				$row["title"]=$custTitle;
				$this->logWrite("countitem ".json_encode($row));
				echo json_encode($row).PHP_EOL;				
			}
			else {
				 if ($n==0) {
					echo json_encode($this->buildInit()).PHP_EOL;					 
				 }
				 echo $this->buildItem($row).PHP_EOL;				
				 
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
		$response->title="neco2342";	
		$response->content="neco345643";
		echo json_encode($response);
		flush();
		ob_flush();		  
		exit;
	}

	function logWrite($val) {
	 file_put_contents(plugin_dir_path( __FILE__ ) . "log.txt",date("d-m-Y h:i:s")." ".$val."\n",FILE_APPEND | LOCK_EX);
    }
    function filter_rows_continuous() {
        $delayBetweenPostsSeconds=0.5;	
        $ajaxPost=new StdClass();
        $response=new StdClass();
        //tohle natahuje data pro ajax jeden post po jednom, vraci json
                
        $ajaxposts = new \WP_Query($this->buildQuery());
        //todo seskupovani podle typu/modelu, pokud bude vic vysledku	  
        //ukazani kolik je vysledku kazde option
        if($ajaxposts->have_posts()) {
          $this->logWrite("posts found");
          while($ajaxposts->have_posts()) {
            $ajaxposts->the_post();	  
            $ajaxPost->title=get_the_title()."id:".get_the_id();
            $ajaxPost->content=get_the_title();
            $ajaxPost->url=get_the_permalink();	
            $ajaxPost->meta="transmission:".get_post_meta(get_the_id(),"mauta_automat",true)."".get_post_meta(get_the_id(),"hp_vendor",true);	
            echo json_encode($ajaxPost);
            flush();
            ob_flush();
            usleep($delayBetweenPostsSeconds*1000000);
          }
          exit;
        } else {	
          $response->title="majaxnone";
          $response->content="no results";
          $this->logWrite("no response");
          echo json_encode($response);
          flush();
          ob_flush();
          exit;
        }
      }

  
}