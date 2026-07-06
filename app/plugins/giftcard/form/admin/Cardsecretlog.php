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

/**
 * 礼品卡密日志动态表单
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-05-16
 * @desc    description
 */
class CardSecretLog
{
    // 基础条件
    public $condition_base = [];

    // 礼品卡密id
    public $card_secret_id;

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
        // 礼品卡密id
        $this->card_secret_id = empty($params['csid']) ? 0 : intval($params['csid']);
        $this->condition_base[] = ['card_secret_id', '=', $this->card_secret_id];
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
                'search_url'    => PluginsAdminUrl('giftcard', 'cardsecretlog', 'index', ['csid'=>$this->card_secret_id]),
                'is_middle'     => 0,
            ],
            // 表单配置
            'form' => [
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
                    'label'         => '客户端ip',
                    'view_type'     => 'field',
                    'view_key'      => 'client_ip',
                    'search_config' => [
                        'form_type'         => 'input',
                    ],
                ],
                [
                    'label'         => '操作系统',
                    'view_type'     => 'field',
                    'view_key'      => 'os',
                    'search_config' => [
                        'form_type'         => 'input',
                    ],
                ],
                [
                    'label'         => '浏览器',
                    'view_type'     => 'field',
                    'view_key'      => 'browser',
                    'search_config' => [
                        'form_type'         => 'input',
                    ],
                ],
                [
                    'label'         => '客户端信息',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/giftcard/view/admin/cardsecretlog/module/client',
                    'grid_size'     => 'lg',
                    'search_config' => [
                        'form_type'         => 'input',
                        'form_name'         => 'client',
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
            ],
            // 数据配置
            'data'  => [
                'table_name'            => 'PluginsGiftcardCardSecretLog',
                'is_handle_user_field'  => 1,
                'is_handle_time_field'  => 1,
            ],
        ];
    }
}
?>