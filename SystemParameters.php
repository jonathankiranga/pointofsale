<?php
include('includes/session.inc');
$Title = _('System Parameters');
$ViewTopic= 'GettingStarted';
$BookMark = 'SystemConfiguration';
include('includes/header.inc');
include('includes/CountriesArray.php');
 
echo '<p class="page_title_text"><img src="css/'.$Theme.'/images/maintenance.png" title="' . _('Supplier Types')
	. '" alt="" />' . $Title. '</p>';

if (isset($_POST['submit'])) {
	//initialise no input errors assumed initially before we test
$InputError = 0;

if($InputError !=1){
    $sql = array();
    if ($_SESSION['ProhibitPostingsBefore'] != $_POST['X_ProhibitPostingsBefore'] ) {
			$sql[] = "UPDATE config SET confvalue = '" . $_POST['X_ProhibitPostingsBefore']."' WHERE confname = 'ProhibitPostingsBefore'";
		}
    if ($_SESSION['DB_Maintenance'] != $_POST['X_DB_Maintenance'] ) {
			$sql[] = "UPDATE config SET confvalue = '". ($_POST['X_DB_Maintenance'])."' WHERE confname = 'DB_Maintenance'";
		}
    if ($_SESSION['DefaultDateFormat'] != $_POST['X_DefaultDateFormat'] ) {
            $sql[] = "UPDATE config SET confvalue = '".$_POST['X_DefaultDateFormat']."' WHERE confname = 'DefaultDateFormat'";
    }
    if ($_SESSION['DefaultTheme'] != $_POST['X_DefaultTheme'] ) {
            $sql[] = "UPDATE config SET confvalue = '".$_POST['X_DefaultTheme']."' WHERE confname = 'DefaultTheme'";
    }
    if ($_SESSION['part_pics_dir'] != $_POST['X_part_pics_dir'] ) {
			$sql[] = "UPDATE config SET confvalue = 'companies/" . $_SESSION['DatabaseName'] . '/' . $_POST['X_part_pics_dir']."' WHERE confname = 'part_pics_dir'";
		}
    if ($_SESSION['SmtpSetting'] != $_POST['X_SmtpSetting']){
            $sql[] = "UPDATE config SET confvalue = '" . $_POST['X_SmtpSetting'] . "' WHERE confname='SmtpSetting'";
     }
    if ($_SESSION['PageLength'] != $_POST['X_PageLength'] ) {
			$sql[] = "UPDATE config SET confvalue = '".$_POST['X_PageLength']."' WHERE confname = 'PageLength'";
    }
    
    if ($_SESSION['PageLeftLogo'] != $_POST['X_PageLeftLogo'] ) {
			$sql[] = "UPDATE config SET confvalue = '".$_POST['X_PageLeftLogo']."' WHERE confname = 'PageLeftLogo'";
    }
    
    if ($_SESSION['PageRightLogo'] != $_POST['X_PageRightLogo'] ) {
			$sql[] = "UPDATE config SET confvalue = '".$_POST['X_PageRightLogo']."' WHERE confname = 'PageRightLogo'";
    }
    
    
    if ($_SESSION['LowFile'] != $_POST['X_LowFile'] ) {
			$sql[] = "UPDATE config SET confvalue = '".$_POST['X_LowFile']."' WHERE confname = 'LowFile'";
    }
    if ($_SESSION['OptimumFile'] != $_POST['X_OptimumFile'] ) {
			$sql[] = "UPDATE config SET confvalue = '".$_POST['X_OptimumFile']."' WHERE confname = 'OptimumFile'";
    }
    if ($_SESSION['HighFile'] != $_POST['X_HighFile'] ) {
			$sql[] = "UPDATE config SET confvalue = '".$_POST['X_HighFile']."' WHERE confname = 'HighFile'";
    }
    
    if ($_SESSION['reports_dir'] != $_POST['X_reports_dir'] ) {
			$sql[] = "UPDATE config SET confvalue = 'companies/" . $_SESSION['DatabaseName'] . '/' . $_POST['X_reports_dir']."' WHERE confname = 'reports_dir'";
		} 
                
    if ($_SESSION['RomalpaClause'] != $_POST['X_RomalpaClause'] ) {
            $sql[] = "UPDATE config SET confvalue = '".$_POST['X_RomalpaClause']."' WHERE confname = 'RomalpaClause'";
    } 
    
    
    if ($_SESSION['Conformity'] != $_POST['X_Conformity'] ) {
            $sql[] = "UPDATE config SET confvalue = '".$_POST['X_Conformity']."' WHERE confname = 'Conformity'";
    } 
    
    if (isset($_POST['X_ItemDescriptionLanguages'])) {
          $ItemDescriptionLanguages = '';
          foreach ($_POST['X_ItemDescriptionLanguages'] as $ItemLanguage){
                  $ItemDescriptionLanguages .= $ItemLanguage .',';
          }

          if ($_SESSION['ItemDescriptionLanguages'] != $ItemDescriptionLanguages){
                  $sql[] = "UPDATE config SET confvalue='" . $ItemDescriptionLanguages . "' WHERE confname='ItemDescriptionLanguages'";
          }
      }          
        
    $ErrMsg =  _('The system configuration could not be updated because');
		if (sizeof($sql) > 1 ) {
			$result = DB_Txn_Begin($db);
			foreach ($sql as $line) {
				$result = DB_query($line,$db,$ErrMsg);
			}
			$result = DB_Txn_Commit($db);
		} elseif(sizeof($sql)==1) {
			$result = DB_query($sql,$db,$ErrMsg);
		}

		prnMsg( _('System configuration updated'),'success');

		$ForceConfigReload = True; // Required to force a load even if stored in the session vars
		include('includes/GetConfig.php');
		$ForceConfigReload = False;
}else {
		prnMsg( _('Validation failed') . ', ' . _('no updates or deletes took place'),'warn');
	}


}

echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
echo '<div class="card">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<table class="table-bordered">';

$TableHeader = '<tr>
                    <th>' . _('System Variable Name') . '</th>
                    <th>' . _('Value') . '</th>
                    <th>' . _('Notes') . '</th>
                </tr>';

echo '<tr><th colspan="3">' . _('General Settings') . '</th></tr>';
echo $TableHeader;

// DefaultDateFormat
echo '<tr style="outline: 1px solid"><td>' . _('Default Date Format') . ':</td>
	<td><select name="X_DefaultDateFormat">
	<option '.(($_SESSION['DefaultDateFormat']=='d/m/Y')?'selected="selected" ':'').'value="d/m/Y">' . _('d/m/Y') . '</option>
	<option '.(($_SESSION['DefaultDateFormat']=='d.m.Y')?'selected="selected" ':'').'value="d.m.Y">' . _('d.m.Y') . '</option>
	<option '.(($_SESSION['DefaultDateFormat']=='m/d/Y')?'selected="selected" ':'').'value="m/d/Y">' . _('m/d/Y') . '</option>
	<option '.(($_SESSION['DefaultDateFormat']=='Y/m/d')?'selected="selected" ':'').'value="Y/m/d">' . _('Y/m/d') . '</option>
	<option '.(($_SESSION['DefaultDateFormat']=='Y-m-d')?'selected="selected" ':'').'value="Y-m-d">' . _('Y-m-d') . '</option>
	</select></td>
	<td>' . _('The default date format for entry of dates and display.') . '</td></tr>';

// DefaultTheme
echo '<tr style="outline: 1px solid"><td>' . _('New Users Default Theme') . ':</td>
	 <td><select name="X_DefaultTheme">';
$ThemeDirectory = dir('css/');
while (false != ($ThemeName = $ThemeDirectory->read())){
	if (is_dir("css/$ThemeName") AND $ThemeName != '.' AND $ThemeName != '..' AND $ThemeName != '.svn'){
		if ($_SESSION['DefaultTheme'] == $ThemeName) {
			echo '<option selected="selected" value="' . $ThemeName . '">' . $ThemeName . '</option>';
		} else {
			echo '<option value="' . $ThemeName . '">' . $ThemeName . '</option>';
		}
	}
}
echo '</select></td>
	<td>' . _('The default theme is used for new users who have not yet defined the display colour scheme theme of their choice') . '</td></tr>';
//ItemDescriptionLanguages
if (!isset($_POST['X_ItemDescriptionLanguages'])){
	$_POST['X_ItemDescriptionLanguages'] = explode(',',$_SESSION['ItemDescriptionLanguages']);
}
echo '<tr style="outline: 1px solid">
		<td>' . _('Languages to Maintain Translations for Item Descriptions') . ':</td>
		<td><select name="X_ItemDescriptionLanguages[]" size="3" multiple="multiple" >';
		echo '<option value="">' . _('None')  . '</option>';
foreach ($LanguagesArray as $LanguageEntry => $LanguageName){
	if (isset($_POST['X_ItemDescriptionLanguages']) AND in_array($LanguageEntry,$_POST['X_ItemDescriptionLanguages'])){
		echo '<option selected="selected" value="' . $LanguageEntry . '">' . $LanguageName['LanguageName']  . '</option>';
	} elseif ($LanguageEntry != $DefaultLanguage) {
		echo '<option value="' . $LanguageEntry . '">' . $LanguageName['LanguageName']  . '</option>';
	}
}
echo '</select></td>
		<td>' . _('Select the languages in which translations of the item description will be maintained. The default language is excluded.') . '</td>
	</tr>';
//$part_pics_dir
echo '<tr style="outline: 1px solid"><td>' . _('The directory where images are stored') . ':</td><td><select name="X_part_pics_dir">';


$CompanyDirectory = 'companies/' . $_SESSION['DatabaseName'] . '/';
$DirHandle = dir($CompanyDirectory);

while ($DirEntry = $DirHandle->read() ){
    if (is_dir($CompanyDirectory . $DirEntry)
        AND $DirEntry != '..'
        AND $DirEntry!='.'
        AND $DirEntry!='.svn'
        AND $DirEntry != 'CVS'
        AND $DirEntry != 'reports'
        AND $DirEntry != 'locale'
        AND $DirEntry != 'fonts'   ){

        if ($_SESSION['part_pics_dir'] == $CompanyDirectory . $DirEntry){
                echo '<option selected="selected" value="' . $DirEntry . '">' . $DirEntry . '</option>';
        } else {
                echo '<option value="' . $DirEntry . '">' . $DirEntry  . '</option>';
        }
    }
}

echo '</select></td>
	<td>' . _('The directory under which all image files should be stored. Image files take the format of ItemCode.jpg - they must all be .jpg files and the part code will be the name of the image file. This is named automatically on upload. The system will check to ensure that the image is a .jpg file') . '</td>
	</tr>';

//Whenever Labworks East Africa Ltd issues a statement of conformity, the decision rules are applied taking into considerations the uncertainty of measurements for the individual parameters. The statements shall be issued on outright "PASS" and outright "FAIL". Conditional PASS or FAIL can maybe issued for borderline results at the discretion of the Laboratory.												

echo '<tr style="outline: 1px solid">
	<td>' . _('Statement of Conformity'). '</td>
	<td colspan="2"><textarea  rows="2" cols="100"   title="'._('Statement of Conformity').'" name="X_Conformity">' . $_SESSION['Conformity'] . '</textarea></td>
    </tr>';

/*Perform Database maintenance DB_Maintenance*/
echo '<tr style="outline: 1px solid"><td>' . _('Perform Database Maintenance At Logon') . ':</td><td><select name="X_DB_Maintenance">';
	
	if ($_SESSION['DB_Maintenance']=='0'){
		echo '<option selected="selected" value="0">' . _('Un-Restricted') . '</option>';
	} else {
		echo '<option value="0">' . _('Un-Restricted') . '</option>';
	}
	
        if ($_SESSION['DB_Maintenance']=='-1'){
		echo '<option selected="selected" value="-1">' . _('Allow SysAdmin Access Only') . '</option>';
	} else {
		echo '<option value="-1">' . _('Allow SysAdmin Access Only') . '</option>';
	}

echo '</select></td>
	<td>' . _('Uses the function DB_Maintenance defined in ConnectDB.inc to perform database maintenance tasks, to run at regular intervals - checked at each and every user login') . '</td>
	</tr>';

//PageLength
echo '<tr style="outline: 1px solid"><td>' . _('Report Page Length') . ':</td>
	<td><input type="text" class="integer" pattern="(?!^0\d*$)[\d]{1,3}" title="'._('The input should be between 1 and 999').'" placeholder="'._('1 to 999').'" name="X_PageLength" size="4" maxlength="6" value="' . $_SESSION['PageLength'] . '" /></td><td>&nbsp;</td>
</tr>';

//PageLogo
echo '<tr style="outline: 1px solid"><td>' . _('Report Page Left LOGO') . ':</td>
	<td><select name="X_PageLeftLogo">';

$CompanyDirectory = 'companies/' . $_SESSION['DatabaseName'] . '/';
$DirHandle = dir($CompanyDirectory);

while ($DirEntry = $DirHandle->read() ){
   if (is_file($CompanyDirectory . $DirEntry)){
        if ($_SESSION['PageLeftLogo'] == $CompanyDirectory . $DirEntry){
            echo '<option selected="selected" value="' . $CompanyDirectory . $DirEntry . '">' . $CompanyDirectory . $DirEntry . '</option>';
        } else {
            echo '<option value="' . $CompanyDirectory . $DirEntry . '">' . $CompanyDirectory . $DirEntry  . '</option>';
        }
    }
}

echo '</select></td><td>'._('This is left Report logo').'</td></tr>';

//PageLogo
echo '<tr style="outline: 1px solid"><td>' . _('Report Page Right LOGO') . ':</td><td><select name="X_PageRightLogo">';

$CompanyDirectory = 'companies/' . $_SESSION['DatabaseName'] . '/';
$DirHandle = dir($CompanyDirectory);

while ($DirEntry = $DirHandle->read() ){
   if (is_file($CompanyDirectory . $DirEntry)){
        if ($_SESSION['PageRightLogo'] == $CompanyDirectory . $DirEntry){
            echo '<option selected="selected" value="' . $CompanyDirectory . $DirEntry . '">' . $CompanyDirectory . $DirEntry . '</option>';
        } else {
            echo '<option value="' . $CompanyDirectory . $DirEntry . '">' . $CompanyDirectory . $DirEntry  . '</option>';
        }
    }
}
echo '</select></td><td>'._('This is right Report logo').'</td></tr>';

//Testreport low colour
echo '<tr style="outline: 1px solid"><td>' . _('Test Report Low colour') . ':</td><td><select name="X_LowFile">';
$CompanyDirectory = 'companies/' . $_SESSION['DatabaseName'] . '/';
$DirHandle = dir($CompanyDirectory);
while ($DirEntry = $DirHandle->read() ){
   if (is_file($CompanyDirectory . $DirEntry)){
        if ($_SESSION['LowFile'] == $CompanyDirectory . $DirEntry){
            echo '<option selected="selected" value="' . $CompanyDirectory . $DirEntry . '">' . $CompanyDirectory . $DirEntry . '</option>';
        } else {
            echo '<option value="' . $CompanyDirectory . $DirEntry . '">' . $CompanyDirectory . $DirEntry  . '</option>';
        }
    }
}
echo '</select></td><td>'._('This is Test Report Low colour').'</td></tr>';

//Testreport low colour
echo '<tr style="outline: 1px solid"><td>' . _('Test Report Optimum colour') . ':</td><td><select name="X_OptimumFile">';
$CompanyDirectory = 'companies/' . $_SESSION['DatabaseName'] . '/';
$DirHandle = dir($CompanyDirectory);
while ($DirEntry = $DirHandle->read() ){
   if (is_file($CompanyDirectory . $DirEntry)){
        if ($_SESSION['OptimumFile'] == $CompanyDirectory . $DirEntry){
            echo '<option selected="selected" value="' . $CompanyDirectory . $DirEntry . '">' . $CompanyDirectory . $DirEntry . '</option>';
        } else {
            echo '<option value="' . $CompanyDirectory . $DirEntry . '">' . $CompanyDirectory . $DirEntry  . '</option>';
        }
    }
}
echo '</select></td><td>'._('This is Test Report Optimum  colour').'</td></tr>';

//Testreport low colour
echo '<tr style="outline: 1px solid"><td>' . _('Test Report High colour') . ':</td><td><select name="X_HighFile">';
$CompanyDirectory = 'companies/' . $_SESSION['DatabaseName'] . '/';
$DirHandle = dir($CompanyDirectory);
while ($DirEntry = $DirHandle->read() ){
   if (is_file($CompanyDirectory . $DirEntry)){
        if ($_SESSION['HighFile'] == $CompanyDirectory . $DirEntry){
            echo '<option selected="selected" value="' . $CompanyDirectory . $DirEntry . '">' . $CompanyDirectory . $DirEntry . '</option>';
        } else {
            echo '<option value="' . $CompanyDirectory . $DirEntry . '">' . $CompanyDirectory . $DirEntry  . '</option>';
        }
    }
}
echo '</select></td><td>'._('This is Test Report High colour').'</td></tr>';



 //$reports_dir
echo '<tr style="outline: 1px solid"><td>' . _('The directory where reports are stored') . ':</td>
	<td><select name="X_reports_dir">';

$DirHandle = dir($CompanyDirectory);

while (false != ($DirEntry = $DirHandle->read())){

	if (is_dir($CompanyDirectory . $DirEntry)
		AND $DirEntry != '..'
		AND $DirEntry != 'includes'
		AND $DirEntry!='.'
		AND $DirEntry!='.svn'
		AND $DirEntry != 'doc'
		AND $DirEntry != 'css'
		AND $DirEntry != 'CVS'
		AND $DirEntry != 'sql'
		AND $DirEntry != 'part_pics'
		AND $DirEntry != 'locale'
		AND $DirEntry != 'fonts'      ){

		if ($_SESSION['reports_dir'] == $CompanyDirectory . $DirEntry){
			echo '<option selected="selected" value="' . $DirEntry . '">' . $DirEntry . '</option>';
		} else {
			echo '<option value="' . $DirEntry . '">' . $DirEntry  . '</option>';
		}
	}
}

echo '</select></td>
	<td>' . _('The directory under which all report pdf files should be created in. A separate directory is recommended') . '</td>
	</tr>';



//DefaultDisplayRecordsMax
echo '<tr style="outline: 1px solid">
		<td>' . _('This is the invoice footer') . ':</td>
		<td colspan="2"><textarea id="ParameterName" rows="2" cols="100"  required="required" title="'._('This is the invoice footer').'" name="X_RomalpaClause">' . $_SESSION['RomalpaClause'] . '</textarea></td>
	</tr>';


echo '</table>
	<br /><div class="centre"><input type="submit" name="submit" value="' . _('Update') . '" /></div>
    </div></form>';

include('includes/footer.inc');
?>