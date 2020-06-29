<?php
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
use TencentWordpressSMS\TencentWordpressSMSActions;
function TencentWordpressSMSSettingPage()
{
    $ajaxUrl = admin_url('admin-ajax.php');
    $SMSOptions = TencentWordpressSMSActions::getSMSOptionsObject();
    $secretID = $SMSOptions->getSecretID();
    $secretKey = $SMSOptions->getSecretKey();
    $SDKAPPID = $SMSOptions->getSDKAppID();
    $templateID = $SMSOptions->getTemplateID();
    $sign = $SMSOptions->getSign();
    $codeExpired = $SMSOptions->getCodeExpired();
    $commentNeedPhone = $SMSOptions->getCommentNeedPhone();
    $postNeedPhone = $SMSOptions->getPostNeedPhone();
    $hasExpireTime= $SMSOptions->getHasExpiredTime();
    $customKey = $SMSOptions->getCustomKey();

    ?>
    <link rel="stylesheet" href="<?php echo TENCENT_WORDPRESS_SMS_CSS_DIR . 'bootstrap.min.css' ?>">
    <style type="text/css">
        .dashicons {
            vertical-align: middle;
            position: relative;
            right: 30px;
        }
    </style>
    <div id="message" class="updated notice is-dismissible" style="margin-bottom: 1%;margin-left:0;"><p>
            腾讯云短信（SMS）插件启用生效中。</p>
        <button type="button" class="notice-dismiss"><span class="screen-reader-text">忽略此通知。</span></button>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <div class="page-header ">
                <h1 id="forms">腾讯云短信（SMS）插件</h1>
            </div>
            <p>使WordPress支持手机号登录,通过手机号+短信验证码找回密码</p>
        </div>
    </div>
    <div class="alert alert-dismissible alert-success" style="display: none;">
        <button type="button" id="close-ajax-return-msg" class="close" data-dismiss="alert">&times;</button>
        <div id="show-ajax-return-msg">操作成功.</div>
    </div>
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link active" href="javascript:void(0);" id="sub-tab-settings">插件配置</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="javascript:void(0);" data-clicked="0" id="sub-tab-records">短信发送记录</a>
        </li>
    </ul>
    <div id="post-body">
        <div class="postbox">
            <form method="post" id="tencnetcloud-sms-setting-form" action="" data-ajax-url="<?php echo $ajaxUrl ?>">
                <div id="group-settings" class="group" style="display: block;">
                    <div class="inside">
                        <table class="form-table">
                            <tbody>
                            <tr>
                                <th scope="row"><label for="sms-option-custom-key">自定义密钥</label></th>
                                <td>
                                    <div class="custom-control custom-switch div_custom_switch_padding_top">
                                        <input type="checkbox" class="custom-control-input"
                                               id="sms-option-custom-key" <?php if ( $customKey === $SMSOptions::CUSTOM_KEY ) {
                                            echo 'checked';
                                        } ?> >
                                        <label class="custom-control-label"
                                               for="sms-option-custom-key">为该插件配置单独定义的腾讯云密钥</label>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><label for="sms-option-secret-id">SecretId</label></th>
                                <td><input type="password" autocomplete="off"
                                           value="<?php echo $secretID; ?>" <?php if ( $customKey !== $SMSOptions::CUSTOM_KEY ) {
                                        echo 'disabled="disabled"';
                                    } ?>
                                           id="sms-option-secret-id" size="65"><span id="secret_id_type_exchange"
                                                                                     class="dashicons dashicons-hidden"></span>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="sms-option-secret-key">SecretKey</label></th>
                                <td><input type="password" autocomplete="off"
                                           value="<?php echo $secretKey; ?>" <?php if ( $customKey !== $SMSOptions::CUSTOM_KEY ) {
                                        echo 'disabled="disabled"';
                                    } ?>
                                           id="sms-option-secret-key" size="65"><span id="secret_key_type_exchange"
                                                                                      class="dashicons dashicons-hidden"></span>
                                    <p class="description">访问 <a href="https://console.qcloud.com/cam/capi"
                                                                 target="_blank">密钥管理</a>获取
                                        SecretId和SecretKey或通过"新建密钥"创建密钥串</p></td>
                            </tr>

                            <tr>
                                <th scope="row"><label for="sms-option-sdk-appid">SDKAppID</label></th>
                                <td><input type="text" name="sms-option-sdk-appid" autocomplete="off"
                                           value="<?php echo $SDKAPPID; ?>"
                                           id="sms-option-sdk-appid" size="65">
                                    <p class="description">访问<a
                                                href="https://console.cloud.tencent.com/smsv2/app-manage"
                                                target="_blank">应用列表</a>获取
                                        SDKAppID或通过"创建应用"创建SDKAppID</p></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="sms-option-sign">短信签名</label></th>
                                <td><input type="text" name="sms-option-sign" autocomplete="off"
                                           value="<?php echo $sign; ?>"
                                           id="sms-option-sign" size="65">
                                    <p class="description">审核通过的短信签名，不包含【】</p></td>
                            </tr>

                            <tr>
                                <th scope="row"><label for="sms-option-tpl-id">模板ID</label></th>
                                <td><input type="text" autocomplete="off" value="<?php echo $templateID; ?>"
                                           id="sms-option-tpl-id" size="65">
                                    <p class="description">审核通过的模板ID</p></td>
                            </tr>

                            <tr>
                                <th scope="row"><label for="sms-option-tpl-id">验证码有效时间</label></th>
                                <td><input type="text" value="<?php echo $codeExpired ?>" autocomplete="off"
                                           id="sms-option-code-expired" size="65">
                                    <p class="description">单位：分钟，默认5。范围【1 - 360】</p>
                                    <div class="custom-control custom-switch div_custom_switch_padding_top"
                                         style="margin-top: 1%">
                                        <input type="checkbox" class="custom-control-input"
                                               id="has_expire_time" <?php if ( $hasExpireTime === $SMSOptions::HAS_EXPIRED_TIME ) {
                                            echo 'checked';
                                        } ?> >
                                        <label class="custom-control-label"
                                               for="has_expire_time">短信模板参数中包含验证码有效时间</label>
                                    </div>
                                    <p class="description">请与模板中参数个数保持一致，否则将导致短信发送失败。</p></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="sms-option-sign">发送测试</label></th>
                                <td>
                                    <div class="card border-secondary mb-3" style="max-width: 35%">
                                        <div class="card-header">使用上方填写的参数进行测试。(仅测试，不生成发送记录)</div>
                                        <div class="card-body">
                                            <label class="label" for="test_phone">测试手机号：</label>
                                            <p class="card-text">
                                                <input type="text" id="test_phone" size="18" style="margin-right: 1em"/>
                                                <button type="button" class="button"
                                                        id="get_test_verify_code_btn">
                                                    获取验证码
                                                </button>
                                            </p>
                                        </div>
                                    </div>

                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><label for="sms-option-sign">发文章/评论前需验证手机号</label></th>
                                <td>
                                    <div class="custom-control custom-switch div_custom_switch_padding_top">
                                        <input type="checkbox" class="custom-control-input"
                                               id="sms-option-comment-need-phone" <?php if ( $commentNeedPhone === $SMSOptions::COMMENT_NEED_PHONE ) {
                                            echo 'checked';
                                        } ?> >
                                        <label class="custom-control-label"
                                               for="sms-option-comment-need-phone">发评论</label>
                                    </div>

                                    <div class="custom-control custom-switch div_custom_switch_padding_top">
                                        <input type="checkbox" class="custom-control-input"
                                               id="sms-option-post-need-phone" <?php if ( $postNeedPhone === $SMSOptions::POST_NEED_PHONE ) {
                                            echo 'checked';
                                        } ?> >
                                        <label class="custom-control-label" for="sms-option-post-need-phone">发文章</label>
                                    </div>
                                </td>
                            </tr>

                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="group-records" class="group" style="display: none;">
                    <nav class="navbar navbar-expand-lg navbar-light bg-light">
                        <span class="navbar-brand">短信发送记录</span>
                        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarColor03"
                                aria-controls="navbarColor03" aria-expanded="false" aria-label="Toggle navigation">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="collapse navbar-collapse" id="navbarColor03" style="max-width: 35%;">
                            <form class="form-inline my-2 my-lg-0">
                                <input class="form-control mr-sm-2" type="text" id="search_phone_sent_list">
                                <button class="btn btn-secondary" style="width: 5rem;" type="button"
                                        id="search_sms_sent_list_button">搜索
                                </button>
                            </form>
                        </div>
                    </nav>
                    <div class="inside table-responsive">
                        <table id="sms-record-list-table" class="table table-hover" style="table-layout:fixed">
                            <tbody id="more_list">
                            <tr class="table-primary">
                                <th>手机号</th>
                                <th>验证码</th>
                                <th>模版ID</th>
                                <th>是否发送成功</th>
                                <th style="width: 25%">短信接口返回</th>
                                <th>发送时间</th>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <div style="float: right;">
                        <ul class="pagination">
                            <li class="page-item disabled" id="record_previous_page" data-current-page="1">
                                <a class="page-link" href="javascript:void(0);">&laquo;</a>
                            </li>
                            <li class="page-item active">
                                <a class="page-link" href="javascript:void(0);">1</a>
                            </li>
                            <li class="page-item" id="record_next_page">
                                <a class="page-link" href="javascript:void(0);">&raquo;</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </form>
        </div>
        <button type="button" id="tencnetcloud-sms-setting-update-button" class="btn btn-primary">保存设置</button>
        <div style="text-align: center;flex: 0 0 auto;margin-top: 3rem;">
            <a href="https://openapp.qq.com/Wordpress/sms.html" target="_blank">文档中心</a> | <a href="https://github.com/Tencent-Cloud-Plugins/tencentcloud-wordpress-plugin-sms" target="_blank">GitHub</a> | <a
                    href="https://support.qq.com/product/164613" target="_blank">意见反馈</a>
        </div>
    </div>
<?php
}