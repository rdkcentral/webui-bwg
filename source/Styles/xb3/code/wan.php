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

<!-- $Id: port_forwarding_add.php 3158 2010-01-08 23:32:05Z slemoine $ -->

<div id="sub-header">
	<?php include('includes/userbar.php'); ?>
</div><!-- end #sub-header -->

<?php include('includes/nav.php'); ?>
<?php include('includes/utility.php'); ?>

<?php

/* To prepare the data used for view */

/* WAN IP configuration */
$wanCurNatIP = null;				// to hold the current ip for nat
$wanMode = getStr("Device.X_CISCO_COM_DeviceControl.WanAddressMode");
$wanDHCPGwIp = getStr("Device.DHCPv4.Client.1.IPRouters");
$wanTrueStaticIpEnabled = !strcasecmp('true', getStr('Device.X_CISCO_COM_TrueStaticIP.Enable'));
/*if ($_DEBUG) {
	$wanMode = 'DHCP';
	$wanDHCPGwIp = '172.24.15.1';
	$wanTrueStaticIpEnabled = false;
}*/
$wanDhcpIpAddr = getStr("Device.IP.Interface.1.IPv4Address.1.IPAddress");
$wanDhcpSubnetMask = getStr("Device.IP.Interface.1.IPv4Address.1.SubnetMask");
/*if ($_DEBUG) {
	$wanDhcpIpAddr = '172.24.15.5';
	$wanDhcpSubnetMask = '255.255.255.0';
}*/
if ($wanTrueStaticIpEnabled) {
	$wanTrueStaticIpAddr = getStr('Device.X_CISCO_COM_TrueStaticIP.IPAddress');
	$wanTrueStaticIpMask = getStr('Device.X_CISCO_COM_TrueStaticIP.SubnetMask');
	//$wanTrueStaticIpGWIp = getStr('Device.X_CISCO_COM_TrueStaticIP.GatewayIPAddress');
	$natDisable = getStr('Device.X_CISCO_COM_DeviceControl.LanManagementEntry.1.LanNaptEnable') !== 'true';
	$natDhcp = getStr('Device.X_CISCO_COM_DeviceControl.LanManagementEntry.1.LanNaptOnDhcp') === 'true';
	/*if ($_DEBUG) {
		$wanTrueStaticIpAddr = '1.1.1.6';
		$wanTrueStaticIpMask = '255.255.255.248';
		$natDisable = false;
		$natDhcp = true;
	}*/
	$wanMode = 'TrueStatic';			// change wan mode to static once TrueStaticIp no matter what set before
	$wanCurNatIP = (!$natDisable && $natDhcp) ? $wanDhcpIpAddr : $wanTrueStaticIpAddr;
}
else {
	$wanCurNatIP = $wanDhcpIpAddr;
}

$staticDnsEnabled = !strcasecmp('true', getStr("Device.X_CISCO_COM_DeviceControl.EnableStaticNameServer"));
$nameServer1 = getStr("Device.X_CISCO_COM_DeviceControl.NameServer1");
if(strcmp("0.0.0.0", $nameServer1 ) == 0) $nameServer1 = "";
$nameServer2 = getStr("Device.X_CISCO_COM_DeviceControl.NameServer2");
if(strcmp("0.0.0.0", $nameServer2 ) == 0) $nameServer2 = "";

/* WAN additional subnet */
$addiSubnetTable = fetchAdditionalSubnetTable();
?>

<script type="text/javascript">
var o_wanCurNatIp = "<?php echo $wanCurNatIP; ?>";
var o_wanDhcpIp = "<?php echo $wanDhcpIpAddr; ?>";
var o_wanDhcpMask = "<?php echo $wanDhcpSubnetMask; ?>";
var o_wanMode = "<?php echo $wanMode; ?>";
var o_wanTrueStaticIpEnable = <?php echo $wanTrueStaticIpEnabled ? 'true' : 'false'; ?>;
var o_trueStaticIpInfo = {
	ip: "<?php echo isset($wanTrueStaticIpAddr) ? $wanTrueStaticIpAddr : ""; ?>",
	mask: "<?php echo isset($wanTrueStaticIpMask) ? $wanTrueStaticIpMask : ""; ?>",
	gw: "<?php echo isset($wanDHCPGwIp) ? $wanDHCPGwIp : ""; ?>", // use DHCP GW as reference
	natDisable: <?php echo (isset($natDisable) && $natDisable) ? 'true' : 'false'; ?>,
	natDhcp: <?php echo (isset($natDhcp) && $natDhcp) ? 'true' : 'false'; ?>
};
var o_staticDnsEnabled = <?php echo $staticDnsEnabled ? 'true' : 'false'; ?>;
var o_nameServer = [<?php echo "\"$nameServer1\""; ?>, <?php echo "\"$nameServer2\""; ?>];

function onchangeWanMode(mode, preFill) {
	if (mode === undefined) {
		mode = $('#wan_ip_method').comboVal();
	}
	else {
		$('#wan_ip_method').comboVal(mode);
	}
	if (mode == 'DHCP') {
		$('#row_ip, #row_mask, #row_gw, #row_natdisable, #row_natdhcp').hide();
		$('#row_dhcpctrl > span').show();
		if (preFill) {
			/* dhcp controls */
			$('#release_wan').css({cursor: 'pointer'}).click(function(){
				setWanDHCP("release");
			}).children('img').attr('src', 'cmn/img/release_ip.gif');
			$('#renew_wan').css({cursor: 'pointer'}).click(function(){
				setWanDHCP("renew");
			}).children('img').attr('src', 'cmn/img/renew_ip.gif');
		}
	}
	else if (mode == 'TrueStatic') {
		$('#row_ip, #row_mask, #row_gw, #row_natdisable, #row_natdhcp').show();
		$('#row_dhcpctrl > span').hide();
		$('#curNatIpInfo1').show();
		$('#curNatIpInfo2, #curNatIpInfo3').hide();
		if (preFill) {
			$('#wan_ip').val(o_trueStaticIpInfo.ip);
			$('#subnet_mask').val(o_trueStaticIpInfo.mask);
			$('#gateway_ip').val(o_trueStaticIpInfo.gw);
			$('#nat_disable').prop("checked", o_trueStaticIpInfo.natDisable);
			$('#nat_dhcp').prop("checked", o_trueStaticIpInfo.natDhcp);
			onclickNatDisable();
		}
	}
	else {
		jAlert("Invalid WAN IP Method: "+mode);
		return;
	}
}

function onchangeManualDns(enabled, preFill) {
	if (enabled === undefined) {
		enabled = $('#manual_dns_enable').prop("checked");
	}
	else {
		$('#manual_dns_enable').prop("checked", enabled);
	}
	$('#dns_primary, #dns_secondary').prop("disabled", !enabled);
	if (preFill) {
		$('#dns_primary').val(o_nameServer[0]);
		$('#dns_secondary').val(o_nameServer[1]);
	}
}

function onenableSubnet(isEnabling) {
	var $this = $(this);
	var id = $this.closest('tr').attr('eid');
	var postData = {};

	postData.op = isEnabling ? 'enable' : 'disable';
	postData.id = id;

	jProgress('This may take several seconds', 60);
	$.ajax({
		type: 'POST',
		url: 'actionHandler/ajaxSet_WAN_additional_subnet.php',
		dataType: 'json',
		data: postData,
		success: function(data) {
			jHide();
			if (data.status != 'success') {
				var str = "Failed, please try again later.";
				if (data.msg) {
					str += '\nMessage: ' + data.msg;
				}
				/* restore the previous state */
				$this.prop("checked", !isEnabling);

				jAlert(str);
				return;
			}
			else {
				window.location.reload(true);
			}
		},
		error: function() {
			jHide();
			/* restore the previous state */
			$this.prop("checked", !isEnabling);
			jAlert("Failure, please try again.");
		}
	});
}

function ondeleteSubnet() {
	var $this = $(this);
	var id = $this.closest('tr').attr('eid');
	var postData = {};

	postData.op = 'del';
	postData.id = id;

	jProgress('This may take several seconds', 60);
	$.ajax({
		type: 'POST',
		url: 'actionHandler/ajaxSet_WAN_additional_subnet.php',
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
			jHide();
			jAlert("Failure, please try again.");
		}
	});
}

function onclickNatDisable() {
	var disabled = $('#nat_disable').prop("checked");
	if (disabled) {
		$('#nat_dhcp').attr({checked: false, disabled: true});
	}
	else {
		$('#nat_dhcp').prop("disabled", false);
	}
}

function isWanGWInValidRange(gwIp, rangeIp, rangeMask) {
	var rangeIpBin = ip4StrToBin(rangeIp);
	var rangeMaskBin = ip4StrToBin(rangeMask);
	var gwIpBin = ip4StrToBin(gwIp);

	if (rangeIpBin === null || rangeMaskBin === null || gwIpBin === null
		|| !isIp4ValidInSubnet(gwIpBin, rangeIpBin, rangeMaskBin)) {
		return false;
	}
	return true;
}

function onsave(event) {
	event.stopPropagation();
	event.preventDefault();

	var wanStaticIP = $('#wan_ip').val();
	var wanSubnetMask = $('#subnet_mask').val();
	var nameServer1 = $('#dns_primary').val();
	var nameServer2 = $('#dns_secondary').val();
	var wanMode = $('#wan_ip_method').comboVal();
	var staticDnsEnabled = $('#manual_dns_enable').is(':checked');
	var natDisable = $('#nat_disable').is(':checked');
	var natDhcp = $('#nat_dhcp').is(':checked:enabled');

	var msg = "";
	pattern=/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
	if(wanMode != "DHCP") {
		/* validate manual IP address */
                if(isPrivate_Addr(wanStaticIP)) {
                      msg+="The Ip address is within the private IP range.\n";
                }
		if(pattern.test(wanStaticIP) == false)
			msg+="The IP Address is invalid.\n";

		/* validate manual subnet mask */
		pattern2=/^(((128|192|224|240|248|252|254)\.0\.0\.0)|(255\.(0|128|192|224|240|248|252|254)\.0\.0)|(255\.255\.(0|128|192|224|240|248|252|254)\.0)|(255\.255\.255\.(0|128|192|224|240|248|252|254)))$/i;
		if(pattern2.test(wanSubnetMask) == false)
			msg+="The Subnet Mask is invalid.\n";

		/* validate ip range */
		var ipBin = ip4StrToBin(wanStaticIP);
		var maskBin = ip4StrToBin(wanSubnetMask);
		if (!isIp4ValidInSubnet(ipBin, ipBin, maskBin)) {
			msg += "The IP Address is not valid host IP.\n";
		}
	}

	if(staticDnsEnabled) {
		if(pattern.test(nameServer1) == false)
			msg+="The Primary DNS is invalid.\n";
		if(nameServer2 != "" && pattern.test(nameServer2) == false)
			msg+="The Secondary DNS is invalid.\n";
	}

	if(msg != "") {
		jAlert("Please fix the following problems:\n\n"+msg);
		return;
	}

	var info;

	if(wanMode == "DHCP") {
		info = '{"wanMode": "'+wanMode+'", ';
		if (staticDnsEnabled != o_staticDnsEnabled || staticDnsEnabled == true) {
			info += '"staticDnsEnabled": "'+staticDnsEnabled+'", ' +
				'"nameServer1": "'+nameServer1+'", '+
				'"nameServer2": "'+nameServer2+'", ';
		}
		info += '"__ph": true}';

		saveWAN(info, false);
	}
	else {
		info = '{"wanMode": "'+wanMode+'", ';
		if (staticDnsEnabled != o_staticDnsEnabled || staticDnsEnabled == true) {
			info += '"staticDnsEnabled": "'+staticDnsEnabled+'", ' +
				'"nameServer1": "'+nameServer1+'", '+
				'"nameServer2": "'+nameServer2+'", ';
		}
		info += '"wanStaticIP": "'+wanStaticIP+'", '+
			'"wanSubnetMask": "'+wanSubnetMask+'", '+
			'"natDisable": "'+natDisable+'", '+
			'"natDhcp": "'+natDhcp+'", ';
		info += '"__ph": true}';

		saveWAN(info, false);
	}
}

function saveWAN(information, reboot){
	var info = new Array("btn1", "Device");
	jProgress('This may take several seconds', 90);
	$.ajax({
		type: 'POST',
		url: "actionHandler/ajaxSet_WAN_configuration.php",
		dataType: 'json',
		data: {wanInfo: information},
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
			if(reboot) {
				jConfirm("You must restart your gateway for the configuration changes made to WAN IP to take effect.\n Click OK to restart now or Cancel if you plan to restart manually later."
				, "Are You Sure?"
				, function(ret) {
					if(ret) {
						setResetInfo(info);
						setTimeout(function(){
							location.href = "home_loggedout.php";
						}, 3000);
					}
					else
						location.reload()
				});
			}
			else {
				jProgress('Please wait for refreshing', 60);
				window.location.reload(true);
			}
		},
		error: function() {
			jHide();
			jAlert("Failure, please try again.");
		}
	});
}

function setResetInfo(info) {
	var jsonInfo = '["' + info[0] + '","' + info[1] + '"]';

	jProgress('This may take several seconds', 60);
	$.ajax({
		type: 'POST',
		url: "actionHandler/ajaxSet_Reset_Restore.php",
		dataType: 'json',
		data: {resetInfo: jsonInfo},
		success: function(){
			jHide();
		},
		error: function(){
			jHide();
			jAlert("Failure, please try again.");
		}
	});
}

function setWanDHCP(mode) {
	var information;
	if(mode == "release") information = '{"release":"true"}';
	else information = '{"renew":"true"}';

	jProgress('This may take several seconds', 60);
	$.ajax({
		type: "POST",
		url: "actionHandler/ajaxSet_WAN_configuration.php",
		dataType: 'json',
		data: {wanInfo: information},
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
			if (mode == 'release') {
				$('#curNatIpInfo1, #curNatIpInfo3').hide();
				$('#curNatIpInfo2').show();
			}
			else {
				$('#curNatIpInfo1, #curNatIpInfo2').hide();
				$('#curNatIpInfo3').text('Your new ip address is '+data.curNatIp).show();
			}
		},
		error: function() {
			jHide();
			jAlert("Failure, please try again.");
		}
	});
}

$(document).ready(function() {
    comcast.page.init("Gateway > Connection > WAN", "nav-wan");

	/* information part */
	$('#curNatIpInfo1').text(o_wanCurNatIp);

	/* init wan mode parameters */
	onchangeWanMode(o_wanMode, true);
	$('#wan_ip_method').change(function(){
		onchangeWanMode();
	});

	/* manual dns */
	onchangeManualDns(o_staticDnsEnabled, true);
	$('#manual_dns_enable').click(function(){
		onchangeManualDns();
	});

	/* enable subnet checkbox */
	$('input[name=enableSubnet]').off('click').click(function(){
		var that = this;
		var isEnabling = $(this).prop("checked");

		/* prevent this if true static ip is not enabled */
		if (!o_wanTrueStaticIpEnable) {
			jAlert("It should be under \"True Static IP\" mode before you can enable additional subnet.");
			$(that).prop("checked", !isEnabling);
			return;
		}

		jConfirm('Are you sure you want to '+(isEnabling ? 'enable' : 'disable')+' this additional subnet?',
			"Are You Sure?", function(ret) {
			if(ret) {
				onenableSubnet.call(that, isEnabling);
			}
			else {
				$(that).prop("checked", !isEnabling);
			}
		});
	});

	/* delete subnet button */
	$('td.delete > a.btn').off('click').click(function(e){
		e.stopPropagation();
		e.preventDefault();
		var message = ($(this).attr("title").length > 0) ?
			"Are you sure you want to " + $(this).attr("title") + "?" : "Are you sure?";
		var that = this;
		jConfirm(message, "Are You Sure?", function(ret) {
			if(ret) {
				ondeleteSubnet.call(that);
			}    
		});
	});

	/* edit and add button when not true static ip mode */
	if (!o_wanTrueStaticIpEnable) {
		$("#add-service, td.edit > a.btn").off("click").click(function(e){
			e.stopPropagation();
			e.preventDefault();
			/* prevent this if true static ip is not enabled */
			jAlert("It should be under \"True Static IP\" mode before you can add/edit additional subnet.");
			return false;
		});
	}

	$('#nat_disable').click(onclickNatDisable);

	/* submit */
	$('#submit').click(onsave);
});

</script>

<div id="content">
	<h1>Gateway > Connection > WAN</h1>

    <div id="educational-tip">
        <p class="tip"> Add a rule for adding WAN IP.</p>
        <p class="hidden">WAN IP Method: A Public IP for WAN interface is automatically assigned using DHCP Protocol or Statically assigned by Comcast.</p>
    </div>

	<form method="post" id="pageForm" action="wan.php">
	<div class="module forms">
		<h2>WAN IP Setup</h2>
		<div class="form-row ">
        <p id="curNatIp"><strong>Current WAN-NAT IP Address:</strong>&nbsp;<span id='curNatIpInfo1'></span><span id='curNatIpInfo2' style="display:none;text-decoration:blink;"> Your IP has been released.Click on "Renew IP" to obtain new ip.</span><span id='curNatIpInfo3' style="display:none;text-decoration:blink;"></span></p>
		</div>	
			<div class="form-row odd" id="row_ipmethod">
				<label for="wan_ip_method">WAN IP Method:</label>
				<select id="wan_ip_method" name="wan_ip_method" class="input-big">
                    <option value="DHCP">WAN DHCP</option>
                    <option value="TrueStatic">True Static IP</option>
                </select>
			</div>

			<div class="form-row " id="row_ip">
			  <span class="label-text"><label for="wan_ip">IP Address</label></span>
              <span class="input"><input type="text" id="wan_ip" name="wan_ip" class="input-big" value=""/></span>
			</div>

			<div class="form-row " id="row_mask">
			  <span class="label-text"><label for="subnet_mask">Subnet Mask</label></span>
			  <span class="input"><input type="text" id="subnet_mask" name="subnet_mask" class="input-big" value=""/></span>
			</div>

			<div class="form-row" id="row_gw">
			  <span class="label-text"><label for="gateway_ip">Gateway IP</label></span>
			  <span class="input"><input type="text" id="gateway_ip" name="gateway_ip" class="input-big" value="" disabled/></span>
			</div>			

            <div class="form-btn " id="row_dhcpctrl">
                    <span id="release"><a id="release_wan" style="position:relative; top: 0px ; left: -220px; cursor: default;" href="javascript:void(0);" rel="release_ip"><img src="cmn/img/release_ip_off.gif" alt="Release IP" name="release_ip" /></a></span>
					<span id="renew"><a id="renew_wan" style="position:relative; top: 0px ; left: -220px; cursor: default;" href="javascript:void(0);" rel="renew_ip"><img src="cmn/img/renew_ip_off.gif" alt="Renew IP" name="renew_ip" /></a></span>
			</div>

				  
		  <div class="form-row odd">
		  <label for="manual_dns_enable" class="acs-hide">Assign DNS Manually</label>
          <input type="checkbox" id="manual_dns_enable" name="manual_dns_enable" /><b>Assign DNS Manually</b>
		  </div>
		  <div class="form-row ">
			  <span class="label-text"><label for="dns_primary">Primary DNS:</label></span>
              <span class="input"><input type="text" id="dns_primary" name="dns_primary" class="input-mid" disabled="disabled" value=""/></span>
			</div>
			<div class="form-row">
			  <span class="label-text"><label for="dns_secondary">Secondary DNS:</label></span>
			  <span class="input"><input type="text" id="dns_secondary" name="dns_secondary" class="input-mid" disabled="disabled" value=""/></span>
		  </div>
		  <div class="form-row odd" id="row_natdisable">
		  <label for="nat_disable" class="acs-hide">Disable NAT</label>
			  <input type="checkbox" id="nat_disable" name="nat_disable" /><b>Disable NAT</b>
		  </div>
		  <div class="form-row" id="row_natdhcp">
		  <label for="nat_dhcp" class="acs-hide">NAT on DHCP IP</label>
			  <input type="checkbox" id="nat_dhcp" name="nat_dhcp" /><b>NAT on DHCP IP</b>
		  </div>

		<div class="form-btn odd">
			<input id="submit" type="button" value="save" class="btn submit"/>
			<input type="button" id="btn-cancel" value="Cancel" class="btn alt reset" onclick="location.reload()"/>
		</div>
		
			</div> <!-- end .module -->
	</form>

	<div id=forwarding-items>	
	<form method="post" id="pageForm2" action="wan.php"> 
	<div class="module data">
		<h2>Additional Public Subnets</h2>
			<p class="button"><a href="wan_edit.php?t=add" class="btn" id="add-service">+ ADD New</a></p>

		<table class="data" summary="This table lists all additional public subnets">
		    <tr>
		        <th id="hdr_number">#</th>
				<th id="hdr_publicip">Public IP Address</th>
				<th id="hdr_subnet">Subnet Mask</th>
				<th id="hdr_enable">Enable</th>
				<th id="hdr_controls" colspan="2">&nbsp;</th>
		    </tr>
<?php
/* output the additional subnet table entries */
if (isset($addiSubnetTable)) {
	$eIdx = 0;
	$htmlStr = '';
	foreach ($addiSubnetTable as $entry) {
		$htmlStr .= '<tr eid="'.$entry['id'].'" '.(($eIdx%2 != 0) ? 'class="odd"' : "").'>';
		$htmlStr .= '<td headers="hdr_number">'.($eIdx+1).'</td>';
		$htmlStr .= '<td headers="hdr_publicip">'.$entry['ip'].'</td>';
		$htmlStr .= '<td headers="hdr_subnet">'.$entry['mask'].'</td>';
		$htmlStr .= '<td headers="hdr_enable"><label for="enableSubnet'.$eIdx.'" class="acs-hide"></label>'.
			'<input type="checkbox" id="enableSubnet'.$eIdx.'" name="enableSubnet" '.($entry['enable'] ? 'checked' : '').' /></td>';
		$htmlStr .= '<td headers="hdr_controls" class="edit"><a href="wan_edit.php?id='.$entry['id'].'" class="btn">Edit</a></td>';
		$htmlStr .= '<td headers="hdr_controls" class="delete"><a href="javascript:void(0)" class="btn" title="delete Public IP Address '.$entry['ip'].'?">x</a></td>';
		$htmlStr .= '</tr>';
		$eIdx++;
	}
	if (count($addiSubnetTable) == 0) {
		echo '<tr><td colspan="6">No entries!</td></tr>';
	}
	else {
		echo $htmlStr;
	}
}
else {
	echo '<tr><td colspan="6">Error occurs when fetching entries!</td></tr>';
}
?>
		<tfoot>
			<tr class="acs-hide">
				<td headers="hdr_number">null</td>
				<td headers="hdr_publicip">null</td>
				<td headers="hdr_subnet">null</td>
				<td headers="hdr_enable">null</td>
				<td headers="hdr_controls">null</td>
				<td headers="hdr_controls">null</td>
			</tr>
		</tfoot>
		</table>
		</div> 
	  	</form>
		</div> <!-- end .module -->


	</div><!-- end #content -->

<?php include('includes/footer.php'); ?>
