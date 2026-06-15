#! /bin/sh
##########################################################################
# If not stated otherwise in this file or this component's Licenses.txt
# file the following copyright and licenses apply:
#
# Copyright 2015 RDK Management
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
# http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
##########################################################################
#WEBGUI_SRC=/fss/gw/usr/www2/html.tar.bz2
#WEBGUI_DEST=/var/www

source /etc/device.properties

LOG_FILE=/tmp/ui_debug.txt
log_debug() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" >> $LOG_FILE
}

log_debug "[WEBGUI] Script started"

echo "setenv.add-environment = (\
\"WAN0_IS_DUMMY\" => \"$WAN0_IS_DUMMY\"
)"

#if test -f "$WEBGUI_SRC"
#then
#	if [ ! -d "$WEBGUI_DEST" ]; then
#		/bin/mkdir -p $WEBGUI_DEST
#	fi
#	/bin/tar xjf $WEBGUI_SRC -C $WEBGUI_DEST
#else
#	echo "WEBGUI SRC does not exist!"
#fi
if [ -z $1 ] && [ ! -f /tmp/webuifwbundle ]; then
    log_debug "[WEBGUI] Checking webui firmware bundle"
    fwbundlename=$(basename `find /etc/ -name "webui-cert-bundle*.tar"`)
    if [ ! -f /nvram/certs/myrouter.io.cert.pem ] || [ -f /etc/$fwbundlename ]; then
        if [ -f /lib/rdk/check-webui-update.sh ]; then
            log_debug "[WEBGUI] Running check-webui-update.sh"
            sh /lib/rdk/check-webui-update.sh
            log_debug "[WEBGUI] check-webui-update.sh completed"
        else
            echo "check-webui-update.sh not available means webuiupdate support is disabled"
            log_debug "[WEBGUI] check-webui-update.sh not available, webuiupdate support is disabled"
        fi
    else
        echo "certificate /nvram/certs/myrouter.io.cert.pem or webui bundle not available"
        log_debug "[WEBGUI] certificate /nvram/certs/myrouter.io.cert.pem or webui bundle not available"
    fi
fi

if [ -d /nvram/certs ]; then
    log_debug "[WEBGUI] Setting up certs from /nvram/certs"
    if [ ! -f /usr/bin/GetConfigFile ];then
        echo "Error: GetConfigFile Not Found"
        log_debug "[WEBGUI] ERROR: GetConfigFile Not Found"
        exit 127
    fi
    mkdir -p /tmp/.webui/
    ID="/tmp/trpfizyanrln"
    log_debug "[WEBGUI] Calling GetConfigFile"
    GetConfigFile $ID
    log_debug "[WEBGUI] GetConfigFile completed"
    cp /nvram/certs/myrouter.io.cert.pem /tmp/.webui/
    #lighttpd expects file with key and pem
    cat /tmp/.webui/myrouter.io.cert.pem >> $ID
    log_debug "[WEBGUI] Cert setup done"
fi


# start lighttpd
source /etc/utopia/service.d/log_capture_path.sh
source /fss/gw/etc/utopia/service.d/log_env_var.sh
# setup non-root related file-permission for lighttpd
touch /rdklogs/logs/lighttpderror.log
chown non-root:non-root /rdklogs/logs/lighttpderror.log
touch /rdklogs/logs/webui.log
chown non-root:non-root /rdklogs/logs/webui.log
REVERT_FLAG="/nvram/reverted"

LIGHTTPD_PID=`pidof lighttpd`
if [ "$LIGHTTPD_PID" != "" ]; then
	log_debug "[WEBGUI] Killing existing lighttpd PID=$LIGHTTPD_PID"
	/bin/kill $LIGHTTPD_PID
	log_debug "[WEBGUI] lighttpd killed"
fi
WIFIUNCONFIGURED=`syscfg get redirection_flag`
SET_CONFIGURE_FLAG=`psmcli get eRT.com.cisco.spvtg.ccsp.Device.WiFi.NotifyWiFiChanges`

#Read the http response value
NETWORKRESPONSEVALUE=`cat /var/tmp/networkresponse.txt`

iter=0
max_iter=2
log_debug "[WEBGUI] Reading NotifyWiFiChanges flag (max retries=$max_iter)"
while [ "$SET_CONFIGURE_FLAG" = "" ] && [ "$iter" -le $max_iter ]
do
	iter=$((iter+1))
	echo "$iter"
	log_debug "[WEBGUI] NotifyWiFiChanges retry iter=$iter"
	SET_CONFIGURE_FLAG=`psmcli get eRT.com.cisco.spvtg.ccsp.Device.WiFi.NotifyWiFiChanges`
done
echo "WEBGUI : NotifyWiFiChanges is $SET_CONFIGURE_FLAG"
echo "WEBGUI : redirection_flag val is $WIFIUNCONFIGURED"
log_debug "[WEBGUI] NotifyWiFiChanges=$SET_CONFIGURE_FLAG redirection_flag=$WIFIUNCONFIGURED"
if [ "$WIFIUNCONFIGURED" = "true" ]
then
	log_debug "[WEBGUI] WiFi is unconfigured (redirection_flag=true)"
	if [ "$NETWORKRESPONSEVALUE" = "204" ] && [ "$SET_CONFIGURE_FLAG" = "true" ]
	then
		log_debug "[WEBGUI] Network response=204 and NotifyWiFiChanges=true, waiting for PandM init"
		while : ; do
		echo "WEBGUI : Waiting for PandM to initalize completely to set ConfigureWiFi flag"
		CHECK_PAM_INITIALIZED=`find /tmp/ -name "pam_initialized"`
		echo "CHECK_PAM_INITIALIZED is $CHECK_PAM_INITIALIZED"
		log_debug "[WEBGUI] CHECK_PAM_INITIALIZED=$CHECK_PAM_INITIALIZED"
  	        	if [ "$CHECK_PAM_INITIALIZED" != "" ]
   			then
			   echo "WEBGUI : WiFi is not configured, setting ConfigureWiFi to true"
			   log_debug "[WEBGUI] PAM initialized, setting ConfigureWiFi to true"
	         	   output=`dmcli eRT setvalues Device.DeviceInfo.X_RDKCENTRAL-COM_ConfigureWiFi bool TRUE`
			   check_success=`echo $output | grep  "Execution succeed."`
  	        		if [ "$check_success" != "" ]
   				then
     			 	   echo "WEBGUI : Setting ConfigureWiFi to true is success"
				   log_debug "[WEBGUI] ConfigureWiFi set to true successfully"
 	       			fi
      			   break
 	       		fi
		sleep 2
		done
	

	else
		echo "WEBGUI : WiFi is already configured"
		log_debug "[WEBGUI] WiFi is already configured"
		if [ ! -e "$REVERT_FLAG" ] && [ "$NETWORKRESPONSEVALUE" = "204" ]
		then
			echo "WEBGUI: WiFi is already configured. Set reverted flag in nvram"	
			log_debug "[WEBGUI] Setting reverted flag in nvram"
			touch $REVERT_FLAG
			log_debug "[WEBGUI] Reverted flag set"
		fi
	fi
fi		


log_debug "[WEBGUI] Starting lighttpd"
LD_LIBRARY_PATH=/fss/gw/usr/ccsp:$LD_LIBRARY_PATH lighttpd -f /etc/lighttpd.conf
log_debug "[WEBGUI] lighttpd started (exit code=$?)"

echo "WEBGUI : Set event"
log_debug "[WEBGUI] Setting sysevent webserver started"
sysevent set webserver started
log_debug "[WEBGUI] Cleaning up temp files"
rm -rf /tmp/.webui
rm $ID
log_debug "[WEBGUI] Script completed"
