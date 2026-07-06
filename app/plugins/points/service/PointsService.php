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
namespace app\plugins\points\service;

use think\facade\Db;
use app\service\UserService;
use app\service\IntegralService;
use app\service\ResourcesService;
use app\plugins\points\service\BaseService;

/**
 * 积分商城 - 积分兑换、抵扣服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class PointsService
{
    /**
     * 下单页面用户积分信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-12-25
     * @desc    description
     * @param   [array]          $base   [插件配置信息]
     * @param   [array]          $goods  [仓库组商品]
     * @param   [array]          $params [输入参数]
     */
    public static function BuyUserPointsData($base, $goods, $params = [])
    {
        // 用户id、先读取参数指定用户
        if(empty($params['user_id']))
        {
            $user_id = (empty($params['params']) || empty($params['params']['user_id'])) ? 0 : intval($params['params']['user_id']);
            if(empty($user_id) && !empty($params['user']))
            {
                $user_id = $params['user']['id'];
            }
        } else {
            $user_id = intval($params['user_id']);
        }
        if(empty($user_id))
        {
            // 获取用户信息
            $user = UserService::LoginUserInfo();
            if(!empty($user) && !empty($user['id']))
            {
                $user_id = $user['id'];
            }
        }

        // 用户可用积分
        $user_integral = empty($user_id) ? 0 : (int) Db::name('User')->where(['id'=>$user_id])->value('integral');

        // 用户指定使用的积分
        $actual_use_integral = empty($params['actual_use_integral']) ? $user_integral : intval($params['actual_use_integral']);
        if($actual_use_integral > $user_integral)
        {
            $actual_use_integral = $user_integral;
        }

        // 是否选中
        $is_checked = (isset($params['is_points']) && $params['is_points'] == 1) ? 1 : 0;

        // 抵扣类型(0积分抵扣, 1积分兑换)
        $discount_type = 0;
        $discount_price = 0;
        $exchange_inc_price = 0;
        $exchange_integral_total = 0;
        $goods_exchange_count = 0;
        $goods_exchange_total_price = 0;
        $order_total_price = 0;
        $deduction_data = [];

        // 使用提示信息
        $use_msg_tips = '';
        $usable_msg_tips = '';
        $not_msg_tips = '';

        // 优先计算是否满足积分兑换
        // 是否全部商品都存在兑换配中、并且积分满足
        // 当前所有商品是否支持兑换
        $use_integral = 0;
        $group_not_exchange_price = [];
        $group_integral_price = [];
        $group_integral = [];
        $is_support_goods_exchange = 0;
        $is_integral_exchange = isset($base['is_integral_exchange']) && $base['is_integral_exchange'] == 1 ? 1 : 0;
        $is_pure_exchange_modal = isset($base['is_pure_exchange_modal']) && $base['is_pure_exchange_modal'] == 1 ? 1 : 0;
        // 商品处理
        $goods_count = 0;
        foreach($goods as $v)
        {
            if(!empty($v['goods_items']))
            {
                // 减去订单扩展优惠金额后、是否还存在金额
                $order_total_price += $v['order_base']['total_price'];
                if(!empty($v['order_base']['extension_data']) && is_array($v['order_base']['extension_data']))
                {
                    $order_total_price -= array_sum(array_filter(array_map(function($v)
                    {
                        return (isset($v['type']) && $v['type'] == 0 && isset($v['price']) && $v['price'] && isset($v['business']) && !in_array($v['business'], ['plugins-points-exchange', 'plugins-points-deduction'])) ? $v['price'] : 0;
                    }, $v['order_base']['extension_data'])));
                }

                // 兑换商品处理
                if(!array_key_exists($v['id'], $group_not_exchange_price))
                {
                    $group_not_exchange_price[$v['id']] = 0;
                }
                if(!array_key_exists($v['id'], $group_integral_price))
                {
                    $group_integral_price[$v['id']] = 0;
                }
                if(!array_key_exists($v['id'], $group_integral))
                {
                    $group_integral[$v['id']] = 0;
                }
                foreach($v['goods_items'] as $vs)
                {
                    if($vs['stock'] > 0)
                    {
                        if(!empty($vs['plugins_points_data']))
                        {
                            $integral = $vs['stock']*$vs['plugins_points_data']['points_integral'];
                            $price = $vs['stock']*$vs['plugins_points_data']['points_price'];
                            $exchange_inc_price += $price;
                            $exchange_integral_total += $integral;
                            $group_integral_price[$v['id']] += $price;
                            $group_integral[$v['id']] += $integral;
                            $goods_exchange_total_price += $vs['total_price'];
                            $goods_exchange_count++;
                        } else {
                            $group_not_exchange_price[$v['id']] += $vs['stock']*$vs['price'];
                        }
                        $goods_count++;
                    }
                }
            }
        }

        // 订单金额必须大于0
        if($order_total_price > 0)
        {
            // 开启积分兑换，兑换商品大于0，兑换积分总额大于0
            if($is_integral_exchange && $goods_exchange_count > 0)
            {
                $is_support_goods_exchange = 1;
                if($exchange_integral_total > 0 && $goods_count > 0 && $user_integral >= $exchange_integral_total)
                {
                    $discount_type = 1;
                    $use_integral = $exchange_integral_total;
                    $discount_price = $goods_exchange_total_price;
                }
            }

            // 积分抵扣
            if($discount_type == 0 && isset($base['is_integral_deduction']) && $base['is_integral_deduction'] == 1)
            {
                // 当前订单限制可使用积分数量
                $order_max_integral = empty($base['order_max_integral']) ? 0 : intval($base['order_max_integral']);

                // 抵扣金额比例
                $deduction_price = empty($base['deduction_price']) ? 0 : PriceNumberFormat($base['deduction_price']);

                // 临时记录计算
                $temp_use_integral = $actual_use_integral;

                // 循环处理
                $temp_use_integral_total = 0;
                foreach($goods as $k=>$v)
                {
                    if($temp_use_integral > 0)
                    {
                        $item_data = [
                            'order_total_price' => 0,
                        ];
                        if(!empty($v['goods_items']))
                        {
                            // 减去订单扩展优惠金额后、是否还存在金额
                            $item_data['order_total_price'] += $v['order_base']['total_price'];
                            if(!empty($v['order_base']['extension_data']) && is_array($v['order_base']['extension_data']))
                            {
                                $item_data['order_total_price'] -= array_sum(array_filter(array_map(function($v)
                                {
                                    return (isset($v['type']) && $v['type'] == 0 && isset($v['price']) && $v['price'] && isset($v['business']) && !in_array($v['business'], ['plugins-points-exchange', 'plugins-points-deduction'])) ? $v['price'] : 0;
                                }, $v['order_base']['extension_data'])));
                            }
                        }
                        // 订单最低金额条件
                        if(empty($base['order_total_price']) || $item_data['order_total_price'] >= $base['order_total_price'])
                        {
                            // 订单金额最多可使用比例
                            if(!empty($base['order_price_max_rate']))
                            {
                                $item_data['order_total_price'] = PriceNumberFormat($item_data['order_total_price']*($base['order_price_max_rate']/100));
                            }

                            // 使用积分
                            $item_data['use_integral'] = ($item_data['order_total_price'] > 0 && $deduction_price > 0) ? PriceNumberFormat($item_data['order_total_price']/($deduction_price/100), 0) : 0;

                            // 当前可用积分
                            if($order_max_integral > 0 && $item_data['use_integral'] > $order_max_integral)
                            {
                                $item_data['use_integral'] = $order_max_integral;
                            }

                            // 减掉积分使用
                            if($temp_use_integral < $item_data['use_integral'])
                            {
                                $item_data['use_integral'] = $temp_use_integral;
                            }

                            // 抵扣金额
                            $item_data['discount_price'] = ($item_data['use_integral'] > 0 && $deduction_price > 0) ? PriceNumberFormat($item_data['use_integral']*($deduction_price/100)) : 0;

                            // 加入兑换数据
                            $deduction_data[$k] = $item_data;

                            // 减掉用户使用积分
                            $temp_use_integral -= $item_data['use_integral'];

                            // 公共数据更新
                            $temp_use_integral_total += $item_data['use_integral'];
                            $discount_price += $item_data['discount_price'];
                        }
                    }
                }
                // 可用积分
                $use_integral = $user_integral-$temp_use_integral_total;
                // 使用积分是否超过指定积分
                if($temp_use_integral_total < $actual_use_integral)
                {
                    $actual_use_integral = $temp_use_integral_total;
                }
            }

            // 是否已选中
            if($is_checked == 0)
            {
                // 是否开启默认使用积分、用户未选择的情况下
                if(!isset($params['is_points']) && $discount_price > 0 && $use_integral > 0 && isset($base['is_default_use_points']) && $base['is_default_use_points'] == 1)
                {
                    $is_checked = 1;
                }

                // 未开启自动选择并且开启了纯积分兑换模式则默认选中
                if($is_checked == 0 && $is_integral_exchange == 1 && $is_pure_exchange_modal == 1 && $discount_type == 1)
                {
                    $is_checked = 1;
                }
            }

            // 提示信息处理
            if($discount_price > 0)
            {
                if($discount_type == 1)
                {
                    $currency_symbol = ResourcesService::CurrencyDataSymbol();
                    $use_msg_tips = '使用'.$use_integral.'个积分'.($exchange_inc_price > 0 ? '加'.$currency_symbol.$exchange_inc_price : '').'兑换商品';
                }
                $usable_msg_tips = ($discount_type != 1) ? '你有积分'.$user_integral.'个，可用'.$use_integral.'个' : '';
            } else {
                if($is_support_goods_exchange == 1)
                {
                    $not_msg_tips = '你有积分'.$user_integral.'个，不足以兑换当前商品';
                }
            }
        }

        return [
            'user_integral'               => $user_integral,
            'use_integral'                => $use_integral,
            'actual_use_integral'         => $actual_use_integral,
            'deduction_data'              => $deduction_data,
            'group_not_exchange_price'    => $group_not_exchange_price,
            'group_integral_price'        => $group_integral_price,
            'group_integral'              => $group_integral,
            'discount_type'               => $discount_type,
            'discount_price'              => $discount_price,
            'exchange_inc_price'          => $exchange_inc_price,
            'exchange_integral_total'     => $exchange_integral_total,
            'goods_exchange_count'        => $goods_exchange_count,
            'goods_exchange_total_price'  => $goods_exchange_total_price,
            'is_support_goods_exchange'   => $is_support_goods_exchange,
            'is_checked'                  => $is_checked,
            'is_integral_exchange'        => $is_integral_exchange,
            'is_pure_exchange_modal'      => $is_pure_exchange_modal,
            'use_msg_tips'                => $use_msg_tips,
            'usable_msg_tips'             => $usable_msg_tips,
            'not_msg_tips'                => $not_msg_tips,
        ];
    }

    /**
     * 下单数据处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-12-25
     * @desc    description
     * @param   [array]          $base   [插件配置信息]
     * @param   [array]          $goods  [仓库组商品]
     * @param   [array]          $params [输入参数]
     */
    public static function BuyUserPointsHandle($base, &$goods, $params = [])
    {
        $data = self::BuyUserPointsData($base, $goods, $params);
        if(!empty($data) && !empty($data['discount_price']) && $data['discount_price'] > 0 && $data['is_checked'] == 1)
        {
            $currency_symbol = ResourcesService::CurrencyDataSymbol();
            foreach($goods as $k=>$v)
            {
                // 积分兑换则所有商品分组都增加优惠数据
                if($data['discount_type'] == 1)
                {
                    // 兑换积分
                    $integral = empty($data['group_integral'][$v['id']]) ? 0 : $data['group_integral'][$v['id']];
                    // 兑换积分金额
                    $integral_price = empty($data['group_integral_price'][$v['id']]) ? 0 : $data['group_integral_price'][$v['id']];
                    // 没有参与兑换的商品金额
                    $not_exchange_price = empty($data['group_not_exchange_price'][$v['id']]) ? 0 : $data['group_not_exchange_price'][$v['id']];
                    // 纯兑换模式 兑换积分金额 + 未参与兑换的商品金额，则未参与兑换的商品金额
                    $price = ($data['is_pure_exchange_modal'] == 1) ? $integral_price+$not_exchange_price : $not_exchange_price;
                    if($integral > 0)
                    {
                        $goods[$k]['order_base']['extension_data'][] = [
                            'name'         => '积分兑换('.$integral.'积分'.($integral_price > 0 ? ' +'.$currency_symbol.$integral_price : '').')',
                            'price'        => $v['order_base']['total_price']-$price,
                            'type'         => 0,
                            'business'     => 'plugins-points-exchange',
                            'tips'         => '',
                            'ext'          => $integral,
                        ];
                    }
                } else {
                    if(!empty($data['deduction_data']) && array_key_exists($k, $data['deduction_data']))
                    {
                        $temp = $data['deduction_data'][$k];
                        $goods[$k]['order_base']['extension_data'][] = [
                            'name'      => '积分抵扣('.$temp['use_integral'].'积分)',
                            'price'     => $temp['discount_price'],
                            'type'      => 0,
                            'business'  => 'plugins-points-deduction',
                            'tips'      => '-'.$currency_symbol.$temp['discount_price'],
                            'ext'       => $temp['use_integral'],
                        ];
                    }
                }
            }
        }
    }

    /**
     * 订单添加成功处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-12-21
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function OrderInsertSuccessHandle($params)
    {
        $ret = DataReturn('success', 0);
        if(!empty($params['order']) && !empty($params['order']['extension_data']) && !empty($params['order']['user_id']))
        {
            $extension_data = json_decode($params['order']['extension_data'], true);
            if(!empty($extension_data) && is_array($extension_data))
            {
                foreach($extension_data as $v)
                {
                    if(!empty($v) && is_array($v) && isset($v['business']) && !empty($v['ext']))
                    {
                        switch($v['business'])
                        {
                            // 积分兑换
                            case 'plugins-points-exchange' :
                                $ret = self::UserIntegralDec($params['order']['user_id'], $v['ext'], '积分兑换');
                                break;

                            // 积分抵扣
                            case 'plugins-points-deduction' :
                                $ret = self::UserIntegralDec($params['order']['user_id'], $v['ext'], '积分抵扣');
                                break;
                        }
                    }
                }
            }
        }
        return $ret;
    }

    /**
     * 用户积分扣除
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-12-21
     * @desc    description
     * @param   [int]          $user_id  [用户id]
     * @param   [int]          $integral [积分]
     * @param   [string]       $title    [名称]
     */
    public static function UserIntegralDec($user_id, $integral, $title)
    {
        // 用户积分增加
        $user_integral = Db::name('User')->where(['id'=>$user_id])->value('integral');
        if(!Db::name('User')->where(['id'=>$user_id])->dec('integral', $integral)->update())
        {
            return DataReturn('用户积分扣除失败', -1);
        }

        // 积分日志
        $res = IntegralService::UserIntegralLogAdd($user_id, $user_integral, $integral, $title, 0);
        if(!$res)
        {
            return DataReturn('积分日志记录失败', -1);
        }

        // 当前登录用户
        $user = UserService::LoginUserInfo();
        if(!empty($user) && $user['id'] == $user_id)
        {
            // 更新用户登录缓存数据
            UserService::UserLoginRecord($user_id);
        }
        return DataReturn('success', 0);
    }

    /**
     * 订单状态改变处理,状态为取消|关闭时释放积分
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-12-21
     * @desc    description
     * @param   [int]          $order_id [订单id]
     */
    public static function OrderStatusChangeHandle($order_id)
    {
        $order = Db::name('Order')->where(['id'=>intval($order_id)])->field('id,user_id,status,extension_data')->find();
        if(!empty($order))
        {
            $extension_data = json_decode($order['extension_data'], true);
            if(!empty($extension_data) && is_array($extension_data))
            {
                foreach($extension_data as $v)
                {
                    if(!empty($v) && is_array($v) && isset($v['business']) && !empty($v['ext']))
                    {
                        switch($v['business'])
                        {
                            // 积分兑换
                            case 'plugins-points-exchange' :
                                self::UserIntegralInc($order['user_id'], $v['ext'], '积分兑换退回');
                                break;

                            // 积分抵扣
                            case 'plugins-points-deduction' :
                                self::UserIntegralInc($order['user_id'], $v['ext'], '积分抵扣退回');
                                break;
                        }
                    }
                }
            }
        }
    }

    /**
     * 用户积分增加
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-12-21
     * @desc    description
     * @param   [int]          $user_id  [用户id]
     * @param   [int]          $integral [积分]
     * @param   [string]       $title    [名称]
     */
    public static function UserIntegralInc($user_id, $integral, $title)
    {
        // 用户积分增加
        $user_integral = Db::name('User')->where(['id'=>$user_id])->value('integral');
        Db::name('User')->where(['id'=>$user_id])->inc('integral', $integral)->update();

        // 积分日志
        IntegralService::UserIntegralLogAdd($user_id, $user_integral, $integral, $title, 1);

        // 当前登录用户
        $user = UserService::LoginUserInfo();
        if(!empty($user) && $user['id'] == $user_id)
        {
            // 更新用户登录缓存数据
            UserService::UserLoginRecord($user_id);
        }
    }

    /**
     * 商品兑换数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-02-22
     * @desc    description
     * @param   [array]          $config    [插件配置]
     * @param   [int|array]      $goods_ids [商品id]
     */
    public static function GoodsExchangeData($config, $goods_ids)
    {
        // 非数组则处理为数组
        if(!is_array($goods_ids))
        {
            $goods_ids = explode(',', $goods_ids);
        }

        // 静态数据不重复查询
        static $plugins_points_goods_exchange_integral_data = [];
        $temp_goods_ids = [];
        foreach($goods_ids as $gid)
        {
            if(!array_key_exists($gid, $plugins_points_goods_exchange_integral_data))
            {
                $temp_goods_ids[] = $gid;
            }
        }
        if(!empty($temp_goods_ids))
        {
            // 是否商品纯兑换模式
            $is_pure_exchange_modal = isset($config['is_pure_exchange_modal']) && $config['is_pure_exchange_modal'] == 1;

            // 获取商品兑换数据
            $data = Db::name('Goods')->where(['id'=>$temp_goods_ids])->column('plugins_points_exchange_integral as integral,plugins_points_exchange_price as price', 'id');
            foreach($temp_goods_ids as $gid)
            {
                $temp = (!empty($data) && array_key_exists($gid, $data) && !empty($data[$gid]['integral'])) ? $data[$gid] : [];
                // 加金额仅支持纯积分兑换模式
                if(!$is_pure_exchange_modal && !empty($temp))
                {
                    $temp['price'] = 0;
                }
                $plugins_points_goods_exchange_integral_data[$gid] = $temp;
            }
        }

        // 返回处理后的数据
        $result = [];
        foreach($goods_ids as $gid)
        {
            if(array_key_exists($gid, $plugins_points_goods_exchange_integral_data))
            {
                $result[$gid] = $plugins_points_goods_exchange_integral_data[$gid];
            }
        }
        return $result;
    }
}
?>