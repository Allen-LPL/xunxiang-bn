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
namespace app\plugins\giftcard\form\admin;

use app\plugins\giftcard\service\BaseService;

/**
 * 礼品卡密动态表单
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-05-16
 * @desc    description
 */
class CardSecret
{
    // 基础条件
    public $condition_base = [];

    // 礼品卡id
    public $card_id;

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
        // 当前礼品卡
        $this->card_id = empty($params['cid']) ? 0 : intval($params['cid']);
        $this->condition_base[] = ['card_id', '=', $this->card_id];
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
                'key_field'             => 'id',
                'is_search'             => 1,
                'search_url'            => PluginsAdminUrl('giftcard', 'cardsecret', 'index', ['cid'=>$this->card_id]),
                'is_middle'             => 0,
                'is_data_export_excel'  => 1,
            ],
            // 表单配置
            'form' => [
                [
                    'view_type'         => 'checkbox',
                    'is_checked'        => 0,
                    'checked_text'      => MyLang('reverse_select_title'),
                    'not_checked_text'  => MyLang('select_all_title'),
                    'align'             => 'center',
                    'width'             => 80,
                ],
                [
                    'label'         => '数据id',
                    'view_type'     => 'field',
                    'view_key'      => 'id',
                    'width'         => 130,
                    'is_copy'       => 1,
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'input',
                        'where_type'        => '=',
                    ],
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
                    'label'         => '二维码',
                    'view_type'     => 'images',
                    'view_key'      => 'images',
                    'images_width'  => 50,
                    'width'         => 80,
                ],
                [
                    'label'         => '卡密数据',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/giftcard/view/admin/cardsecret/module/secret_value',
                    'grid_size'     => 'lg',
                ],
                [
                    'label'         => '使用数据',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/giftcard/view/admin/cardsecret/module/use_data',
                    'grid_size'     => 'lg',
                ],
                [
                    'label'         => '是否兑换',
                    'view_type'     => 'field',
                    'view_key'      => 'exchange_name',
                    'is_color'      => 1,
                    'color_key'     => 'is_exchange',
                    'color_style'   => [0=>'#ccc'],
                    'width'         => 130,
                    'search_config' => [
                        'form_type'         => 'select',
                        'form_name'         => 'is_exchange',
                        'where_type'        => 'in',
                        'data'              => MyConst('common_is_text_list'),
                        'data_key'          => 'id',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
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
                    'view_key'      => '../../../plugins/giftcard/view/admin/cardsecret/module/operate',
                    'align'         => 'center',
                    'fixed'         => 'right',
                    'width'         => 60,
                ],
            ],
            // 数据配置
            'data'  => [
                'table_name'            => 'PluginsGiftcardCardSecret',
                'data_handle'           => 'CardSecretService::DataListHandle',
                'is_handle_user_field'  => 1,
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