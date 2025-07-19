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
<?php include('../includes/actionHandlerUtility.php') ?>
<?php
session_start();
if (!(isset($_SESSION["loginuser"]) || isset($_SESSION["password_change"]))) {
        echo '<script type="text/javascript">alert("Please Login First!"); location.href="../index.php";</script>';
        exit(0);
}
if (($_POST['FileName'] != "") && ($_POST['UserInputPassword'] != "")) {
	$Filename = $_POST['FileName'];
	$Password = $_POST['UserInputPassword'];
	$return_save = 1; //Fail by default
	$validation = true;
        if($validation) $validation = ((strlen($Password)>=8) && (strlen($Password)<= 20));
        if($validation) $validation = !noSpace($Password);
	if(preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/', $Password)==1 && preg_match('/^[a-zA-Z\d.]+$/', $Filename)==1 && $validation) {
		$command = "save_restore_config save"." ".escapeshellarg($Filename)." ".escapeshellarg($Password);
		exec($command,$output,$return_save);
	}
	if ($return_save == 0)
		$result = "Success!";
	else
		$result = "Failure!";
}
echo htmlspecialchars($result, ENT_NOQUOTES, 'UTF-8');
?>
