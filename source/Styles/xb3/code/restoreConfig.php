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
	include_once __DIR__ .'/CSRF-Protector-PHP/libs/csrf/csrfprotector_rdkb.php';
	//Initialise CSRFGuard library
	csrfprotector_rdkb::init();
?>
<?php
	session_start();
	if (!isset($_SESSION["loginuser"])) {
		echo '<script type="text/javascript">alert("Please Login First!"); location.href="index.php";</script>';
		exit(0);
	}

	function randString(){
		$str = "";
		$char = array_merge(range('A','Z'), range('a','z'), range('0','9'));
		for ($i = 0; $i < 6; $i++) {
			$str .= $char[mt_rand(0, 61)];
		}
		return $str;
	}
	ini_set('upload_tmp_dir','/var/tmp/');
	$target = '/var/tmp/';
	$target = $target.randString().'.conf';


	if($_FILES['file']['error']>0){
		echo "Return Code: ".$_FILES["file"]["error"];
		exit;
	}
	if(preg_match('/^[a-zA-Z0-9?]+$/', $_REQUEST["VerifyPassword"])!=1) {
		echo("Error: password contains invalid characters");
		$restoreStatus = 3;
	} else {
		if(move_uploaded_file($_FILES['file']['tmp_name'], $target)){
			$fileExplode=explode(".",strrev($_FILES['file']['name']));
			$fileExtension=strrev($fileExplode[0]);
			$restoreStatus=-1;
			if (!strcasecmp($fileExtension,"cf2")) {
				exec('save_restore_config restore_CBR '.$target.' '.$_REQUEST["VerifyPassword"],$output,$restoreStatus);
			}
			else if (!strcasecmp($fileExtension,"cfg")) {
				exec('save_restore_config restore_BWG '.$target.' '.$_REQUEST["VerifyPassword"],$output,$restoreStatus);
			}
                        if($restoreStatus==0)
                        {
                            exec('/usr/bin/logger -t GUI -p local5.notice Configuration was successfully updated.');
                        }
			if (($restoreStatus==2) || ($restoreStatus==3) || ($restoreStatus==4)){
				echo "Password Check Failed";
				$passLockoutAttempt=3;
				$failedAttempts=getStr("Device.Users.User.1.NumOfRestoreFailedAttempt");
				$failedAttempts=$failedAttempts+1;
				setStr("Device.Users.User.1.NumOfRestoreFailedAttempt",$failedAttempts,true);
			}
			if ($restoreStatus!=0){
				echo "Error when trying to restore configuraion!";
			}
		}
		else { echo "Error when trying to restore configuraion!"; }
	}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<!-- $Id: header.php 3167 2010-03-03 18:11:27Z slemoine $ -->

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title>XFINITY</title>

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
		<script type="text/javascript" src="./cmn/js/lib/jquery.alerts.js"></script>
		<script type="text/javascript" src="./cmn/js/lib/bootstrap.min.js"></script>
   	 	<script type="text/javascript" src="./cmn/js/lib/bootstrap-waitingfor.js"></script>
   	 	<script type="text/javascript" src="./cmn/js/utilityFunctions.js"></script>
	</head>
	<body>
	<script type="text/javascript">
		$setTimeout(function() {
			if(0 == "<?php echo $restoreStatus; ?>"){	//Need Reboot to restore the saved configuration.
				var info = new Array("btn1", "Device");
				var jsonInfo = '["' + info[0] + '","' + info[1]+ '"]';
				$.ajax({
					type: "POST",
					url: "actionHandler/ajaxSet_Reset_Restore.php",
					data: { resetInfo: jsonInfo },
					success: function(results){
						if (results["reboot"]==true) {
							jProgress("Configuration was successfully updated from <?php  echo $_FILES["file"]['name'] ?>. Please wait for reboot", 999999,"Configuration Success");
							setTimeout(checkForRebooting, 4 * 60 * 1000);
						}
						else if (results["reboot"]!=true) {
							jAlert("Configuration updated. Failed to Reboot");
						}
					}
				});
			}
		}, 100);
		function checkForRebooting() {
			$.ajax({
				type: "GET",
				url: "index.php",
				timeout: 10000,
				success: function() {
					/* goto login page once the box reboots*/
					window.open ("index.php","_self");
					setTimeout(window.close(),1000);
				},
				error: function() {
					/* retry after 2 minutes */
					setTimeout(checkForRebooting, 30 * 1000);
				}
			});
		}
		function errorPrint(message) {
			jAlert(message,'That did not work',function(){ window.history.back(); });
		}
	</script>
		<div id="container">
			<?php
				switch ($restoreStatus) {
					case 2:
						echo "<script>errorPrint('Device responded but the format of ".$_FILES["file"]['name']." is not correct.Please use different configuration file. ".($passLockoutAttempt-$failedAttempts)." attempts left');</script>";
						break;
					case 3:
						echo "<script>errorPrint('Password Wrong. ".($passLockoutAttempt-$failedAttempts)." attempts left');</script>";
                                                break;
					case 4:                                 
						echo "<script>errorPrint('Device responded but the formatting of ".$_FILES["file"]['name']." is not correct. Restore Failed. File rejected. ".($passLockoutAttempt-$failedAttempts)." attempts left');</script>";
						break;
				}
			?>
		</div>
	</body>
</html>

