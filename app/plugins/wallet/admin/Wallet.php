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

use app\service\UserService;
use app\plugins\wallet\admin\Common;
use app\plugins\wallet\service\WalletService;
use app\plugins\wallet\service\BaseService;

/**
 * 钱包插件 - 钱包管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Wallet extends Common
{
    /**
     * 列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        return MyView('../../../plugins/wallet/view/admin/wallet/index');
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
        return MyView('../../../plugins/wallet/view/admin/wallet/detail');
    }

    /**
     * 钱包编辑页面
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-05
     * @desc    description
     * @param    [array]          $params [输入参数]
     */
    public function SaveInfo($params = [])
    {
        // 静态数据
        MyViewAssign('wallet_status_list', BaseService::ConstData('wallet_status_list'));
        return MyView('../../../plugins/wallet/view/admin/wallet/saveinfo');
    }

    /**
     * 钱包余额修改页面
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-05
     * @desc    description
     * @param    [array]          $params [输入参数]
     */
    public function ChangeInfo($params = [])
    {
        // 用户钱包
        $user_wallet = empty($params['id']) ? null : WalletService::WalletInfo($params['id']);
        MyViewAssign('user_wallet', $user_wallet);
        // 用户信息
        $user_info = empty($user_wallet) ? null : UserService::GetUserViewInfo($user_wallet['user_id']);
        MyViewAssign('user_info', $user_info);

        // 静态数据
        MyViewAssign('wallet_money_type_list', BaseService::ConstData('wallet_money_type_list'));
        MyViewAssign('wallet_operate_type_list', BaseService::ConstData('wallet_operate_type_list'));
        return MyView('../../../plugins/wallet/view/admin/wallet/changeinfo');
    }

    /**
     * 钱包编辑
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-06
     * @desc    description
     * @param    [array]          $params [输入参数]
     */
    public function Save($params = [])
    {
        $params['operate_id'] = $this->admin['id'];
        $params['operate_name'] = $this->admin['username'];
        $params['plugins_config'] = $this->plugins_config;
        return WalletService::WalletEdit($params);
    }

    /**
     * 钱包余额修改
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-06
     * @desc    description
     * @param    [array]          $params [输入参数]
     */
    public function Change($params = [])
    {
        $params['operate_id'] = $this->admin['id'];
        $params['operate_name'] = $this->admin['username'];
        $params['plugins_config'] = $this->plugins_config;
        return WalletService::WalletChange($params);
    }
}
?>