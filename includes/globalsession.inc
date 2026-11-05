<?php
/* $Id: session.inc 6338 2013-09-28 05:10:46Z daintree $*/
if (!isset($PathPrefix)) {
    $PathPrefix='';
}

if (!file_exists($PathPrefix . 'config.php')){
    $RootPath = dirname(htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'));
    if ($RootPath == '/' OR $RootPath == "\\") {
        $RootPath = '';
    }
     exit;
}
include($PathPrefix . 'config.php');

if (isset($dbuser)) {
    $DBUser=$dbuser;
    $DBPassword=$dbpassword;
    $DBType=$dbType;
}

if (isset($SessionSavePath)){
  session_save_path($SessionSavePath);
}

if (!isset($SysAdminEmail)) {
    $SysAdminEmail='';
}
if (!isset($_SESSION)) {
ini_set('session.gc_maxlifetime',$SessionLifeTime);

if( !ini_get('safe_mode') ){
        set_time_limit($MaximumExecutionTime);
        ini_set('max_execution_time',$MaximumExecutionTime);
}
session_write_close(); //in case a previous session is not closed
session_name('smartlimserp');
session_start();
}


include($PathPrefix . 'includes/ConnectDB.inc');
include($PathPrefix . 'includes/DateFunctions.inc');

if (!isset($_SESSION['AttemptsCounter']) OR $AllowDemoMode==true){
	$_SESSION['AttemptsCounter'] = 0;
}

if (isset($_SESSION['DatabaseName'])){
    foreach ($_POST as $PostVariableName => $PostVariableValue) {
        if (gettype($PostVariableValue) != 'array') {
              $_POST[$PostVariableName] = DB_escape_string($PostVariableValue);
        } else {
            foreach ($PostVariableValue as $PostArrayKey => $PostArrayValue) {
                    $PostVariableValue[$PostArrayKey] = DB_escape_string($PostArrayValue);
            }
        }
    }

    /* iterate through all elements of the $_GET array and DB_escape_string them
    to limit possibility for SQL injection attacks and cross scripting attacks
    */
    foreach ($_GET as $GetKey => $GetValue) {
        if (gettype($GetValue) != 'array') {
           $_GET[$GetKey] = DB_escape_string($GetValue);
        }
    }
} else { //set SESSION['FormID'] before the a user has even logged in
	$_SESSION['FormID'] = sha1(uniqid(mt_rand(), true));
}

include($PathPrefix . 'includes/LanguageSetup.php');

if (!isset($AllowAnyone)){ /* only do security checks if AllowAnyone is not true */

    include $PathPrefix . 'includes/UserLogin.php';	/* Login checking and setup */

    if (isset($_POST['UserNameEntryField']) AND isset($_POST['Password'])) {
        $rc = userLogin($_POST['UserNameEntryField'], $_POST['Password'], $SysAdminEmail, $db);
    } elseif (empty($_SESSION['DatabaseName'])) {
        $rc = UL_SHOWLOGIN;
    } else {
        $rc = UL_OK;
    }

   
switch ($rc) {
	case  UL_OK; //user logged in successfully
		include($PathPrefix . 'includes/LanguageSetup.php'); //set up the language
		break;

	case UL_SHOWLOGIN:
		include($PathPrefix . 'includes/posLogin.php');
		exit;

	case UL_BLOCKED:
		die(include($PathPrefix . 'includes/FailedLogin.php'));

	case  UL_CONFIGERR:
		prnMsg(_('Your user role does not have any access defined. There is an error in the security setup for this user account'),'error');
        	exit;

	case  UL_NOTVALID:
		$demo_text = '<font size="3" color="red"><b>' .  _('incorrect password') . '</b></font><br /><b>' . _('The user/password combination') . '<br />' . _('is not a valid user of the system') . '</b>';
		die(include($PathPrefix . 'includes/posLogin.php'));

	case  UL_MAINTENANCE:
		$demo_text = '<font size="3" color="red"><b>' .  _('system maintenance') . '</b></font><br /><b>' . _('webERP is not available right now') . '<br />' . _('during maintenance of the system') . '</b>';
		die(include($PathPrefix . 'includes/posLogin.php'));

	}
        
}

/*User is logged in so get configuration parameters  - save in session*/
include($PathPrefix . 'includes/GetConfig.php');


/*The page security variable is now retrieved from the database in GetConfig.php and stored in the $SESSION['PageSecurityArray'] array
 * the key for the array is the script name - the script name is retrieved from the basename ($_SERVER['SCRIPT_NAME'])
 */
if (!isset($PageSecurity)){
 //only hardcoded in the UpgradeDatabase script - so old versions that don't have the scripts.pagesecurity field do not choke
 $PageSecurity = $_SESSION['PageSecurityArray']['posindex.php'];
}


if (!isset($AllowAnyone)){
	if ((!in_array($PageSecurity, $_SESSION['AllowedPageSecurityTokens']) OR !isset($PageSecurity))) {
        	echo '<table width="100%" class="table_index"><tr><td class="menu_group_item">';
		echo '<b><font style="size:+1; text-align:center;">' . _('The security settings on your account do not permit you to access this function') . '</font></b>';
		echo '</td></tr></table>';
  		exit;
	}
}


/*System administrator login */
if (in_array($_SESSION['PageSecurityArray']['WWW_Users.php'],$_SESSION['AllowedPageSecurityTokens'])) {
	$debug = 1; //allow debug messages
} else {
	$debug = 0; //don't allow debug messages
}

function CryptPass( $Password ) {
    global $CryptFunction;
    if ( $CryptFunction == 'sha1' ) {
            return sha1($Password);
    } elseif ( $CryptFunction == 'md5' ) {
            return md5($Password);
    } else {
            return $Password;
    }
 }

