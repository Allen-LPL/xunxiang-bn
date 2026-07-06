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
use app\service\UserService;
use app\plugins\wallet\service\WalletService;

/**
 * 转账服务层
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class TransferService
{
    /**
     * 用户余额转账
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2023-10-23
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function TransferSave($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'plugins_config',
                'error_msg'         => MyLang('plugins_config_error_tips'),
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user',
                'error_msg'         => MyLang('user_info_incorrect_tips'),
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'receive_user_id',
                'error_msg'         => '请输入收款用户',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user_wallet',
                'error_msg'         => '用户钱包有误',
            ],
            [
                'checked_type'      => 'fun',
                'key_name'          => 'money',
                'checked_data'      => 'CheckPrice',
                'error_msg'         => '请输入有效的转账金额',
            ],
            [
                'checked_type'      => 'min',
                'key_name'          => 'money',
                'checked_data'      => 0.01,
                'error_msg'         => '请输入大于0的转账金额',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 是否开启转账
        if(!isset($params['plugins_config']['is_enable_transfer']) || $params['plugins_config']['is_enable_transfer'] != 1)
        {
            return DataReturn('未开启转账功能、请联系管理员！', -1);
        }

        // 余额是否充足
        if($params['user_wallet']['normal_money'] < $params['money'])
        {
            return DataReturn('钱包余额不足('.$params['user_wallet']['normal_money'].'<'.$params['money'].')', -1);
        }

        // 获取用户钱包
        $receive_user_wallet = WalletService::UserWallet($params['receive_user_id']);
        if($receive_user_wallet['code'] != 0)
        {
            return $receive_user_wallet;
        }
        $receive_user_wallet = $receive_user_wallet['data'];

        // 当前是否和收款人是同一个
        if($params['user_wallet']['id'] == $receive_user_wallet['id'])
        {
            return DataReturn('不能在相同钱包之间转账', -1);
        }

        // 接收用户
        $receive_user = UserService::UserHandle(UserService::UserBaseInfo('id', $params['receive_user_id']));
        if(empty($receive_user))
        {
            return DataReturn('接收用户不存在', -1);
        }

        // 捕获异常
        Db::startTrans();
        try {
            // 转账单号
            $transfer_no = date('YmdHis').GetNumberCode(6);

            // 扣减当前用户余额
            $ret = WalletService::UserWalletMoneyUpdate($params['user_wallet']['user_id'], $params['money'], 0, 'normal_money', 4, $transfer_no.'转账订单('.$receive_user['user_name_view'].')转出');
            if($ret['code'] != 0)
            {
                throw new \Exception($ret['msg']);
            }

            // 增加收款用户余额
            $ret = WalletService::UserWalletMoneyUpdate($receive_user_wallet['user_id'], $params['money'], 1, 'normal_money', 4, $transfer_no.'转账订单('.$params['user']['user_name_view'].')转入'.(empty($params['note']) ? '' : '('.$params['note'].')'));
            if($ret['code'] != 0)
            {
                throw new \Exception($ret['msg']);
            }

            // 新增转账记录
            $data = [
                'transfer_no'        => $transfer_no,
                'send_user_id'       => $params['user_wallet']['user_id'],
                'send_wallet_id'     => $params['user_wallet']['id'],
                'receive_user_id'    => $receive_user_wallet['user_id'],
                'receive_wallet_id'  => $receive_user_wallet['id'],
                'money'              => $params['money'],
                'note'               => empty($params['note']) ? '' : $params['note'],
                'add_time'           => time(),
            ];
            if(Db::name('PluginsWalletTransfer')->insertGetId($data) <= 0)
            {
                throw new \Exception('转账记录添加失败');
            }

            // 处理成功
            Db::commit();
            return DataReturn(MyLang('operate_success'), 0);
        } catch(\Exception $e) {
            Db::rollback();
            return DataReturn($e->getMessage(), -1);
        }
    }
}
?>