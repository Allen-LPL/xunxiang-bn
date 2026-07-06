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
namespace app\plugins\wallet\form\index;

use app\service\UserService;
use app\plugins\wallet\service\BaseService;

/**
 * 充值动态表单
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-05-16
 * @desc    description
 */
class Recharge
{
    // 基础条件
    public $condition_base = [];

    /**
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-06-29
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function __construct($params = [])
    {
        // 当前用户
        $user = UserService::LoginUserInfo();
        $user_id = empty($user['id']) ? 0 : $user['id'];
        $this->condition_base[] = ['user_id', '=', $user_id];
    }

    /**
     * 入口
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-05-16
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Run($params = [])
    {
        return [
            // 基础配置
            'base' => [
                'key_field'     => 'id',
                'is_search'     => 1,
            ],
            // 表单配置
            'form' => [
                [
                    'label'         => '充值单号',
                    'view_type'     => 'field',
                    'view_key'      => 'recharge_no',
                    'is_sort'       => 1,
                    'width'         => 200,
                    'search_config' => [
                        'form_type'         => 'input',
                    ],
                ],
                [
                    'label'              => '状态',
                    'view_type'          => 'field',
                    'view_key'           => 'status_name',
                    'is_sort'            => 1,
                    'width'              => 120,
                    'is_round_point'     => 1,
                    'round_point_key'    => 'status',
                    'round_point_style'  => [0=>'warning', 1=>'success'],
                    'search_config'      => [
                        'form_type'         => 'select',
                        'form_name'         => 'status',
                        'where_type'        => 'in',
                        'data'              => BaseService::ConstData('recharge_status_list'),
                        'data_key'          => 'value',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '充值金额',
                    'view_type'     => 'field',
                    'view_key'      => 'money',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'section',
                        'is_point'          => 1,
                    ],
                ],
                [
                    'label'         => '支付金额',
                    'view_type'     => 'field',
                    'view_key'      => 'pay_money',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'section',
                        'is_point'          => 1,
                    ],
                ],
                [
                    'label'         => '支付方式',
                    'view_type'     => 'field',
                    'view_key'      => 'payment_name',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'select',
                        'form_name'         => 'payment',
                        'data'              => BaseService::BuyPaymentList(),
                        'data_key'          => 'payment',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                        
                    ],
                ],
                [
                    'label'         => '支付时间',
                    'view_type'     => 'field',
                    'view_key'      => 'pay_time',
                    'search_config' => [
                        'form_type'         => 'datetime',
                    ],
                ],
                [
                    'label'         => '创建时间',
                    'view_type'     => 'field',
                    'view_key'      => 'add_time',
                    'search_config' => [
                        'form_type'         => 'datetime',
                    ],
                ],
                [
                    'label'         => MyLang('operate_title'),
                    'view_type'     => 'operate',
                    'view_key'      => '../../../plugins/wallet/view/index/recharge/module/operate',
                    'align'         => 'center',
                    'fixed'         => 'right',
                ],
            ],
            // 数据配置
            'data'  => [
                'table_name'            => 'PluginsWalletRecharge',
                'data_handle'           => 'BaseService::RechargeListHandle',
                'is_page_stats'         => 1,
                'page_stats_data'       => [
                    ['name'=>'充值金额', 'field'=>'money'],
                    ['name'=>'支付金额', 'field'=>'pay_money'],
                ],
            ],
        ];
    }
}
?>