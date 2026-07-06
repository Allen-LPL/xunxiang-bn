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
namespace app\plugins\wallet\admin;

use app\service\PaymentService;
use app\plugins\wallet\admin\Common;
use app\plugins\wallet\service\RechargeService;
use app\plugins\wallet\service\PayService;

/**
 * 钱包 - 充值管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Recharge extends Common
{
    /**
     * 列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-03-15T23:51:50+0800
     * @param   [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        // 发起支付 - 支付方式
        $pay_wparams = [
            'where' => [
                ['is_enable', '=', 1],
                ['payment', 'in', MyConfig('shopxo.under_line_list')],
            ],
        ];
        MyViewAssign('payment_list', PaymentService::BuyPaymentList($pay_wparams));
        return MyView('../../../plugins/wallet/view/admin/recharge/index');
    }

    /**
     * 详情
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-03-15T23:51:50+0800
     * @param   [array]          $params [输入参数]
     */
    public function Detail($params = [])
    {
        return MyView('../../../plugins/wallet/view/admin/recharge/detail');
    }

    /**
     * 订单支付
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-28
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Pay($params = [])
    {
        $params['operate_id'] = $this->admin['id'];
        $params['operate_name'] = $this->admin['username'];
        return PayService::OrderPaymentUnderLinePay($params);
    }

    /**
     * 充值纪录删除
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-14
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Delete($params = [])
    {
        $params['plugins_config'] = $this->plugins_config;
        return RechargeService::RechargeDelete($params);
    }
}
?>