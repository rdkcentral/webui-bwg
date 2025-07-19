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
<?php include('includes/utility.php'); ?>

<div id="sub-header">
	<?php include('includes/userbar.php'); ?>
</div><!-- end #sub-header -->

<?php include('includes/nav.php'); ?>

<?php 
	$ret = init_psmMode("Connected Devices - Devices", "nav-cdevices");
	if ("" != $ret){echo $ret;	return;}

	$beginAddr 	= getStr("Device.DHCPv4.Server.Pool.1.MinAddress");
	$endAddr 	= getStr("Device.DHCPv4.Server.Pool.1.MaxAddress");
	$loginuser = $_SESSION["loginuser"];
	$PreferPrivate = getStr("Device.WiFi.X_RDKCENTRAL-COM_PreferPrivate");
	$encrypt_mode = getStr("Device.WiFi.AccessPoint.1.Security.ModeEnabled");
	$encrypt_mode1 = getStr("Device.WiFi.AccessPoint.2.Security.ModeEnabled");
        $modelName= getStr("Device.DeviceInfo.ModelName");
?>

<style>
table a:link, table a:visited {
	text-decoration: none;
	color: #808080;
}
</style>

<script type="text/javascript">
$(document).ready(function() {
    comcast.page.init("Connected Devices > Devices", "nav-cdevices");

	var privateDisabled = ("<?php echo $_SESSION['lanMode']; ?>" != "router") ? true : false ;

	var beginAddr	= "<?php echo $beginAddr; ?>";
	var endAddr		= "<?php echo $endAddr; ?>";
	var beginArr	= beginAddr.split(".");
	var endArr		= endAddr.split(".");

    var login_user = "<?php echo $loginuser; ?>";
    //console.log(login_user);

    if(login_user != "mso") {
    	$('.div-pub-network').remove();
    	$('.div-xhs-network').remove();
    	$('#online-table-cap').html('Online Devices');
    	$('#offline-table-cap').html('Offline Devices');
    }

    function setEducationalTip() {
        if($(".educational-tip-edit:has(.hidden)").length > 0) {
            var closed = true;
            var $link = $("<a href=\"javascript:;\"  class=\"tip-more\">more</a>").click(function() {
                if(closed) {
                    $(".educational-tip-edit .hidden").fadeIn();
                    closed = false;
                    $(this).html("less");
                } else {
                    $(".educational-tip-edit .hidden").fadeOut();
                    closed = true;
                    $(this).html("more");
                }
            }).appendTo(".educational-tip-edit");
        }
    }

    setEducationalTip();

    if ($.browser.msie){
		$("textarea").keypress(function(e){
		    var lengthF = $(this).val();

		    if (lengthF.length > 62){
		        e.preventDefault();
		    }
		});
    }    

    $("input:checked[name='ip']").trigger("click");
    // activate validation plugin
	$(".pageForm").validate({
	    rules : {
		host_name: {
	    		allowed_char: true
	    	},
	    	staticIPAddress: {
	    		ipv4 : {
	            	depends : function() {
	            		return ("static" == $("input:radio[name=ip]:checked").val());
	            	}
	            },
	            required : {
	                depends : function() {
	                	return ("static" == $("input:radio[name=ip]:checked").val());
	                }
	            }
	    	}
	    	,mac_address:{
	    		required: true,
	    		mac: true
	    	},
			comments:{
	    		allowed_char: true
	    	}
	    }
	});

    $(".edit-device").hide();
    $('.device-info').addClass('device-hide');

    $(".btn-cancel").click(function() {
    	window.location.href = "connected_devices_computers.php";
    });

    $('.device-name').click(function(){
	if(!(privateDisabled && $(this).hasClass("private"))){
		$(this).next().toggleClass('device-hide');
		//$('.device-info').toggleClass('device-hide');
	}
    });

    $('.device-name').keypress(function(ev) {
	if(!(privateDisabled && $(this).hasClass("private"))){
		var keycode = (ev.keyCode ? ev.keyCode : ev.which);
		if (keycode == '13') { //enter key, preventDefault is for bug fixing Firefox/IE compatiblity issue
			ev.preventDefault(); 
			$(this).next().toggleClass('device-hide');
		}
	}
    });


    for (var i = 0; i < onlineDeviceNum; i++) {  
    ( 
    	function(x) {
    	//alert(onlineDeviceInstanceArr[i]);    	
        var butn = "#btn-" + onlineDeviceInstanceArr[x];
        var editButn = "#edit-device-" + onlineDeviceInstanceArr[x];
        //alert(editButn);
    	$(butn).click(function(){
		if(!(privateDisabled && $(butn).hasClass("private"))){
			$('.cnt-device-main').hide();
			$(editButn).show();
		}
    	});

    	var submitEditDevice = "#submit_editDevice-" + onlineDeviceInstanceArr[x];
    	var macAddr          = "#mac_address-"       + onlineDeviceInstanceArr[x];
    	var staticIPAddr     = "#staticIPAddress-"   + onlineDeviceInstanceArr[x]; 
        var ipDHCP           = "#ip_dhcp-"           + onlineDeviceInstanceArr[x];
        var ipResv	         = "#ip_static-"         + onlineDeviceInstanceArr[x];
        var comment          = "#comment-"           + onlineDeviceInstanceArr[x];

        var dhcp_mac   = "#dhcp-mac"   + onlineDeviceInstanceArr[x];
        var static_ip  = "#static-ip"  + onlineDeviceInstanceArr[x];
    	//alert(onlineDeviceInstanceArr[x]);

    	var isDHCP = $(ipDHCP).is(":checked");
    	if (isDHCP) {
    	    $(static_ip).hide();
    	}
    	else{
    	    $(static_ip).show();
    	}

    	$(ipResv).click(function(){
    	    $(static_ip).show();
    	});
    	$(ipDHCP).click(function(){
    	    $(static_ip).hide();
    	});
    	
        var hostName = onlineHostNameArr[x];
        var macAddress = onlineHostMAC[x];

    	$(submitEditDevice).click(function(e){

            e.preventDefault();

            var reseverd_ipAddr = $(staticIPAddr).val();
            var isDHCP = $(ipDHCP).is(":checked");
            var Comments = $(comment).val();
	    Comments=Comments.replace(/[\r\n]+/gm, "@" );	
            if (Comments.length > 63) {
                jAlert("The comments should be no more than 63 characters !");
                return;
            }

            if (isDHCP){
                if($(ipDHCP).val() == 'DHCP'){
           	  	//this initial value is DHCP, user is going to modify comments
                    var editDevInfo = '{"UpdateComments": "true", "Comments": " '+ Comments +' ", "hostName": "' + hostName + '", "macAddress": "' + macAddress + '", "reseverd_ipAddr": "' + reseverd_ipAddr + '"}';
                }
                else{
	                // this is to provide info to remove this device in the static addr list, REservedIP => DHCP
	                var editDevInfo = '{"delFlag": "true", "Comments": " '+ Comments +' ", "hostName": "' + hostName + '", "macAddress": "' + macAddress + '", "reseverd_ipAddr": "' + reseverd_ipAddr + '"}';
                }
            }
            else{
				//to check if "Reserved IP Address" is in "DHCP Pool range"
				if(reseverd_ipAddr==""){
					jAlert("Please enter Reserved IP Address !");
					return;
				}
				var reseverd_ipArr	= reseverd_ipAddr.split(".");
				for(i=0;i<4;i++){
					if(parseInt(beginArr[i]) > parseInt(reseverd_ipArr[i]) || parseInt(reseverd_ipArr[i]) > parseInt(endArr[i])){
						jAlert("Reserved IP Address is not in valid range:\n"+beginAddr+" ~ "+endAddr);
						return;
					}
				}

				// this is to provide info to edit REservedIP
				var editDevInfo = '{"Comments": " '+ Comments +' ", "hostName": "' + hostName + '", "macAddress": "' + macAddress + '", "reseverd_ipAddr": "' + reseverd_ipAddr + '"}';
            } 

            //alert(editDevInfo);
           
            if($(".pageForm").valid()){
                jProgress('This may take several seconds', 60); 
    			$.ajax({           	
    				type: "POST",
    				url: "actionHandler/ajaxSet_add_device.php",
    				data: { DeviceInfo: editDevInfo },
    				dataType: "json",
    				success: function(results){
    					setTimeout(function(){
    						jHide();
    						if (results=="success") { window.location.href="connected_devices_computers.php";}
    						else if (results=="") {jAlert('Failure! Please check your inputs.');}
    						else jAlert(results);
    					}, 15000);
    				},
    				error: function(){
    					jHide();
    					jAlert("Failure, Please check your inputs and try again.");
    				}
    			});
            } //end of page form valid
    	}); //end of submit edit device click
       }) (i); //end of function(x)
    }; // end of for loop
    
  for (var i = 0; i < offlineDeviceNum; i++) {
    (
        function(x) {
        var butn = "#btn-" + offlineDeviceInstanceArr[x];
        var editButn = "#offline-edit-device-" + offlineDeviceInstanceArr[x];
        //alert(editButn);
        $(butn).click(function(){
                if(!(privateDisabled && $(butn).hasClass("private"))){
                        $('.cnt-device-main').hide();
                        $(editButn).show();
                }
        });
        var submitEditDevice = "#submit_editDevice-" + offlineDeviceInstanceArr[x];
        var macAddr          = "#mac_address-"       + offlineDeviceInstanceArr[x];
        var staticIPAddr     = "#staticIPAddress-"   + offlineDeviceInstanceArr[x];
        var ipDHCP           = "#ip_dhcp-"           + offlineDeviceInstanceArr[x];
        var ipResv               = "#ip_static-"         + offlineDeviceInstanceArr[x];
        var comment          = "#comment-"           + offlineDeviceInstanceArr[x];
        var dhcp_mac   = "#dhcp-mac"   + offlineDeviceInstanceArr[x];
        var static_ip  = "#static-ip"  + offlineDeviceInstanceArr[x];
        //alert(offlineDeviceInstanceArr[x]);
        var isDHCP = $(ipDHCP).is(":checked");
        if (isDHCP) {
            $(static_ip).hide();
        }
        else{
            $(static_ip).show();
        }
        $(ipResv).click(function(){
            $(static_ip).show();
        });
        $(ipDHCP).click(function(){
            $(static_ip).hide();
        });
        var hostName = offlineHostNameArr[x];
        var macAddress = offlineHostMAC[x];
        $(submitEditDevice).click(function(e){
            e.preventDefault();
            var reseverd_ipAddr = $(staticIPAddr).val();
            var isDHCP = $(ipDHCP).is(":checked");
            var Comments = $(comment).val();
	    Comments=Comments.replace(/[\r\n]+/gm, "@" );
            if (isDHCP){
                if($(ipDHCP).val() == 'DHCP'){
                        //this initial value is DHCP, user is going to modify comments
                    var editDevInfo = '{"UpdateComments": "true", "Comments": " '+ Comments +' ", "hostName": "' + hostName + '", "macAddress": "' + macAddress + '", "reseverd_ipAddr": "' + reseverd_ipAddr + '"}';
                }
                else{
                        // this is to provide info to remove this device in the static addr list, REservedIP => DHCP
                        var editDevInfo = '{"delFlag": "true", "Comments": " '+ Comments +' ", "hostName": "' + hostName + '", "macAddress": "' + macAddress + '", "reseverd_ipAddr": "' + reseverd_ipAddr + '"}';
                }
            }
            else{
                                //to check if "Reserved IP Address" is in "DHCP Pool range"
                                if(reseverd_ipAddr==""){
                                        jAlert("<?php echo _('Please enter Reserved IP Address !')?>");
                                        return;
                                }
                                var reseverd_ipArr      = reseverd_ipAddr.split(".");
                                for(i=0;i<4;i++){
                                        if(parseInt(beginArr[i]) > parseInt(reseverd_ipArr[i]) || parseInt(reseverd_ipArr[i]) > parseInt(endArr[i])){
                                                jAlert("<?php echo _('Reserved IP Address is not in valid range:')?>\n"+beginAddr+" ~ "+endAddr);
                                                return;
                                        }
                                }
                                // this is to provide info to edit REservedIP
                                var editDevInfo = '{"Comments": " '+ Comments +' ", "hostName": "' + hostName + '", "macAddress": "' + macAddress + '", "reseverd_ipAddr": "' + reseverd_ipAddr + '"}';
            }
            //alert(editDevInfo);
            if($(".pageForm").valid()){
                jProgress('<?php echo _("This may take several seconds")?>', 60);
                        $.ajax({
                                type: "POST",
                                url: "actionHandler/ajaxSet_add_device.php",
                                data: { DeviceInfo: editDevInfo },
                                dataType: "json",
                                success: function(results){
                                        setTimeout(function(){
                                                jHide();
                                                if (results=="success") { window.location.href="connected_devices_computers.php";}
                                                else if (results=="") {jAlert('<?php echo _("Failure! Please check your inputs.")?>');}
                                                else jAlert(results);
                                        }, 15000);
                                },
                                error: function(){
                                        jHide();
                                        jAlert("<?php echo _('Failure, Please check your inputs and try again.')?>");
                                }
                        });
            } //end of page form valid
        }); //end of submit edit device click
       }) (i); //end of function(x)
    }; // end of for loop   

    $('.confirm').off('click').click(function(e){

	    if(!(privateDisabled && $(this).hasClass("private"))){
		    e.preventDefault();
		    var message = ($(this).attr("title").length > 0) ? "Are you sure you want to " + $(this).attr("title") + "?" : "Are you sure?";
		    var name = $(this).attr('name');
		    var devInfo = eval("("+name+")");
		    //alert(devInfo.mac_addr);

		    if($(this).hasClass('XfinitySSID')){
				var devBlockInfo = '{"XfinitySSID": "true", "hostName": "'+devInfo.dev_name+'", "macAddr": "'+devInfo.mac_addr+'"}';
				var fileName = "wireless_network_configuration.php?mac_ssid=5";
		    }
		    else if($(this).hasClass('xhsSSID')){
				var devBlockInfo = '{"xhsSSID": "true", "hostName": "'+devInfo.dev_name+'", "macAddr": "'+devInfo.mac_addr+'"}';
				var fileName = "wireless_network_configuration.php?mac_ssid=3";
		    }
		    else{
			var devBlockInfo = '{"privateDevice": "true", "hostName": "'+devInfo.dev_name+'", "macAddr": "'+devInfo.mac_addr+'"}';	
			var fileName = "managed_devices.php";
		    }//end of else

		    jConfirm(
			message
			,"Are You Sure?"
			,function(ret) {
			    if(ret) {
			       jProgress('This may take several seconds', 60); 
			       $.ajax({                   
				   type: "POST",
				   url: "actionHandler/ajaxSet_addDevice_blockedList.php",
				   data: { BlockInfo: devBlockInfo },
				   success: function(){   
						window.location.href = fileName;
						jHide();        
				   },
				   error: function(){
						jHide();
						jAlert("Failure, please try again.");
				   }
			    	});
			    } //end of if ret    
		    }); //end of jConfirm
		}//end of if hasClass private
    }); //end of confirm click

	if(privateDisabled){
		$("#online-private, #offline-private").addClass("disabled");
		$(".add-Client").addClass("disabled");

		$("#online-private").find(".device-name").addClass("disabled");
		$("#online-private").find(".btn").addClass("disabled");

		$("#offline-private").find(".device-name").addClass("disabled");
		$("#offline-private").find(".btn").addClass("disabled");
	}

	$('.add-Client').click(function(e){
		if(!privateDisabled){
			if($(this).hasClass("lan")) window.location.href = "connected_devices_computers_add.php";
			else if($(this).hasClass("wifi")) window.location.href = "wireless_network_configuration_wps.php";
		}
	});
	$("#prefer_private").change(function(){
		var isChecked=$(this).is(":checked");
		jProgress('This may take several seconds.',60);
		$.ajax({
			type:"POST",
			url:"actionHandler/ajaxSet_wireless_network_configuration.php",
			data: { configInfo: '{"prefer_private": "true", "isChecked":"'+isChecked+'"}' },
			success:function(){
				jHide();
			},
			error:function(){
				jHide();
				jAlert("Error! Please try later!");
			}
		});
	});
});

</script>

<div id="content"  class="cnt-device-main">
	<h1>Connected Devices > Devices</h1>
     <div id="educational-tip">
				<p  class="tip">View information about devices currently connected to your network, as well as connection history.</p>
				<p  class="hidden">Every device listed below was auto discovered via DHCP.</p>
				<p  class="hidden"><strong>Online Devices</strong> are currently connected to your Gateway.</p>
				<p  class="hidden"><strong>Offline Devices</strong>  were once connected to your network, but not currently.</p>
				<p  class="hidden">To block Internet access to a device connected to your Gateway, click the <strong>X</strong> button. </p>
    </div>
<?php
	$checked = ($PreferPrivate == "true")?"checked":"";
	echo '<div class="module" id="prefer_private_connection">
		<div class="select-row">
	 		<label class="readonlyLabel label" for="prefer_private">Prefer Private Connection</label><input style="margin-top: 8px" type="checkbox" id="prefer_private" name="prefer_private" '.$checked.' >
		</div>
	</div>';
?>
	<div id='online-private' class="module data">
        <h2 id='online-table-cap'>Online Devices-Private Network</h2>
		<table   class="data"  summary="This table displays Online Devices connected to priviate network">
		    <tr>
		        <th id="host-name" >Host Name</th>
		        <th id="dhcp-or-reserved" >DHCP/Reserved IP</th>
		        <th id="rssi-level" >RSSI Level</th>
		        <th id="connection-type" >Connection</th>

		        <th id="edit-button">&nbsp;</th>
		        <th id="disconnect-button">&nbsp;</th>
		    </tr>

	<?php 
	$rootObjName    = "Device.Hosts.Host.";
	$paramNameArray = array("Device.Hosts.Host.");
	$mapping_array  = array("PhysAddress", "IPAddress", "Layer1Interface", "HostName", "Active", "AddressSource", "X_CISCO_COM_RSSI", "Comments", "IPv4Address.1.IPAddress", "IPv6Address.1.IPAddress", "IPv6Address.2.IPAddress","IPv6Address.3.IPAddress");

	$HostIndexArr = DmExtGetInstanceIds("Device.Hosts.Host.");
	if(0 == $HostIndexArr[0]){  
	    // status code 0 = success   
		$HostNum = count($HostIndexArr) - 1;
	}
	$onlinePrivateInstanceArr = array();
        $onlinePrivateHostNameArr = array();
        $onlineHostNameArr        = array();
        $onlineHostMAC            = array();
        $onlinePrivateNetworkHost['hostNum'] = 0;
        $offlinePrivateNetworkHost['hostNum'] = 0;
        $offlinePrivateInstanceArr = array();
        $offlineHostNameArr = array();
        $offlineHostMAC = array();
        $PublicNetworkHost['hostNum']  = 0;
	if(!empty($HostNum)){

		$Host = getParaValues($rootObjName, $paramNameArray, $mapping_array);

		if(!empty($Host)){
			
			//check if online device is there in Blocked Devices List using MAC Address
			//if it's there remove 'X' button for "Online Blocked Devices"
			$MD_rootObjName    = "Device.X_Comcast_com_ParentalControl.ManagedDevices.Device.";
			$MD_paramNameArray = array("Device.X_Comcast_com_ParentalControl.ManagedDevices.Device.");
			$MD_mapping_array  = array("Type", "MACAddress");

			$ManagedDevices = getParaValues($MD_rootObjName, $MD_paramNameArray, $MD_mapping_array);
			//update clients RSSI from Device.WiFi.AccessPoint.{i} instead of Device.Hosts.Host.
			$WiFi1_rootObjName    = "Device.WiFi.AccessPoint.1.AssociatedDevice.";
			$WiFi1_paramNameArray = array("Device.WiFi.AccessPoint.1.AssociatedDevice.");
			$WiFi1_mapping_array  = array("MACAddress", "SignalStrength");
			$AssoDeviceArr1 = getParaValues($WiFi1_rootObjName, $WiFi1_paramNameArray, $WiFi1_mapping_array);
			$WiFi2_rootObjName    = "Device.WiFi.AccessPoint.2.AssociatedDevice.";
			$WiFi2_paramNameArray = array("Device.WiFi.AccessPoint.2.AssociatedDevice.");
			$WiFi2_mapping_array  = array("MACAddress", "SignalStrength");
			$AssoDeviceArr2 = getParaValues($WiFi2_rootObjName, $WiFi2_paramNameArray, $WiFi2_mapping_array);
			$AssoDeviceArr = array();
			$AssoDeviceArr = array_merge($AssoDeviceArr1, $AssoDeviceArr2);
			$clients_RSSI = array();
			foreach ($AssoDeviceArr as $key => $value) {
				$clients_RSSI[strtoupper($value['MACAddress'])] = $value['SignalStrength'];
			}unset($value);
			$arrayBlockMAC=array();
			foreach ($ManagedDevices as $key => $value) {
				if($ManagedDevices[$key]['Type'] == "Block") {
					array_push($arrayBlockMAC, strtolower($ManagedDevices[$key]['MACAddress']));
				}
			}

		    //This for loop aims to construct online and offline network host arrays based on $Host		    
		    for ($i=0,$j=0,$k=0,$x=0; $i < $HostNum; $i++) { 

                $Host["$i"]['instanceID'] = $i + 1;
				$Host[$i]['HostName'] = htmlspecialchars($Host[$i]['HostName'], ENT_NOQUOTES, 'UTF-8');
                $Host[$i]['Comments'] = htmlspecialchars($Host[$i]['Comments'], ENT_NOQUOTES, 'UTF-8');
                $tmpHost = ProcessLay1Interface( $Host["$i"]['Layer1Interface']);

		    	if ($tmpHost['networkType'] == "Private"){
		      	//construct private network host info

		      	if ( !strcasecmp("true", $Host["$i"]['Active']) ) {
		      		//construct online private network host info

					$onlinePrivateNetworkHost['hostNum'] += 1;
			        $onlinePrivateNetworkHost["$j"]['instanceID'] = $i + 1;
			        array_push($onlinePrivateInstanceArr, $onlinePrivateNetworkHost["$j"]['instanceID']);

			        if (($Host[$i]['HostName'] == "*") || (strlen($Host[$i]['HostName']) == 0)) 
			        	$onlinePrivateNetworkHost["$j"]['HostName'] = strtoupper($Host["$i"]['PhysAddress']);
			        else
			        	$onlinePrivateNetworkHost["$j"]['HostName'] = $Host["$i"]['HostName'];
			        array_push($onlineHostNameArr, $onlinePrivateNetworkHost["$j"]['HostName']);

                    $onlinePrivateNetworkHost["$j"]['IPv4Address'] = $Host["$i"]['IPv4Address.1.IPAddress'];
                    $Host["$i"]['IPv6Address.2.IPAddress'] = isset($Host["$i"]['IPv6Address.2.IPAddress']) ? $Host["$i"]['IPv6Address.2.IPAddress'] : "";
                    $onlinePrivateNetworkHost["$j"]['IPv6Address1'] = $Host["$i"]['IPv6Address.2.IPAddress'];
		    $Host["$i"]['IPv6Address.1.IPAddress'] = isset($Host["$i"]['IPv6Address.1.IPAddress']) ? $Host["$i"]['IPv6Address.1.IPAddress'] : "";
                    $Host["$i"]['IPv6Address.3.IPAddress'] = isset($Host["$i"]['IPv6Address.3.IPAddress']) ? $Host["$i"]['IPv6Address.3.IPAddress'] : "";
                    $onlinePrivateNetworkHost["$j"]['IPv6Address2'] = resolve_IPV6_global_address($Host["$i"]['IPv6Address.1.IPAddress'],$Host["$i"]['IPv6Address.3.IPAddress']);

                    $onlinePrivateNetworkHost["$j"]['PhysAddress'] = strtoupper($Host["$i"]['PhysAddress']);
		            array_push($onlineHostMAC, $onlinePrivateNetworkHost["$j"]['PhysAddress']);

                    $onlinePrivateNetworkHost["$j"]['AddressSource'] = $Host["$i"]['AddressSource'];
                    $onlinePrivateNetworkHost["$j"]['Connection'] = $tmpHost['connectionType'];
                    $onlinePrivateNetworkHost["$j"]['Comments'] = $Host["$i"]['Comments'];


                    if (stristr($tmpHost['connectionType'], 'Wi-Fi')) {
                       $onlinePrivateNetworkHost[$j]['RSSI'] = $clients_RSSI[strtoupper($Host["$i"]['PhysAddress'])]." dBm";
                    }
                    else {
                       $onlinePrivateNetworkHost[$j]['RSSI'] = "NA";
                    }

					if(in_array(strtolower($onlinePrivateNetworkHost["$j"]['PhysAddress']), $arrayBlockMAC)){
						$onlinePrivateNetworkHost["$j"]['Blocked'] = true;
					} else {
						$onlinePrivateNetworkHost["$j"]['Blocked'] = false;
					}

                    $j++;
		      	}
		      	else {

		            $offlinePrivateNetworkHost['hostNum'] += 1;
			        $offlinePrivateNetworkHost["$k"]['instanceID'] = $i + 1;
                               array_push($offlinePrivateInstanceArr, $offlinePrivateNetworkHost["$k"]['instanceID']);

			        if (($Host[$i]['HostName'] == "*") || (strlen($Host[$i]['HostName']) == 0)) 
			        	$offlinePrivateNetworkHost["$k"]['HostName'] = strtoupper($Host["$i"]['PhysAddress']);
			        else
			        	$offlinePrivateNetworkHost["$k"]['HostName'] = $Host["$i"]['HostName'];
                              array_push($offlineHostNameArr, $offlinePrivateNetworkHost["$k"]['HostName']);

                    $offlinePrivateNetworkHost["$k"]['IPv4Address'] = $Host["$i"]['IPv4Address.1.IPAddress'];
		    $Host["$i"]['IPv6Address.2.IPAddress'] = isset($Host["$i"]['IPv6Address.2.IPAddress']) ? $Host["$i"]['IPv6Address.2.IPAddress'] : "";
		    $Host["$i"]['IPv6Address.1.IPAddress'] = isset($Host["$i"]['IPv6Address.1.IPAddress']) ? $Host["$i"]['IPv6Address.1.IPAddress'] : "";
                    $offlinePrivateNetworkHost["$k"]['IPv6Address1'] = $Host["$i"]['IPv6Address.2.IPAddress'];
                    $offlinePrivateNetworkHost["$k"]['IPv6Address2'] = $Host["$i"]['IPv6Address.1.IPAddress'];

                    $offlinePrivateNetworkHost["$k"]['PhysAddress'] = strtoupper($Host["$i"]['PhysAddress']);
                    array_push($offlineHostMAC, $offlinePrivateNetworkHost["$k"]['PhysAddress']);
                    $offlinePrivateNetworkHost["$k"]['Connection'] = $tmpHost['connectionType'];
                    $offlinePrivateNetworkHost["$k"]['AddressSource'] = $Host["$i"]['AddressSource'];
                    $offlinePrivateNetworkHost["$k"]['Comments'] = $Host["$i"]['Comments'];

					if(in_array(strtolower($offlinePrivateNetworkHost["$k"]['PhysAddress']), $arrayBlockMAC)){
						$offlinePrivateNetworkHost["$k"]['Blocked'] = true;
					} else {
						$offlinePrivateNetworkHost["$k"]['Blocked'] = false;
					}

                    $k++;
		      	}	
		      }		     
		    }//end of for
		}//end of if empty host
	}//end of if empty hostNums

    if ("" == $onlinePrivateNetworkHost['hostNum']) $onlinePrivateNetworkHost['hostNum']=0;

	echo "<script type=\"text/javascript\">
        var onlineDeviceNum = ", $onlinePrivateNetworkHost['hostNum'] , "; 
        var onlineDeviceInstanceArr = ", json_encode($onlinePrivateInstanceArr) , ";
        var onlineHostNameArr = ", json_encode($onlineHostNameArr) ,";
		var onlineHostMAC = ", json_encode($onlineHostMAC) ,";
	</script>";
    if ("" == $offlinePrivateNetworkHost['hostNum']) $offlinePrivateNetworkHost['hostNum']=0;
        echo "<script type=\"text/javascript\">
        var offlineDeviceNum = ", $offlinePrivateNetworkHost['hostNum'] , ";
        var offlineDeviceInstanceArr = ", json_encode($offlinePrivateInstanceArr) , ";
        var offlineHostNameArr = ", json_encode($offlineHostNameArr) ,";
        var offlineHostMAC = ", json_encode($offlineHostMAC) ,";
        </script>";
	?>	
     
    <?php 
    	for($x=0,$k=1; $x<$onlinePrivateNetworkHost['hostNum']; $x++,$k++)
        {
		$commentsOnline1= $onlinePrivateNetworkHost["$x"]['Comments'];
		$commentsOnline1=trim($commentsOnline1);
		if(preg_match_all("/@/",$commentsOnline1)){
  		        $commentsOnline1=preg_replace("/@/","<br/>",$commentsOnline1);
 		} 
         	$dev_name = $onlinePrivateNetworkHost["$x"]['HostName'];
         	$mac_addr = $onlinePrivateNetworkHost["$x"]['PhysAddress'];
         	$AddrSrc  = $onlinePrivateNetworkHost["$x"]['AddressSource'];

		if($onlinePrivateNetworkHost["$x"]['Blocked']) $style = "&nbsp;";
		else $style = "<input type='button' id=" . "'online-X-" .$k. "'" . " value='X' tabindex='0' title=\"add this device to Blocked Devices List \" name='{\"dev_name\":\"$dev_name\", \"mac_addr\":\"$mac_addr\"}'  class=\"btn confirm private\"></input>";

         	if($k % 2)  $odd = "";
				else $odd = " class='odd'";
         	echo "
		    <tr $odd>
		        <td headers='host-name'><a href='javascript:void(0)' tabindex='0' class=\"label device-name private\"><u>" , $onlinePrivateNetworkHost["$x"]['HostName'] , "</u></a>

					<div class=\"device-info\">
						<dl><dd><br/></dd>";

						if ($onlinePrivateNetworkHost["$x"]['IPv4Address'] != '') {echo "<dd><b>IPV4 Address</b><br/>", $onlinePrivateNetworkHost["$x"]['IPv4Address'] , "</dd>";}
						if ($onlinePrivateNetworkHost["$x"]['IPv6Address2'] != '') {echo "<dd><b>IPV6 Address</b><br/>", $onlinePrivateNetworkHost["$x"]['IPv6Address2'] , "</dd>";}
						if ($onlinePrivateNetworkHost["$x"]['IPv6Address1'] != '') {echo "<dd><b>Local Link IPV6 Address</b><br/>", $onlinePrivateNetworkHost["$x"]['IPv6Address1'] , "</dd>";}

						echo    "<dd><b>MAC Address</b><br/>", $onlinePrivateNetworkHost["$x"]['PhysAddress'] , "</dd>
							<dd><b>Comments</b><br/>", $commentsOnline1 , "</dd>
						</dl>
					</div>
                </td>

		        <td headers='dhcp-or-reserved'>", (($AddrSrc == "DHCP") ? "DHCP" : "Reserved IP") ,"</td>
		        <td headers='rssi-level'>", $onlinePrivateNetworkHost["$x"]['RSSI'] ,"</td>
		        <td headers='connection-type'>", $onlinePrivateNetworkHost["$x"]['Connection'] , "</td>

                <td headers='edit-button'><input type='button' value='Edit' tabindex='0' id=" , "'btn-" ,$onlinePrivateNetworkHost["$x"]['instanceID'] , "'", "  class=\"btn private\"></input></td>
                <td headers='disconnect-button'>$style</td>
		    </tr>    
		    ";
		}
    ?>

     	<tfoot>
			<tr class="acs-hide">
				<td headers="host-name">null</td>
				<td headers="dhcp-or-reserved">null</td>
				<td headers="rssi-level">null</td>
				<td headers="connection-type">null</td>
				<td headers="edit-button">null</td>
				<td headers="disconnect-button">null</td>
			</tr>
		</tfoot>

		</table>
		<div  class="btn-group">
			<a href="javascript:void(0)" class="btn add-Client lan">Add Device with Reserved IP</a>
		</div>
	</div> <!-- end .module -->

	<div id='offline-private' class="module forms data">
    <h2 id='offline-table-cap'>Offline Devices-Private Network</h2>
		<table   class="data" summary="this table display off line devices for private network">
		    <tr>
		        <th id="offline-device-host-name">Host Name</th>
		        <th id="offline-device-dhcp-reserve">DHCP/Reserved IP</th>
		        <th id="offline-device-conncection">Connection</th>
                        <th id="offline-edit-button">&nbsp;</th>
		        <th id="offline-device-disconnect-button">&nbsp;</th>
		    </tr>

    <?php 

    for($x=0,$k=1; $x<$offlinePrivateNetworkHost['hostNum']; $x++,$k++)
    { 
	$commentsOffline1=  $offlinePrivateNetworkHost["$x"]['Comments'];
	$commentsOffline1=trim($commentsOffline1);
	if(preg_match_all("/@/",$commentsOffline1)){
          	$commentsOffline1=preg_replace("/@/","<br/>",$commentsOffline1);
 	}
    	$dev_name = $offlinePrivateNetworkHost["$x"]['HostName'];
    	$mac_addr = $offlinePrivateNetworkHost["$x"]['PhysAddress'];
    	$AddrSrc  = $offlinePrivateNetworkHost["$x"]['AddressSource'];

		if($offlinePrivateNetworkHost["$x"]['Blocked']) $style = "&nbsp;";
		else $style = "<input type='button' id=" . "'offline-X-" .$k. "'" . " value='X' tabindex='0' title=\"remove computer named $dev_name\" name='{\"dev_name\":\"$dev_name\", \"mac_addr\":\"$mac_addr\"}'  class=\"btn confirm private\"></input>";

    	if($k % 2) $odd = "";
    	else $odd = " class='odd'";
    	echo "
		    <tr $odd>
	        <td headers='offline-device-host-name'><a href='javascript:void(0)' tabindex='0' class=\"label device-name private\"><u>" , $offlinePrivateNetworkHost["$x"]['HostName'] , "</u></a>

				<div class=\"device-info\">
					<dl><dd><br/></dd>";

						if ($offlinePrivateNetworkHost["$x"]['IPv4Address'] != '') {echo "<dd><b>IPV4 Address</b><br/>", $offlinePrivateNetworkHost["$x"]['IPv4Address'] , "</dd>";}
						if ($offlinePrivateNetworkHost["$x"]['IPv6Address2'] != '') {echo "<dd><b>IPV6 Address</b><br/>", $offlinePrivateNetworkHost["$x"]['IPv6Address2'] , "</dd>";}
						if ($offlinePrivateNetworkHost["$x"]['IPv6Address1'] != '') {echo "<dd><b>Local Link IPV6 Address</b><br/>", $offlinePrivateNetworkHost["$x"]['IPv6Address1'] , "</dd>";}

						echo    "<dd><b>MAC Address</b><br/>", $offlinePrivateNetworkHost["$x"]['PhysAddress'] , "</dd>
						<dd><b>Comments</b><br/>", $commentsOffline1 , "</dd>
					</dl>
				</div>
            </td>

	        <td headers='offline-device-dhcp-reserve'>", (($AddrSrc == "DHCP") ? "DHCP" : "Reserved IP") ,"</td>
	        <td headers='offline-device-conncection'>",  $offlinePrivateNetworkHost["$x"]['Connection'] , "</td>
              <td headers='offline-edit-button'><input type='button' value='edit' tabindex='0' id=" , "'btn-" ,$offlinePrivateNetworkHost["$x"]['instanceID'] , "'", "  class=\"btn private\"></input></td>

            <td headers='offline-device-disconnect-button'>$style</td>
		    </tr>    
		";
	}

    ?>

     	<tfoot>
			<tr class="acs-hide">
				<td headers="offline-device-host-name">null</td>
				<td headers="offline-device-dhcp-reserve">null</td>
				<td headers="offline-device-conncection">null</td>
                                <td headers="offline-edit-button">null</td>
				<td headers="offline-device-disconnect-button">null</td>
			</tr>
		</tfoot>

		</table>
	</div> <!-- end .module -->
	<?php 
                if($encrypt_mode!="None" && $encrypt_mode1!="None" && ($modelName != "CGA4332COM") ){
	?>
	<div  class="form-btn">
		<a tabindex='0' href="javascript:void(0)"  class="btn add-Client wifi" >Add Wi-Fi Protected Setup (WPS) Client</a>
	</div>
		<!--Home Security part-->
	<?php
		}
		if($loginuser=="mso"){
		/*
	    ** Hotspot feature ----XfinitySSID part
	    */
                 function get_hotspot_clients($idx){
                        $Hotspot_SSID_clients = array();
			$rootObjName	= "Device.X_COMCAST-COM_GRE.Tunnel.1.SSID.$idx.AssociatedDevice.";
			$paramNameArray	= array($rootObjName);
			$mapping_array	= array("MACAddress", "Hostname", "RSSILevel", "IPv4Address", "DHCPv4Status", "IPv6Address", "IPv6Prefix", "DHCPv6Status", "IPv6LinkLocalAddress");
			$Hotspot_SSID_idAr = DmExtGetInstanceIds($rootObjName);
			if(0 == $Hotspot_SSID_idAr[0]){
			   // status code 0 = success
			   $Hotspot_SSID_clients = getParaValues($rootObjName, $paramNameArray, $mapping_array);
	                }
			//$Hotspot_SSID_clientsNum = getStr("Device.X_COMCAST-COM_GRE.Tunnel.1.SSID.1.AssociatedDeviceNumberOfEntries");
			return $Hotspot_SSID_clients;
		}
    echo '<div  class="module forms data div-pub-network" style="position:relative; top:10px; ">';
      	echo '<h2>Online Devices-xfinitywifi SSID</h2>';
      	echo '<table   class="data" summary="this table displays online devices connected to xfinitywifi SSID">';
      		echo '<tr>';
      			echo '<th id="xfinitywifi-host-name" width="30%">Host Name</th>';
      			echo '<th id="xfinitywifi-ipv4-address" width="30%">IPV4 Address</th>';
      			echo '<th id="xfinitywifi-rssi-level" width="20%">RSSI Level</th>';
      			echo '<th id="xfinitywifi-mac-address" width="20%">MAC Address</th>';
      			echo '<th id="xfinitywifi-disconnect-button" width="20%"></th>';
      		echo '</tr>';
		
		$Hotspot_SSIDNumberOfEntries = getStr("Device.X_COMCAST-COM_GRE.Tunnel.1.SSIDNumberOfEntries");
		$macEntry="";
	for ($j=0; $j < $Hotspot_SSIDNumberOfEntries; $j++) {
                 $Hotspot_SSID_clients = get_hotspot_clients($j+1);
      		for ($i=0; $i < count($Hotspot_SSID_clients); $i++) { 
	      		$Hostname      = htmlspecialchars($Hotspot_SSID_clients[$i]['Hostname'], ENT_NOQUOTES, 'UTF-8');
	      		$MACAddress    = $Hotspot_SSID_clients[$i]['MACAddress'];
	      		$RSSILevel     = $Hotspot_SSID_clients[$i]['RSSILevel'];
	      		$IPv4Address   = $Hotspot_SSID_clients[$i]['IPv4Address'];
	      		$DHCPv4Status  = $Hotspot_SSID_clients[$i]['DHCPv4Status'];
	      		$IPv6Address   = $Hotspot_SSID_clients[$i]['IPv6Address'];
	      		$IPv6Prefix    = $Hotspot_SSID_clients[$i]['IPv6Prefix'];
	      		$DHCPv6Status  = $Hotspot_SSID_clients[$i]['DHCPv6Status'];
	      		$IPv6LocalAddr = $Hotspot_SSID_clients[$i]['IPv6LinkLocalAddress'];
			$instanceIds= getInstanceIds("Device.WiFi.AccessPoint.6.X_CISCO_COM_MacFilterTable.");
			$macfilterexplode= explode(",",$instanceIds);
			for($n=0;$n<count($macfilterexplode);$n++){
				$id= $macfilterexplode[$n];
				$macfilterAddress= getStr("Device.WiFi.AccessPoint.6.X_CISCO_COM_MacFilterTable."+$id+".MACAddress");
				if(strtolower($MACAddress) == strtolower($macfilterAddress)){
					$macEntry="1";
					break;
				}
			}			
	      		if ($j == 0 || $j == 2) {
	      			$gre_ssid = 1;
                                $frequency_band = getStr("Device.WiFi.Radio.1.OperatingFrequencyBand");
                                $frequency_band = (strstr($frequency_band,"5G")) ? "5G" : "2.4G";
                                $WiFiType = "Wi-Fi $frequency_band";
			}
	      		else {
	      			$gre_ssid = 2;
                                $frequency_band = getStr("Device.WiFi.Radio.2.OperatingFrequencyBand");
                                $frequency_band = (strstr($frequency_band,"5G")) ? "5G" : "2.4G";
                                $WiFiType = "Wi-Fi $frequency_band";

	      		}

	         	if($i % 2) $odd = "";
					else $odd = " class='odd'";

         		echo '<tr' .$odd. '>';
         		echo '<td headers="xfinitywifi-host-name"><a href="javascript:void(0)" tabindex="0" class="label device-name"><u>'. $Hostname .'</u></a>';
				echo '<div class="device-info">';	
					echo '<dl><dd><br/></dd>';
					echo '<dd><b>IPv6 Prefix</b><br/>'. $IPv6Prefix. '</dd>';
					echo '<dd><b>IPv6 Address</b><br/>'. $IPv6Address. '</dd>';
					echo '<dd><b>Local link IPv6 Address</b><br/>'. $IPv6LocalAddr. '</dd>';
					echo '<dd><b>DHCPv4 Status</b><br/>'. $DHCPv4Status. '</dd>';
					echo '<dd><b>DHCPv6 Status</b><br/>'. $DHCPv6Status. '</dd>';
					echo '<dd><b>Connection</b><br/>'. $WiFiType . '</dd>';
					echo '</dl>';	
				echo '</div>';	
                echo '</td>';
                echo '<td headers="xfinitywifi-ipv4-address">'. $IPv4Address;
                echo '<td headers="xfinitywifi-rssi-level">'. $RSSILevel." dBm";
                echo '<td headers="xfinitywifi-mac-address">'. $MACAddress;
		if($macEntry!="1")
                	echo "<td headers=\"xfinitywifi-disconnect-button\"><input type='button' id=" . "'hotspot-X-" .$i. "'" . "  value='X' tabindex='0' name=\"{'gre_ssid':'$gre_ssid','dev_name':'$Hostname','mac_addr':'$MACAddress'}\" title='disconnect and deny Wi-Fi access to this device'  class='XfinitySSID btn confirm'></input></td>";
		    	echo '</tr>';
      		}//end of for;
	}

	
		echo '<tfoot>';
				echo '<tr class="acs-hide">';
					echo '<td headers="xfinitywifi-host-name">null</td>';
					echo '<td headers="xfinitywifi-ipv4-address">null</td>';
					echo '<td headers="xfinitywifi-rssi-level">null</td>';
					echo '<td headers="xfinitywifi-mac-address">null</td>';
					echo '<td headers="xfinitywifi-disconnect-button">null</td>';
				echo '</tr>';
			echo '</tfoot>';

   		echo '</table>';
   	echo '</div> <!-- end .module -->';
		}
      	?>

</div><!-- end #content -->


<?php 

//this part is to populate edit device info on each online private network host basis 
for ($i=0; $i < $onlinePrivateNetworkHost['hostNum']; $i++) { 
    $commentsOnline2= $onlinePrivateNetworkHost["$i"]['Comments'];
    $commentsOnline2=trim($commentsOnline2);	
    if(preg_match_all("/@/",$commentsOnline2)){
          $commentsOnline2=preg_replace("/@/","\n",$commentsOnline2);
 }	
    if(!isset($onlinePrivateNetworkHost["$i"]['IPv4Address']))
    {
        $onlinePrivateNetworkHost["$i"]['IPv4Address'] = "";
    }
	$ID      = $onlinePrivateNetworkHost["$i"]['instanceID'];
	$AddrSrc = $onlinePrivateNetworkHost["$i"]['AddressSource'];

	echo "
        <div id=\"edit-device-" .$ID. "\"  class=\"edit-device content \" style='display:none'>
		    <h1>Connected Devices > Devices > Edit Device</h1>
		    <div  class=\"educational-tip-edit\">
				<p  class=\"tip\">Change the IP address assignment method for Online Devices.</p>
				<p  class=\"hidden\">If DHCP is selected, the Gateway's DHCP server will automatically assign the IP address.</p>
				<p  class=\"hidden\">If Reserved IP is selected, the IP address will be fixed without DHCP operation and you'll need to manually enter the IP address. The IP address must be within the DHCP IP address pool. To find your IP address range, go to <strong>Gateway > Connection > Local IP Network.</strong></p>
				<p  class=\"hidden\">Reserved IP addresses can be assigned to any device that acts as a server or that requires a fixed IP address.</p> 
			</div>
		<div  class=\"module forms\" id=\"computers-edit-" .$ID. "\" >
		<h2>Edit Device</h2> 
        <form id=\"pageForm-" .$ID. "\"   class=\"pageForm\">

			<div  class=\"form-row\">
        		<span  class=\"readonlyLabel\" name=\"host_name\">Host Name:</span>
        		<span  class=\"value\">" . $onlinePrivateNetworkHost["$i"]['HostName'] . "</span>
			</div>
			<div  class=\"form-row odd\">
			    <span  class=\"readonlyLabel\">Connection:</span>
        		<span  class=\"value\">" . $onlinePrivateNetworkHost["$i"]['Connection'] . "</span>
			</div>
			<div  class=\"form-row\">
				<label for=\"ip\" style='margin:4px 5px 0 0;'>Configuration:</label>
				<input type=\"radio\" name=\"ip\" value=\"" .$AddrSrc. "\" " .( ($AddrSrc == "DHCP") ? "checked='checked'" : '' ). " class=\"ip_dchp\" id=\"ip_dhcp-" .$ID. "\" />
				<label  class=\"radio\" for=\"ip_dhcp-" .$ID. "\">DHCP</label>
				<br/>

				<input  class=\"trigger ip_static\" type=\"radio\"" .(($AddrSrc == "DHCP") ? '' : "checked='checked'"  ). " name=\"ip\" value=\"static\" id=\"ip_static-" .$ID. "\" />
				<label  class=\"radio\" for=\"ip_static-" .$ID. "\">Reserved IP</label>
			</div>
			<div id=\"dhcp-mac" .$ID. "\"  class=\"dhcp-mac form-row odd\">
			    	<span  class=\"readonlyLabel\">MAC Address:</span>
        			<span  class=\"value\">".$onlinePrivateNetworkHost["$i"]['PhysAddress']."</span>
			</div>

      		<div id=\"static-ip" .$ID. "\"  class=\"static-ip form-row odd\" >
				<label for=\"staticIPAddress-" .$ID. "\">Reserved IP Address:</label>
				<input type=\"text\" value=\"". $onlinePrivateNetworkHost["$i"]['IPv4Address'] ."\" id=\"staticIPAddress-" .$ID. "\" name=\"staticIPAddress\"  class=\"target\" />
			</div>
			<div  class=\"form-row\">
				<label for=\"comment-" .$ID. "\" >Comments:</label>
		        <textarea id=\"comment-" .$ID. "\" name=\"comments\" ros=\"6\" cols=\"18\" maxlength=\"63\">". $commentsOnline2 ."</textarea>
			</div>

			<div  class=\"form-row form-btn\">
				<input type=\"button\" id=\"submit_editDevice-" .$ID. "\"  class=\"btn\" value=\"Save\"/>
			    <input type=\"reset\" id=\"btn-cancel-" .$ID. "\" class=\"btn-cancel btn alt reset\" value=\"Cancel\"/>
			</div>
		</form>

	</div> <!-- end .module -->
</div><!-- end #content -->

    ";
}
//this part is to populate edit device info on each offline private network host basis
for ($i=0; $i < $offlinePrivateNetworkHost['hostNum']; $i++) {
	$commentOffline2=$offlinePrivateNetworkHost["$i"]["Comments"];
	$commentOffline2=trim($commentOffline2);
	if(preg_match_all("/@/",$commentOffline2)){
          	$commentOffline2=preg_replace("/@/","\n",$commentOffline2);
	 }
        if(!isset($offlinePrivateNetworkHost["$i"]['IPv4Address'] ))
        {
            $offlinePrivateNetworkHost["$i"][''] = "IPv4Address";
        }
        $ID      = $offlinePrivateNetworkHost["$i"]['instanceID'];
        $AddrSrc = $offlinePrivateNetworkHost["$i"]['AddressSource'];
        echo "
        <div id=\"offline-edit-device-" .$ID. "\"  class=\"edit-device content \" style='display:none'>
                    <h1>"._('Connected Devices > Devices > Edit Device')."</h1>
                    <div  class=\"educational-tip-edit\">
                                <p  class=\"tip\">"._('Change the IP address assignment method for Offline Devices.')."</p>
                                <p  class=\"hidden\">"._('If DHCP is selected, the Gateway\'s DHCP server will automatically assign the IP address.')."</p>
                                <p  class=\"hidden\">"._('If Reserved IP is selected, the IP address will be fixed without DHCP operation and you\'ll need to manually enter the IP address. The IP address must be within the DHCP IP address pool. To find your IP address range, go to <strong>Gateway > Connection > Local IP Network.</strong>')."</p>
                                <p  class=\"hidden\">"._('Reserved IP addresses can be assigned to any device that acts as a server or that requires a fixed IP address.')."</p>
                        </div>
                <div  class=\"module forms\" id=\"computers-edit-" .$ID. "\" >
                <h2>"._('Edit Device')."</h2>
        <form id=\"pageForm-" .$ID. "\"   class=\"pageForm\">
                        <div  class=\"form-row\">
                        <span  class=\"readonlyLabel\" name=\"host_name\">"._('Host Name:')."</span>
                        <span  class=\"value\">" . $offlinePrivateNetworkHost["$i"]['HostName'] . "</span>
                        </div>
                        <div  class=\"form-row odd\">
                            <span  class=\"readonlyLabel\">"._('Connection:')."</span>
                        <span  class=\"value\">" . $offlinePrivateNetworkHost["$i"]['Connection'] . "</span>
                        </div>
                        <div  class=\"form-row\">
                                <label for=\"ip\" style='margin:4px 5px 0 0;'>"._('Configuration:')."</label>
                                <input type=\"radio\" name=\"ip\" value=\"" .$AddrSrc. "\" " .( ($AddrSrc == "DHCP") ? "checked='checked'" : '' ). " class=\"ip_dchp\" id=\"ip_dhcp-" .$ID. "\" />
                                <label  class=\"radio\" for=\"ip_dhcp-" .$ID. "\">DHCP</label>
                                <br/>
                                <input  class=\"trigger ip_static\" type=\"radio\"" .(($AddrSrc == "DHCP") ? '' : "checked='checked'"  ). " name=\"ip\" value=\"static\" id=\"ip_static-" .$ID. "\" />
                                <label  class=\"radio\" for=\"ip_static-" .$ID. "\">"._("Reserved IP")."</label>
                        </div>
                        <div id=\"dhcp-mac" .$ID. "\"  class=\"dhcp-mac form-row odd\">
                                <span  class=\"readonlyLabel\">"._('MAC Address:')."</span>
                                <span  class=\"value\">".$offlinePrivateNetworkHost["$i"]['PhysAddress']."</span>
                        </div>
                <div id=\"static-ip" .$ID. "\"  class=\"static-ip form-row odd\" >
                                <label for=\"staticIPAddress-" .$ID. "\">"._('Reserved IP Address:')."</label>
                                <input type=\"text\" value=\"". $offlinePrivateNetworkHost["$i"]['IPv4Address'] ."\" id=\"staticIPAddress-" .$ID. "\" name=\"staticIPAddress\"  class=\"target\" />
                        </div>
                        <div  class=\"form-row\">
                                <label for=\"comment-" .$ID. "\" >"._('Comments:')."</label>
                        <textarea id=\"comment-" .$ID. "\" name=\"comments\" ros=\"6\" cols=\"18\" maxlength=\"63\">". $commentOffline2 ."</textarea>
                        </div>
                        <div  class=\"form-row form-btn\">
                                <input type=\"button\" id=\"submit_editDevice-" .$ID. "\"  class=\"btn\" value=\""._("Save")."\"/>
                            <input type=\"reset\" id=\"btn-cancel-" .$ID. "\" class=\"btn-cancel btn alt reset\" value=\""._("Cancel")."\"/>
                        </div>
                </form>
        </div> <!-- end .module -->
</div><!-- end #content -->
    ";
}


?>


<?php include('includes/footer.php'); ?>

