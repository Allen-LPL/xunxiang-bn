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
namespace app\plugins\points;

use think\facade\Db;
use app\service\UserService;
use app\service\IntegralService;
use app\service\PluginsAdminService;
use app\plugins\points\service\BaseService;
use app\plugins\points\service\PointsService;
use app\plugins\points\service\RewardUserIntegralService;

/**
 * 积分商城 - 钩子入口
 * @author   Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2019-04-11
 * @desc    description
 */
class Hook
{
    // 配置信息
    private $plugins_config;

    // 当前模块/控制器/方法
    private $module_name;
    private $controller_name;
    private $action_name;
    private $mca;

    private $pluginsname;
    private $pluginscontrol;
    private $pluginsaction;
    private $pca;

    // 是否开启商品兑换
    private $is_integral_exchange;
    // 是否商品纯兑换模式
    private $is_pure_exchange_modal;
    // 积分商城首页
    private $is_integral_home;
    // 商品详情去掉加入购物车
    private $is_goods_detail_integral_exchange_hide_cart;
    // 商品管理列表（可配置兑换积分），支持多商户
    private $is_admin_goods_integral_exchange;
    private $is_admin_goods_integral_exchange_shop;

    // 导航名称
    private $nav_title = '积分商城';

    /**
     * 应用响应入口
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-11
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function handle($params = [])
    {
        // 钩子名称
        if(!empty($params['hook_name']))
        {
            // 当前模块/控制器/方法
            $this->module_name = RequestModule();
            $this->controller_name = RequestController();
            $this->action_name = RequestAction();
            $this->mca = $this->module_name.$this->controller_name.$this->action_name;

            // 插件
            $this->pluginsname = PluginsRequestName();
            $this->pluginscontrol = PluginsRequestController();
            $this->pluginsaction = PluginsRequestAction();
            $this->pca = $this->pluginsname.$this->pluginscontrol.$this->pluginsaction;

            // 配置信息
            $config = BaseService::BaseConfig();
            $this->plugins_config = empty($config['data']) ? [] : $config['data'];

            // 快捷导航名称
            $this->nav_title = empty($this->plugins_config['application_name']) ? '积分商城' : $this->plugins_config['application_name'];

            // 快捷导航入口
            $is_user_quick = isset($this->plugins_config['is_user_quick']) ? intval($this->plugins_config['is_user_quick']) : 0;

            // 是否开启商品兑换
            $this->is_integral_exchange = isset($this->plugins_config['is_integral_exchange']) && $this->plugins_config['is_integral_exchange'] == 1;

            // 是否商品纯兑换模式
            $this->is_pure_exchange_modal = isset($this->plugins_config['is_pure_exchange_modal']) && $this->plugins_config['is_pure_exchange_modal'] == 1;

            // 积分商城首页
            $this->is_integral_home = in_array($this->pca, ['pointsindexindex']);

            // 商品详情去掉加入购物车
            $this->is_goods_detail_integral_exchange_hide_cart = isset($this->plugins_config['is_goods_detail_integral_exchange_hide_cart']) && $this->plugins_config['is_goods_detail_integral_exchange_hide_cart'] == 1;

            // 商品管理列表（可配置兑换积分），支持多商户
            $this->is_admin_goods_integral_exchange = isset($this->plugins_config['is_admin_goods_integral_exchange']) && $this->plugins_config['is_admin_goods_integral_exchange'] == 1;
            $this->is_admin_goods_integral_exchange_shop = $this->is_admin_goods_integral_exchange && isset($this->plugins_config['is_admin_goods_integral_exchange_shop']) && $this->plugins_config['is_admin_goods_integral_exchange_shop'] == 1;

            // 是否引入多商户样式
            $is_shop_admin_style = $this->module_name == 'index' && $this->is_admin_goods_integral_exchange_shop && in_array($this->pca, ['pointsgoodsindex']);

            // 走钩子
            $ret = '';
            $points_style = ['indexbuyindex'];
            switch($params['hook_name'])
            {
                // 公共css
                case 'plugins_css' :
                    $ret = [];
                    if(in_array($this->mca, $points_style))
                    {
                        $ret[] = 'static/plugins/points/css/index/style.css';
                    }
                    // 引入多商户样式
                    if($is_shop_admin_style)
                    {
                        $ret[] = 'static/plugins/shop/css/index/public/shop_admin.css';
                    }
                    break;

                // 公共js
                case 'plugins_js' :
                    if(in_array($this->mca, $points_style))
                    {
                        $ret = 'static/plugins/points/js/index/style.js';
                    }
                    break;

                // web端快捷导航操作按钮
                case 'plugins_service_quick_navigation_pc' :
                    if($is_user_quick == 1)
                    {
                        $this->WebQuickNavigationHandle($params);
                    }
                    break;

                // 小程序/APP端快捷导航操作按钮
                case 'plugins_service_quick_navigation_h5' :
                case 'plugins_service_quick_navigation_weixin' :
                case 'plugins_service_quick_navigation_alipay' :
                case 'plugins_service_quick_navigation_baidu' :
                case 'plugins_service_quick_navigation_qq' :
                case 'plugins_service_quick_navigation_toutiao' :
                    if($is_user_quick == 1)
                    {
                        $this->MiniQuickNavigationHandle($params);
                    }
                    break;

                // 在前面添加导航
                case 'plugins_service_navigation_header_handle' :
                    $ret = $this->NavigationHeaderHandle($params);
                    break;

                // 商品列表
                case 'plugins_service_goods_list_handle_end' :
                    $this->GoodslistHandle($params);
                    break;

                // 商品规格基础数据
                case 'plugins_service_goods_spec_base' :
                    if($this->module_name != 'admin')
                    {
                        $this->GoodsSpecBase($params);
                    }
                    break;

                // 购买提交订单页面隐藏域html
                case 'plugins_view_buy_form_inside' :
                    $ret = $this->BuyFormInsideContent($params);
                    break;

                // 订单确认页面基础信息顶部
                case 'plugins_view_buy_base_confirm_top' :
                    $ret = $this->BuyBaseConfirmTopContent($params);
                    break;

                // 积分兑换/抵扣计算
                case 'plugins_service_buy_group_goods_handle' :
                    $this->DeductionExchangeCalculate($params);
                    break;

                // 订单添加成功处理
                case 'plugins_service_buy_order_insert_end' :
                    $ret = $this->OrderInsertSuccessHandle($params);
                    break;

                // 订单状态改变处理
                case 'plugins_service_order_status_change_history_success_handle' :
                    $ret = $this->OrderStatusChangeHandle($params);
                    break;

                // 下单页面积分数据
                case 'plugins_service_base_data_return_api_buy_index' :
                    $ret = $this->BuyResultHandle($params);
                    break;

                // 用户注册
                case 'plugins_service_user_register_end' :
                    $ret = $this->UserRegisterSuccessHandle($params);
                    break;

                // 商品详情页面导航购买按钮处理
                case 'plugins_service_goods_buy_nav_button_handle' :
                    $this->GoodsDetailBuyNavButtonContent($params);
                    break;

                // 拖拽可视化-页面地址
                case 'plugins_layout_service_url_value_begin' :
                    $this->LayoutServiceUrlValueBegin($params);
                    break;
                // 拖拽可视化-页面名称
                case 'plugins_layout_service_pages_list' :
                    $this->LayoutServicePagesList($params);
                    break;

                // 商品价格下面钩子
                case 'plugins_view_goods_detail_panel_price_bottom' :
                    // 开兑换模式，并且非纯兑换模式则展示积分兑换信息
                    if(APPLICATION == 'web' && $this->mca == 'indexgoodsindex' && !empty($params['goods']) && !empty($params['goods']['plugins_points_data']) && isset($params['goods']['plugins_points_data']['is_goods_detail_show']) && $params['goods']['plugins_points_data']['is_goods_detail_show'] == 1)
                    {
                        $ret = $this->GoodsDetailViewPriceBottom($params);
                    }
                    break;

                // 后台商品动态列表
                case 'plugins_module_form_admin_goods_index' :
                case 'plugins_module_form_admin_goods_detail' :
                    $this->AdminFormGoodsHandle($params);
                    break;

                // 后台商品保存页面
                case 'plugins_view_admin_goods_save' :
                    $ret = $this->AdminGoodsSaveView($params);
                    break;

                // 商品保存前处理
                case 'plugins_service_goods_save_handle' :
                    $ret = $this->GoodsSaveBeginHandle($params);
                    break;

                // 后台商品管理商品操作
                case 'plugins_view_admin_goods_list_operate' :
                    if($this->is_admin_goods_integral_exchange)
                    {
                        $ret = $this->AdminGoodsListOperate($params);
                    }
                    break;

                // 多商户商品管理操作
                case 'plugins_view_index_plugins_shop_goods_list_operate' :
                    if($this->is_admin_goods_integral_exchange_shop)
                    {
                        $ret = $this->ShopGoodsListOperate($params);
                    }
                    break;

                // 下单处理纯兑换验证
                case 'plugins_service_buy_handle' :
                    if($this->is_integral_exchange && $this->is_pure_exchange_modal)
                    {
                        $ret = $this->PureExchangeBuyCheck($params);
                    }
                    break;

                // diyapi初始化
                case 'plugins_service_diyapi_init_data' :
                    $this->DiyApiInitDataHandle($params);
                    break;
            }
            return $ret;
        }
    }

    /**
     * diyapi初始化
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2023-03-23
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function DiyApiInitDataHandle($params = [])
    {
        // 页面链接
        if(isset($params['data']['page_link_list']) && is_array($params['data']['page_link_list']))
        {
            foreach($params['data']['page_link_list'] as &$lv)
            {
                if(isset($lv['data']) && isset($lv['type']) && $lv['type'] == 'plugins')
                {
                    $lv['data'][] = [
                        'name'  => '积分商城',
                        'type'  => 'points',
                        'data'  => [
                            ['name'=>'积分首页', 'page'=>'/pages/plugins/points/index/index'],
                        ],
                    ];
                    break;
                }
            }
        }
    }

    /**
     * 下单处理纯兑换验证
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2025-05-30
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function PureExchangeBuyCheck($params)
    {
        $ret = DataReturn('success', 0);
        if(!empty($params['data']) && !empty($params['data']['goods']))
        {
            $goods_integral = 0;
            foreach($params['data']['goods'] as $v)
            {
                if(!empty($v['goods_items']))
                {
                    foreach($v['goods_items'] as $vs)
                    {
                        if(!empty($vs['plugins_points_data']) && isset($vs['plugins_points_data']['points_integral']))
                        {
                            $goods_integral += $vs['plugins_points_data']['points_integral'];
                        }
                    }
                }
            }
            if($goods_integral > 0)
            {
                $user_id = MyInput('user_id');
                if(empty($user_id))
                {
                    $user = UserService::LoginUserInfo();
                    if(!empty($user))
                    {
                        $user_id = $user['id'];
                    }
                }
                if(!empty($user_id))
                {
                    $user_integral = IntegralService::UserIntegral($user_id);
                    if($user_integral['integral'] < $goods_integral)
                    {
                        $ret = DataReturn('积分兑换不足(持有'.$user_integral['integral'].'，需要'.$goods_integral.')', -1);
                    }
                }
            }
        }
        return $ret;
    }

    /**
     * 商品管理列表操作 - 多商户
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2023-05-04
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function ShopGoodsListOperate($params = [])
    {
        return MyView('../../../plugins/points/view/index/goods/goods_button', [
            'plugins_data'    => $params['data'],
            'plugins_config'  => $this->plugins_config,
        ]);
    }

    /**
     * 商品管理列表操作
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2023-05-04
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function AdminGoodsListOperate($params = [])
    {
        if(AdminIsPower('goods', 'index'))
        {
            return MyView('../../../plugins/points/view/admin/goods/goods_button', [
                'plugins_data'    => $params['data'],
                'plugins_config'  => $this->plugins_config,
            ]);
        }
    }

    /**
     * 商品保存前处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-01
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function GoodsSaveBeginHandle($params = [])
    {
        if(!empty($params['params']) && $this->is_admin_goods_integral_exchange)
        {
            $params['data']['plugins_points_exchange_integral'] = isset($params['params']['plugins_points_exchange_integral']) ? intval($params['params']['plugins_points_exchange_integral']) : 0;
            $params['data']['plugins_points_exchange_price'] = isset($params['params']['plugins_points_exchange_price']) ? floatval($params['params']['plugins_points_exchange_price']) : 0;
            // 是否积分商城首页显示（商品管理直接勾选，替代应用配置选品）
            $params['data']['plugins_points_is_home_show'] = (isset($params['params']['plugins_points_is_home_show']) && intval($params['params']['plugins_points_is_home_show']) == 1) ? 1 : 0;
        }
    }

    /**
     * 商品信息保存页面
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-07-23
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    private function AdminGoodsSaveView($params = [])
    {
        // 积分兑换填写
        if($this->is_admin_goods_integral_exchange)
        {
            return MyView('../../../plugins/points/view/admin/goods/goods_edit', [
                'plugins_data' => empty($params['data']) ? [] : $params['data'],
            ]);
        }
    }

    /**
     * 后台商品动态列表兑换积分信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-04
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    private function AdminFormGoodsHandle($params = [])
    {
        if($this->is_admin_goods_integral_exchange)
        {
            array_splice($params['data']['form'], 3, 0, [
                [
                    'label'         => '兑换积分',
                    'view_type'     => 'field',
                    'view_key'      => 'plugins_points_exchange_integral',
                    'search_config' => [
                        'form_type'     => 'section',
                    ],
                ],
                [
                    'label'         => '兑换加金额',
                    'view_type'     => 'field',
                    'view_key'      => 'plugins_points_exchange_price',
                    'search_config' => [
                        'form_type'     => 'section',
                        'is_point'      => 1,
                    ],
                ]
            ]);
        }
    }

    /**
     * 商品价格下面处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-07-25
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    private function GoodsDetailViewPriceBottom($params = [])
    {
        return MyView('../../../plugins/points/view/index/public/detail_goods_price', $params['goods']['plugins_points_data']);
    }

    /**
     * 拖拽可视化-页面名称
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2023-03-23
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function LayoutServicePagesList($params = [])
    {
        $params['data']['plugins']['data'][] = [
            'name'  => '积分商城',
            'value' => 'points',
            'data'  => [
                [ 'value' => 'home', 'name' => MyLang('home_title')],
            ],
        ];
    }

    /**
     * 拖拽可视化-页面地址
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2023-03-23
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function LayoutServiceUrlValueBegin($params = [])
    {
        $params['static_url_web_arr']['plugins-points-home'] = PluginsAdminService::PluginsSecondDomainUrl('points', true);
        $params['static_url_app_arr']['plugins-points-home'] = '/pages/plugins/points/index/index';
    }

    /**
     * 商品详情页面导航购买按钮处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-19
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function GoodsDetailBuyNavButtonContent($params = [])
    {
        if($this->is_integral_exchange && !empty($params['goods']) && !empty($params['goods']['plugins_points_data']) && !empty($params['data']) && is_array($params['data']))
        {            
            // 当前商品可兑换的积分
            $points_value = isset($params['goods']['plugins_points_exchange_integral']) ? (empty($params['goods']['plugins_points_exchange_integral']) ? 0 : $params['goods']['plugins_points_exchange_integral']) : Db::name('Goods')->where(['id'=>$params['goods']['id']])->value('plugins_points_exchange_integral');
            if($points_value > 0)
            {
                // 当前用户积分
                static $plugins_points_user_integral = false;
                if($plugins_points_user_integral === false)
                {
                    // 是否指定用户id、通过请求接口地址指定用户id，则取当前登录用户
                    $user_id = MyInput('user_id');
                    if(empty($user_id))
                    {
                        $user = UserService::LoginUserInfo();
                        if(!empty($user))
                        {
                            $user_id = $user['id'];
                        }
                    }
                    if(empty($user_id))
                    {
                        $plugins_points_user_integral = 0;
                    } else {
                        $user_integral = IntegralService::UserIntegral($user_id);
                        $plugins_points_user_integral = empty($user_integral) || empty($user_integral['integral']) ? 0 : $user_integral['integral'];
                    }
                }

                // 兑换按钮名称
                foreach($params['data'] as $k=>$v)
                {
                    if(isset($v['type']) && $v['type'] == 'buy')
                    {
                        // 积分是否满足
                        if($plugins_points_user_integral >= $points_value)
                        {
                            $params['data'][$k]['name'] = ($this->is_pure_exchange_modal == 1) ? '兑换' : '购买/兑换';
                        } else {
                            // 纯兑换模式则移除购买，并提示错误
                            if($this->is_pure_exchange_modal)
                            {
                                unset($params['data'][$k]);
                                $params['error'] = '积分不足';
                            }
                        }
                    } else {
                        if(isset($v['type']))
                        {
                            // 是否去除展示，加入购物车的操作按钮，或积分不足并且是纯兑换则去除非购买按钮
                            if($this->is_goods_detail_integral_exchange_hide_cart || ($plugins_points_user_integral < $points_value && $this->is_pure_exchange_modal))
                            {
                                if(in_array($v['type'], ['show', 'cart']))
                                {
                                    unset($params['data'][$k]);
                                }
                            }
                        }
                    }
                }
                $params['data'] = array_values($params['data']);
            }
        }
    }

    /**
     * 用户注册
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-06-03
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function UserRegisterSuccessHandle($params = [])
    {
        if(isset($this->plugins_config['is_register_reward_integral']) && $this->plugins_config['is_register_reward_integral'] == 1 && !empty($params) && !empty($params['user_id']))
        {
            return RewardUserIntegralService::Run($params['user_id'], $this->plugins_config);
        }
    }

    /**
     * 下单接口数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-06
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function BuyResultHandle($params = [])
    {
        if(!empty($params['data']) && !empty($params['data']['goods_list']))
        {
            // 下单页面用户积分信息
            $params['data']['plugins_points_data'] = PointsService::BuyUserPointsData($this->plugins_config, $params['data']['goods_list'], $params['params']);
        }
    }

    /**
     * 订单状态改变处理,状态为取消|关闭时释放积分
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-12-24
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function OrderStatusChangeHandle($params = [])
    {
        if(!empty($params['data']) && isset($params['data']['new_status']) && in_array($params['data']['new_status'], [5,6]) && !empty($params['order_id']))
        {
            return PointsService::OrderStatusChangeHandle($params['order_id']);
        }
    }

    /**
     * 订单添加成功处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-12-24
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function OrderInsertSuccessHandle($params = [])
    {
        return PointsService::OrderInsertSuccessHandle($params);
    }

    /**
     * 积分兑换/抵扣计算
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-12-24
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function DeductionExchangeCalculate($params = [])
    {
        if(!empty($params['params']) && !empty($params['params']['params']) && !empty($params['data']))
        {
            // 是否使用积分
            if(BaseService::IsUsePoints($this->plugins_config, $params['data'], $params['params']['params']))
            {
                PointsService::BuyUserPointsHandle($this->plugins_config, $params['data'], $params['params']['params']);
            }
        }
    }

    /**
     * 订单确认页面基础信息顶部
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-12-24
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function BuyBaseConfirmTopContent($params = [])
    {
        if(!empty($params['data']) && !empty($params['data']['goods']) && isset($params['params']))
        {
            // 下单页面用户积分信息
            $buy_user_points = PointsService::BuyUserPointsData($this->plugins_config, $params['data']['goods'], $params['params']);
            return MyView('../../../plugins/points/view/index/public/buy', [
                'buy_user_points'  => $buy_user_points,
            ]);
        }
    }

    /**
     * 订单确认页面基础信息顶部
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-12-24
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function BuyFormInsideContent($params = [])
    {
        if(!empty($params['params']) && !empty($params['data']) && !empty($params['data']['goods']))
        {
            $is_checked = BaseService::IsUsePoints($this->plugins_config, $params['data']['goods'], $params['params']);
            return '<input type="hidden" name="is_points" value="'.($is_checked ? 1 : 0).'" />
                    <input type="hidden" name="actual_use_integral" value="'.(empty($params['params']['actual_use_integral']) ? 0 : intval($params['params']['actual_use_integral'])).'" />';
        }
        return '';
    }

    /**
     * 商品规格基础数据
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-03-26
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function GoodsSpecBase($params = [])
    {
        if($this->module_name != 'admin' && $this->is_pure_exchange_modal && !empty($params['data']['spec_base']) && !empty($params['data']['spec_base']['goods_id']))
        {
            $goods_exchange = PointsService::GoodsExchangeData($this->plugins_config, $params['data']['spec_base']['goods_id']);
            if(!empty($goods_exchange[$params['data']['spec_base']['goods_id']]))
            {
                $temp = $goods_exchange[$params['data']['spec_base']['goods_id']];
                // 是否有加金额
                $points_value = ($temp['price'] > 0) ? $temp['price'] : $temp['integral'];
                $params['data']['spec_base']['price'] = $points_value;
                $params['data']['spec_base']['original_price'] = 0;
            }
        }
    }

    /**
     * 商品详情
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-11
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function GoodslistHandle($params = [])
    {
        if($this->module_name != 'admin' && $this->is_integral_exchange && !empty($params['data']) && is_array($params['data']))
        {
            // key字段
            $key_field = empty($params['params']['data_key_field']) ? 'id' : $params['params']['data_key_field'];

            // 积分兑换商品（这里直接去读取商品积分、避免有些业务读取商品指定了字段导致没有读取到当前积分商城增加的积分兑换字段）
            $goods_exchange = PointsService::GoodsExchangeData($this->plugins_config, array_column($params['data'], $key_field));
            if(!empty($goods_exchange))
            {
                // 价格标题icon
                $goods_detail_icon = empty($this->plugins_config['goods_detail_icon']) ? '积分兑换' : $this->plugins_config['goods_detail_icon'];

                foreach($params['data'] as &$goods)
                {
                    if(!empty($goods[$key_field]))
                    {
                        $goods_id = $goods[$key_field];
                        if(!empty($goods_exchange[$goods_id]))
                        {
                            // 兑换所需积分
                            $points_unit = '积分';
                            $show_price_symbol = $goods['show_price_symbol'];
                            $show_price_unit = $goods['show_price_unit'];
                            $temp = $goods_exchange[$goods_id];
                            $points_value = $temp['integral'];
                            $tips_text = $points_value.$points_unit;

                            // 是否有加金额
                            if($temp['price'] > 0)
                            {
                                $points_value = $temp['price'];
                                $show_price_symbol = $temp['integral'].$points_unit.' + '.$show_price_symbol;
                                $tips_text = $show_price_symbol.$points_value;
                            } else {
                                $show_price_symbol = '';
                                $show_price_unit = ' '.$points_unit.$show_price_unit;
                            }

                            // icon
                            if(!empty($this->plugins_config['goods_detail_title_icon']))
                            {
                                $goods['plugins_view_icon_data'][] = [
                                    'name'      => $this->plugins_config['goods_detail_title_icon'],
                                    'bg_color'  => '#fb7364',
                                    'color'     => '#fff',
                                ];
                            }

                            // 面板
                            if(!empty($this->plugins_config['goods_detail_panel']))
                            {
                                $goods['plugins_view_panel_data'][] = str_replace('{$integral}', $tips_text, $this->plugins_config['goods_detail_panel']);
                            }

                            // 积分兑换数据
                            $goods['plugins_points_data'] = [
                                'points_icon'           => $goods_detail_icon,
                                'points_integral'       => $temp['integral'],
                                'points_price'          => $temp['price'],
                                'points_value'          => $points_value,
                                'points_unit'           => $points_unit,
                                'is_goods_detail_show'  => ($this->is_integral_exchange && !$this->is_pure_exchange_modal) ? 1 : 0,
                            ];

                            // 是否隐藏售价和原价
                            if($this->is_pure_exchange_modal || $this->is_integral_home)
                            {
                                $goods['price'] = $points_value;
                                $goods['min_price'] = $points_value;
                                $goods['max_price'] = $points_value;
                                $goods['original_price'] = 0;
                                $goods['min_original_price'] = 0;
                                $goods['max_original_price'] = 0;
                                $goods['show_price_symbol'] = $show_price_symbol;
                                $goods['show_price_unit'] = $show_price_unit;
                            }

                            // icon图标
                            if(APPLICATION == 'app' || $this->is_pure_exchange_modal)
                            {
                                $goods['show_field_price_text'] = $goods_detail_icon;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * 中间大导航
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-12
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function NavigationHeaderHandle($params = [])
    {
        if(isset($params['header']) && is_array($params['header']))
        {
            // 获取应用数据
            if(!empty($this->plugins_config['application_name']))
            {
                $nav = [
                    'id'                    => 0,
                    'pid'                   => 0,
                    'name'                  => $this->plugins_config['application_name'],
                    'url'                   => PluginsAdminService::PluginsSecondDomainUrl('points', true),
                    'data_type'             => 'custom',
                    'is_show'               => 1,
                    'is_new_window_open'    => 0,
                    'items'                 => [],
                ];
                array_unshift($params['header'], $nav);
            }
        }
    }

    /**
     * web端快捷导航操作导航
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function WebQuickNavigationHandle($params = [])
    {
        if(isset($params['data']) && is_array($params['data']))
        {
            $nav = [
                'event_type'    => 0,
                'event_value'   => PluginsAdminService::PluginsSecondDomainUrl('points', true),
                'name'          => $this->nav_title,
                'images_url'    => StaticAttachmentUrl('quick-nav-icon.png'),
                'bg_color'      => '#fe3e28',
            ];
            array_push($params['data'], $nav);
        }
    }

    /**
     * 小程序端快捷导航操作导航
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function MiniQuickNavigationHandle($params = [])
    {
        if(isset($params['data']) && is_array($params['data']))
        {
            $nav = [
                'event_type'    => 1,
                'event_value'   => '/pages/plugins/points/index/index',
                'name'          => $this->nav_title,
                'images_url'    => StaticAttachmentUrl('quick-nav-icon.png'),
                'bg_color'      => '#fe3e28',
            ];
            array_push($params['data'], $nav);
        }
    }
}
?>