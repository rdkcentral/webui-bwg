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
if (!isset($_SESSION["loginuser"])) {
	echo '<script type="text/javascript">alert("Please Login First!"); location.href="index.php";</script>';
	exit(0);
}

/*
* set Router Name
*/
$ConfigInfo = $_REQUEST['configInfo'];
$ip_config = json_decode($ConfigInfo, true);

if (array_key_exists('routername', $ip_config)) {
	$post_routername = $ip_config['routername'];
	$routername = getStr("Device.DeviceInfo.RouterName");
	if ($post_routername != $routername) {
		setStr("Device.DeviceInfo.RouterName", $post_routername, false);
	}
}

/*
*  Enable or Disable Lan DHCP
*/
if (isset($_POST['enableDHCP'])) {
	$enableDHCP = $_POST['enableDHCP'];
	setStr("Device.DHCPv4.Server.Enable", $enableDHCP, true);
	setStr("Device.DHCPv6.Server.Enable", $enableDHCP, true);

	//reboot not required when enabling/disabling DHCP without changing the parameters of DHCP
	//setStr("Device.DeviceInfo.X_RDKCENTRAL-COM_UI_ACCESS","reboot_device",true);
	//setStr("Device.X_CISCO_COM_DeviceControl.RebootDevice", "Device",true);

	return; //stop processing
}

function isValidIP($ip, $ipRange){
	$longIP = ip2long($ip);
	if ($longIP == -1 || $longIP === FALSE) return false;
	else return ($longIP >= ip2long($ipRange[0]) && $longIP <= ip2long($ipRange[1]));
}
function ipRange($gwAddress, $subnetMask){
	$subnetMask_nr		= ip2long($subnetMask);
	$gateway_ip_start	= long2ip((ip2long($gwAddress) & $subnetMask_nr) + 1);
	$gateway_ip_end		= long2ip((ip2long($gwAddress) | ~$subnetMask_nr) - 1);
	return array($gateway_ip_start, $gateway_ip_end);
}
function valid_GW_IP($beginAddress, $endAddress, $gwAddress){
	$min	= ip2long($beginAddress);
	$max	= ip2long($endAddress);
	return ((ip2long($gwAddress) >= $min) && (ip2long($gwAddress) <= $max));
}
function isValidGW($gwAddress, $subnetMask, $beginAddress, $endAddress){
	//RFC1918 >> valid private IP range [10.0.0.1 ~ 10.255.255.253,\n172.16.0.1 ~ 172.31.255.253,\n192.168.0.1 ~ 192.168.255.253]
	//subnetMask
	$exp="/^(((255\.){3}(255|254|252|248|240|224|192|128|0+))|((255\.){2}(255|254|252|248|240|224|192|128|0+)\.0)|((255\.)(255|254|252|248|240|224|192|128|0+)(\.0+){2})|((255|254|252|248|240|224|192|128|0+)(\.0+){3}))$/";
	if (ip2long($gwAddress) == -1 || ip2long($gwAddress) === FALSE) return false;
	if (explode('.', $gwAddress)[0] == '10') {
                if(!preg_match($exp, $subnetMask)) return false;
		if(!valid_GW_IP('10.0.0.1', '10.255.255.253', $gwAddress)) return false;
		$ipRange = ipRange($gwAddress, $subnetMask);
	}
	else if (explode('.', $gwAddress)[0] == '172') {
                if(!preg_match($exp, $subnetMask))
                        return false;
		if(!valid_GW_IP('172.16.0.1', '172.31.255.253', $gwAddress)) return false;
		$ipRange = ipRange($gwAddress, $subnetMask);
	}
	else if (explode('.', $gwAddress)[0] == '192') {
                if(!preg_match($exp, $subnetMask))
                        return false;
		if(!(valid_GW_IP('192.168.0.1', '192.168.146.253', $gwAddress) || valid_GW_IP('192.168.148.1', '192.168.255.253', $gwAddress))) return false;
		$ipRange = ipRange($gwAddress, $subnetMask);
	}
	else return false;
	//beginAddress
	if(!isValidIP($beginAddress, $ipRange)) return false;
	//endAddress
	if(!isValidIP($endAddress, $ipRange)) return false;
	return true;
}

$validation = isValidGW($ip_config['Ipaddr'], $ip_config['Subnet_mask'], $ip_config['Dhcp_begin_addr'], $ip_config['Dhcp_end_addr']);

if($validation) {
	$post_gw_addr    = $ip_config['Ipaddr'];
	$post_netmask    = $ip_config['Subnet_mask'];
	$post_begin_addr = $ip_config['Dhcp_begin_addr'];
	$post_end_addr   = $ip_config['Dhcp_end_addr'];

	$gw_addr    = getStr("Device.X_CISCO_COM_DeviceControl.LanManagementEntry.1.LanIPAddress");
	$netmask    = getStr("Device.X_CISCO_COM_DeviceControl.LanManagementEntry.1.LanSubnetMask");
	$begin_addr = getStr("Device.DHCPv4.Server.Pool.1.MinAddress");
	$end_addr   = getStr("Device.DHCPv4.Server.Pool.1.MaxAddress");

	if (($gw_addr != $post_gw_addr) || ($netmask != $post_netmask) 
		|| ($begin_addr != $post_begin_addr) || ($end_addr != $post_end_addr)) {
		//set dhcpv4 part
		setStr("Device.X_CISCO_COM_DeviceControl.LanManagementEntry.1.LanIPAddress", $ip_config['Ipaddr'], false);
		setStr("Device.X_CISCO_COM_DeviceControl.LanManagementEntry.1.LanSubnetMask", $ip_config['Subnet_mask'], true);
		setStr("Device.DHCPv4.Server.Pool.1.MinAddress", $ip_config['Dhcp_begin_addr'], true);
		setStr("Device.DHCPv4.Server.Pool.1.MaxAddress", $ip_config['Dhcp_end_addr'], true);           
		//reboot the CBR for change of any DHCP parameters from UI
		setStr("Device.DeviceInfo.X_RDKCENTRAL-COM_UI_ACCESS","reboot_device",true);
		setStr("Device.X_CISCO_COM_DeviceControl.RebootDevice", "Device",true);          
	}
}

//set dms part 
if (array_key_exists('dmz_host', $ip_config)) {	
	setStr("Device.NAT.X_CISCO_COM_DMZ.Enable", $ip_config['dmz_enable'], false);
	setStr("Device.NAT.X_CISCO_COM_DMZ.InternalIP", $ip_config['dmz_host'], true);
}
else 
	setStr("Device.NAT.X_CISCO_COM_DMZ.Enable", "false", true);

/*
*  Enable or Disable DMZ
*/
/*if (isset($_POST['enableDMZ'])) {
	$enableDMZ = $_POST['enableDMZ'];
	setStr("Device.NAT.X_CISCO_COM_DMZ.Enable", $enableDMZ, true);
}*/


?>
