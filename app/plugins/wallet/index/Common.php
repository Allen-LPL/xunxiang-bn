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
namespace app\plugins\wallet\index;

use app\service\PaymentService;
use app\plugins\wallet\service\BaseService;
use app\plugins\wallet\service\WalletService;

/**
 * 钱包 - 公共
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Common
{
    // 公共属性参数数据
    protected $props_params;

    // 插件配置信息
    protected $plugins_config;

    // 用户钱包
    protected $user_wallet;

    /**
     * 构造方法
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-07-17
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function __construct($params = [])
    {
        // 公共属性参数数据
        $this->props_params = $params;

        // 应用配置
        $plugins_config = BaseService::BaseConfig();
        $this->plugins_config = $plugins_config['data'];
        MyViewAssign('plugins_config', $this->plugins_config);

        // 充值配置数据
        $recharge_config_data = BaseService::RechargeConfigData($this->plugins_config);
        MyViewAssign('recharge_config_data', $recharge_config_data);

        // 默认支付方式
        MyViewAssign('default_payment_id', PaymentService::BuyDefaultPayment());

        // 发起支付 - 支付方式
        MyViewAssign('buy_payment_list', BaseService::BuyPaymentList($this->plugins_config));

        // 用户钱包
        $user_wallet = empty($this->user['id']) ? null : WalletService::UserWallet($this->user['id']);

        // 用户钱包错误信息
        $wallet_error = empty($user_wallet) ? '请先登录' : (($user_wallet['code'] == 0) ? '' : $user_wallet['msg']);
        MyViewAssign('wallet_error', $wallet_error);

        // 所有ajax请求校验用户钱包状态
        if(IS_AJAX && !empty($wallet_error))
        {
            exit(json_encode(DataReturn($wallet_error, -50)));
        }

        // 用户钱包信息
        $this->user_wallet = empty($user_wallet) ? [] : $user_wallet['data'];
        MyViewAssign('user_wallet', $this->user_wallet);

        // 导航
        MyViewAssign('wallet_nav_list', BaseService::UserCenterNav($this->plugins_config));

        // 是否加载条形码组件
        MyViewAssign('is_load_barcode', 1);

        // 用户中心主导航名称
        MyViewAssign('user_center_main_title', '我的钱包');
    }

    /**
     * 属性读取处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-04-23
     * @desc    description
     * @param   [string]          $name [属性名称]
     * @return  [mixed]                 [属性的数据]
     */
    public function __get($name)
    {
        return (!empty($this->props_params) && is_array($this->props_params) && isset($this->props_params[$name])) ? $this->props_params[$name] : null;
    }
}
?>