<?php
// remove header
header_remove('ETag');
header_remove('Pragma');
header_remove('Cache-Control');
header_remove('Last-Modified');
header_remove('Expires');

// set header
header('Expires: Thu, 1 Jan 1970 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0',false);
header('Pragma: no-cache');

include('config.php');
if (isset($SessionSavePath)){
  session_save_path($SessionSavePath);
}

if (!isset($SysAdminEmail)) {
    $SysAdminEmail='';
}

if (!isset($_SESSION)) {
    ini_set('session.gc_maxlifetime',$SessionLifeTime);

    if(!ini_get('safe_mode') ){
       set_time_limit($MaximumExecutionTime);
       ini_set('max_execution_time',$MaximumExecutionTime);
    }
    session_write_close();  
    session_name('smartlimserp');
    session_start();
}

if(isset($_GET['link'])){
   $getpage =  $_GET['link'];
   $_SESSION['SCRIPT_NAME_BASE']="$getpage.php" ;
}

if(isset($_GET['Stocks']) && $_GET['Stocks'] == '1'){
    include('Stocks.php');
}elseif($_SESSION['SCRIPT_NAME_BASE']=='Logout.php'){
    session_unset();
    session_destroy();
    $PageSecurity=0;
        
    include('includes/session.inc');
    $Title =_('index');
    include('includes/bootstrapheader.inc');
    echo '<main id="view-panel" class="container">';
    include('includes/boostrapfooter.inc');
    
}elseif(isset($_SESSION['SCRIPT_NAME_BASE'])){
    include $_SESSION['SCRIPT_NAME_BASE'] ;
}else{
    $PageSecurity=0;
    include('includes/session.inc');
    $Title =_('index');
    include('includes/bootstrapheader.inc');
    echo '<main id="view-panel" class="container">';
    include('includes/boostrapfooter.inc');
   
}