<?php
namespace CustomAjaxFilters\Majax\MajaxWP;

Class MajaxItem {	
    private $metaFields=array();
    private $mainFields=array();  
    public function addMeta($metaKey,$metaValue) {
        $this->metaFields[$metaKey]=($metaValue);
        return $this;
    }    
    public function addField($fieldKey,$fieldValue) {
        $this->mainFields[$fieldKey]=($fieldValue);
        return $this;
    }
    public function expose($getJson=1) {        
        $arr=$this->mainFields;
        $arr["meta"]=$this->metaFields;
        if ($getJson) return json_encode($arr);        
        else return $arr;
    }
    
}
