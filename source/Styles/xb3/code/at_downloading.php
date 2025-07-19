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


<script type="text/javascript">
$(document).ready(function() {
    comcast.page.init("Save Configuration - Xfinity");


    jQuery.validator.addMethod("passwordValidation",function(value,element){
    return value.match(/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/);
    }, "Password Require Minimum 8 Characters, One UPPERCASE, One lowercase and One Numeric Character");


    jQuery.validator.addMethod("FileNameValidation",function(value,element){
    return value.match(/^[a-zA-Z\d.]+$/);
    }, "FileName contains Invalid Characters");


    $("#pageForm").validate({
                debug: false,
                rules: {
                        saveFileName: {
                                required: true
                                ,FileNameValidation: true
                                ,maxlength: 63
                                ,minlength: 3
                        }
                        ,userPassword: {
                                required: true
                                ,alphanumeric: true
                                ,passwordValidation: true
                                ,maxlength: 20
                                ,minlength: 8
                        }
                        ,verifyPassword: {
                                required: true
                                ,alphanumeric: true
                                ,maxlength: 20
                                ,minlength: 8
                                ,equalTo: "#userPassword"
                        },
                        submitHandler:function(form){
                                next_step();
                        }
                }

        });
        $("#userPassword").val("");
        $("#verifyPassword").val("");
		
		        //Fix for IE8 browser issue, IE8 changeing type from "text" to "password" is not supported
        $("#password_show").change(function() {
                if ($("#password_show").is(":checked")) {
                        document.getElementById("password_field_2").innerHTML =
                        '<input type="text"     size="23" id="userPassword" name="userPassword" class="text" value="' + $("#userPassword").val() + '" />';
                        document.getElementById("password_field_3").innerHTML =
                        '<input type="text"     size="23" id="verifyPassword" name="verifyPassword" class="text" value="' + $("#verifyPassword").val() + '" />';
                }
                else {
                        document.getElementById("password_field_2").innerHTML =
                        '<input type="password" size="23" id="userPassword" name="userPassword" class="text" value="' + $("#userPassword").val() + '" />';
                        document.getElementById("password_field_3").innerHTML =
                        '<input type="password" size="23" id="verifyPassword" name="verifyPassword" class="text" value="' + $("#verifyPassword").val() + '" />';
                }
        });

        $("#btn-cancel").click(function() {
                window.location = "at_a_glance.php";
        });

        $("#btn-save").click(function() {
        var FileName =$('#saveFileName').val();
        var UserInputPassword =$('#userPassword').val();
        var FilenameExt = FileName.substring(FileName.indexOf('.')+1);
        if((FilenameExt !== FileName) && (FilenameExt !== "CF2") && (FilenameExt !== "cf2"))
        {
                jAlert("Filename entered is invalid! Please enter a file name with a .CF2 extension or a file name without an extension.");
                return;
        }
     FileName = FileName.split('.',1);
     FileName_Only = FileName[0];
          
     if (FileName_Only.length < 3)
     {
                jAlert("FileName without Extension requires minimum 3 characters!!!");
                return;
     }       

        if($("#pageForm").valid()){
                        jProgress('This may take several seconds.',60);
                        $.ajax({
                                type:"POST",
                                url:"actionHandler/ajaxSet_at_downloading.php",
                                data:{edit:"true",FileName:FileName_Only,UserInputPassword:UserInputPassword},
                                success:function(result){
                                        jHide();
                                        if (result=="Success!") {
                                                jConfirm("Saving  Configuration Completed.", "Start Download ??", function (ans) {
                                                if (ans) {
                                                                window.open("at_download.php","_blank");
                                                                window.open("at_a_glance.php","_self")
                                                        }
                                                });
					}
                                        else if (result=="") {
                                                jAlert('Failure! Please check your inputs.');}
                                        else jAlert(result);
                                },
                                error:function(){
                                        jHide();
                                        jAlert("Something wrong, please try later!");
                                }
                        });
                } //end of if

        });


});
</script>


<div id="content">
        <h1>Save Router Configuration</h1>
        <div class="module forms">
                <form action="" method="post" id="pageForm">
                        <h2>User Inputs</h2>
                        <p class="summary">Enter File Name to be saved. Enter Password</p>

                        <div class="form-row">
                                <label for="FileName">Config FileName:</label>
                                <input type="text" size="23" id="saveFileName" name="saveFileName" class="text" value="">
                        </div>

                        <div class="form-row odd password">
                                <label for="userPassword">Password for File:</label>
                                <span id="password_field_2"><input type="password" size="23" id="userPassword" name="userPassword" class="text" value=""></span>
                        </div>

                        <div class="form-row password">
                                <label for="verifyPassword">Re-enter Password for File:</label>
                                <span id="password_field_3"><input type="password" size="23" id="verifyPassword" name="verifyPassword" class="text" value=""></span>
                        </div>

                        <div class="form-row odd">
                                <label for="password_show">Show Typed Password:</label>
                                <span class="checkbox"><input type="checkbox" id="password_show" name="password_show" /></span>
                        </div>

                        <p class="footnote">8-20 characters. Alphanumeric only. No spaces. Case sensitive.</p>
                        <div class="form-row form-btn">
                                <input type="button" id="btn-save" class="btn submit" value="Save"/>
                                <input type="button" id="btn-cancel" class="btn alt reset" value="Cancel"/>
                        </div>
                </form>
        </div>
</div>
<?php include('includes/footer.php'); ?>