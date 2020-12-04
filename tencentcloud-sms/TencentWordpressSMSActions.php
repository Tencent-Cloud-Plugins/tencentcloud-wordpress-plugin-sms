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

namespace TencentWordpressSMS;
// 导入 SMS 的 client
use TencentCloud\Sms\V20190711\SmsClient;

// 导入要请求接口对应的 Request 类
use TencentCloud\Sms\V20190711\Models\SendSmsRequest;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Common\Credential;
use TencentWordpressPluginsSettingActions;
use \WP_Error;
use \WP_User;

class TencentWordpressSMSActions
{
    const TABLE_NAME = 'tencent_wordpress_sms_sent_records';
    //短信发送成功
    const STATUS_SUCCESS = 0;
    //短信发送失败
    const STATUS_FAIL = 1;
    //短信验证码已被使用
    const STATUS_INVALID = 2;

    //该手机号未绑定用户
    const UNBIND_ERROR_CODE = 100001;
    //手机号已绑定其他用户
    const PHONE_BIND_ERROR_CODE = 100002;
    //该手机号频繁发送验证码
    const PHONE_FREQUENTLY_CODE = 100003;

    const PLUGIN_TYPE = 'sms';

    private static $wpdb;
    private static $errorCodeDesc = [
        'FailedOperation.TemplateIncorrectOrUnapproved' => '该模版ID未审批或请求的参数个数与该模版ID不匹配',
        'InvalidParameterValue.IncorrectPhoneNumber' => '手机号格式错误',
        'AuthFailure.SecretIdNotFound' => '请检查Secret Id是否填写正确，注意前后不得有空格。',
        'AuthFailure.SignatureFailure' => '请检查Secret Key是否填写正确，注意前后不得有空格。',
        'AuthFailure.SignatureExpire' => '签名过期。本地时间和腾讯云服务器时间相差不得超过五分钟，请检查本地时间是否和北京标准时间同步',
        'UnauthorizedOperation.SmsSdkAppidVerifyFail' => '请检查SDK APP ID是否填写正确。',
        'FailedOperation.SignatureIncorrectOrUnapproved' => '签名填写错误或者签名未审批。',
        'FailedOperation.InsufficientBalanceInSmsPackage' => '套餐包余量不足，请购买套餐包。',
        'FailedOperation.PhoneNumberInBlacklist' => '手机号在黑名单库中，通常是用户退订或者命中运营商黑名单导致的。',
        'FailedOperation.PhoneNumberOnBlacklist	' => '手机号在黑名单库中，通常是用户退订或者命中运营商黑名单导致的。',
        'LimitExceeded.PhoneNumberOneHourLimit' => '单个手机号1小时内下发短信条数超过设定的上限，可自行到腾讯云控制台调整短信频率限制策略。',
        'LimitExceeded.PhoneNumberThirtySecondLimit' => '单个手机号30秒内下发短信条数超过设定的上限，可自行到腾讯云控制台调整短信频率限制策略。',
    ];

    public function __construct()
    {
        self::getWpDbObject();
    }

    public static function getTableName()
    {
        return self::$wpdb->prefix.self::TABLE_NAME;
    }

    /**
     * 插件初始化
     */
    public static function initPlugin()
    {
        static::createSMSSentRecordsTable();
        static::addToPluginCenter();
        self::requirePluginCenterClass();
        // 第一次开启插件则生成一个全站唯一的站点id，保存在公共的option中
        TencentWordpressPluginsSettingActions::setWordPressSiteID();
        $staticData = self::getTencentCloudWordPressStaticData('activate');
        TencentWordpressPluginsSettingActions::sendUserExperienceInfo($staticData);
    }

    /**
     * 禁用插件
     */
    public static function disablePlugin()
    {
        self::requirePluginCenterClass();
        TencentWordpressPluginsSettingActions::disableTencentWordpressPlugin(TENCENT_WORDPRESS_SMS_SHOW_NAME);
        $staticData = self::getTencentCloudWordPressStaticData('deactivate');
        TencentWordpressPluginsSettingActions::sendUserExperienceInfo($staticData);
    }

    /**
     * 卸载插件
     */
    public static function uninstallPlugin()
    {
        self::requirePluginCenterClass();
        delete_option( TENCENT_WORDPRESS_SMS_OPTIONS );
        $tableName = self::getTableName();
        if (self::$wpdb->get_var("SHOW TABLES LIKE '{$tableName}'") === $tableName) {
            $sql = "DROP TABLE {$tableName};";
            self::$wpdb->query($sql);
        }
        TencentWordpressPluginsSettingActions::deleteTencentWordpressPlugin(TENCENT_WORDPRESS_SMS_SHOW_NAME);
        $staticData = self::getTencentCloudWordPressStaticData('uninstall');
        TencentWordpressPluginsSettingActions::sendUserExperienceInfo($staticData);
    }

    /**
     * 引入插件中心类
     */
    public static function requirePluginCenterClass()
    {
        require_once TENCENT_WORDPRESS_PLUGINS_COMMON_DIR . 'TencentWordpressPluginsSettingActions.php';
    }


    public static function getTencentCloudWordPressStaticData($action)
    {
        self::requirePluginCenterClass();
        $staticData['action'] = $action;
        $staticData['plugin_type'] = self::PLUGIN_TYPE;
        $staticData['data']['site_id'] = TencentWordpressPluginsSettingActions::getWordPressSiteID();
        $staticData['data']['site_url'] = TencentWordpressPluginsSettingActions::getWordPressSiteUrl();
        $staticData['data']['site_app'] = TencentWordpressPluginsSettingActions::getWordPressSiteApp();
        $SMSOptions = self::getSMSOptionsObject();
        $staticData['data']['uin'] = TencentWordpressPluginsSettingActions::getUserUinBySecret($SMSOptions->getSecretID(), $SMSOptions->getSecretKey());
        $staticData['data']['cust_sec_on'] = $SMSOptions->getCustomKey() === $SMSOptions::CUSTOM_KEY ? 1:2;
        $data['data']['others'] = json_encode(array('sms_appid'=>$SMSOptions->getSDKAppID()));
        return $staticData;
    }


    /**
     * 加入插件中心
     */
    public static function addToPluginCenter()
    {
        self::requirePluginCenterClass();
        $plugin = array(
            'plugin_name' => TENCENT_WORDPRESS_SMS_SHOW_NAME,
            'plugin_dir' => TENCENT_WORDPRESS_SMS_BASENAME,
            'nick_name' => '腾讯云短信（SMS）插件',
            'href' => "admin.php?page=TencentWordpressSMSSettingPage",
            'activation' => TencentWordpressPluginsSettingActions::ACTIVATION_INSTALL,
            'status' => TencentWordpressPluginsSettingActions::STATUS_OPEN,
            'download_url' => ''
        );
        TencentWordpressPluginsSettingActions::prepareTencentWordressPluginsDB($plugin);
    }

    /**
     * 初始化插件中心设置页面
     */
    public function initCommonSettingPage()
    {
        self::requirePluginCenterClass();
        if ( class_exists('TencentWordpressPluginsSettingActions') ) {
            TencentWordpressPluginsSettingActions::init();
        }
    }

    /**
     *
     */
    public static function getWpDbObject()
    {
        self::$wpdb = $GLOBALS['wpdb'];
    }

    /**
     * 插件初始化建立记录表
     */
    public static function createSMSSentRecordsTable()
    {
        $tableName = self::getTableName();
        if ( self::$wpdb->get_var("SHOW TABLES LIKE '{$tableName}'") !== $tableName ) {
            $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
			`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			`phone` varchar(32) NOT NULL DEFAULT '',
			`verify_code` varchar(32) NOT NULL DEFAULT '',
			`template_params` text NOT NULL DEFAULT '',
			`template_id` varchar(32) NOT NULL DEFAULT '',
			`response` text NOT NULL DEFAULT '',
			`status` varchar(32) NOT NULL DEFAULT '',
			`send_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    /**
     * 获取配置对象
     * @return TencentWordpressSMSOptions
     */
    public static function getSMSOptionsObject()
    {
        $SMSOptions = get_option(TENCENT_WORDPRESS_SMS_OPTIONS);
        if ( $SMSOptions instanceof TencentWordpressSMSOptions ) {
            return $SMSOptions;
        }
        return new TencentWordpressSMSOptions();
    }

    /**
     * 参数过滤
     * @param $key
     * @param string $default
     * @return string|void
     */
    public function filterPostParam($key, $default = '')
    {
        return isset($_POST[$key]) ? sanitize_text_field($_POST[$key]) : $default;
    }

    /**
     * 参数过滤
     * @param $key
     * @param string $default
     * @return string|void
     */
    public function filterGetParam($key, $default = '')
    {
        return isset($_GET[$key]) ? sanitize_text_field($_GET[$key]) : $default;
    }

    /**
     * 使用手机号检索用户
     * @param $username
     * @param $rawUsername
     * @param $strict
     *
     * @return mixed
     */
    public function allowPhoneLogin($username, $rawUsername, $strict)
    {
        $resetWay = $this->filterPostParam('reset_way');
        if ( $resetWay !== 'phone' ) {
            return $username;
        }
        if ( empty($rawUsername) || !self::isPhoneNumber($rawUsername) ) {
            return $username;
        }
        $args = array(
            'meta_key' => 'user_phone',
            'meta_value' => $rawUsername,
        );
        $user = get_users($args);
        if ( !empty($user[0]) ) {
            return $user[0]->user_login;
        }
        return $username;
    }

    /**
     * 使用手机号和短信验证码登录
     * @param $error
     * @param $username
     * @param $pwd
     *
     * @return mixed|WP_Error
     */
    public function authenticatePhoneSMSCode($error, $username, $pwd)
    {
        $phone = $this->filterPostParam('user_phone');
        $verifyCode = $this->filterPostParam('verify_code');
        $userBindPhone = $this->filterPostParam('need_bind_phone', 'unbind');
        //非手机号和验证码登录直接返回
        if ( empty($verifyCode) || !self::isPhoneNumber($phone) ) {
            return $error;
        }
        //通过手机号查找用户
        $args = array(
            'meta_key' => 'user_phone',
            'meta_value' => $phone,
        );
        $phoneFindUser = get_users($args);
        if ( !empty($phoneFindUser) ) {
            $phoneFindUser = $phoneFindUser[0];
        }
        //通过用户名和手机号都未找到用户
        if ( !($phoneFindUser instanceof WP_User) && !($error instanceof WP_User) ) {
            return ($error instanceof WP_Error) ?
                $error : new WP_Error('no_user_used_that_phone', '该手机号未绑定用户.');
        }
        $currentUser = ($phoneFindUser instanceof WP_User) ? $phoneFindUser : $error;
        //验证码校验
        $DBVerifyCode = $this->getRecentVerifyCode($phone);
        if ( empty($DBVerifyCode) || $DBVerifyCode->verify_code !== $verifyCode ) {
            return new WP_Error(
                'invalid_phone_verify_code',
                __('无效的验证码.')
            );
        }
        if ( $userBindPhone === 'bind' ) {
            if ( ($phoneFindUser instanceof WP_User) && $phoneFindUser->ID !== $error->ID ) {
                return new WP_Error('phone_has_been_bind', __('该手机号已绑定其他用户.'));
            }

            $this->userBindPhone($phone, $currentUser->ID);
        }
        $this->loseCodeEfficacy($DBVerifyCode->id);
        return $currentUser;
    }

    /**
     * 查询该手机号最近发送成功的验证码
     * @param $phone
     *
     * @param $recentTime
     * @return mixed
     */
    private function getRecentVerifyCode($phone, $recentTime = 0)
    {
        $SMSOptions = self::getSMSOptionsObject();
        $codeExpired = intval($SMSOptions->getCodeExpired());
        if ( $recentTime > 0 ) {
            $codeExpired = $recentTime;
        }

        $dateStart = date('Y-m-d H:i:s', time() - $codeExpired * 60);
        $dateEnd = date('Y-m-d H:i:s');
        $status = self::STATUS_SUCCESS;
        $tableName = self::getTableName();
        $query = "SELECT `id`,`verify_code` FROM `{$tableName}` WHERE `status`=%d AND `phone`= %s AND `send_date` BETWEEN %s AND %s ORDER BY `id` DESC";
        $result = self::$wpdb->get_row(self::$wpdb->prepare($query, $status, $phone, $dateStart, $dateEnd));
        if ( empty($result) ) {
            return $result;
        }
        return $result;
    }

    /**
     * 发文章前判断是否验证了手机号
     */
    public function authenticatedPhoneBeforeSavePost()
    {
        $SMSOptions = self::getSMSOptionsObject();
        if ( $SMSOptions->getPostNeedPhone() !== TencentWordpressSMSOptions::POST_NEED_PHONE ) {
            return true;
        }

        $hasPhone = self::userHasAuthenticatedPhone();
        if ( !$hasPhone ) {
            $error = new WP_Error(
                'need_authenticated_phone',
                '请前往个人资料页验证手机号后再操作.<a href="' . get_edit_profile_url() . '"> >>> 验证手机号</a>'
            );
            wp_die($error, '请前往个人资料页验证手机号后再操作', array('back_link' => true));
        }
        return true;
    }

    /**
     * 在用户资料页面添加手机号字段的html
     * @param $user
     */
    public function profileExtraPhoneFieldsHtml($user)
    {
        $userPhone = ($user instanceof WP_User) ? get_the_author_meta('user_phone', $user->ID) : '';
        $ajaxUrl = admin_url('admin-ajax.php');
        $display = 'table-row';
        echo '<h3>绑定手机号</h3>
				<table class="form-table">
				    <tr>
					    <th><label for="user_phone">手机号</label></th>
						<td><input type="text" autocomplete="off" id="user_phone"';
        if ( $userPhone ) {
            echo 'disabled';
        }
        echo ' name="user_phone" placeholder="请输入有效手机号" size="25" value="' . $userPhone . '">';
        if ( $userPhone ) {
            echo '<button type="button" class="button" id="rebind_user_phone">重新绑定</button>';
            $display = 'none';
        }
        echo '</td></tr>';
        echo '<tr id="verify_code_fields" style="display: ' . $display . ';">
    <th><label for="verify_code">短信验证码</label></th>
    <td>
        <input type="text" id="verify_code" size="12" autocomplete="off" name="verify_code"/>
        <button type="button" class="button" id="get_verify_code_btn">获取验证码</button>
    </td>
 		<input type="hidden" id="hidden_ajax_url" value="' . $ajaxUrl . '" data-check_phone_used="check"></tr></table>';
    }

    /**
     * 登录表单
     */
    public function loginFormAddFields()
    {
        $ajaxUrl = admin_url('admin-ajax.php');

        echo '<div id="phone-verify-fields" style="display: none;">
            <p>
				<label for="user_phone">手机号</label>
				<input type="text" name="user_phone" id="user_phone" class="input" size="20" autocapitalize="off" />
			</p>
			<p><label for="verify_code">验证码</label></p>
				<p><input type="text" name="verify_code" id="verify_code" style="width: 104px;" autocapitalize="off" />
				<button type="button" id="get_verify_code_btn" class="button">发送验证码</button>
			</p>
</div>';

        echo '<p id="smsForm" style="padding-top: 50px;text-align: center;">
                <span style="display: block;padding-bottom: 16px;">------其它登录方式------</span>
                    <span style="margin-right: 20px;cursor:pointer;" id="login-way-phone" >手机号</span>
                    <span id="login-way-name-or-email" style=" cursor:pointer;display: none;">账号密码</span>
                    <input type="hidden" name="login_way" id="login_way" value="phone">
            </p>
			  <input type="hidden" id="hidden_ajax_url" value="' . $ajaxUrl . '" data-check_phone_used="login">
			  <input type="hidden" id="need_bind_phone" name="need_bind_phone" value="unbind" />';
    }

    /**
     * 找回密码表单
     */
    public function lossPasswordFormAddFields()
    {
        $ajaxUrl = admin_url('admin-ajax.php');
        echo '<div id="special_filed" style="display: none;">
            <p><label for="verify_code">短信验证码</label></p>
				<p><input type="text"  name="verify_code" style="width: 104px;" id="verify_code"  autocapitalize="off" />
				<button type="button" class="button" id="get_verify_code_btn">获取验证码</button>
			</p>';

        echo '<input type="hidden" id="hidden_ajax_url" value="' . $ajaxUrl . '" data-check_phone_used="lossPassword">
              <input type="hidden" id="need_bind_phone" name="need_bind_phone" value="unbind" /></div>';
        echo '<p id="smsForm" style="text-align: center;">
                <span style="display: block;padding-bottom: 16px;">------其它找回方式------</span>
                    <span style="margin-right: 20px;cursor:pointer;" id="reset-way-phone" >手机号</span>
                    <span id="reset-way-name-or-email" style=" cursor:pointer;display: none;">账号密码</span>
                    <input type="hidden" name="reset_way" id="reset_way" value="phone">
            </p>';
    }

    /**
     * 更新用户手机号
     * @param $userID
     * @return bool
     */
    public function updateUserPhoneMetaFields($userID)
    {
        if ( $this->filterGetParam('action') === 'register' ) {
            $cap = user_can($userID, 'edit_user', $userID);
        } else {
            $cap = current_user_can('edit_user', $userID);
        }

        if ( !$cap ) {
            $error = new WP_Error(
                'no_authenticated_edit_user',
                __('无权编辑用户信息.')
            );
            wp_die($error, '无权编辑用户信息', array('back_link' => true));
        }

        $phone = $this->filterPostParam('user_phone');
        $verifyCode = $this->filterPostParam('verify_code');

        //手机号可以为空的处理
        if ( empty($phone) && empty($verifyCode) ) {
            return true;
        }
        if ( !self::isPhoneNumber($phone) ) {
            $error = new WP_Error(
                'invalid_phone_verify_code',
                __('无效的手机号.')
            );
            wp_die($error, '无效的手机号', array('back_link' => true));
        }
        $dbVerifyCode = $this->getRecentVerifyCode($phone);
        if ( $dbVerifyCode->verify_code !== $verifyCode ) {
            $error = new WP_Error(
                'invalid_phone_verify_code',
                __('无效的验证码.')
            );
            wp_die($error, '无效的验证码', array('back_link' => true));
        }
        $this->userBindPhone($phone, $userID);
        $this->loseCodeEfficacy($dbVerifyCode->id);
        return true;
    }

    /**
     * 验证码失效
     * @param $id
     */
    private function loseCodeEfficacy($id)
    {
        $tableName = self::getTableName();
        $sql = "UPDATE `{$tableName}` SET `status`=%d WHERE `id`=%d";
        self::$wpdb->query(self::$wpdb->prepare($sql, self::STATUS_INVALID, $id));
    }

    /**
     * 使用手机号重置密码
     * @param $errors
     * @param $userData
     * @return bool|int|string|WP_Error
     */
    public function resetPasswordByPhone($errors, $userData)
    {
        $phone = $this->filterPostParam('user_login');
        $verifyCode = $this->filterPostParam('verify_code');
        $resetWay = $this->filterPostParam('reset_way');
        //不是使用手机号跳过
        if ( $resetWay !== 'phone' || !self::isPhoneNumber($phone) || empty($verifyCode) ) {
            return false;
        }
        if ( $errors->has_errors() ) {
            wp_die($errors, $errors->get_error_messages(), array('back_link' => true));
        }
        if ( !($userData instanceof WP_User) ) {
            $error = new WP_Error(
                'no_user_used_that_phone',
                __('该手机号未绑定用户.')
            );
            wp_die($error, '该手机号未绑定用户', array('back_link' => true));
        }
        //验证码校验
        $DBVerifyCode = $this->getRecentVerifyCode($phone);
        if ( empty($DBVerifyCode) || $DBVerifyCode->verify_code !== $verifyCode ) {
            $error = new WP_Error(
                'invalid_phone_verify_code',
                __('无效的验证码.')
            );
            wp_die($error, '无效的验证码', array('back_link' => true));
        }
        //让验证码失效
        $this->loseCodeEfficacy($DBVerifyCode->id);
        $key = get_password_reset_key($userData);
        if ( is_wp_error($key) ) {
            wp_die($key, $key->get_error_messages(), array('back_link' => true));
        }
        $resetUrl = network_site_url("wp-login.php?action=rp&key={$key}&login=" . rawurlencode($userData->user_login), 'login');
        wp_redirect($resetUrl, 302);
        exit;
    }

    /**
     * 屏蔽评论输入框
     * @param $comment_fields
     *
     * @return mixed
     */
    public function authenticatedPhoneBeforeCommentTextArea($comment_fields)
    {
        $SMSOptions = self::getSMSOptionsObject();
        if ( $SMSOptions->getCommentNeedPhone() !== TencentWordpressSMSOptions::COMMENT_NEED_PHONE ) {
            return $comment_fields;
        }
        $commentHtml = '<p class="comment-form-comment"><label for="comment">评论</label> <a href="' . wp_login_url() . '">请先登录后再评论</a></p>';
        if ( !is_user_logged_in() ) {
            $comment_fields['comment'] = $commentHtml;
            $comment_fields['author'] = '';
            $comment_fields['email'] = '';
            $comment_fields['url'] = '';
            $comment_fields['cookies'] = '';
        }
        return $comment_fields;
    }

    /**
     * 屏蔽评论提交按钮
     * @param $submitButton
     *
     * @return string
     */
    public function authenticatedPhoneBeforeCommentSubmitButton($submitButton)
    {
        $SMSOptions = self::getSMSOptionsObject();
        if ( is_user_logged_in() || $SMSOptions->getCommentNeedPhone() !== TencentWordpressSMSOptions::COMMENT_NEED_PHONE ) {
            return $submitButton;
        }
        if ( !self::userHasAuthenticatedPhone() ) {
            $submitButton = '';
        }
        return $submitButton;
    }

    /**
     * 登录且未验证手机号的评论框
     * @param $submitField
     * @param $args
     * @return string
     */
    public function authenticatedPhoneCommentForm($submitField, $args)
    {
        $SMSOptions = self::getSMSOptionsObject();
        if ( !is_user_logged_in() || $SMSOptions->getCommentNeedPhone() !== TencentWordpressSMSOptions::COMMENT_NEED_PHONE ) {
            return $submitField;
        }
        if ( self::userHasAuthenticatedPhone() ) {
            return $submitField;
        }
        $ajaxUrl = admin_url('admin-ajax.php');
        $html = '
        <p>
           <label for="phone">手机号</label>
           <input type="text" id="phone" name="phone">
           </p>
        <p>
        <label for="get_verify_code_btn">验证码</label>
          <input type="text" name="verify_code" style="width: 55%;display: inline-block">
          <button type="button"  id="get_verify_code"  style="width: 40%;display: inline-block;
          color: #0071a1;
          border-color: #0071a1;
          background: #f3f5f6;
          text-decoration:none;
          vertical-align: top;" >获取验证码</button>
         </p>
         <input type="hidden" id="hidden_ajax_url" value="' . $ajaxUrl . '">';
        return $html . $submitField;
    }

    /**
     *    评论提交入库前验证
     */
    public function authenticatedPhoneBeforeCommentPost()
    {
        $SMSOptions = self::getSMSOptionsObject();
        if ( $SMSOptions->getCommentNeedPhone() !== TencentWordpressSMSOptions::COMMENT_NEED_PHONE ) {
            return true;
        }
        $phone = $this->filterPostParam('phone');
        $verifyCode = $this->filterPostParam('verify_code');

        //已验证过手机号的跳过
        if ( self::userHasAuthenticatedPhone() ) {
            return true;
        }
        if ( !self::isPhoneNumber($phone) || empty($verifyCode) ) {
            $error = new WP_Error(
                'need_authenticated_phone',
                __('请填写正确的手机号和验证码.')
            );
            wp_die($error, '请填写正确的手机号和验证码', array('back_link' => true));
        }

        $DBVerifyCode = $this->getRecentVerifyCode($phone);
        if ( empty($DBVerifyCode) || $DBVerifyCode->verify_code !== $verifyCode ) {
            $error = new WP_Error(
                'invalid_phone_verify_code',
                __('请填写正确的手机号和验证码.')
            );
            wp_die($error, '请填写正确的手机号和验证码', array('back_link' => true));
        }
        $this->userBindPhone($phone);
        $this->loseCodeEfficacy($DBVerifyCode->id);
        return true;
    }


    /**
     * 测试发送短信验证码
     */
    public function testSendVerifyCode()
    {
        try {
            if ( !current_user_can('manage_options') ) {
                wp_send_json_error(array('msg' => '当前用户无权限'));
            }
            $SMSOptions = new TencentWordpressSMSOptions();
            $SMSOptions->setCustomKey($this->filterPostParam('custom_key'));
            $SMSOptions->setSecretID($this->filterPostParam('secret_id'));
            $SMSOptions->setSecretKey($this->filterPostParam('secret_key'));
            $SMSOptions->setSDKAppID($this->filterPostParam('sdk_app_id'));
            $SMSOptions->setSign($this->filterPostParam('sign'));
            $SMSOptions->setTemplateID($this->filterPostParam('template_id'));
            $SMSOptions->setHasExpiredTime($this->filterPostParam('has_expire_time'));
            $SMSOptions->setCodeExpired($this->filterPostParam('code_expired'));
            $phone = $this->filterPostParam('user_phone');
            if ( empty($phone) || !self::isPhoneNumber($phone) ) {
                wp_send_json_error(array('msg' => '手机号格式错误'));
            }
            if ( !empty($this->getRecentVerifyCode($phone, 1)) ) {
                wp_send_json_error(array('msg' => '该手机号操作过于频繁，请一分钟后再试'));
            }

            $verifyCode = self::SMSCodeGenerator();
            $templateParams = array($verifyCode);
            if ( $SMSOptions->getHasExpiredTime() === TencentWordpressSMSOptions::HAS_EXPIRED_TIME ) {
                $templateParams[] = $SMSOptions->getCodeExpired();
            }

            $response = self::sendSMS(array($phone), $SMSOptions, $templateParams);
            if ( !in_array($response['SendStatusSet'][0]['Fee'], [1, 2]) || $response['SendStatusSet'][0]['Code'] !== 'Ok' ) {
                $errorCode = $response['errorCode'] ?: $response['SendStatusSet'][0]['Code'];
                $msg = self::$errorCodeDesc[$errorCode];
                wp_send_json_error(array('msg' => '发送失败：' . $msg));
            }
            wp_send_json_success(array('msg' => '发送成功'));
        } catch (\Exception $exception) {
            wp_send_json_error(array('msg' => '发送失败：' . $exception->getMessage()));
        }
    }

    /**
     * 通过的手机号
     * @return string|void
     * @throws \Exception
     */
    private function phoneCheck()
    {
        $checkPhoneUsed = $this->filterPostParam('check_phone_used');
        $phone = $this->filterPostParam('user_phone');
        if ( empty($phone) || !self::isPhoneNumber($phone) ) {
            throw new \Exception('手机号错误');
        }
        if ( !empty($this->getRecentVerifyCode($phone, 1)) ) {
            throw new \Exception('该手机号操作过于频繁，请一分钟后再试', self::PHONE_FREQUENTLY_CODE);
        }
        if ( $checkPhoneUsed === 'check' ) {
            //手机号是否被使用（绑定用户时）
            if ( self::thatPhoneHasBeenUsed($phone) ) {
                throw new \Exception('该手机号已经绑定其他用户', self::PHONE_BIND_ERROR_CODE);
            }
        } else {
            //手机号是否绑定了注册用户（登录时）
            $args = array(
                'meta_key' => 'user_phone',
                'meta_value' => $phone,
            );
            $user = get_users($args);
            if ( empty($user) ) {
                throw new \Exception('该手机号未绑定用户', self::UNBIND_ERROR_CODE);
            }
        }
        return $phone;
    }


    /**
     * 发送短信验证码
     */
    public function getVerifyCode()
    {
        try {
            $SMSOptions = self::getSMSOptionsObject();
            if ( empty($SMSOptions->getSecretID()) || empty($SMSOptions->getSecretKey()) ) {
                wp_send_json_error(array('msg' => '短信系统未配置，不能使用短信功能，请联系管理员.'));
            }
            if ( empty($SMSOptions->getSDKAppID()) || empty($SMSOptions->getSign()) || empty($SMSOptions->getTemplateID()) ) {
                wp_send_json_error(array('msg' => '短信系统未配置，不能使用短信功能，请联系管理员.'));
            }
            //获取检测通过的手机号
            $phone = $this->phoneCheck();
            $templateID = $SMSOptions->getTemplateID();
            $verifyCode = self::SMSCodeGenerator();
            $templateParams = array($verifyCode);
            if ( $SMSOptions->getHasExpiredTime() === TencentWordpressSMSOptions::HAS_EXPIRED_TIME ) {
                $templateParams[] = $SMSOptions->getCodeExpired();
            }
            $response = self::sendSMS(array($phone), $SMSOptions, $templateParams);
            $status = self::STATUS_SUCCESS;
            if ( !in_array($response['SendStatusSet'][0]['Fee'], [1, 2]) || $response['SendStatusSet'][0]['Code'] !== 'Ok' ) {
                $status = self::STATUS_FAIL;
            }
            //记录发送结果
            $result = $this->insertSMSSentRecord($phone, $verifyCode, $templateParams, $templateID, $response, $status);
            if ( $status === self::STATUS_FAIL ) {
                $msg = $response['errorMessage'] ?: $response['SendStatusSet'][0]['Message'];
                wp_send_json_error(array('msg' => '短信系统错误：' . $msg, 'phone' => $phone));
            }
            if ( !$result ) {
                wp_send_json_error(array('msg' => '数据库服务错误', 'phone' => $phone));
            }
            wp_send_json_success(array('phone' => $phone, 'msg' => '发送成功'));
        } catch (\Exception $exception) {
            wp_send_json_error(array('msg' => $exception->getMessage(), 'errorCode' => $exception->getCode()));
        }
    }

    /**
     * 判断这个手机号是否已经被使用过
     * @param $phone
     *
     * @return bool
     */
    public static function thatPhoneHasBeenUsed($phone)
    {
        $currentUser = wp_get_current_user();
        $args = array(
            'meta_key' => 'user_phone',
            'meta_value' => $phone,
        );
        $phoneGetUser = get_users($args);
        if ( empty($phoneGetUser[0]->ID) ) {
            return false;
        }
        return $currentUser->ID !== $phoneGetUser[0]->ID;
    }

    /**
     * 发送短信
     * @param $phones
     * @param TencentWordpressSMSOptions $SMSOptions
     * @param array $templateParams
     * @return array|mixed
     */
    public static function sendSMS($phones, $SMSOptions, $templateParams = array())
    {
        try {
            $cred = new Credential($SMSOptions->getSecretID(), $SMSOptions->getSecretKey());
            $client = new SmsClient($cred, "ap-shanghai");
            $req = new SendSmsRequest();

            $req->SmsSdkAppid = $SMSOptions->getSDKAppID();
            $req->Sign = $SMSOptions->getSign();
            $req->ExtendCode = "0";

            foreach ($phones as &$phone) {
                $preFix = substr($phone, 0, 3);
                if ( !in_array($preFix, array('+86')) ) {
                    $phone = '+86' . $phone;
                }
            }
            /*最多不要超过200个手机号*/
            $req->PhoneNumberSet = $phones;
            /* 国际/港澳台短信 senderid: 国内短信填空 */
            $req->SenderId = "";
            $req->TemplateID = $SMSOptions->getTemplateID();
            $req->TemplateParamSet = $templateParams;
            $resp = $client->SendSms($req);
            return json_decode($resp->toJsonString(), JSON_OBJECT_AS_ARRAY);
        } catch (TencentCloudSDKException $e) {
            return array('requestId' => $e->getRequestId(), 'errorCode' => $e->getErrorCode(), 'errorMessage' => $e->getMessage());
        }
    }

    /**
     * 加载js脚本
     */
    public function loadMyScriptEnqueue()
    {
        wp_register_script('SMS_front_user_script', TENCENT_WORDPRESS_SMS_JS_DIR . 'front_user_script.js', array('jquery'), '2.1', true);
        wp_enqueue_script('SMS_front_user_script');

        wp_register_script('SMS_back_admin_script', TENCENT_WORDPRESS_SMS_JS_DIR . 'back_admin_script.js', array('jquery'), '2.1', true);
        wp_enqueue_script('SMS_back_admin_script');

    }

    /**
     * 加载css
     * @param $hookSuffix
     */
    public function loadCSSEnqueue($hookSuffix)
    {
        //只在后台配置页引入
        if (strpos($hookSuffix,'page_TencentWordpressSMSSettingPage') !== false){
            wp_register_style('SMS_back_admin_css', TENCENT_WORDPRESS_SMS_CSS_DIR . 'bootstrap.min.css');
            wp_enqueue_style('SMS_back_admin_css');
        }
    }

    /**
     * 加载js脚本
     */
    public function loadCommentScriptEnqueue()
    {
        wp_register_script('SMS_comment_user_script', TENCENT_WORDPRESS_SMS_JS_DIR . 'comment_script.js', array('jquery'), '2.1', true);
        wp_enqueue_script('SMS_comment_user_script');
    }

    /**
     * 当前用户是否验证了手机号
     * @return bool
     */
    public static function userHasAuthenticatedPhone()
    {
        if ( !is_user_logged_in() ) {
            return false;
        }
        $user = wp_get_current_user();
        $userPhone = get_user_meta($user->ID, 'user_phone', true);
        return !empty($userPhone);
    }

    /**
     * 验证是否为手机号
     * @param $phone
     *
     * @return bool
     */
    public static function isPhoneNumber($phone)
    {
        return preg_match("/^1[3-9]\d{9}$/", $phone) === 1;
    }


    /**
     * 生成随机验证码
     * @param int $length
     *
     * @return string
     */
    public static function SMSCodeGenerator($length = 4)
    {
        $nums = range(0, 9);
        shuffle($nums);
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $index = mt_rand(0, 9);
            $code .= $nums[$index];
        }
        return $code;
    }

    /**
     * 插入短信发送记录
     * @param $phone
     * @param $verifyCode
     * @param $templateParams
     * @param $templateID
     * @param $response
     * @param $status
     * @return mixed
     */
    private function insertSMSSentRecord($phone, $verifyCode, $templateParams, $templateID, $response, $status)
    {
        $date = date('Y-m-d H:i:s');
        if ( !is_string($templateParams) ) {
            $templateParams = wp_json_encode($templateParams, JSON_UNESCAPED_SLASHES);
        }
        if ( !is_string($response) ) {
            $response = wp_json_encode($response, JSON_UNESCAPED_SLASHES);
        }
        $tableName = self::getTableName();

        $sql = "INSERT INTO `{$tableName}` (`phone`, `verify_code`, `template_params`, `template_id`, `response`, `status`, `send_date`) VALUES (%s, %s, %s, %s, %s, %d, %s);";
        return self::$wpdb->query(self::$wpdb->prepare(
            $sql, $phone, $verifyCode, $templateParams, $templateID, $response, $status, $date
        ));
    }

    /**
     * 配置页
     */
    public function pluginSettingPage()
    {
        require_once 'TencentWordpressSMSSettingPage.php';
        TencentWordpressPluginsSettingActions::addTencentWordpressCommonSettingPage();
        add_submenu_page('TencentWordpressPluginsCommonSettingPage', '短信', '短信', 'manage_options', 'TencentWordpressSMSSettingPage', 'TencentWordpressSMSSettingPage');
    }

    /**
     * 获取短信发送记录
     */
    public function getSMSSentList()
    {
        if ( !current_user_can('manage_options') ) {
            wp_send_json_error(array('msg' => '当前用户无权限'));
        }
        $phone = $this->filterPostParam('search_phone');
        $page = $this->filterPostParam('page', 1);
        $pageSize = $this->filterPostParam('page_size', 10);
        if ( $page < 1 || $page > 999999 ) {
            $page = 1;
        }
        if ( $pageSize < 1 || $pageSize > 50 ) {
            $page = 10;
        }
        $pageSize = intval($pageSize);
        $page = intval($page);

        $skip = ($page - 1) * $pageSize;

        if ( !empty($phone) && !is_numeric($phone) ) {
            wp_send_json_error(array('msg' => '手机号格式错误'));
        }
        $tableName = self::getTableName();
        if ( empty($phone) ) {
            $sql = "SELECT * FROM `{$tableName}` ORDER BY `id` DESC LIMIT {$skip},{$pageSize}";
            $result = self::$wpdb->get_results(self::$wpdb->prepare($sql));
            //统计总条数
            $sql = "SELECT COUNT(`id`) as `count` FROM `{$tableName}`";
            $count = self::$wpdb->get_row(self::$wpdb->prepare($sql));
        } else {
            $sql = "SELECT * FROM `{$tableName}` WHERE `phone` LIKE '%s' ORDER BY `id` DESC LIMIT {$skip},{$pageSize}";
            $result = self::$wpdb->get_results(self::$wpdb->prepare($sql, $phone . '%'));
            $sql = "SELECT COUNT(`id`) as `count` FROM `{$tableName}` WHERE `phone` LIKE '%s'";
            $count = self::$wpdb->get_row(self::$wpdb->prepare($sql, $phone . '%'));
        }

        $return = array('list' => $result, 'totalNum' => 0, 'totalPage' => 0, 'hasNext' => false);
        if ( !$result ) {
            wp_send_json_success($return);
        }
        $return['totalNum'] = (int)$count->count;
        $return['hasNext'] = $count->count > $pageSize * $page;
        $return['totalPage'] = intval(ceil($count->count / $pageSize));
        wp_send_json_success($return);
    }


    /**
     * 保存插件配置
     */
    public function updateSMSSettings()
    {
        try {
            if ( !current_user_can('manage_options') ) {
                wp_send_json_error(array('msg' => '当前用户无权限'));
            }
            $SMSOptions = new TencentWordpressSMSOptions();
            $SMSOptions->setCustomKey($this->filterPostParam('custom_key'));
            $SMSOptions->setSecretID($this->filterPostParam('secret_id'));
            $SMSOptions->setSecretKey($this->filterPostParam('secret_key'));
            $SMSOptions->setSDKAppID($this->filterPostParam('sdk_app_id'));
            $SMSOptions->setSign($this->filterPostParam('sign'));
            $SMSOptions->setTemplateID($this->filterPostParam('template_id'));
            $SMSOptions->setHasExpiredTime($this->filterPostParam('has_expire_time'));
            $SMSOptions->setCodeExpired($this->filterPostParam('code_expired'));
            $SMSOptions->setCommentNeedPhone($this->filterPostParam('comment_need_phone', TencentWordpressSMSOptions::COMMENT_NEED_PHONE));
            $SMSOptions->setPostNeedPhone($this->filterPostParam('post_need_phone', TencentWordpressSMSOptions::POST_NEED_PHONE));
            self::requirePluginCenterClass();
            $staticData = self::getTencentCloudWordPressStaticData('save_config');
            TencentWordpressPluginsSettingActions::sendUserExperienceInfo($staticData);
            update_option(TENCENT_WORDPRESS_SMS_OPTIONS, $SMSOptions, true);
            wp_send_json_success(array('msg' => '保存成功'));
        } catch (\Exception $exception) {
            wp_send_json_error(array('msg' => $exception->getMessage()));
        }
    }

    /**
     * 添加设置按钮
     * @param $links
     * @param $file
     * @return mixed
     */
    public function pluginSettingPageLinkButton($links, $file)
    {
        if ( $file === TENCENT_WORDPRESS_SMS_BASENAME ) {
            $links[] = '<a href="admin.php?page=TencentWordpressSMSSettingPage">设置</a>';
        }
        return $links;
    }

    /**
     * @param $phone
     * @param int $userID
     */
    private function userBindPhone($phone, $userID = 0)
    {
        if ( $userID == 0 ) {
            $user = wp_get_current_user();
            $userID = $user->ID;
        }
        update_user_meta($userID, 'user_phone', $phone);
    }

}




