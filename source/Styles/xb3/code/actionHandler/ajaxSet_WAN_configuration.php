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

session_start();
if (!isset($_SESSION["loginuser"])  || $_SESSION['loginuser'] != 'mso') {
	echo '<script type="text/javascript">alert("Please Login First!"); location.href="../index.php";</script>';
	exit(0);
}

function getInfo($index, $info) {
    $ret = empty($info[$index]) ? NULL : $info[$index];
    return $ret;
}

function handleConfSuccess($result = NULL) {
	if (!isset($result)) {
		$result = array();
	}
	$result['status'] = 'success';
	header("Content-Type: application/json");
	echo json_encode($result);
}

function handleConfError($result = NULL) {
	if (!isset($result)) {
		$result = array();
	}
	$result['status'] = 'failed';
	if (!isset($result['msg'])) {
		$result['msg'] = 'Unknown error';
	}
	header("Content-Type: application/json");
	echo json_encode($result);
}

/*
function dumpToFile($content) {
	$outputStr = strftime('%Y/%m/%d %H:%M:%S') . ': ' . $content . "\n";
	file_put_contents('/var/log/phpdebug.log', $outputStr, FILE_APPEND | LOCK_EX);
}
 */

$result = array();

$wanInfo = json_decode($_REQUEST['wanInfo'], true);
//dumpToFile(var_export($wanInfo, true));
if ($wanInfo === NULL) {
	$result['msg'] = 'Bad format of configuration!';
	handleConfError($result);
	exit(0);
}

/* handle DHCP release */
$releaseWan = getInfo('release', $wanInfo);
if($releaseWan != NULL) {
    setStr("Device.X_CISCO_COM_DeviceControl.ReleaseWan", $releaseWan, true);
	handleConfSuccess();
	sleep(10);
	exit(0);
}
/* handle DHCP renew */
$renewWan = getInfo('renew', $wanInfo);
if($renewWan != NULL) {
    setStr("Device.X_CISCO_COM_DeviceControl.RenewWan", $renewWan, true);
	sleep(5);
	/* Try to fetch current NAT IP. As this can only be triggered when in DHCP
	 * mode, so we just fetch DHCP address without concerning True Static IP
	 * options. */
	$wanDhcpIpAddr = getStr("Device.IP.Interface.1.IPv4Address.1.IPAddress");
	if (empty($wanDhcpIpAddr)) $wanDhcpIpAddr = '0.0.0.0';
	$result['curNatIp'] = $wanDhcpIpAddr;
	handleConfSuccess($result);
	exit(0);
}

/* handle wan info saving */
$wanMode = $wanInfo['wanMode'];
$staticDnsEnabled = $wanInfo['staticDnsEnabled'];
$nameServer1 = $wanInfo['nameServer1'];
$nameServer2 = $wanInfo['nameServer2'];
if(empty($nameServer2)) $nameServer2 = "0.0.0.0";

$wanStaticIP = getInfo('wanStaticIP', $wanInfo);
$wanSubnetMask = getInfo('wanSubnetMask', $wanInfo);
$natDisable = getInfo('natDisable', $wanInfo);
$natDhcp = getInfo('natDhcp', $wanInfo);

if (strcasecmp("TrueStatic", $wanMode) == 0) {
	/* True Static IP mode */
        if(isPrivate_Addr($wanStaticIP)) {
            $result="Invalid Ip";
            handleConfError($result);
        }else{
	setStr("Device.X_CISCO_COM_TrueStaticIP.Enable", "true", false);
    setStr("Device.X_CISCO_COM_TrueStaticIP.IPAddress", $wanStaticIP, false);
    setStr("Device.X_CISCO_COM_TrueStaticIP.SubnetMask", $wanSubnetMask, true);
    //setStr("Device.X_CISCO_COM_TrueStaticIP.GatewayIPAddress", $wanGatewayIP, true);
	setStr('Device.X_CISCO_COM_DeviceControl.LanManagementEntry.1.LanNaptEnable',
		$natDisable === 'false' ? 'true' : 'false', false);
	setStr('Device.X_CISCO_COM_DeviceControl.LanManagementEntry.1.LanNaptOnDhcp',
		$natDhcp === 'true' ? 'true' : 'false', true);
        }
}
else {
	/* DHCP mode */
	setStr("Device.X_CISCO_COM_DeviceControl.WanAddressMode", 'DHCP', true);
	setStr("Device.X_CISCO_COM_TrueStaticIP.Enable", "false", false);
	/* restore NAT settings for DHCP mode */
	setStr('Device.X_CISCO_COM_DeviceControl.LanManagementEntry.1.LanNaptEnable', 'true', false);
	setStr('Device.X_CISCO_COM_DeviceControl.LanManagementEntry.1.LanNaptOnDhcp', 'true', true);
}

if (!isset($staticDnsEnabled)) {
	/* nothing to do with this case as frontend suppresses this value from being saved */
}
else if(strcasecmp("true", $staticDnsEnabled) == 0) {
    setStr("Device.X_CISCO_COM_DeviceControl.EnableStaticNameServer", $staticDnsEnabled, false);
    setStr("Device.X_CISCO_COM_DeviceControl.NameServer1", $nameServer1, false);
    setStr("Device.X_CISCO_COM_DeviceControl.NameServer2", $nameServer2, true);
}
else {
    setStr("Device.X_CISCO_COM_DeviceControl.EnableStaticNameServer", $staticDnsEnabled, true);
}

handleConfSuccess($result);
?>
