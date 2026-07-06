-- 礼品卡插件升级（2026-07-06）：兑换码唯一性加固
-- 执行前请先确认存量数据无重复卡密：
--   SELECT secret_key, COUNT(*) c FROM `{PREFIX}plugins_giftcard_card_secret` GROUP BY secret_key HAVING c > 1;
-- 如有重复请先人工处理（正常情况下生成算法内嵌自增id、不会重复）。
ALTER TABLE `{PREFIX}plugins_giftcard_card_secret` DROP INDEX `secret_key`, ADD UNIQUE KEY `secret_key` (`secret_key`);
