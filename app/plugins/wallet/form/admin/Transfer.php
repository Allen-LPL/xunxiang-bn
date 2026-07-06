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

/**
 * 转账记录动态表单
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-05-16
 * @desc    description
 */
class Transfer
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
                    'label'         => '发起钱包ID',
                    'view_type'     => 'field',
                    'view_key'      => 'send_wallet_id',
                    'is_list'       => 0,
                    'is_detail'     => 0,
                ],
                [
                    'label'         => '收款钱包ID',
                    'view_type'     => 'field',
                    'view_key'      => 'receive_wallet_id',
                    'is_list'       => 0,
                    'is_detail'     => 0,
                ],
                [
                    'label'         => '发起用户ID',
                    'view_type'     => 'field',
                    'view_key'      => 'send_user_id',
                    'is_list'       => 0,
                    'is_detail'     => 0,
                ],
                [
                    'label'         => '收款用户ID',
                    'view_type'     => 'field',
                    'view_key'      => 'receive_user_id',
                    'is_list'       => 0,
                    'is_detail'     => 0,
                ],
                [
                    'label'         => '发起用户',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/wallet/view/admin/transfer/module/send_user',
                    'grid_size'     => 'sm',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'             => 'input',
                        'form_name'             => 'send_user_id',
                        'where_type_custom'     => 'in',
                        'where_value_custom'    => 'SystemModuleUserWhereHandle',
                        'placeholder'           => '请输入用户名/昵称/手机/邮箱',
                    ],
                ],
                [
                    'label'         => '收款用户',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/wallet/view/admin/transfer/module/receive_user',
                    'grid_size'     => 'sm',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'             => 'input',
                        'form_name'             => 'receive_user_id',
                        'where_type_custom'     => 'in',
                        'where_value_custom'    => 'SystemModuleUserWhereHandle',
                        'placeholder'           => '请输入用户名/昵称/手机/邮箱',
                    ],
                ],
                [
                    'label'         => '转账单号',
                    'view_type'     => 'field',
                    'view_key'      => 'transfer_no',
                    'is_sort'       => 1,
                    'is_copy'       => 1,
                    'width'         => 200,
                    'search_config' => [
                        'form_type'         => 'input',
                    ],
                ],
                [
                    'label'         => '转账金额',
                    'view_type'     => 'field',
                    'view_key'      => 'money',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'section',
                        'is_point'          => 1,
                    ],
                ],
                [
                    'label'         => '备注',
                    'view_type'     => 'field',
                    'view_key'      => 'note',
                    'text_truncate' => 2,
                    'is_popover'    => 1,
                    'grid_size'     => 'sm',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'input',
                        'where_type'        => 'like',
                    ],
                ],
                [
                    'label'         => '转账时间',
                    'view_type'     => 'field',
                    'view_key'      => 'add_time',
                    'search_config' => [
                        'form_type'         => 'datetime',
                    ],
                ],
                [
                    'label'         => MyLang('operate_title'),
                    'view_type'     => 'operate',
                    'view_key'      => '../../../plugins/wallet/view/admin/transfer/module/operate',
                    'align'         => 'center',
                    'fixed'         => 'right',
                    'width'         => 80,
                ],
            ],
            // 数据配置
            'data'  => [
                'table_name'            => 'PluginsWalletTransfer',
                'data_handle'           => 'BaseService::TransferListHandle',
                'is_page_stats'         => 1,
                'page_stats_data'       => [
                    ['name'=>'转账金额', 'field'=>'money'],
                ],
                'data_params'           => [
                    'user_type' => 'admin',
                ],
            ],
        ];
    }
}
?>