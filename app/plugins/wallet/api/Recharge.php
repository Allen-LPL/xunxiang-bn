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

use app\service\PaymentService;
use app\plugins\wallet\api\Common;
use app\plugins\wallet\service\BaseService;
use app\plugins\wallet\service\WalletService;
use app\plugins\wallet\service\PayService;
use app\plugins\wallet\service\RechargeService;

/**
 * 钱包 - 充值记录
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Recharge extends Common
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
        $params['user_type'] = 'user';

        // 条件
        $where = BaseService::RechargeWhere($params);

        // 获取总数
        $total = BaseService::RechargeTotal($where);
        $page_total = ceil($total/$this->page_size);
        $start = intval(($this->page-1)*$this->page_size);

        // 获取列表
        $data_params = array(
            'm'             => $start,
            'n'             => $this->page_size,
            'where'         => $where,
        );
        $data = BaseService::RechargeList($data_params);

        // 返回数据
        $result = [
            'total'               => $total,
            'page_total'          => $page_total,
            'data'                => $data['data'],
            'payment_list'        => BaseService::BuyPaymentList($this->plugins_config),
            'default_payment_id'  => PaymentService::BuyDefaultPayment($params),
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
        $params['user_type'] = 'user';
        if(empty($params['id']))
        {
            return DataReturn(MyLang('params_error_tips'), -1);
        }

        // 条件
        $where = BaseService::RechargeWhere($params);

        // 获取列表
        $data_params = array(
            'm'         => 0,
            'n'         => 1,
            'where'     => $where,
        );
        $ret = BaseService::RechargeList($data_params);
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
     * 充值配置数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-11-17
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function RechargeConfigData($params = [])
    {
        $result = BaseService::RechargeConfigData($this->plugins_config);
        $result['user_wallet'] = $this->user_wallet;
        return DataReturn('success', 0, $result);
    }

    /**
     * 创建充值订单
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-14
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Create($params = [])
    {
        $params['user'] = $this->user;
        $params['user_wallet'] = $this->user_wallet;
        $params['operate_id'] = $this->user['id'];
        $params['operate_name'] = $this->user['user_name_view'];
        $params['plugins_config'] = $this->plugins_config;
        $ret = RechargeService::RechargeCreate($params);
        if($ret['code'] == 0)
        {
            // 加入支付列表方式
            $ret['data']['payment_list'] = BaseService::BuyPaymentList($this->plugins_config);
            $ret['data']['default_payment_id']  = PaymentService::BuyDefaultPayment($params);
        }
        return $ret;
    }

    /**
     * 充值支付
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-03-15T23:51:50+0800
     * @param   [array]          $params [输入参数]
     */
    public function Pay($params = [])
    {
        // 用户
        $params['user'] = $this->user;
        $params['operate_id'] = $this->user['id'];
        $params['operate_name'] = $this->user['user_name_view'];
        $params['plugins_config'] = $this->plugins_config;
        return PayService::Pay($params);
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
        $params['user_type'] = 'user';
        $params['user'] = $this->user;
        $params['plugins_config'] = $this->plugins_config;
        return RechargeService::RechargeDelete($params);
    }

    /**
     * 支付状态校验
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-03-15T23:51:50+0800
     * @param   [array]          $params [输入参数]
     */
    public function PayCheck($params = [])
    {
        $params['user'] = $this->user;
        $params['plugins_config'] = $this->plugins_config;
        return PayService::RechargePayCheck($params);
    }
}
?>