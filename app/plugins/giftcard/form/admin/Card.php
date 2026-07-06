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
use app\plugins\giftcard\service\CardCategoryService;

/**
 * 礼品卡动态表单
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-05-16
 * @desc    description
 */
class Card
{
    // 基础条件
    public $condition_base = [];

    // 扫码分类
    public $category_list;

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
        // 扫码分类
        $this->category_list = CardCategoryService::CardCategoryAll();
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
                'status_field'  => 'is_enable',
                'is_search'     => 1,
                'is_delete'     => 1,
                'is_middle'     => 0,
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
                    'label'         => '名称',
                    'view_type'     => 'field',
                    'view_key'      => 'name',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'input',
                    ],
                ],
                [
                    'label'         => '分类',
                    'view_type'     => 'field',
                    'view_key'      => 'category_name',
                    'is_sort'       => 1,
                    'width'         => 150,
                    'search_config' => [
                        'form_type'         => 'select',
                        'form_name'         => 'category_id',
                        'where_type'        => 'in',
                        'data'              => $this->category_list,
                        'data_key'          => 'id',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '是否启用',
                    'view_type'     => 'status',
                    'view_key'      => 'is_enable',
                    'post_url'      => PluginsAdminUrl('giftcard', 'card', 'statusupdate'),
                    'is_form_su'    => 1,
                    'align'         => 'center',
                    'width'         => 130,
                    'search_config' => [
                        'form_type'         => 'select',
                        'where_type'        => 'in',
                        'data'              => MyConst('common_is_text_list'),
                        'data_key'          => 'id',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '卡密类型',
                    'view_type'     => 'field',
                    'view_key'      => 'data_type_name',
                    'is_sort'       => 1,
                    'width'         => 150,
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
                    'label'         => '生成方式',
                    'view_type'     => 'field',
                    'view_key'      => 'generate_type_name',
                    'is_sort'       => 1,
                    'width'         => 140,
                    'search_config' => [
                        'form_type'         => 'select',
                        'form_name'         => 'generate_type',
                        'where_type'        => 'in',
                        'data'              => BaseService::ConstData('card_generate_type_list'),
                        'data_key'          => 'value',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '礼品卡前缀',
                    'view_type'     => 'field',
                    'view_key'      => 'prefix',
                    'is_sort'       => 1,
                    'width'         => 150,
                    'search_config' => [
                        'form_type'         => 'input',
                    ],
                ],
                [
                    'label'         => '卡密数据',
                    'view_type'     => 'field',
                    'view_key'      => 'secret_value_text',
                    'grid_size'     => 'sm',
                ],
                [
                    'label'         => '礼品卡总数',
                    'view_type'     => 'field',
                    'view_key'      => 'card_count',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'section',
                    ],
                ],
                [
                    'label'         => '已使用总数',
                    'view_type'     => 'field',
                    'view_key'      => 'card_exchange_count',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'section',
                    ],
                ],
                [
                    'label'         => '备注',
                    'view_type'     => 'field',
                    'view_key'      => 'note',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'input',
                        'where_type'        => 'like',
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
                    'view_key'      => '../../../plugins/giftcard/view/admin/card/module/operate',
                    'align'         => 'center',
                    'fixed'         => 'right',
                ],
            ],
            // 数据配置
            'data'  => [
                'table_name'            => 'PluginsGiftcardCard',
                'data_handle'           => 'CardService::DataListHandle',
                'detail_action'         => ['detail', 'saveinfo', 'downloadinfo'],
                'is_handle_time_field'  => 1,
                'is_fixed_name_field'   => 1,
                'fixed_name_data'       => [
                    'category_id'    => [
                        'data'  => array_column($this->category_list, 'name', 'id'),
                        'field' => 'category_name',
                    ],
                    'data_type'    => [
                        'data'  => array_column(BaseService::ConstData('card_data_type_list'), 'name', 'value'),
                    ],
                    'generate_type'    => [
                        'data'  => array_column(BaseService::ConstData('card_generate_type_list'), 'name', 'value'),
                    ],
                ],
            ],
        ];
    }
}
?>