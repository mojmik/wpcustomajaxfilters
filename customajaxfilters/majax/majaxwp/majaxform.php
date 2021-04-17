<?php
namespace CustomAjaxFilters\Majax\MajaxWP;

class MajaxForm {   
    private $postType;
    private $postedFields;
    function __construct($type="",$fields=[]) {            
        $this->postType=$type;
        $this->postedFields=$fields;
        if (empty($fields)) {          
            if ($type=="dotaz") {
                $this->postedFields=[                
                "fname" => "Jméno", 
                "email" => "Email",                 
                "msg" => "Zpráva"
                ];
            }
            else $this->postedFields=["fname" => "Jméno", 
                "lname" => "Příjmení", 
                "email" => "Email", 
                "start_date" => "Začátek pronájmu", 
                "end_date" => "Konec pronájmu", 
                "phone_no" => "Telefon", 
                "expected_mileage" => "Předpoklad km", 
                "business" => "Již je firemní zákazník", 
                "postTitle" => "Vybrané auto"
                ];
        }
    }
    function printForm($id,$title) {     
        ?>
        <div class="mpagination">
            <div class="row frameGray">
                <div class="col-md-11 col-xs-12 mcent">
                    <form id="<?= $id?>" method="post">
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
                                                <input type='hidden' name='postTitle' value='<?= $title?>' />
                                                <input type='hidden' name='postType' value='<?= $this->postType?>' />
                                                
                        </form>
                    </div>
                </div>
            </div>
        <?php             
   }
   function checkCaptcha() {
       $captchaResponse=$_POST["captcha"];
       if (!$captchaResponse) { 
        $this->logWrite("CAPTCHA blank ","filledform.txt");
        return false;
       }               
       $secret=$this->loadSecret("captchakey");       
       //$verify=file_get_contents($url);

       $ch = curl_init();
       curl_setopt_array($ch, [
            CURLOPT_URL => 'https://www.google.com/recaptcha/api/siteverify',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'secret' => $secret,
                'response' => $captchaResponse,
                'remoteip' => $_SERVER['REMOTE_ADDR']
            ],
            CURLOPT_RETURNTRANSFER => true
        ]);
       $output = curl_exec($ch);
       curl_close($ch);
       $verify = json_decode($output);

       $this->logWrite("CAPTCHA response out -".$output."-","filledform.txt");           
        if ($verify->success==true) {
           $this->logWrite("CAPTCHA success ".$output,"filledform.txt");
          //This user is verified by recaptcha
        return true;
       }
       $this->logWrite("CAPTCHA fail ".$output,"filledform.txt");
       return false;
   }
   function processForm($action="",$title="",$type="") {		
        $row=[];
        if ($action=="action") {
            $row["title"]="action";
            $row["content"]="";
            //$row["postTitle"]=$this->postType;
            $row["postTitle"]=$title;
            $row["postType"]=$this->postType;
            //$row["itemTitle"]=$title;
        }
        if ($action=="contactFilled") {  
            if (!$this->checkCaptcha()) {
                $row["title"]="action";
                $row["content"]="Antispam ověření selhalo, vraťte se zpět a zkuste znova."; 
                return $row;
            }
            $outHtml="";
            $outTxt="";
            foreach ($this->postedFields as $name => $value) {
                if ($outTxt) $outTxt.="<br />";
                //$out.="$name: ".filter_var($_POST[$name], FILTER_SANITIZE_STRING);
                $formVal=$_POST[$name];
                $outTxt.="$value - $formVal";	
                $outHtml.="<tr><td><b>$value</b></td><td>".$formVal."</td></tr>";	
                if ($name=="email") $replyTo=$formVal;
            }			
            $outHtml="<table>$outHtml</table>";			
            
            $to = $this->loadSecret("emailydefault",true);            
            $subject = 'objednavka z hertz-autopujcovna.cz';
            $body = "<h1>Objednavka z webu</h1> <h3>Typ: $type</h3> <br /><br />$outHtml";      	
            $headers = array('Content-Type: text/html; charset=UTF-8');	
            wp_mail($to,$subject,$body,$headers);			
            $this->logWrite("".$outTxt." replyto $replyTo.","filledform.txt");
            $row["title"]="action";
            $row["content"]="Díky za odeslání. Budeme vás brzy kontaktovat.";
        }
        return $row;
    }	
    function loadSecret($file,$isArray=false) {     
     $out=@file_get_contents(plugin_dir_path( __FILE__ ) ."secret/$file.txt");      
     if (!$isArray) return $out;
     else return explode(";",$out);
    }
    function logWrite($val,$file="log.txt") {
        file_put_contents(plugin_dir_path( __FILE__ ) . $file,date("d-m-Y h:i:s")." ".$val."\n",FILE_APPEND | LOCK_EX);
       }
}