<?php
namespace CustomAjaxFilters\Admin;

class ImportCSV {
	public $fieldsList=array();
	private $postCustomFields;	
	private $customPostType;	
    private $settings=array();	 
	private $separator;
	
	public function __construct($cpt="") {
		$this->customPostType=$cpt;
		$this->settings=[		
		 "createpost" => true,
		 "createmeta" => true,
		 "createcat" => false
		];
		/*
			mapping template: text %1 %2 %3 text2|csv field1|csv field2|csv field3
			no | => string
		*/
		$this->mapping=[
		 "post" => [
			 "post_title" => "auto %1 %2|Běžné číslo|Vozidlo - model",
			 "post_content" => "%1|Popis modelu"			 	 
		 ],
		 "meta" => [
			 "mauta_znacka" => "%1|Vozidlo - značka",
			 "mauta_automat" => "%1|Automatická převodovka",
			 "mauta_cenaden" => "100"
		 ],
		 "replaceglobally" => [
			"Ano" => "1",
			"Ne" => "0"
		 ]
		];
		$this->initDefaults();	
	}	
	private function initDefaults() {
		$this->params["separator"]=";";
		$this->params["encoding"]="";
		$this->params["emptyFirst"]=false;
		$this->params["skipCols"]=null;
		$this->params["createTable"]=false;
		$this->params["colsOnFirstLine"]=true;
		$this->params["mColNames"]=[];

	}
	public function setParam($name,$val) {
		$this->params[$name]=$val;
		return $this;
	}	
	
	public function removePreviousPosts($table,$brute=false) {
		global $wpdb;	
		
		if ($brute) {
			$query="
				DELETE a,b,c
				FROM wp_posts a
				LEFT JOIN wp_term_relationships b ON ( a.ID = b.object_id )
				LEFT JOIN wp_postmeta c ON ( a.ID = c.post_id )
				WHERE a.post_title like 'neco%';
			";
			$result = $wpdb->get_results($query);  			
		}
		$results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table"));		
		foreach ($results as $r) {			
			$title=$this->processTemplate($this->mapping["post"]["post_title"],$r);
			echo "<br />deleted: ".$title;
			$query="
				DELETE a,b,c
				FROM wp_posts a
				LEFT JOIN wp_term_relationships b ON ( a.ID = b.object_id )
				LEFT JOIN wp_postmeta c ON ( a.ID = c.post_id )
				WHERE a.post_title like '".$title."';
			";
			$result = $wpdb->get_results($query);  			
		}
	}
	public function preInsertThumbs() {
		global $post;		

		//list all images
		$args = array(
			'post_type' => 'attachment',
			'post_status' => 'published',
			'posts_per_page' =>-1,			
			'numberposts' => null,
			'orderby' => 'modified',
            'order' => 'DESC',
		);		
		$attachments = get_posts($args);
		foreach ( $attachments as $pic ) {				
			//echo $pic->ID.$pic->post_name."<br />";
			$pics[]=["ID" => $pic->ID, "name" => $pic->post_name];
		}
		
		//insert thumbnail programatically			
		$args = array( 'posts_per_page' => -1, 'post_type' => $this->customPostType );
		$myposts = get_posts( $args );

		foreach ( $myposts as $post ) {	
			$thumbnail_id=0;		
			$arr = explode(' ',trim($post->post_content));
			$word=$arr[0];
			$word=strtolower($word);
			if (strlen($word)<4) continue;
			foreach ($pics as $pic)	 {
				if (strpos($pic["name"],$word)!==false) { 
					$thumbnail_id=$pic["ID"];
					echo "<br />".$word."found in ".$pic["name"];
					break;
				}
			}
			if ($thumbnail_id>0) { 
				update_post_meta( $post->ID, '_thumbnail_id', $thumbnail_id );
				//echo "<br />adding thumb ".$post->post_name;
			}
		}		
	}
	private function processTemplate($template,$r) {
		$templateEx=explode("|",$template);
		if (count($templateEx)<=1) { 
			$out = $template;				
		}
		else {
			$out = $templateEx[0];					
			for ($substCnt=1;$substCnt<count($templateEx);$substCnt++)	{																
				$out = str_replace("%{$substCnt}",$r->{$templateEx[$substCnt]},$out);
			}
		}
		foreach ($this->mapping["replaceglobally"] as $repl=>$for) {
			if ($out==$repl) $out=$for;
		}
		return $out;
	}
	public function createPostsFromTable($table,$fields) {
		global $wpdb;		
		$results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table"));	
		
		//get columns of csvtab
		//get columns of wp_mauta_fields

		foreach ($results as $r) {
			//create post
			$postArr=array();
			if ($this->settings["createpost"]) {
				foreach ($this->mapping["post"] as $key=>$template) {
				 //$postArr[$key] = $r->$field;	
				 $postArr[$key]=$this->processTemplate($template,$r);				 
				}								
				$postArr["post_status"]="publish";
				$postArr["post_type"]=$this->customPostType;
				//print_r($postArr);
				$postId=wp_insert_post($postArr);
				echo "<br />inserted {$postArr["post_title"]} $postId";
				
				//create metas
				if ($this->settings["createmeta"]) {								
					foreach ($this->mapping["meta"] as $key=>$template) {						
					 //echo "<br />addmeta $postId $key $template ".$r->$template."--".$this->processTemplate($template,$r);					 
					 add_post_meta($postId,$key,$this->processTemplate($template,$r));
					}					
				}
				
				//all mauta_fields detected in csvtab are loaded
				foreach ($fields as $f) {
					$name=$f->name;
					$title=$f->title;
					$metaValue=$r->$title;
					//echo "<br />$name $title $metaValue";
					if (isset($metaValue)) {						
						add_post_meta($postId,$name,$metaValue);
						//echo "<br />added meta detected ".$name." value ".$metaValue;
					}
				}
						
				
				if ($this->settings["createcat"]) {
					//category?
					$nameKat="";
					$slugKat="";
					$parentKatId=0;
					$wpdocs_cat = array('taxonomy' => 'hp_listing_category', 'cat_name' => $nameKat, 'category_description' => $nameKat, 'category_nicename' => $slugKat, 'category_parent' => $parentKatId);	 
					$wpdocs_cat_id = wp_insert_category($wpdocs_cat,false);
				}
			}
			
			
		}

	}
	private function fgetcsvUTF8(&$handle, $length, $separator = ';',$encoding="") {
		if (($buffer = fgets($handle, $length)) !== false)    {
			$buffer = $this->autoUTF($buffer,$encoding);			
			return str_getcsv($buffer, $separator,"","\\");
		}
		return false;	
	}
	private function autoUTF($s,$encoding="") 	{
		if ($encoding=="cp1250") return iconv('WINDOWS-1250', 'UTF-8', $s);
		if ($encoding=="cp852") return iconv('CP852', 'UTF-8', $s);
		return $s;
	}
	public function loadCsvValuesFromColumn($file,$selectColNum) {
		//output values from selected column in csv file
		$sep=$this->params["separator"];
		$encoding=$this->params["encoding"];
		$colsOnFirstLine=$this->params["colsOnFirstLine"];
		$fh = fopen($file, "r"); 
		$lineNum=0;
		$out=[];	
		while ($line = $this->fgetcsvUTF8($fh, 8000, $sep,$encoding)) {		
			$lineNum++;			
			//echo "line:".$line[1];
			if ($colsOnFirstLine && $lineNum===1) {		
			 $mCols=$line;
			}
			else {			 			
				$n=0;
				$out[]=$line[$selectColNum];
			}							 
		}		
		return $out;
	}
	public function loadCsvValuesFromColumnWithKey($file,$keyCol,$keyVal,$selectColNum) {
		//output values from selected column in csv file
		$sep=$this->params["separator"];
		$encoding=$this->params["encoding"];
		$colsOnFirstLine=$this->params["colsOnFirstLine"];
		$fh = fopen($file, "r"); 
		$lineNum=0;
		$out=[];	
		while ($line = $this->fgetcsvUTF8($fh, 8000, $sep,$encoding)) {		
			$lineNum++;			
			//echo "line:".$line[1];
			if ($colsOnFirstLine && $lineNum===1) {		
			 $mCols=$line;
			}
			else {			 			
				if ($line[$keyCol]==$keyVal) $out[]=$line[$selectColNum];
			}							 
		}		
		return $out;
	}
	public function loadCsvFile($file,$table) {
		global $wpdb;
		$sep=$this->params["separator"];
		$encoding=$this->params["encoding"];
		$emptyFirst=$this->params["emptyFirst"];
		$skipCols=$this->params["skipCols"];
		$createTable=$this->params["createTable"];
		$colsOnFirstLine=$this->params["colsOnFirstLine"];
		$mCols=$this->params["mColNames"];
		$fh = fopen($file, "r"); 
		$lineNum=0;
		$mInserted=0;		
		if ($emptyFirst) $wpdb->query("TRUNCATE TABLE `$table`");
		while ($line = $this->fgetcsvUTF8($fh, 8000, $sep,$encoding)) {		
			$lineNum++;			
			if ($colsOnFirstLine && $lineNum===1) {		
			 $mCols=$line;
			 if ($createTable) $this->createTable($table,$line);
			}
			else {			 			
				$n=0;
				foreach ($line as $mVal) {
					$colName=$mCols[$n];
					$mRow[$colName]=$mVal;
					$n++;
				}									
				$query=$this->getInsertQueryFromArray($table,$mRow,$skipCols);
				$result = $wpdb->get_results($query);
				$mInserted++;			 
			}			
				 
		}		
	}
	function createTable($tabName,$mCols) {	
		global $wpdb;	
		$charset_collate = $wpdb->get_charset_collate();
		$wpdb->query( "DROP TABLE IF EXISTS {$tabName}");
		$cols="";
		foreach ($mCols as $mCol) {
			$cols.="`$mCol` text,";
		}
		$sql = "CREATE TABLE $tabName (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,
		  $cols
		  PRIMARY KEY  (id)
		) $charset_collate;";		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
	function getInsertQueryFromArray($table,$mArr,$skipCols) {
	 $query="INSERT INTO `$table` SET ";
	 $n=0;
	 foreach ($mArr as $colName => $mVal) {   
	   //echo "<br />colname:$colName value:$mVal";
	   if (!is_array($skipCols) || !in_array($colName,$skipCols)) {
		 if ($n>0) $query.=",";   
		 $query.="`$colName`='$mVal'";
		 $n++;
	  }
	 }
	 return $query;
	}

	public function loadFileLoadData($file,$sep="^",$enc='"') {
		//tohle nepude, protoze to je zakazany kvuli securiyu
		global $wpdb;
		$query="LOAD DATA LOCAL INFILE '{$file}' INTO TABLE sas FIELDS TERMINATED BY '{$sep}' ENCLOSED BY '{$enc}'
						IGNORE 1 LINES (@category,@temple) SET category = @category, temple = @temple;";
		echo "<div style='position:absolute;top:100px;left:600px;'>".$query."</div>";
						
		$wpdb->query(
                $wpdb->prepare(
                        "LOAD DATA LOCAL INFILE '{$file}' INTO TABLE sas FIELDS TERMINATED BY '{$sep}' ENCLOSED BY '{$enc}' 
						IGNORE 1 LINES (@category,@temple) SET category = @category, temple = @temple;"
                )
        );
	}
	public function importCSVfromWP($thisTable) {
		if(isset($_FILES['mfilecsv']) && ($_FILES['mfilecsv']['size'] > 0)) {
			$upload_overrides = array( 'test_form' => false ); 
			$uploaded_file = wp_handle_upload($_FILES['mfilecsv'], $upload_overrides);
			$fn = $uploaded_file['file'];
			if(isset($fn) && wp_check_filetype($uploaded_file['file'],"text/csv")) {									
					$importCSV=new ImportCSV($this->customPostType);	
					
					$importCSV->loadCsvFile($fn,$thisTable,false,"",true,true);		  	  																	
					return "imported";
			}
		} else {			
			?>
			<form method="post" enctype="multipart/form-data"> 
				<input type="file" name="mfilecsv" id="mfilecsv" />
				<input type="submit" name="html-upload" id="html-upload" class="button" value="Upload" />
			</form>
			<?php	
		}				
	}
}
