<?php
/* $Id: MaintenaceReminders.php 4551 2011-04-16 06:20:56Z daintree $*/
//this script can be set to run from cron
$AllowAnyone = true;
include('includes/session.inc');
$Title = _('Run Maintenance Reminders');
include('includes/header.inc');
include('Autoloadmailsettings.php');
#DATE_ADD("2017-06-15", INTERVAL 10 DAY); 

$sql="SELECT 	Customer,
                taskdescription,
                DATE_ADD(lastcompleted,INTERVAL frequencydays day) AS duedate,
                userresponsible,
                www_users.email
		FROM crmtasks
		JOIN debtors ON crmtasks.leadid=debtors.itemcode
		left JOIN www_users ON crmtasks.userresponsible=www_users.[salesman]
		WHERE DATE_ADD(lastcompleted,INTERVAL frequencydays-1 day) > sysdate()
		ORDER BY userresponsible";

$result = DB_query($sql,$db);
$LastUserResponsible = '';

while ($myrow = DB_fetch_array($result)){
	if (!isset(${'Mail' . $myrow['userresponsible']}) ) {
		
            if ($LastUserResponsible!=''){
			${'Mail' . $myrow['userresponsible']}->Body = $MailText;
		        ${'Mail' . $myrow['userresponsible']}->addAddress(array($LastUserEmail));
			$MailText = _('You have the following maintenance task(s) falling due or over-due:') . "\n";
		        ${'Mail' . $myrow['userresponsible']}->send();
            }
		$LastUserResponsible = $myrow['userresponsible'];
		$LastUserEmail = $myrow['email'];
                
		${'Mail' . $myrow['userresponsible']} = new Autoloadmailsettings();
                ${'Mail' . $myrow['userresponsible']}->load();
                ${'Mail' . $myrow['userresponsible']}->Subject = 'Followup Reminders';
        }	
        
	$MailText .=  " Customer : " . $myrow['Customer'] . "\n  Task: " . $myrow['taskdescription'] . "\n Due: " . ConvertSQLDate($myrow['duedate']);
	if (Date1GreaterThanDate2(ConvertSQLDate($myrow['duedate']),Date($_SESSION['DefaultDateFormat']))) {
		$MailText .= _('NB:This follow up is over due');
	}
	$MailText . "\n\n";
      
}


if (DB_num_rows($result)>0){
	${'Mail' . $LastUserResponsible}->Body($MailText);
        ${'Mail' . $LastUserResponsible}->addAddress(array(${'Mail' . $LastUserResponsible}));
        ${'Mail' . $LastUserResponsible}->send();
}
#DATE_ADD(lastcompleted,INTERVAL frequencydays day)
/* Now do manager emails for overdue jobs */
    $sql="SELECT   Customer,
                    taskdescription,
                    DATE_ADD(lastcompleted,INTERVAL frequencydays day) AS duedate,
                    realname,
                    userresponsible,
                    manager
		FROM crmtasks
		JOIN debtors ON crmtasks.leadid=debtors.itemcode
		left JOIN www_users ON crmtasks.manager=www_users.userid
		WHERE DATE_ADD(lastcompleted,INTERVAL frequencydays day)> sysdate()
		ORDER BY manager";

$result = DB_query($sql,$db);
$LastManager = '';

while ($myrow = DB_fetch_array($result)){
	if (!isset(${'Mail' . $myrow['manager']})) {
		if ($LastManager!=''){
			${'Mail' . $myrow['manager']}->Body = $MailText;
		        ${'Mail' . $myrow['manager']}->addAddress(array($LastManagerEmail));
			$MailText = "Your staff have failed to complete the following tasks by the due date:\n";
                        ${'Mail' .$myrow['manager']}->send();
		}
		$LastManager = $myrow['manager'];
		$LastManagerEmail = $myrow['email'];
                
                ${'Mail' . $myrow['manager']} = new Autoloadmailsettings();
                ${'Mail' . $myrow['manager']}->load();
          	${'Mail' . $myrow['manager']}->Subject = 'Overdue Maintenance Tasks Reminder';
		
	}
	$MailText .=  _('Customer:') .' ' . $myrow['Customer'] . "\n" . _('Task:') . ' ' . $myrow['taskdescription'] . "\n" . _('Due:') . ' ' . ConvertSQLDate($myrow['duedate']);
	$MailText . "\n\n";
}


if (DB_num_rows($result)>0){
  ${'Mail' . $LastManager}->Body = $MailText;
  ${'Mail' . $LastManager}->addAddress(array($LastManagerEmail));
  ${'Mail' .$LastManager}->send();
}



$sql="SELECT taskid,
                crmtasks.leadid,
                Customer,
                taskdescription,
                frequencydays,
                lastcompleted,
                userresponsible,
                realname,
                manager
		FROM crmtasks 
                JOIN debtors ON crmtasks.leadid=debtors.itemcode
		left JOIN www_users ON crmtasks.userresponsible=www_users.userid 
                WHERE DATE_ADD(lastcompleted,INTERVAL frequencydays-1 day) > sysdate()";

$ErrMsg = _('The maintenance task details cannot be retrieved because');
$Result=DB_query($sql,$db,$ErrMsg);

echo '<table class="table table-bordered">
     <tr>
		<th>' . _('Task ID') . '</th>
		<th>' . _('Customer') . '</th>
		<th>' . _('Description') . '</th>
		<th>' . _('Last Completed') . '</th>
		<th>' . _('Person') . '</th>
		<th>' . _('Manager') . '</th>
    </tr>';
while ($myrow=DB_fetch_array($Result)) {

	if ($myrow['manager']!=''){
		$ManagerResult = DB_query("SELECT realname FROM www_users WHERE userid='" . $myrow['manager'] . "'",$db);
		$ManagerRow = DB_fetch_array($ManagerResult);
		$ManagerName = $ManagerRow['realname'];
	} else {
		$ManagerName = _('No Manager Set');
	}

                  echo '<tr>
			<td>' . $myrow['taskid'] . '</td>
			<td>' . $myrow['Customer'] . '</td>
			<td>' . $myrow['taskdescription'] . '</td>
			<td>' . ConvertSQLDate($myrow['lastcompleted']) . '</td>
			<td>' . $myrow['realname'] . '</td>
			<td>' . $ManagerName . '</td>
		       </tr>';
}

echo '</table><br /><br />';


include('includes/footer.inc');



?>