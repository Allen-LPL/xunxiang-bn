# v3.2.6
# 积分商城可兑换的积分加金额
ALTER TABLE `{PREFIX}goods` add `plugins_points_exchange_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '积分商城可兑换加金额' after `plugins_points_exchange_integral`;


































# v3.0.0
# 扫码
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_points_scan` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `name` char(30) NOT NULL DEFAULT '' COMMENT '名称',
  `platform` char(30) NOT NULL DEFAULT 'pc' COMMENT '所属平台（pc PC网站, h5 H5手机网站, ios 苹果APP, android 安卓APP, alipay 支付宝小程序, weixin 微信小程序, baidu 百度小程序, toutiao 头条小程序, qq QQ小程序, kuaishou 快手小程序）',
  `brand_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '品牌id',
  `brand_category_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '品牌分类id',
  `integral` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '赠送积分',
  `qrcode_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '二维码总数',
  `qrcode_use_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '二维码使用总数',
  `batch_data` text COMMENT '批次数据（json存储）',
  `is_enable` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否启用（0否, 1是）',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `platform` (`platform`),
  KEY `integral` (`integral`),
  KEY `qrcode_count` (`qrcode_count`),
  KEY `qrcode_use_count` (`qrcode_use_count`),
  KEY `is_enable` (`is_enable`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='扫码 - 积分商城';

# 扫码二维码
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_points_scan_qrcode` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '扫码用户id',
  `scan_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '扫码id',
  `batch_id` char(60) NOT NULL DEFAULT '' COMMENT '生成批次id',
  `qrcode` char(160) NOT NULL DEFAULT '' COMMENT '二维码标识',
  `is_use` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否使用（0否, 1是）',
  `integral` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '赠送积分',
  `use_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '使用时间',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `scan_id` (`scan_id`),
  KEY `batch_id` (`batch_id`),
  KEY `qrcode` (`qrcode`),
  KEY `is_use` (`is_use`),
  KEY `integral` (`integral`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='扫码二维码列表 - 积分商城';

# 积分商城可兑换的积分
ALTER TABLE `{PREFIX}goods` add `plugins_points_exchange_integral` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '积分商城可兑换的积分' after `fictitious_goods_value`;