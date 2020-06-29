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
class TencentWordpressSMSOptions
{
    //使用全局密钥
    const GLOBAL_KEY = 0;
    //使用自定义密钥
    const CUSTOM_KEY = 1;
    //验证码默认有效时间
    const DEFAULT_EXPIRED = 5;
    //模板参数不包含过期时间
    const NOT_EXPIRED_TIME = 0;
    //模版参数包含过期时间
    const HAS_EXPIRED_TIME = 1;
    //评论需要验证手机号
    const COMMENT_NEED_PHONE = 1;
    //发文章需要验证手机号
    const POST_NEED_PHONE = 1;


    private $secretID;
    private $customKey;
    private $secretKey;
    private $SDKAppID;
    private $sign;
    private $templateID;
    private $hasExpireTime;
    private $codeExpired;
    private $commentNeedPhone;
    private $postNeedPhone;


    public function __construct($customKey = self::GLOBAL_KEY, $secretID = '', $secretKey = '', $SDKAppID = '', $sign = '', $templateID = '', $hasExpiredTime = self::NOT_EXPIRED_TIME, $codeExpired = self::DEFAULT_EXPIRED, $commentNeedPhone = self::COMMENT_NEED_PHONE, $postNeedPhone = self::POST_NEED_PHONE)
    {
        $this->customKey = $customKey;
        $this->secretID = $secretID;
        $this->secretKey = $secretKey;
        $this->SDKAppID = $SDKAppID;
        $this->sign = $sign;
        $this->templateID = $templateID;
        $this->hasExpireTime = $hasExpiredTime;
        $this->codeExpired = $codeExpired;
        $this->commentNeedPhone = $commentNeedPhone;
        $this->postNeedPhone = $postNeedPhone;
    }

    /**
     * 获取全局的配置项
     */
    public function getCommonOptions()
    {
        return get_option(TENCENT_WORDPRESS_COMMON_OPTIONS);
    }


    public function setSecretID($secretID)
    {
        if ( empty($secretID) ) {
            throw new \Exception('secretID不能为空');
        }
        $this->secretID = $secretID;
    }

    public function setCustomKey($customKey)
    {
        if ( !in_array($customKey, array(self::GLOBAL_KEY, self::CUSTOM_KEY)) ) {
            throw new \Exception('自定义密钥传参错误');
        }
        $this->customKey = intval($customKey);
    }

    public function setSecretKey($secretKey)
    {
        if ( empty($secretKey) ) {
            throw new \Exception('secretKey不能为空');
        }
        $this->secretKey = $secretKey;
    }

    public function setSDKAppID($SDKAppID)
    {
        if ( empty($SDKAppID) ) {
            throw new \Exception('secretKey不能为空');
        }
        $this->SDKAppID = $SDKAppID;
    }

    public function setSign($sign)
    {
        if ( empty($sign) ) {
            throw new \Exception('secretKey不能为空');
        }
        $this->sign = $sign;
    }

    public function setTemplateID($templateID)
    {
        if ( empty($templateID) ) {
            throw new \Exception('secretKey不能为空');
        }
        $this->templateID = $templateID;
    }

    public function setHasExpiredTime($hasExpiredTime)
    {
        $this->hasExpireTime = intval($hasExpiredTime);
    }

    public function setCodeExpired($codeExpired)
    {
        if ( empty($codeExpired) ) {
            throw new \Exception('验证码有效时间不能为空');
        }
        if ( $codeExpired > 360 ) {
            $codeExpired = 360;
        }
        if ( $codeExpired < 1 ) {
            $codeExpired = 1;
        }
        $this->codeExpired = strval(ceil($codeExpired));
    }

    public function setCommentNeedPhone($commentNeedPhone)
    {
        $this->commentNeedPhone = intval($commentNeedPhone);
    }

    public function setPostNeedPhone($postNeedPhone)
    {
        $this->postNeedPhone = intval($postNeedPhone);
    }


    public function getSecretID()
    {
        $commonOptions = $this->getCommonOptions();
        if ( $this->customKey === self::GLOBAL_KEY && isset($commonOptions['secret_id']) ) {
            $this->secretID = $commonOptions['secret_id'] ?: '';
        }
        return $this->secretID;
    }

    public function getSecretKey()
    {
        $commonOptions = $this->getCommonOptions();
        if ( $this->customKey === self::GLOBAL_KEY && isset($commonOptions['secret_key']) ) {
            $this->secretKey = $commonOptions['secret_key'] ?: '';
        }
        return $this->secretKey;
    }

    public function getSDKAppID()
    {
        return $this->SDKAppID;
    }

    public function getSign()
    {
        return $this->sign;
    }

    public function getTemplateID()
    {
        return $this->templateID;
    }

    public function getHasExpiredTime()
    {
        return $this->hasExpireTime;
    }

    public function getCodeExpired()
    {
        return $this->codeExpired;
    }

    public function getCommentNeedPhone()
    {
        return $this->commentNeedPhone;
    }

    public function getPostNeedPhone()
    {
        return $this->postNeedPhone;
    }

    public function getCustomKey()
    {
        return $this->customKey;
    }
}