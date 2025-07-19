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
session_start();
if (!isset($_SESSION["loginuser"])) {
	echo '<script type="text/javascript">alert("Please Login First!"); location.href="index.php";</script>';
	exit(0);
}
// require "../includes/auth.php";
/**
 * ajax_port_management.php
 *
 * Action handler via AJAX for configuration of Port Management.
 * This includes adding, editing, deleting, enabling/disabling rule entry and
 * change the rule type.
 *
 * Author:	Nobel Huang
 * Date:	Oct 9, 2013
 */

require_once("../includes/utility.php");

// FIXME: session check!!!

$opType = $_POST['op'];
$r_id = $_POST['id'];
$r_enable = $_POST['enable'];
$r_appName = $_POST['appName'];
$r_protocol = $_POST['protocol'];
$r_ipStart = $_POST['ipStart'];
$r_ipEnd = $_POST['ipEnd'];
$r_portStart = $_POST['portStart'];
$r_portEnd = $_POST['portEnd'];
$r_ruleType = $_POST['type'];
$r_isDisabling = $_POST['isDisabling'];

try {
	if (!in_array($opType, array('add', 'edit', 'del', 'enable', 'disable', 'disableAllPm', 'changeType', 'refreshConnDev'), true)
		|| (($opType === 'add' || $opType === 'edit') && (!isset($r_enable) || empty($r_appName) || empty($r_protocol) || empty($r_ipStart) || empty($r_ipEnd) || empty($r_portStart) || empty($r_portEnd)))
		|| ($opType === 'edit' && !isset($r_id))
		|| ($opType === 'del' && !isset($r_id))
		|| (($opType === 'enable' || $opType === 'disable') && !isset($r_id))
		|| ($opType === 'disableAllPm' && !isset($r_isDisabling))
		|| ($opType === 'changeType' && !isset($r_ruleType))) {
		throw new Exception('Parameters are incompleted');
	}

	$baseObjName = "Device.X_CISCO_COM_TrueStaticIP.PortManagement.";
	$rootObjName = "Device.X_CISCO_COM_TrueStaticIP.PortManagement.Rule.";

	$response = array();

	/*
	if ($_SESSION['_DEBUG']) {
		header("Content-Type: application/json");
		echo json_encode(array('status'=>'success'));
		exit(0);
	}
	 */

	if ($opType === 'edit') {
		/* editing a rule */
                if(!($r_portStart >= 1 &&  $r_portStart <= 65535 && $r_portEnd >= 1 && $r_portEnd <=65535)){
                       throw new Exception("Port Number is not in the valid range");
                }
                if( $r_portEnd < $r_portStart ){
                       throw new Exception("Please enter a value greater than or equal to Start port");
                }
                $ip_s = explode(".", "$r_ipStart") ;
                if(!(($ip_s[0] >=1 && $ip_s[0] <=255) && ($ip_s[1] >=1 && $ip_s[1] <=255) && ($ip_s[2] >=1 && $ip_s[2] <=255) && ($ip_s[3] >=1 && $ip_s[3] <=255))) {
                       throw new Exception("ip_start is not valid");
                }
                $ip_e = explode(".", "$r_ipEnd") ;
                if(!(($ip_e[0] >=1 && $ip_e[0] <=255) && ($ip_e[1] >=1 && $ip_e[1] <=255) && ($ip_e[2] >=1 && $ip_e[2] <=255) && ($ip_e[3] >=1 && $ip_e[3] <=255))) {
                       throw new Exception("ip_end is not valid");
                }
		$paramArray = 
			array (
				array($rootObjName.$r_id.".Name", "string", $r_appName),
				array($rootObjName.$r_id.".Protocol", "string", $r_protocol),
				array($rootObjName.$r_id.".IPRangeMin", "string", $r_ipStart),
				array($rootObjName.$r_id.".IPRangeMax", "string", $r_ipEnd),
				array($rootObjName.$r_id.".PortRangeMin", "uint", $r_portStart),
				array($rootObjName.$r_id.".PortRangeMax", "uint", $r_portEnd),
				array($rootObjName.$r_id.".Enable", "bool", $r_enable)
			);
		$retStatus = DmExtSetStrsWithRootObj($rootObjName, TRUE, $paramArray);
		if ($retStatus !== 0) {
			throw new Exception("Failed to set data to backend data model. ErrCode $retStatus");
		}
	}
	else if ($opType === 'add'){
		/* try to add an entry */
		$addId = addTblObj($rootObjName);
		//$addId = 3;
		if ($addId == 0) {
			throw new Exception("Failed to add port management rule entry");
		}
		$idArr = DmExtGetInstanceIds($rootObjName);
		//$idArr = array(0,"1","2","3");
		if ($idArr[0] !== 0 || count($idArr) <= 1) {
			throw new Exception("Failed to add port management rule entry");
		}
		array_shift($idArr);
		if (!in_array($addId, $idArr)) {
			throw new Exception("Failed to add port management rule entry");
		}
                if(!($r_portStart >= 1 &&  $r_portStart <= 65535 && $r_portEnd >= 1 && $r_portEnd <=65535)){
                       throw new Exception("Port Number is not in the valid range");
                }
                if( $r_portEnd < $r_portStart ){
                       throw new Exception("Please enter a value greater than or equal to Start port");
                }
                $ip_s = explode(".", "$r_ipStart") ;
                if(!(($ip_s[0] >=1 && $ip_s[0] <=255) && ($ip_s[1] >=1 && $ip_s[1] <=255) && ($ip_s[2] >=1 && $ip_s[2] <=255) && ($ip_s[3] >=1 && $ip_s[3] <=255))) {
                       throw new Exception("ip_start is not valid");
                }
                $ip_e = explode(".", "$r_ipEnd") ;
                if(!(($ip_e[0] >=1 && $ip_e[0] <=255) && ($ip_e[1] >=1 && $ip_e[1] <=255) && ($ip_e[2] >=1 && $ip_e[2] <=255) && ($ip_e[3] >=1 && $ip_e[3] <=255))) {
                       throw new Exception("ip_end is not valid");
                }
		$paramArray = 
			array (
				array($rootObjName.$addId.".Name", "string", $r_appName),
				array($rootObjName.$addId.".Protocol", "string", $r_protocol),
				array($rootObjName.$addId.".IPRangeMin", "string", $r_ipStart),
				array($rootObjName.$addId.".IPRangeMax", "string", $r_ipEnd),
				array($rootObjName.$addId.".PortRangeMin", "uint", $r_portStart),
				array($rootObjName.$addId.".PortRangeMax", "uint", $r_portEnd),
				array($rootObjName.$addId.".Enable", "bool", $r_enable)
			);
		$retStatus = DmExtSetStrsWithRootObj($rootObjName, TRUE, $paramArray);
		if ($retStatus !== 0) {
			throw new Exception("Failed to set data to backend data model. ErrCode $retStatus");
		}
	}
	else if ($opType === 'del') {
		/* delete a rule */
		$ret = delTblObj($rootObjName.$r_id.".");
		if ($ret !== 0) {
			throw new Exception("Failed to delete port management rule entry. ErrCode $ret");
		}
	}
	else if ($opType === 'enable') {
		$ret = setStr($rootObjName.$r_id.".Enable", 'true', true);
		if ($ret !== true) {
			throw new Exception('Failed to enable this port management rule.');
		}
	}
	else if ($opType === 'disable') {
		$ret = setStr($rootObjName.$r_id.".Enable", 'false', true);
		if ($ret !== true) {
			throw new Exception('Failed to disable this port management rule.');
		}
	}
	else if ($opType === 'disableAllPm') {
		$ret = setStr($baseObjName."Enable", ($r_isDisabling === 'true' ? 'false' : 'true'), true);
		if ($ret !== true) {
			throw new Exception('Failed to '.($r_isDisabling === 'true' ? 'disable' : 'enable').' port management.');
		}
	}
	else if ($opType === 'changeType') {
		$ret = setStr($baseObjName."RuleType", $r_ruleType, true);
		if ($ret !== true) {
			throw new Exception('Failed to change port management rule type.');
		}
	}
	else if ($opType === 'refreshConnDev') {
		/* prepare connected devices */
		$rootObjName    = "Device.Hosts.Host.";
		$paramNameArray = array("Device.Hosts.Host.");
		$mapping_array  = array("HostName", "IPAddress", "IPv6Addr", "PhysAddress", "Layer1Interface", "Layer3Interface", "Active");
		$connDevArray = getParaValues($rootObjName, $paramNameArray, $mapping_array);
		/*if ($_SESSION['_DEBUG']) {
			$connDevArray = array(
				array('HostName'=>'Computer1', 'IPAddress'=>'10.1.10.67', 'IPv6Addr'=>'::', 'X_CISCO_COM_TrueStaticIPClient'=>'false',
					  'PhysAddress'=>'11:11:11:11:11:11', 'Layer1Interface'=>'Ethernet', 'Layer3Interface'=>'brlan0', 'Active'=>'true'),
				array('HostName'=>'Computer2', 'IPAddress'=>'10.1.10.68', 'IPv6Addr'=>'::', 'X_CISCO_COM_TrueStaticIPClient'=>'false',
					  'PhysAddress'=>'22:22:22:22:22:22', 'Layer1Interface'=>'Ethernet', 'Layer3Interface'=>'brlan0', 'Active'=>'true'),
				array('HostName'=>'Computer3', 'IPAddress'=>'6.6.6.10', 'IPv6Addr'=>'::', 'X_CISCO_COM_TrueStaticIPClient'=>'true',
					  'PhysAddress'=>'33:33:33:33:33:33', 'Layer1Interface'=>'Ethernet', 'Layer3Interface'=>'brlan0', 'Active'=>'true'),
				array('HostName'=>'Computer4', 'IPAddress'=>'6.6.6.15', 'IPv6Addr'=>'::', 'X_CISCO_COM_TrueStaticIPClient'=>'true',
					  'PhysAddress'=>'44:44:44:44:44:44', 'Layer1Interface'=>'Device.WiFi.SSID.1.', 'Layer3Interface'=>'ath0', 'Active'=>'false'),
				array('HostName'=>'Computer5', 'IPAddress'=>'6.6.6.16', 'IPv6Addr'=>'::', 'X_CISCO_COM_TrueStaticIPClient'=>'true',
					  'PhysAddress'=>'55:55:55:55:55:55', 'Layer1Interface'=>'Device.WiFi.SSID.2.', 'Layer3Interface'=>'ath0', 'Active'=>'true')
			);
		}*/
		/* prune those hosts not belonging to TSI, and convert values */
		for ($i = count($connDevArray) - 1; $i >= 0; --$i) {
			if ($connDevArray[$i]['Active'] !== 'true') {
				array_splice($connDevArray, $i, 1);
			}
			else {
				$tempType = ProcessLay1Interface($connDevArray[$i]["Layer1Interface"]);
				$connDevArray[$i]["Layer1Interface"] = $tempType["connectionType"];
			}
		}
		$response["connDevArray"] = $connDevArray;
	}

        $response = array("status" => "success", "ipstart" => "$r_ipStart", "ipend"  => "$r_ipEnd");
	header("Content-Type: application/json");
	echo json_encode($response);
}
catch (Exception $e) {
	$response = array("status" => "Failed", "msg" => $e->getMessage());
	header("Content-Type: application/json");
	echo json_encode($response);
}

?>
