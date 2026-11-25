<?php
/*
 * Salon back-office application shell.
 * Navigation follows the Smart ERP LIMS workspace pattern:
 * persistent application banner/menu/footer with PHP pages loaded in a
 * dedicated content iframe. Each destination retains its own session,
 * permissions, header, footer and form handling.
 */
session_name('smartlimserp');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$PageSecurity = isset($_SESSION['PageSecurityArray']['CompanyPreferences.php'])
    ? $_SESSION['PageSecurityArray']['CompanyPreferences.php'] : -1;
$PathPrefix = '';
include('includes/globalsession.inc');

$RootPath = isset($RootPath) ? $RootPath : '';
$RootPath = rtrim($RootPath, '/\\');
if ($RootPath === '.' || $RootPath === '/') {
    $RootPath = '';
}
$companyName = isset($_SESSION['CompanyRecord']['coyname'])
    ? $_SESSION['CompanyRecord']['coyname'] : 'Salon POS';
$userName = isset($_SESSION['UsersRealName']) ? $_SESSION['UsersRealName'] : 'User';

$salonMenuItems = array(
    array('Salon', '', '', '', '', '', '', '', '', '', '', ''),
    array('|Workspace', '', '', '', '', '', '', '', '', '', '', ''),
    array('||Dashboard', 'SalonDashboard.php', '', '', '', '', '', '', '', '', '', ''),
    array('||Appointments', 'SalonManagement.php?view=appointments', '', '', '', '', '', '', '', '', '', ''),
    array('||Services', 'SalonManagement.php?view=services', '', '', '', '', '', '', '', '', '', ''),
    array('||Hairdressers', 'SalonManagement.php?view=hairdressers', '', '', '', '', '', '', '', '', '', ''),
    array('|Customers', '', '', '', '', '', '', '', '', '', '', ''),
    array('||Customer Records', 'Customer.php', '', '', '', '', '', '', '', '', '', ''),
    array('|Sales and Reports', '', '', '', '', '', '', '', '', '', '', ''),
    array('||Sales and Z Reports', 'POSReports.php', '', '', '', '', '', '', '', '', '', ''),
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Salon POS</title>
    <link rel="icon" href="<?php echo htmlspecialchars($RootPath, ENT_QUOTES, 'UTF-8'); ?>/favicon.ico">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($RootPath, ENT_QUOTES, 'UTF-8'); ?>/css/bootstrap5.min.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($RootPath, ENT_QUOTES, 'UTF-8'); ?>/css/fontawesome6.4.0.all.min.css">
    <style>
        *{box-sizing:border-box}
        html,body{height:100%;margin:0;overflow:hidden;font-family:Arial,sans-serif;background:#eef2f7;color:#203047}
        .salon-shell{height:100vh;display:flex;flex-direction:column}
        .salon-banner{height:58px;min-height:58px;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:0 22px;background:#172b4d;color:#fff;box-shadow:0 2px 8px rgba(15,23,42,.15);z-index:20}
        .salon-brand{display:flex;align-items:center;gap:12px;font-size:16px;font-weight:700;white-space:nowrap}
        .salon-brand-mark{width:34px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:9px;background:#2b6695;color:#fff}
        .salon-banner-meta{display:flex;align-items:center;gap:17px;color:#dbe7f3;font-size:12px}
        .salon-banner a{color:#fff;text-decoration:none}
        .salon-banner a:hover{text-decoration:underline}
        .salon-menu-wrap{min-height:44px;background:#fff;border-bottom:1px solid #dce4ed;z-index:15;overflow:visible}
        #salonAppMenu{min-height:44px;display:flex;align-items:center;padding-left:8px}
        #salonAppMenu>div{margin:0!important}
        .salon-content{flex:1;min-height:0;padding:12px 14px;background:#eef2f7}
        .salon-content iframe{width:100%;height:100%;display:block;border:1px solid #dfe6ee;border-radius:9px;background:#fff;box-shadow:0 4px 16px rgba(15,23,42,.06)}
        .salon-footer{height:30px;min-height:30px;display:flex;align-items:center;gap:18px;padding:0 16px;background:#fff;border-top:1px solid #dce4ed;color:#64748b;font-size:11px}
        .salon-footer .status{margin-left:auto;color:#28734d}
        @media(max-width:700px){.salon-banner{padding:0 11px}.salon-banner-meta .user-name{display:none}.salon-content{padding:5px}.salon-brand{font-size:14px}}
    </style>
</head>
<body>
<div class="salon-shell">
    <header class="salon-banner">
        <div class="salon-brand"><span class="salon-brand-mark"><i class="fa fa-scissors"></i></span><span>Salon POS</span></div>
        <div class="salon-banner-meta">
            <span class="user-name"><?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></span>
            <a href="SalonPOS.php" target="_blank" rel="noopener"><i class="fa fa-cash-register"></i> Open Salon Terminal</a>
            <a href="Logout.php"><i class="fa fa-sign-out-alt"></i> Log out</a>
        </div>
    </header>
    <nav class="salon-menu-wrap" aria-label="Salon navigation">
        <div id="salonAppMenu">
            <script src="<?php echo htmlspecialchars($RootPath, ENT_QUOTES, 'UTF-8'); ?>/javascripts/menu/dmenu.js"></script>
            <script src="<?php echo htmlspecialchars($RootPath, ENT_QUOTES, 'UTF-8'); ?>/javascripts/menu/dmenu_key.js"></script>
            <script>
                var itemStylesNames=[], menuStylesNames=[];
                var isHorizontal=1, smColumns=1, smOrientation=0, dmRTL=0;
                var pressedItem=-2, itemCursor="pointer", itemTarget="salonContentFrame";
                var statusString="link", blankImage="images/blank.gif";
                var pathPrefix_img="javascripts/menu/", pathPrefix_link="";
                var menuWidth="240px", menuHeight="", smWidth="", smHeight="";
                var absolutePos=0, posX="8px", posY="8px", topDX=0, topDY=0, DX=0, DY=0;
                var subMenuAlign="left", subMenuVAlign="top";
                var fontStyle=["normal 13px Arial","normal 13px Arial"];
                var fontColor=["#203047","#FFFFFF"], fontDecoration=["none","none"];
                var fontColorDisabled="#94A3B8";
                var menuBackColor="#FFFFFF", menuBackImage="", menuBackRepeat="repeat";
                var menuBorderColor="#D7E0EA", menuBorderWidth=1, menuBorderStyle="solid";
                var itemBackColor=["transparent","#245B86"], itemBackImage=["",""];
                var beforeItemImage=["",""], afterItemImage=["",""];
                var beforeItemImageW="", afterItemImageW="", beforeItemImageH="", afterItemImageH="";
                var itemBorderWidth=0, itemBorderColor=["#D7E0EA","#245B86"];
                var itemBorderStyle=["solid","solid"], itemSpacing=0;
                var itemPadding="8px 12px", itemAlignTop="left", itemAlign="left";
                var iconTopWidth=18, iconTopHeight=16, iconWidth=18, iconHeight=16;
                var arrowWidth=7, arrowHeight=7;
                var arrowImageMain=["images/arr_black_2.gif","images/arr_white_2.gif"];
                var arrowWidthSub=0, arrowHeightSub=0;
                var arrowImageSub=["images/arr_black_2.gif","images/arr_white_2.gif"];
                var separatorImage="", separatorWidth="90%", separatorHeight="1px";
                var separatorAlignment="center", separatorVImage="", separatorVWidth="1px";
                var separatorVHeight="100%", separatorPadding="3px";
                var floatable=0, floatIterations=6, floatableX=1, floatableY=1;
                var floatableDX=15, floatableDY=15, movable=0, moveWidth=12, moveHeight=20;
                var moveColor="#DECA9A", moveImage="", moveCursor="move", smMovable=0;
                var closeBtnW=15, closeBtnH=15, closeBtn="";
                var transparency="100", transition=0, transOptions="", transDuration=0, transDuration2=0;
                var shadowLen=3, shadowColor="#94A3B8", shadowTop=1;
                var cssStyle=0, cssSubmenu="", cssItem=["",""], cssItemText=["",""];
                var dmObjectsCheck=0, saveNavigationPath=1, showByClick=0, noWrap=1;
                var smShowPause=150, smHidePause=500, smSmartScroll=1, topSmartScroll=0;
                var smHideOnClick=1, dm_writeAll=0, useIFRAME=0, dmSearch=0;
                var dmAJAX=0, dmAJAXCount=0, ajaxReload=0, dynamic=0, popupMode=0;
                var keystrokes=1, dm_focus=1, dm_actKey=113, onOverSnd="", onClickSnd="";
                var itemStyles=[], menuStyles=[];
                var menuItems=<?php echo json_encode($salonMenuItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
                dm_init();
            </script>
        </div>
    </nav>
    <main class="salon-content">
        <iframe id="salonContentFrame" name="salonContentFrame"
            src="<?php echo htmlspecialchars($RootPath, ENT_QUOTES, 'UTF-8'); ?>/SalonDashboard.php"
            title="Salon back-office workspace"></iframe>
    </main>
    <footer class="salon-footer">
        <span><?php echo htmlspecialchars(date('l, j F Y'), ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="status" id="salonWorkspaceStatus">Ready</span>
        <span><?php echo htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8'); ?></span>
    </footer>
</div>
<script>
(function(){
    var dock=document.getElementById('salonAppMenu');
    if(!dock)return;
    var menu=document.getElementById('dm0m0') || document.querySelector('div[id^="dm"]');
    if(menu){menu.style.position='relative';menu.style.top='0';menu.style.left='0';menu.style.margin='0';menu.style.zIndex='30';if(!dock.contains(menu))dock.appendChild(menu);}
    var frame=document.getElementById('salonContentFrame');
    if(frame){frame.addEventListener('load',function(){var status=document.getElementById('salonWorkspaceStatus');if(status)status.textContent='Ready';});}
    document.addEventListener('click',function(e){
        var link=e.target.closest ? e.target.closest('a[target="salonContentFrame"]') : null;
        if(link){var status=document.getElementById('salonWorkspaceStatus');if(status)status.textContent='Loading '+(link.textContent||'page').trim()+'...';}
    });
})();
</script>
</body>
</html>
