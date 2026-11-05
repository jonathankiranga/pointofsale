<?php
	if (!isset($RootPath)){
            $RootPath = dirname(htmlspecialchars($_SERVER['PHP_SELF']));
            if ($RootPath == '/' OR $RootPath == "\\") {
                $RootPath = '';
            }
	}
$AllowCompanySelectionBox = 'Hide';
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
?>
<html>
<head>
  <title>SMARTERNOW DATA VENTURE</title><meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="Jonathan Kirnaga">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="css/assets/dist/css/bootstrap.min.css"  type="text/css" />
    <link rel="stylesheet" href="css/headers.css" type="text/css" />
    <link rel="stylesheet" href="css/signin.css" type="text/css" />
    <script type="text/javascript" src="javascripts/jquery.min.js"></script>
    <script type="text/javascript" src="javascripts/jquery-1.12.0.min.js"></script>
    <script type="text/javascript" src="css/assets/dist/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="Ajax/jquerryportal.js"></script>
<style>
    main#main{
            width:100%;
            height: 100%;
            background:white;
    }
    #login-right{
            position: absolute;
            right:0;
            width:40vw;
            height: 100%;
            background:white;
            display: flex;
            align-items: center;
    }
    #login-left{
            position: absolute;
            left:0;
            width:60vw;
            height: 100%;
            background:#59b6ec61;
            display: flex;
            align-items: center;
            background: url(css/logo.png);
            background-repeat: no-repeat;
            background-size: contain;
    }
    #login-right .card{
            margin: auto;
            z-index: 1
    }
    .logo {
        margin: auto;
        font-size: 8rem;
        background: white;
        padding: .5em 0.7em;
        border-radius: 50% 50%;
        color: #000000b3;
        z-index: 10;
    }
    div#login-right::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: #000000e0;

    }
    .bg-info {
      background-color: #173fb896 !important ;
      color: white;
      font-style: bold;
    }
</style>
</head>
<body>
    <main id="main" class="bg-info">
    <div id="login-left">
     </div>
    <div id="login-right">
<div class="card  bg-info">
    <div class="card-body">
        <form autocomplete="off" id="login-form" class="form-signin" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');?>" method="post">
  	<input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
	<span class="icon-bar">
	<?php
	    if (isset($CompanyList) && is_array($CompanyList)) {
            foreach ($CompanyList as $key => $CompanyEntry){
                if ($DefaultDatabase == $CompanyEntry['database']) {
                    $CompanyNameField = "$key";
                    $DefaultCompany = $CompanyEntry['company'];
                }
            }
	        if ($AllowCompanySelectionBox === 'Hide'){
                        // do not show input or selection box
                        echo '<input type="hidden" name="CompanyNameField"  value="' .  $CompanyNameField . '" />';
		    } elseif ($AllowCompanySelectionBox === 'ShowInputBox'){
                        // show input box
                        echo _('Company') .':' .  '<input class="form-control" type="text" name="DefaultCompany"  autofocus="autofocus" required="required" value="' .  htmlspecialchars($DefaultCompany ,ENT_QUOTES,'UTF-8') . '" disabled="disabled"/>';//use disabled input for display consistency
		        echo '<input type="hidden" name="CompanyNameField"  value="' .  $CompanyNameField . '" />';
		    } else {
                // Show selection box ($AllowCompanySelectionBox == 'ShowSelectionBox')
                echo _('Company') . ':<br />';
                echo '<select name="CompanyNameField" class="form-control">';
                foreach ($CompanyList as $key => $CompanyEntry){
                    if (is_dir('companies/' . $CompanyEntry['database']) ){
                        if ($CompanyEntry['database'] == $DefaultDatabase) {
                            echo '<option selected="selected" label="'.htmlspecialchars($CompanyEntry['company'],ENT_QUOTES,'UTF-8').'" value="'.$key.'">' . htmlspecialchars($CompanyEntry['company'],ENT_QUOTES,'UTF-8') . '</option>';
                        } else {
                            echo '<option label="'.htmlspecialchars($CompanyEntry['company'],ENT_QUOTES,'UTF-8').'" value="'.$key.'">' . htmlspecialchars($CompanyEntry['company'],ENT_QUOTES,'UTF-8') . '</option>';
                        }
                    }
                }
                echo '</select>';
            }
	    }
	      else { //provision for backward compat - remove when we have a reliable upgrade for config.php
            if ($AllowCompanySelectionBox === 'Hide'){
			    // do not show input or selection box
			    echo '<input type="hidden" name="CompanyNameField"  value="' . $DefaultCompany . '" />';
		    } else if ($AllowCompanySelectionBox === 'ShowInputBox'){
			    // show input box
			    echo _('Company') . '<input class="form-control" type="text" name="CompanyNameField"  autofocus="autofocus" required="required" value="' . $DefaultCompany . '" />';
		    } else {
      			// Show selection box ($AllowCompanySelectionBox == 'ShowSelectionBox')
    			echo _('Company') . ':';
	    		echo '<select name="CompanyNameField"  class="form-control">';
	    		$Companies = scandir('companies/', 0);
			    foreach ($Companies as $CompanyEntry){
                    if (is_dir('companies/' . $CompanyEntry) AND $CompanyEntry != '..' AND $CompanyEntry != '' AND $CompanyEntry!='.svn' AND $CompanyEntry!='.'){
                        if ($CompanyEntry==$DefaultDatabase) {
                            echo '<option selected="selected" label="'.$CompanyEntry.'" value="'.$CompanyEntry.'">' . $CompanyEntry . '</option>';
                        } else {
                            echo '<option label="'.$CompanyEntry.'" value="'.$CompanyEntry.'">' . $CompanyEntry . '</option>';
                        }
                    }
    	        }
    	         echo '</select>';
            }
        } //end provision for backward compat
        
        
if(isset($demo_text)) {
   echo $demo_text ;
}
	?>
	</span>
        <label for="UserNameEntryField" class="control-label">User name</label>
	<input class="form-control" type="text" id="UserNameEntryField" name="UserNameEntryField" required="required" autofocus="autofocus" maxlength="20" placeholder="<?php echo _('User name'); ?>" />
	<label for="Password" class="control-label">Password</label>
	<input class="form-control" type="password" id="password" required="required" name="Password" placeholder="<?php echo _('Password'); ?>" />
	<input class="btn btn-lg btn-primary btn-block"  type="submit" value="<?php echo _('Sign In'); ?>" name="SubmitUser" />
	</form>
        <div><label for="ForgotPassword">Forgot or Don't know Password</label><br>
        <input type="email" name="ForgotPassword" id="getEmail" maxlength="30" placeholder="<?php echo _('Enter your registered email'); ?>" />
        <input type="button" id="SendPword" value="Send Password" name="SendPassword" />
       </div>
</div></div></div>
    </main>
</body>
</html>
