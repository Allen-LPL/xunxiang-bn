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
namespace app\plugins\wallet\api;

use app\plugins\wallet\api\Common;
use app\plugins\wallet\service\BaseService;
use app\plugins\wallet\service\CashService;

/**
 * 钱包 - 提现记录
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Cash extends Common
{
    /**
     * 构造方法
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-11-30
     * @desc    description
     */
    public function __construct($params = [])
    {
        parent::__construct($params);

        // 是否已经登录
        IsUserLogin();
    }

    /**
     * 列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-19
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        // 参数
        $params['user'] = $this->user;
        $params['plugins_config'] = $this->plugins_config;
        $params['user_type'] = 'user';

        // 条件
        $where = BaseService::CashWhere($params);

        // 获取总数
        $total = BaseService::CashTotal($where);
        $page_total = ceil($total/$this->page_size);
        $start = intval(($this->page-1)*$this->page_size);

        // 获取列表
        $data_params = array(
            'm'             => $start,
            'n'             => $this->page_size,
            'where'         => $where,
        );
        $data = BaseService::CashList($data_params);

        // 返回数据
        $result = [
            'total'             => $total,
            'page_total'        => $page_total,
            'data'              => $data['data'],
        ];
        return DataReturn('success', 0, $result);
    }

    /**
     * 获取详情
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-01-07
     * @param   [array]          $params [输入参数]
     */
    public function Detail($params = [])
    {
        // 参数
        $params['user'] = $this->user;
        $params['plugins_config'] = $this->plugins_config;
        $params['user_type'] = 'user';
        if(empty($params['id']))
        {
            return DataReturn(MyLang('params_error_tips'), -1);
        }

        // 条件
        $where = BaseService::CashWhere($params);

        // 获取列表
        $data_params = array(
            'm'         => 0,
            'n'         => 1,
            'where'     => $where,
        );
        $ret = BaseService::CashList($data_params);
        if(!empty($ret['data'][0]))
        {
            // 返回信息
            $result = [
                'data'      => $ret['data'][0],
            ];

            return DataReturn('success', 0, $result);
        }
        return DataReturn(MyLang('data_no_exist_or_delete_error_tips'), -100);
    }

    /**
     * 提现安全校验
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-01-23
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Auth($params = [])
    {
        // 认证方式
        $check_account_list = CashService::UserCheckAccountList($this->user);

        // 返回数据
        $result = [
            'base'                  => $this->plugins_config,
            'user_wallet'           => $this->user_wallet,
            'check_account_list'    => $check_account_list,
        ];
        return DataReturn('success', 0, $result);
    }

    /**
     * 验证码发送
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-08
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Verifysend($params = [])
    {
        // 开始处理
        $params['user'] = $this->user;
        $params['plugins_config'] = $this->plugins_config;
        return CashService::VerifySend($params);
    }

    /**
     * 验证码校验
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-08
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function VerifyCheck($params = [])
    {
        // 开始处理
        $params['user'] = $this->user;
        $params['plugins_config'] = $this->plugins_config;
        return CashService::VerifyCheck($params);
    }

    /**
     * 提现创建初始化
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-01-07
     * @param   [array]          $params [输入参数]
     */
    public function CreateInit($params = [])
    {
        // 安全校验
        $ret = CashService::CashAuthCheck(['user'=>$this->user, 'plugins_config'=>$this->plugins_config]);
        $check_status = ($ret['code'] == 0) ? $ret['data'] : 0;

        // 验证通过则读取相关数据
        if($check_status == 1)
        {
            // 可提现最大金额
            $can_cash_max_money = CashService::CanCashMaxMoney($this->user_wallet, $this->plugins_config);

            // 默认提现信息
            $default_data = CashService::CachDefaultData($this->user['id']);

            // 当前用户是否已存在openid
            $user_weixin_openid_ret = BaseService::UserWeixinOpenidValue($this->user['id']);
            // 提现类型列表
            $user_cash_type_list = BaseService::UserCashTypeList($this->plugins_config, ['user_weixin_openid'=>$user_weixin_openid_ret['value']]);
        }
        
        // 返回信息
        $result = [
            'check_status'          => $check_status,
            'base'                  => $this->plugins_config,
            'user_wallet'           => $this->user_wallet,
            'can_cash_max_money'    => isset($can_cash_max_money) ? $can_cash_max_money : 0,
            'default_data'          => empty($default_data) ? null : $default_data,
            'user_cash_type_list'   => empty($user_cash_type_list) ? [] : $user_cash_type_list,
        ];
        return DataReturn('success', 0, $result);
    }

    /**
     * 提现创建
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-08
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Create($params = [])
    {
        // 开始处理
        $params['user'] = $this->user;
        $params['plugins_config'] = $this->plugins_config;
        return CashService::CashCreate($params);
    }

    /**
     * 余额提现 - 用户提现领取
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-03-15T23:51:50+0800
     * @param   [array]          $params [输入参数]
     */
    public function ReceiveData($params = [])
    {
        // 开始处理
        $params['user'] = $this->user;
        $params['plugins_config'] = $this->plugins_config;
        return CashService::CashReceiveData($params);
    }

    /**
     * 支付刷新
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-06
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function PayRefresh($params = [])
    {
        // 开始处理
        $params['user'] = $this->user;
        $params['plugins_config'] = $this->plugins_config;
        return CashService::PayRefresh($params);
    }
}
?>