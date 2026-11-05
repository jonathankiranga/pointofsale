<?php
/* $Id: MaintenanceTasks.php 5231 2012-04-07 18:10:09Z daitnree $*/
include('includes/session.inc');
$Title = _('Customer Maintenance Schedule');
include('includes/header.inc');
 
$Role = array();
$Role['Ta'] = 'New Customer Lead';
$Role['Tb'] = 'Collect Debts from Sales';

echo '<p class="page_title_text"><img src="css/'.$Theme.'/images/group_add.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p><br />';

if (isset($_GET['Complete'])) {
   $result = DB_query("UPDATE crmtasks SET lastcompleted='" . Date('Y-m-d') . "' WHERE taskid='" . $_GET['TaskID'] . "'",$db);
}
##DATE_ADD("2017-06-15", INTERVAL 10 DAY); 
$sql="SELECT taskid,
            crmtasks.leadid,
            Customer,
            taskdescription,
            frequencydays,
            lastcompleted,
            DATE_ADD(lastcompleted, INTERVAL frequencydays day) AS duedate,
            userresponsible,
            realname,
            manager,
            userid
            FROM crmtasks
            INNER JOIN debtors
            ON crmtasks.leadid=debtors.itemcode
            left JOIN www_users
	    ON crmtasks.userresponsible=www_users.[salesman]
            WHERE followid='Tb' and (userid='" . $_SESSION['UserID'] . "'
            OR manager = '" . $_SESSION['UserID'] . "')
            ORDER BY lastcompleted DESC";

$ErrMsg = _('The maintenance schedule cannot be retrieved because');
$Result=DB_query($sql,$db,$ErrMsg);

echo '<table class="table table-bordered">
     <tr><th>' . _('Task ID') . '</th>
		<th>' . _('Customer') . '</th>
		<th>' . _('Debt Status') . '</th>
		<th>' . _('Last Updated') . '</th>
		<th>' . _('Due By') . '</td>
		<th>' . _('Person') . '</th>
		<th>' . _('Manager') . '</th>
		<th>' . _('Action has been taken') . '</th>
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
			<td>' . ConvertSQLDate($myrow['duedate']) . '</td>
			<td>' . $myrow['realname'] . '</td>
			<td>' . $ManagerName . '</td>
			<td><a href="SalesFollowups.php?Complete=Yes&amp;TaskID=' . $myrow['taskid'] .'" onclick="return confirm(\'' . _('Are you sure you wish to mark this maintenance task as completed?') . '\');">' . _('Mark Completed') . '</a></td>
		</tr>';
}

echo '</table><br /><br />';


include('includes/footer.inc');
?>