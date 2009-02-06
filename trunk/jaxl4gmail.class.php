<?php
  
  /*
   * Author:
   *    Abhinav Singh
   *
   * Contact:
   *    mailsforabhinav@gmail.com
   *    admin@abhinavsingh.com
   *
   * Site:
   *    http://abhinavsingh.com
   *    http://abhinavsingh.com/blog
   *
   * Source:
   *    http://code.google.com/p/jaxl
   *
   * About:
   *    JAXL stands for "Just Another XMPP Library"
   *    For geeks, JAXL stands for "Jabber XMPP Library"
   *    
   *    I wrote this library while developing Gtalkbots (http://gtalkbots.com)
   *    I have highly customized it to work with Gtalk Servers and inspite of
   *    production level usage at Gtalkbots, I recommend still not to use this
   *    for any live project.
   *    
   *    Feel free to add me in Gtalk and drop an IM.
   *
  */
  
  /*
   * ==================================== IMPORTANT =========================================
   * This is a sample application code showing the power of JAXL. How without knowing the 
   * inner tit-bits of XMPP protocols you can develop cool applications.
   *
   * To run this sample application you need:
   * 1. JAXL Library version 1.0.4 or higher (http://code.google.com/p/jaxl)
   * 2. PHP Mailer Class (http://phpmailer.codeworxtech.com/)
   * ==================================== IMPORTANT =========================================
  */
  
  /* Include PHP Mailer Class */
  include_once("class.phpmailer.php");
  
  /* Include XMPP Class */
  include_once("xmpp.class.php");
  
  class JAXL extends XMPP {
    
    /* Define custom mail groups */
    var $family = array("mom@gmail.com","dad@gmail.com");
    var $colleague = array("boss@company.com","manager@company.com");
    
    /* Define custom mail subject and message */
    var $familySubject = "Hi, Will get back to you";
    var $familyMessage = "Hey, I am currently out on vacation at my grannies house in Delhi. I will return back home by next monday.<br/>Reach out to me at +91-987654321.<br/><br/>With Love,<br/>Abhinav Singh";
    
    /* Define custom mail subject and message */
    var $colleagueSubject = "[Auto-Reply] Out of Office";
    var $colleagueMessage = "Hi, I am on vacation in my village with very limited access to internet and phone. I will return back home by next monday.<br/><br/>Regards,<br/>Abhinav Singh";
    
    function eventMessage($fromJid, $content, $offline = FALSE) {
      
    }
    
    function eventPresence($fromJid, $status, $photo) {
      
    }
    
    function eventNewEMail($total,$thread,$url,$participation,$messages,$date,$senders,$labels,$subject,$snippet) {
      // We only want to send auto-reply message to latest sender
      $sender = $senders[0];
      
      // Check if the user lie in any category, and send appropriate mail
      if(in_array($sender["address"],$this->family)) {
        $mail = new PHPMailer();
        $mail->From = "youremailid@gmail.com";
        $mail->FromName = "Your Name";
        $mail->Subject = $this->familySubject;
        $mail->MsgHTML($this->familyMessage."<br/><br/>Powered by Jaxl http://code.google.com/p/jaxl");
        $mail->IsHTML(true);
        $mail->AddAddress($sender["address"],$sender["name"]);
        
        if(!$mail->Send()) $this->logger->logger("Error occured while sending mail to ".$sender["address"]);
        else $this->logger->logger("Mail sent successfully to ".$sender["address"]);
      }
      else if(in_array($sender["address"],$this->colleague)) {
        $mail = new PHPMailer();
        $mail->From = "youremailid@gmail.com";
        $mail->FromName = "Your Name";
        $mail->Subject = $this->colleagueSubject;
        $mail->MsgHTML($this->colleagueMessage."<br/><br/>Powered by Jaxl http://code.google.com/p/jaxl");
        $mail->IsHTML(true);
        $mail->AddAddress($sender["address"],$sender["name"]);
        
        if(!$mail->Send()) $this->logger->logger("Error occured while sending mail to ".$sender["address"]);
        else $this->logger->logger("Mail sent successfully to ".$sender["address"]);
      }
      else {
        // Do nothing, will handle later on
        $this->logger->logger("No handler for this email id...");
      }
    }
    
    function setStatus() {
      print "Setting Status...\n";
      $this->sendStatus("Available");
      print "Requesting new mails...\n";
      $this->getNewEMail();
      print "Done\n";
    }
    
  }
  
?>
