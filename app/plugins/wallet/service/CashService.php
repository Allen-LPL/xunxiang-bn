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
use app\service\ResourcesService;
use app\service\MessageService;
use app\service\ConfigService;
use app\service\PluginsDataConfigService;
use app\plugins\wallet\service\BaseService;
use app\plugins\wallet\service\WalletService;
use app\plugins\wallet\service\CashWeixinService;

/**
 * 提现服务层
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class CashService
{
    // 校验缓存 key
    public static $wallet_cash_check_success_key = 'plugins_wallet_cash_check_success_key_'.APPLICATION_CLIENT_TYPE;

    /**
     * 验证码发送
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2017-03-05T19:17:10+0800
     * @param    [array]          $params [输入参数]
     */
    public static function VerifySend($params = [])
    {
        // 数据验证
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'account_type',
                'error_msg'         => '身份认证方式有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user',
                'error_msg'         => MyLang('user_info_incorrect_tips'),
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 账户
        if(empty($params['user'][$params['account_type']]))
        {
            return DataReturn('当前验证类型账号未绑定', -1);
        }

        // 是否开启图片验证码
        // 仅 web 端校验图片验证码
        if(in_array(APPLICATION_CLIENT_TYPE, ['pc']))
        {
            $verify = self::IsImaVerify($params);
            if($verify['code'] != 0)
            {
                return $verify;
            }
        }

        // 当前验证账户
        $accounts = $params['user'][$params['account_type']];

        // 发送验证码
        $verify_params = [
                'key_prefix'     => md5('wallet_cash_'.$accounts),
                'expire_time'    => MyC('common_verify_expire_time'),
                'interval_time'  => MyC('common_verify_interval_time'),
            ];
        $code = GetNumberCode(4);
        if($params['account_type'] == 'mobile')
        {
            $obj = new \base\Sms($verify_params);
            $status = $obj->SendCode($accounts, $code, ConfigService::SmsTemplateValue('common_sms_currency_template'));
        } else {
            $obj = new \base\Email($verify_params);
            $email_params = array(
                    'email'     =>  $accounts,
                    'content'   =>  MyC('common_email_currency_template'),
                    'title'     =>  MyC('home_site_name').' - 账户安全认证',
                    'code'      =>  $code,
                );
            $status = $obj->SendHtml($email_params);
        }
        
        // 状态
        if($status)
        {
            // 清除验证码
            if(isset($verify['data']) && is_object($verify['data']))
            {
                $verify['data']->Remove();
            }

            return DataReturn(MyLang('send_success'), 0);
        }
        return DataReturn(MyLang('send_fail').'['.$obj->error.']', -100);
    }

    /**
     * 是否开启图片验证码校验
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2017-03-22T15:48:31+0800
     * @param    [array]    $params         [输入参数]
     * @return   [object]                   [图片验证码类对象]
     */
    private static function IsImaVerify($params)
    {
        if(MyC('common_img_verify_state') == 1)
        {
            if(empty($params['verify']))
            {
                return DataReturn(MyLang('params_error_tips'), -10);
            }

            // 验证码基础参数
            $verify_params = [
                    'key_prefix'     => 'wallet_cash',
                    'expire_time'    => MyC('common_verify_expire_time'),
                    'interval_time'  => MyC('common_verify_interval_time'),
                ];
            $verify = new \base\Verify($verify_params);
            if(!$verify->CheckExpire())
            {
                return DataReturn(MyLang('verify_code_expire_tips'), -11);
            }
            if(!$verify->CheckCorrect($params['verify']))
            {
                return DataReturn(MyLang('verify_code_error_tips'), -12);
            }
            return DataReturn(MyLang('operate_success'), 0, $verify);
        }
        return DataReturn(MyLang('operate_success'), 0);
    }

    /**
     * 验证码校验
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2017-03-28T15:57:19+0800
     * @param    [array]          $params [输入参数]
     */
    public static function VerifyCheck($params = [])
    {
        // 数据验证
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'account_type',
                'error_msg'         => '身份认证方式有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'verify',
                'error_msg'         => MyLang('verify_code_empty_tips'),
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user',
                'error_msg'         => MyLang('user_info_incorrect_tips'),
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 账户
        if(empty($params['user'][$params['account_type']]))
        {
            return DataReturn('当前验证类型账号未绑定', -1);
        }

        // 当前验证账户
        $accounts = $params['user'][$params['account_type']];

        // 验证码校验
        $verify_params = array(
                'key_prefix' => md5('wallet_cash_'.$accounts),
                'expire_time' => MyC('common_verify_expire_time')
            );
        if($params['account_type'] == 'mobile')
        {
            $obj = new \base\Sms($verify_params);
        } else {
            $obj = new \base\Email($verify_params);
        }
        // 是否已过期
        if(!$obj->CheckExpire())
        {
            return DataReturn(MyLang('verify_code_expire_tips'), -10);
        }
        // 是否正确
        if($obj->CheckCorrect($params['verify']))
        {
            // 安全验证后规定时间内时间限制
            $cash_time_limit = (empty($params['plugins_config']) || empty($params['plugins_config']['cash_time_limit'])) ? 30 : intval($params['plugins_config']['cash_time_limit']);

            // 校验成功标记
            MyCache(self::$wallet_cash_check_success_key.$params['user']['id'], time(), $cash_time_limit*60);

            // 清除验证码
            $obj->Remove();

            return DataReturn('验证正确', 0);
        }
        return DataReturn(MyLang('verify_code_error_tips'), -11);
    }

    /**
     * 提现创建
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-08
     * @desc    description
     * @param    [array]          $params [输入参数]
     */
    public static function CashCreate($params = [])
    {
        // 参数验证
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'plugins_config',
                'error_msg'         => MyLang('plugins_config_error_tips'),
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'cash_type',
                'checked_data'      => array_column(BaseService::ConstData('cash_type_list'), 'value'),
                'error_msg'         => '提现方式范围值有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'money',
                'error_msg'         => '提现金额不能为空',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'bank_username',
                'checked_data'      => '1,30',
                'error_msg'         => '开户人姓名格式1~30个字符之间',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user',
                'error_msg'         => MyLang('user_info_incorrect_tips'),
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 是否开启提现申请
        if(!isset($params['plugins_config']['is_enable_cash']) || $params['plugins_config']['is_enable_cash'] != 1)
        {
            return DataReturn('未开启转账功能、请联系管理员！', -1);
        }

        // 用户钱包
        $user_wallet = WalletService::UserWallet($params['user']['id']);
        if($user_wallet['code'] != 0)
        {
            return $user_wallet;
        }

        // 提现金额
        $money = PriceNumberFormat($params['money']);
        if($money > $user_wallet['data']['normal_money'])
        {
            return DataReturn('提现金额不能大于有效金额', -1);
        }

        // 赠送金额是否可以提现、默认赠送金额不可提现
        $can_cach_max_money = self::CanCashMaxMoney($user_wallet['data'], $params['plugins_config']);
        if($money > $can_cach_max_money)
        {
            return DataReturn('赠送金额不可提现', -1);
        }

        // 是否开启最低金额限制
        if(isset($params['plugins_config']['cash_minimum_amount']) && $params['plugins_config']['cash_minimum_amount'] > 0 && $money < $params['plugins_config']['cash_minimum_amount'])
        {
            return DataReturn('提现最低金额'.ResourcesService::CurrencyDataSymbol(['is_only_currency_default'=>1]).$params['plugins_config']['cash_minimum_amount'].'起', -1);
        }

        // 提现手续费
        $commission = 0;
        if(isset($params['plugins_config']['cash_commission_rate']) && $params['plugins_config']['cash_commission_rate'] > 0)
        {
            $commission = PriceNumberFormat($money*$params['plugins_config']['cash_commission_rate']);
        }

        // 添加提现数据
        $data = [
            'cash_no'           => date('YmdHis').GetNumberCode(6),
            'user_id'           => $user_wallet['data']['user_id'],
            'wallet_id'         => $user_wallet['data']['id'],
            'status'            => 0,
            'cash_type'         => intval($params['cash_type']),
            'money'             => $money,
            'commission'        => $commission,
            'bank_username'     => $params['bank_username'],
            'add_time'          => time(),
        ];
        // 提现方式
        switch($data['cash_type'])
        {
            // 其他方式
            case 0 :
                $data['bank_name'] = empty($params['bank_name']) ? '' : $params['bank_name'];
                $data['bank_accounts'] = empty($params['bank_accounts']) ? '' : $params['bank_accounts'];
                if(empty($data['bank_name']))
                {
                    return DataReturn('收款平台格式1~60个字符之间', -1);
                }
                if(empty($data['bank_accounts']))
                {
                    return DataReturn('收款账号格式1~60个字符之间', -1);
                }
                break;

            // 微信
            case 1 :
                $user_weixin_openid_ret = BaseService::UserWeixinOpenidValue($params['user']['id']);
                $data['bank_accounts'] = $user_weixin_openid_ret['value'];
                $data['accounts_other_type'] = $user_weixin_openid_ret['type'];
                if(empty($data['bank_accounts']))
                {
                    return DataReturn('请先完成微信授权', -1);
                }
                break;

            // 支付宝
            case 2 :
                $data['bank_accounts'] = empty($params['bank_accounts']) ? '' : $params['bank_accounts'];
                if(empty($data['bank_accounts']))
                {
                    return DataReturn('请填写支付宝账户', -1);
                }
                break;
        }

        // 开始处理
        Db::startTrans();
        $cash_id = Db::name('PluginsWalletCash')->insertGetId($data);
        if($cash_id <= 0)
        {
            Db::rollback();
            return DataReturn('提现操作失败', -1);
        }

        // 钱包更新
        $wallet_data = [
            'normal_money'  => PriceNumberFormat($user_wallet['data']['normal_money']-$money),
            'frozen_money'  => PriceNumberFormat($user_wallet['data']['frozen_money']+$money),
            'upd_time'      => time(),
        ];
        if(!Db::name('PluginsWallet')->where(['id'=>$user_wallet['data']['id']])->update($wallet_data))
        {
            Db::rollback();
            return DataReturn('钱包操作失败', -1);
        }

        // 日志
        $currency_symbol = ResourcesService::CurrencyDataSymbol(['is_only_currency_default'=>1]);
        $money_field = [
            ['field' => 'normal_money', 'money_type' => 0, 'msg' => ' [ 有效金额减少'.$currency_symbol.$money.' ]'],
            ['field' => 'frozen_money', 'money_type' => 1, 'msg' => ' [ 冻结金额增加'.$currency_symbol.$money.' ]'],
        ];
        foreach($money_field as $v)
        {
            // 有效金额
            if($user_wallet['data'][$v['field']] != $wallet_data[$v['field']])
            {
                $log_data = [
                    'user_id'           => $user_wallet['data']['user_id'],
                    'wallet_id'         => $user_wallet['data']['id'],
                    'business_type'     => 2,
                    'operation_type'    => ($user_wallet['data'][$v['field']] < $wallet_data[$v['field']]) ? 1 : 0,
                    'money_type'        => $v['money_type'],
                    'operation_money'   => ($user_wallet['data'][$v['field']] < $wallet_data[$v['field']]) ? PriceNumberFormat($wallet_data[$v['field']]-$user_wallet['data'][$v['field']]) : PriceNumberFormat($user_wallet['data'][$v['field']]-$wallet_data[$v['field']]),
                    'original_money'    => $user_wallet['data'][$v['field']],
                    'latest_money'      => $wallet_data[$v['field']],
                    'msg'               => '用户提现申请 '.$v['msg'],
                ];
                if(!WalletService::WalletLogInsert($log_data))
                {
                    Db::rollback();
                    return DataReturn('日志添加失败', -101);
                }

                // 消息通知
                MessageService::MessageAdd($log_data['user_id'], '账户余额变动', $log_data['msg'], BaseService::$business_type_name, $cash_id);

                // 通知
                $wallet_money_type_list = BaseService::ConstData('wallet_money_type_list');
                NoticeService::Send([
                    'user_id'       => $log_data['user_id'],
                    'msg_title'     => '用户提现申请',
                    'money_type'    => $wallet_money_type_list[$log_data['money_type']]['name'],
                    'opt_type'      => ($log_data['operation_type'] == 1) ? '增加' : '减少',
                    'opt_money'     => $log_data['operation_money'],
                    'content'       => $log_data['msg'],
                ]);
            }
        }

        // 是否自动提现、数据配置
        $data_config = PluginsDataConfigService::DataConfigData('wallet');
        switch($data['cash_type'])
        {
            // 微信
            case 1 :
                if(isset($data_config['weixin_is_auto_cash']) && $data_config['weixin_is_auto_cash'] == 1)
                {
                    $ret = self::WeixinPay(['id'=>$cash_id]);
                    if($ret['code'] != 0)
                    {
                        Db::rollback();
                        return $ret;
                    }
                }
                break;

            // 支付宝
            case 2 :
                if(isset($data_config['alipay_is_auto_cash']) && $data_config['alipay_is_auto_cash'] == 1)
                {
                    $ret = self::AlipayPay(['id'=>$cash_id]);
                    if($ret['code'] != 0)
                    {
                        Db::rollback();
                        return $ret;
                    }
                }
                break;
        }

        // 提交事务
        Db::commit();
        return DataReturn(MyLang('operate_success'), 0);   
    }

    /**
     * 可提现最大金额
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-01-20
     * @desc    description
     * @param   [array]          $wallet      [用户钱包数据]
     * @param   [array]          $plugins_config [插件配置]
     */
    public static function CanCashMaxMoney($wallet, $plugins_config)
    {
        // 赠送金额是否可以提现、默认赠送金额不可提现
        if(isset($plugins_config['is_cash_retain_give']) && $plugins_config['is_cash_retain_give'] == 1)
        {
            $money = PriceNumberFormat($wallet['normal_money']-$wallet['give_money']);
        } else {
            $money = $wallet['normal_money'];
        }
        return $money;
    }

    /**
     * 提现领取数据
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-10
     * @desc    description
     * @param    [array]          $params [输入参数]
     */
    public static function CashReceiveData($params = [])
    {
        // 参数验证
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => MyLang('data_id_error_tips'),
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user',
                'error_msg'         => MyLang('user_info_incorrect_tips'),
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 更新提现数据
        $cash = Db::name('PluginsWalletCash')->where(['id'=>intval($params['id']), 'user_id'=>$params['user']['id']])->find();
        if(empty($cash))
        {
            return DataReturn('提现数据不存在', -1);
        }
        if($cash['cash_type'] != 1)
        {
            return DataReturn('仅支持微信提现支付刷新', -1);
        }
        // 支付日志
        $cash_log = Db::name('PluginsWalletCashPayment')->where(['cash_id'=>$cash['id'], 'status'=>1])->order('id desc')->find();
        if(empty($cash_log))
        {
            return DataReturn('没有对应的提现支付日志', -1);
        }
        // 请求参数
        if(empty($cash_log['request_params']))
        {
            return DataReturn('支付日志没有请求数据', -1);
        }
        $request = json_decode($cash_log['request_params'], true);
        if(empty($request))
        {
            return DataReturn('支付日志请求数据有误', -1);
        }
        if(empty($request['appid']))
        {
            return DataReturn('支付日志请求数据无appid', -1);
        }
        // 回调参数
        if(empty($cash_log['response_data']))
        {
            return DataReturn('支付日志没有回调数据', -1);
        }
        $response = json_decode($cash_log['response_data'], true);
        if(empty($response))
        {
            return DataReturn('支付日志回调数据有误', -1);
        }
        if(empty($response['package_info']))
        {
            return DataReturn('支付日志回调数据无package_info', -1);
        }
        // 配置信息
        $data_config = PluginsDataConfigService::DataConfigData('wallet');
        if(empty($data_config['weixin_merchant_id']))
        {
            return DataReturn('没有配置商户号', -1);
        }
        // 返回数据
        $result = [
            'cash_id'  => $cash['id'],
            'mchid'    => $data_config['weixin_merchant_id'],
            'appid'    => $request['appid'],
            'package'  => $response['package_info'],
        ];
        return DataReturn('success', 0, $result);
    }

    /**
     * 提现支付状态更新
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-10
     * @desc    description
     * @param    [array]          $params [输入参数]
     */
    public static function CashStatusUpdate($params = [])
    {
        // 参数验证
        if(empty($params['id']))
        {
            return DataReturn('提现id有误', -1);
        }
        if(!isset($params['status']))
        {
            return DataReturn('状态未指定', -1);
        }

        // 更新提现数据
        if(Db::name('PluginsWalletCash')->where(['id'=>intval($params['id'])])->update(['status'=>$params['status'], 'upd_time'=>time()]))
        {
            return DataReturn(MyLang('operate_success'), 0);
        }
        return DataReturn(MyLang('operate_fail'), -1);
    }

    /**
     * 提现申请审核
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-10
     * @desc    description
     * @param    [array]          $params [输入参数]
     */
    public static function CashAudit($params = [])
    {
        // 参数验证
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'pay_money',
                'error_msg'         => '打款金额有误',
            ],
            [
                'checked_type'      => 'fun',
                'key_name'          => 'pay_money',
                'checked_data'      => 'CheckPrice',
                'error_msg'         => '请输入有效的打款金额有误',
            ],
            [
                'checked_type'      => 'min',
                'key_name'          => 'pay_money',
                'checked_data'      => 0.01,
                'error_msg'         => '打款金额有误，最低0.01',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'msg',
                'checked_data'      => '180',
                'is_checked'        => 1,
                'error_msg'         => '备注最多180个字符',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'type',
                'checked_data'      => ['agree', 'refuse'],
                'error_msg'         => '操作类型有误，同意或拒绝操作出错',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }
        $check = self::AuditParamsCheck($params);
        if($check['code'] != 0)
        {
            return $check;
        }
        $wallet = $check['data']['wallet'];
        $cash = $check['data']['cash'];
        $pay_money = $check['data']['pay_money'];

        // 是否发送消息
        $is_send_message = (isset($params['is_send_message']) && $params['is_send_message'] == 1) ? 1 : 0;

        // 货币符号
        $currency_symbol = ResourcesService::CurrencyDataSymbol(['is_only_currency_default'=>1]);

        // 数据处理
        $release_money = 0;
        $commission = $cash['commission'];
        if($params['type'] == 'agree')
        {
            // 打款金额是否小于提现金额
            if($pay_money < $cash['money'])
            {
                // 退回金额
                $release_money = PriceNumberFormat($cash['money']-$pay_money);

                // 是否有提现手续费、换算之前记录的手续费换算汇率值
                if(isset($cash['commission']) && $cash['commission'] > 0)
                {
                    // 重新计算手续费
                    $rate = $cash['commission']/$cash['apply_money'];
                    $commission = PriceNumberFormat($pay_money*$rate);

                    // 增加退回的手续费
                    $release_money += $cash['commission']-$commission;
                }
            }

            // 钱包更新数据
            $wallet_upd_data = [
                'frozen_money'  => PriceNumberFormat($wallet['frozen_money']-$cash['apply_money']),
            ];

            // 提现更新数据
            $cash_upd_data = [
                'status'        => 2,
                'commission'    => $commission,
                'pay_money'     => $pay_money,
                'pay_time'      => time(),
            ];
            $money_field = [
                ['field' => 'frozen_money', 'money_type' => 1, 'msg' => ' [ 提现申请成功 , 冻结金额减少'.$currency_symbol.$cash['apply_money'].' ]'],
            ];

            // 打款金额是否小于提现金额、退回金额处理
            if($release_money > 0)
            {
                // 更新钱包金额
                $wallet_upd_data['normal_money'] = PriceNumberFormat($wallet['normal_money']+$release_money);
                $money_field[] = ['field' => 'normal_money', 'money_type' => 0, 'msg' => ' [ 提现申请成功 , 部分金额未打款 , 冻结金额退回至有效金额'.$currency_symbol.$release_money.' ]'];
            }
        } else {
            // 钱包更新数据
            $wallet_upd_data = [
                'frozen_money'  => PriceNumberFormat($wallet['frozen_money']-$cash['apply_money']),
                'normal_money'  => PriceNumberFormat($wallet['normal_money']+$cash['apply_money']),
            ];

            // 提现更新数据
            $cash_upd_data = [
                'status'    => 3,
            ];

            $money_field = [
                ['field' => 'frozen_money', 'money_type' => 1, 'msg' => ' [ 提现申请失败 , 冻结金额释放 '.$currency_symbol.$cash['apply_money'].' ]'],
                ['field' => 'normal_money', 'money_type' => 0, 'msg' => ' [ 提现申请失败 , 冻结金额退回至有效金额'.$currency_symbol.$cash['apply_money'].' ]'],
            ];
        }

        // 开始处理
        Db::startTrans();

        // 提现更新
        $cash_upd_data['msg'] = empty($params['msg']) ? '' : $params['msg'];
        $cash_upd_data['upd_time'] = time();
        if(!Db::name('PluginsWalletCash')->where(['id'=>$cash['id']])->update($cash_upd_data))
        {
            Db::rollback();
            return DataReturn('提现申请操作失败', -100);
        }

        // 钱包更新
        if(!Db::name('PluginsWallet')->where(['id'=>$wallet['id']])->update($wallet_upd_data))
        {
            Db::rollback();
            return DataReturn('钱包操作失败', -101);
        }

        foreach($money_field as $v)
        {
            // 有效金额
            if($wallet[$v['field']] != $wallet_upd_data[$v['field']])
            {
                $log_data = [
                    'user_id'           => $wallet['user_id'],
                    'wallet_id'         => $wallet['id'],
                    'business_type'     => 2,
                    'operation_type'    => ($wallet[$v['field']] < $wallet_upd_data[$v['field']]) ? 1 : 0,
                    'money_type'        => $v['money_type'],
                    'operation_money'   => ($wallet[$v['field']] < $wallet_upd_data[$v['field']]) ? PriceNumberFormat($wallet_upd_data[$v['field']]-$wallet[$v['field']]) : PriceNumberFormat($wallet[$v['field']]-$wallet_upd_data[$v['field']]),
                    'original_money'    => $wallet[$v['field']],
                    'latest_money'      => $wallet_upd_data[$v['field']],
                    'msg'               => '管理员审核'.$v['msg'],
                ];
                if(!WalletService::WalletLogInsert($log_data))
                {
                    Db::rollback();
                    return DataReturn('日志添加失败', -101);
                }

                // 消息通知
                if($is_send_message == 1)
                {
                    // 消息通知
                    MessageService::MessageAdd($wallet['user_id'], '账户余额变动', $log_data['msg'], BaseService::$business_type_name, $cash['id']);

                    // 通知
                    $wallet_money_type_list = BaseService::ConstData('wallet_money_type_list');
                    NoticeService::Send([
                        'user_id'       => $log_data['user_id'],
                        'msg_title'     => '提现审核',
                        'money_type'    => $wallet_money_type_list[$log_data['money_type']]['name'],
                        'opt_type'      => ($log_data['operation_type'] == 1) ? '增加' : '减少',
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
     * 支付刷新
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-09-20
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function PayRefresh($params = [])
    {
        // 获取提现数据
        $cash = BaseService::UserCashAuditData($params);
        if(empty($cash))
        {
            return DataReturn('提现数据不存在或已删除', -10);
        }
        if($cash['cash_type'] != 1)
        {
            return DataReturn('仅支持微信提现支付刷新', -1);
        }
        // 支付日志
        $cash_log = Db::name('PluginsWalletCashPayment')->where(['cash_id'=>$cash['id'], 'status'=>1])->order('id desc')->find();
        if(empty($cash_log))
        {
            return DataReturn('没有对应的提现支付日志', -1);
        }

        return CashWeixinService::PayRefresh($cash, $cash_log, $params);
    }

    /**
     * 微信转账
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-09-20
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function WeixinPay($params = [])
    {
        // 参数验证
        $check = self::AuditParamsCheck($params);
        if($check['code'] != 0)
        {
            return $check;
        }

        // 转账处理
        return CashWeixinService::TransferCreate($check['data']['cash'], $params);
    }

    /**
     * 支付宝转账
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-09-20
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function AlipayPay($params = [])
    {
        // 参数验证
        $check = self::AuditParamsCheck($params);
        if($check['code'] != 0)
        {
            return $check;
        }

        // 转账处理
        return CashAlipayService::TransferCreate($check['data']['cash'], $params);
    }

    /**
     * 审核参数验证
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-09-20
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function AuditParamsCheck($params = [])
    {
        // 参数验证
        if(empty($params['id']) && empty($params['value']))
        {
            return DataReturn('提现id有误', -1);
        }

        // 获取提现数据
        $cash = BaseService::UserCashAuditData($params);
        if(empty($cash))
        {
            return DataReturn('提现数据不存在或已删除', -10);
        }

        // 状态
        if(!in_array($cash['status'], [0, 1]))
        {
            $cash_status_list = BaseService::ConstData('cash_status_list');
            return DataReturn(MyLang('status_not_can_operate_tips').'['.$cash_status_list[$cash['status']]['name'].']', -11);
        }

        // 金额处理，仅通过验证金额
        $pay_money = PriceNumberFormat(empty($params['pay_money']) ? $cash['money'] : $params['pay_money']);
        if(isset($params['type']) && $params['type'] == 'agree')
        {
            $currency_symbol = ResourcesService::CurrencyDataSymbol(['is_only_currency_default'=>1]);
            if($pay_money <= 0.00 || $pay_money > $cash['money'])
            {
                return DataReturn('打款金额有误，最低'.$currency_symbol.'0.01，最高'.$currency_symbol.$cash['money'], -12);
            }
        }

        // 获取用户钱包
        $wallet = Db::name('PluginsWallet')->find(intval($cash['wallet_id']));
        if(empty($wallet))
        {
            return DataReturn('用户钱包不存在或已删除', -20);
        }

        return DataReturn('success', 0, [
            'wallet'     => $wallet,
            'cash'       => $cash,
            'pay_money'  => $pay_money,
        ]);
    }

    /**
     * 用户钱包安全认证方式
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-01-20
     * @desc    description
     * @param   [array]          $user [用户数据]
     */
    public static function UserCheckAccountList($user)
    {
        $check_account_list = [];
        if(!empty($user['mobile_security']))
        {
            $check_account_list[] = [
                'field' => 'mobile',
                'value' => $user['mobile_security'],
                'name'  => '手机',
                'msg'   => '手机['.$user['mobile_security'].']',
            ];
        }
        if(!empty($user['email_security']))
        {
            $check_account_list[] = [
                'field' => 'email',
                'value' => $user['email_security'],
                'name'  => '邮箱',
                'msg'   => '邮箱['.$user['email_security'].']',
            ];
        }
        return $check_account_list;
    }

    /**
     * 用户提现安全验证状态
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-01-20
     * @desc    description
     * @param   [array]          $user [用户数据]
     */
    public static function CashAuthCheck($params = [])
    {
        // 数据验证
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user',
                'error_msg'         => MyLang('user_info_incorrect_tips'),
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 基础配置信息
        $base = BaseService::BaseConfig();

        // 安全验证后规定时间内时间限制
        $cash_time_limit = (empty($base['data']) || empty($base['data']['cash_time_limit'])) ? 30 : intval($base['data']['cash_time_limit']);

        // 是否验证成功
        $check_time = MyCache(self::$wallet_cash_check_success_key.$params['user']['id']);
        $status = (!empty($check_time) && $check_time+($cash_time_limit*60) >= time()) ? 1 : 0;

        // 返回数据
        return DataReturn(MyLang('check_success'), 0, $status);
    }

    /**
     * 获取默认提现信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-02-23
     * @desc    description
     * @param   [int]          $user_id [用户id]
     */
    public static function CachDefaultData($user_id)
    {
        $data = Db::name('PluginsWalletCash')->where(['user_id'=>$user_id])->order('id desc')->field('id,cash_type,bank_name,bank_accounts,bank_username')->find();
        return empty($data) ? null : $data;
    }
}
?>