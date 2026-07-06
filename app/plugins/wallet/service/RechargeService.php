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
namespace app\plugins\wallet\service;

use think\facade\Db;
use app\plugins\wallet\service\BaseService;
use app\plugins\wallet\service\WalletService;

/**
 * 充值服务层
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class RechargeService
{
    /**
     * 充值订单创建
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-29
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function RechargeCreate($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user',
                'error_msg'         => MyLang('user_info_incorrect_tips'),
            ],
            [
                'checked_type'      => 'fun',
                'key_name'          => 'money',
                'checked_data'      => 'CheckPrice',
                'error_msg'         => '请输入有效的充值金额',
            ],
            [
                'checked_type'      => 'min',
                'key_name'          => 'money',
                'checked_data'      => 0.01,
                'error_msg'         => '请输入大于0的充值金额',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 是否存在钱包基础配置数据
        if(empty($params['plugins_config']))
        {
            $base = BaseService::BaseConfig();
            $params['plugins_config'] = $base['data'];
        }

        // 是否开启充值
        if(!isset($params['plugins_config']['is_enable_recharge']) || $params['plugins_config']['is_enable_recharge'] != 1)
        {
            return DataReturn('未开启充值功能、请联系管理员！', -1);
        }

        // 用户钱包信息
        if(empty($params['user_wallet']))
        {
            $user_wallet = WalletService::UserWallet($params['user']['id']);
            if($user_wallet['code'] != 0)
            {
                return $$user_wallet;
            }
            $params['user_wallet'] = $user_wallet['data'];
        }

        // 添加
        $data = [
            'recharge_no'   => date('YmdHis').GetNumberCode(6),
            'wallet_id'     => $params['user_wallet']['id'],
            'user_id'       => $params['user']['id'],
            'money'         => PriceNumberFormat($params['money']),
            'status'        => 0,
            'operate_id'    => empty($params['operate_id']) ? 0 : intval($params['operate_id']),
            'operate_name'  => empty($params['operate_name']) ? '' : trim($params['operate_name']),
            'add_time'      => time(),
        ];
        $recharge_id = Db::name('PluginsWalletRecharge')->insertGetId($data);
        if($recharge_id > 0)
        {
            return DataReturn(MyLang('insert_success'), 0, [
                'recharge_id'   => $recharge_id,
                'recharge_no'   => $data['recharge_no'],
                'money'         => $data['money'],
                'user_id'       => $data['user_id'],
                'wallet_id'     => $data['wallet_id'],
            ]);
        }
        return DataReturn(MyLang('insert_fail'), -100);
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
    public static function RechargeDelete($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => MyLang('data_id_error_tips'),
            ],
            [
                'checked_type'      => 'empty',
                'is_checked'        => 2,
                'key_name'          => 'user',
                'error_msg'         => MyLang('user_info_incorrect_tips'),
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 删除
        $where = [
            'id'        => intval($params['id']),
        ];
        if(!empty($params['user']['id']))
        {
            $where['user_id'] = $params['user']['id'];
        }
        if(Db::name('PluginsWalletRecharge')->where($where)->delete())
        {
            return DataReturn(MyLang('delete_success'), 0);
        }
        return DataReturn(MyLang('delete_fail'), -100);
    }

}
?>