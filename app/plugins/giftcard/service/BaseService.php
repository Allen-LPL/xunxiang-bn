<?php
// +----------------------------------------------------------------------
// | ShopXO 国内领先企业级B2C免费开源电商系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2099 http://shopxo.net All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://opensource.org/licenses/mit-license.php )
// +----------------------------------------------------------------------
// | Author: Devil
// +----------------------------------------------------------------------
namespace app\plugins\giftcard\service;

use app\service\PluginsService;

/**
 * 礼品卡 - 基础服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class BaseService
{
    // 基础私有字段
    public static $plugins_config_private_field = [];

    // 基础数据附件字段
    public static $plugins_config_attachment_field = [];

    /**
     * 基础配置信息保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-24
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function BaseConfigSave($params = [])
    {
        return PluginsService::PluginsDataSave(['plugins'=>'giftcard', 'data'=>$params], self::$plugins_config_attachment_field);
    }
    
    /**
     * 基础配置信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-24
     * @desc    description
     * @param   [boolean]          $is_cache    [是否缓存中读取]
     * @param   [boolean]          $is_private  [是否读取隐私字段]
     */
    public static function BaseConfig($is_cache = true, $is_private = true)
    {
        return PluginsService::PluginsData('giftcard', self::$plugins_config_attachment_field, $is_cache);
    }

    /**
     * 后台导航
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-03-07
     * @desc    description
     * @param   [array]           $plugins_config [插件配置]
     */
    public static function AdminNavList($plugins_config = [])
    {
        $lang = MyLang('admin_nav_list');
        return [
            [
                'name'      => $lang['admin'],
                'control'   => 'admin',
                'action'    => 'index',
            ],
            [
                'name'      => $lang['card'],
                'control'   => 'card',
                'action'    => 'index',
            ],
            [
                'name'      => $lang['cardcategory'],
                'control'   => 'cardcategory',
                'action'    => 'index',
            ],
        ];
    }

    /**
     * 静态数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2023-03-26
     * @desc    description
     * @param   [string]          $key [数据key]
     */
    public static function ConstData($key)
    {
        $data = [
            // 礼品卡数据类型
            'card_data_type_list'   => [
                0 => ['value'=>0, 'type'=>'wallet', 'name'=>'余额', 'checked'=>true],
                1 => ['value'=>1, 'type'=>'coupon', 'name'=>'优惠券'],
                2 => ['value'=>2, 'type'=>'points', 'name'=>'积分'],
                3 => ['value'=>3, 'type'=>'goods', 'name'=>'商品'],
            ],
            // 礼品卡生成方式
            'card_generate_type_list'   => [
                0 => ['value'=>0, 'name'=>'纯数字', 'checked'=>true],
                1 => ['value'=>1, 'name'=>'数字+字母'],
            ],
        ];
        return isset($data[$key]) ? $data[$key] : [];
    }

    /**
     * 优惠券列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-06-25
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function CouponList($params = [])
    {
        // 优惠券
        $data_params = [
            'm'      => 0,
            'n'      => 0,
            'where'  => ['is_enable' => 1],
        ];
        $coupon = CallPluginsServiceMethod('coupon', 'CouponService', 'CouponList', $data_params);
        return empty($coupon['data']) ? [] : $coupon['data'];
    }
}
?>