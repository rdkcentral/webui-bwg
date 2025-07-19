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
<?php
	$ft = array();
	$id = explode(",", getInstanceIds("Device.Routing.Router.1.IPv4Forwarding."));
	foreach ($id as $i){
		if ("true" == getStr("Device.Routing.Router.1.IPv4Forwarding.$i.StaticRoute"))
		{		
			array_push($ft, array(
				$i,
				getStr("Device.Routing.Router.1.IPv4Forwarding.$i.Alias"),
				getStr("Device.Routing.Router.1.IPv4Forwarding.$i.DestIPAddress"),
				getStr("Device.Routing.Router.1.IPv4Forwarding.$i.DestSubnetMask"),
				getStr("Device.Routing.Router.1.IPv4Forwarding.$i.GatewayIPAddress"),
				getStr("Device.Routing.Router.1.IPv4Forwarding.$i.Enable")			
			));
		}	
	}
	/*if ($_DEBUG) {
		$ft = array(
			array("1", "route1", "1.1.1.1", "255.255.255.0", "1.1.1.2", "true"),
			array("2", "route2", "2.2.2.2", "255.255.255.0", "2.2.2.1", "true")
		);
	}*/
	$arConfig = array('ft'=>$ft);			
	$jsConfig = json_encode($arConfig);
?>

<script type="text/javascript">
$(document).ready(function() {
    comcast.page.init("Advanced > Static Routing", "nav-staticrouting");
	
	var obj	= <?php echo $jsConfig;?>; 
	var dat	= obj.ft;

	for (var i=0; i<dat.length; i++)
	{
		$("#static_routing_table > tbody").append('\
			<tr name="'+ dat[i][0] +'" class="'+ (i%2 ? "odd" : "") +'">\
				<td headers="static-Name" class="fix_length_text" title="'+dat[i][1]+'">'+ dat[i][1] +'</td>\
				<td headers="static-Destination">'+ dat[i][2] +'</td>\
				<td headers="static-Subnet">'+ dat[i][3] +'</td>\
				<td headers="static-Gateway">'+ dat[i][4] +'</td>\
				<td headers="static-Active"><input name="active" type="checkbox" '+("true"==dat[i][5]?'checked="checked"':'')+' id="act_'+i+'"/><label class="acs-hide" for="act_'+i+'"></label></td>\
				<td headers="static-Blank"><input type="button" name="delete" class="btn" value="X" id="del_'+i+'"/></td>\
			</tr>');
	}
	
    $("#pageForm").validate({
		errorPlacement: function(error, element){
			error.appendTo(element.parent("td"));
		},
		errorElement: "div",
		rules: {
			route_name: {
				required: true
			},
			route_destination_ip: {
				required: true,
				ipv4: true
			},
			route_subnet_mask: {
				required: true,
				ipv4: true
			},
			route_gateway_ip: {
				required: true,
				ipv4: true
			}			
		}
    });	
	
	$('[name="add"],[name="active"],[name="delete"]').click(function(){
		var theObj	= $(this);
		var idex	= theObj.parents("tr").attr("name");
		var target	= theObj.attr("name");
		var active	= theObj.prop("checked");
		
		if ("add"==target && !$("#pageForm").valid()){
			return;
		}
		
		jConfirm(
			'Are you sure you want to '+("active"==target && !theObj.prop("checked")?"inactive":target)+' this item?',
			'Are You Sure?',
			function(ret){
				if(ret){
					jProgress('This may take several seconds...', 60);
					$.ajax({
						type:"POST",
						url:"actionHandler/ajaxSet_staticrouting.php",
						data:{
							idex:	idex, 
							target:	target,
							active:	active,
							Alias:				$("#route_name").val(),
							DestIPAddress:		$("#route_destination_ip").val(),
							DestSubnetMask:		$("#route_subnet_mask").val(),
							GatewayIPAddress:	$("#route_gateway_ip").val()
						},
						success:function(data){
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
						error:function(){
							jHide();
							jAlert("Something wrong, please try later!");
							location.reload();
						}
					});
				}
				else{
					if ("active"==target){
						theObj.prop("checked", !theObj.prop("checked"));
					}					
				}
			}
		);
	});
});
</script>

<div id="content">
	<h1>Advanced > Static Routing </h1>
	<div id="educational-tip">
		<p class="tip">Manage your Static Routing settings.</p>
		<p class="hidden">Static Routes allow the users to manually add static routes to create specific paths to the destined networks.</p>
	</div>
	<form id="pageForm">
	<div class="module data data">
		<h2>Static Routing</h2>
		<div class="form-row">
			<p><strong>Static Routing Entry</strong></p>
			<table cellpadding="0" cellspacing="0" class="fixed-medium" id="add_static_route">
			<tr >
				<td class="label-text"><label for="route_name" class="bold">Name</label></td>
				<td class="input"><input type="text" id="route_name" name="route_name" class="input-big" value=""/></td>
			</tr>
			<tr>
				<td class="label-text"><label for="route_destination_ip" class="bold">Destination Subnet</label></td>
				<td class="input"><input type="text" id="route_destination_ip" name="route_destination_ip" class="input-big" value="" /></td>
			</tr>
			<tr>
				<td class="label-text"><label for="route_subnet_mask" class="bold">Subnet Mask</label></td>
				<td class="input"><input type="text" id="route_subnet_mask" name="route_subnet_mask" class="input-big" value=""/></td>
			</tr>
			<tr>
				<td class="label-text"><label for="route_gateway_ip" class="bold">Gateway IP</label></td>
				<td class="input"><input type="text" id="route_gateway_ip" name="route_gateway_ip" class="input-big" value=""/></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td><input name="add" type="button" value="ADD" class="btn" size="5"/></td>
			</tr>
			</table>	

			<p><strong>Static Routing Table</strong></p>
			<table class="data" id="static_routing_table" cellpadding="0" cellspacing="0" summary="This table is to show static routing sheet">
				<thead>
					<tr>
						<th id="static-Name" class="left">Name</th>
						<th id="static-Destination">Destination Subnet</th>
						<th id="static-Subnet">Subnet Mask</th>
						<th id="static-Gateway">Gateway IP</th>
						<th id="static-Active">Active</th>
						<th id="static-Blank">&nbsp;</th>
					</tr>
				</thead>
				<tbody>
					<!--tr>
						<td >Comcast</td>
						<td>111.19.24.0</td>
						<td>255.255.255.0</td>
						<td>10.1.19.24</td>
						<td><input class="active" type="checkbox" /></td>
						<td><a class="btn delete">X</a></td>
					</tr-->
				</tbody>
			<tfoot>
				<tr class="acs-hide">
					<td headers="static-Name">null</td>
					<td headers="static-Destination">null</td>
					<td headers="static-Subnet">null</td>
					<td headers="static-Gateway">null</td>
					<td headers="static-Active">null</td>
					<td headers="static-Blank">null</td>
				</tr>
			</tfoot>
			</table>
		</div>
	</div>
	</form>
</div><!-- end #content -->

<?php include('includes/footer.php'); ?>
