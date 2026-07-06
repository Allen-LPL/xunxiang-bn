# 礼品卡
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_giftcard_card` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `name` char(60) NOT NULL DEFAULT '' COMMENT '名称',
  `category_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分类id{plugins_giftcard_card_category}',
  `data_type` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '数据类型（0钱包充值, 1优惠券兑换）',
  `generate_type` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '生成类型（0纯数字, 1数字加字母）',
  `prefix` char(60) NOT NULL DEFAULT '' COMMENT '礼品卡前缀',
  `secret_value` char(230) NOT NULL DEFAULT '' COMMENT '卡密数据',
  `card_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '卡总数',
  `card_exchange_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '卡兑换总数',
  `batch_data` text COMMENT '批次数据（json存储）',
  `note` char(255) NOT NULL DEFAULT '' COMMENT '备注信息',
  `is_enable` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否启用（0否, 1是）',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `category_id` (`category_id`),
  KEY `data_type` (`data_type`),
  KEY `secret_value` (`secret_value`),
  KEY `card_count` (`card_count`),
  KEY `card_exchange_count` (`card_exchange_count`),
  KEY `is_enable` (`is_enable`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='礼品卡 - 礼品卡';

# 礼品卡分类
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_giftcard_card_category` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `pid` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '父id',
  `icon` char(255) NOT NULL DEFAULT '' COMMENT 'icon图标',
  `name` char(60) NOT NULL DEFAULT '' COMMENT '名称',
  `sort` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `is_enable` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否启用（0否，1是）',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `name` (`name`),
  KEY `is_enable` (`is_enable`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='礼品卡分类 - 礼品卡';

# 礼品卡密
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_giftcard_card_secret` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `card_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '礼品卡id',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `batch_id` char(60) NOT NULL DEFAULT '' COMMENT '生成批次id',
  `data_type` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '数据类型（0钱包充值, 1优惠券兑换）',
  `secret_key` char(60) NOT NULL DEFAULT '' COMMENT '卡密key',
  `secret_value` char(255) NOT NULL DEFAULT '' COMMENT '卡密数据',
  `use_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '使用状态（0未使用, 1已使用）',
  `use_data` text COMMENT '使用数据（json存储）',
  `is_exchange` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否兑换（0否, 1是）',
  `exchange_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '兑换时间',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `card_id` (`card_id`),
  KEY `user_id` (`user_id`),
  KEY `data_type` (`data_type`),
  KEY `secret_key` (`secret_key`),
  KEY `secret_value` (`secret_value`),
  KEY `is_exchange` (`is_exchange`),
  KEY `exchange_time` (`exchange_time`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='礼品卡密 - 礼品卡';

# 礼品卡日志
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_giftcard_card_secret_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `card_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '礼品卡id',
  `card_secret_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '礼品卡密id',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `client_ip` char(200) NOT NULL DEFAULT '' COMMENT '客户端ip',
  `os` char(20) NOT NULL DEFAULT '' COMMENT '操作系统',
  `browser` char(20) NOT NULL DEFAULT '' COMMENT '浏览器',
  `client` text COMMENT '客户端详情信息',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `card_id` (`card_id`),
  KEY `card_secret_id` (`card_secret_id`),
  KEY `user_id` (`user_id`),
  KEY `client_ip` (`client_ip`),
  KEY `os` (`os`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='礼品卡日志 - 礼品卡';