# 扫码二维码
DROP TABLE IF EXISTS `{PREFIX}plugins_points_scan`;
# 扫码二维码列表
DROP TABLE IF EXISTS `{PREFIX}plugins_points_scan_qrcode`;
# 积分商城可兑换的积分
ALTER TABLE `{PREFIX}goods` DROP `plugins_points_exchange_integral`;
# 积分商城可兑换的金额
ALTER TABLE `{PREFIX}goods` DROP `plugins_points_exchange_price`;