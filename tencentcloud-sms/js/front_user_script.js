/*
 * Copyright (C) 2020 Tencent Cloud.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
jQuery(function ($) {
   // 重新获取验证码短信时间间隔
   var waitTime = 60;
   function sendCountdown() {
      if (waitTime > 0) {
         $('#get_verify_code_btn').text(waitTime + '秒后重新获取验证码').attr("disabled", true);
         waitTime--;
         setTimeout(sendCountdown, 1000);
      } else {
         $('#get_verify_code_btn').text('获取短信验证码').attr("disabled", false).fadeTo("slow", 1);
         waitTime = 60;
      }
   }

   //未绑定手机号表单提示
   $('#loginform').before('<p id="ajax-error-tips" class="message" style="border-left-color:red;display: none;">该手机号为未绑定已注册用户 <button id="to-bind-user" style="margin-top: -5px;" type="button" class="button button-primary">绑定用户</button><br> </p>');
   //重置密码表单交互
   $('#reset-way-phone').click(function () {
      $("p.message").text('输入手机号和短信验证码，验证通过后将会跳转到密码重置界面。');
      $("label[for='user_login']").text('手机号');
      $("#special_filed").show();
      $('#reset-way-name-or-email').show();
      $('#reset_way').val('phone');
      $(this).hide();
   });
   //重置密码表单交互
   $('#reset-way-name-or-email').click(function () {
      $("p.message").text('输入您的用户名或电子邮箱地址。您会收到一封包含重设密码指引的电子邮件。');
      $("label[for='user_login']").text('使用用户名或邮箱重置密码');
      $("#special_filed").hide();
      $('#reset-way-phone').show();
      $('#reset_way').val('usernameOrEmail');
      $(this).hide();
   });
      //登录表单交互
   $('#login-way-phone').click(function () {
      $(this).hide();
      $('#login-way-name-or-email').show();
      $('#phone-verify-fields').show();
      $('#user_login').parent().hide();
      $('.user-pass-wrap').hide();

      $('#need_bind_phone').val('unbind');
      $("#smsForm").css('padding-top','50px');
      $('#wp-submit').val('登录').css({"margin-top":"0","margin-right":"0"});
   });
   //登录表单交互
   $('#login-way-name-or-email').click(function () {
      $(this).hide();
      $('#login-way-phone').show();
      $('#phone-verify-fields').hide();
      $('#user_login').parent().show();
      $('.user-pass-wrap').show();

      $('#need_bind_phone').val('unbind');
      $("#smsForm").css('padding-top','50px');
      $('#wp-submit').val('登录').css({"margin-top":"0","margin-right":"0"});
   });

   //获取短信验证码
   $('#get_verify_code_btn').click(function () {
      var phone = $("#user_phone").val();
      if (phone === undefined) {
         phone = $("#user_login").val();
      }
      if (phone === '' || !phone.match(/^1[3-9]\d{9}$/)) {
         alert('手机号格式错误');
         $("#user_phone").focus();
         return;
      }
      var ajaxurl = $("#hidden_ajax_url").val();
      var checkPhoneUsed = $("#hidden_ajax_url").attr("data-check_phone_used")
      $.ajax({
         type: "post",
         dataType: "json",
         url: ajaxurl,
         data: {
            action: "get_verify_code",
            user_phone: phone,
            check_phone_used:checkPhoneUsed
         },
         success: function (response) {
            console.log(response);
            switch (response.data.errorCode) {
               //手机号已被绑定
               case 100001:
                  $('#ajax-error-tips').show();
                  break;
                  //未绑定手机号
               case 100002:
                  $('#ajax-error-tips').text(response.data.msg).show();
                  break;
                  default:
                  alert(response.data.msg);
            }
            if (response.success){
               sendCountdown();
            }
         }
      });
   });
   //点击去绑定手机号的交互
   $('#to-bind-user').click(function () {
      $('#ajax-error-tips').hide();
      $("#smsForm").css('padding-top','100px');
      $('#wp-submit').val('绑定用户').css({"margin-top":"8%","margin-right":"35%"});
      $('#need_bind_phone').val('bind');
      $('#user_login').parent().show();
      $('.user-pass-wrap').show();
      $('#login-way-phone').show();
      $("#hidden_ajax_url").attr("data-check_phone_used",'check')
   });

});