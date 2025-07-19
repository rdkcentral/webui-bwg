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
<?php include('../includes/utility.php') ?>
<?php include('../includes/actionHandlerUtility.php') ?>
<?php
session_start();
if (!isset($_SESSION["loginuser"])) {
	echo '<script type="text/javascript">alert("Please Login First!"); location.href="../index.php";</script>';
	exit(0);
}
$result="";

function PORTTEST($sport,$eport,$arraySPort,$arrayEPort) {
	
	//echo $sport."  ".$eport."  ".$arraySPort."  ".$arrayEPort."<hr/>";

	if ( ($sport>=$arraySPort) && ($sport<=$arrayEPort) ){
		return 1;
	}
	elseif ( ($eport>=$arraySPort) && ($eport<=$arrayEPort) ){
		return 1;
	}
	elseif ( ($sport<$arraySPort) && ($eport>$arrayEPort) ){
		return 1;
	}
	else 
		return 0;
}

if (isset($_POST['set'])){
	if (isValInArray($_POST['UFWDStatus'], array("Enabled", "Disabled"))){
	$UFWDStatus=(($_POST['UFWDStatus']=="Enabled")?"true":"false");
	setStr("Device.NAT.X_Comcast_com_EnablePortMapping",$UFWDStatus,true);
	while(getStr("Device.NAT.X_Comcast_com_EnablePortMapping")!=$UFWDStatus) sleep(2);
	//$UFWDStatus=(getStr("Device.NAT.X_Comcast_com_EnablePortMapping")=="true"?"Enabled":"Disabled");
	//echo json_encode($UFWDStatus);
	}
}

/* special case for OneOneNat handling */
$isToOneOneNat = isset($_POST['toOneOneNat']);

if (isset($_POST['add'])){
	$validation = true;
	if($validation) $validation = printableCharacters($_POST['name']);
	if($validation) $validation = is_allowed_string($_POST['name']);
	if($validation) $validation = isValInArray($_POST['type'], array('TCP', 'UDP', 'TCP/UDP'));
	if($validation) $validation = validIPAddr($_POST['ip']);
	if($validation)
		if (!($_POST['ipv6addr']=='x' || validIPAddr($_POST['ipv6addr']))) $validation = false;
	if(!isset( $_POST['publicIp'])){
    	if($validation) $validation = validPort($_POST['startport']);
        if($validation) $validation = validPort($_POST['endport']);
    }
	if($validation) {
		$name=$_POST['name'];
		$type=$_POST['type'];
		if ($type=="TCP/UDP") $type="BOTH";
		$ip=$_POST['ip'];
		$ip6 = $_POST['ipv6addr'];
		$sport=$_POST['startport'];
		$eport=$_POST['endport'];
		$publicIp = $_POST['publicIp'];
		
		if (getStr("Device.NAT.PortMappingNumberOfEntries")==0) {	
			addTblObj("Device.NAT.PortMapping.");
			$IDs=explode(",",getInstanceIDs("Device.NAT.PortMapping."));
			$i=$IDs[count($IDs)-1];

			$rootObjName ="Device.NAT.PortMapping.";
			$paramArray = 
				array (
					array("Device.NAT.PortMapping.".$i.".Enable", "bool", "true"),
					array("Device.NAT.PortMapping.".$i.".InternalClient", "string", $ip),
					array("Device.NAT.PortMapping.".$i.".X_CISCO_COM_InternalClientV6", "string", $ip6),
					array("Device.NAT.PortMapping.".$i.".InternalPort", "uint", "0"),
					array("Device.NAT.PortMapping.".$i.".ExternalPort", "uint", $sport),
					array("Device.NAT.PortMapping.".$i.".ExternalPortEndRange", "uint", $eport),
					array("Device.NAT.PortMapping.".$i.".Protocol", "string", $type),
					array("Device.NAT.PortMapping.".$i.".Description", "string", $name),
				);
			if ($isToOneOneNat) {
				$paramArray[] = array("Device.NAT.PortMapping.".$i.".X_Comcast_com_PublicIP", "string", $publicIp);
			}
			$retStatus = DmExtSetStrsWithRootObj($rootObjName, TRUE, $paramArray);	
			if (!$retStatus){
				if ($isToOneOneNat) $result = array('status'=>'success');
				else $result="Success!";
			}
		} 
		else {
			// $result="";
			$ids=explode(",",getInstanceIDs("Device.NAT.PortMapping."));
			foreach ($ids as $key=>$j) {
				if (getStr("Device.NAT.PortMapping.".$j.".LeaseDuration")==0 && getStr("Device.NAT.PortMapping.".$j.".InternalPort")==0){
					$arrayName  = getStr("Device.NAT.PortMapping.".$j.".Description");
					$arrayIP    = getStr("Device.NAT.PortMapping.".$j.".InternalClient");
					$arrayType  = getStr("Device.NAT.PortMapping.".$j.".Protocol");
					$arraySPort = getStr("Device.NAT.PortMapping.".$j.".ExternalPort");
					$arrayEPort = getStr("Device.NAT.PortMapping.".$j.".ExternalPortEndRange");
					$arrayPublicIp = getStr("Device.NAT.PortMapping.".$j.".X_Comcast_com_PublicIP");

					/* check uniqueness of public ip and private ip */
					$isOneOneNatEntry = !empty($arrayPublicIp) && $arrayPublicIp !== '0.0.0.0';
					if ($isOneOneNatEntry && $arrayIP === $ip) {
						$result .= 'The private IP (Server IP) has been used by One One NAT.';
						break;
					}
					else if ($isOneOneNatEntry && isset($publicIp) && $publicIp === $arrayPublicIp) {
						$result .= "The public IP has been used!";
						break;
					}
					else if ($isToOneOneNat && $arrayIP === $ip) {
						$result .= "The private IP has been used!";
						break;
					}

					if ($isToOneOneNat) {
						/* if this is targeting to one one nat entry, skip the port overlap checking */
						continue;
					}
					if($name==$arrayName) { 
						$result.="Service name has been used!\n";
						break;
					} 
					else if($type=="BOTH"||$arrayType=="BOTH"||$type==$arrayType){
						$porttest=PORTTEST($sport,$eport,$arraySPort,$arrayEPort);
						if ($porttest==1) {
							$result.="Conflict with other service. Please check port and IP!";
							break;
						}
					}
				}
			}
			
			if ($result=="") {
				addTblObj("Device.NAT.PortMapping.");
				$IDs=explode(",",getInstanceIDs("Device.NAT.PortMapping."));
				$i=$IDs[count($IDs)-1];

				$rootObjName ="Device.NAT.PortMapping.";
				$paramArray = 
					array (
						array("Device.NAT.PortMapping.".$i.".Enable", "bool", "true"),
						array("Device.NAT.PortMapping.".$i.".InternalClient", "string", $ip),
						array("Device.NAT.PortMapping.".$i.".X_CISCO_COM_InternalClientV6", "string", $ip6),
						array("Device.NAT.PortMapping.".$i.".InternalPort", "uint", "0"),
						array("Device.NAT.PortMapping.".$i.".ExternalPort", "uint", $sport),
						array("Device.NAT.PortMapping.".$i.".ExternalPortEndRange", "uint", $eport),
						array("Device.NAT.PortMapping.".$i.".Protocol", "string", $type),
						array("Device.NAT.PortMapping.".$i.".Description", "string", $name),
					);
				if ($isToOneOneNat) {
					$paramArray[] = array("Device.NAT.PortMapping.".$i.".X_Comcast_com_PublicIP", "string", $publicIp);
				}
				$retStatus = DmExtSetStrsWithRootObj($rootObjName, TRUE, $paramArray);	
				if (!$retStatus){
					if ($isToOneOneNat) $result = array('status'=>'success');
					else $result="Success!";
				}	
				else {
					$result = 'Failed to add';
				}
			}
		}
	}
}

if (isset($_POST['edit'])){
	$validation = true;
	if($validation) $validation = validId($_POST['ID']);
	if($validation) $validation = printableCharacters($_POST['name']);
	if($validation) $validation = is_allowed_string($_POST['name']);
	if($validation) $validation = isValInArray($_POST['type'], array('TCP', 'UDP', 'TCP/UDP'));
	if($validation) $validation = validIPAddr($_POST['ip']);
	if($validation)
		if (!($_POST['ipv6addr']=='x' || validIPAddr($_POST['ipv6addr']))) $validation = false;
	if(!isset( $_POST['publicIp'])){
    	if($validation) $validation = validPort($_POST['startport']);
		if($validation) $validation = validPort($_POST['endport']);
    }
	if($validation) {
		$i=$_POST['ID'];
		$name=$_POST['name'];
		$type=$_POST['type'];
		if ($type=="TCP/UDP") $type="BOTH";
		$ip=$_POST['ip'];
		$ip6 = $_POST['ipv6addr'];
		$sport=$_POST['startport'];
		$eport=$_POST['endport'];
		$publicIp = $_POST['publicIp'];
		
		// $result="";
		$ids=explode(",",getInstanceIDs("Device.NAT.PortMapping."));
		foreach ($ids as $key=>$j) {
			if ($i==$j) continue;
			if (getStr("Device.NAT.PortMapping.".$j.".LeaseDuration")==0 && getStr("Device.NAT.PortMapping.".$j.".InternalPort")==0){
				$arrayName  = getStr("Device.NAT.PortMapping.".$j.".Description");
				$arrayIP    = getStr("Device.NAT.PortMapping.".$j.".InternalClient");
				$arrayType  = getStr("Device.NAT.PortMapping.".$j.".Protocol");
				$arraySPort = getStr("Device.NAT.PortMapping.".$j.".ExternalPort");
				$arrayEPort = getStr("Device.NAT.PortMapping.".$j.".ExternalPortEndRange");
				$arrayPublicIp = getStr("Device.NAT.PortMapping.".$j.".X_Comcast_com_PublicIP");

				/* check uniqueness of public ip and private ip */
				$isOneOneNatEntry = !empty($arrayPublicIp) && $arrayPublicIp !== '0.0.0.0';
				if ($isOneOneNatEntry && $arrayIP === $ip) {
					$result .= 'The private IP (Server IP) has been used by One One NAT.';
					break;
				}
				else if ($isOneOneNatEntry && isset($publicIp) && $publicIp === $arrayPublicIp) {
					$result .= "The public IP has been used!";
					break;
				}
				else if ($isToOneOneNat && $arrayIP === $ip) {
					$result .= "The private IP has been used!";
					break;
				}

				if ($isToOneOneNat) {
					/* if this is targeting to one one nat entry, skip the port overlap checking */
					continue;
				}
				if($name==$arrayName) { 
					$result.="Service name has been used!\n";
					break;
				}
				else if($type=="BOTH"||$arrayType=="BOTH"||$type==$arrayType){
					$porttest=PORTTEST($sport,$eport,$arraySPort,$arrayEPort);
					if ($porttest==1) {
						$result.="Conflict with other service. Please check port and IP!";
						break;
					}
				}
			}
		}

		if ($result=="") {
			$rootObjName ="Device.NAT.PortMapping.";
			$paramArray = 
				array (
					array("Device.NAT.PortMapping.".$i.".Enable", "bool", "true"),
					array("Device.NAT.PortMapping.".$i.".InternalClient", "string", $ip),
					array("Device.NAT.PortMapping.".$i.".X_CISCO_COM_InternalClientV6", "string", $ip6),
					array("Device.NAT.PortMapping.".$i.".InternalPort", "uint", "0"),
					array("Device.NAT.PortMapping.".$i.".ExternalPort", "uint", $sport),
					array("Device.NAT.PortMapping.".$i.".ExternalPortEndRange", "uint", $eport),
					array("Device.NAT.PortMapping.".$i.".Protocol", "string", $type),
					array("Device.NAT.PortMapping.".$i.".Description", "string", $name),
				);
			if ($isToOneOneNat) {
				$paramArray[] = array("Device.NAT.PortMapping.".$i.".X_Comcast_com_PublicIP", "string", $publicIp);
			}
			$retStatus = DmExtSetStrsWithRootObj($rootObjName, TRUE, $paramArray);	
			if (!$retStatus){
				if ($isToOneOneNat) $result = array('status'=>'success');
				else $result="Success!";
			}
			else {
				$result = 'Failed to edit';
			}
		}
	}
}


if (isset($_POST['active'])){
	$validation = true;
	if($validation) $validation = isValInArray($_POST['isChecked'], array('true', 'false'));
	if($validation) $validation = validId($_POST['id']);
	if($validation) {
		$isChecked=$_POST['isChecked'];
		$i=$_POST['id'];
		if (setStr("Device.NAT.PortMapping.$i.Enable",$isChecked,true) === true) {
			if ($isToOneOneNat) $result = array('status'=>'success');
			else $result="Success!";
		}
	}
}

if (isset($_REQUEST['del'])){
	$validation = true;
	if($validation) $validation = validId($_POST['del']);
	if($validation) 
		delTblObj("Device.NAT.PortMapping.".$_REQUEST['del'].".");
	if ($isToOneOneNat) {
		$result = array('status' => 'success');
		header("Content-Type: application/json");
		echo json_encode($result);
	}
	else {
		Header("Location:../port_forwarding.php");
	}
	exit;
}

if (isset($_POST['disableAllNat'])) {
	if ($isToOneOneNat) {
		$isDisabling = $_POST['disableAllNat'] === 'true';
		if (setStr('Device.NAT.X_Comcast_com_EnableNATMapping', $isDisabling ? 'false' : 'true', true) === true) {
			$result = array('status' => 'success');
		}
	}
}

//the set operation failure due to conflict with port trigger rules or ...
//so need to remove the '0.0.0.0' entry
$ids=explode(",",getInstanceIDs("Device.NAT.PortMapping."));
	foreach ($ids as $key=>$j) {

        if (getStr("Device.NAT.PortMapping.$j.InternalClient") == "0.0.0.0") {
        	delTblObj("Device.NAT.PortMapping.$j.");
        }
	} //end of foreach

if ($isToOneOneNat && $result === '') $result = array('status'=>'failed');
else if ($isToOneOneNat && !is_array($result)) {
	$result = array('status' => 'failed', 'msg' => $result);
}

if ($isToOneOneNat) {
	header("Content-Type: application/json");
}
echo htmlspecialchars(json_encode($result), ENT_NOQUOTES, 'UTF-8');

?>
