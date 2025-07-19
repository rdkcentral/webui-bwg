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

	<script type="text/javascript" src="./cmn/js/lib/jquery-3.7.1.js"></script>
	<script type="text/javascript" src="./cmn/js/lib/jquery-migrate-1.2.1.js"></script>
	<script type="text/javascript" src="./cmn/js/lib/jquery.validate.js"></script>
	<script type="text/javascript" src="./cmn/js/lib/jquery.alerts.js"></script>
	<script type="text/javascript" src="./cmn/js/lib/jquery.alerts.progress.js"></script>
	<script type="text/javascript" src="./cmn/js/lib/jquery.ciscoExt.js"></script>
	<script type="text/javascript" src="./cmn/js/lib/jquery.highContrastDetect.js"></script>
	<script type="text/javascript" src="./cmn/js/lib/jquery.radioswitch.js"></script>
	<script type="text/javascript" src="./cmn/js/lib/jquery.virtualDialog.js"></script>

	<script type="text/javascript" src="./cmn/js/utilityFunctions.js"></script>
	<script type="text/javascript" src="./cmn/js/comcast.js"></script>
	<script type="text/javascript" src="./cmn/js/lib/bootstrap.min.js"></script>
    <script type="text/javascript" src="./cmn/js/lib/bootstrap-waitingfor.js"></script>

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
		#header{
			height: 500px;
		}
	</style>
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
		<div id="main-content">

<?php
require('includes/jwt.php');

$flag=0;
$flag_mso=0;
$flag_cusadmin=0;
$https_enable= getStr("Device.UserInterface.X_CISCO_COM_RemoteAccess.HttpsEnable");
$passLockEnable = getStr("Device.UserInterface.PasswordLockoutEnable");
$failedAttempt=getStr("Device.Users.User.3.NumOfFailedAttempts");
$failedAttempt_mso=getStr("Device.Users.User.1.NumOfFailedAttempts");
$failedAttempt_cusadmin=getStr("Device.Users.User.2.NumOfFailedAttempts");
$passLockoutAttempt=getStr("Device.UserInterface.PasswordLockoutAttempts");
$passLockoutTime=getStr("Device.UserInterface.PasswordLockoutTime");
$cusAdminLoginCount=getStr("Device.Users.User.2.X_RDKCENTRAL-COM_LoginCounts");
$cusAdminLockoutTime=getStr("Device.Users.User.2.X_RDKCENTRAL-COM_LockOutRemainingTime");
$cusAdminRemainingAttempts=getStr("Device.Users.User.2.X_RDKCENTRAL-COM_RemainingAttempts");
$currentOpMode = getStr("Device.X_RDKCENTRAL-COM_EthernetWAN.CurrentOperationalMode");
$cusAdminRemainingAttempts=$cusAdminRemainingAttempts-1;
$passLockoutTimeMins=$cusAdminLockoutTime/(60);
$passLockoutTimeMins=round($passLockoutTimeMins,2);
if($passLockoutTimeMins==0)
	$passLockoutTimeMins=5;
$client_ip		= $_SERVER["REMOTE_ADDR"];			// $client_ip="::ffff:10.0.0.101";
$server_ip		= $_SERVER["SERVER_ADDR"];
$redirect_page = "https://{$_SERVER["SERVER_NAME"]}" . $_SERVER["PHP_SELF"];
header('X-robots-tag: noindex,nofollow');
$modelName= getStr("Device.DeviceInfo.ModelName");

function create_session(){
	        $modelName= getStr("Device.DeviceInfo.ModelName");
		session_start();
                $curr_sessID = session_id();
                $curr_IP = $_SERVER['REMOTE_ADDR'];
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
		else
                {
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
		//echo("You are logging...");
		$timeout_val 		= intval(getStr("Device.X_CISCO_COM_DeviceControl.WebUITimeout"));
		("" == $timeout_val) && ($timeout_val = 900);
		$_SESSION["timeout"]	= $timeout_val - 60;	//dmcli param is returning 900, GUI expects 840 - then GUI adds 60
		$_SESSION["sid"]	= session_id();
                if (isset($_POST["username"]))
                {
                    $_SESSION["loginuser"]	= $_POST["username"];
                }
	}
    if (isset($_POST["username"]))
	{
		/*=============================================*/
		// $dev_mode = true;
		/*if (file_exists("/var/ui_dev_mode")) {
			create_session();
			$_SESSION["timeout"] = 100000; 
			if ($_POST["password"] == "dev") {
				if ($_POST["username"] == "mso") {
					header("location:at_a_glance.php");
				}
				elseif ($_POST["username"] == "cusadmin") {
					header("location:at_a_glance.php");
				}	
				elseif ($_POST["username"] == "admin") {
					header("location:at_a_glance.php");
				}			
				return; 
			} 
		}*/
		/*===============================================*/

        if ($_POST["username"] == "mso")
	{
            $authmode=getStr( "Device.DeviceInfo.X_RDKCENTRAL-COM_RFC.Feature.OAUTH.AuthMode" );
            if( $authmode != "sso" )
            {
                if( $authmode == "potd" || $_POST["password"] != "" )
                {
                    //triggering password validation in back end if "potd" or "mixed" and a password is supplied
	                $return_status = setStr("Device.Users.User.1.X_CISCO_COM_Password",$_POST["password"],true);
	                sleep(1);
	                $curPwd1 = getStr("Device.Users.User.1.X_CISCO_COM_Password");
                }
                else
                {
                    $curPwd1 = "Invalid_PWD";    // trigger SSO login attempt
                }
            }
            else
            {
                $curPwd1 = "Invalid_PWD";    // trigger SSO login attempt
            }
	   // Allowing mso access only through CM IP , in case of CFG3 device CM IP is erouter0 IP.
	    if ( ( (if_type($server_ip)=="rg_ip") && (strtolower($currentOpMode) !="ethernet") ) || (if_type($server_ip) =="lan_ip") || (($modelName == "CGA4131COM" || $modelName == "CGA4332COM")  && (if_type($server_ip)=="remote_ip"))) 
            {
            	if($passLockEnable == "true"){
					
					if($failedAttempt_mso<$passLockoutAttempt){
						$failedAttempt_mso=$failedAttempt_mso+1;
						setStr("Device.Users.User.1.NumOfFailedAttempts",$failedAttempt_mso,true);
					}
					
					if($failedAttempt_mso==$passLockoutAttempt){
						$flag_mso=1;
						echo '<script type="text/javascript"> alert("You have '.$passLockoutAttempt.' failed login attempts and your account will be locked for '.$passLockoutTimeMins.' minutes");history.back();</script>';
								
					}
				}
				if($flag_mso==0){
            		session_destroy();
                	echo '<script type="text/javascript"> alert("Access denied!"); history.back(); </script>';
                }
            }
            elseif ($curPwd1 == "Good_PWD" && $return_status)
            {
            	if(($passLockEnable == "true") && ($failedAttempt_mso==$passLockoutAttempt)){
						$flag_mso=1;
						echo '<script type="text/javascript"> alert("You have '.$passLockoutAttempt.' failed login attempts and your account will be locked for '.$passLockoutTimeMins.' minutes");history.back();</script>';
								
				}else{
					create_session();
					$failedAttempt_mso=0;	
					setStr("Device.Users.User.1.NumOfFailedAttempts",$failedAttempt_mso,true);
            		exec("/usr/bin/logger -t GUI -p local5.notice 'User:mso login'");
                	header("location:at_a_glance.php");
                }		

            }
            elseif ("" == $curPwd1)
            {
				session_destroy();
				echo '<script type="text/javascript"> alert("Can not get password for mso from backend!"); history.back(); </script>';
            }
            else
          	{
                $authendpoint=getStr( "Device.DeviceInfo.X_RDKCENTRAL-COM_RFC.Feature.OAUTH.ServerUrl" );
                if( $authmode != "potd" && $authendpoint )
                {
                    if (!isset($_GET['code']))
                    {
                        $auth_url = getAuthenticationUrl( $authendpoint, $redirect_page );
                        echo "<script type='text/javascript'>document.location.href='{$auth_url}';</script>";
                        die('Please wait ...');
                    }
                }
                else
                {
				    if($passLockEnable == "true"){
					
					    if($failedAttempt_mso<$passLockoutAttempt){
						    $failedAttempt_mso=$failedAttempt_mso+1;
						    setStr("Device.Users.User.1.NumOfFailedAttempts",$failedAttempt_mso,true);
					}
					
					if($failedAttempt_mso==$passLockoutAttempt){
						$flag_mso=1;
						echo '<script type="text/javascript"> alert("You have '.$passLockoutAttempt.' failed login attempts and your account will be locked for '.$passLockoutTimeMins.' minutes");history.back();</script>';
								
					}
				}
	
				if($flag_mso==0){
				 	session_destroy();
					echo '<script type="text/javascript"> alert("Incorrect password for mso!"); history.back(); </script>';
				}
            }
        }
        }
        elseif ($_POST["username"] == "cusadmin")
		{
			$return_status = setStr("Device.Users.User.2.X_RDKCENTRAL-COM_ComparePassword",$_POST["password"],true);
			sleep(1);
			$passVal= getStr("Device.Users.User.2.X_RDKCENTRAL-COM_ComparePassword");		
			//$curPwd2 = getStr("Device.Users.User.2.X_CISCO_COM_Password");
			if ( !innerIP($client_ip) && (if_type($server_ip)!="rg_ip") && (checkCusadminAccess($server_ip)!="rm_ip") )
			{
				if($passLockEnable == "true"){
					
					if($failedAttempt_cusadmin<$passLockoutAttempt){
						$failedAttempt_cusadmin=$failedAttempt_cusadmin+1;
						setStr("Device.Users.User.2.NumOfFailedAttempts",$failedAttempt_cusadmin,true);
					}

					if($failedAttempt_cusadmin==$passLockoutAttempt){
						$flag_cusadmin=1;
						echo '<script type="text/javascript"> alert("You have '.$passLockoutAttempt.' failed login attempts and your account will be locked for '.$passLockoutTimeMins.' minutes");history.back();</script>';
								
					}
				}
				if($flag_cusadmin==0){
            		session_destroy();
			echo '<script type="text/javascript"> alert("Access denied! You have '.$cusAdminRemainingAttempts.' remaining login attempts; after '.$cusAdminRemainingAttempts.' attempt your account will be locked for 5 mins."); history.back(); </script>';
                }
			}
			
			else if($passVal=="Invalid_PWD"  || !($return_status)){

				if($passLockEnable == "true"){
					
					if($failedAttempt_cusadmin<$passLockoutAttempt){
						$failedAttempt_cusadmin=$failedAttempt_cusadmin+1;
						setStr("Device.Users.User.2.NumOfFailedAttempts",$failedAttempt_cusadmin,true);
					}

					if($failedAttempt_cusadmin==$passLockoutAttempt){
						$flag_cusadmin=1;
						echo '<script type="text/javascript"> alert("You have '.$passLockoutAttempt.' failed login attempts and your account will be locked for '.$passLockoutTimeMins.' minutes");history.back();</script>';
								
					}
				}
	
				if($flag_cusadmin==0){
				 	session_destroy();
					echo '<script type="text/javascript"> alert("Incorrect password for cusadmin! You have '.$cusAdminRemainingAttempts.' remaining login attempts; after '.$cusAdminRemainingAttempts.' attempt your account will be locked for 5 mins."); history.back(); </script>';
				}
			}else{
				if(($passLockEnable == "true") && ($failedAttempt_cusadmin==$passLockoutAttempt)){
						$flag_cusadmin=1;
						echo '<script type="text/javascript"> alert("You have '.$passLockoutAttempt.' failed login attempts and your account will be locked for '.$passLockoutTimeMins.' minutes");history.back();</script>';
				}else{
					$failedAttempt_cusadmin=0;
					setStr("Device.Users.User.2.NumOfFailedAttempts",$failedAttempt_cusadmin,true);
					exec("/usr/bin/logger -t GUI -p local5.notice 'User:cusadmin login'");
					if($passVal=="Default_PWD"){
							$newLoginAttempt=$cusAdminLoginCount+1;
							if($newLoginAttempt>10)
								$newLoginAttempt=10;
							$remainingCount= 10-$cusAdminLoginCount;
							setStr("Device.Users.User.2.X_RDKCENTRAL-COM_LoginCounts",$newLoginAttempt,true);
							echo '<script type="text/javascript"> ';
							if($cusAdminLoginCount<10 && $https_enable=="false"){
								echo '$.alerts.cancelButton="Remind Me Later";';
								echo 'jConfirm("There are '.$remainingCount.' remaining login attempts for remind me later option; after '.$remainingCount.' attempts, you will be required to change your password.",
									"Are You Sure?", function(ret) {
											if(ret) {';
												create_session();	
												$_SESSION["password_change"] = "default_pwd";
												echo 'location.href = "cusadmin_password_change.php";
											}
											else {';
												create_session();
												echo 'location.href = "at_a_glance.php";
											}
							});';
							}else{
								create_session();
								$_SESSION["password_change"] = "default_pwd";
								if($https_enable=="true"){
									$alertMsg="Default cusadmin password with Remote Management configuration enabled will expose your Gateway GUI to internet,cusadmin password change required";
								}else{
									$alertMsg="There are no remaining login attempts for remind me later option. Please change the password.";
								}
								echo 'jAlert(" '.$alertMsg. '",
								"Password Change Required",function(ret){
                                    if(ret){
                                            location.href = "cusadmin_password_change.php";
                                    }
                                    });';
							}
				echo '</script>';
						}else{
							create_session();
							header("location:at_a_glance.php");
						}
				}
			}
        }
        else
	    {
		    setStr("Device.DeviceInfo.X_RDKCENTRAL-COM_UI_ACCESS","ui_failed",true);
            if( session_status() == PHP_SESSION_ACTIVE )
            {
		        session_destroy();
            }
		    echo '<script type="text/javascript"> alert("Incorrect user name!"); history.back(); </script>';
        }
    }
    else
    {
        if (isset($_GET['token']))
        {
            $ADToken = $_GET['token'];
            $tokenvalid = VerifyToken( $ADToken );
            if( $tokenvalid == true )
            {
                $failedAttempt_mso=0;
                setStr("Device.Users.User.1.NumOfFailedAttempts",$failedAttempt_mso,true);
                exec("/usr/bin/logger -t GUI -p local5.notice 'User:mso login'");
                header("location:at_a_glance.php");
                create_session();
                // since $_POST["username"] is empty when we get a token, we'll set it in the $_SESSION["loginuser"]
                // to mso because that's the only user that can get the JWT. Later Web GUI processing requires it to b
                $_SESSION["loginuser"] = "mso";
                $_SESSION['JWT_VALID'] = true;
            }
            else
            {
                setStr("Device.DeviceInfo.X_RDKCENTRAL-COM_UI_ACCESS","token_failed",true);
                if( session_status() == PHP_SESSION_ACTIVE )
                {
                    session_destroy();
                }
                echo '<script type="text/javascript"> alert("'._("Access Denied, level is none!").'"); history.back(); </script>';
            }
	}
        else
        {
            setStr("Device.DeviceInfo.X_RDKCENTRAL-COM_UI_ACCESS","token_fetch",true);
            if( session_status() == PHP_SESSION_ACTIVE )
            {
                session_destroy();
            }
            echo '<script type="text/javascript"> alert("'._("Access Denied,  unknown error").'"); history.back(); </script>';
        }
    }

	function innerIP($client_ip){		//for compatibility, $client_ip is not used
		$out		= array();
		$tmp		= array();
		$lan_ip		= array();
		$server_ip	= $_SERVER["SERVER_ADDR"];
		
		if (strpos($server_ip, ".")){		//ipv4, something like "::ffff:10.1.10.1"
			$tmp		= explode(":", $server_ip);
			$server_ip	= array_pop($tmp);
		}
		
		exec("ifconfig brlan0", $out);

		foreach ($out as $v){
			if (strpos($v, 'inet addr')){
				$tmp = explode('Bcast', $v);
				$tmp = explode('addr:', $tmp[0]);
				array_push($lan_ip, trim($tmp[1]));
			}
			else if (strpos($v, 'inet6 addr')){
				$tmp = explode('Scope', $v);
				$tmp = explode('addr:', $tmp[0]);
				$tmp = explode('/', $tmp[1]);
				array_push($lan_ip, trim($tmp[0]));
			}
		}
		
		return in_array($server_ip, $lan_ip);
	}

	function get_ips($if_name){
		$out = array();
		$tmp = array();
		$ret = array();
	
		exec("ip addr show ".$if_name, $out);
		
		foreach ($out as $v){
			if (strstr($v, 'inet')){
				$tmp = explode('/', $v);
				$tmp = explode(' ', $tmp[0]);
				array_push($ret, trim(array_pop($tmp)));
			}
		}
		return $ret;
	}
	function checkCusadminAccess($ip_addr){
                $remote_ip      = get_ips("erouter0");
                $server_port = $_SERVER["SERVER_PORT"];
                if (in_array($ip_addr, $remote_ip)){
                        if(strtolower($currentOpMode) =="ethernet"){
                                $remoteAcess= getStr("Device.UserInterface.X_CISCO_COM_RemoteAccess.Enable");
                                $httpsRemoteEnable=getStr("Device.UserInterface.X_CISCO_COM_RemoteAccess.HttpsEnable");
                                $httpsRemotePort= getStr("Device.UserInterface.X_CISCO_COM_RemoteAccess.HttpsPort");
                                if($remoteAcess==true  && $httpsRemoteEnable==true && $server_port==$httpsRemotePort){
                                                        return "rm_ip";
                                }
                        }
                                return "eth_ip";
                }
        }
	function if_type($ip_addr){
		$tmp	= array();
		$lan_ip	= get_ips("brlan0");
		$is_dummy_wan0 = getenv("WAN0_IS_DUMMY");
		if ($is_dummy_wan0 == "true")
                {   
		    $cm_ip  = get_ips("privbr");
	        }
	        else
	        {
		    $cm_ip  = get_ips("wan0");
                }	
		if (strstr($ip_addr, ".")){		//ipv4, something like "::ffff:10.1.10.1"
			$tmp	 = explode(":", $ip_addr);
			$ip_addr = array_pop($tmp);
		}
		
		if (in_array($ip_addr, $lan_ip)){
			return "lan_ip";
		}
		else if (in_array($ip_addr, $cm_ip)){
			return "cm_ip";
		}
		else{
			return "rg_ip";
		}
		
		// print_r($lan_ip);
		// print_r($cm_ip);
	}

function getAuthenticationUrl( $auth_endpoint, $redirect_uri )
{
    $parameters = array(
        'ip_uri'  => $redirect_uri
    );
    return $auth_endpoint . '?' . http_build_query($parameters, null, '&');
}

/*	
	function innerIP($client_ip)
	{
		if (strstr($client_ip, "192.168.100.") && ("bridge-static"==getStr("Device.X_CISCO_COM_DeviceControl.LanManagementEntry.1.LanMode")))
		{
			return true;
		}

		if (strpos($client_ip, "."))		//IPv4
		{
			$tmp0	= explode(":", $client_ip);
			$tmp1	= array_pop($tmp0);
			$client	= explode(".", $tmp1);
			$lanip4	= explode(".", getStr("Device.X_CISCO_COM_DeviceControl.LanManagementEntry.1.LanIPAddress"));
			$lanmsk	= explode(".", getStr("Device.X_CISCO_COM_DeviceControl.LanManagementEntry.1.LanSubnetMask"));
					
			for ($i=0; $i<4; $i++)
			{
				if (((int)$lanmsk[$i]&(int)$client[$i]) != ((int)$lanmsk[$i]&(int)$lanip4[$i]))
				{
					return false;
				}
			}	
		}
		else
		{
			$prefix_dm	= getStr("Device.RouterAdvertisement.InterfaceSetting.1.Prefixes");
			$client		= explode(":", $client_ip);		
			$prefix		= explode(":", $prefix_dm);
			$prelen		= explode("/", $prefix_dm);
			$intlen		= intval(array_pop($prelen))/16;
			
			($intlen < 1) && ($intlen = 1);

			if (strtolower($client[0]) != "fe80")
			{
				for ($i=0; $i<$intlen; $i++)
				{
					if ($client[$i]!=$prefix[$i])
					{
						return false;
					}
				}
			}
		}
		
		return true;
	}
*/
?>
