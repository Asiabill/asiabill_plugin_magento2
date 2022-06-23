
Asiabill Magento2 支付插件
=

插件安装
-

1、把Asiabill目录上传到站点app/code目录中

2、打开终端并在 Magento 目录中运行以下命令
```shell
php bin/magento setup:upgrade
php bin/magento cache:flush
php bin/magento cache:clean
```

4、如果您在生产模式下运行 Magento，您还必须编译和部署模块的静态文件。
```shell
php bin/magento setup:di:compile
php bin/magento setup:stat
```

5、设置：Stores -> Configuration -> Sales -> Payment Methods 可以看到：Asiabill Payment

![image](https://files.gitbook.com/v0/b/gitbook-x-prod.appspot.com/o/spaces%2FcSYgMg71VCxeEVhWhVFp%2Fuploads%2FE8Q0btGVbc09yHPtDFOB%2Fmaegnto2-admin-list.png?alt=media&token=62e0e936-0c2e-4c60-9638-04f056fd3551)

__Basic Setting：基础设置__

* Version：版本信息
* Mode：模式
  * test：测试
  * live：正式
* Test Mer No、Test Gateway No、Test Sign Key：测试账户信息，测试模式下使用，默认已设置
* Icons Location：图标显示位置
* Start Log：开启支付数据日志
* Success Order：交易成功更新订单状态
* Failure Order：交易失败更新订单状态
* Outstanding Order：待处理订单更状态
* Webhook：是否接受异步订单回调
* Webhook URL：异步回调通知地址

__Credit Card Payment：信用卡支付__

* Enabled：是否开启
* Title：显示支付方式名称
* Checkout Model：支付模式
  * Asiabill Elements ：内嵌表单模式
  * Asiabill Checkout ：托管模式（重定向）
* Elements Style：内嵌表单样式，单行/双行
* Mer No、Gateway No、Sign Key：账户信息，非测试模式下使用
* Select Card Icons：显示卡种图标
* Payment from Applicable Countries：适用支付国家
* Sort Order排序

__Digital Wallets：电子钱包__

* Alipay、Wechat

__Local Payments：本地支付__

* Crypto、Ebanx、Directpay、Giropay、Ideal、P24、Paysafe Card、Korea card、Kakaopay


信用卡支付
-
1、站内支付模式：在网站内可以填写卡号信息，体验相对友好
![images](https://files.gitbook.com/v0/b/gitbook-x-prod.appspot.com/o/spaces%2FcSYgMg71VCxeEVhWhVFp%2Fuploads%2Fu6jDsfj2jzpgA8kYyKTo%2Fmagento2-inner-payment.png?alt=media&token=7d0a38d4-a645-41c0-b57e-2980a4b286f1)

2、跳转支付模式：页面会跳出当前网站，在Asiabill页面进行输入卡号，支付完成后跳转回网站
![images](https://files.gitbook.com/v0/b/gitbook-x-prod.appspot.com/o/spaces%2FcSYgMg71VCxeEVhWhVFp%2Fuploads%2FJhjGY4FOLbq7UlkjkurH%2Fimage.png?alt=media&token=bd122e1d-42f3-491e-b8b9-2a6319f90671)


测试卡号
-
* 支付成功：4242424242424242
* 支付失败：4000000000009995
* 3D交易：4000002500003155

本地支付
-
需要额外开通才能交易
