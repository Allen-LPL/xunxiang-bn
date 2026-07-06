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
namespace app\plugins\points\form\admin;

use app\service\BrandService;
use app\service\BrandCategoryService;

/**
 * 积分商城扫码动态表单
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-05-16
 * @desc    description
 */
class Scan
{
    // 基础条件
    public $condition_base = [];

    // 品牌和分类
    public $brand_list;
    public $brand_category_list;

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
        $this->brand_list = BrandService::CategoryBrand();
        $this->brand_category_list = $this->BrandCategoryList();
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
                    'label'         => '所属平台',
                    'view_type'     => 'field',
                    'view_key'      => 'platform_name',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'select',
                        'form_name'         => 'platform',
                        'where_type'        => 'in',
                        'data'              => MyConst('common_platform_type'),
                        'data_key'          => 'value',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '品牌分类',
                    'view_type'     => 'field',
                    'view_key'      => 'brand_category_name',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'select',
                        'form_name'         => 'brand_category_id',
                        'where_type'        => 'in',
                        'data'              => $this->brand_category_list,
                        'data_key'          => 'id',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '品牌',
                    'view_type'     => 'field',
                    'view_key'      => 'brand_name',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'select',
                        'form_name'         => 'brand_id',
                        'where_type'        => 'in',
                        'data'              => $this->brand_list,
                        'data_key'          => 'id',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '是否启用',
                    'view_type'     => 'status',
                    'view_key'      => 'is_enable',
                    'post_url'      => PluginsAdminUrl('points', 'scan', 'statusupdate'),
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
                    'label'         => '赠送积分',
                    'view_type'     => 'field',
                    'view_key'      => 'integral',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'section',
                    ],
                ],
                [
                    'label'         => '二维码总数',
                    'view_type'     => 'field',
                    'view_key'      => 'qrcode_count',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'section',
                    ],
                ],
                [
                    'label'         => '二维码使用总数',
                    'view_type'     => 'field',
                    'view_key'      => 'qrcode_use_count',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'section',
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
                    'view_key'      => '../../../plugins/points/view/admin/scan/module/operate',
                    'align'         => 'center',
                    'fixed'         => 'right',
                ],
            ],
            // 数据配置
            'data'  => [
                'table_name'            => 'PluginsPointsScan',
                'data_handle'           => 'ScanService::DataListHandle',
                'detail_action'         => ['detail', 'saveinfo', 'downloadinfo'],
                'is_handle_time_field'  => 1,
                'is_fixed_name_field'   => 1,
                'fixed_name_data'       => [
                    'platform'    => [
                        'data'  => MyConst('common_platform_type'),
                        'field' => 'platform_name',
                        'key'   => 'name',
                    ],
                    'brand_id'    => [
                        'data'  => array_column($this->brand_list, 'name', 'id'),
                        'field' => 'brand_name',
                    ],
                    'brand_category_id'    => [
                        'data'  => array_column($this->brand_category_list, 'name', 'id'),
                        'field' => 'brand_category_name',
                    ],
                ],
            ],
        ];
    }

    /**
     * 品牌分类
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-06-19
     * @desc    description
     */
    public function BrandCategoryList()
    {
        $ret = BrandCategoryService::BrandCategoryList(['field'=>'id,name']);
        return isset($ret['data']) ? $ret['data'] : [];
    }
}
?>