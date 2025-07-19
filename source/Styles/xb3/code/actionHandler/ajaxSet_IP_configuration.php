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
	echo '<script type="text/javascript">alert("Please Login First!"); location.href="../index.php";</script>';
	exit(0);
}
// DNS servers
// dmcli eRT setv Device.DHCPv4.Server.Pool.1.DNSServers string 172.168.10.11,10.1.14.1
// dmcli eRT setv Device.DHCPv6.Server.Pool.1.X_RDKCENTRAL-COM_DNSServers string "2001:558:feed::1 2001:558:feed::2"
if (isset($_POST['enableDHCP'])) {
	$enableDHCP = $_POST['enableDHCP'];
	setStr("Device.DHCPv4.Server.Enable", $enableDHCP, true);
	return; //stop processing
}
$ip_config = json_decode($_REQUEST['configInfo'], true);
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
if(!array_key_exists('IPv6', $ip_config)){
	$validation = true;
	if($validation) $validation = isValidGW($ip_config['Ipaddr'], $ip_config['Subnet_mask'], $ip_config['Dhcp_begin_addr'], $ip_config['Dhcp_end_addr']);
	if($validation) $validation = (isValInRange($ip_config['Dhcp_lease_time'], 120, 604195200) || $ip_config['Dhcp_lease_time'] == '-1');
	if($validation) $validation = isValInArray($ip_config['dns_manually'], array('true', 'false'));
	if($ip_config['dns_manually'] == 'true') {
		if($validation) $validation = validIPAddr($ip_config['primary_dns']);
		if($validation) $validation = validIPAddr($ip_config['secondary_dns']);
		$DHCPv4_DNSServers = $ip_config['primary_dns'].','.$ip_config['secondary_dns'];
	}
	else {
		$DHCPv4_DNSServers = '';
	}
	if($ip_config['Dhcp_begin_addr'] == $ip_config['Dhcp_end_addr']){
		$validation = false;
	}
	if($validation){
	    //set ipv4 part
		setStr("Device.X_CISCO_COM_DeviceControl.LanManagementEntry.1.LanIPAddress", $ip_config['Ipaddr'], true);
		setStr("Device.X_CISCO_COM_DeviceControl.LanManagementEntry.1.LanSubnetMask", $ip_config['Subnet_mask'], true);

		//20140523
		//set LanManagementEntry_ApplySettings after change LanManagementEntry table
		setStr("Device.X_CISCO_COM_DeviceControl.LanManagementEntry_ApplySettings", "true", true);

		setStr("Device.DHCPv4.Server.Pool.1.MinAddress", $ip_config['Dhcp_begin_addr'], true);
		setStr("Device.DHCPv4.Server.Pool.1.MaxAddress", $ip_config['Dhcp_end_addr'], true);
		setStr("Device.DHCPv4.Server.Pool.1.LeaseTime" , $ip_config['Dhcp_lease_time'], true);

		setStr("Device.DHCPv4.Server.Pool.1.DNSServers", $DHCPv4_DNSServers, true);
		setStr("Device.DHCPv4.Server.Pool.1.DNSServersEnabled", $ip_config['dns_manually'], true);

		//reboot the CBR for change of any DHCP parameters from UI
		setStr("Device.DeviceInfo.X_RDKCENTRAL-COM_UI_ACCESS","reboot_device",true);
		setStr("Device.X_CISCO_COM_DeviceControl.RebootDevice", "Device",true);
	}
}
else {
	//set ipv6 part
	$state = $ip_config['Stateful'];
	$restore = $ip_config['restore'];

	if ($state == 'true') {//stateful	
		$validation = true;
		if($validation) $validation = (preg_match("/^([0-9a-f]{1,4}:){3}[0-9a-f]{1,4}$/i", $ip_config['dhcpv6_begin_addr'])==1);
		if($validation) $validation = (preg_match("/^([0-9a-f]{1,4}:){3}[0-9a-f]{1,4}$/i", $ip_config['dhcpv6_end_addr'])==1);
		if($validation){
			getStr("Device.IP.Interface.1.IPv6Prefix.1."); //this line if a trick fix for Yan's framework bug, may delete in future 
			setStr("Device.RouterAdvertisement.InterfaceSetting.1.AdvManagedFlag", "true", true);
			setStr("Device.DHCPv6.Server.X_CISCO_COM_Type", "Stateful", true);
			setStr("Device.DHCPv6.Server.Pool.1.PrefixRangeEnd", $ip_config['dhcpv6_end_addr'], false);
			setStr("Device.DHCPv6.Server.Pool.1.PrefixRangeBegin", $ip_config['dhcpv6_begin_addr'], false);
			setStr("Device.DHCPv6.Server.Pool.1.LeaseTime", $ip_config['dhcpv6_lease_time'], true);
		}
	}
	else {//stateless
		setStr("Device.RouterAdvertisement.InterfaceSetting.1.AdvManagedFlag", "false", true);
		setStr("Device.DHCPv6.Server.X_CISCO_COM_Type", "Stateless", true);
	}
	if(isset($ip_config['dns_manually_ipv6'])) {
		$validation = true;
		if($validation) $validation = isValInArray($ip_config['dns_manually_ipv6'], array('true', 'false'));
		if($ip_config['dns_manually_ipv6'] == 'true'){
			if($validation) $validation = validIPAddr($ip_config['primary_dns']);
			if($validation) $validation = validIPAddr($ip_config['secondary_dns']);
			$DHCPv6_DNSServers = $ip_config['primary_dns'].' '.$ip_config['secondary_dns'];
		}
		else
			$DHCPv6_DNSServers = '';
		if($validation){
			setStr("Device.DHCPv6.Server.Pool.1.X_RDKCENTRAL-COM_DNSServers", $DHCPv6_DNSServers, true);
			setStr("Device.DHCPv6.Server.Pool.1.X_RDKCENTRAL-COM_DNSServersEnabled", $ip_config['dns_manually_ipv6'], true);
		}
	}
	if ($restore == 'true'){
		$validation = true;
		if($validation) $validation = (preg_match("/^([0-9a-f]{1,4}:){3}[0-9a-f]{1,4}$/i", $ip_config['dhcpv6_begin_addr'])==1);
		if($validation) $validation = (preg_match("/^([0-9a-f]{1,4}:){3}[0-9a-f]{1,4}$/i", $ip_config['dhcpv6_end_addr'])==1);
		if($validation){
			setStr("Device.RouterAdvertisement.InterfaceSetting.1.AdvManagedFlag", "true", true);
			setStr("Device.DHCPv6.Server.X_CISCO_COM_Type", "Stateful", true);
			setStr("Device.DHCPv6.Server.Pool.1.PrefixRangeBegin", $ip_config['dhcpv6_begin_addr'], false);
			setStr("Device.DHCPv6.Server.Pool.1.PrefixRangeEnd", $ip_config['dhcpv6_end_addr'], false);
			setStr("Device.DHCPv6.Server.Pool.1.LeaseTime", $ip_config['dhcpv6_lease_time'], true);
		}
		else {//stateless
			setStr("Device.RouterAdvertisement.InterfaceSetting.1.AdvManagedFlag", "false", true);
			setStr("Device.DHCPv6.Server.X_CISCO_COM_Type", "Stateless", true);
		}
	}
}

?>
