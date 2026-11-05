<?php
include('includes/session.inc');
$Title = _('Company Preferences');
/* webERP manual links before header.inc */
$ViewTopic= 'CreatingNewSystem';
$BookMark = 'CompanyParameters';
include('includes/header.inc');


if (isset($Errors)) {
    unset($Errors);
}

//initialise no input errors assumed initially before we test
$InputError = 0;
$Errors = array();
$i=1;
$Da=array();

$ResultIndex=DB_query('SELECT [id],[Dimension_type] FROM [DimensionSetUp]', $db);
while($rows=DB_fetch_array($ResultIndex)){
    $Da[$rows['id']]= $rows['Dimension_type'];
}


$DatabaseName = $_SESSION['DatabaseName'];
$Path_To_Root = dirname(htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'));
$CompanyDir = 'companies/' . $DatabaseName .'/Signatures';

       
if (isset($_POST['submit'])) {
 	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */
	//first off validate inputs sensible
	if (mb_strlen($_POST['CoyName']) > 50 OR mb_strlen($_POST['CoyName'])==0) {
		$InputError = 1;
		prnMsg(_('The company name must be entered and be fifty characters or less long'), 'error');
		$Errors[$i] = 'CoyName';
		$i++;
	}

	if (mb_strlen($_POST['Email'])>0 and !IsEmailAddress($_POST['Email'])) {
		$InputError = 1;
		prnMsg(_('The email address is not correctly formed'),'error');
		$Errors[$i] = 'Email';
		$i++;
	}
       
        
        If(!empty($_FILES['SignatureFile_1'])){//We check the file upload situation
			if($_FILES['SignatureFile_1']['error'] == UPLOAD_ERR_INI_SIZE 
                                || $_FILES['SignatureFile_1']['error'] == UPLOAD_ERR_FORM_SIZE){//the file is over the php.ini limit or over the from limit
				$InputError = 1;
				if(upload_max_filesize < 0.01){
					prnMsg(_('The Signature 1 file failed to upload due to it\'s size. The file was over the upload_max_filesize set in your php.ini configuration.'),'error');
                            	}else{
					prnMsg(_('The Signature 1 file failed to upload as it was over 10KB size limit.'),'error');
				}

			}elseif($_FILES['SignatureFile_1']['error'] == UPLOAD_ERR_OK){
                                //The file has been successfully uploaded
				$File_Temp_Name = $_FILES['SignatureFile_1']['tmp_name'];
			}elseif($_FILES['SignatureFile_1']['error'] == UPLOAD_ERR_NO_FILE){
                                //There are no file uploaded by users
				$File_To_Copy1 = 1;
			}
                              if(isset($File_Temp_Name)){
					$Result = move_uploaded_file($File_Temp_Name, $CompanyDir . '/signature1.jpg');
				}
                                /*
                                elseif(isset($File_To_Copy1)){
					$Result = copy ($Path_To_Root . '/signature1.jpg',$CompanyDir.'/signature1.jpg');
				}*/
		}
        
        If(!empty($_FILES['SignatureFile_2'])){//We check the file upload situation
			if($_FILES['SignatureFile_2']['error'] == UPLOAD_ERR_INI_SIZE 
                                || $_FILES['SignatureFile_2']['error'] == UPLOAD_ERR_FORM_SIZE){
                            //the file is over the php.ini limit or over the from limit
				$InputError = 1;
                                
				if(upload_max_filesize < 0.01){
					prnMsg(_('The Signature 2 file failed to upload due to it\'s size. The file was over the upload_max_filesize set in your php.ini configuration.'),'error');

				}else{
					prnMsg(_('The Signature 2 file failed to upload as it was over 10KB size limit.'),'error');
				}

			}elseif($_FILES['SignatureFile_2']['error'] == UPLOAD_ERR_OK){//The file has been successfully uploaded
				$File_Temp_Name = $_FILES['SignatureFile_2']['tmp_name'];
			}elseif($_FILES['SignatureFile_2']['error'] == UPLOAD_ERR_NO_FILE){//There are no file uploaded by users
				$File_To_Copy2 = 1;
			}
                        
                           	if(isset($File_Temp_Name)){
					$Result = move_uploaded_file($File_Temp_Name, $CompanyDir . '/signature2.jpg');
				}
                                /*
                                elseif(isset($File_To_Copy2)){
					$Result = copy ($Path_To_Root . '/signature2.jpg',$CompanyDir.'/signature2.jpg');
				}*/
		}
        
        If(!empty($_FILES['SignatureFile_3'])){//We check the file upload situation
			if($_FILES['SignatureFile_3']['error'] == UPLOAD_ERR_INI_SIZE 
                                || $_FILES['SignatureFile_3']['error'] == UPLOAD_ERR_FORM_SIZE){
                            //the file is over the php.ini limit or over the from limit
				$InputError = 1;
                                
				if(upload_max_filesize < 0.01){
					prnMsg(_('The Signature 3 file failed to upload due to it\'s size.'
                                                . ' The file was over the upload_max_filesize set in your php.ini configuration.'),'error');

				}else{
					prnMsg(_('The Signature 3 file failed to upload as it was over 10KB size limit.'),'error');
				}

                                }elseif($_FILES['SignatureFile_3']['error'] == UPLOAD_ERR_OK){
                                        //The file has been successfully uploaded
                                        $File_Temp_Name = $_FILES['SignatureFile_3']['tmp_name'];
                                }elseif($_FILES['SignatureFile_3']['error'] == UPLOAD_ERR_NO_FILE){
                                        //There are no file uploaded by users
                                        $File_To_Copy3 = 1;
                                }
                        
                                
                           	if(isset($File_Temp_Name)){
					$Result = move_uploaded_file($File_Temp_Name, $CompanyDir . '/signature3.jpg');
				}/*
                                elseif(isset($File_To_Copy3)){
                         		$Result = copy ($Path_To_Root . '/signature3.jpg',$CompanyDir.'/signature3_2.jpg');
				}
                               */
		}
        
	if ($InputError !=1){

		$sql = "UPDATE companies SET coyname='" . $_POST['CoyName'] . "',
                        PIN = '" . $_POST['PIN'] . "',
                        VAT = '" . $_POST['VAT'] . "',
                        regoffice1='" . $_POST['RegOffice1'] . "',
                        regoffice2='" . $_POST['RegOffice2'] . "',
                        regoffice3='" . $_POST['RegOffice3'] . "',
                        regoffice4='" . $_POST['RegOffice4'] . "',
                        regoffice5='" . $_POST['RegOffice5'] . "',
                        regoffice6='" . $_POST['RegOffice6'] . "',
                        telephone='" . $_POST['Telephone'] . "',
                        [DefaultDimension_1] ='" .$_POST['DefaultDimension_1'] . "',
                        [DefaultDimension_2] ='" .$_POST['DefaultDimension_2'] . "',
                        Signaturename_1 ='" . $_POST['Signaturename_1'] . "',
                        Signaturename_2 ='" . $_POST['Signaturename_2'] . "',
                        Signaturename_3 ='" . $_POST['Signaturename_3'] . "',
                        fax ='" . $_POST['Fax'] . "',
                        email ='" . $_POST['Email'] . "'";
                
                        if( !empty($_FILES['SignatureFile_1'])){
                            $sql .= ",  Sign1='" . $CompanyDir . '/signature1.jpg' . "'";
                        }
                        
                        if( !empty($_FILES['SignatureFile_2'])){
                            $sql .= ",Sign2='" . $CompanyDir . '/signature2.jpg' . "'";
                        }
                        
                        if( !empty($_FILES['SignatureFile_3'])){
                            $sql .= ",Sign3='" . $CompanyDir . '/signature3.jpg' . "'";
                        }
                        
                        $sql .= "  WHERE coycode=1";

			$ErrMsg =  _('The company preferences could not be updated because');
			$result = DB_query($sql,$db,$ErrMsg);
			prnMsg( _('Company preferences updated'),'success');
			/* End of update currencies */

			$ForceConfigReload = True; // Required to force a load even if stored in the session vars
			include('includes/GetConfig.php');
			$ForceConfigReload = False;

	} else {
		prnMsg( _('Validation failed') . ', ' . _('no updates or deletes took place'),'warn');
	}

} /* end of if submit */

echo '<p class="page_title_text"><img src="css/'.$Theme.'/images/maintenance.png" title="' . _('Search') .'" alt="" />' . ' ' . $Title . '</p>';
echo '<form method="post" enctype="multipart/form-data" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
echo '<div><input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" /><table>';

if ($InputError != 1) {
	$sql = "SELECT coyname,
                        [PIN],
                        [vat],
                        regoffice1,
                        regoffice2,
                        regoffice3,
                        regoffice4,
                        regoffice5,
                        regoffice6,
                        telephone,
                        fax,
                        email,
                        Sign1,
                        Sign2,
                        Sign3,
                        DefaultDimension_1,
                        DefaultDimension_2,
                        Signaturename_1 ,
                        Signaturename_2,
                        Signaturename_3
                        FROM companies
                WHERE coycode=1";

	$ErrMsg =  _('The company preferences could not be retrieved because');
	$result = DB_query($sql,$db,$ErrMsg);
	$myrow = DB_fetch_array($result);

	$_POST['CoyName']     = $myrow['coyname'];
	$_POST['PIN']         = $myrow['PIN'];
        $_POST['VAT']         = $myrow['vat'];
	$_POST['RegOffice1']  = $myrow['regoffice1'];
	$_POST['RegOffice2']  = $myrow['regoffice2'];
	$_POST['RegOffice3']  = $myrow['regoffice3'];
	$_POST['RegOffice4']  = $myrow['regoffice4'];
	$_POST['RegOffice5']  = $myrow['regoffice5'];
	$_POST['RegOffice6']  = $myrow['regoffice6'];
	$_POST['Telephone']   = $myrow['telephone'];
	$_POST['Fax']         = $myrow['fax'];
	$_POST['Email']       = $myrow['email'];
        $signature1           = $myrow['Sign1'];
	$signature2           = $myrow['Sign2'];
        $signature3           = $myrow['Sign3'];
        $_POST['DefaultDimension_1'] =$myrow['DefaultDimension_1'];
	$_POST['DefaultDimension_2'] =$myrow['DefaultDimension_2'];
	$_POST['Signaturename_1'] = $myrow['Signaturename_1'] ;
        $_POST['Signaturename_2'] = $myrow['Signaturename_2'] ;
        $_POST['Signaturename_3'] = $myrow['Signaturename_3'] ;
        
        $_POST['Batchprefix']=GetLabPrefix();
}

echo '<tr><td  style="vertical-align:top"><table class="table table-bordered">';
echo '<tr><td>' . _('Name') . ' (' . _('to appear on reports') . '):</td>
		<td><input '.(in_array('CoyName',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="1" type="text" autofocus="autofocus" required="required" name="CoyName" value="' . stripslashes($_POST['CoyName']) . '"  pattern="?!^ +$"  title="' . _('Enter the name of the business. This will appear on all reports and at the top of each screen. ') . '" size="52" maxlength="50" /></td>
	</tr>';
echo '<tr>
		<td>' . _('PIN') . ':</td>
		<td><input '.(in_array('PIN',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="2" type="text" required="required"  name="PIN" value="' . $_POST['PIN'] . '" size="22" maxlength="20" /></td>
	</tr>';
echo '<tr>
		<td>' . _('VAT') . ':</td>
		<td><input '.(in_array('VAT',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="3" type="text" required="required"  name="VAT" value="' . $_POST['VAT'] . '" size="22" maxlength="20" /></td>
	</tr>';

echo '<tr>
		<td>' . _('Address Line 1') . ':</td>
		<td><input '.(in_array('RegOffice1',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="4" type="text" name="RegOffice1" title="' . _('Enter the first line of the company registered office. This will appear on invoices and statements.') . '" required="required" size="42" maxlength="40" value="' . stripslashes($_POST['RegOffice1']) . '" /></td>
	</tr>';

echo '<tr>
		<td>' . _('Address Line 2') . ':</td>'
        . ' <td><input '.(in_array('RegOffice2',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="5" type="text" name="RegOffice2" title="' . _('Enter the second line of the company registered office. This will appear on invoices and statements.') . '" size="42" maxlength="40" value="' . stripslashes($_POST['RegOffice2']) . '" /></td>
	</tr>';

echo '<tr>
		<td>' . _('Address Line 3') . ':</td>
		<td><input '.(in_array('RegOffice3',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="6" type="text" name="RegOffice3" title="' . _('Enter the third line of the company registered office. This will appear on invoices and statements.') . '" size="42" maxlength="40" value="' . stripslashes($_POST['RegOffice3']) . '" /></td>
	</tr>';

echo '<tr>
		<td>' . _('Address Line 4') . ':</td>
		<td><input '.(in_array('RegOffice4',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="7" type="text" name="RegOffice4" title="' . _('Enter the fourth line of the company registered office. This will appear on invoices and statements.') . '" size="42" maxlength="40" value="' . stripslashes($_POST['RegOffice4']) . '" /></td>
</tr>';

echo '<tr>
		<td>' . _('Address Line 5') . ':</td>
		<td><input '.(in_array('RegOffice5',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="8" type="text" name="RegOffice5" size="22" maxlength="20" value="' . stripslashes($_POST['RegOffice5']) . '" /></td>
	</tr>';

echo '<tr>
		<td>' . _('Address Line 6') . ':</td>
		<td><input '.(in_array('RegOffice6',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="9" type="text" name="RegOffice6" size="17" maxlength="15" value="' . stripslashes($_POST['RegOffice6']) . '" /></td>
	</tr>';

echo '<tr>
		<td>' . _('Telephone Number') . ':</td>
		<td><input '.(in_array('Telephone',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="10" type="tel" name="Telephone" required="required" title="' . _('Enter the main telephone number of the company registered office. This will appear on invoices and statements.') . '" size="26" maxlength="25" value="' . $_POST['Telephone'] . '" /></td>
	</tr>';

echo '<tr><td>' . _('Lab Batch Prefix') . ':</td>
	<td><input class="labref" id="labref" tabindex="11" type="text" name="Batchprefix" size="5" maxlength="3" value="' . $_POST['Batchprefix'] . '" /></td>
	</tr>';

echo '<tr><td>' . _('Email Address') . ':</td>
	<td><input '.(in_array('Email',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="12" type="email" name="Email" title="' . _('Enter the main company email address. This will appear on invoices and statements.') . '" required="required" placeholder="accounts@example.com" size="50" maxlength="55" value="' . $_POST['Email'] . '" /></td>
	</tr>';


echo '<tr><td COLSPAN="2"><B>Project Management Info(Accounting Purposes)</B></td></tr>';
echo '<tr><td><i>' . _('Dimension Type One') . ':</i></td><td><select name="DefaultDimension_1" tabindex="13"><option></option>';
foreach ($Da as $key => $value) {
    echo '<option value="'.$key.'"  '.(($_POST['DefaultDimension_1']==trim($key))?'selected="selected"':'').'>'.$value.'</option>';
}
echo  '</select></td></tr>';

echo '<tr><td><i>' . _('Dimension Type Two') . ':</i></td><td><select name="DefaultDimension_2" tabindex="14"><option></option>';
foreach ($Da as $key => $value) {     
    echo '<option value="'.$key.'"  '.(($_POST['DefaultDimension_2']==trim($key))?'selected="selected"':'').'>'.$value.'</option>';
       
}
echo '</select></td></tr>';
echo '</table></td>'
    . '<td style="vertical-align:top"><table class="table table-bordered">';
echo '<tr><td COLSPAN="2"><B>LAB SIGN OFF SIGNATURES</B></td></tr>';

ECHO '<tr><td>Name 1</td><td><input type="text" name="Signaturename_1"  value="'.$_POST['Signaturename_1'].'"  size="20" maxlength="30"/></td></tr>';

echo '<tr>
        <td><label for="Logo">Signature 1:</label></td><td>
        <input type="file" accept="image/jpg" name="SignatureFile_1" title="A jpg file up to 10k, and not greater than 170px x 80px" />
        <span>jpg file to 10k, not greater than 170px x 80px</span>
        <div><img src="'.$signature1.'" width="100" alt="First Signature" title="'.$_POST['Signaturename_1'].'" />
        </div></td>
    </tr>';

ECHO '<tr><td>Name 2</td><td><input type="text" name="Signaturename_2"  value="'.$_POST['Signaturename_2'].'" size="20" maxlength="30"/></td></tr>';

echo '<tr>
        <td><label for="Logo">Signature 2:</label></td><td>
        <input type="file" accept="image/jpg" name="SignatureFile_2" title="A jpg file up to 10k, and not greater than 170px x 80px" />
        <span>jpg file to 10k, not greater than 170px x 80px</span>
        <div><img src="'.$signature2.'" width="100" alt="Second Signature" title="'.$_POST['Signaturename_2'].'" />
        </div></td>
    </tr>';

ECHO '<tr><td>Name 3</td><td><input type="text" name="Signaturename_3"  value="'.$_POST['Signaturename_3'].'" size="20" maxlength="30"/></td></tr>';

echo '<tr>
        <td><label for="Logo">Signature 3:</label></td><td>
        <input type="file" accept="image/jpg" name="SignatureFile_3" title="A jpg file up to 10k, and not greater than 170px x 80px" />
        <span>jpg file to 10k, not greater than 170px x 80px</span>
        <div><img src="'.$signature3.'" width="100" alt="Third Signature" title="'.$_POST['Signaturename_3'].'" />
        </div></td>
    </tr>';


echo '</table><br /><div class="centre"><input tabindex="26" type="submit" name="submit" value="' . _('Update-Save') . '" /></div>';
echo '</td></tr></table>';
echo '</div></form>';

include('includes/footer.inc');

Function GetLabPrefix(){
    global $db;
  
    $SQL = SPRINTF("SELECT IFNULL(prefix,'') as prefix FROM systypes_1 WHERE typeid='%s'",'10');
    $ErrMsg =  _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': <BR>' . _('The next transaction number could not be retrieved from the database because');
    $DbgMsg =  _('The following SQL to retrieve the transaction number was used');
    $GetTransNoResult = DB_query($SQL,$db,$ErrMsg,$DbgMsg);
    $myrow = DB_fetch_row($GetTransNoResult);
    $prefix = $myrow[0];
   
    return $prefix   ;
}

$m = GetLabPrefix();
?><script>         
    $(".labref").change(function(){
        var oldref='<?php echo trim($m) ?>';
        var Ref=$("#labref").val();
        if(oldref != Ref){
            if(confirm('Do you want to Change the Batch Refix ? This will reset the Lab Batch No To 0')){
                $.post("Ajax/ConnectAjax.php",{
                    LabPrefix:Ref
                 },function(data){
                    alert(data)
                 });
            }
        }
       
    }); 
</script>