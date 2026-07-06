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
use app\service\AdminService;
use app\plugins\wallet\admin\Common;
use app\plugins\wallet\service\BaseService;
use app\plugins\wallet\service\StatisticalService;

/**
 * 钱包插件 - 管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Admin extends Common
{
    /**
     * 首页
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        // 支付方式
        MyViewAssign('payment_list', PaymentService::PaymentList(['is_enable'=>1, 'is_open_user'=>1]));

        // 静态数据
        MyViewAssign('recharge_give_type_list', BaseService::ConstData('recharge_give_type_list'));
        MyViewAssign('cash_type_list', BaseService::ConstData('cash_type_list'));

        // 统计数据
        MyViewAssign('statistical', StatisticalService::StatisticalData());

        MyViewAssign('data', $this->plugins_config);
        return MyView('../../../plugins/wallet/view/admin/admin/index');
    }

    /**
     * 编辑页面
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function SaveInfo($params = [])
    {
        // 支付方式
        MyViewAssign('payment_list', PaymentService::PaymentList(['is_enable'=>1, 'is_open_user'=>1]));

        // 静态数据
        MyViewAssign('recharge_give_type_list', BaseService::ConstData('recharge_give_type_list'));
        MyViewAssign('cash_type_list', BaseService::ConstData('cash_type_list'));

        MyViewAssign('data', $this->plugins_config);
        return MyView('../../../plugins/wallet/view/admin/admin/saveinfo');
    }

    /**
     * 数据保存
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function Save($params = [])
    {
        // 钱包修改密码校验
        if(!empty($params['wallet_edit_money_password']))
        {
            // 当前登录的管理员是否为超管
            $admin = AdminService::LoginInfo();
            if(!isset($admin['id']) || $admin['id'] != 1)
            {
                return DataReturn('钱包修改密码仅超管有权限修改', -1);
            }

            // 密码加密
            $params['wallet_edit_money_password'] = BaseService::WalletMoneyEditPassword($params['wallet_edit_money_password']);
        } else {
            // 使用原来设置的密码覆盖
            $ret = BaseService::BaseConfig();
            if(!empty($this->plugins_config['wallet_edit_money_password']))
            {
                $params['wallet_edit_money_password'] = $this->plugins_config['wallet_edit_money_password'];
            }
        }

        // 数据保存
        return BaseService::BaseConfigSave($params);
    }
}
?>