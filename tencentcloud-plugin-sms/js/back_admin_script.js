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
   //ajax请求地址
   var ajaxUrl = $("#tencnetcloud-sms-setting-form").data("ajax-url");
   var pageSize = 10;
   function sendCountdown() {
      if (waitTime > 0) {
         $('#get_test_verify_code_btn').text(waitTime + '秒后重新获取验证码').attr("disabled", true);
         waitTime--;
         setTimeout(sendCountdown, 1000);
      } else {
         $('#get_test_verify_code_btn').text('获取短信验证码').attr("disabled", false).fadeTo("slow", 1);
         waitTime = 60;
      }
   }

   $("#sms-option-custom-key").change(function() {
      var disabled = !($(this).is(':checked'));
      $("#sms-option-secret-id").attr('disabled',disabled);
      $("#sms-option-secret-key").attr('disabled',disabled);

   });
   //转换input框的type
   function change_type(input_element, span_eye) {
      if(input_element[0].type === 'password') {
         input_element[0].type = 'text';
         span_eye.addClass('dashicons-visibility').removeClass('shicons-hidden');
      } else {
         input_element[0].type = 'password';
         span_eye.addClass('shicons-hiddenda').removeClass('dashicons-visibility');
      }
   }
   //转换input框的type
   $('#secret_id_type_exchange').click(function () {
      change_type($('#sms-option-secret-id'), $(this));
   });
   //转换input框的type
   $('#secret_key_type_exchange').click(function () {
      change_type($('#sms-option-secret-key'), $(this));
   });
   //顶部标签交互
    $('#sub-tab-settings').click(function () {
       $('#group-settings').show();
       $('#group-records').hide();
       $('#group-send-test').hide();
       $('#tencnetcloud-sms-setting-update-button').show();
       $('#sub-tab-records').removeClass('active');
       $(this).addClass('active');

    });
   //顶部标签交互
   $('#sub-tab-records').click(function () {
      $('#group-settings').hide();
      $('#group-send-test').hide();
      $('#tencnetcloud-sms-setting-update-button').hide();
      $('#group-records').show();
      var clicked = $(this).attr('data-clicked');
      if (clicked === '0') {
         getData(1,pageSize);
      }
      clicked++
      $(this).attr('data-clicked',clicked);
      $('#sub-tab-settings').removeClass('active');
      $(this).addClass('active');
   });
   //短信记录表格交互
   $('#search_sms_sent_list_button').click(function () {
      $("#more_list  tr:not(:first)").remove();
      getData(1,pageSize)
   });
//获取上一页
   $('#record_previous_page').click(function () {
      // if ($(this).attr('disabled') === 'disabled') {
      //    return;
      // }
      var currentPage = $(this).attr('data-current-page');
      if (currentPage === '1' || currentPage < '1') {
         return;
      }
      $("#more_list  tr:not(:first)").remove();
      currentPage--
      getData(currentPage,pageSize);
      $(this).attr('data-current-page',currentPage);
      $('#record_next_page').attr('disabled','').removeClass('disabled');
   });
//获取下一页
   $('#record_next_page').click(function () {
      if ($(this).attr('disabled') === 'disabled') {
         return;
      }
      var currentPage = $('#record_previous_page').attr('data-current-page');
      currentPage++
      $("#more_list  tr:not(:first)").remove();
       getData(currentPage,pageSize);
      $('#record_previous_page').attr('disabled','');
      $('#record_previous_page').attr('data-current-page',currentPage).removeClass('disabled');
   });

   //保存配置
   $('#tencnetcloud-sms-setting-update-button').click(function () {
      var customKey = $("#sms-option-custom-key").is(":checked")?1:0
      var secretID = $("#sms-option-secret-id").val()
      var secretKey = $("#sms-option-secret-key").val()
      var sdkAppId = $("#sms-option-sdk-appid").val()
      var sign = $("#sms-option-sign").val()
      var templateID = $("#sms-option-tpl-id").val()
      var hasExpireTime = $("#has_expire_time").is(":checked")?1:0;
      var commentNeedPhone = $("#sms-option-comment-need-phone").is(":checked")?1:0;
      var postNeedPhone = $("#sms-option-post-need-phone").is(":checked")?1:0;
      var codeExpired = $("#sms-option-code-expired").val()

      $.ajax({
         type: "post",
         url: ajaxUrl,
         dataType:"json",
         data: {
            action: "update_sms_settings",
            secret_id: secretID,
            custom_key: customKey,
            secret_key: secretKey,
            sdk_app_id: sdkAppId,
            sign: sign,
            template_id: templateID,
            has_expire_time:hasExpireTime,
            code_expired: codeExpired,
            comment_need_phone: commentNeedPhone,
            post_need_phone: postNeedPhone,
         },
         success: function(response) {
            showAjaxReturnMsg(response.data.msg,response.success)
            if (response.success){
               setTimeout(function(){
                  window.location.reload();//刷新当前页面.
               },2000)
            }
         }
      });
   });
   $('#loginform').append($('#smsForm'));
   //发送测试短信
   $('#get_test_verify_code_btn').click(function () {
      var phone = $("#test_phone").val();
      if (phone === '' || !phone.match(/^1[3-9]\d{9}$/)) {
         alert('手机号格式错误');
         $("#test_phone").focus();
         return;
      }
      var secretID = $("#sms-option-secret-id").val()
      var secretKey = $("#sms-option-secret-key").val()
      var sdkAppId = $("#sms-option-sdk-appid").val()
      var sign = $("#sms-option-sign").val()
      var templateID = $("#sms-option-tpl-id").val()
      var hasExpireTime = $("#has_expire_time").is(":checked")?1:0;
      var codeExpired = $("#sms-option-code-expired").val()

      $.ajax({
         type: "post",
         dataType: "json",
         url: ajaxUrl,
         data: {
            action: "test_send_sms_verify_code",
            user_phone: phone,
            secret_id: secretID,
            secret_key: secretKey,
            sdk_app_id: sdkAppId,
            sign: sign,
            template_id: templateID,
            has_expire_time: hasExpireTime,
            code_expired: codeExpired,
         },
         success: function (response) {
            console.log(response);
            showAjaxReturnMsg(response.data.msg,response.success);
            if (response.success){
               sendCountdown();
            }
         }
      });
   });

   $('#rebind_user_phone').click(function () {
      $('#user_phone').val('').attr('disabled',false);
      $(this).hide();
      $('#verify_code_fields').show();
   });
   //关闭提示条
   $('#close-ajax-return-msg').click(function () {
      $(this).parent().hide();
   });
   //鼠标悬浮
   $(document).on('mouseenter', "#sms-record-list-table td", function () {
      if (this.offsetWidth < this.scrollWidth) {
         $(this).attr('data-toggle', 'tooltip').attr('title', $(this).text());
      }
   });
   //鼠标离开时，tooltip消失
   $(document).on('mouseleave', '#sms-record-list-table td', function () {
      $(this).attr('data-toggle', '');
   });
   //回到顶部
   function goToTheTop() {
      $('html ,body').animate({scrollTop: 0}, 330);
   }
   //展示ajax返回的信息
   function showAjaxReturnMsg(msg,success) {
      var parent = $('#show-ajax-return-msg').parent();
      if (!success) {
         parent.removeClass('alert-success');
         parent.hasClass('alert-danger') || parent.addClass('alert-danger');
      } else {
         parent.removeClass('alert-danger');
         parent.hasClass('alert-success') || parent.addClass('alert-success');
      }
      //展示ajax返回并跳转到顶部
      $('#show-ajax-return-msg').text(msg);
      parent.show();
      goToTheTop();
   }
   //ajax获取短信记录
   function getData(page,pageSize){
      var searchPhone = $("#search_phone_sent_list").val()
      $.ajax({
         type: "post",
         url: ajaxUrl,
         dataType:"json",
         data: {
            action: "get_sms_sent_list",
            search_phone: searchPhone,
            page:page,
            page_size:pageSize
         },
         success: function(response) {
            console.log(response);
            var list = response.data.list;
            var html = '';
            var status = '';
            if (!response.success) {
               alert(response.data.msg);
               return;
            }
            //填充短信记录表格
            $.each(list, function(i, item) {
               status = item['status'] !== '1' ? '成功':'失败';
               html += '<tr>';
               if (item['status'] === '1') {
                  html += '<tr class="table-warning">';
               }
               html += '<td>' +item['phone']+'</td>';
               html += '<td>' +item['verify_code']+'</td>';
               html += '<td>' +item['template_id']+'</td>';
               html += '<td>' + status +'</td>';
               html += '<td style="overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">' +item['response']+'</td>';
               html += '<td>' +item['send_date']+'</td>';
               html += '</tr>';
            });
            $('#more_list').append(html);
            if (page <= 1) {
               $("#record_previous_page").attr('disabled','disabled').addClass('disabled');
            }
            if (!response.data.hasNext){
               $("#record_next_page").attr('disabled','disabled').addClass('disabled');
            }
         }
      });
   }

});