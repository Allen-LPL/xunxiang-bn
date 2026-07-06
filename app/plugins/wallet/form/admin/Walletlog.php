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
namespace app\plugins\wallet\form\admin;

use app\plugins\wallet\service\BaseService;

/**
 * 钱包账户明细动态表单
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-05-16
 * @desc    description
 */
class WalletLog
{
    // 基础条件
    public $condition_base = [];

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
                'key_field'             => 'id',
                'is_search'             => 1,
                'is_data_export_excel'  => 1,
            ],
            // 表单配置
            'form' => [
                [
                    'label'         => '钱包ID',
                    'view_type'     => 'field',
                    'view_key'      => 'wallet_id',
                    'is_list'       => 0,
                    'is_detail'     => 0,
                ],
                [
                    'label'         => '用户ID',
                    'view_type'     => 'field',
                    'view_key'      => 'user_id',
                    'is_list'       => 0,
                    'is_detail'     => 0,
                ],
                [
                    'label'         => '用户信息',
                    'view_type'     => 'module',
                    'view_key'      => 'lib/module/user',
                    'grid_size'     => 'sm',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'             => 'input',
                        'form_name'             => 'user_id',
                        'where_type_custom'     => 'in',
                        'where_value_custom'    => 'SystemModuleUserWhereHandle',
                        'placeholder'           => '请输入用户名/昵称/手机/邮箱',
                    ],
                ],
                [
                    'label'         => '业务类型',
                    'view_type'     => 'field',
                    'view_key'      => 'business_type_name',
                    'is_sort'       => 1,
                    'width'         => 140,
                    'search_config' => [
                        'form_type'         => 'select',
                        'form_name'         => 'business_type',
                        'where_type'        => 'in',
                        'data'              => BaseService::ConstData('business_type_list'),
                        'data_key'          => 'value',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '操作类型',
                    'view_type'     => 'field',
                    'view_key'      => 'operation_type_name',
                    'is_sort'       => 1,
                    'width'         => 140,
                    'is_color'      => 1,
                    'color_key'     => 'operation_type',
                    'color_style'   => [0=>'danger', 1=>'success'],
                    'search_config' => [
                        'form_type'         => 'select',
                        'form_name'         => 'operation_type',
                        'where_type'        => 'in',
                        'data'              => BaseService::ConstData('wallet_operate_type_list'),
                        'data_key'          => 'value',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'              => '金额类型',
                    'view_type'          => 'field',
                    'view_key'           => 'money_type_name',
                    'is_sort'            => 1,
                    'width'              => 140,
                    'is_round_point'     => 1,
                    'round_point_key'    => 'money_type',
                    'round_point_style'  => [0=>'success', 1=>'danger'],
                    'search_config'      => [
                        'form_type'         => 'select',
                        'form_name'         => 'money_type',
                        'where_type'        => 'in',
                        'data'              => BaseService::ConstData('wallet_money_type_list'),
                        'data_key'          => 'value',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '操作金额',
                    'view_type'     => 'field',
                    'view_key'      => 'operation_money',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'section',
                        'is_point'          => 1,
                    ],
                ],
                [
                    'label'         => '原始金额',
                    'view_type'     => 'field',
                    'view_key'      => 'original_money',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'section',
                        'is_point'          => 1,
                    ],
                ],
                [
                    'label'         => '最新金额',
                    'view_type'     => 'field',
                    'view_key'      => 'latest_money',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'section',
                        'is_point'          => 1,
                    ],
                ],
                [
                    'label'         => '变更说明',
                    'view_type'     => 'field',
                    'view_key'      => 'msg',
                    'is_sort'       => 1,
                    'grid_size'     => 'sm',
                    'text_truncate' => 2,
                    'is_popover'    => 1,
                    'search_config' => [
                        'form_type'         => 'input',
                        'where_type'        => 'like',
                    ],
                ],
                [
                    'label'         => '操作人',
                    'view_type'     => 'field',
                    'view_key'      => 'operate_name',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'input',
                        'where_type'        => 'like',
                    ],
                ],
                [
                    'label'         => '操作时间',
                    'view_type'     => 'field',
                    'view_key'      => 'add_time',
                    'search_config' => [
                        'form_type'         => 'datetime',
                    ],
                ],
                [
                    'label'         => MyLang('operate_title'),
                    'view_type'     => 'operate',
                    'view_key'      => '../../../plugins/wallet/view/admin/walletlog/module/operate',
                    'align'         => 'center',
                    'fixed'         => 'right',
                    'width'         => 80,
                ],
            ],
            // 数据配置
            'data'  => [
                'table_name'            => 'PluginsWalletLog',
                'data_handle'           => 'BaseService::WalletLogListHandle',
                'is_page_stats'         => 1,
                'page_stats_data'       => [
                    ['name'=>'操作金额', 'field'=>'operation_money'],
                ],
                'data_params'           => [
                    'user_type' => 'admin',
                ],
            ],
        ];
    }
}
?>