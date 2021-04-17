<?php
namespace CustomAjaxFilters\Majax\MajaxWP;

class CustomFields {
  public $fieldsList=array();
  public $fieldsRows=array();
  private $customPostType;
  public function prepare($cpt) {
	//recreate fields json		
	$this->customPostType=$cpt;	
	$fieldRows=Caching::cacheRead("fieldsrows".$this->customPostType);	
	if (!$fieldRows || !count($fieldRows))	$this->createJson();		
	else $this->loadFromRows($fieldRows);
  }
  private function createJson() {
	$this->loadFromSQL();
	Caching::cacheWrite("fieldsrows".$this->customPostType,$this->fieldsRows);
  }
  public function addField($c) {
    $this->fieldsList[] = $c;	  
  }
  public function getList() {
	return $this->fieldsList;
  }
  public function outFields() {
	$out="";
	foreach ($this->fieldsList as $f) {
	  if ($out!="") $out.="|";	
	  $out.=$f->outField();
	}
	return $out;
  }
  public function getFieldsFilteredGreaterThan($filter=[]) {
	$rows=[];
	foreach ($this->fieldsList as $f) {
		$skip=false;
		foreach ($filter as $key=>$value) {			
			if ($f->$key <= $value) { 
				$skip=true;				
			}	
		}
		if (!$skip) $rows[]=$f;
	  } 
	  return $rows;
  }
  public function setFixFilter($name,$value) {
	foreach ($this->fieldsList as $f) {		
		if ($f->name == $name) { 
			$f->setFixFilter($value);
			return "set";
		}
	} 
  }
  public function getFixFilter($name) {
	foreach ($this->fieldsList as $f) {		
		if ($f->name == $name) return $f->fixFilter;
	} 
  }
  public function getFieldsFilteredOrDisplayed() {
	$rows=[];
	foreach ($this->fieldsList as $f) {
		if ($f->displayOrder>0 || $f->filterOrder>0) $rows[]=$f;
	  } 
	  return $rows;
  }
  public function getFieldsOfType($type) {
	$rows=[];
	foreach ($this->fieldsList as $f) {		
		if ($f->typeIs($type)) $rows[]=$f;
	} 
	  return $rows;
  }
  public function getFieldsFiltered() {
	return $this->getFieldsFilteredGreaterThan(["filterOrder" => "0"]);
  }
  public function getFieldsDisplayed() {
	return $this->getFieldsFilteredGreaterThan(["displayOrder" => "0"]);
  }
   public function readValues(bool $doSave=true) {
	$out="";
	foreach ($this->fieldsList as $f) {
	  $out.="values:".$f->getValues();
	  if ($doSave) $f->save();
	}
	return $out;
  }
  public function getFields() {
	  return $this->fieldsList;
  }

  private function loadFromSQL() {
	global $wpdb;
	$query = "SELECT * FROM `".$wpdb->prefix.CAF_TAB_PREFIX_BACKEND.$this->customPostType."_fields` WHERE `filterorder`>'0' OR `displayorder`>'0' ORDER BY `filterorder`";
	$load=false;
	foreach( $wpdb->get_results($query) as $key => $row) {	
		$this->fieldsRows[] = $row;	
		$this->fieldsList[] = new CustomField($row->name,$row->value,$row->type,$row->title,$row->compare,$row->valMin,$row->valMax,$row->postType,$row->icon,$row->filterorder,$row->displayorder,$row->fieldformat);
		$load=true;
	}	
	return $load;
  }
  public function saveToSQL() {
	  foreach ($this->fieldsList as $f) {
		  $f->save();
	  }
  }  
  public function loadFromRows($rows) {	
	foreach( $rows as $row) {	
		$this->fieldsRows[] = $row;	
		$this->fieldsList[] = new CustomField($row["name"],$row["value"],$row["type"],$row["title"],$row["compare"],$row["valMin"],$row["valMax"],$row["postType"],$row["icon"],$row["filterorder"],$row["displayorder"],$row["fieldformat"]);
		$load=true;
	}	
	return $load;
  }
  public function loadPostedValues() {
	foreach ($this->fieldsList as $f) {
		$f->loadPostedValue();
	} 
  }
}


