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
/**
 * Plugin Name: tencentcloud-plugin-sms
 * Plugin URI: https://openapp.qq.com/Wordpress/sms.html
 * Author URI: https://openapp.qq.com/Wordpress/sms.html
 * Description: 通过腾讯云短信服务使网站支持手机号登录,通过手机号+短信验证码找回密码等功能。
 * Version: 1.0.0
 * Author: 腾讯云
 *
*/
date_default_timezone_set('Asia/Shanghai');

defined('TENCENT_WORDPRESS_SMS_VERSION')||define( 'TENCENT_WORDPRESS_SMS_VERSION', '1.0.0' );
defined('TENCENT_WORDPRESS_SMS_OPTIONS')||define( 'TENCENT_WORDPRESS_SMS_OPTIONS', 'tencent_wordpress_sms_options' );
defined('TENCENT_WORDPRESS_SMS_DIR')||define( 'TENCENT_WORDPRESS_SMS_DIR', plugin_dir_path( __FILE__ ) );
defined('TENCENT_WORDPRESS_SMS_BASENAME')||define( 'TENCENT_WORDPRESS_SMS_BASENAME', plugin_basename(__FILE__));
defined('TENCENT_WORDPRESS_SMS_URL')||define( 'TENCENT_WORDPRESS_SMS_URL', plugins_url( 'tencentcloud-plugin-sms' ) );
defined('TENCENT_WORDPRESS_SMS_JS_DIR')||define( 'TENCENT_WORDPRESS_SMS_JS_DIR', TENCENT_WORDPRESS_SMS_URL . DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR );
defined('TENCENT_WORDPRESS_SMS_CSS_DIR')||define( 'TENCENT_WORDPRESS_SMS_CSS_DIR', TENCENT_WORDPRESS_SMS_URL . DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR );
//插件中心常量
defined('TENCENT_WORDPRESS_SMS_NAME')||define( 'TENCENT_WORDPRESS_SMS_NAME', 'tencentcloud-plugin-sms');
defined('TENCENT_WORDPRESS_COMMON_OPTIONS')||define( 'TENCENT_WORDPRESS_COMMON_OPTIONS', 'tencent_wordpress_common_options' );
defined('TENCENT_WORDPRESS_SMS_SHOW_NAME')||define( 'TENCENT_WORDPRESS_SMS_SHOW_NAME', 'tencentcloud-plugin-sms');
defined('TENCENT_WORDPRESS_PLUGINS_COMMON_URL')||define('TENCENT_WORDPRESS_PLUGINS_COMMON_URL', TENCENT_WORDPRESS_SMS_URL .DIRECTORY_SEPARATOR. 'common' . DIRECTORY_SEPARATOR);
defined('TENCENT_WORDPRESS_PLUGINS_COMMON_DIR')||define('TENCENT_WORDPRESS_PLUGINS_COMMON_DIR', TENCENT_WORDPRESS_SMS_DIR . 'common' . DIRECTORY_SEPARATOR);
defined('TENCENT_WORDPRESS_PLUGINS_COMMON_CSS_URL')||define('TENCENT_WORDPRESS_PLUGINS_COMMON_CSS_URL', TENCENT_WORDPRESS_PLUGINS_COMMON_URL . 'css' . DIRECTORY_SEPARATOR);

if (!is_file(TENCENT_WORDPRESS_SMS_DIR.'vendor/autoload.php')) {
    wp_die('缺少依赖文件，请确保安装了腾讯云sdk','缺少依赖文件',array('back_link'=>true));
}
require_once 'vendor/autoload.php';

use TencentWordpressSMS\TencentWordpressSMSActions;
$SMSPluginActions = new TencentWordpressSMSActions();

register_activation_hook(__FILE__, array($SMSPluginActions, 'initPlugin'));
register_deactivation_hook(__FILE__,array($SMSPluginActions, 'disablePlugin'));
//插件中心初始化
add_action('init',array($SMSPluginActions, 'initCommonSettingPage'));

//添加插件设置页面
add_action('admin_menu',  array($SMSPluginActions, 'pluginSettingPage'));
// 插件列表加入设置按钮
add_filter('plugin_action_links', array($SMSPluginActions, 'pluginSettingPageLinkButton'), 10, 2);
//发布文章时的钩子
add_action( 'save_post_post', array($SMSPluginActions,'authenticatedPhoneBeforeSavePost'));
//用户添加手机号字段
add_action( 'show_user_profile', array($SMSPluginActions,'profileExtraPhoneFieldsHtml') );
add_action( 'edit_user_profile', array($SMSPluginActions,'profileExtraPhoneFieldsHtml') );
add_action( 'user_new_form', array($SMSPluginActions,'profileExtraPhoneFieldsHtml') );
//保存用户手机号钩子
add_action( 'personal_options_update', array($SMSPluginActions,'updateUserPhoneMetaFields') );
add_action( 'edit_user_profile_update', array($SMSPluginActions,'updateUserPhoneMetaFields') );
add_action( 'user_register', array($SMSPluginActions,'updateUserPhoneMetaFields') );
//找回密码
add_action('lostpassword_post',array($SMSPluginActions,'resetPasswordByPhone'),100,2);
add_action('lostpassword_form',array($SMSPluginActions,'lossPasswordFormAddFields'));
//评论的钩子
add_filter('comment_form_fields',array($SMSPluginActions,'authenticatedPhoneBeforeCommentTextArea'));
add_filter('comment_form_submit_button',array($SMSPluginActions,'authenticatedPhoneBeforeCommentSubmitButton'));
add_action( 'pre_comment_on_post', array($SMSPluginActions,'authenticatedPhoneBeforeCommentPost') );
add_action( 'comment_form_submit_field', array($SMSPluginActions,'authenticatedPhoneCommentForm'),100,2);
//登录时的钩子
add_filter( 'authenticate', array($SMSPluginActions,'authenticatePhoneSMSCode'),101,3);
//只在忘记密码的时候添加
$resetWay = $SMSPluginActions->filterPostParam('reset_way');
$action = $SMSPluginActions->filterGetParam('action');
if( $GLOBALS['pagenow']=== 'wp-login.php'
    && $action ==='lostpassword'
    &&  $resetWay === 'phone') {
	add_filter( 'sanitize_user', array( $SMSPluginActions, 'allowPhoneLogin' ), 10, 3 );
}
add_action('login_form',array($SMSPluginActions,'loginFormAddFields'));
//ajax发送手机短信验证码
add_action('wp_ajax_get_verify_code', array($SMSPluginActions, 'getVerifyCode'));
add_action('wp_ajax_nopriv_get_verify_code', array($SMSPluginActions, 'getVerifyCode'));
//ajax测试发送手机短信验证码
add_action('wp_ajax_test_send_sms_verify_code', array($SMSPluginActions, 'testSendVerifyCode'));
//ajax查询短信发送记录
add_action('wp_ajax_get_sms_sent_list', array($SMSPluginActions, 'getSMSSentList'));
//ajax更新插件设置
add_action('wp_ajax_update_sms_settings', array($SMSPluginActions, 'updateSMSSettings'));
//js脚本引入
add_action( 'admin_enqueue_scripts', array($SMSPluginActions, 'loadMyScriptEnqueue'));
add_action( 'login_enqueue_scripts', array($SMSPluginActions, 'loadMyScriptEnqueue'));
add_action( 'wp_enqueue_scripts', array($SMSPluginActions, 'loadCommentScriptEnqueue'));
