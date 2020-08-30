# 腾讯云短信插件

## 1.插件介绍
> tencentcloud-sms插件是一款腾讯云研发的，提供给WordPress站长使用的官方插件。使WordPress支持手机号登录,通过手机号+短信验证码找回密码等功能

| 标题       | 内容                                                         |
| ---------- | ------------------------------------------------------------ |
| 中文名称     | 腾讯云短信（SMS）插件                                         |
| 英文名称   | tencentcloud-sms                                       |
| 最新版本   | v1.0.0 (2020.06.22)                                           |
| 适用平台 | [WordPress](https://wordpress.org/)                             |
| 适用产品 | [腾讯云短信（SMS）](https://cloud.tencent.com/product/sms)      |
| 文档中心   | [春雨文档中心](https://openapp.qq.com/docs/Wordpress/sms.html) |
| 主创团队   | 腾讯云中小企业产品中心（SMB Product Center of Tencent Cloud）    |

## 2.功能特性

- 支持在个人资料页绑定手机号
- 支持登录页面进行绑定手机号
- 支持在评论时对用户手机号进行验证
- 支持验证手机号后才能发布文章
- 支持登录页面使用手机号+验证码登录
- 支持找回密码页面使用手机号+验证码重置密码

## 3.安装指引

### 3.1.部署方式一：通过GitHub部署安装

> 1. git clone https://github.com/Tencent-Cloud-Plugins/tencentcloud-wordpress-plugin-sms.git
> 2. 复制tencentcloud-sms文件夹到 WordPress安装路径/wp-content/plugins/文件夹里面

### 3.2.部署方式二：通过WordPress插件中心安装
> 1. 前往[WordPress插件中心](https://wordpress.org/plugins/tencentcloud-sms)点击下载
> 2. 你的WordPress站点后台=》插件=》安装插件。点击左上角的"上传插件"按钮，选择上一步下载的zip安装包

### 3.3.部署方式三：通过WordPress站点后台安装
> 1. 你的WordPress站点后台=》插件=》安装插件。在页面搜索框输入tencentcloud-sms
> 2. 点击"安装"按钮，就会自动下载安装插件

## 4.使用指引

### 4.1.界面功能介绍

![](./images/sms1.png)

![](../images/Wordpress/sms2.png)
> 后台配置页面。配置介绍请参考下方的[名词解释](#_4-2-名词解释)

![](./images/sms4.png)
> 选择使用手机号的登录框

![](./images/sms5.png)
> 用户绑定手机号的操作框

![](./images/sms6.png)
> 未绑定手机号的评论框

![](./images/sms7.png)
> 后台短信发送记录页


### 4.2.名词解释
- **自定义密钥：** 插件提供统一密钥管理，既可在多个腾讯云插件之间共享SecretId和SecretKey，也可为插件配置单独定义的腾讯云密钥。
- **Secret ID：** 在[腾讯云API密钥管理](https://console.cloud.tencent.com/cam/capi)上申请的标识身份的 SecretId。
- **Secret Key：** 在[腾讯云API密钥管理](https://console.cloud.tencent.com/cam/capi)上申请的与SecretId对应的SecretKey。
- **SDKAppID：** 在[腾讯云短信应用管理](https://console.cloud.tencent.com/smsv2/app-manage)上创建到应用ID。
- **短信签名：** 在[腾讯云短信签名管理](https://console.cloud.tencent.com/smsv2/csms-sign)审核通过的短信签名，不包含【】。
- **模板ID：** 在[腾讯云短信正文模板管理](https://console.cloud.tencent.com/smsv2/csms-template)审核通过的模板ID。


## 5.获取入口

| 插件入口          | 链接                                                         |
| ----------------- | ------------------------------------------------------------ |
| GitHub            | [link](https://github.com/Tencent-Cloud-Plugins/tencentcloud-wordpress-plugin-sms)   |
| WordPress插件中心 |  [link](https://wordpress.org/plugins/tencentcloud-sms)   |


## 6.FAQ

> 暂无

## 7.GitHub版本迭代记录

### 7.2 tencentcloud-wordpress-plugin-sms v1.0.1
- 验证码过期时间错误判断Bug修复
- windows环境下样式加载问题

### 7.1 tencentcloud-wordpress-plugin-sms v1.0.0
- 支持在个人资料页绑定手机号
- 支持登录页面进行绑定手机号
- 支持在评论时对用户手机号进行验证
- 支持验证手机号后才能发布文章
- 支持登录页面使用手机号+验证码登录
- 支持找回密码页面使用手机号+验证码

---
本项目由腾讯云中小企业产品中心建设和维护，了解与该插件使用相关的更多信息，请访问[春雨文档中心](https://openapp.qq.com/docs/Wordpress/sms.html) 

请通过[咨询建议](https://support.qq.com/products/164613) 向我们提交宝贵意见。