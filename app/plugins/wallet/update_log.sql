# v3.3.0
# 更新提现状态
UPDATE `{PREFIX}plugins_wallet_cash` SET `status`=3 WHERE `status`=2;
UPDATE `{PREFIX}plugins_wallet_cash` SET `status`=2 WHERE `status`=1;
# 失败原因
UPDATE `{PREFIX}plugins_wallet_cash_payment` SET `status`=4 WHERE `status`=3;
UPDATE `{PREFIX}plugins_wallet_cash_payment` SET `status`=3 WHERE `status`=2;
UPDATE `{PREFIX}plugins_wallet_cash_payment` SET `status`=2 WHERE `status`=1;
ALTER TABLE `{PREFIX}plugins_wallet_cash_payment` change `reason` `reason` text COMMENT '请求失败原因';
# 新增字段
ALTER TABLE `{PREFIX}plugins_wallet_cash_payment` add `pay_no` char(60) NOT NULL DEFAULT '' COMMENT '支付单号' after `cash_no`;
ALTER TABLE `{PREFIX}plugins_wallet_cash` add `accounts_other_type` char(60) NOT NULL DEFAULT '' COMMENT '账号其他类型（比如 web，mini）' after `bank_username`;



































# 3.2.3
# 钱包提现支付
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_wallet_cash_payment` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `wallet_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '钱包id',
  `cash_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '提现id',
  `cash_no` char(60) NOT NULL DEFAULT '' COMMENT '提现单号',
  `pay_type` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '付款方式（0微信, 1支付宝）',
  `status` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '状态（0待处理, 1已支付, 2已失败, 3已关闭）',
  `reason` char(230) NOT NULL DEFAULT '' COMMENT '请求失败原因',
  `out_order_no` char(160) NOT NULL DEFAULT '' COMMENT '外部订单号',
  `request_params` mediumtext COMMENT '请求参数（数组则json字符串存储）',
  `response_data` mediumtext COMMENT '响应参数（数组则json字符串存储）',
  `tsc` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '耗时时间（单位秒）',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `wallet_id` (`wallet_id`),
  KEY `cash_id` (`cash_id`),
  KEY `cash_no` (`cash_no`),
  KEY `pay_type` (`pay_type`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='钱包提现支付 - 钱包';
# 提现
ALTER TABLE `{PREFIX}plugins_wallet_cash` add `cash_type` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '提现方式（0其他方式, 1微信, 2支付宝）' after `cash_no`;

























# v3.1.4
# 提现
ALTER TABLE `{PREFIX}plugins_wallet_cash` add `commission` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '手续费' after `money`;
















# v2.1.0
# 转账记录
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_wallet_transfer` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `transfer_no` char(60) NOT NULL DEFAULT '' COMMENT '转账单号',
  `send_user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '发起用户id',
  `send_wallet_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '发起钱包id',
  `receive_user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '接收用户id',
  `receive_wallet_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '接收钱包id',
  `money` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '金额',
  `note` char(230) NOT NULL DEFAULT '' COMMENT '备注',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `transfer_no` (`transfer_no`),
  KEY `send_user_id` (`send_user_id`),
  KEY `send_wallet_id` (`send_wallet_id`),
  KEY `receive_user_id` (`receive_user_id`),
  KEY `receive_wallet_id` (`receive_wallet_id`),
  KEY `money` (`money`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='转账记录 - 钱包';

















# v1.3.6
# 付款码
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_wallet_payment_code` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `wallet_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '钱包id',
  `code` char(30) NOT NULL DEFAULT '' COMMENT '付款码',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `wallet_id` (`wallet_id`),
  KEY `user_id` (`user_id`),
  KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='付款码 - 钱包';











# v1.2.6
# 充值记录
ALTER TABLE `{PREFIX}plugins_wallet_recharge` add `operate_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作人id' after `payment_name`;
ALTER TABLE `{PREFIX}plugins_wallet_recharge` add `operate_name` char(30) NOT NULL DEFAULT '' COMMENT '操作人名称' after `operate_id`;
# 日志
ALTER TABLE `{PREFIX}plugins_wallet_log` add `operate_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作人id' after `msg`;
ALTER TABLE `{PREFIX}plugins_wallet_log` add `operate_name` char(30) NOT NULL DEFAULT '' COMMENT '操作人名称' after `operate_id`;