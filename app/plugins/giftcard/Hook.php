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
namespace app\plugins\giftcard;

use think\facade\Db;
use app\service\UserService;
use app\service\ResourcesService;
use app\plugins\giftcard\service\BaseService;
use app\plugins\giftcard\service\CardSecretService;

/**
 * 钩子入口
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2024-05-24
 * @desc    description
 */
class Hook
{
    // 插件配置信息
    private $plugins_config;

    // 当前模块/控制器
    private $module_name;
    private $controller_name;
    private $mc;

    // 是否开启用户中心菜单入口
    private $is_user_menu;
    private $user_menu_name;

    // 商品兑换
    private $is_goods_exchange;

    /**
     * 应用响应入口
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2024-05-24
     * @param    [array]       $params [输入参数]
     */
    public function handle($params = [])
    {
        if(!empty($params['hook_name']))
        {
            // 当前模块/控制器
            $this->module_name = RequestModule();
            $this->controller_name = RequestController();
            $this->mc = $this->module_name.$this->controller_name;

            // 插件配置信息
            $base = BaseService::BaseConfig();
            $this->plugins_config = $base['data'];

            // 用户中心菜单入口
            $this->is_user_menu = isset($this->plugins_config['is_user_menu']) && $this->plugins_config['is_user_menu'] == 1;
            $this->user_menu_name = empty($this->plugins_config['user_menu_name']) ? '我的礼品卡' : $this->plugins_config['user_menu_name'];

            // 是否开启商品兑换
            $this->is_goods_exchange = isset($this->plugins_config['is_goods_exchange']) && $this->plugins_config['is_goods_exchange'] == 1;

            // 商品详情页面兑换价格处理
            $is_detail_goods_exchange_price = $this->is_goods_exchange && in_array($this->mc, ['indexgoods', 'apigoods']);
            // 下单页面商品兑换处理
            $is_buy_goods_exchange_price = $this->is_goods_exchange && in_array($this->mc, ['indexbuy', 'apibuy']);

            // 走条件判断
            $ret = '';
            switch($params['hook_name'])
            {
                // 用户中心左侧导航
                case 'plugins_service_users_center_left_menu_handle' :
                    if($this->is_user_menu)
                    {
                        $ret = $this->UserCenterLeftMenuHandle($params);
                    }
                    break;

                // 顶部小导航右侧-我的商城
                case 'plugins_service_header_navigation_top_right_handle' :
                    if($this->is_user_menu)
                    {
                        $ret = $this->CommonTopNavRightMenuHandle($params);
                    }
                    break;

                // 商品规格基础数据
                case 'plugins_service_goods_spec_base' :
                    if($is_detail_goods_exchange_price)
                    {
                        $this->GoodsSpecBase($params);
                    }
                    break;

                // 商品列表
                case 'plugins_service_goods_list_handle_end' :
                    if($is_detail_goods_exchange_price)
                    {
                        $this->GoodslistHandle($params);
                    }
                    break;

                // 商品详情页面导航购买按钮处理
                case 'plugins_service_goods_buy_nav_button_handle' :
                    if($is_detail_goods_exchange_price)
                    {
                        $this->GoodsDetailBuyNavButtonContent($params);
                    }
                    break;

                // 下单商品兑换处理
                case 'plugins_service_buy_group_goods_handle' :
                    if($is_buy_goods_exchange_price)
                    {
                        $this->BuyGroupGoodsHandle($params);
                    }
                    break;

                // 订单添加成功处理
                case 'plugins_service_buy_order_insert_success' :
                    $this->OrderInsertSuccessHandle($params);
                    break;

                // 订单状态改变处理
                case 'plugins_service_order_status_change_history_success_handle' :
                    $this->OrderStatusUpdateHandle($params);
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
                        'name'  => '礼品卡',
                        'type'  => 'giftcard',
                        'data'  => [
                            ['name'=>'我的礼品卡', 'page'=>'/pages/plugins/giftcard/index/index'],
                            ['name'=>'礼品卡兑换', 'page'=>'/pages/plugins/giftcard/form/form'],
                        ],
                    ];
                    break;
                }
            }
        }
    }

    /**
     * 订单状态改变处理,状态为取消|关闭时释放优惠券
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-15
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function OrderStatusUpdateHandle($params = [])
    {
        if(!empty($params['data']) && isset($params['data']['new_status']) && in_array($params['data']['new_status'], [5,6]) && !empty($params['order_id']))
        {
            // 释放商品兑换卡券
            CardSecretService::UserCardSecretUseStatusUpdate($params['order_id'], 0);
        }
    }

    /**
     * 订单添加成功处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-14
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function OrderInsertSuccessHandle($params = [])
    {
        if(!empty($params['order_ids']))
        {
            $order = Db::name('Order')->where(['id'=>$params['order_ids']])->field('id,extension_data')->select()->toArray();
            if(!empty($order))
            {
                // 使用商品兑换卡券
                foreach($order as $v)
                {
                    CardSecretService::UserCardSecretUseStatusUpdate($v['id'], 1, $v['extension_data']);
                }
            }
        }
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
    public function BuyGroupGoodsHandle($params = [])
    {
        if(!empty($params['data']) && is_array($params['data']))
        {
            // 当前用户
            $user = UserService::LoginUserInfo();
            if(!empty($user))
            {
                $user_card = CardSecretService::CardSecretValueGoodsData($user['id']);
                if(!empty($user_card))
                {
                    $currency_symbol = ResourcesService::CurrencyDataSymbol();
                    // 礼品卡兑换支付：仅当 is_gift=1 且用户持有的兑换权益可完全覆盖本单兑换商品时成立(与积分插件核验一致，避免重复抵扣金额)
                    $is_gift_pay = CardSecretService::GiftPayVerify($params['data'], $user['id']) == 1;
                    foreach($params['data'] as $k=>$v)
                    {
                        if(!empty($v['goods_items']) && is_array($v['goods_items']))
                        {
                            $use_card = [];
                            foreach($v['goods_items'] as $vs)
                            {
                                // 该规格行需要抵扣的件数（按购买件数、跨卡累计抵扣）
                                $need = max(1, intval($vs['stock']));
                                foreach($user_card as $ci=>$cv)
                                {
                                    if($need <= 0)
                                    {
                                        break;
                                    }
                                    if(!empty($cv['not_use_goods_num']) && !empty($cv['not_use_goods_num'][$vs['goods_id']]))
                                    {
                                        $take = min($need, intval($cv['not_use_goods_num'][$vs['goods_id']]));
                                        // 扣减该卡剩余额度（同单多行/多店铺分组共享额度）
                                        $user_card[$ci]['not_use_goods_num'][$vs['goods_id']] -= $take;
                                        $need -= $take;
                                        $use_card[] = [
                                            'card_secret_id'  => $cv['id'],
                                            'card_id'         => $cv['card_id'],
                                            'goods_id'        => $vs['goods_id'],
                                            'goods_title'     => $vs['title'],
                                            'goods_price'     => $vs['price'],
                                            'goods_spec'      => $vs['spec'],
                                            'stock'           => $take,
                                        ];
                                    }
                                }
                            }
                            if(!empty($use_card))
                            {
                                $total_price = 0;
                                foreach($use_card as $ucv)
                                {
                                    $total_price += $ucv['goods_price'] * $ucv['stock'];
                                }
                                $total_price = PriceNumberFormat($total_price);
                                // 礼品卡兑换支付时金额侧由积分插件归零，此处仅保留权益核销数据(ext)，price置0避免重复抵扣金额
                                $params['data'][$k]['order_base']['extension_data'][] = [
                                    'name'         => '礼品卡商品兑换',
                                    'price'        => $is_gift_pay ? 0 : $total_price,
                                    'type'         => 0,
                                    'business'     => 'plugins-giftcard-goods-exchange',
                                    'tips'         => $is_gift_pay ? '' : '-'.$currency_symbol.$total_price,
                                    'ext'          => $use_card,
                                ];
                            }
                        }
                    }
                }
            }
        }
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
        if($this->is_goods_exchange && !empty($params['goods']) && !empty($params['data']) && is_array($params['data']))
        {
            // 当前用户
            $user = UserService::LoginUserInfo();
            if(!empty($user))
            {
                $user_card = CardSecretService::CardSecretValueGoodsData($user['id']);
                if(!empty($user_card))
                {
                    // 兑换按钮名称
                    $exchange_btn = empty($this->plugins_config['goods_detail_buy_btn_exchange_text']) ? '免费兑换' : $this->plugins_config['goods_detail_buy_btn_exchange_text'];
                    foreach($user_card as $cv)
                    {
                        if(!empty($cv['not_use_goods_ids']) && is_array($cv['not_use_goods_ids']) && in_array($params['goods']['id'], $cv['not_use_goods_ids']))
                        {
                            foreach($params['data'] as $k=>$v)
                            {
                                if(isset($v['type']) && $v['type'] == 'buy')
                                {
                                    $params['data'][$k]['name'] = $exchange_btn;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * 商品列表
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-11
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function GoodslistHandle($params = [])
    {
        if($this->is_goods_exchange && !empty($params['data']) && is_array($params['data']))
        {
            // 当前用户
            $user = UserService::LoginUserInfo();
            if(!empty($user))
            {
                $user_card = CardSecretService::CardSecretValueGoodsData($user['id']);
                if(!empty($user_card))
                {
                    // key字段
                    $key_field = empty($params['params']['data_key_field']) ? 'id' : $params['params']['data_key_field'];

                    foreach($params['data'] as &$goods)
                    {
                        if(!empty($goods[$key_field]))
                        {
                            $goods_id = $goods[$key_field];
                            foreach($user_card as $cv)
                            {
                                if(!empty($cv['not_use_goods_ids']) && is_array($cv['not_use_goods_ids']) && in_array($goods_id, $cv['not_use_goods_ids']))
                                {
                                    if(isset($goods['price']))
                                    {
                                        $goods['original_price'] = $goods['price'];
                                        $goods['price'] = 0.00;
                                    }
                                    if(isset($goods['min_price']))
                                    {
                                        $goods['min_original_price'] = $goods['min_price'];
                                        $goods['min_price'] = 0.00;
                                    }
                                    if(isset($goods['max_price']))
                                    {
                                        $goods['max_original_price'] = $goods['max_price'];
                                        $goods['max_price'] = 0.00;
                                    }
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
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
        if($this->is_goods_exchange && !empty($params['data']['spec_base']) && !empty($params['data']['spec_base']['goods_id']))
        {
            // 当前用户
            $user = UserService::LoginUserInfo();
            if(!empty($user))
            {
                $user_card = CardSecretService::CardSecretValueGoodsData($user['id']);
                if(!empty($user_card))
                {
                    foreach($user_card as $cv)
                    {
                        if(!empty($cv['not_use_goods_ids']) && is_array($cv['not_use_goods_ids']) && in_array($params['data']['spec_base']['goods_id'], $cv['not_use_goods_ids']))
                        {
                            $params['data']['spec_base']['original_price'] = $params['data']['spec_base']['price'];
                            $params['data']['spec_base']['price'] = 0.00;
                            break;
                        }
                    }
                }
            }
        }
    }

    /**
     * 用户中心左侧菜单处理
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-05-24
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function UserCenterLeftMenuHandle($params = [])
    {
        if(!empty($params['data']) && !empty($params['data']['property']) && isset($params['data']['property']['item']) && is_array($params['data']['property']['item']))
        {
            $params['data']['property']['item'][] = [
                'name'      =>  $this->user_menu_name,
                'url'       =>  PluginsHomeUrl('giftcard', 'index', 'index'),
                'contains'  =>  ['giftcardindexindex'],
                'is_show'   =>  1,
                'icon'      =>  'am-icon-credit-card',
            ];
        }
    }

    /**
     * 顶部小导航右侧-我的商城
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-05-24
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function CommonTopNavRightMenuHandle($params = [])
    {
        if(!empty($params['data']) && !empty($params['data'][1]) && isset($params['data'][1]['items']) && is_array($params['data'][1]['items']))
        {
            array_push($params['data'][1]['items'], [
                'name'  => $this->user_menu_name,
                'url'   => PluginsHomeUrl('giftcard', 'index', 'index'),
            ]);
        }
    }
}
?>