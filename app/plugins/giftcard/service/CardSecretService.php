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
namespace app\plugins\giftcard\service;

use think\facade\Db;
use app\service\IntegralService;
use app\service\GoodsService;
use app\service\ResourcesService;

/**
 * 礼品卡 - 礼品卡密服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class CardSecretService
{
    /**
     * 列表数据处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-02-06
     * @desc    description
     * @param   [array]          $data   [列表数据]
     * @param   [array]          $params [输入参数]
     */
    public static function DataListHandle($data, $params = [])
    {
        if(!empty($data))
        {
            // 价格符号
            $currency_symbol = ResourcesService::CurrencyDataSymbol();
            // 优惠券
            $coupon = array_column(BaseService::CouponList(), null, 'id');
            // 商品
            $goods_ids = call_user_func_array('array_merge', array_filter(array_map(function($item) {
                return isset($item['data_type']) && $item['data_type'] == 3 ? explode(',', $item['secret_value']) : [];
            }, $data)));
            $goods = Db::name('Goods')->where(['id'=>array_unique($goods_ids)])->column('id,title', 'id');
            if(!empty($goods))
            {
                $goods = array_map(function($item) {
                    $item['url'] = GoodsService::GoodsUrlCreate($item['id']);
                    return $item;
                }, $goods);
            }

            foreach($data as &$v)
            {
                // 使用数据
                $v['use_data'] = empty($v['use_data']) ? '' : json_decode($v['use_data'], true);
                if(!empty($v['use_data']))
                {
                    foreach($v['use_data'] as &$uv)
                    {
                        if(isset($uv['use_time']))
                        {
                            $uv['use_time'] = date('Y-m-d H:i:s', $uv['use_time']);
                        }
                        if(isset($uv['goods_id']))
                        {
                            $uv['goods_url'] = GoodsService::GoodsUrlCreate($uv['goods_id']);
                        }
                    }
                }

                // 优惠券
                if(isset($v['data_type']))
                {
                    $v['secret_value_text'] = '';
                    $v['secret_value_data'] = '';
                    switch($v['data_type'])
                    {
                        // 余额
                        case 0 :
                            $v['secret_value_text'] = $currency_symbol.$v['secret_value'];
                            break;

                        // 优惠券
                        case 1 :
                            $v['secret_value_text'] = (empty($coupon) || !isset($coupon[$v['secret_value']])) ? '' : $coupon[$v['secret_value']]['name'];
                            break;

                        // 积分
                        case 2 :
                            $v['secret_value_text'] = $v['secret_value'].'积分';
                            break;

                        // 商品
                        case 3 :
                            $temp = explode(',', $v['secret_value']);
                            $secret_value_data = [];
                            foreach($temp as $gid)
                            {
                                if(!empty($goods) && array_key_exists($gid, $goods))
                                {
                                    $secret_value_data[] = $goods[$gid];
                                }
                            }
                            $v['secret_value_data'] = $secret_value_data;
                            break;
                    }
                }

                // 二维码图片
                if(array_key_exists('secret_key', $v))
                {
                    $file = DS.'download'.DS.'plugins_giftcard'.DS.'qrcode_'.$v['card_id'].DS.$v['batch_id'].DS.md5($v['secret_key'].$v['id']).'.png';
                    $v['images'] = file_exists(ROOT.'public'.$file) ? ResourcesService::AttachmentPathViewHandle($file) : '';
                }
            }
        }
        return $data;
    }

    /**
     * 二维码生成
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-08
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function CardSecretGenerate($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '卡密数据id为空',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 卡密数据
        $data = Db::name('PluginsGiftcardCardSecret')->where(['id'=>intval($params['id'])])->find();
        if(empty($data))
        {
            return DataReturn('没有相关卡密数据', -1);
        }
        // 卡数数据
        $card = Db::name('PluginsGiftcardCard')->where(['id'=>$data['card_id']])->find();
        if(empty($card))
        {
            return DataReturn('没有相关礼品卡数据', -1);
        }

        // 二维码生成处理
        return self::CardSecretGenerateHandle($data);
    }

    /**
     * 二维码生成处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-02-07
     * @desc    description
     * @param   [array]          $data [卡密数据]
     */
    public static function CardSecretGenerateHandle($data)
    {
        // 生成二维码参数
        $qrcode_params = [
            'content'   => $data['secret_key'],
            'filename'  => md5($data['secret_key'].$data['id']).'.png',
            'root_path' => ROOT.'public',
            'path'      => DS.'download'.DS.'plugins_giftcard'.DS.'qrcode_'.$data['card_id'].DS.$data['batch_id'].DS,
        ];

        // 目录不存在则创建
        if(\base\FileUtil::CreateDir($qrcode_params['root_path'].$qrcode_params['path']) !== true)
        {
            return DataReturn('二维码目录创建失败', -1);
        }

        // 创建二维码
        $ret = (new \base\Qrcode())->Create($qrcode_params);
        if($ret['code'] != 0)
        {
            return $ret;
        }
        return DataReturn(MyLang('operate_success'), 0, $ret['data']['url']);
    }

    /**
     * 卡密兑换
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-02-19
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function CardSecretExchange($params = [])
    {
        // 参数校验
        $p = [
            [
                'checked_type'      => 'isset',
                'key_name'          => 'plugins_config',
                'error_msg'         => MyLang('plugins_config_error_tips'),
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user',
                'error_msg'         => MyLang('user_info_incorrect_tips'),
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'secret_key',
                'error_msg'         => '请输入礼品卡密',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 是否开启兑换
        if(!isset($params['plugins_config']['is_card_exchange']) || $params['plugins_config']['is_card_exchange'] != 1)
        {
            return DataReturn('未开启卡密兑换、请联系管理员！', -1);
        }

        // 卡密信息
        $data = Db::name('PluginsGiftcardCardSecret')->where(['secret_key'=>$params['secret_key']])->find();
        if(empty($data))
        {
            return DataReturn('没有相关礼品卡密数据！', -1);
        }
        // 是否已兑换
        if($data['is_exchange'] == 1)
        {
            // 新增日志
            $ret = self::CardSecretLogInsert($data, $params);
            if($ret['code'] != 0)
            {
                return $ret;
            }
            return DataReturn('该礼品卡密已被使用！', -1);
        }

        // 处理数据
        Db::startTrans();
        try {
            // 更新礼品卡数据
            if(!Db::name('PluginsGiftcardCardSecret')->where(['id'=>$data['id']])->update([
                'user_id'        => $params['user']['id'],
                'is_exchange'    => 1,
                'exchange_time'  => time(),
                'upd_time'       => time(),
            ]))
            {
                throw new \Exception('礼品卡密数据更新失败、请稍后再试！');
            }
            // 更新礼品卡主数据
            if(!Db::name('PluginsGiftcardCard')->where(['id'=>$data['card_id']])->inc('card_exchange_count')->update())
            {
                throw new \Exception('礼品卡密主数据更新失败、请稍后再试！');
            }
            // 新增日志
            $ret = self::CardSecretLogInsert($data, $params);
            if($ret['code'] != 0)
            {
                throw new \Exception($ret['msg']);
            }

            // 根据类型处理兑换
            if(!empty($data['secret_value']))
            {
                switch($data['data_type'])
                {
                    // 充值
                    case 0 :
                        $ret = CallPluginsServiceMethod('wallet', 'WalletService', 'UserWalletMoneyUpdate', $params['user']['id'], $data['secret_value'], 1, 'normal_money', 0, '礼品卡兑换');
                        if($ret['code'] != 0)
                        {
                            throw new \Exception($ret['msg']);
                        }
                        break;

                    // 优惠券
                    case 1 :
                        $ret = CallPluginsServiceMethod('coupon', 'CouponService', 'CouponSend', [
                            'coupon_id'  => $data['secret_value'],
                            'user_ids'   => $params['user']['id'],
                        ]);
                        if($ret['code'] != 0)
                        {
                            throw new \Exception($ret['msg']);
                        }
                        break;

                    // 积分
                    case 2 :
                        $ret = IntegralService::UserIntegralUpdate($params['user']['id'], null, $data['secret_value'], '礼品卡积分兑换', 1);
                        if($ret['code'] != 0)
                        {
                            throw new \Exception($ret['msg']);
                        }
                        break;
                }
            }

            // 成功
            Db::commit();
            return DataReturn('兑换成功', 0, ['data_type'=>intval($data['data_type'])]);
        } catch(\Exception $e) {
            Db::rollback();
            return DataReturn($e->getMessage(), -1);
        }
    }

    /**
     * 卡密日志添加
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-05-26
     * @desc    description
     * @param   [array]          $data   [卡密数据]
     * @param   [array]          $params [输入参数]
     */
    public static function CardSecretLogInsert($data, $params)
    {
        // 新增日志
        $behavior = new \base\Behavior();
        $log = [
            'card_id'         => $data['card_id'],
            'card_secret_id'  => $data['id'],
            'user_id'         => $params['user']['id'],
            'client_ip'       => GetClientIP(),
            'os'              => $behavior->GetOs(),
            'browser'         => $behavior->GetBrowser(),
            'client'          => $behavior->GetClinet(),
            'add_time'        => time(),
        ];
        if(Db::name('PluginsGiftcardCardSecretLog')->insertGetId($log) <= 0)
        {
            return DataReturn('礼品卡密日志添加失败、请稍后再试！', -1);
        }
        return DataReturn('success', 0);
    }

    /**
     * 用户卡密商品数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-06-29
     * @desc    description
     * @param   [int]          $user_id [用户id]
     */
    public static function CardSecretValueGoodsData($user_id)
    {
        $where = [
            ['user_id', '=', $user_id],
            ['data_type', '=', 3],
            ['use_status', '=', 0]
        ];
        $data = Db::name('PluginsGiftcardCardSecret')->where($where)->field('id,card_id,secret_value,use_data')->select()->toArray();
        if(!empty($data))
        {
            foreach($data as &$v)
            {
                // 卡密数据
                $v['secret_value'] = empty($v['secret_value']) ? [] : explode(',', $v['secret_value']);

                // 使用数据
                $v['use_data'] = empty($v['use_data']) ? '' : json_decode($v['use_data'], true);

                // 未使用的商品id
                $use_goods_ids = (empty($v['use_data']) || !is_array($v['use_data'])) ? [] : array_column($v['use_data'], 'goods_id');
                $v['not_use_goods_ids'] = array_diff($v['secret_value'], $use_goods_ids);
            }
        }
        return $data;
    }

    /**
     * 用户已兑换礼品卡密总数
     * @author  Devil
     * @date    2026-06-29
     * @param   [int]          $user_id [用户id]
     */
    public static function UserExchangeTotal($user_id)
    {
        $user_id = intval($user_id);
        if($user_id <= 0)
        {
            return DataReturn("success", 0, 0);
        }
        $count = (int) Db::name("PluginsGiftcardCardSecret")->where([
            ["user_id", "=", $user_id],
            ["is_exchange", "=", 1],
        ])->count();
        return DataReturn("success", 0, $count);
    }

    /**
     * 用户已兑换未领取的实物商品清单
     * @author  Devil
     * @date    2026-07-01
     * @param   [int]          $user_id [用户id]
     */
    public static function UserExchangeGoodsList($user_id)
    {
        $user_id = intval($user_id);
        if($user_id <= 0)
        {
            return [];
        }
        // 已兑换实物卡密(data_type=3)可领取的商品id
        $data = self::CardSecretValueGoodsData($user_id);
        $goods_ids = [];
        if(!empty($data) && is_array($data))
        {
            foreach($data as $v)
            {
                if(!empty($v['not_use_goods_ids']) && is_array($v['not_use_goods_ids']))
                {
                    $goods_ids = array_merge($goods_ids, $v['not_use_goods_ids']);
                }
            }
        }
        $goods_ids = array_values(array_unique(array_filter($goods_ids)));
        if(empty($goods_ids))
        {
            return [];
        }
        $goods = GoodsService::GoodsList([
            'where'     => [
                ['id', 'in', $goods_ids],
                ['is_delete_time', '=', 0],
                ['is_shelves', '=', 1],
            ],
            'field'     => 'id,title,images,price,original_price,plugins_points_exchange_integral',
            'm'         => 0,
            'n'         => count($goods_ids),
        ]);
        return empty($goods['data']) ? [] : $goods['data'];
    }

    /**
     * 用户兑换记录列表（近期）
     * @author  Devil
     * @date    2026-07-01
     * @param   [int]          $user_id [用户id]
     * @param   [int]          $limit   [数量]
     */
    public static function UserExchangeRecordList($user_id, $page = 1, $limit = 10)
    {
        $user_id = intval($user_id);
        if($user_id <= 0)
        {
            return [];
        }
        $page = intval($page) > 0 ? intval($page) : 1;
        $limit = intval($limit) > 0 ? intval($limit) : 10;
        $data = Db::name('PluginsGiftcardCardSecret')->where([
            ['user_id', '=', $user_id],
            ['is_exchange', '=', 1],
        ])->field('id,data_type,secret_value,use_status,exchange_time')->order('exchange_time desc, id desc')->page($page, $limit)->select()->toArray();

        $result = [];
        foreach($data as $v)
        {
            $title = '兑换记录';
            $state = 'used';
            $status_text = '已兑换';
            switch(intval($v['data_type']))
            {
                case 0 :
                    $title = '余额兑换 ¥'.$v['secret_value'];
                    break;
                case 1 :
                    $title = '优惠券兑换';
                    break;
                case 2 :
                    $title = '积分兑换 '.$v['secret_value'].'积分';
                    break;
                case 3 :
                    $title = '实物商品兑换';
                    if(intval($v['use_status']) == 0)
                    {
                        $state = 'usable';
                        $status_text = '立即使用';
                    } else {
                        $status_text = '已领取';
                    }
                    break;
            }
            $result[] = [
                'data_type'     => intval($v['data_type']),
                'state'         => $state,
                'title'         => $title,
                'date_text'     => '兑换时间：'.(empty($v['exchange_time']) ? '' : date('Y.m.d', $v['exchange_time'])),
                'status_text'   => $status_text,
            ];
        }
        return $result;
    }



    /**
     * 用户卡密使用状态更新
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-06-30
     * @desc    description
     * @param   [int]          $order_id       [订单id]
     * @param   [int]          $type           [操作类型（0释放, 1使用）]
     * @param   [array]        $extension_data [订单扩展数据]
     */
    public static function UserCardSecretUseStatusUpdate($order_id, $type, $extension_data = null)
    {
        // 订单信息处理
        if($extension_data === null)
        {
            $info = Db::name('Order')->where(['id'=>intval($order_id)])->field('order_no,extension_data')->find();
            $extension_data = isset($info['extension_data']) ? $info['extension_data'] : '';
            $order_no = isset($info['order_no']) ? $info['order_no'] : '';
        } else {
            $order_no = Db::name('Order')->where(['id'=>intval($order_id)])->value('order_no');
        }
        if(!empty($extension_data) && !empty($order_no))
        {
            if(!is_array($extension_data))
            {
                $extension_data = json_decode($extension_data, true);
            }
            if(!empty($extension_data))
            {
                foreach($extension_data as $v)
                {
                    if(!empty($v['business']) && $v['business'] == 'plugins-giftcard-goods-exchange' && !empty($v['ext']) && is_array($v['ext']))
                    {
                        foreach($v['ext'] as $ev)
                        {
                            if(!empty($ev['card_secret_id']) && !empty($ev['goods_id']))
                            {
                                $card_secret = Db::name('PluginsGiftcardCardSecret')->where(['id'=>$ev['card_secret_id']])->field('secret_value,use_data')->find();
                                $use_data = empty($card_secret['use_data']) ? [] : array_column(json_decode($card_secret['use_data'], true), null, 'order_id');
                                if($type == 1)
                                {
                                    $use_data[$order_id] = [
                                        'order_id'     => $order_id,
                                        'order_no'     => $order_no,
                                        'goods_id'     => $ev['goods_id'],
                                        'goods_title'  => $ev['goods_title'],
                                        'goods_price'  => $ev['goods_price'],
                                        'goods_spec'   => $ev['goods_spec'],
                                        'use_time'     => time(),
                                    ];
                                } else {
                                    unset($use_data[$order_id]);
                                }
                                if(Db::name('PluginsGiftcardCardSecret')->where(['id'=>$ev['card_secret_id']])->update([
                                    'use_status'  => empty($use_data) ? 0 : 1,
                                    'use_data'    => empty($use_data) ? '' : json_encode(array_values($use_data), JSON_UNESCAPED_UNICODE),
                                    'upd_time'    => time(),
                                ]) === false)
                                {
                                    return DataReturn('礼品卡更新失败', -1);
                                }
                            }
                        }
                    }
                }
            }
        }
        return DataReturn('success', 0);
    }
}
?>