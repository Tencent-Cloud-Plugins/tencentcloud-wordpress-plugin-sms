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
         $('#get_verify_code').text(waitTime + '秒后重新获取验证码').attr("disabled", true);
         waitTime--;
         setTimeout(sendCountdown, 1000);
      } else {
         $('#get_verify_code_btn').text('获取短信验证码').attr("disabled", false).fadeTo("slow", 1);
         waitTime = 60;
      }
   }

   //获取短信验证码
   $('#get_verify_code').click(function () {
      var phone = $("#phone").val();
      if (phone === '' || !phone.match(/^1[3-9]\d{9}$/)) {
         alert('手机号格式错误');
         $("#phone").focus();
         return;
      }
      var ajaxurl = $("#hidden_ajax_url").val();
      $.ajax({
         type: "post",
         dataType: "json",
         url: ajaxurl,
         data: {
            action: "get_verify_code",
            user_phone: phone,
            check_phone_used:'check'
         },
         success: function (response) {
            if (response.data.errorCode === 100001) {
               $('#ajax-error-tips').show();
            }
            alert(response.data.msg);
            if (response.success){
               sendCountdown();
            }
         }
      });
   });


});