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
<div  id="sub-header">
	<?php include('includes/userbar.php'); ?>
</div><!-- end #sub-header -->

<?php include('includes/nav.php'); ?>
<?php include('includes/utility.php'); ?>
<?php
/* This page handles Add/Edit for 1-1 NAT */
$reqType = $_GET['t'];
if (!isset($reqType)) $reqType = 'edit';
$id = $_GET['id'];
if ($reqType !== 'add' && !isset($id)) {
	echo 'Request parameter is invalid!';
	exit(0);
}

if(isset($id) && preg_match('/^[0-9]+$/', $id) != 1) {
	echo "Requested ID is invalid";
	exit(0);
}

/* prepare the data */
$wanTrueStaticIpAddr = getStr('Device.X_CISCO_COM_TrueStaticIP.IPAddress');
$wanTrueStaticIpMask = getStr('Device.X_CISCO_COM_TrueStaticIP.SubnetMask');
$addiSubnetTable = fetchAdditionalSubnetTable();
$lanGwIp = getStr("Device.X_CISCO_COM_DeviceControl.LanManagementEntry.1.LanIPAddress");
$lanSubnetMask = getStr("Device.X_CISCO_COM_DeviceControl.LanManagementEntry.1.LanSubnetMask");
$OneToOneNAT= getStr("Device.DeviceInfo.X_RDKCENTRAL-COM_RFC.Feature.OneToOneNAT");
/*if ($_DEBUG) {
	$wanTrueStaticIpAddr = '1.1.1.6';
	$wanTrueStaticIpMask = '255.255.255.248';
	$lanGwIp = '10.1.10.1';
	$lanSubnetMask = '255.255.255.0';
}*/

$pageTypeStr = ($reqType === 'add') ? 'Add' : 'Edit';

if ($reqType === 'add') {
	/* add, nothing to do here */
}
else {
	/* edit based on id */
	$rootObjName    = "Device.NAT.PortMapping.$id.";
	$paramNameArray = array("Device.NAT.PortMapping.$id.");
	$mapping_array  = array("X_Comcast_com_PublicIP", "InternalClient", "Enable");
	$natArray = getParaValues($rootObjName, $paramNameArray, $mapping_array);
	/*if ($_DEBUG) {
		$natArray = array(array('__id'=>$id,'X_Comcast_com_PublicIP'=>$id.'.1.1.1','InternalClient'=>'10.0.0.'.$id,'Enable'=>'true'));
	}*/

	if (count($natArray) > 0) {
		$entry = $natArray[0];
		$publicIp = $entry['X_Comcast_com_PublicIP'];
		$privateIp = $entry['InternalClient'];
		$isEnable = $entry['Enable'] === 'true';
	}
	else {
		echo 'Failed to fetch NAT entry';
		exit(0);
	}
}
?>
<script type="text/javascript">
var o_id = <?php echo isset($id) ? "\"$id\"" : "null"; ?>;
var o_publicIp = <?php echo isset($publicIp) ? "\"$publicIp\"" : "null"; ?>;
var o_privateIp = <?php echo isset($privateIp) ? "\"$privateIp\"" : "null"; ?>;
var o_isEnable = <?php echo isset($isEnable) && $isEnable ? "true" : "false"; ?>;

var o_wanTrueStaticIpAddr = "<?php echo $wanTrueStaticIpAddr; ?>";
var o_wanTrueStaticIpMask = "<?php echo $wanTrueStaticIpMask; ?>";
var o_lanGwIp = "<?php echo $lanGwIp; ?>";
var o_lanSubnetMask = "<?php echo $lanSubnetMask; ?>";
var o_addiSubnets = [
<?php
$isFirst = true;
foreach ($addiSubnetTable as $entry) {
	if (!$isFirst) {
		echo ",\n";
	}
	$isFirst = false;
	echo '{ip: "'.$entry['ip'].'", mask: "'.$entry['mask'].'"}';
}
?>
];
var OneToOneNAT = ("<?php echo $OneToOneNAT; ?>"=="true") ? true : false;

function initNatFields() {
	var publicIpArr = o_publicIp ? o_publicIp.split('.') : null;
	var privateIpArr = o_privateIp ? o_privateIp.split('.') : null;

	if (publicIpArr == null || privateIpArr == null) return;

	$('[id^=public_ip_address_]').each(function(idx){
		$(this).val(publicIpArr[idx]);
	});
	$('[id^=private_ip_address_]').each(function(idx){
		$(this).val(privateIpArr[idx]);
	});
}

function initValidation() {
	$("#pageForm").validate({
		onfocusout: false,
		onkeyup: false,
		groups: {
			public_ip: "public_ip_address_1 public_ip_address_2 public_ip_address_3 public_ip_address_4",
			private_ip: "private_ip_address_1 private_ip_address_2 private_ip_address_3 private_ip_address_4"
		},
		rules: {
			public_ip_address_1: {
        		required: true,
        		min: 1,
        		max: 255,
        		digits: true
        	},
			public_ip_address_2: {
        		required: true,
        		min: 0,
        		max: 255,
        		digits: true
        	},
			public_ip_address_3: {
        		required: true,
        		min: 0,
        		max: 255,
        		digits: true
        	},
			public_ip_address_4: {
        		required: true,
        		min: 0,
        		max: 255,
        		digits: true
        	},
			private_ip_address_1: {
        		required: true,
        		min: 1,
        		max: 255,
        		digits: true
        	},
			private_ip_address_2: {
        		required: true,
        		min: 0,
        		max: 255,
        		digits: true
        	},
			private_ip_address_3: {
        		required: true,
        		min: 0,
        		max: 255,
        		digits: true
        	},
			private_ip_address_4: {
        		required: true,
        		min: 0,
        		max: 255,
        		digits: true
        	}
		}
	});
}

function validateIpRange(publicIp, privateIp) {
	var msg = '';
	var publicIpBin = ip4StrToBin(publicIp);
	var privateIpBin = ip4StrToBin(privateIp);

	/* check publicIp */
	var isInTSIRange = isIp4ValidInSubnet(publicIpBin, ip4StrToBin(o_wanTrueStaticIpAddr), ip4StrToBin(o_wanTrueStaticIpMask));
	var isInAddiSubnetRange = false;
	for (var i=0; i<o_addiSubnets.length; ++i) {
		var subnet = o_addiSubnets[i];
		if (isIp4ValidInSubnet(publicIpBin, ip4StrToBin(subnet.ip), ip4StrToBin(subnet.mask))) {
			isInAddiSubnetRange = true;
			break;
		}
	}
	if (!isInTSIRange && !isInAddiSubnetRange) {
		msg += 'Public IP is in range of neither True Static IP subnet nor Additional Public Subnets.\n';
	}
	if (publicIpBin !== null && publicIpBin === ip4StrToBin(o_wanTrueStaticIpAddr)) {
		msg += 'Public IP should not be the same as True Static IP.\n';
	}

	/* check privateIp */
	if (!isIp4ValidInSubnet(privateIpBin, ip4StrToBin(o_lanGwIp), ip4StrToBin(o_lanSubnetMask))) {
		msg += 'Private IP is not in range of local network.\n';
	}

	return msg;
}

function onsave() {
	if ($('#pageForm').valid()) {
		var errMsg = '';
		var postData = {};
		postData.publicIp = $('#public_ip_address_1').val() + '.' +
						$('#public_ip_address_2').val() + '.' +
						$('#public_ip_address_3').val() + '.' +
						$('#public_ip_address_4').val();
		postData.ip = $('#private_ip_address_1').val() + '.' +
						$('#private_ip_address_2').val() + '.' +
						$('#private_ip_address_3').val() + '.' +
						$('#private_ip_address_4').val();
		/* try to validate ip range */
		errMsg = validateIpRange(postData.publicIp, postData.ip);
		if (errMsg != '') {
			jAlert("Please fix the following problems:\n\n"+errMsg);
			return;
		}

		if (o_id === null) {
			postData.add = true;
		}
		else {
			postData.edit = true;
			postData.ID = o_id;
		}

		/* other predefined attributes for OneOneNat */
		postData.toOneOneNat = OneToOneNAT;
		postData.name = 'OneOneNat';
		postData.ipv6addr = 'x';
		postData.startport = 0;
		postData.endport = 0;	// set to 0 since we don't care about the range
		postData.type = 'TCP/UDP';

		jProgress('This may take several seconds', 60);
		$.ajax({
			type: 'POST',
			url: 'actionHandler/ajax_port_forwarding.php',
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
				window.location.href = 'nat.php';
			},
			error: function() {
				jHide();
				jAlert("Failure, please try again.");
			}
		});
	}
}

$(document).ready(function() {
    comcast.page.init("Advanced > NAT > <?php echo $pageTypeStr; ?> Network Address Translation", "nav-nat");

	initNatFields();
	initValidation();

	if(!OneToOneNAT){
		console.log("inside if");
		$('.module.forms *').addClass('disabled');
		$( "input[name^='public_ip_address_']" ).prop("disabled", true);
		$( "input[name^='private_ip_address_']" ).prop("disabled", true);
		$('#device').click(function(e){
			e.preventDefault();
		});
		$('input.submit').click(function(e) {
			e.stopPropagation();
			e.preventDefault();
		});

		$("#btn-cancel").click(function(e) {
    		e.stopPropagation();
			e.preventDefault();
    	});
	}else{
		$('.module.forms *').removeClass('disabled');

		 $('#device').click(function(){
	 	
			$.virtualDialog({
				title: "Select from below Connected Devices:",
				content: $("#device_list"),
				footer: '<div id="pop-btn-group">' +
							'<div style="float:left; position:relative; left:140px">' +
							'<input id="add_btn" type="button" value="Add"/>' +
							'</div>' +
							'<div style="position:relative; left:200px">' +
							'<input id="close_btn" type="button" value="Close" />' +
							'</div>' +
						'</div>'
			});
			$('#add_btn').click(function() {
				var ipv4_addr = $('input[type="radio"]:checked').parent().parent().find("td:eq(1)").text().replace(/^\s+|\s+$/g, '');
				var ipv4_arr = ipv4_addr.split(".");
				$("#private_ip_address_1").val(ipv4_arr[0]);
				$("#private_ip_address_2").val(ipv4_arr[1]);
				$("#private_ip_address_3").val(ipv4_arr[2]);
				$("#private_ip_address_4").val(ipv4_arr[3]);
				$.virtualDialog("hide");
			});
			$("#close_btn").click(function(){
				$.virtualDialog("hide");
			});
		$('#add-0').focus();
		});

		$('input.submit').click(function(e) {
			e.stopPropagation();
			e.preventDefault();
			onsave();
		});

		$("#btn-cancel").click(function() {
    		window.location.href = "nat.php";
    	});
	}
	
   

});
</script>

<div id="content">
	<h1>Advanced > NAT > <?php echo $pageTypeStr; ?> Network Address Translation</h1>

    <div id="educational-tip">
        <p class="tip"> Add a rule for 1-to-1 Network Address Translation.</p>
         <p class="tip"> NOTE : Adding 1-to-1 NAT rule is not recommended, as it poses a security risk.</p>
    </div>

	<form method="post" id="pageForm" action="nat.php">
	<div class="module forms">
		<h2><?php echo $pageTypeStr; ?> Network Address Translation</h2>

		<div class="form-row ">
					<label for="public_ip_address_1">Public IP Address:</label>
					 <input type="text" size="3" value="" id="public_ip_address_1" name="public_ip_address_1" class="" title="first part"/>
					<label for="public_ip_address_2" class="acs-hide"></label>
			        .<input type="text" size="3" value="" id="public_ip_address_2" name="public_ip_address_2" class="" title="second part"/>
					<label for="public_ip_address_3" class="acs-hide"></label>
			        .<input type="text" size="3" value="" id="public_ip_address_3" name="public_ip_address_3" class="" title="third part"/>
					<label for="public_ip_address_4" class="acs-hide"></label>
			        .<input type="text" size="3" value="" id="public_ip_address_4" name="public_ip_address_4" class="" title="fourth part"/>
		</div>

		<div class="form-row ">
					<label for="private_ip_address_1">Private IP Address:</label>
		             <input type="text" size="3" value="" id="private_ip_address_1" name="private_ip_address_1" class="" title="first part"/>
					<label for="private_ip_address_2" class="acs-hide"></label>
			        .<input type="text" size="3" value="" id="private_ip_address_2" name="private_ip_address_2" class="" title="second part"/>
					<label for="private_ip_address_3" class="acs-hide"></label>
			        .<input type="text" size="3" value="" id="private_ip_address_3" name="private_ip_address_3" class="" title="third part"/>
					<label for="private_ip_address_4" class="acs-hide"></label>
			        .<input type="text" size="3" value="" id="private_ip_address_4" name="private_ip_address_4" class="" title="fourth part"/>
		</div>	
		<div class="form-row">
			<strong><p>Select a device to add IPv4 address</p></strong>
			<input  id="device" type="button" value="Connected Device" class="btn"  style="position:relative;top:0px;right: 0px;"/>
		</div>

		<div class="form-btn">
			<input type="submit" value="save" class="btn submit"/>
			<input type="button" id="btn-cancel" value="Cancel" class="btn alt reset"/>
		</div>

	</div> <!-- end .module -->
</form>

</div><!-- end #content -->
<div id="device_list" style="display: none;">
	<table summary="This table lists connected devices">
	<tr>
		<th id="device-nmae">Device Name</th>
		<th id="ipv4-addr">IPv4 Address</th>
		<th id="add-radio">Add</th>					
		<th colspan="2">&nbsp;</th>
	</tr>

	<?php 
		$hostsInstance = getInstanceIds("Device.Hosts.Host.");
		$hostsInstanceArr = explode(",", $hostsInstance);
		$hostNums = getStr("Device.Hosts.HostNumberOfEntries");
		/*if ($_DEBUG) {
			$hostNums = "2";
		}*/

		for ($i=0; $i < $hostNums; $i++) { 

			$HostName  = getStr("Device.Hosts.Host.$hostsInstanceArr[$i].HostName");
			$IPAddress = getStr("Device.Hosts.Host.$hostsInstanceArr[$i].IPAddress");
			$Active    = getStr("Device.Hosts.Host.$hostsInstanceArr[$i].Active");
			$IPv6Addr = "";
			$IPv6Num = getStr("Device.Hosts.Host.$hostsInstanceArr[$i].IPv6AddressNumberOfEntries");
			if (((int)$IPv6Num) > 0) {
				$ids = explode(",", getInstanceIds("Device.Hosts.Host.$hostsInstanceArr[$i].IPv6Address."));
				foreach ($ids as $j) {
					if ($j === '') break;
					$val = getStr("Device.Hosts.Host.$hostsInstanceArr[$i].IPv6Address.$j.IPAddress");
					if (stripos($val, "fe80:") === 0) continue;
					$IPv6Addr = $val;
					break;
				}
			}
			/*if ($_DEBUG) {
				$HostName = "Host ".($i+1);
				$IPAddress = "1.1.1.".($i+1);
				$IPv6Addr = "2001::89$i";
				$Active = "true";
			}*/
			
			if($Active == 'true'){
				echo '<tr>';
				echo '<td headers="devcie-name">'. $HostName . '</td>';
				echo '<td headers="ipv4-addr">'. $IPAddress . '</td>';
				echo '<td headers="add-radio"><input type="radio" value="add" id="add-' .$i. '" name="select-device"><label for="add-' .$i. '" class="acs-hide"></label></td>';
				echo '</tr>';
			} 
		}

	?>
	<tfoot>
		<tr class="acs-hide">
			<td headers="device-name">null</td>
			<td headers="ipv4-addr">null</td>
			<td headers="add-radio">null</td>
		</tr>
	</tfoot>

   </table>
</div>
<?php include('includes/footer.php'); ?>
