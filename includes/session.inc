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
    header('Location:' . $RootPath . '/install/index.php');
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

        $_SESSION['Theme'] = 'xenos';
	/*  Need to set the theme to make login screen nice */
	$Theme =  $_SESSION['Theme'] ;
	switch ($rc) {
	case  UL_OK; //user logged in successfully
		include($PathPrefix . 'includes/LanguageSetup.php'); //set up the language
		break;

	case UL_SHOWLOGIN:
		include($PathPrefix . 'includes/Login.php');
		exit;

	case UL_BLOCKED:
		die(include($PathPrefix . 'includes/FailedLogin.php'));

	case  UL_CONFIGERR:
		$Title = _('Account Error Report');
		include($PathPrefix . 'includes/header.inc');
		echo '<br /><br /><br />';
		prnMsg(_('Your user role does not have any access defined. There is an error in the security setup for this user account'),'error');
		include($PathPrefix . 'includes/footer.inc');
		exit;

	case  UL_NOTVALID:
		$demo_text = '<font size="3" color="red"><b>' .  _('incorrect password') . '</b></font><br /><b>' . _('The user/password combination') . '<br />' . _('is not a valid user of the system') . '</b>';
		die(include($PathPrefix . 'includes/Login.php'));

	case  UL_MAINTENANCE:
		$demo_text = '<font size="3" color="red"><b>' .  _('system maintenance') . '</b></font><br /><b>' . _('webERP is not available right now') . '<br />' . _('during maintenance of the system') . '</b>';
		die(include($PathPrefix . 'includes/Login.php'));

	}
        
}

/*User is logged in so get configuration parameters  - save in session*/
include($PathPrefix . 'includes/GetConfig.php');

//purge the audit trail if necessary
if (isset($_SESSION['MonthsAuditTrail'])){
    $sql = "DELETE FROM audittrail	WHERE  transactiondate <= '" . Date('Y-m-d', mktime(0,0,0, Date('m')-$_SESSION['MonthsAuditTrail'])) . "'";
    $ErrMsg = _('There was a problem deleting expired audit-trail history');
    $result = DB_query($sql,$db);
}

/*Check to see if currency rates need to be updated */
$_SESSION['UpdateCurrencyRatesDaily']=0;
if (isset($_SESSION['UpdateCurrencyRatesDaily'])){
   if ($_SESSION['UpdateCurrencyRatesDaily']!=0)  {
		if (DateDiff(Date($_SESSION['DefaultDateFormat']),
			ConvertSQLDate($_SESSION['UpdateCurrencyRatesDaily']),'d')> 0){

			if ($_SESSION['ExchangeRateFeed']=='ECB') {
				$CurrencyRates = GetECBCurrencyRates(); // gets rates from ECB see includes/MiscFunctions.php
				/*Loop around the defined currencies and get the rate from ECB */
				if ($CurrencyRates!=false) {
					$CurrenciesResult = DB_query("SELECT currabrev FROM currencies",$db);
					while ($CurrencyRow = DB_fetch_array($CurrenciesResult)){
						if ($CurrencyRow['currabrev']!=$_SESSION['CompanyRecord']['currencydefault']){
							$UpdateCurrRateResult = DB_query("UPDATE currencies SET rate='" . GetCurrencyRate($CurrencyRow['currabrev'],$CurrencyRates) . "'
                                                            WHERE currabrev='" . $CurrencyRow['currabrev'] . "'",$db);
						}
					}
				}
			} else {
				$CurrenciesResult = DB_query("SELECT currabrev FROM currencies",$db);
				while ($CurrencyRow = DB_fetch_row($CurrenciesResult)){
					if ($CurrencyRow['currabrev']!=$_SESSION['CompanyRecord']['currencydefault']){
						$UpdateCurrRateResult = DB_query("UPDATE currencies SET rate='" . google_currency_rate($CurrencyRow['currabrev']) . "'
															WHERE currabrev='" . $CurrencyRow['currabrev'] . "'",$db);
					}
				}
			}
			$_SESSION['UpdateCurrencyRatesDaily'] = Date('Y-m-d');
			$UpdateConfigResult = DB_query("UPDATE config SET confvalue = '" . Date('Y-m-d') . "' WHERE confname='UpdateCurrencyRatesDaily'",$db);
		}
	}
}

If (isset($_POST['Theme']) AND ($_SESSION['UsersRealName'] == $_POST['RealName'])) {
	$_SESSION['Theme'] = $_POST['Theme'];
	$Theme = $_POST['Theme'];
} elseif (!isset($_SESSION['Theme'])) {
	$Theme = $_SESSION['DefaultTheme'];
	$_SESSION['Theme'] = $_SESSION['DefaultTheme'];
} else {
	$Theme = $_SESSION['Theme'];
}

/* Set the logo if not yet set.
 * will be done only once per session and each time
 * we are not in session (i.e. before login)
 */
if (empty($_SESSION['LogoFile'])) {
    /* find a logo in companies/$CompanyDir
     * (nice side effect of function:
     * variables are local, so we will never
     * cause name clashes)
     */

    function findLogoFile($CompanyDir, $PathPrefix) {
            $dir = $PathPrefix.'companies/' . $CompanyDir . '/';
            $DirHandle = dir($dir);
            while ($DirEntry = $DirHandle->read() ){
                    if ($DirEntry != '.' AND $DirEntry !='..'){
                            $InCompanyDir[] = $DirEntry; //make an array of all files under company directory
                    }
            } //loop through list of files in the company directory
            if ($InCompanyDir !== FALSE) {
                    foreach($InCompanyDir as $logofilename) {
                            if (strncasecmp($logofilename,'logo.png',8) === 0 AND
                                    is_readable($dir . $logofilename) AND
                                    is_file($dir . $logofilename)) {
                                    $logo = $logofilename;
                                    break;
                            }
                    }
                    if (!isset($logo)) {
                            foreach($InCompanyDir as $logofilename) {
                                    if (strncasecmp($logofilename,'logo.jpg',8) === 0 AND
                                            is_readable($dir . $logofilename) AND
                                            is_file($dir . $logofilename)) {
                                            $logo = $logofilename;
                                            break;
                                    }
                            }
                    }
                    if (empty($logo)) {
                            return null;
                    } else {
                            return 'companies/' .$CompanyDir .'/'. $logo;
                    }
            } //end listing of files under company directory is not empty
    }

    /* Find a logo in companies/<company of this session> */
    if (!empty($_SESSION['DatabaseName'])) {
            $_SESSION['LogoFile'] = findLogoFile($_SESSION['DatabaseName'], $PathPrefix);
    }
}


if (empty($_SESSION['KenhasFile'])) {
    /* find a logo in companies/$CompanyDir
     * (nice side effect of function:
     * variables are local, so we will never
     * cause name clashes)
     */

    function findkenhaFile($CompanyDir, $PathPrefix) {
            $dir = $PathPrefix.'companies/' . $CompanyDir . '/';
            $DirHandle = dir($dir);
            while ($DirEntry = $DirHandle->read() ){
                    if ($DirEntry != '.' AND $DirEntry !='..'){
                            $InCompanyDir[] = $DirEntry; //make an array of all files under company directory
                    }
            } //loop through list of files in the company directory
            if ($InCompanyDir !== FALSE) {
                    foreach($InCompanyDir as $logofilename) {
                            if (strncasecmp($logofilename,'KenhasFile.png',8) === 0 AND
                                    is_readable($dir . $logofilename) AND
                                    is_file($dir . $logofilename)) {
                                    $logo = $logofilename;
                                    break;
                            }
                    }
                    if (!isset($logo)) {
                            foreach($InCompanyDir as $logofilename) {
                                    if (strncasecmp($logofilename,'KenhasFile.jpg',8) === 0 AND
                                            is_readable($dir . $logofilename) AND
                                            is_file($dir . $logofilename)) {
                                            $logo = $logofilename;
                                            break;
                                    }
                            }
                    }
                    if (empty($logo)) {
                            return null;
                    } else {
                            return 'companies/' .$CompanyDir .'/'. $logo;
                    }
            } //end listing of files under company directory is not empty
    }

    /* Find a logo in companies/<company of this session> */
    if (!empty($_SESSION['DatabaseName'])) {
            $_SESSION['KenhasFile'] = findkenhaFile($_SESSION['DatabaseName'], $PathPrefix);
    }
}


if (empty($_SESSION['NeemaFile'])) {
    /* find a logo in companies/$CompanyDir
     * (nice side effect of function:
     * variables are local, so we will never
     * cause name clashes)
     */

    function findNeemaFile($CompanyDir, $PathPrefix) {
            $dir = $PathPrefix.'companies/' . $CompanyDir . '/';
            $DirHandle = dir($dir);
            while ($DirEntry = $DirHandle->read() ){
                    if ($DirEntry != '.' AND $DirEntry !='..'){
                            $InCompanyDir[] = $DirEntry; //make an array of all files under company directory
                    }
            } //loop through list of files in the company directory
            if ($InCompanyDir !== FALSE) {
                    foreach($InCompanyDir as $logofilename) {
                            if (strncasecmp($logofilename,'neemaLogo.png',8) === 0 AND
                                    is_readable($dir . $logofilename) AND
                                    is_file($dir . $logofilename)) {
                                    $logo = $logofilename;
                                    break;
                            }
                    }
                    if (!isset($logo)) {
                            foreach($InCompanyDir as $logofilename) {
                                    if (strncasecmp($logofilename,'neemaLogo.jpg',8) === 0 AND
                                            is_readable($dir . $logofilename) AND
                                            is_file($dir . $logofilename)) {
                                            $logo = $logofilename;
                                            break;
                                    }
                            }
                    }
                    if (empty($logo)) {
                            return null;
                    } else {
                            return 'companies/' .$CompanyDir .'/'. $logo;
                    }
            } //end listing of files under company directory is not empty
    }

    /* Find a logo in companies/<company of this session> */
    if (!empty($_SESSION['DatabaseName'])) {
            $_SESSION['NeemaFile'] = findNeemaFile($_SESSION['DatabaseName'], $PathPrefix);
    }
}


if (empty($_SESSION['LABCODE'])) {
    /* find a logo in companies/$CompanyDir
     * (nice side effect of function:
     * variables are local, so we will never
     * cause name clashes)
     */

    function findLABCODEFile($CompanyDir, $PathPrefix) {
            $dir = $PathPrefix.'companies/' . $CompanyDir . '/';
            $DirHandle = dir($dir);
            while ($DirEntry = $DirHandle->read() ){
                    if ($DirEntry != '.' AND $DirEntry !='..'){
                            $InCompanyDir[] = $DirEntry; //make an array of all files under company directory
                    }
            } //loop through list of files in the company directory
            if ($InCompanyDir !== FALSE) {
                    foreach($InCompanyDir as $logofilename) {
                            if (strncasecmp($logofilename,'labcode.png',8) === 0 AND
                                    is_readable($dir . $logofilename) AND
                                    is_file($dir . $logofilename)) {
                                    $logo = $logofilename;
                                    break;
                            }
                    }
                    if (!isset($logo)) {
                            foreach($InCompanyDir as $logofilename) {
                                    if (strncasecmp($logofilename,'labcode.jpg',8) === 0 AND
                                            is_readable($dir . $logofilename) AND
                                            is_file($dir . $logofilename)) {
                                            $logo = $logofilename;
                                            break;
                                    }
                            }
                    }
                    if (empty($logo)) {
                            return null;
                    } else {
                            return 'companies/' .$CompanyDir .'/'. $logo;
                    }
            } //end listing of files under company directory is not empty
    }

    /* Find a logo in companies/<company of this session> */
    if (!empty($_SESSION['DatabaseName'])) {
            $_SESSION['LABCODE'] = findLABCODEFile($_SESSION['DatabaseName'], $PathPrefix);
    }
}


if (empty($_SESSION['ILAC'])) {
  
    function findILACFile($CompanyDir, $PathPrefix) {
            $dir = $PathPrefix.'companies/' . $CompanyDir . '/';
            $DirHandle = dir($dir);
            while ($DirEntry = $DirHandle->read() ){
                    if ($DirEntry != '.' AND $DirEntry !='..'){
                            $InCompanyDir[] = $DirEntry; //make an array of all files under company directory
                    }
            } //loop through list of files in the company directory
            if ($InCompanyDir !== FALSE) {
                    foreach($InCompanyDir as $logofilename) {
                            if (strncasecmp($logofilename,'ILAC.png',8) === 0 AND
                                    is_readable($dir . $logofilename) AND
                                    is_file($dir . $logofilename)) {
                                    $logo = $logofilename;
                                    break;
                            }
                    }
                    if (!isset($logo)) {
                            foreach($InCompanyDir as $logofilename) {
                                    if (strncasecmp($logofilename,'ILAC.jpg',8) === 0 AND
                                            is_readable($dir . $logofilename) AND
                                            is_file($dir . $logofilename)) {
                                            $logo = $logofilename;
                                            break;
                                    }
                            }
                    }
                    if (empty($logo)) {
                            return null;
                    } else {
                            return 'companies/' .$CompanyDir .'/'. $logo;
                    }
            } //end listing of files under company directory is not empty
    }

    /* Find a logo in companies/<company of this session> */
    if (!empty($_SESSION['DatabaseName'])) {
            $_SESSION['ILAC'] = findILACFile($_SESSION['DatabaseName'], $PathPrefix);
    }
}




if (!isset($PageSecurity)){
 //only hardcoded in the UpgradeDatabase script - so old versions that don't have the scripts.pagesecurity field do not choke
     $PageSecurity = $_SESSION['PageSecurityArray'][$_SESSION['SCRIPT_NAME_BASE']];
     
}


// Now check that the user as logged in has access to the page being called. $SecurityGroups is an array of
// arrays defining access for each group of users. These definitions can be modified by a system admin under setup


if (!is_array($_SESSION['AllowedPageSecurityTokens']) AND !isset($AllowAnyone)) {
	$Title = _('Account Error Report');
	include($PathPrefix . 'includes/header.inc');
	echo '<br /><br /><br />';
	prnMsg(_('Security settings have not been defined for your user account. Please advise your system administrator. It could also be that there is a session problem with your PHP web server'),'error');
	include($PathPrefix . 'includes/footer.inc');
	exit;
}

/*The page security variable is now retrieved from the database in GetConfig.php and stored in the $SESSION['PageSecurityArray'] array
 * the key for the array is the script name - the script name is retrieved from the basename ($_SERVER['SCRIPT_NAME'])
 */
if (!isset($PageSecurity)){
 //only hardcoded in the UpgradeDatabase script - so old versions that don't have the scripts.pagesecurity field do not choke
     $PageSecurity = $_SESSION['PageSecurityArray'][$_SESSION['SCRIPT_NAME_BASE']];
     
}

if (!isset($AllowAnyone)){
	if ((!in_array($PageSecurity, $_SESSION['AllowedPageSecurityTokens'])
                OR !isset($PageSecurity))) {
		$Title = _('Security Permissions Problem');
		include($PathPrefix . 'includes/header.inc');
		echo '<tr><td class="menu_group_items"><table width="100%" class="table_index"><tr><td class="menu_group_item">';
		echo '<b><font style="size:+1; text-align:center;">' . _('The security settings on your account do not permit you to access this function') . '</font></b>';
		echo '</td></tr></table></td></tr>';
            
		include($PathPrefix . 'includes/footer.inc');
		exit;
	}
}



/*System administrator login */
if (in_array($_SESSION['PageSecurityArray']['WWW_Users.php'], $_SESSION['AllowedPageSecurityTokens'])) {
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

/*
if (sizeof($_POST) > 0 AND !isset($AllowAnyone)) {
	//Security check to ensure that the form submitted is originally sourced from webERP with the FormID = $_SESSION['FormID'] - which is set before the first login
	if (!isset($_POST['FormID']) OR ($_POST['FormID'] != $_SESSION['FormID'])) {
		$Title = _('Error in form verification');
		include('includes/header.inc');
		prnMsg(_('This form was not submitted with a correct ID') , 'error');
		include('includes/footer.inc');
		exit;
	}
}
*/
 
 