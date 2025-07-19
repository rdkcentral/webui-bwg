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
<?php include('includes/header.php'); ?>

<!-- $Id: lan.php 3158 2010-01-08 23:32:05Z slemoine $ -->

<div id="sub-header">
	<?php include('includes/userbar.php'); ?>
</div><!-- end #sub-header -->

<?php include('includes/nav.php'); ?>
<?php include('includes/utility.php'); ?>
<?php
function getPort4XHSEnabled() {
	$rootObjName = "Device.X_CISCO_COM_MultiLAN.";
	$paramNameArray = array("Device.X_CISCO_COM_MultiLAN.");
	$mapping_array  = array("PrimaryLANBridge", "PrimaryLANBridgeHSPorts", "HomeSecurityBridge", "HomeSecurityBridgePorts");

	$multiLan = getParaValues($rootObjName, $paramNameArray, $mapping_array);
	if (!empty($multiLan)) {
		$pLanBridgeHSPortEnable = getStr($multiLan[0]["PrimaryLANBridge"].".Port.".$multiLan[0]["PrimaryLANBridgeHSPorts"].".Enable");
		$HSBridgePortEnable = getStr($multiLan[0]["HomeSecurityBridge"].".Port.".$multiLan[0]["HomeSecurityBridgePorts"].".Enable");
		return ($pLanBridgeHSPortEnable === 'false' && $HSBridgePortEnable === 'true');
	}

	return false;
}

$isPort4XHSEnabled = getPort4XHSEnabled();

?>

<script type="text/javascript">
var o_isPort4XHSEnabled = <?php echo $isPort4XHSEnabled ? 'true' : 'false'; ?>;

function onsavePort4() {
	var postData = {};

	postData.op = "savePort4XHS";
	postData.enable = $("#port4").prop("checked");

	jProgress('This may take several seconds', 60);
	$.ajax({
		type: 'POST',
		url: 'actionHandler/ajaxSet_hardware_lan.php',
		dataType: 'json',
		data: postData,
		success: function(data) {
			jHide();
			if (data.status != 'success') {
				var str = "Failed, please try again later.";
				if (data.msg) {
					str += '\nMessage: ' + data.msg;
				}

				jAlert(str);
				return;
			}
			else {
				window.location.reload(true);
			}
		},
		error: function() {
			/* restore the previous state */
			jHide();
			jAlert("Failure, please try again.");
		}
	});
}

function initEvents() {
	$("#saveXHSBtn").off("click").click(onsavePort4);
}

$(document).ready(function() {
    comcast.page.init("Gateway > Hardware > LAN Ethernet", "nav-lan");

	$("#port4").prop("checked", o_isPort4XHSEnabled);

	initEvents();
});
</script>

<div id="content">
	<h1>Gateway > Hardware > LAN Ethernet</h1>
	<div id="educational-tip">
		<p class="tip">View information about the Gateway's Ethernet Ports. </p>
		<p class="hidden">The Gateway has 4 Gigabit (GbE) Ethernet Ports. When a device is connected to the Gateway with an Ethernet cable, you'll see an <i>Active</i> status for that port.</p>
	</div>

	<?php

	function NameMap($str,$i)
	{
                $wan_enable = getStr("Device.Ethernet.X_RDKCENTRAL-COM_WAN.Enabled");
                $selectedOperationalMode = getStr("Device.X_RDKCENTRAL-COM_EthernetWAN.SelectedOperationalMode");
                $autoWanEnable= getStr("Device.DeviceInfo.X_RDKCENTRAL-COM_AutowanFeatureSupport");
                $wanPort= getStr("Device.Ethernet.X_RDKCENTRAL-COM_WAN.Port");
          	$currentOpMode = getStr("Device.X_RDKCENTRAL-COM_EthernetWAN.CurrentOperationalMode");
		switch ($str)
		{
			case "Up":
				if(($autoWanEnable=="true") && ($i==($wanPort+1))){
					if(strtolower($selectedOperationalMode)=="ethernet"){
						return "Active Ethernet WAN";
					}else if(strtolower($selectedOperationalMode)=="docsis"){
						return "Active";
					}else{
						if(strtolower($currentOpMode)=="ethernet"){
                                                        return  "Active Auto WAN";
                                                }else{
                                                        return "Active";
                                                }
					}
				} else {
					return "Active";
				}
				break;
			case "Down":
				return "Inactive";
				break;
			default:
				return $str;
		}
	}
	
	
	/*if ($_DEBUG) {
		$ids = array("1", "2", "3", "4");
	}*/
	$InterfaceNumber = getStr("Device.Ethernet.InterfaceNumberOfEntries");
	for ( $j=1; $j<= ($InterfaceNumber); $j++) 
	{
		$ethernetUpstream = getStr("Device.Ethernet.Interface.$j.Upstream");
		$ethernetStatus = getStr("Device.Ethernet.Interface.$j.Status");
		$ethernetMACAddress = getStr("Device.Ethernet.Interface.$j.MACAddress");
		$ethernetBitRate = getStr("Device.Ethernet.Interface.$j.CurrentBitRate");
		if ("true" == $ethernetUpstream){
			continue;		
		}
		echo '<div class="module forms block">';
		echo '<h2>LAN Ethernet Port '.$j.'</h2>';

		$dm = array(
			array("LAN Ethernet link status:", null, $ethernetStatus),
			array("MAC Address:", null, $ethernetMACAddress)
		);

		/* link speed */
		$lspeed = $ethernetBitRate;
		$lunit = " Mbps";
		if (empty($lspeed)) {
			$lspeed = "Not Applicable";
			$lunit = "";
		}
		else if ((int)$lspeed < 0) {
			$lspeed = "Disconnected";
			$lunit = "";
		}
		/* zqiu
		else if ((int)$lspeed >= 1000) {
			$lspeed = floor((int)$lspeed / 1000);
			$lunit = " Gbps";
		} 
		*/
		array_push($dm, array("Connection Speed:", $lspeed.$lunit));

		for ($m=0, $i=0; $i<count($dm); $i++)
		{
			echo '<div class="form-row '.(($m++ % 2)?'odd':'').'" >';
			echo '<span class="readonlyLabel">'.$dm[$i][0].'</span>';
			echo '<span class="value">'.($dm[$i][1] === null ? NameMap($dm[$i][2],$j) : $dm[$i][1]).'</span>';
			
			echo '</div>';
		}

		echo '</div>';
	}
	?>
	
</div><!-- end #content -->

<?php include('includes/footer.php'); ?>

