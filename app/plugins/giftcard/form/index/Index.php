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
namespace app\plugins\giftcard\form\index;

use app\service\UserService;
use app\plugins\giftcard\service\BaseService;

/**
 * 用户礼品卡密动态表单
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-05-16
 * @desc    description
 */
class Index
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
        $this->condition_base[] = ['user_id', '=', empty($user) ? 0 : $user['id']];
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
                'is_middle'     => 0,
            ],
            // 表单配置
            'form' => [
                [
                    'label'         => '卡密key',
                    'view_type'     => 'field',
                    'view_key'      => 'secret_key',
                    'width'         => 250,
                    'is_copy'       => 1,
                    'search_config' => [
                        'form_type'         => 'input',
                    ],
                ],
                [
                    'label'         => '卡密类型',
                    'view_type'     => 'field',
                    'view_key'      => 'data_type_name',
                    'is_sort'       => 1,
                    'width'         => 130,
                    'search_config' => [
                        'form_type'         => 'select',
                        'form_name'         => 'data_type',
                        'where_type'        => 'in',
                        'data'              => BaseService::ConstData('card_data_type_list'),
                        'data_key'          => 'value',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '卡密数据',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/giftcard/view/index/index/module/secret_value',
                    'grid_size'     => 'lg',
                ],
                [
                    'label'         => '使用数据',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/giftcard/view/index/index/module/use_data',
                    'grid_size'     => 'lg',
                ],
                [
                    'label'         => '兑换时间',
                    'view_type'     => 'field',
                    'view_key'      => 'exchange_time',
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
                    'label'         => '更新时间',
                    'view_type'     => 'field',
                    'view_key'      => 'upd_time',
                    'search_config' => [
                        'form_type'         => 'datetime',
                    ],
                ],
                [
                    'label'         => MyLang('operate_title'),
                    'view_type'     => 'operate',
                    'view_key'      => '../../../plugins/giftcard/view/index/index/module/operate',
                    'align'         => 'center',
                    'fixed'         => 'right',
                    'width'         => 60,
                ],
            ],
            // 数据配置
            'data'  => [
                'table_name'            => 'PluginsGiftcardCardSecret',
                'data_handle'           => 'CardSecretService::DataListHandle',
                'order_by'              => 'exchange_time desc, id desc',
                'is_handle_time_field'  => 1,
                'is_fixed_name_field'   => 1,
                'fixed_name_data'       => [
                    'is_exchange'    => [
                        'data'  => MyConst('common_is_text_list'),
                        'field' => 'exchange_name',
                    ],
                    'data_type'    => [
                        'data'  => array_column(BaseService::ConstData('card_data_type_list'), 'name', 'value'),
                    ],
                ],
            ],
        ];
    }
}
?>