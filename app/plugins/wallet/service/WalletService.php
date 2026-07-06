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
use app\service\PluginsService;
use app\service\AdminService;
use app\service\ResourcesService;
use app\service\PaymentService;
use app\service\MessageService;
use app\plugins\wallet\service\BaseService;
use app\plugins\wallet\service\NoticeService;

/**
 * 钱包服务层
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class WalletService
{
    /**
     * 用户钱包
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-30
     * @desc    description
     * @param   [int]          $user_id [用户id]
     */
    public static function UserWallet($user_id)
    {
        // 请求参数
        if(empty($user_id))
        {
            return DataReturn(MyLang('user_id_error_tips'), -1);
        }

        // 获取钱包, 不存在则创建
        $wallet = Db::name('PluginsWallet')->where(['user_id' => $user_id])->find();
        if(empty($wallet))
        {
            $data = [
                'user_id'       => $user_id,
                'status'        => 0,
                'add_time'      => time(),
            ];
            $wallet_id = Db::name('PluginsWallet')->insertGetId($data);
            if($wallet_id > 0)
            {
                return DataReturn('success', 0, self::WalletInfo($wallet_id));
            }
            return DataReturn('钱包添加失败', -100);
        }
        return self::UserWalletStatusCheck($wallet);
    }

    /**
     * 钱包信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2023-10-26
     * @desc    description
     * @param   [int]          $wallet_id [钱包id]
     */
    public static function WalletInfo($wallet_id)
    {
        return Db::name('PluginsWallet')->find(intval($wallet_id));
    }

    /**
     * 用户钱包状态校验
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-08
     * @desc    description
     * @param   [array]          $user_wallet [用户钱包]
     */
    public static function UserWalletStatusCheck($user_wallet)
    {
        // 用户钱包状态
        $wallet_error = '';
        if(isset($user_wallet['status']))
        {
            if($user_wallet['status'] != 0)
            {
                $wallet_status_list = BaseService::ConstData('wallet_status_list');
                $wallet_error = array_key_exists($user_wallet['status'], $wallet_status_list) ? '用户钱包[ '.$wallet_status_list[$user_wallet['status']]['name'].' ]' : '用户钱包状态异常错误';
            }
        } else {
            $wallet_error = '用户钱包异常错误';
        }

        if(!empty($wallet_error))
        {
            return DataReturn($wallet_error, -30);
        }
        return DataReturn('success', 0, $user_wallet);
    }

    /**
     * 钱包日志添加
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-05-07T00:57:36+0800
     * @param   [array]          $params [输入参数]
     * @return  [boolean]                [成功true, 失败false]
     */
    public static function WalletLogInsert($params = [])
    {
        $data = [
            'user_id'           => isset($params['user_id']) ? intval($params['user_id']) : 0,
            'wallet_id'         => isset($params['wallet_id']) ? intval($params['wallet_id']) : 0,
            'business_type'     => isset($params['business_type']) ? intval($params['business_type']) : 0,
            'operation_type'    => isset($params['operation_type']) ? intval($params['operation_type']) : 0,
            'money_type'        => isset($params['money_type']) ? intval($params['money_type']) : 0,
            'operation_money'   => isset($params['operation_money']) ? PriceNumberFormat($params['operation_money']) : 0.00,
            'original_money'    => isset($params['original_money']) ? PriceNumberFormat($params['original_money']) : 0.00,
            'latest_money'      => isset($params['latest_money']) ? PriceNumberFormat($params['latest_money']) : 0.00,
            'msg'               => empty($params['msg']) ? MyLang('system_title') : $params['msg'],
            'operate_id'        => empty($params['operate_id']) ? 0 : intval($params['operate_id']),
            'operate_name'      => empty($params['operate_name']) ? '' : trim($params['operate_name']),
            'add_time'          => time(),
        ];
        return Db::name('PluginsWalletLog')->insertGetId($data) > 0;
    }

    /**
     * 钱包编辑
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-06
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function WalletEdit($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '钱包id有误',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'status',
                'checked_data'      => array_column(BaseService::ConstData('wallet_status_list'), 'value'),
                'error_msg'         => '钱包状态有误',
            ],
            [
                'checked_type'      => 'fun',
                'key_name'          => 'normal_money',
                'checked_data'      => 'CheckPrice',
                'is_checked'        => 1,
                'error_msg'         => '有效金额格式有误',
            ],
            [
                'checked_type'      => 'fun',
                'key_name'          => 'frozen_money',
                'checked_data'      => 'CheckPrice',
                'is_checked'        => 1,
                'error_msg'         => '冻结金额格式有误',
            ],
            [
                'checked_type'      => 'fun',
                'key_name'          => 'give_money',
                'checked_data'      => 'CheckPrice',
                'is_checked'        => 1,
                'error_msg'         => '赠送金额格式有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 获取钱包
        $wallet = Db::name('PluginsWallet')->find(intval($params['id']));
        if(empty($wallet))
        {
            return DataReturn('钱包不存在或已删除', -10);
        }

        // 开始处理
        Db::startTrans();

        // 数据
        $data = [
            'status'        => intval($params['status']),
            'normal_money'  => empty($params['normal_money']) ? 0.00 : PriceNumberFormat($params['normal_money']),
            'frozen_money'  => empty($params['frozen_money']) ? 0.00 : PriceNumberFormat($params['frozen_money']),
            'give_money'    => empty($params['give_money']) ? 0.00 : PriceNumberFormat($params['give_money']),
            'upd_time'      => time(),
        ];
        if(!Db::name('PluginsWallet')->where(['id'=>$wallet['id']])->update($data))
        {
            Db::rollback();
            return DataReturn(MyLang('operate_fail'), -100);
        }

        // 插件基础配置
        $base = BaseService::BaseConfig(false);

        // 日志
        // 字段名称 金额类型 金额名称
        $money_field = [
            ['field' => 'normal_money', 'money_type' => 0],
            ['field' => 'frozen_money', 'money_type' => 1],
            ['field' => 'give_money', 'money_type' => 2],
        ];

        // 是否发送消息
        $is_send_message = (isset($params['is_send_message']) && $params['is_send_message'] == 1) ? 1 : 0;

        // 管理员
        $admin = AdminService::LoginInfo();

        // 货币符号
        $currency_symbol = ResourcesService::CurrencyDataSymbol(['is_only_currency_default'=>1]);

        // 操作原因
        $operation_msg = empty($params['msg']) ? '' : ' [ '.$params['msg'].' ]';
        foreach($money_field as $v)
        {
            // 有效金额
            if($wallet[$v['field']] != $data[$v['field']])
            {
                // 验证密码
                if(empty($params['wallet_edit_money_password']))
                {
                    return DataReturn('请输入操作密码', -1);
                }
                if(empty($base['data']['wallet_edit_money_password']))
                {
                    return DataReturn('请联系超级管理员设置操作密码', -1);
                }
                if(BaseService::WalletMoneyEditPassword($params['wallet_edit_money_password']) != $base['data']['wallet_edit_money_password'])
                {
                    return DataReturn('操作密码错误', -1);
                }

                // 添加日志
                $log_data = [
                    'user_id'           => $wallet['user_id'],
                    'wallet_id'         => $wallet['id'],
                    'business_type'     => 0,
                    'operation_type'    => ($wallet[$v['field']] < $data[$v['field']]) ? 1 : 0,
                    'money_type'        => $v['money_type'],
                    'operation_money'   => ($wallet[$v['field']] < $data[$v['field']]) ? PriceNumberFormat($data[$v['field']]-$wallet[$v['field']]) : PriceNumberFormat($wallet[$v['field']]-$data[$v['field']]),
                    'original_money'    => $wallet[$v['field']],
                    'latest_money'      => $data[$v['field']],
                    'operate_id'        => empty($params['operate_id']) ? 0 : intval($params['operate_id']),
                    'operate_name'      => empty($params['operate_name']) ? '' : trim($params['operate_name']),
                ];
                $msg_title = $admin['username'].'管理员操作';
                $wallet_money_type_list = BaseService::ConstData('wallet_money_type_list');
                $money_type = $wallet_money_type_list[$v['money_type']]['name'];
                $opt_type = ($log_data['operation_type'] == 1) ? '增加' : '减少';
                $msg = $opt_type;
                $log_data['msg'] = $msg_title.' [ '.$money_type.'金额'.$msg.$currency_symbol.$log_data['operation_money'].' ]'.$operation_msg;
                if(!self::WalletLogInsert($log_data))
                {
                    Db::rollback();
                    return DataReturn('日志添加失败', -101);
                }

                // 消息通知
                if($is_send_message == 1)
                {
                    // 系统消息
                    MessageService::MessageAdd($log_data['user_id'], '账户余额变动', $log_data['msg'], BaseService::$business_type_name, $wallet['id']);

                    // 通知
                    NoticeService::Send([
                        'user_id'       => $log_data['user_id'],
                        'msg_title'     => $msg_title,
                        'money_type'    => $money_type,
                        'opt_type'      => $opt_type,
                        'opt_money'     => $log_data['operation_money'],
                        'content'       => $log_data['msg'],
                    ]);
                }
            }
        }

        // 处理成功
        Db::commit();
        return DataReturn(MyLang('operate_success'), 0);
    }

    /**
     * 钱包余额修改
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2023-10-26
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function WalletChange($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '钱包id有误',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'money_type',
                'checked_data'      => array_column(BaseService::ConstData('wallet_money_type_list'), 'value'),
                'error_msg'         => '余额类型有误',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'operate_type',
                'checked_data'      => array_column(BaseService::ConstData('wallet_operate_type_list'), 'value'),
                'error_msg'         => '操作类型有误',
            ],
            [
                'checked_type'      => 'fun',
                'key_name'          => 'money',
                'checked_data'      => 'CheckPrice',
                'is_checked'        => 1,
                'error_msg'         => '操作金额格式有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 金额字段
        $money_field = [0 => 'normal_money', 1 => 'frozen_money', 2 => 'give_money'];
        if(!array_key_exists($params['money_type'], $money_field))
        {
            return DataReturn('余额类型字段有误', -1);
        }

        // 钱包信息
        $wallet = self::WalletInfo($params['id']);
        if(empty($wallet))
        {
            return DataReturn('钱包不存在或已删除', -1);
        }

        // 修改余额
        $msg_title = $params['operate_name'].'管理员操作';
        if(!empty($params['note']))
        {
            $msg_title .= '('.$params['note'].')';
        }
        return self::UserWalletMoneyUpdate($wallet['user_id'], $params['money'], $params['operate_type'], $money_field[$params['money_type']], 0, $msg_title, ['is_consistent'=>1]);
    }

    /**
     * 用户钱包金额更新
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-06-10
     * @desc    description
     * @param   [int]          $user_id         [用户id]
     * @param   [float]        $money           [操作金额]
     * @param   [int]          $type            [类型（0减少, 1增加）]
     * @param   [string]       $field           [金额字段, 默认normal_money有效金额, frozen_money冻结金额, give_money赠送金额]
     * @param   [int]          $business_type   [业务类型（0系统, 1充值, 2提现, 3消费, 4转账）]
     * @param   [string]       $msg_title       [附加描述标题]
     * @param   [array]        $params          [输入参数]
     */
    public static function UserWalletMoneyUpdate($user_id, $money, $type, $field = 'normal_money', $business_type = 0, $msg_title = '钱包变更', $params = [])
    {
        // 获取用户钱包
        $wallet = self::UserWallet($user_id);
        if($wallet['code'] == 0)
        {
            // 金额字段
            $money_field = ['normal_money' => 0, 'frozen_money'=> 1, 'give_money' => 2];
            if(!array_key_exists($field, $money_field))
            {
                return DataReturn('钱包操作金额字段有误('.$field.')', -10);
            }
            // 是否开启实务
            $is_consistent = (isset($params['is_consistent']) && $params['is_consistent'] == 1);

            // 操作金额
            $money = PriceNumberFormat($money);

            // 是否开启实务
            if($is_consistent)
            {
                Db::startTrans();
            }

            // 货币符号
            $currency_symbol = ResourcesService::CurrencyDataSymbol(['is_only_currency_default'=>1]);

            // 捕获异常
            try {
                // 钱包数据
                $data = [
                    $field      => ($type == 1) ? PriceNumberFormat($wallet['data'][$field]+$money) : PriceNumberFormat($wallet['data'][$field]-$money),
                    'upd_time'  => time(),
                ];
                if(!Db::name('PluginsWallet')->where(['id'=>$wallet['data']['id']])->update($data))
                {
                    throw new \Exception('钱包操作失败');
                }

                // 日志
                $log_data = [
                    'user_id'           => $wallet['data']['user_id'],
                    'wallet_id'         => $wallet['data']['id'],
                    'business_type'     => $business_type,
                    'operation_type'    => $type,
                    'money_type'        => $money_field[$field],
                    'operation_money'   => $money,
                    'original_money'    => $wallet['data'][$field],
                    'latest_money'      => $data[$field],
                    'operate_id'        => empty($params['operate_id']) ? 0 : intval($params['operate_id']),
                    'operate_name'      => empty($params['operate_name']) ? '' : trim($params['operate_name']),
                ];
                $opt_type = ($log_data['operation_type'] == 1) ? '增加' : '减少';
                $wallet_money_type_list = BaseService::ConstData('wallet_money_type_list');
                $money_type = $wallet_money_type_list[$log_data['money_type']]['name'];
                $msg = $opt_type;
                $log_data['msg'] = $msg_title.' [ '.$money_type.'金额'.$msg.$currency_symbol.$log_data['operation_money'].' ]';
                if(!self::WalletLogInsert($log_data))
                {
                    throw new \Exception('钱包日志添加失败');
                }

                // 消息通知
                MessageService::MessageAdd($wallet['data']['user_id'], '钱包变更', $log_data['msg'], BaseService::$business_type_name, $wallet['data']['id']);

                // 通知
                NoticeService::Send([
                    'user_id'       => $log_data['user_id'],
                    'msg_title'     => $msg_title,
                    'money_type'    => $money_type,
                    'opt_type'      => $opt_type,
                    'opt_money'     => $log_data['operation_money'],
                    'content'       => $log_data['msg'],
                ]);

                // 处理成功
                if($is_consistent)
                {
                    Db::commit();
                }
                return DataReturn(MyLang('operate_success'), 0);
            } catch(\Exception $e) {
                if($is_consistent)
                {
                    Db::rollback();
                }
                return DataReturn($e->getMessage(), -1);
            }
        }
        return $wallet;
    }
}
?>