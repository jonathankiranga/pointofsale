<?php

/**
 * Description of Autoloadmailsettings
 *
 * @author Admin
 */

require('Mailer/PHPMailerAutoload.php');

class Autoloadmailsettings extends PHPMailer {
  
    function load(){
        
        $this->getemailsettings();
        //Tell PHPMailer to use SMTP
        $this->isSMTP();
        //Enable SMTP debugging
        // 0 = off (for production use)
        // 1 = client messages
        // 2 = client and server messages
        $this->SMTPDebug = 0;
        //Ask for HTML-friendly debug output
        $this->Debugoutput = 'html';
       //Set who the message is to be sent from
         $this->setFrom($this->Username, 'SmartERP');
        //Set an alternative reply-to address
         $this->addReplyTo($this->Username, 'No-Reply');
         
         
         /*   $mail->Body = <<<EOT
            Email: {$_POST['email']}
            Name: {$_POST['name']}
            Message: {$_POST['message']}
            EOT;
          */
    }
    
    function getemailsettings(){
        Global $db;
        
        /*
        $this->Host = $this->host ;//"smtp.mail.yahoo.com";
        //Set the SMTP port number - likely to be 25, 465 or 587
        $this->Port =  $this->port; //587;
        //Whether to use SMTP authentication
        $this->SMTPAuth = true;
        //Username to use for SMTP authentication
        $this->Username = $this->username;
        //Password to use for SMTP authentication
        $this->Password = $this->password;
        */
        
        $sql="SELECT host,port,username,password FROM emailsettings";
        $ErrMsg = _('The email settings information cannot be retrieved');
        $DbgMsg = _('The SQL that failed was');
        $result = DB_query($sql, $db,$ErrMsg,$DbgMsg);
        $myrow  = DB_fetch_row($result);
        $this->Host = $myrow[0];
        $this->Port = $myrow[1];
        $this->Username = $myrow[2];
        $this->Password = $myrow[3];
		
    }
    
   
}
