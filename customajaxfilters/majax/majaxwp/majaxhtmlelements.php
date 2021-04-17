<?php
namespace CustomAjaxFilters\Majax\MajaxWP;

use stdClass;

Class MajaxHtmlElements {	
    function showBackButton() {
        ?>
        <div style='display:none;' id="majaxback">
            <div id='goBackButton' class='mbutton btn btn-primary'>
                <a href='javascript: history.go(-1)'>zpátky</a>
            </div>
        </div>
        <?php  
    }
    function showMainPlaceHolder() {  
        $this->showBackButton();  
		?>
		<div id="majaxmain" class="majaxmain">
         <div></div>
		 <?php
		  //ajax content comes here
		 ?>
		</div> 
		<?php
    }
    function showIdSign() {
        ?>
        <input id='idSign' type='hidden' name='idSign' value='1' />
        <?php
    }
    function showMainPlaceHolderStatic($header=false,$postType="") {    
        if ($header) {
            $this->showBackButton();
            ?>
            <input type='hidden' name='type' value='<?= $postType?>' />
            <div id="majaxmain" class="majaxmain">
            <div></div>            
             <?php
        }
        //ajax content comes here
		else {
            ?>
            </div> 
            <?php
        }
    }
    function showFilters($postType,$allFields) {
		?>
		<form id="majaxform">
			<div class='majaxfiltercontainer'>			
					<input type='hidden' name='type' value='<?= $postType?>' />
				<?php		
				foreach ($allFields as $fields) {
				  ?> <div class='majaxfilterbox'> <?php  
							echo $fields->outFieldFilter();	
				  ?> </div> <?php
				}
				?>			
            </div>            
		</form>		       
		<?php
    }
    function formatField($field,$fieldFormat) {                
        if ($fieldFormat) $field=str_replace("%1",$field,$fieldFormat);
        return $field;
    }
    function showPost($cpt,$id,$name,$title,$image="",$content="",$metas="",$itemDetails="") {       
        $this->showPostDefault($id,$name,$title,$image,$content,$metas,$itemDetails);
    }
    function showPostDefault($id,$name,$title,$image,$content,$metas,$itemDetails) {         
        $metaOut=array();     
        for ($n=0;$n<5;$n++) {
            $metaOut[$n]="";
        }        
        foreach ($metas as $metaName => $metaMisc) {           
            //echo json_encode($metaMisc); da
            $metaIcon=$metaMisc["icon"];
            $displayOrder=$metaMisc["displayorder"];
            $fieldFormat=$metaMisc["fieldformat"];
            $metaVal=$itemDetails[$metaName];
            if ($metaIcon) $metaIcon="<img src='$metaIcon' />";
            else $metaIcon="<span>$metaName</span>";	
           
            if ($displayOrder<20) {
                if ($metaMisc["type"]=="NUMERIC") $metaOut[0]=$metaOut[0] . "<div class='col meta col-md-2'>$metaIcon"."$metaVal</div>";
                else $metaOut[0]=$metaOut[0] . "<div class='col meta'>$metaIcon"."$metaVal</div>";
            }
            if ($displayOrder>=20 && $displayOrder<=30) {
                $metaOut[1]=$metaOut[1] . "
                <div class='col-sm-6 price'>
                    Cena bez DPH / měsíc 
                    <div class='row'>
                        <div class='col priceTag'>".
                            $this->formatField($metaVal,$fieldFormat)."
                        </div>
                    </div> 
                </div>";
                $metaOut[2]=$metaOut[2] . "
                <div class='col-sm-6 price'>
                    Cena včetně DPH / měsíc 
                    <div class='row'>
                        <div class='col priceTag'>".
                        $this->formatField(ceil($metaVal*1.21),$fieldFormat)."
                        </div>
                    </div> 
                </div>";
            }
            if ($displayOrder>30 && $displayOrder<=40) {
                $propVal=$metaVal;
                if (!$propVal) $propVal="neuvedeno";
                $metaOut[3]=$metaOut[3] . "
                <div class='col-sm-3'>
                    <span>".$metaMisc["title"]."</span>
                    <div class='row'>
                        <span>
                         $propVal
                        </span>
                    </div> 
                </div>";
            }
            
        }
  
        if ($image!="") {
            $image="<img class='pct80' src='$image' />";
        }  else {
            $image=""; //image missing
        }
        ?>
        <div class='majaxout majaxoutStatic' id='majaxout<?=$id?>'>
                                  
                        <div class='row mcontent mtitle'>			    
                            <span><?= $title?></span>
                        </div>
                        <div class='row flex-grow-1 bort'>
                            <div class='col title'>                        
                                <?= $image?>
                                <div class='stripes stripe1'>Převodovka - manuál</div>
                                <div class='stripes stripe2'>Dálniční známka pro ČR</div>                                
                            </div>
                        </div>                        
                        <div class='row mcontent'>			    
                            <span><?= $content?></span>
                        </div>
                        <div class='row bors'>			
                            <?=$metaOut[0]?>
                        </div>
                        <div class='row bort'>			
                            <?=$metaOut[1]?>
                            <?=$metaOut[2]?>                                
                        </div>
                        <div class='row borb'>
                            <div class='col action'>
                                <a class='mButtonA' data-slug='<?=$name?>' href='?id=<?=$name?>'>Objednat</a>
                            </div>
                        </div>
                    </div>
        <?php
    }    
}