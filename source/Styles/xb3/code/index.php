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
<?php include('includes/utility.php'); ?>
<?php
header('X-robots-tag: noindex,nofollow');

$DeviceControl_param = array(
	"LanGwIPv4"	=> "Device.X_CISCO_COM_DeviceControl.LanManagementEntry.1.LanIPAddress",
	"lanMode"	=> "Device.X_CISCO_COM_DeviceControl.LanManagementEntry.1.LanMode",
	"psmMode"	=> "Device.X_CISCO_COM_DeviceControl.PowerSavingModeStatus",
	);
$DeviceControl_value = KeyExtGet("Device.X_CISCO_COM_DeviceControl.", $DeviceControl_param);


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

/*
 *	bridge-static 		> Advanced Bridge Mode
 *	router 				> Bridge Mode Disabled
 *	full-bridge-static 	> Basic Bridge Mode
 */

$lanMode = $DeviceControl_value['lanMode'];
$psmMode = $DeviceControl_value['psmMode'];

/*-------- redirection logic - uncomment the code below while checking in --------*/
	//$LanGwIPv4
	$LanGwIPv4 = $DeviceControl_value['LanGwIPv4'];

	//$LanGwIPv6
	$interface = getStr("com.cisco.spvtg.ccsp.pam.Helper.FirstDownstreamIpInterface");
	$idArr = explode(",", getInstanceIds($interface."IPv6Address."));
	foreach ($idArr as $key => $value) {
		$ipv6addr = getStr($interface."IPv6Address.$value.IPAddress");
		if (stripos($ipv6addr, "fe80::") !== false) {
			$LanGwIPv6 = $ipv6addr;
		}
		else{
			$LanGwIPv6 = $ipv6addr;
		}
	}

if(!$isMSO) {
	setStr("Device.DeviceInfo.X_RDKCENTRAL-COM_UI_ACCESS","ui_access",true);
	if(($webuiEnabled == "Disable") || ($webuiEnabled == "MSOonly")){
        	$webuiIsEnabled="false";
        }
}else{
        if($webuiEnabled == "Disable"){
                $webuiIsEnabled="false";
        }
}

?>
<?php
//----------Ported from includes/header.php for new login page
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">



<head>
	<!--CSS-->
	<link rel="stylesheet" type="text/css" media="screen" href="./cmn/css/common-min.css" />
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

	<script type="text/javascript" src="./cmn/js/utilityFunctions.js"></script>
	<script type="text/javascript" src="./cmn/js/comcast.js"></script>
	<script type="text/javascript" src="./cmn/js/lib/bootstrap.min.js"></script>
    <script type="text/javascript" src="./cmn/js/lib/bootstrap-waitingfor.js"></script>

</head>

<body>
	<!--Main Container - Centers Everything-->
	<div id="container">

		<!--Header-->
		<div id="header">
			<h2 id="logo"><img src="./cmn/img/logo_xfinity.png" alt="Company logo" title="Company logo" /></h2>
		</div> <!-- end #header -->

		<div id='div-skip-to' style="display: none;">
			<a id="skip-link" name="skip-link" href="#content">Skip to content</a>
		</div>

		<!--Main Content-->
		<div id="main-content">

<!-- $Id: at_a_glance.dory.php 2943 2009-08-25 20:58:43Z slemoine $ -->
<div id="sub-header">

<?php
//----------End port of userbar code for new index page
?>
</div><!-- end #sub-header -->

<?php
//Old Nav Bar. Put new login here.
//include('includes/nav.php');
//
if($webuiIsEnabled == "false"){
?>
<div  id="content">
        <div  id=forwarding-items>
                <div class="module data" style="width: 717px; margin: 89px">
                        <div id="content" style="text-align: center;">
                                <br>
                                <h3>Web UI is disabled for this gateway.<br></h3>
                                 <br>
                        </div>
                </div> <!-- end .module -->
        </div>
</div><!-- end #content -->
<?php
}else{
?>
<!--div id="nav"-->
<h1>Admin Tool Login</h1>
<div style="float: left; margin: 0 20px 20px 0; width: 60%; height:190px;background:white;">

	<form action="check.php" method="post" id="pageForm"  onsubmit="return f();">
	<div class="form-row">
		<p>Please login to manage your router.</p>
	</div>
	<div>
		<table style="background:white; text-align:center;">
			<tr>
				<td><label for="username"><b>Username:</b></label></td>
				<td><input type="text"     id="username" name="username" style="width: 250px;" class="text" autocomplete="off" /></td>
			</tr>
			<tr>
				<td><label for="password"><b>Password:</b></label></td>
				<td><input type="password" id="password" name="password" style="width: 250px;" class="text" autocomplete="off" /></td>
			</tr>
		</table>
	</div>
	<div class="form-btn" style="margin-top: 25px;text-align:center;">
		<input type="submit" class="btn" value="Login" />
	</div>
</form>
</div>
<?php
}
?>
<script type="text/javascript">
$(document).ready(function() {
	comcast.page.init("Login", "nav-login");

	$("#pageForm").validate({
		errorElement : "p"
		,errorContainer : "#error-msg-box"
		,invalidHandler: function(form, validator) {
			var errors = validator.numberOfInvalids();
			if (errors) {
				var message = errors == 1 ? 'You missed 1 field. It has been highlighted' : 'You missed ' + errors + ' fields. They have been highlighted';
				$("div.error").html(message);
				$("div.error").show();
			} else {
				$("div.error").hide();
			}
		}
		,rules : {
			username: {
				required: true
				,minlength: 3
			}
			,password: {
				required: true
				,minlength: 3
			}
		}
		,messages: {
			username: {
				required: "Username cannot be blank. Please enter a valid username."
			}
			,password: {
				required: "Password cannot be blank. Please enter a valid password."
				,minlength: "Password must be at least 3 characters."
			}
		}
	});

	$("#username").focus();
	$("#username").val("");
	$("#password").val("");
});

function f()
{
	var username;
	username = document.getElementById("username");
	username.value = (username.value.toLowerCase());
	//get the form id and submit it
	var form = document.getElementById("pageForm");
	form.submit();
	return true;
}
</script>

<?php include('includes/footer.php'); ?>
