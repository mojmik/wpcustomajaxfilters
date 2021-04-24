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
    function showPost($id,$name,$title,$image="",$content="",$metas=[],$itemDetails="") {      
        //used for static content output   
        $metaOut=[];     
        $featuredText=[];
        for ($n=0;$n<5;$n++) {
            $metaOut[$n]="";
        }        
        foreach ($metas as $metaName => $metaMisc) {
            //iterate fields           
            $metaIcon=$metaMisc["icon"];
            $displayOrder=$metaMisc["displayorder"];
            $fieldFormat=$metaMisc["fieldformat"];
            $metaVal=$itemDetails[$metaName];
            $htmlTemplate=$metaMisc["htmlTemplate"];
            
            if ($metaIcon) $metaIcon="<img src='$metaIcon' />";
            else $metaIcon="<span>{$metaMisc["title"]}</span>";	

            if ($metaMisc["virtVal"]) { //virtual values; first character .. # - clone value from other field, otherwise fix value
                if (substr($metaMisc["virtVal"],0,1) == "#") { 
                    //clone from other field
                    $cloneVar=substr($metaMisc["virtVal"],1);
                    $metaVal=ceil($itemDetails[$cloneVar]*1.21);    
                    $displayOrder=($displayOrder) ? $displayOrder : $metas[$cloneVar]["displayorder"];
                    $htmlTemplate=($htmlTemplate) ? $htmlTemplate : $metas[$cloneVar]["htmlTemplate"];
                    $fieldFormat=($fieldFormat) ? $fieldFormat : $metas[$cloneVar]["fieldformat"];
                    $metaIcon=($metaIcon) ? $metaIcon : $metas[$cloneVar]["icon"];
                }
                else $metaVal=$metaMisc["virtVal"];
            }             
            if ($htmlTemplate) {
                $htmlTemplate=str_replace('${formattedVal}',$this->formatField($metaVal,$fieldFormat),$htmlTemplate);
                $htmlTemplate=str_replace('${metaIcon}',$metaIcon,$htmlTemplate);  
                $metaVal=$htmlTemplate;              
            }
            
           
           
            if ($displayOrder<20) {
                if ($metaMisc["type"]=="NUMERIC") $metaOut[0]=$metaOut[0] . "<div class='col meta col-md-2'>$metaIcon"."$metaVal</div>";
                else $metaOut[0]=$metaOut[0] . "<div class='col meta'>$metaIcon"."$metaVal</div>";
            }
            if ($displayOrder>=20 && $displayOrder<=30) {
                $metaOut[1]=$metaOut[1] . $metaVal;                
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
            if ($displayOrder>40 && $displayOrder<=50) {
                $featuredText[]=$metaVal;
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
                                <?php
                                    $n=1;
                                    foreach ($featuredText as $f) {
                                    ?>
                                     <div class='stripes stripe<?= $n?>'><?= $f?></div>
                                    <?php
                                     $n++;
                                    }
                                ?>

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
    function postTemplate($templateName,$params=[]) {        
        if ($templateName=="multi") {
            return "
            <div class='majaxout' id='majaxout{id}'>         
                        <div class='row flex-grow-1 bort'>
                            <div class='col title'>                        
                                {image}{featuredHtml}                                                        
                            </div>
                        </div>
                        <div class='row mcontent borb'>			    
                            <span>{content}</span>
                        </div>
                        <div class='row bors'>			
                            {metaOut[0]}                    
                        </div>
                        <div class='row bort'>			
                                {metaOut[1]}
                                {metaOut[2]}	
                        </div>
                        <div class='row borb'>
                            <div class='col action'>
                                <a class='mButtonA' data-slug='{name}' href='?id={name}'>Objednat</a>
                            </div>
                        </div>
                    </div>";
        }
        if ($templateName=="single") {
            return "
				<div class='majaxout row2' id='majaxout{id}'>
                    <div class='row mcontent mtitle'>			    
                            <span>{title}</span>
                        </div>
                    <div class='row flex-grow-1'>
                        <div class='col title borf'>                        
                            {image}{featuredHtml}                        
                        </div>
                    </div>
                    <div class='row mcontent'>			    
                        <span>{content}</span>
                    </div>
                    <div class='row bors'>			
                         {metaOut[0]}                    
                    </div>
                    <div class='row bort'>			
                            {metaOut[1]}
                            {metaOut[2]}	
                    </div>                
                </div>";
        }
    }
    function formTemplate($templateName,$params=[]) {
        if (isset($params["title"])) $postTitle=$params["title"];
        if (isset($params["type"])) $postType=$params["type"];
        $innerForm="";
        if ($templateName=="contactForm") {
            ?>
            <div class="mpagination">
            <div class="row frameGray">
                <div class="col-md-11 col-xs-12 mcent">
                    <form id="majaxContactForm" data-group="staticForms" method="post">
                                                <div class="row formGroup">
                                                    <div class="col-sm-6">                                    
                                                        <input type="text" class="form-control" id="fname" name="fname" placeholder="Jméno">
                                                    </div>
                                                    <div class="col-sm-6">                                                                        
                                                        <input type="text" class="form-control email" id="email" name="email" placeholder="Email*">
                                                    </div>                                
                                                </div>
                                                <div class="row formGroup">                                                                                    
                                                    <div class="col-sm-12">                                    
                                                        <textarea class="form-control" id="txtmsg" name="msg" placeholder="Vaše zpráva*"></textarea>
                                                    </div>
                                                </div>
                                                <div class="row formGroup">                                                                                    
                                                    <div class="col-sm-12">
                                                        anti-spam                                                                                            
                                                        <div id="myCaptcha"></div>
                                                    </div>
                                                </div>                                                                                                
                                                <div class="row3">	
                                                        <div class="col-sm-3 pullRight col-xs-12">
                                                            <input type="submit" class="btn btn-primary btn-block" name="submit" id="submit" value="Potvrdit">
                                                                <input type="Button" class="btn btn-primary btn block" value="Processing.." id="divprocessing" style="display: none;">
                                                        </div>
                                                </div>                                                
                                                <input type='hidden' name='postTitle' value='<?= $postType?>' />
                                                <input type='hidden' name='postType' value='<?= $postType?>' />
                                                
                        </form>
                    </div>
                </div>
            </div>
            <?php
            return "";
        }
        if ($templateName=="defaultForm") {
            $innerForm='
            <div class="row formGroup">
                                        <div class="col-sm-6">                                    
                                            <input type="text" class="form-control" id="fname" name="fname" placeholder="Jméno*">
                                        </div>
                                        <div class="col-sm-6">                                    
                                            <input type="text" class="form-control" id="lname" name="lname" placeholder="Příjmení*">
                                        </div>
                                    </div>
                                    <div class="row formGroup">                                
                                        <div class="col-sm-6">                                                                        
                                            <input type="text" class="form-control email" id="email" name="email" placeholder="Email*">
                                        </div>                                
                                        <div class="col-sm-6">                                    
                                            <input type="text" class="form-control email" id="remail" name="cemail" placeholder="Email*">
                                        </div>
                                    </div>
                                    <div class="row formGroup">
                                        <div class="col-sm-3">
                                            <input type="text" class="form-control cal pointerEvent" id="pickDate" placeholder="Začátek pronájmu*" name="start_date" readonly="readonly">
                                        </div>
                                        <div class="col-sm-3">
                                            <input type="text" class="form-control cal pointerEvent" id="dropDate" placeholder="Konec pronájmu*" name="end_date" readonly="readonly">
                                        </div>
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control tel" id="phone_no" name="phone_no" placeholder="Telefon*">
                                        </div>
                                    </div>
                                    <div class="row formGroup">
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control mileage" id="mileage" placeholder="Předpoklad najetých kilometrů*" name="expected_mileage">
                                        </div>
                                        <div class="col-sm-6 p-spc-0">
                                                    <label for="business" id="leasing-for-leisure" class="leisLabel">
                                                        <input name="business" id="businessChBox" type="checkbox" class="leisCheck">
                                                        <em id="businessBox" class="sprite"></em>
                                                        Jste již naším firemním zákazníkem*
                                                    </label>
                                        </div>
                                    </div>
                                    <div class="row formGroup">                                                                                    
                                                    <div class="col-sm-12">
                                                        anti-spam                                    
                                                        <div id="myCaptcha"></div>
                                                    </div>
                                    </div> 
                                    <div class="row formGroup">
                                        <div class="col-sm-12">* Povinné pole</div>
                                    </div>                                 
                                    <div class="row3">	
                                            <div class="col-sm-3 pullRight col-xs-12">
                                                <input type="submit" class="btn btn-primary btn-block" name="submit" id="submit" value="Potvrdit">
                                                    <input type="Button" class="btn btn-primary btn block" value="Processing.." id="divprocessing" style="display: none;">
                                            </div>
                                    </div>
                                    ';
        }
        
        return '
            <div class="mpagination">     
                <div class="row frameGray">                        
                    <div class="col-md-11 col-xs-12 mcent">
                        <div class="row">
                            <div class="yellowBand" id="enquiryP">
                                Pokud potřebujete více informací nebo se zajímáte o pronájem vozu na delší dobu, vyplňte prosím níže uvedený formulář a my vás budeme kontaktovat.
                            </div>
                        </div>
                        <div class="col-md-12 col-xs-12">
                                <div class="row">
                                    <div class="col-xs-12">
                                        <p class="formhead">
                                        
                                        </p>								
                                    </div>
                                </div>
                            <form id="{name}" method="post">'.$innerForm.'
                                
                                <input type="hidden" name="postTitle" value="'.$postTitle.'" />
                                <input type="hidden" name="postType" value="'.$postType.'" />
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        ';
    }
    function getTemplate($templateName,$type="post",$params=[]) {
        if ($type=="post") {
            return $this->postTemplate($templateName,$params);
        }
        if ($type=="form") {
            return $this->formTemplate($templateName,$params);
        }  
    }
}