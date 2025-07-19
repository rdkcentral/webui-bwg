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

<div id="sub-header">
	<?php include('includes/userbar.php'); ?>
</div><!-- end #sub-header -->

<?php include('includes/nav.php'); ?>

<?php 
$DHCPEnable = getStr("Device.DHCPv4.Server.Enable");
$DMZEnalbe  = getStr("Device.NAT.X_CISCO_COM_DMZ.Enable");
$LanGwIP    = getStr("Device.X_CISCO_COM_DeviceControl.LanManagementEntry.1.LanIPAddress");
$LanNetmask = getStr("Device.X_CISCO_COM_DeviceControl.LanManagementEntry.1.LanSubnetMask");
$DHCPTime   = getStr("Device.DHCPv4.Server.Pool.1.LeaseTime");
$begin_addr = getStr("Device.DHCPv4.Server.Pool.1.MinAddress");
$end_addr   = getStr("Device.DHCPv4.Server.Pool.1.MaxAddress");
$DHCPV6Time	= "";
?>

<style type="text/css">

label{
	margin-right: 10px !important;
}

</style>

<script type="text/javascript">
$(document).ready(function() {
	comcast.page.init("Gateway > Initial Setup", "nav-initial-setup");

	var isBridge = "<?php echo $_SESSION["lanMode"]; ?>";
        var login_user = "<?php echo $_SESSION["loginuser"]; ?>";

    if(isBridge != 'router'){
      //local network configuration should not be editable in bridge mode
      $('#pageForm *').addClass('disabled').prop("disabled", true);
     
	  return; //stop further processing
    };

    var jsGwIP = "<?php echo $LanGwIP; ?>";
    var jsNetMask = "<?php echo $LanNetmask; ?>";
    var jsLeaseTime = "<?php echo $DHCPTime; ?>";
    var jsV6LeaseTime = "<?php echo $DHCPV6Time; ?>";

	function updateIPv4() {
		if (netmask_is_validate() == false) {
			return;
		}

		var ip_addr = $("#ipv4_gateway_address_1").val() + "." + $("#ipv4_gateway_address_2").val() + "." + $("#ipv4_gateway_address_3").val() + "." + $("#ipv4_gateway_address_4").val();
		var subnet_mask = $("#ipv4_subnet_mask_1").val() + "." + $("#ipv4_subnet_mask_2").val() + "." + $("#ipv4_subnet_mask_3").val() + "." + $("#ipv4_subnet_mask_4").val();

		if (subnet_mask == "255.255.255.255") {
			var ipv4_dhcp_beginning_address = ip_addr;
			var ipv4_dhcp_ending_address = ip_addr;		
		} else {
			var ip = ip2long(ip_addr);
			var nm = ip2long(subnet_mask);
			var nw = ip & nm;
			var bc = nw | (~nm);
			var ipv4_dhcp_beginning_address = long2ip(nw + 1);
			var ipv4_dhcp_ending_address = long2ip(bc - 1);

                        if ( $("#ipv4_gateway_address_4").val() == "1" ) {
                                ipv4_dhcp_beginning_address = long2ip(nw + 2);
			}
		}
		var beginning_ip = ip_addr.split('.');
		var ending_ip = ipv4_dhcp_ending_address.split('.');
		ending_ip[3] -= 1;
		
		$("#ipv4_dhcp_beginning_address_1").val(replaceNaNwithEmptyString(beginning_ip[0]));
		$("#ipv4_dhcp_beginning_address_2").val(replaceNaNwithEmptyString(beginning_ip[1]));
		$("#ipv4_dhcp_beginning_address_3").val(replaceNaNwithEmptyString(beginning_ip[2]));
		$("#ipv4_dhcp_beginning_address_4").val(replaceNaNwithEmptyString(2));

		$("#ipv4_dhcp_ending_address_1").val(replaceNaNwithEmptyString(ending_ip[0]));
		$("#ipv4_dhcp_ending_address_2").val(replaceNaNwithEmptyString(ending_ip[1]));
		$("#ipv4_dhcp_ending_address_3").val(replaceNaNwithEmptyString(ending_ip[2]));
		$("#ipv4_dhcp_ending_address_4").val(replaceNaNwithEmptyString(ending_ip[3]));

		$("#ipv4_dhcp_beginning_address_1").prop("disabled", false);
		$("#ipv4_dhcp_ending_address_1").prop("disabled", false);
		$("#ipv4_dhcp_beginning_address_2").prop("disabled", false);
		$("#ipv4_dhcp_ending_address_2").prop("disabled", false);
		$("#ipv4_dhcp_beginning_address_3").prop("disabled", false);
		$("#ipv4_dhcp_ending_address_3").prop("disabled", false);
		$("#ipv4_dhcp_beginning_address_4").prop("disabled", false);
		$("#ipv4_dhcp_ending_address_4").prop("disabled", false);

		if ($("#ipv4_subnet_mask_1").val() == "255") {
			$("#ipv4_dhcp_beginning_address_1").prop("disabled", true);
			$("#ipv4_dhcp_ending_address_1").prop("disabled", true);
		}

		if ($("#ipv4_subnet_mask_2").val() == "255") {
			$("#ipv4_dhcp_beginning_address_2").prop("disabled", true);
			$("#ipv4_dhcp_ending_address_2").prop("disabled", true);
		}

		if ($("#ipv4_subnet_mask_3").val() == "255") {
			$("#ipv4_dhcp_beginning_address_3").prop("disabled", true);
			$("#ipv4_dhcp_ending_address_3").prop("disabled", true);
		}

		if ($("#ipv4_subnet_mask_4").val() == "255") {
			$("#ipv4_dhcp_beginning_address_4").prop("disabled", true);
			$("#ipv4_dhcp_ending_address_4").prop("disabled", true);
		}
	}//end of updateIPv4

	// Update range addresses automatically
	$(".gateway_address").blur(function() {
		updateIPv4();
	});
/*
	$("#ipv4_subnet_mask").change(function() {
		updateIPv4();
	});
*/
	function initPopulateDHCPv4(){
/*
		$("select#ipv4_subnet_mask").prop("disabled", false);
		$("select option").prop("disabled", false);
		$('#ipv4_dhcp_lease_time_measure').prop("disabled", false);

		if ($('#ipv4_dhcp_lease_time_measure').val() != "forever") 
			$('#ipv4_dhcp_lease_time_amount').prop("disabled", false);
*/
		if ($("#ipv4_subnet_mask_1").val() == "255") {
			$("#ipv4_dhcp_beginning_address_1").prop("disabled", true);
			$("#ipv4_dhcp_ending_address_1").prop("disabled", true);
		}
		
		if ($("#ipv4_subnet_mask_2").val() == "255") {
			$("#ipv4_dhcp_beginning_address_2").prop("disabled", true);
			$("#ipv4_dhcp_ending_address_2").prop("disabled", true);
		}
		
		if ($("#ipv4_subnet_mask_3").val() == "255") {
			$("#ipv4_dhcp_beginning_address_3").prop("disabled", true);
			$("#ipv4_dhcp_ending_address_3").prop("disabled", true);
		}
				
		if ($("#ipv4_subnet_mask_4").val() == "255") {
			$("#ipv4_dhcp_beginning_address_4").prop("disabled", true);		
			$("#ipv4_dhcp_ending_address_4").prop("disabled", true);
		}
	}

	initPopulateDHCPv4();

	// Disable time text field, if lease time is forever
	$("#ipv4_dhcp_lease_time_measure").change(function() {
		var $select = $(this);
		var $time = $("#ipv4_dhcp_lease_time_amount");
		if($select.find("option:selected").val() == "forever") {
			$time.prop("disabled", true).addClass("disabled").val();
		} else {
			$time.prop("disabled", false).removeClass("disabled").val();
		}
	}).trigger("change");


	jQuery.validator.addMethod("checkMask",function(value,element){		
		var netmask = $("#ipv4_subnet_mask_1").val() + "." + $("#ipv4_subnet_mask_2").val() + "." + $("#ipv4_subnet_mask_3").val() + "." + $("#ipv4_subnet_mask_4").val();
		if (netmask == '255.255.255.128'){
			return ((value>=2) && (value<=126));
		}
		else if (netmask == '255.255.255.252'){
			return  (value == 2);
		}
		else if (netmask == '255.255.255.0'){
			return ((value>=2) && (value<=(element.id == "ipv4_dhcp_ending_address_4" ? 253 : 254)));
		}
		else
		{
			return true;
		}
	}, "DHCP ending address is beyond the valid range.");

	$("#pageForm").validate({
		debug: true,
		onfocusout: false,
		onkeyup: false,
		groups: {
	    	ip_set: "ipv4_gateway_address_1 ipv4_gateway_address_2 ipv4_gateway_address_3 ipv4_gateway_address_4",
	    	net_mask: 	"ipv4_subnet_mask_1 ipv4_subnet_mask_2 ipv4_subnet_mask_3 ipv4_subnet_mask_4",
	    	b_range:"ipv4_dhcp_beginning_address_2, ipv4_dhcp_beginning_address_3, ipv4_dhcp_beginning_address_4",
	    	e_range:"ipv4_dhcp_ending_address_2, ipv4_dhcp_ending_address_3, ipv4_dhcp_ending_address_4",
		},
		rules: {
			ipv4_gateway_address_1: {
				required: true,
				min: 1,
				max: 255,
				digits: true
			},
			ipv4_gateway_address_2: {
				required: true,
				min: 0,
				max: 255,
				digits: true
			},
			ipv4_gateway_address_3: {
				required: true,
				min: 0,
				max: 255,
				digits: true
			},
			ipv4_gateway_address_4: {
				required: true,
				min: 1,
				max: 253,
				digits: true
			}
			,ipv4_dhcp_beginning_address_2: {
			    required: true,
				min: 0,
				max: 255,
				digits: true
			}
			,ipv4_dhcp_ending_address_2: {
			    required: true,
				min: 0,
				max: function(){
					var mask_2 = parseInt($("#ipv4_subnet_mask_2").val(),10);
					return (255-mask_2);
				},
				digits: true,
			}
			,ipv4_dhcp_beginning_address_3: {
			    required: true,
				min: 0,
				max: 255,
				digits: true
			}
			,ipv4_dhcp_ending_address_3: {
			    required: true,
				min: 0,
				max: function(){
					var mask_3 = parseInt($("#ipv4_subnet_mask_3").val(),10);
					return (255-mask_3);
				},
				digits: true,
			}
			,ipv4_dhcp_beginning_address_4: {
			    required: true,
				min: 2,
				max: 254,
				digits: true
			}
			,ipv4_dhcp_ending_address_4: {
			    required: true,
				min: 1,
				max: function(){
					var mask_3 = parseInt($("#ipv4_subnet_mask_3").val(),10);
					var mask_4 = parseInt($("#ipv4_subnet_mask_4").val(),10);
					var ending_address_3 = parseInt($("#ipv4_dhcp_ending_address_3").val(),10);
					var ipv4_DEA_4 = 255-mask_4-1;
					if((parseInt(mask_3,10) + parseInt(ending_address_3,10))>='255') return (ipv4_DEA_4-1);
					else return ipv4_DEA_4;
				},
				digits: true,
			}
			,ipv4_dhcp_lease_time_amount: {
				required: function() {
					return $("#ipv4_dhcp_lease_time_measure option:selected").val() != "forever";
				},
	            digits : true,
	            min: 1
	        }	        
		},
	    highlight: function( element, errorClass, validClass ) {
			$(element).closest(".form-row").find("input").addClass(errorClass).removeClass(validClass);
		},
		unhighlight: function( element, errorClass, validClass ) {
			$(element).closest(".form-row").find("input").removeClass(errorClass).addClass(validClass);
		}
	});

	jQuery.validator.addMethod("noHTML", function(value, element) {
		return this.optional(element) || ! /<\/?[^>]+(>|$)/g.test(value);
		}, "No HTML tags are allowed!");

	$("#pageForm2").validate({
		debug: true,
		onfocusout: false,
		onkeyup: false,
		rules: {
			https_filename: {
				required: true,
				noHTML: true
			}
		},
	    highlight: function( element, errorClass, validClass ) {
			$(element).closest(".form-row").find("input").addClass(errorClass).removeClass(validClass);
		},
		unhighlight: function( element, errorClass, validClass ) {
			$(element).closest(".form-row").find("input").removeClass(errorClass).addClass(validClass);
		}
	});

		$.validator.addMethod("hexadecimal", function(value, element) {
    		return this.optional(element) || /^[a-fA-F0-9]+$/i.test(value);
    	}, "Only hexadecimal characters are valid. Acceptable characters are ABCDEF0123456789.");

	$("#restore-default-settings-ipv4").click(function(e) {
		e.preventDefault();

		jConfirm(
		"Are you sure you want the change LAN IPv4 to default settings?"
		,"Reset Default IPv4 Settings"
		,function(ret) {
		if(ret) {

		$("#ipv4_gateway_address_1").val(10);
		$("#ipv4_gateway_address_2").val(1);
		$("#ipv4_gateway_address_3").val(10);
		$("#ipv4_gateway_address_4").val(1);
		
		$("#ipv4_subnet_mask_1").val(255)
		$("#ipv4_subnet_mask_2").val(255)
		$("#ipv4_subnet_mask_3").val(255)
		$("#ipv4_subnet_mask_4").val(0)

		$("#ipv4_dhcp_beginning_address_1").val(10);
		$("#ipv4_dhcp_beginning_address_2").val(1);
		$("#ipv4_dhcp_beginning_address_3").val(10);
		$("#ipv4_dhcp_beginning_address_4").val(2);

		$("#ipv4_dhcp_ending_address_1").val(10);
		$("#ipv4_dhcp_ending_address_2").val(1);
		$("#ipv4_dhcp_ending_address_3").val(10);
		$("#ipv4_dhcp_ending_address_4").val(253);
		
		$("#ipv4_dhcp_lease_time_amount").val(1);
		$("#ipv4_dhcp_lease_time_measure").val("weeks");
		updateIPv4();
		var ipaddr = "10.1.10.1"; //ares specific requirement
		var subnet_mask = "255.255.255.0"; 
		var dhcp_begin_addr = "10.1.10.2";
		var dhcp_end_addr = "10.1.10.253";
		var lease_time = 604800; // 1 week	

		//==================================ares specific
		var routername = "BWG/ARES";
		//var dmz_enable = "false";
		//var dmz_host   = "10.1.10.5";
        var Config = '{"Ipaddr":"' + ipaddr + '", "Subnet_mask":"' + subnet_mask + '", "Dhcp_begin_addr":"' + dhcp_begin_addr + 
        			'", "Dhcp_end_addr":"' + dhcp_end_addr +'", "Dhcp_lease_time":"' + lease_time + '", "routername":"' + routername + '"}';
        			//'", "dmz_enable":"' + dmz_enable + '", "dmz_host":"' + dmz_host + '"}';
        
	    setIPconfiguration(Config,ipaddr);

		} //end of if ret
		});
	});//end of click

/*var gw_ip_1 = parseInt($('#ipv4_gateway_address_1').val());
if (gw_ip_1 == 172){
	//gw ip is B class ip address		
	if( $('#mask4').length>0 ) $('#mask4').remove();
	if( ! $('#mask2').length>0 ) mask2Option.insertAfter('#mask1');
}
else if (gw_ip_1 == 192){
	//gw ip is C class ip address
	if( $('#mask2').length>0 ) $('#mask2').remove();
	if( $('#mask4').length>0 ) $('#mask4').remove();
}*/

// $('#ipv4_gateway_address_1').change(function(){
// 	var gw_ip1 = parseInt($('#ipv4_gateway_address_1').val());

//     var mask2Option = $('<option id="mask2" value="255.255.0.0">255.255.0.0</option>');
//     var mask4Option = $('<option id="mask4" value="255.0.0.0">255.0.0.0</option>');

//     if (gw_ip1 == 10){
// 		if( ! $('#mask2').length>0 ) mask2Option.insertAfter('#mask1');
// 		if( ! $('#mask4').length>0 ) mask4Option.insertAfter('#mask3');
//     }
//     else if (gw_ip1 == 172){
// 		//gw ip is B class ip address
// 		if( $('#mask4').length>0 ) $('#mask4').remove();
// 		if( ! $('#mask2').length>0 ) mask2Option.insertAfter('#mask1');
// 	}
// 	else if (gw_ip1 == 192){
// 		//gw ip is C class ip address
// 		if( $('#mask2').length>0 ) $('#mask2').remove();
// 		if( $('#mask4').length>0 ) $('#mask4').remove();
// 	}
// });

function ipv4_valid_range(){
	var gw4_1 = parseInt($("#ipv4_gateway_address_1").val());
	var gw4_2 = parseInt($("#ipv4_gateway_address_2").val());
	var gw4_3 = parseInt($("#ipv4_gateway_address_3").val());
	var gw4_4 = parseInt($("#ipv4_gateway_address_4").val());

	var ip_addr = gw4_1 + "." + gw4_2 + "." + gw4_3 + "." + gw4_4;
	var subnet_mask = $("#ipv4_subnet_mask_1").val() + "." + $("#ipv4_subnet_mask_2").val() + "." + $("#ipv4_subnet_mask_3").val() + "." + $("#ipv4_subnet_mask_4").val();

	if (subnet_mask == "255.255.255.255") {
		var ipv4_dhcp_beginning_address = ip_addr;
		var ipv4_dhcp_ending_address = ip_addr;
	} else {
		var ip = ip2long(ip_addr);
		var nm = ip2long(subnet_mask);
		var nw = ip & nm;
		var bc = nw | (~nm);
		var ipv4_dhcp_beginning_address = long2ip(nw + 1);
		var ipv4_dhcp_ending_address = long2ip(bc - 1);
	}
	var beginning_ip = ipv4_dhcp_beginning_address;
	var ending_ip = ipv4_dhcp_ending_address.split('.');
	ending_ip[3] -= 1;
	ending_ip = ending_ip.join('.');
	return [beginning_ip, ending_ip];
}

/* 
 This function checks dhcpv4 ending address should be larger than begin address
 @DBArr = Array(dhcp begin dot address partial 2, 3, 4)
 @DEArr = Array(dhcp ending dot address partial 2, 3, 4)
 */
function validate_v4addr_pool(DBArr, DEArr) {
	
	var flag = true;

	if (DEArr[0] < DBArr[0]) {
		flag = false;
	}
	else if (DEArr[0] == DBArr[0]) {
		if (DEArr[1] < DBArr[1]) {
			flag = false;
		}
		else if (DEArr[1] == DBArr[1]) {
			if (DEArr[2] < DBArr[2]) {
				flag = false;
			}			
		}
	}

	return flag;
}

function is_ipv4_in_range(ipv4_addr, ipv4_start, ipv4_end) {
	var ipv4_num = ip4StrToBin(ipv4_addr);
	return (ipv4_num >= ip4StrToBin(ipv4_start)) && (ipv4_num <= ip4StrToBin(ipv4_end));
}
function netmask_is_validate()
	{
		var netmask = $("#ipv4_subnet_mask_1").val() + "." + $("#ipv4_subnet_mask_2").val() + "." + $("#ipv4_subnet_mask_3").val() + "." + $("#ipv4_subnet_mask_4").val();
		return judgeSubnetMask(netmask);
	}

function judgeSubnetMask(ipAddress){
		var ip_addr = $("#ipv4_gateway_address_1").val();
		var exp="";
		if(ip_addr=="10" || ip_addr=="172" || ip_addr=="192" ){
			exp="^(((255\.){3}(255|254|252|248|240|224|192|128|0+))|((255\.){2}(255|254|252|248|240|224|192|128|0+)\.0)|((255\.)(255|254|252|248|240|224|192|128|0+)(\.0+){2})|((255|254|252|248|240|224|192|128|0+)(\.0+){3}))$";
		}
		var str = ipAddress;
		var patt = new RegExp(exp);
		var res= patt.test(str);
		if(patt.test(str)==true)
			return true;
		else
			return false;
	}

function long2ip (ip) {
			if (!isFinite(ip))
			return false

			return [ip >>> 24, ip >>> 16 & 0xFF, ip >>> 8 & 0xFF, ip & 0xFF].join('.')
		}
//  discuss at: https://locutus.io/php/ip2long/
  // original by: Waldo Malqui Silva (https://waldo.malqui.info)
  // improved by: Victor
  //  revised by: fearphage (https://my.opera.com/fearphage/)
  //  revised by: Theriault (https://github.com/Theriault)
  //    estarget: es2015
  //   example 1: ip2long('192.0.34.166')
  //   returns 1: 3221234342
  //   example 2: ip2long('0.0xABCDEF')
  //   returns 2: 11259375
  //   example 3: ip2long('255.255.255.256')
  //   returns 3: false
function ip2long (IP) {
			var i = 0

			IP = IP.match(
				/^([1-9]\d*|0[0-7]*|0x[\da-f]+)(?:\.([1-9]\d*|0[0-7]*|0x[\da-f]+))?(?:\.([1-9]\d*|0[0-7]*|0x[\da-f]+))?(?:\.([1-9]\d*|0[0-7]*|0x[\da-f]+))?$/i
			) // Verify IP format.
			if (!IP) {
				// Invalid format.
				return false
			}
			// Reuse IP variable for component counter.
			IP[0] = 0
			for (i = 1; i < 5; i += 1) {
				IP[0] += !!((IP[i] || '')
				.length)
				IP[i] = parseInt(IP[i]) || 0
			}
			// Continue to use IP for overflow values.
			IP.push(256, 256, 256, 256)
			IP[4 + IP[0]] *= Math.pow(256, 4 - IP[0])
			if (IP[1] >= IP[5] || IP[2] >= IP[6] || IP[3] >= IP[7] || IP[4] >= IP[8]) {
				return false
			}
			return IP[1] * (IP[0] === 1 || 16777216) + IP[2] * (IP[0] <= 2 || 65536) + IP[3] * (IP[0] <= 3 || 256) + IP[4] * 1
		}
function replaceNaNwithEmptyString(value) {
			if(isNaN(value)) {
				return "";
			} else {
				return value;
			}
		}
function validate_v4addr_subnetRange(DBArr, DEArr) {
	ipv4_valid_range_ip	= ipv4_valid_range();
	var beginning_ip 	= ipv4_valid_range_ip[0];
	var ending_ip 		= ipv4_valid_range_ip[1];
	return is_ipv4_in_range(DBArr, beginning_ip, ending_ip) && is_ipv4_in_range(DEArr, beginning_ip, ending_ip);
}

$('#submit_ipv4').click(function(e){
	e.preventDefault();

    var dhcp4B2 = parseInt($("#ipv4_dhcp_beginning_address_2").val());
	var dhcp4B3 = parseInt($("#ipv4_dhcp_beginning_address_3").val());
	var dhcp4B4 = parseInt($("#ipv4_dhcp_beginning_address_4").val());
	var dhcp4E2 = parseInt($("#ipv4_dhcp_ending_address_2").val());
	var dhcp4E3 = parseInt($("#ipv4_dhcp_ending_address_3").val());
	var dhcp4E4 = parseInt($("#ipv4_dhcp_ending_address_4").val());
	var gtE2 = parseInt($("#ipv4_gateway_address_2").val());
	var gtE3 = parseInt($("#ipv4_gateway_address_3").val());
	var gtE4 = parseInt($("#ipv4_gateway_address_4").val());

	var DBArr = Array(dhcp4B2, dhcp4B3, dhcp4B4);
	var DEArr = Array(dhcp4E2, dhcp4E3, dhcp4E4);
	var GWArr = Array(gtE2, gtE3, gtE4);

	var ipaddr = $('#ipv4_gateway_address_1').val() + "." + $('#ipv4_gateway_address_2').val() + "." + $('#ipv4_gateway_address_3').val() + "." + $('#ipv4_gateway_address_4').val();
	var subnet_mask = $("#ipv4_subnet_mask_1").val() + "." + $("#ipv4_subnet_mask_2").val() + "." + $("#ipv4_subnet_mask_3").val() + "." + $("#ipv4_subnet_mask_4").val();
	var dhcp_begin_addr = $('#ipv4_dhcp_beginning_address_1').val() + "." + $('#ipv4_dhcp_beginning_address_2').val() + "." + $('#ipv4_dhcp_beginning_address_3').val() + "." + $('#ipv4_dhcp_beginning_address_4').val();
	var dhcp_end_addr = $('#ipv4_dhcp_ending_address_1').val() + "." + $('#ipv4_dhcp_ending_address_2').val() + "." + $('#ipv4_dhcp_ending_address_3').val() + "." + $('#ipv4_dhcp_ending_address_4').val();

    if (netmask_is_validate() == false) {
                jAlert("Invalid netmask!");
                return;
    }

    if (! validate_v4addr_pool(GWArr,DBArr)) {
        jAlert("Beginning Address can't be smaller than gateway address!");
        return;
    }	
    if (! validate_v4addr_pool(DBArr, DEArr)) {
        jAlert("Beginning Address can't be larger than ending address!");
        return;
    }

    if (! validate_v4addr_subnetRange(dhcp_begin_addr, dhcp_end_addr)) {
        jAlert("Either Gateway or DHCP Begin/End Addresses are invaid, please input again!");
        return;
    }

    var gw_ip1 = parseInt($('#ipv4_gateway_address_1').val());
    var gw_ip2 = parseInt($('#ipv4_gateway_address_2').val());
    var gw_ip3 = parseInt($('#ipv4_gateway_address_3').val());
     if( ((gw_ip1 != 10) && (gw_ip1 != 172) && (gw_ip1 != 192)) || ((gw_ip1 == 172) && ((gw_ip2<16) || (gw_ip2>31)))  || ((gw_ip1== 192) && ((gw_ip2 != 168) || (gw_ip3== 147)) ) ){
		jAlert("Gateway IP is not in valid private IP range\n [10.0.0.1 ~ 10.255.255.253,\n172.16.0.1 ~ 172.31.255.253,\n192.168.0.1 ~ 192.168.146.253,\n192.168.148.1 ~ 192.168.255.253]");
    	return;
    }
    if (((gw_ip1==192) && (gw_ip2==168) && (gw_ip3==100))|| ((gw_ip1==172) && (gw_ip2==31))) {
        jAlert("This IP address is reserved , please input again");
        return;
    }
    if ((gw_ip1==172) && (gw_ip2==16) && (gw_ip3==12)) {
    	jAlert("This IP address is reserved for Home Security, please input again");
    	return;
    }

    //=======================================Ares specific
    var routername = $('#router_name').val();


    if($('#DMZ').is(':checked')){
    	var dmz_host = $('#ipv4_DMZ_1').val() + '.' + $('#ipv4_DMZ_2').val() + '.' + $('#ipv4_DMZ_3').val() + '.' + $('#ipv4_DMZ_4').val();
    	var IPv4Config = '{"Ipaddr":"' + ipaddr + '", "Subnet_mask":"' + subnet_mask + '", "Dhcp_begin_addr":"' + dhcp_begin_addr + 
			             '", "Dhcp_end_addr":"' + dhcp_end_addr + '", "routername":"' + routername + 
			             '", "dmz_enable":"true", "dmz_host":"' + dmz_host + '"}';

	    var host0 = parseInt($("#ipv4_DMZ_1").val());
	    var host1 = parseInt($("#ipv4_DMZ_2").val());
	    var host2 = parseInt($("#ipv4_DMZ_3").val());
	    var host3 = parseInt($("#ipv4_DMZ_4").val());
	    //to check whether user input DMZ host is a valid ip address	    
	    if (dmz_host == ipaddr){
	    		jAlert("DMZ Host IP can't be equal to the Gateway IP address !");
	    		return;
	    	}
		//alert(jsNetMask);
		if(jsNetMask.indexOf('255.255.255') >= 0){
			//the first three field should be equal to gw ip field
			if((gw_ip1 != host0) || (gw_ip2 != host1) || (gw_ip3 != host2)){
			  var msg = 'DMZ Host IP is not in valid range:\n' + gw_ip1+'.'+gw_ip2+'.'+gw_ip3+'.[2~254]';
			  jAlert(msg);
			  return;
			}		
		}
		else if(jsNetMask == "255.255.0.0"){
			if((gw_ip1 != host0) || (gw_ip2 != host1)){
			  jAlert('DMZ Host IP is not in valid range:\n' + gw_ip1+ '.' + gw_ip2 + '.[0~255]' + '.[2~254]');
			  return;
			}		
		}
		else{
			if(gw_ip1 != host0){
			  jAlert("DMZ Host IP is not valid, please input again !");
			  return;
			}		
		}
    }
    else
		var IPv4Config = '{"dmz_disable":"true", "Ipaddr":"' + ipaddr + '", "Subnet_mask":"' + subnet_mask + '", "Dhcp_begin_addr":"' + dhcp_begin_addr + 
						'", "Dhcp_end_addr":"' + dhcp_end_addr + '", "routername":"' + routername + '"}';
   	setIPconfiguration(IPv4Config,ipaddr);
});

function setIPconfiguration(configuration,ipaddr){

	if($("#pageForm").valid()){
                var ipaddr = $('#ipv4_gateway_address_1').val() + "." + $('#ipv4_gateway_address_2').val() + "." + $('#ipv4_gateway_address_3').val() + "." + $('#ipv4_gateway_address_4').val();
                var subnet_mask = $("#ipv4_subnet_mask_1").val() + "." + $("#ipv4_subnet_mask_2").val() + "." + $("#ipv4_subnet_mask_3").val() + "." + $("#ipv4_subnet_mask_4").val();
                var dhcp_begin_addr = $('#ipv4_dhcp_beginning_address_1').val() + "." + $('#ipv4_dhcp_beginning_address_2').val() + "." + $('#ipv4_dhcp_beginning_address_3').val() + "." + $('#ipv4_dhcp_beginning_address_4').val();
                var dhcp_end_addr = $('#ipv4_dhcp_ending_address_1').val() + "." + $('#ipv4_dhcp_ending_address_2').val() + "." + $('#ipv4_dhcp_ending_address_3').val() + "." + $('#ipv4_dhcp_ending_address_4').val();
                var IPaddress = "<?php echo $LanGwIP; ?>";
                var Lan_Subnet_Mask = "<?php echo $LanNetmask; ?>";
                var dhcp_min_addr = "<?php echo $begin_addr; ?>";
                var dhcp_max_addr = "<?php echo $end_addr; ?>";
            if((IPaddress === ipaddr) && (Lan_Subnet_Mask === subnet_mask) && (dhcp_min_addr  === dhcp_begin_addr) && (dhcp_max_addr === dhcp_end_addr)){
               jProgress("This may take several seconds.",60);
                  $.ajax({
                type:"POST",
                url:"actionHandler/ajaxSet_initial_setup.php",
                data: { configInfo: configuration  },
                success:function(){
                        jHide();
                        window.location.reload();
                    },
                error: function(){
                        jHide();
                        jAlert("Error! Please try later!");
                     }
            });

         }
          else{    
		var str = 'Changing Gateway IP or any DHCP setting would reset the gateway.\n';
		str += '\n';
		str += '<b>WARNING</b>: Gateway will be rebooted!\n';
		str += 'Incoming/Outgoing call and internet connection will be interrupted!';
		jConfirm(
		str
		,"Are you sure?"
		,function(ret) {
			if(ret) {
				jProgress('This may take several seconds', 60);
				$.ajax({
					type: "POST",
					url: "actionHandler/ajaxSet_initial_setup.php",
					data: { configInfo: configuration },
					success: function(){
						jHide();
						jProgress("Please wait for rebooting ...", 999999);
					},
					error: function(){
						jHide();
						jProgress("Please wait for rebooting ...", 999999);
					}
				});
				if(login_user == "cusadmin")
				{                        
					setTimeout(function(){
						jHide();
						window.location.replace('http://' + ipaddr + '/index.php');
					}, 4 * 60 * 1000); 
                                }
                                else
                                {
                                        setTimeout(checkForRebooting, 4 * 60 * 1000);
                                }
			}
		});
	}
    }
}

function calcuate_lease_time(num, unit){
   
    switch (unit) {
	case 'seconds':
		return num;
		break;
	case 'minutes':
		return num * 60;
		break;
	case 'hours':
		return num * 3600;
		break;
	case 'days': 
		return num * 3600 * 24;
		break;	
	case 'weeks': 
		return num * 3600 * 24 * 7;
		break;	
	case 'forever':
	    return -1;
	    break;	
   }
}

  display_time_format(jsLeaseTime);

function display_time_format(num){

	if (num == "") return;
    var timeNum = parseInt(num);
	var TimeVal  = '#ipv4_dhcp_lease_time_amount';

	if ( (timeNum % 604800) == 0) {
       $(TimeVal).val(timeNum / 604800);
       $('#ipv4_dhcp_lease_time_measure option[value="weeks"]').prop("selected", true);
	}
	else if( (timeNum % 86400) == 0 ) {
	   $(TimeVal).val(timeNum / 86400);
       $('#ipv4_dhcp_lease_time_measure option[value="days"]').prop("selected", true);
	}
	else if( (timeNum % 3600) == 0 ) {
	   $(TimeVal).val(timeNum / 3600);
       $('#ipv4_dhcp_lease_time_measure option[value="hours"]').prop("selected", true);
	}
	else if( (timeNum % 60) == 0 ) {
	   $(TimeVal).val(timeNum / 60);
       $('#ipv4_dhcp_lease_time_measure option[value="minutes"]').prop("selected", true);
	}
	else if( timeNum == -1) {
	   $(TimeVal).prop("disabled", true);
       $('#ipv4_dhcp_lease_time_measure option[value="forever"]').prop("selected", true);
	}
	else {
	   $(TimeVal).val(timeNum);
       $('#ipv4_dhcp_lease_time_measure option[value="seconds"]').prop("selected", true);
	}
}


function checkForRebooting() {
    $.ajax({
    type: "GET",
    url: "index.php",
    timeout: 10000,
        success: function() {
                /* goto login page once the box reboots*/
                window.open ("index.php","_self");
                setTimeout(window.close(),1000);
           },
           error: function() {
                /* retry after 2 minutes */
                setTimeout(checkForRebooting, 30 * 1000);
           }
    });
}
  
//========enable/disable Lan DHCP server
$('#Lan').click(function(){
	if ( $(this).is(':checked') ) {
		var configInfo = '{enableDHCP:"true"}';		
	}
	else{
		var configInfo = '{enableDHCP:"false"}';	
	}
	      jProgress("This may take several seconds.",60);
				$.ajax({
				type:"POST",
				url:"actionHandler/ajaxSet_initial_setup.php",
				data: eval('('+configInfo+')'),
				success:function(){
					jHide();
                                        window.location.reload();
				},
				error: function(){
					jHide();
					jAlert("Error! Please try later!");
				}
			});
});

/*$('#DMZ').click(function(){
	if ( $(this).is(':checked') ) {
		var configInfo = '{enableDMZ:"true"}';		
	}
	else{
		var configInfo = '{enableDMZ:"false"}';	
	}
	saveConfig(configInfo);
});
*/

var DHCPEnable = "<?php echo $DHCPEnable; ?>";
var DMZEnalbe  = "<?php echo $DMZEnalbe; ?>";

if (DHCPEnable == 'false'){
	document.getElementById('ipv4_gateway_address_1').disabled= true;
	document.getElementById('ipv4_gateway_address_2').disabled=true;
	document.getElementById('ipv4_gateway_address_3').disabled=true;
	document.getElementById('ipv4_gateway_address_4').disabled=true;
	
	document.getElementById('ipv4_subnet_mask_1').disabled=true;
	document.getElementById('ipv4_subnet_mask_2').disabled=true;
	document.getElementById('ipv4_subnet_mask_3').disabled=true;
	document.getElementById('ipv4_subnet_mask_4').disabled=true;

	document.getElementById('ipv4_dhcp_beginning_address_1').disabled=true;
	document.getElementById('ipv4_dhcp_beginning_address_2').disabled=true;
	document.getElementById('ipv4_dhcp_beginning_address_3').disabled=true;		
	document.getElementById('ipv4_dhcp_beginning_address_4').disabled=true;
	document.getElementById('ipv4_dhcp_ending_address_1').disabled=true;
	document.getElementById('ipv4_dhcp_ending_address_2').disabled=true;
	document.getElementById('ipv4_dhcp_ending_address_3').disabled=true;		
	document.getElementById('ipv4_dhcp_ending_address_4').disabled=true;
}

if (DMZEnalbe == 'false'){
	document.getElementById('ipv4_DMZ_1').disabled=true;
	document.getElementById('ipv4_DMZ_2').disabled=true;
	document.getElementById('ipv4_DMZ_3').disabled=true;
	document.getElementById('ipv4_DMZ_4').disabled=true;
}

}); //end of document ready

//================================================

function edmz()
{
	if( document.getElementById('DMZ').checked==true)
	{
		document.getElementById('ipv4_DMZ_1').disabled=false;
		document.getElementById('ipv4_DMZ_2').disabled=false;
		document.getElementById('ipv4_DMZ_3').disabled=false;
		document.getElementById('ipv4_DMZ_4').disabled=false;
	}
	else
	{
		document.getElementById('ipv4_DMZ_1').disabled=true;
		document.getElementById('ipv4_DMZ_2').disabled=true;
		document.getElementById('ipv4_DMZ_3').disabled=true;
		document.getElementById('ipv4_DMZ_4').disabled=true;
	}	
}

</script>


<div id="content">
	<h1>Gateway > Initial Setup</h1>

	<div id="educational-tip">
		<p class="tip">Manage Initial Setup settings.</p>
		<p class="hidden"><strong>LAN IP address:</strong> Enter the IP address of the LAN.</p>
		<p class="hidden"><strong>LAN Subnet Mask:</strong> The subnet mask is associated with the IP address.</p>
		<p class="hidden"><strong>DHCP Starting and Ending Addresses:</strong> The DHCP server in the Gateway allows the router to manage IP address assignment for the connected devices.</p>
		<p class="hidden"><strong>DMZ Host IP:</strong> Enter the IP address of the DMZ Host</p>
		<p class="hidden"><strong>Router Name:</strong> Enter the name of the Router</p>
		<p class="hidden"><strong>TFTP Server:</strong> Enter the IP address of the TFTP server</p>
		<p class="hidden"><strong>File Name:</strong> Enter the name of the configuration file to be download</p>
	</div>

	<form action="#TBD" method="post" id="pageForm">
	   <input type="hidden" name="restore_factory_settings" id="restore_factory_settings" class="restore_factory_settings" value="false" />
    <div class="module forms">
   	    <h2>Initial Setup</h2>

			<div class="form-row">
				<input type="checkbox"  name="lan" <?php if ($DHCPEnable == 'true') echo 'checked';  ?> value="Lan" id="Lan" />
				<label for="Lan" class="acs-hide"></label>
				<b>Enable LAN DHCP</b>
			</div>		

    		<div class="form-row odd">
    			<label for="ipv4_gateway_address_1">Lan IP Address:</label>
    			<?php      		
    			   $ipArr = explode(".", $LanGwIP);    			   
    			?>
                <input type="text" size="3" maxlength="3"  value="<?php echo $ipArr['0']; ?>" id="ipv4_gateway_address_1" name="ipv4_gateway_address_1" class="gateway_address smallInput"> 
    	        <label for="ipv4_gateway_address_2" class="acs-hide"></label>
    	        .<input type="text" size="3" maxlength="3" value="<?php echo $ipArr['1']; ?>" id="ipv4_gateway_address_2" name="ipv4_gateway_address_2" class="gateway_address smallInput"> 
    	        <label for="ipv4_gateway_address_3" class="acs-hide"></label>
    	        .<input type="text" size="3" maxlength="3" value="<?php echo $ipArr['2']; ?>" id="ipv4_gateway_address_3" name="ipv4_gateway_address_3" class="gateway_address smallInput"> 
    	        <label for="ipv4_gateway_address_4" class="acs-hide"></label>
    	        .<input type="text" size="3" maxlength="3" value="<?php echo $ipArr['3']; ?>" id="ipv4_gateway_address_4"  name="ipv4_gateway_address_4" class="gateway_address smallInput" />
    		</div>
    		<div class="form-row ">
    			<label for="ipv4_subnet_mask">Lan Subnet Mask:</label>
    			<?php 
                    $subnetmask = getStr("Device.X_CISCO_COM_DeviceControl.LanManagementEntry.1.LanSubnetMask");
                    $mask = explode(".", $subnetmask);
    			?>
				<input type="text" size="3" maxlength="3"  value="<?php echo $mask['0']; ?>" id="ipv4_subnet_mask_1" name="ipv4_subnet_mask_1" class="gateway_address smallInput" />
				<label for="ipv4_subnet_mask_2" class="acs-hide"></label>
				.<input type="text" size="3" maxlength="3" value="<?php echo $mask['1']; ?>" id="ipv4_subnet_mask_2" name="ipv4_subnet_mask_2" class="gateway_address smallInput" />
				<label for="ipv4_subnet_mask_3" class="acs-hide"></label>
				.<input type="text" size="3" maxlength="3" value="<?php echo $mask['2']; ?>" id="ipv4_subnet_mask_3" name="ipv4_subnet_mask_3" class="gateway_address smallInput" />
				<label for="ipv4_subnet_mask_4" class="acs-hide"></label>
				.<input type="text" size="3" maxlength="3" value="<?php echo $mask['3']; ?>" id="ipv4_subnet_mask_4" name="ipv4_subnet_mask_4" class="gateway_address smallInput" />
    	    </div>
    		<div class="form-row odd">
    			<label for="ipv4_dhcp_beginning_address_1">DHCP Start IP:</label>
                <?php  
    			   $beginAddr = getStr("Device.DHCPv4.Server.Pool.1.MinAddress");
    			   $beginArr = explode(".", $beginAddr);    			   
    			?>  
    			<input type="text" size="3" maxlength="3" value="<?php echo $beginArr['0']; ?>" id="ipv4_dhcp_beginning_address_1" name="ipv4_dhcp_beginning_address_1" class="smallInput" />
    	        <label for="ipv4_dhcp_beginning_address_2" class="acs-hide"></label>
    	        .<input type="text" size="3" maxlength="3" value="<?php echo $beginArr['1']; ?>" id="ipv4_dhcp_beginning_address_2" name="ipv4_dhcp_beginning_address_2" class="smallInput" />
    	        <label for="ipv4_dhcp_beginning_address_3" class="acs-hide"></label>
    	        .<input type="text" size="3" maxlength="3" value="<?php echo $beginArr['2']; ?>" id="ipv4_dhcp_beginning_address_3" name="ipv4_dhcp_beginning_address_3" class="smallInput" />
    	        <label for="ipv4_dhcp_beginning_address_4" class="acs-hide"></label>
    	        .<input type="text" size="3" maxlength="3" value="<?php echo $beginArr['3']; ?>" id="ipv4_dhcp_beginning_address_4" name="ipv4_dhcp_beginning_address_4" class="smallInput" />
    	        
    		</div>
    		<div class="form-row ">
                <label for="ipv4_dhcp_ending_address_1">DHCP End IP:</label>
                <?php  
    			   $endAddr = getStr("Device.DHCPv4.Server.Pool.1.MaxAddress");
    			   $endArr = explode(".", $endAddr);    
    			?> 
				<input type="text" size="3" maxlength="3" value="<?php echo $endArr['0']; ?>" id="ipv4_dhcp_ending_address_1" name="ipv4_dhcp_ending_address_1" class="smallInput" />
    	        <label for="ipv4_dhcp_ending_address_2" class="acs-hide"></label>
    	        .<input type="text" size="3" maxlength="3" value="<?php echo $endArr['1']; ?>" id="ipv4_dhcp_ending_address_2" name="ipv4_dhcp_ending_address_2" class="smallInput" />
    	        <label for="ipv4_dhcp_ending_address_3" class="acs-hide"></label>
    	        .<input type="text" size="3" maxlength="3" value="<?php echo $endArr['2']; ?>" id="ipv4_dhcp_ending_address_3" name="ipv4_dhcp_ending_address_3" class="smallInput" />
    	        <label for="ipv4_dhcp_ending_address_4" class="acs-hide"></label>
    	        .<input type="text" size="3" maxlength="3" value="<?php echo $endArr['3']; ?>" id="ipv4_dhcp_ending_address_4" name="ipv4_dhcp_ending_address_4" class="smallInput"  />
    	       
    		 </div>

			<?php				
				$dmz_host = getStr("Device.NAT.X_CISCO_COM_DMZ.InternalIP");
				$dhost = explode('.', $dmz_host);

			?>
			<div class="form-row odd">
				<input type="checkbox" <?php if ($DMZEnalbe == 'true') echo "checked"; ?> name="DMZ" value="DMZ" id="DMZ" onClick="edmz()" /><b>Enable DMZ Host</b>
				<label for="DMZ" class="acs-hide"></label>
			</div>			
    		<div class="form-row ">
    			<label for="ipv4_DMZ_1">DMZ Host IP:</label>
                 <input type="text" size="3" maxlength="3" value="<?php echo $dhost[0]; ?>" id="ipv4_DMZ_1" name="ipv4_DMZ_1" class="smallInput" />
                 <label for="ipv4_DMZ_2" class="acs-hide"></label>
    	        .<input type="text" size="3" maxlength="3" value="<?php echo $dhost[1]; ?>" id="ipv4_DMZ_2" name="ipv4_DMZ_2" class="smallInput" />
                 <label for="ipv4_DMZ_3" class="acs-hide"></label>
    	        .<input type="text" size="3" maxlength="3" value="<?php echo $dhost[2]; ?>" id="ipv4_DMZ_3" name="ipv4_DMZ_3" class="smallInput" />
                 <label for="ipv4_DMZ_4" class="acs-hide"></label>
    	        .<input type="text" size="3" maxlength="3" value="<?php echo $dhost[3]; ?>" id="ipv4_DMZ_4" name="ipv4_DMZ_4" class="smallInput" />
    		</div>
			
		<?php
			$routername = getStr("Device.DeviceInfo.RouterName");
		?>

    		<div class="form-row odd" >
    			<label for="router_name" style="position:relative; top: 0px ; left: -130px;">Router Name:</label>
    			<input style="position:relative; top: 0px ; left: -130px;" type="text" size="15" maxlength="15" value="<?php echo $routername; ?>" name="routername" id="router_name" class="" />
    	    </div>			

			<div class="form-btn odd">
					<input id="submit_ipv4" type="button" value="Save Settings" class="btn" />
					<input id="restore-default-settings-ipv4" type="button" value="Restore Default Settings" class="btn alt" />
				</div>
    		</div> <!-- End Module -->			

			</form>

</div><!-- end #content -->
<?php include('includes/footer.php'); ?>
