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
/**********************************************************************
   Copyright [2014] [Cisco Systems, Inc.]
 
   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at
 
       http://www.apache.org/licenses/LICENSE-2.0
 
   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
**********************************************************************/
?>
<?php
/**
 * ajaxSet_WAN_additional_subnet.php
 *
 * Action handler via AJAX for configuration of WAN additional subnet.
 * This includes adding, editing, deleting and enabling/disabling subnet entry.
 *
 * Author:	Nobel Huang
 * Date:	Sep 12, 2013
 */

session_start();
if (!isset($_SESSION["loginuser"])  || $_SESSION['loginuser'] != 'mso') {
	echo '<script type="text/javascript">alert("Please Login First!"); location.href="../index.php";</script>';
	exit(0);
}

$opType = $_POST['op'];
$id = $_POST['id'];
$publicIp = $_POST['ip'];
$subnetMask = $_POST['mask'];
$enable = $_POST['enable'];

try {
	if (!in_array($opType, array('add', 'edit', 'del', 'enable', 'disable'), true)
		|| (($opType === 'add' || $opType === 'edit') && (empty($publicIp) || empty($subnetMask) || !isset($enable)))
		|| ($opType === 'edit' && !isset($id))
		|| ($opType === 'del' && !isset($id))
		|| (($opType === 'enable' || $opType === 'disable') && !isset($id))) {
		throw new Exception('Parameters are incompleted');
	}
        $exp = "/^(((128|192|224|240|248|252|254)\.0\.0\.0)|(255\.(0|128|192|224|240|248|252|254)\.0\.0)|(255\.255\.(0|128|192|224|240|248|252|254)\.0)|(255\.255\.255\.(0|128|192|224|240|248|252|254)))$/";
	if ($opType === 'edit') {
		/* editing a subnet */
                if(isPrivate_Addr($publicIp)) {
			throw new Exception('The IP address is within the private IP range.');
		}
		if(!preg_match($exp, $subnetMask)) {
			throw new Exception('subnetmask is not valid');
		}
		if (!setStr("Device.X_CISCO_COM_TrueStaticIP.AdditionalSubnet.$id.IPAddress", $publicIp, false)
			|| !setStr("Device.X_CISCO_COM_TrueStaticIP.AdditionalSubnet.$id.SubnetMask", $subnetMask, false)
			|| !setStr("Device.X_CISCO_COM_TrueStaticIP.AdditionalSubnet.$id.Enable", $enable, true)) {
			throw new Exception('Failed to set data to backend data model');
		}
	}
	else if ($opType === 'add'){
		/* try to add an entry */
                if(isPrivate_Addr($publicIp)) {
			throw new Exception('The IP address is within the private IP range.');
                }
		if(!preg_match($exp, $subnetMask)) {
                        throw new Exception('subnetmask is not valid');
                }
		$addId = addTblObj("Device.X_CISCO_COM_TrueStaticIP.AdditionalSubnet.");
		//$addId = 3;
		if ($addId == 0) {
			throw new Exception("Failed to add subnet entry");
		}
		$idArr = DmExtGetInstanceIds("Device.X_CISCO_COM_TrueStaticIP.AdditionalSubnet.");
		//$idArr = array(0,"1","2","3");
		if ($idArr[0] !== 0 || count($idArr) <= 1) {
			throw new Exception("Failed to add subnet entry");
		}
		array_shift($idArr);
		if (!in_array($addId, $idArr)) {
			throw new Exception("Failed to add subnet entry");
		}
		if (!setStr("Device.X_CISCO_COM_TrueStaticIP.AdditionalSubnet.$addId.IPAddress", $publicIp, false)
			|| !setStr("Device.X_CISCO_COM_TrueStaticIP.AdditionalSubnet.$addId.SubnetMask", $subnetMask, false)
			|| !setStr("Device.X_CISCO_COM_TrueStaticIP.AdditionalSubnet.$addId.Enable", $enable, true)) {
			throw new Exception('Failed to set data to backend data model');
		}
	}
	else if ($opType === 'del') {
		/* delete a subnet */
		$ret = delTblObj("Device.X_CISCO_COM_TrueStaticIP.AdditionalSubnet.$id.");
		if ($ret !== 0) {
			throw new Exception("Failed to delete subnet entry. ErrCode $ret");
		}
	}
	else if ($opType === 'enable') {
		$ret = setStr("Device.X_CISCO_COM_TrueStaticIP.AdditionalSubnet.$id.Enable", 'true', true);
		if ($ret !== true) {
			throw new Exception('Failed to enable this additional subnet.');
		}
	}
	else if ($opType === 'disable') {
		$ret = setStr("Device.X_CISCO_COM_TrueStaticIP.AdditionalSubnet.$id.Enable", 'false', true);
		if ($ret !== true) {
			throw new Exception('Failed to disable this additional subnet.');
		}
	}

	$response = array("status" => "success");
	header("Content-Type: application/json");
	echo json_encode($response);
}
catch (Exception $e) {
	$response = array("status" => "Failed", "msg" => $e->getMessage());
	header("Content-Type: application/json");
	echo json_encode($response);
}

?>
