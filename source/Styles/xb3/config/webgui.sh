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
    fwbundlename=$(basename `find /etc/ -name "webui-cert-bundle*.tar"`)
    if [ ! -f /nvram/certs/myrouter.io.cert.pem ] || [ -f /etc/$fwbundlename ]; then
        if [ -f /lib/rdk/check-webui-update.sh ]; then
            sh /lib/rdk/check-webui-update.sh
        else
            echo "check-webui-update.sh not available means webuiupdate support is disabled"
        fi
    else
        echo "certificate /nvram/certs/myrouter.io.cert.pem or webui bundle not available"
    fi
fi

if [ -d /nvram/certs ]; then
    if [ ! -f /usr/bin/GetConfigFile ];then
        echo "Error: GetConfigFile Not Found"
        exit 127
    fi
    mkdir -p /tmp/.webui/
    ID="/tmp/trpfizyanrln"
    GetConfigFile $ID
    cp /nvram/certs/myrouter.io.cert.pem /tmp/.webui/
    #lighttpd expects file with key and pem
    cat /tmp/.webui/myrouter.io.cert.pem >> $ID
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
	/bin/kill $LIGHTTPD_PID
fi
WIFIUNCONFIGURED=`syscfg get redirection_flag`
SET_CONFIGURE_FLAG=`psmcli get eRT.com.cisco.spvtg.ccsp.Device.WiFi.NotifyWiFiChanges`

#Read the http response value
NETWORKRESPONSEVALUE=`cat /var/tmp/networkresponse.txt`

iter=0
max_iter=2
while [ "$SET_CONFIGURE_FLAG" = "" ] && [ "$iter" -le $max_iter ]
do
	iter=$((iter+1))
	echo "$iter"
	SET_CONFIGURE_FLAG=`psmcli get eRT.com.cisco.spvtg.ccsp.Device.WiFi.NotifyWiFiChanges`
done
echo "WEBGUI : NotifyWiFiChanges is $SET_CONFIGURE_FLAG"
echo "WEBGUI : redirection_flag val is $WIFIUNCONFIGURED"
if [ "$WIFIUNCONFIGURED" = "true" ]
then
	if [ "$NETWORKRESPONSEVALUE" = "204" ] && [ "$SET_CONFIGURE_FLAG" = "true" ]
	then
		while : ; do
		echo "WEBGUI : Waiting for PandM to initalize completely to set ConfigureWiFi flag"
		CHECK_PAM_INITIALIZED=`find /tmp/ -name "pam_initialized"`
		echo "CHECK_PAM_INITIALIZED is $CHECK_PAM_INITIALIZED"
  	        	if [ "$CHECK_PAM_INITIALIZED" != "" ]
   			then
			   echo "WEBGUI : WiFi is not configured, setting ConfigureWiFi to true"
	         	   output=`dmcli eRT setvalues Device.DeviceInfo.X_RDKCENTRAL-COM_ConfigureWiFi bool TRUE`
			   check_success=`echo $output | grep  "Execution succeed."`
  	        		if [ "$check_success" != "" ]
   				then
     			 	   echo "WEBGUI : Setting ConfigureWiFi to true is success"
 	       			fi
      			   break
 	       		fi
		sleep 2
		done
	

	else
		echo "WEBGUI : WiFi is already configured"
		if [ ! -e "$REVERT_FLAG" ] && [ "$NETWORKRESPONSEVALUE" = "204" ]
		then
			echo "WEBGUI: WiFi is already configured. Set reverted flag in nvram"	
			touch $REVERT_FLAG
		fi
	fi
fi		


LD_LIBRARY_PATH=/fss/gw/usr/ccsp:$LD_LIBRARY_PATH lighttpd -f /etc/lighttpd.conf

echo "WEBGUI : Set event"
sysevent set webserver started
rm -rf /tmp/.webui
rm $ID
