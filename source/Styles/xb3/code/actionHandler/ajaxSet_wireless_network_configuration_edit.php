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
$jsConfig = $_REQUEST['configInfo'];
$arConfig = json_decode($jsConfig, true);
//print_r($arConfig);

//Model for CBR is CGA4131COM
$model_name		= getStr("Device.DeviceInfo.ModelName");
$isDeviceCBR 	= (($model_name == "CGA4131COM") || ($model_name == "CGA4332COM"));
$isDeviceBWG    = ($model_name == "DPC3939B") || ($model_name == "DPC3941B");
$thisUser = $arConfig['thisUser'];
$Radio_1_Support_Modes = getStr("Device.WiFi.Radio.1.SupportedStandards");
$Radio_2_Support_Modes = getStr("Device.WiFi.Radio.2.SupportedStandards");

/*********************************************************************************************/
$i = $arConfig['ssid_number'];
$r = (2 - intval($i)%2);	//1,3,5,7 == 1(2.4G); 2,4,6,8 == 2(5G)
$frequency_band = getStr("Device.WiFi.Radio.$r.OperatingFrequencyBand");
$radio_band     = (strstr($frequency_band,"5G")) ? "5" : "2.4";

// this method for only restart a certain SSID
function MiniApplySSID($ssid) {
	$apply_id = (1 << intval($ssid)-1);
	$apply_rf = (2  - intval($ssid)%2);
	setStr("Device.WiFi.Radio.$apply_rf.X_CISCO_COM_ApplySettingSSID", $apply_id, false);
	setStr("Device.WiFi.Radio.$apply_rf.X_CISCO_COM_ApplySetting", "true", true);
}

$response_message = '';

//for password_update
$network_pass = getStr("Device.WiFi.AccessPoint.$i.Security.X_COMCAST-COM_KeyPassphrase");
if ("true" == getStr("Device.WiFi.Radio.$i.Enable")) {
	//change SSID status first, if disable, no need to configure following
	setStr("Device.WiFi.SSID.$i.Enable", $arConfig['radio_enable'], true);
	if ("true" == $arConfig['radio_enable']) 
	{
		$validation = true;
		if ($i == 1 || $i == 2) {
			if(($arConfig['password_update']=="false") && ("mso" == $thisUser)){
				$arConfig['network_password']=$network_pass;
			}
		}
	//Remove XHS and XfinityWiFi SSID information pages from MSO and CUSADMIN GUIs (Commercial products)
	else $validation = false;
	if ("mso" != $thisUser){
		if($validation) $validation = isValInArray($arConfig['channel_bandwidth'], array('20MHz', '40MHz', '80MHz', '160MHz'));
		if (strstr($Radio_1_Support_Modes, "ax") && strstr($Radio_2_Support_Modes, "ax"))
		{
			if($validation) $validation = (($radio_band=="2.4" && isValInArray($arConfig['wireless_mode'], array("g,n", "g,n,ax"))) || ($radio_band=="5" && isValInArray($arConfig['wireless_mode'], array("a,n,ac", "a,n,ac,ax"))));
		}
		else
		{
			if($validation) $validation = (($radio_band=="2.4" && isValInArray($arConfig['wireless_mode'], array("n", "g,n", "b,g,n"))) || ($radio_band=="5" && isValInArray($arConfig['wireless_mode'], array("n", "a,n", "ac", "n,ac", "a,n,ac"))));
		}
               if ("false"==$arConfig['channel_automatic']){
			$PossibleChannels = getStr("Device.WiFi.Radio.$r.PossibleChannels");
			if(strpos($PossibleChannels, '-') !== false) {//1-11
				$PossibleChannelsRange = explode('-', $PossibleChannels);
				$PossibleChannelsArr = range($PossibleChannelsRange[0],$PossibleChannelsRange[1]);
				foreach($PossibleChannelsArr as $key => $val) $PossibleChannelsArr[$key] = (string)$val;
			}
			else {//36,40,44,48,149,153,157,161,165 or 1,2,3,4,5,6,7,8,9,10,11
				$PossibleChannelsArr = explode(',', $PossibleChannels);
			}
			if ($validation && "false"==$arConfig['channel_automatic']) $validation = isValInArray($arConfig['channel_number'], $PossibleChannelsArr);
		}
		if($validation) $validation = (preg_match("/^[ -~]{8,63}$|^[a-fA-F0-9]{64}$/i", $arConfig['network_password'])==1);
		$DefaultKeyPassphrase = getStr("Device.WiFi.AccessPoint.$i.Security.X_COMCAST-COM_DefaultKeyPassphrase");
		if($validation && ($DefaultKeyPassphrase == $arConfig['network_password']) && ($arConfig['security'] != 'None' && $arConfig['security'] != 'WPA2_Enterprise' && $arConfig['security'] != 'WPA_WPA2_Enterprise')) {
			$validation = false;
			$response_message = 'Please change Network Password !';
		}
	}
	$DefaultKeyPassphrase = getStr("Device.WiFi.AccessPoint.$i.Security.X_COMCAST-COM_DefaultKeyPassphrase");
	if($validation && ($DefaultKeyPassphrase == $arConfig['network_password']) && ($arConfig['security'] != 'None' && $arConfig['security'] != 'WPA2_Enterprise' && $arConfig['security'] != 'WPA_WPA2_Enterprise')) {
			$validation = false;
			$response_message = 'Please change Network Password !';
	}
	if(($isDeviceCBR && ($arConfig['security'] == 'WPA_WPA2_Enterprise' ||  $arConfig['security'] == 'WPA2_Enterprise')) || ($isDeviceBWG && ($arConfig['security'] == 'WPA_WPA2_Enterprise' ||  $arConfig['security'] == 'WPA2_Enterprise'))){
		if($validation) $validation = validIPAddr($arConfig['RadiusServerIPAddr']);
		if($validation) $validation = validPort($arConfig['RadiusServerPort']);
		if($validation) $validation = is_allowed_string($arConfig['RadiusSecret']);
		if($validation) $validation = validIPAddr($arConfig['SecRadiusServerIPAddr']);
		if($validation) $validation = validPort($arConfig['SecRadiusServerPort']);
		if($validation) $validation = is_allowed_string($arConfig['SecRadiusSecret']);
	}
	if($validation && !valid_ssid_name($arConfig['network_name']))
	{
		$validation = false;
		$response_message = 'WiFi name is not valid. Please enter a new name !';
	}
	$DefaultSSID = getStr("Device.WiFi.SSID.$i.X_COMCAST-COM_DefaultSSID");
	if($validation && (strtolower($DefaultSSID) == strtolower($arConfig['network_name']))){
		$validation = false;
		$response_message = 'WiFi name is not valid. Please enter a new name !';
	}
	if($validation){
		// check if the LowerLayers radio is enabled
		if ("false" == getStr("Device.WiFi.Radio.$r.Enable")){
			setStr("Device.WiFi.Radio.$r.Enable", "true", true);
		}
		
		switch ($arConfig['security'])
		{
			case "WEP_64":
			  $encrypt_mode   = "WEP-64";
			  $encrypt_method = "None";
			  break;
			case "WEP_128":
			  $encrypt_mode   = "WEP-128";
			  $encrypt_method = "None";
			  break;
			case "WPA_PSK_TKIP":
			  $encrypt_mode   = "WPA-Personal";
			  $encrypt_method = "TKIP";
			  break;
			case "WPA_PSK_AES":
			  $encrypt_mode   = "WPA-Personal";
			  $encrypt_method = "AES";
			  break;
			case "WPA2_PSK_TKIP":
			  $encrypt_mode   = "WPA2-Personal";
			  $encrypt_method = "TKIP";
			  break;
			case "WPA2_PSK_AES":
			  $encrypt_mode   = "WPA2-Personal";
			  $encrypt_method = "AES";
			  break;
			case "WPA2_PSK_TKIPAES":
			  $encrypt_mode   = "WPA2-Personal";
			  $encrypt_method = "AES+TKIP";
			  break;
			case "WPAWPA2_PSK_TKIPAES":
			  $encrypt_mode   = "WPA-WPA2-Personal";
			  $encrypt_method = "AES+TKIP";
			  break;
			case "WPA2_Enterprise":
			  $encrypt_mode   = "WPA2-Enterprise";
			  $encrypt_method = "AES+TKIP";
			  break;
			case "WPA_WPA2_Enterprise":
			  $encrypt_mode   = "WPA-WPA2-Enterprise";
			  $encrypt_method = "AES+TKIP";
			  break;
			default:
			  $encrypt_mode   = "None";
			  $encrypt_method = "None";
		}

		// User "mso" have another page to configure this
		if ("mso" != $thisUser){
			setStr("Device.WiFi.Radio.$i.OperatingChannelBandwidth", $arConfig['channel_bandwidth'], false);
			setStr("Device.WiFi.Radio.$i.OperatingStandards", $arConfig['wireless_mode'], true);
			setStr("Device.WiFi.Radio.$i.AutoChannelEnable", $arConfig['channel_automatic'], true);
			if ("false"==$arConfig['channel_automatic']){
				setStr("Device.WiFi.Radio.$i.Channel", $arConfig['channel_number'], true);
			}
		}
		
		if ("None" == $arConfig['security']) {
			setStr("Device.WiFi.AccessPoint.$i.Security.ModeEnabled", $encrypt_mode, true);
		}
		else if ("WEP_64" == $arConfig['security']) {
			setStr("Device.WiFi.AccessPoint.$i.Security.X_CISCO_COM_WEPKey64Bit.1.WEPKey",  $arConfig['network_password'], false);
			setStr("Device.WiFi.AccessPoint.$i.Security.X_CISCO_COM_WEPKey64Bit.2.WEPKey",  $arConfig['network_password'], false);
			setStr("Device.WiFi.AccessPoint.$i.Security.X_CISCO_COM_WEPKey64Bit.3.WEPKey",  $arConfig['network_password'], false);
			setStr("Device.WiFi.AccessPoint.$i.Security.X_CISCO_COM_WEPKey64Bit.4.WEPKey",  $arConfig['network_password'], false);
			setStr("Device.WiFi.AccessPoint.$i.Security.ModeEnabled", $encrypt_mode, true);
		}
		else if("WEP_128" == $arConfig['security']) {
			setStr("Device.WiFi.AccessPoint.$i.Security.X_CISCO_COM_WEPKey128Bit.1.WEPKey", $arConfig['network_password'], false);
			setStr("Device.WiFi.AccessPoint.$i.Security.X_CISCO_COM_WEPKey128Bit.2.WEPKey", $arConfig['network_password'], false);
			setStr("Device.WiFi.AccessPoint.$i.Security.X_CISCO_COM_WEPKey128Bit.3.WEPKey", $arConfig['network_password'], false);
			setStr("Device.WiFi.AccessPoint.$i.Security.X_CISCO_COM_WEPKey128Bit.4.WEPKey", $arConfig['network_password'], false);
			setStr("Device.WiFi.AccessPoint.$i.Security.ModeEnabled", $encrypt_mode, true);
		}
		else {	//no open, no wep
			//bCommit false->true still do validation each, have to group set this...
			DmExtSetStrsWithRootObj("Device.WiFi.", true, array(
				array("Device.WiFi.AccessPoint.$i.Security.ModeEnabled", "string", $encrypt_mode), 
				array("Device.WiFi.AccessPoint.$i.Security.X_CISCO_COM_EncryptionMethod", "string", $encrypt_method)));
			if(($isDeviceCBR && ($arConfig['security'] == 'WPA_WPA2_Enterprise' ||  $arConfig['security'] == 'WPA2_Enterprise')) || ($isDeviceBWG && ($arConfig['security'] == 'WPA_WPA2_Enterprise' ||  $arConfig['security'] == 'WPA2_Enterprise'))){
				setStr("Device.WiFi.AccessPoint.$i.Security.RadiusServerIPAddr", $arConfig['RadiusServerIPAddr'], true);
				setStr("Device.WiFi.AccessPoint.$i.Security.RadiusServerPort", $arConfig['RadiusServerPort'], true);
				setStr("Device.WiFi.AccessPoint.$i.Security.RadiusSecret", $arConfig['RadiusSecret'], true);
				setStr("Device.WiFi.AccessPoint.$i.Security.SecondaryRadiusServerIPAddr",$arConfig['SecRadiusServerIPAddr'], true);
				setStr("Device.WiFi.AccessPoint.$i.Security.SecondaryRadiusServerPort",$arConfig['SecRadiusServerPort'], true);
				setStr("Device.WiFi.AccessPoint.$i.Security.SecondaryRadiusSecret", $arConfig['SecRadiusSecret'], true);
			}
			else setStr("Device.WiFi.AccessPoint.$i.Security.X_COMCAST-COM_KeyPassphrase", $arConfig['network_password'], true);
		}

		setStr("Device.WiFi.SSID.$i.SSID", $arConfig['network_name'], true);
		setStr("Device.WiFi.AccessPoint.$i.SSIDAdvertisementEnabled", $arConfig['broadcastSSID'], true);

		if ("mso" == $thisUser){
			// if ("false" == $arConfig['enableWMM']){
				// setStr("Device.WiFi.AccessPoint.$i.UAPSDEnable", "false", true);
			// }
			// setStr("Device.WiFi.AccessPoint.$i.WMMEnable", $arConfig['enableWMM'], true);

			//when disable WMM, make sure UAPSD is disabled as well, have to use group set		
			if (getStr("Device.WiFi.AccessPoint.$i.WMMEnable") != $arConfig['enableWMM']) {
				DmExtSetStrsWithRootObj("Device.WiFi.", true, array(
					array("Device.WiFi.AccessPoint.$i.UAPSDEnable", "bool", "false"),
					array("Device.WiFi.AccessPoint.$i.WMMEnable",   "bool", $arConfig['enableWMM'])));			
			}
		}
	} 
}
	// setStr("Device.WiFi.Radio.$r.X_CISCO_COM_ApplySetting", "true", true);
	MiniApplySSID($i);
}
if($response_message!='') {
	$response->error_message = $response_message;
	echo htmlspecialchars(json_encode($response), ENT_NOQUOTES, 'UTF-8');
}
else echo htmlspecialchars($jsConfig, ENT_NOQUOTES, 'UTF-8');
?>
