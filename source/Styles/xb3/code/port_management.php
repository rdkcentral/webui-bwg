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
/* construct the rule table for output */
$baseObjName	= "Device.X_CISCO_COM_TrueStaticIP.PortManagement.";
$rootObjName    = "Device.X_CISCO_COM_TrueStaticIP.PortManagement.Rule.";
$paramNameArray = array("Device.X_CISCO_COM_TrueStaticIP.PortManagement.Rule.");
$mapping_array  = array("Enable", "Name", "Protocol", "IPRangeMin", "IPRangeMax", "PortRangeMin", "PortRangeMax");
$pmArray = getParaValues($rootObjName, $paramNameArray, $mapping_array, true);

$pmEnabled = getStr($baseObjName.'Enable') === 'true';
$ruleType = getStr($baseObjName.'RuleType');
/*if ($_DEBUG) {
	$pmEnabled = true;
	$ruleType = 'White';
	$pmArray = array(
		array('__id' => '2', 'Name' => 'app 1', 'PortRangeMin' => '11', 'PortRangeMax' => '12', 'IPRangeMin' => '111.111.111.111', 'IPRangeMax' => '111.111.111.222', 'Protocol' => 'TCP', 'Enable' => 'true'),
		array('__id' => '3', 'Name' => 'app 2', 'PortRangeMin' => '550', 'PortRangeMax' => '552', 'IPRangeMin' => '111.111.111.1', 'IPRangeMax' => '111.111.111.1', 'Protocol' => 'UDP', 'Enable' => 'false'),
		array('__id' => '4', 'Name' => 'app 3', 'PortRangeMin' => '1000', 'PortRangeMax' => '1001', 'IPRangeMin' => '111.111.111.5', 'IPRangeMax' => '111.111.111.5', 'Protocol' => 'BOTH', 'Enable' => 'true')
	);
}*/

?>
<style>

	table td {
		white-space: pre;
	}
</style>
<script type="text/javascript">
var o_pmEnabled = <?php echo $pmEnabled ? 'true' : 'false'; ?>;
var o_ruleType = "<?php echo $ruleType; ?>";
var o_ruleArray = <?php echo isset($pmArray) ? json_encode($pmArray) : '[]';?>;

function ondelete() {
	var $this = $(this);
	var id = $this.closest('tr').attr('eid');
	var postData = {};

	postData.op = 'del';
	postData.id = id;

	jProgress('This may take several seconds', 60);
	$.ajax({
		type: 'POST',
		url: 'actionHandler/ajax_port_management.php',
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

function onenable(isEnabling) {
	var $this = $(this);
	var id = $this.closest('tr').attr('eid');
	var postData = {};

	postData.op = isEnabling ? 'enable' : 'disable';
	postData.id = id;

	jProgress('This may take several seconds', 60);
	$.ajax({
		type: 'POST',
		url: 'actionHandler/ajax_port_management.php',
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
			/* restore the previous state */
			jHide();
			$this.prop("checked", !isEnabling);
			jAlert("Failure, please try again.");
		}
	});
}

function ondisableAll(isDisabling) {
	var $this = $(this);
	var postData = {};

	postData.op = 'disableAllPm';
	postData.isDisabling = isDisabling;

	jProgress('This may take several seconds', 60);
	$.ajax({
		type: 'POST',
		url: 'actionHandler/ajax_port_management.php',
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
				$this.prop("checked", !isDisabling);

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
			$this.prop("checked", !isDisabling);
			jAlert("Failure, please try again.");
		}
	});
}

function onchangeRuleType(ruleType) {
	var $this = $(this);
	var postData = {};

	postData.op = 'changeType';
	postData.type = ruleType;

	jProgress('This may take several seconds', 60);
	$.ajax({
		type: 'POST',
		url: 'actionHandler/ajax_port_management.php',
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
				$this.val(o_ruleType);

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
			$this.val(o_ruleType);
			jAlert("Failure, please try again.");
		}
	});
}

function initEvents() {
	/* enable checkbox */
	$('input[name=EnableRule]').off('click').click(function(){
		var that = this;
		var isEnabling = $(this).prop("checked");
		jConfirm('Are you sure you want to '+(isEnabling ? 'enable' : 'disable')+' this Port Management rule?',
			"Are You Sure?", function(ret) {
			if(ret) {
				onenable.call(that, isEnabling);
			}
			else {
				$(that).prop("checked", !isEnabling);
			}
		});
	});

	/* delete button */
	$('td.delete > a.btn').off('click').click(function(e){
		e.stopPropagation();
		e.preventDefault();
		if (!o_pmEnabled) {
			/* prevent this action */
			return;
		}
		var message = ($(this).attr("title").length > 0) ?
			"Are you sure you want to " + $(this).attr("title") + "?" : "Are you sure?";
		var that = this;
		jConfirm(message, "Are You Sure?", function(ret) {
			if(ret) {
				ondelete.call(that);
			}    
		});
	});

	/* add and edit button */
	if (!o_pmEnabled) {
		$('#add-service').off('click').click(function(e){
			e.preventDefault();
			e.stopPropagation();
		});
		$('td.edit > a.btn').off('click').click(function(e){
			e.preventDefault();
			e.stopPropagation();
		});
	}
	else if (o_ruleArray.length >= 64) {
		$('#add-service').off('click').click(function(e){
			e.preventDefault();
			e.stopPropagation();
			jAlert("There are already 64 rules, cannot add more!");
		});
	}

	/* disable all */
	$('#disableAll').off('click').click(function(){
		var that = this;
		var isDisabling = $(this).prop("checked");
		jConfirm('Are you sure you want to '+(isDisabling ? 'disable <b>ALL</b>' : 'enable')+' Port Management rule?',
			"Are You Sure?", function(ret) {
			if(ret) {
				ondisableAll.call(that, isDisabling);
			}
			else {
				$(that).prop("checked", !isDisabling);
			}
		});
	});

	/* rule type */
	$('#ruleType').off('change').change(function(){
		var that = this;
		var ruleType = $(this).val();
		jConfirm('Are you sure you want to change Port Management to "'+$(this).children(':selected').text()+'"?',
			"Are You Sure?", function(ret) {
			if(ret) {
				onchangeRuleType.call(that, ruleType);
			}
			else {
				$(that).val(o_ruleType);
			}
		});
	});
}

function onchangePmDisable(disabled, preFill) {
	if (disabled === undefined) {
		disabled = $('#disableAll').prop("checked");
	}
	else {
		$('#disableAll').prop("checked", disabled);
	}
	if (disabled) {
		$('#forwarding-items').addClass('disabled');
		$('.module.data *').addClass('disabled');
		$('.module.data input:checkbox').prop("disabled", true);
	}
	else {
		$('#forwarding-items').removeClass('disabled');
		$('.module.data *').removeClass('disabled');
		$('.module.data :checkbox').prop("disabled", false);
	}
}

function initPmFields() {
	/* at first to populate the table data */
	var htmlStr = '';
	for (var i=0; i<o_ruleArray.length; ++i) {
		var entry = o_ruleArray[i];

		htmlStr += '<tr eid="'+entry['__id']+'" '+((i%2 != 0) ? 'class="odd"' : "")+'>';
		htmlStr += '<td headers="hdr_number">'+(i+1)+'</td>';
		htmlStr += '<td headers="hdr_appname">'+htmlspecialchars_js(entry['Name'])+'</td>';
		htmlStr += '<td headers="hdr_portrange">'+entry['PortRangeMin']+' ~ '+entry['PortRangeMax']+'</td>';
		htmlStr += '<td headers="hdr_proto">'+entry['Protocol']+'</td>';
		htmlStr += '<td headers="hdr_iprange">'+entry['IPRangeMin']+' ~ '+entry['IPRangeMax']+'</td>';
		htmlStr += '<td headers="hdr_enable"><label for="EnableRule'+i+'" class="acs-hide"></label>'+
			'<input type="checkbox" id="EnableRule'+i+'" name="EnableRule" '+(entry['Enable'] === 'true' ? 'checked' : '')+' /></td>';
		htmlStr += '<td headers="hdr_controls" class="edit"><a href="port_management_edit.php?id='+entry['__id']+'" class="btn" >Edit</a></td>';
		htmlStr += '<td headers="hdr_controls" class="delete"><a href="javascript:void(0)" class="btn" title="delete Port Management Rule for '+htmlspecialchars_js(entry['Name'])+'?">x</a></td>';
		htmlStr += '</tr>';
	}
	$('#ruleTable tr:first').after(htmlStr);

	onchangePmDisable(!o_pmEnabled, true);
	$('#ruleType').val(o_ruleType);
}

$(document).ready(function() {
    comcast.page.init("Advanced > True Static IP Port Management", "nav-port-management");

	initPmFields();
	initEvents();
});

</script>

<div id="content">
	<h1>Advanced > True Static IP Port Management</h1>

	<div id="educational-tip">
		<p class="tip">Manage rules that restrict certain inbound traffic to specific computers on the True Static IP network.</p>
		<p class="hidden">True Static IP Port Management allows you to restrict inbound traffic to computers within your local Static IP network by IP address and by Logical Port. Logical Ports are assigned numbers to identify Internet traffic that is generated by a software application. The assignable logical port range is between 1 and 65535. Your gateway supports up to 100 True Static IP Port Management rules.  To add a new static IP port management rule, click the add new button.  To edit an existing rule, select the rule from the port blocking rule table, and click edit.  Make sure the Enable box is checked when you wish to enforce a specific static IP port management rule.  Also note the dropdown menu that will allow you to define how the rules function.  You can make your rules work to only allow specific ports to be open, or a list of specific ports to block.</p>
	</div>

	<form action="" method="post">
	<div class="module">

	<div class="form-row ">
		<input type="checkbox" name="disableAll" value="disable" id="disableAll" /><b><label for="disableAll">Disable all rules and allow all inbound traffic through</label></b>
	</div>
	<div class="form-row">
		<label for="ruleType" class="acs-hide">Select rule type</label>
		<select name="ruleType" id="ruleType">
			<option value="White">Block all ports but allow exceptions below</option>
			<option value="Black">Open all ports but block exceptions below</option>
		</select>
	</div>

	</div>
	</form>
	<div id=forwarding-items>
	<div class="module data">
	<h2>Port Management</h2>
			<p class="button"><a href="port_management_edit.php?t=add" class="btn" id="add-service">+ ADD New</a></p>

		<table id="ruleTable" class="data" summary="This table lists all port management rules">
		    <tr>
		        <th id="hdr_number">#</th>
		        <th id="hdr_appname">Application Name</th>
				<th id="hdr_portrange">Port Range</th>
				<th id="hdr_proto">Protocol</th>
				<th id="hdr_iprange">True Static IP Range</th>
				<th id="hdr_enable">Enable</th>
				<th id="hdr_controls" colspan="2">&nbsp;</th>
		    </tr>
		<tfoot>
			<tr class="acs-hide">
				<td headers="hdr_number">null</td>
				<td headers="hdr_appname">null</td>
				<td headers="hdr_portrange">null</td>
				<td headers="hdr_proto">null</td>
				<td headers="hdr_iprange">null</td>
				<td headers="hdr_enable">null</td>
				<td headers="hdr_controls">null</td>
				<td headers="hdr_controls">null</td>
			</tr>
		</tfoot>
		</table>

	</div> <!-- end .module -->
	</div>
</div><!-- end #content -->
<?php include('includes/footer.php'); ?>
