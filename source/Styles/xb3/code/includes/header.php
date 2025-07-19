<?php
/*
 If not stated otherwise in this file or this component's Licenses.txt file the
 following copyright and licenses apply:

 Copyright 2018 RDK Management

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

 http://www.apache.org/licenses/LICENSE-2.0

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
*/
?>
<?php
include_once __DIR__ .'/../CSRF-Protector-PHP/libs/csrf/csrfprotector_rdkb.php';
//Initialise CSRFGuard library
csrfprotector_rdkb::init();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<?php
        $modelName= getStr("Device.DeviceInfo.ModelName");
	header('X-robots-tag: noindex,nofollow');
	session_start();
        $curr_sessID = session_id();
	$url = $_SERVER['HTTP_HOST'];
	$Wan_IPv4 = getStr("Device.X_CISCO_COM_CableModem.IPAddress");
	$Wan_IPv6 = getStr("Device.X_CISCO_COM_CableModem.IPv6Address");

	//if user is entering literal IPv6 address then remove "[" and "]"
	$url = str_replace("[","",$url);
	$url = str_replace("]","",$url);
	$webuiEnabled = getStr("Device.DeviceInfo.X_RDKCENTRAL-COM_RFC.Feature.WebUI.Enable");
	$webuiIsEnabled = "true";
	if(!strcmp($url, $Wan_IPv4) || (inet_pton($url) == inet_pton($Wan_IPv6))){
        	$isMSO  = true;
	}
	else {
        	$isMSO  = false;
	}
	if(!$isMSO) {
        	if(($webuiEnabled == "Disable") || ($webuiEnabled == "MSOonly")){
                	$webuiIsEnabled="false";
        	}
	}else{
        	if($webuiEnabled == "Disable"){
                	$webuiIsEnabled="false";
        	}
	}
	if($webuiIsEnabled=="false"){
		session_destroy(); // Destroy all data in session
		echo '<script type="text/javascript">alert("Access Denied!"); location.href="index.php";</script>';
                exit(0);
	}	
	if (!isset($_SESSION["loginuser"])) {
            if (!isset($_SESSION["JWT_VALID"]) || $_SESSION['JWT_VALID'] != true ) {
		echo '<script type="text/javascript">alert("Please Login First!"); location.href="home_loggedout.php";</script>';
		exit(0);
            }
	}
        if (($modelName != "CGA4131COM") && ($modelName != "CGA4332COM")) {
            // session IP binding
            if (!isset($_SESSION['PREV_REMOTEADDR'])) {
                $_SESSION['PREV_REMOTEADDR'] = $_SERVER['REMOTE_ADDR'];
            }
            if ($_SERVER['REMOTE_ADDR'] != $_SESSION['PREV_REMOTEADDR']) {
                session_destroy(); // Destroy all data in session
                echo '<script type="text/javascript">alert("Please Login First!"); location.href="home_loggedout.php";</script>';
                exit(0);
            }
	}     
        else {
                        if ( strcmp($_SERVER['REMOTE_ADDR'], "172.31.255.45") == 0 ||
                        strcasecmp($_SERVER['REMOTE_ADDR'], "fe80::ffff:ac1f:ff2d") == 0 ||
                        strcasecmp($_SERVER['REMOTE_ADDR'], "fd00::ffff:ac1f:ff2d") == 0 ) {
                        exec("/usr/bin/snmpget -v 2c -c private -t 0.5 -r 1 172.31.255.45 1.3.6.1.4.1.4413.2.2.2.1.2.12161.1.1.27.0 |  awk '/STRING/ {print}' | awk '{print $4}'  > /tmp/IPADDR");
                        $fp=fopen("/tmp/IPADDR","r");
                        if($fp)
                        {

                                $pos = 0;
                                $buffer=trim(substr(fgets($fp),$pos));
                                fclose($fp);
                                $buffer = str_replace('"', "", $buffer);
                                $IP_ADDR = $buffer;
                                if (!isset($_SESSION['PREV_REMOTEADDR'])) {
                                    $_SESSION['PREV_REMOTEADDR'] = $buffer;
                                }
                        }
                    }
                    else {
                       $IP_ADDR = $_SERVER['REMOTE_ADDR'];
                       if (!isset($_SESSION['PREV_REMOTEADDR'])) {
                           $_SESSION['PREV_REMOTEADDR'] = $IP_ADDR;
                       }
                    }
                    $curr_IP = $IP_ADDR;
                    if ($IP_ADDR != $_SESSION['PREV_REMOTEADDR']) {
                        session_destroy(); // Destroy all data in session
                        echo '<script type="text/javascript">alert("Please Login First!"); location.href="home_loggedout.php";</script>';
                        exit(0);
                    }
                    if (!isset($_SESSION['PREV_SESSID'])) {
                        $_SESSION['PREV_SESSID'] = session_id();
                    }

                    if ($curr_sessID != $_SESSION['PREV_SESSID']) {
                        session_destroy(); // Destroy all data in session
                        echo '<script type="text/javascript">alert("Please Login First!"); location.href="home_loggedout.php";</script>';
                        exit(0);
                    }
                 }
	

	$not_cusadmin_pages = array('email_notification.php', 'routing.php', 'change_password.php', 'voice_quality_metrics' ,'qos', 'mta_Line_Diagnostics', 'mta_sip_packet_log', 'callsignallog.php', 'DSXlog.php', 'wan', 'dynamic_dns','wizard_step1','wizard_step2','DPoE','managed_apps');
	$not_admin_pages = array('email_notification.php', 'hs_port_forwarding', 'routing.php', 'dynamic_dns', 'mta', 'voice_quality_metrics' ,'qos');
	$not_bridge_static_pages = array('local_ip','wizard', 'firewall', 'managed', 'parental', 'forwarding', 'triggering', 'dmz', 'routing','DPoE','managed_apps', 'nat', 'wan', 'port_management', 'dynamic_dns', 'device_discovery');
	$not_mso_pages = array('password_change.php','hs_port_forwarding','wizard_step1.php','qos1','wizard_step2','DPoE','managed_apps');

        if($modelName == "CGA4131COM" || $modelName == "CGA4332COM") {
             $not_mso_pages_tccbr = array('moca.php','moca_diagnostics.php');
             $not_cusadmin_pages_tccbr = array('moca.php','moca_diagnostics.php');
                      if ($_SESSION['loginuser'] == 'cusadmin') {
                         foreach ($not_cusadmin_pages_tccbr as $page) {
                            if (strstr($_SERVER['SCRIPT_FILENAME'], $page)) {
                                if (strstr($_SERVER['SCRIPT_FILENAME'], 'staticrouting')) continue;
                                echo '<script type="text/javascript"> alert("Access Denied!"); window.history.back(); </script>';
                                exit(0);
                            }
                       }
                 }
                    if ($_SESSION['loginuser'] == 'mso') {
                        foreach ($not_mso_pages_tccbr as $page) {
                           if (strstr($_SERVER['SCRIPT_FILENAME'], $page)) {
                                if (strstr($_SERVER['SCRIPT_FILENAME'], 'staticrouting')) continue;
                                echo '<script type="text/javascript"> alert("Access Denied!"); window.history.back(); </script>';
                                exit(0);
                          }
                     }
                }
         }
	if ($_SESSION['loginuser'] == 'cusadmin') {
		foreach ($not_cusadmin_pages as $page) {
			if (strstr($_SERVER['SCRIPT_FILENAME'], $page)) {
				if (strstr($_SERVER['SCRIPT_FILENAME'], 'staticrouting')) continue;
				echo '<script type="text/javascript"> alert("Access Denied!"); window.history.back(); </script>';
				exit(0);	
			}
		}
	}
	else if ($_SESSION['loginuser'] == 'admin') {
		foreach ($not_admin_pages as $page) {
			if (strstr($_SERVER['SCRIPT_FILENAME'], $page)) {
				if (strstr($_SERVER['SCRIPT_FILENAME'], 'staticrouting')) continue;
				echo '<script type="text/javascript"> alert("Access Denied!"); window.history.back(); </script>';
				exit(0);	
			}
		}
	}
	else if ($_SESSION['loginuser'] == 'mso') {
		foreach ($not_mso_pages as $page) {
			if (strstr($_SERVER['SCRIPT_FILENAME'], $page)) {
				echo '<script type="text/javascript"> alert("Access Denied!"); window.history.back(); </script>';
				exit(0);	
			}
		}
	}
	if (isset($_SESSION['lanMode']) && $_SESSION["lanMode"] != "router") {
		foreach ($not_bridge_static_pages as $page) {
			if (strstr($_SERVER['SCRIPT_FILENAME'], $page)) {
				echo '<script type="text/javascript"> alert("Access Denied!"); window.history.back(); </script>';
				exit(0);	
			}
		}
	}
	/* demo flag in session */
	/*if (!isset($_SESSION['_DEBUG'])) {
		$_DEBUG = file_exists('/var/ui_dev_debug');
		$_SESSION['_DEBUG'] = $_DEBUG;
	}
	else {
		$_DEBUG = $_SESSION['_DEBUG'];
	}*/
	// disable timeout when debug mode
	//if ($_DEBUG) { $_SESSION["timeout"] = 100000; }

	$header_param = array(
		"Device.X_CISCO_COM_DeviceControl.LanManagementEntry.1.LanMode",
		"Device.X_CISCO_COM_DeviceControl.PowerSavingModeStatus"
	);
	$header_value = DmExtGetStrsWithRootObj("Device.X_CISCO_COM_DeviceControl.", $header_param);
/*
 *	bridge-static 		> Advanced Bridge Mode
 *	router 				> Bridge Mode Disabled
 *	full-bridge-static 	> Basic Bridge Mode
 */
	$lanMode 	= $header_value[1][1];
	$psmMode 	= $header_value[2][1];

    /*
    ** is GW works in Bridge mode or not
    */
	// $lanMode = 'bridge-static';
	if ("full-bridge-static" != $lanMode && "bridge-static" != $lanMode && "router" != $lanMode){
		$lanMode = "router";
	}
	// doc lanMode into session, for directly use it in function
	$_SESSION["lanMode"] = $lanMode;

    /*
    ** is GW works in PSM mode or not
    */
	// $psmMode = "Enabled";
	if ("Enabled" != $psmMode && "Disabled" != $psmMode){
		$psmMode = "Disabled";
	}
	// doc psmMode into session, for directly use it in function
	$_SESSION["psmMode"] = $psmMode;

?>


<head>
	<!--CSS-->
	<link rel="stylesheet" type="text/css" media="screen" href="./cmn/css/common-min.css?sid=<?php echo $_SESSION["sid"]; ?>" />
	<!--[if IE 6]>
	<link rel="stylesheet" type="text/css" href="./cmn/css/ie6-min.css" />
	<![endif]-->
	<!--[if IE 7]>
	<link rel="stylesheet" type="text/css" href="./cmn/css/ie7-min.css" />
	<![endif]-->
	<link rel="stylesheet" type="text/css" media="print" href="./cmn/css/print.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="./cmn/css/lib/jquery.radioswitch.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="./cmn/css/lib/progressBar.css" />
	<!--Character Encoding-->
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	<meta name="robots" content="noindex,nofollow">
    <script type="text/javascript" src="./cmn/js/lib/jquery-3.7.1.js"></script>
    <script type="text/javascript" src="./cmn/js/lib/jquery-migrate-1.2.1.js"></script>
    <script type="text/javascript" src="./cmn/js/lib/jquery.validate.js"></script>
    <script type="text/javascript" src="./cmn/js/lib/jquery.alerts.js"></script>
	<script type="text/javascript" src="./cmn/js/lib/jquery.ciscoExt.js"></script>
	<script type="text/javascript" src="./cmn/js/lib/jquery.highContrastDetect.js"></script>
	<script type="text/javascript" src="./cmn/js/lib/jquery.radioswitch.js"></script>
	<script type="text/javascript" src="./cmn/js/lib/jquery.virtualDialog.js"></script>
	<script type="text/javascript" src="./cmn/js/lib/bootstrap.min.js"></script>
    <script type="text/javascript" src="./cmn/js/lib/bootstrap-waitingfor.js"></script>
    <!-- update the version of utilityFunctions.js if any changes is made to this js file otherwise browser will take the old js file from the cache memory -->
	<script type="text/javascript" src="./cmn/js/utilityFunctions.js?v=1"></script>
    <script type="text/javascript" src="./cmn/js/comcast.js"></script>
    
	<style>
	#div-skip-to {
		position:relative; 
		left: 150px;
		top: -300px;
	}

	#div-skip-to a {
		position: absolute;
		top: 0;
	}

	#div-skip-to a:active, #div-skip-to a:focus {
		top: 300px;
		color: #0000FF;		
		/*background-color: #b3d4fc;*/
	}
	</style>	
</head>
<script type="text/javascript">
	$(document).ready(function() {
		if(document.cookie==""){
			var sessionCsrf= "<?php echo  $_SESSION['Csrf_token'] ;?>";
			sessionStorage.setItem("Csrfp_token",sessionCsrf);
		}
		$("table.data td").each(function() {
			if($(this).text().split("\n")[0].length > 25)
			{
				$(this).closest('table').css("table-layout", "fixed");
				$(this).css("word-wrap", "break-word");
			}
		});
	});
</script>

<body>
    <!--Main Container - Centers Everything-->
	<div id="container">

		<!--Header-->
		<div id="header">
			<h2 id="logo" style="margin-top: 10px"><img src="./cmn/img/logo_xfinity.png" alt="Company logo" title="Company logo" /></h2>
			<p style="margin: -0.7em 0;">
				<strong>
				<?php
					if($lanMode == "bridge-static") echo 'The Device is currently in Advanced Bridge Mode';
					if($lanMode == "full-bridge-static") echo 'The Device is currently in Basic Bridge Mode';
				?>
				</strong>
			</p>
		</div> <!-- end #header -->

		<div id='div-skip-to' style="display: none;">
			<a id="skip-link" name="skip-link" href="#content">Skip to content</a>
		</div>
		
		<!--Main Content-->
		<div id="main-content">
