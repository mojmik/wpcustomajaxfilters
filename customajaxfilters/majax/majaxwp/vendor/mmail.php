<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require("mailer2/Exception.php");
require("mailer2/PHPMailer.php");
require("mailer2/SMTP.php");


function mSendMail($sub,$body,$altbody,$to,$from="dodavky@hertz-autopujcovna.cz",$fromName="dodavky",$replyTo="") {
  $mail = new PHPMailer();
  $mail->CharSet = "UTF-8";
  $mail->IsSMTP();                                      // set mailer to use SMTP
  $mail->Host = "mailhost.dot.com";  // specify main and backup server
  $mail->SMTPAuth = true;     // turn on SMTP authentication
  $mail->Username = "mailuser";  // SMTP username
  $mail->Password = "mailpassword"; // SMTP password
  if ($replyTo) $mail->addReplyTo($replyTo);
  $mail->From = "mail@from.com";
  $mail->FromName = "mailfromname";
  foreach ($to as $toMail) {
	  $mail->AddAddress($toMail);
  }
  //$mail->AddAddress("ellen@example.com");                  // name is optional
  //$mail->AddReplyTo("info@example.com", "Information");
  
  $mail->WordWrap = 50;                                 // set word wrap to 50 characters
  //$mail->AddAttachment("/var/tmp/file.tar.gz");         // add attachments
  //$mail->AddAttachment("/tmp/image.jpg", "new.jpg");    // optional name
  $mail->IsHTML(true);                                  // set email format to HTML
  
  $mail->Subject = $sub;
  $mail->Body    = $body;
  $mail->AltBody = $altbody;
  
  if(!$mail->Send())
  {
     echo "Message could not be sent. <p>";
     echo "Mailer Error: " . $mail->ErrorInfo;
     exit;
  }
  //echo "Message has been sent";
}



?>