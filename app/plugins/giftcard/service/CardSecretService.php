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
            // 商品（secret_value 支持 gid 或 gid:数量 格式）
            $goods_ids = [];
            foreach($data as $item)
            {
                if(isset($item['data_type']) && $item['data_type'] == 3 && !empty($item['secret_value']))
                {
                    $goods_ids = array_merge($goods_ids, array_keys(self::SecretValueGoodsParse($item['secret_value'])));
                }
            }
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
                            $goods_num_map = self::SecretValueGoodsParse($v['secret_value']);
                            $secret_value_data = [];
                            foreach($goods_num_map as $gid=>$num)
                            {
                                if(!empty($goods) && array_key_exists($gid, $goods))
                                {
                                    $temp_goods = $goods[$gid];
                                    $temp_goods['exchange_num'] = $num;
                                    $secret_value_data[] = $temp_goods;
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

        // 防刷限制：同一用户短时间内失败次数过多则临时拒绝（防止暴力遍历卡密）
        $fail_cache_key = 'plugins_giftcard_exchange_fail_'.$params['user']['id'];
        $fail_count = intval(MyCache($fail_cache_key));
        if($fail_count >= 5)
        {
            return DataReturn('尝试次数过多、请1分钟后再试！', -1);
        }

        // 卡密信息
        $data = Db::name('PluginsGiftcardCardSecret')->where(['secret_key'=>trim($params['secret_key'])])->find();
        if(empty($data))
        {
            MyCache($fail_cache_key, $fail_count+1, 60);
            return DataReturn('没有相关礼品卡密数据！', -1);
        }
        // 是否已兑换（预检提示、最终以下方原子更新结果为准）
        if($data['is_exchange'] == 1)
        {
            // 新增日志
            $ret = self::CardSecretLogInsert($data, $params);
            if($ret['code'] != 0)
            {
                return $ret;
            }
            MyCache($fail_cache_key, $fail_count+1, 60);
            return DataReturn('该礼品卡密已被使用！', -1);
        }
        // 礼品卡主体停用则不可兑换（作废控制：后台停用卡即整批作废）
        $card = Db::name('PluginsGiftcardCard')->where(['id'=>$data['card_id']])->find();
        if(empty($card) || $card['is_enable'] != 1)
        {
            return DataReturn('该礼品卡已停用、无法兑换！', -1);
        }

        // 处理数据
        Db::startTrans();
        try {
            // 原子抢占核销：带 is_exchange=0 条件的更新、并发时仅一个请求能成功（防止同一卡密重复兑换）
            $claim = Db::name('PluginsGiftcardCardSecret')->where(['id'=>$data['id'], 'is_exchange'=>0])->update([
                'user_id'        => $params['user']['id'],
                'is_exchange'    => 1,
                'exchange_time'  => time(),
                'upd_time'       => time(),
            ]);
            if($claim < 1)
            {
                throw new \Exception('该礼品卡密已被使用！');
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
            MyCache($fail_cache_key, $fail_count+1, 60);
            return DataReturn($e->getMessage(), -1);
        }
    }

    /**
     * 商品卡密数据解析
     * @author  Devil
     * @date    2026-07-06
     * @desc    secret_value 支持「gid」或「gid:数量」格式、逗号分隔（不带数量默认1件），如：12:2,35 表示商品12可兑2件、商品35可兑1件
     * @param   [string]          $secret_value [卡密数据]
     * @return  [array]           [商品id => 可兑数量]
     */
    public static function SecretValueGoodsParse($secret_value)
    {
        $result = [];
        if(!empty($secret_value))
        {
            foreach(explode(',', str_replace('，', ',', $secret_value)) as $item)
            {
                $item = trim($item);
                if($item === '')
                {
                    continue;
                }
                $temp = explode(':', $item);
                $gid = intval($temp[0]);
                $num = isset($temp[1]) ? max(1, intval($temp[1])) : 1;
                if($gid > 0)
                {
                    $result[$gid] = (isset($result[$gid]) ? $result[$gid] : 0) + $num;
                }
            }
        }
        return $result;
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
                // 卡密商品与可兑数量（gid => 总件数、支持 gid:数量 格式）
                $goods_num_map = self::SecretValueGoodsParse($v['secret_value']);
                $v['secret_value'] = array_keys($goods_num_map);

                // 使用数据
                $v['use_data'] = empty($v['use_data']) ? '' : json_decode($v['use_data'], true);

                // 已使用件数（gid => 件数）
                $use_num_map = [];
                if(!empty($v['use_data']) && is_array($v['use_data']))
                {
                    foreach($v['use_data'] as $uv)
                    {
                        if(!empty($uv['goods_id']))
                        {
                            $gid = intval($uv['goods_id']);
                            $use_num_map[$gid] = (isset($use_num_map[$gid]) ? $use_num_map[$gid] : 0) + (isset($uv['stock']) ? max(1, intval($uv['stock'])) : 1);
                        }
                    }
                }

                // 剩余可兑件数（gid => 件数）
                $not_use_goods_num = [];
                foreach($goods_num_map as $gid=>$num)
                {
                    $left = $num - (isset($use_num_map[$gid]) ? $use_num_map[$gid] : 0);
                    if($left > 0)
                    {
                        $not_use_goods_num[$gid] = $left;
                    }
                }
                $v['goods_num_map'] = $goods_num_map;
                $v['not_use_goods_num'] = $not_use_goods_num;
                // 兼容原字段：仍有剩余件数的商品id
                $v['not_use_goods_ids'] = array_keys($not_use_goods_num);
            }
        }
        return $data;
    }

    /**
     * 礼品卡兑换支付校验（服务端核验）
     * 仅当请求声明 is_gift=1、且当前用户确实持有未使用的礼品卡商品兑换权益、并可完全覆盖本单参与积分兑换的商品件数时才放行。
     * 用于防止仅凭客户端 is_gift 标识绕过积分余额校验、把积分应付归0从而免费下单（fail closed：任一条件不满足均返回0）。
     * @author  Devil
     * @param   [array]        $goods    [仓库组商品数据(含 goods_items)]
     * @param   [int]          $user_id  [用户id, 为空则自动解析登录用户]
     * @return  [int]                    [1 允许礼品卡兑换支付, 0 否]
     */
    public static function GiftPayVerify($goods, $user_id = 0)
    {
        // 客户端未声明礼品卡兑换支付
        if(intval(MyInput('is_gift')) != 1)
        {
            return 0;
        }

        // 解析用户（未指定则读取请求参数/登录用户）
        $user_id = intval($user_id);
        if($user_id <= 0)
        {
            $user_id = intval(MyInput('user_id'));
            if($user_id <= 0)
            {
                $user = \app\service\UserService::LoginUserInfo();
                $user_id = empty($user['id']) ? 0 : intval($user['id']);
            }
        }
        if($user_id <= 0)
        {
            return 0;
        }

        // 统计本单参与积分兑换的商品所需件数（仅积分兑换商品，按 goods_id 累计购买件数）
        $need_map = [];
        if(!empty($goods) && is_array($goods))
        {
            foreach($goods as $v)
            {
                if(!empty($v['goods_items']) && is_array($v['goods_items']))
                {
                    foreach($v['goods_items'] as $vs)
                    {
                        if(!empty($vs['plugins_points_data']) && !empty($vs['goods_id']) && !empty($vs['stock']) && $vs['stock'] > 0)
                        {
                            $gid = intval($vs['goods_id']);
                            $need_map[$gid] = (isset($need_map[$gid]) ? $need_map[$gid] : 0) + intval($vs['stock']);
                        }
                    }
                }
            }
        }
        // 本单不含积分兑换商品、无需放行
        if(empty($need_map))
        {
            return 0;
        }

        // 汇总用户持有的未使用礼品卡商品兑换权益（gid => 剩余可兑件数、跨卡累计）
        $user_card = self::CardSecretValueGoodsData($user_id);
        $remain = [];
        if(!empty($user_card) && is_array($user_card))
        {
            foreach($user_card as $cv)
            {
                if(!empty($cv['not_use_goods_num']) && is_array($cv['not_use_goods_num']))
                {
                    foreach($cv['not_use_goods_num'] as $gid=>$num)
                    {
                        $gid = intval($gid);
                        $remain[$gid] = (isset($remain[$gid]) ? $remain[$gid] : 0) + intval($num);
                    }
                }
            }
        }

        // 每个参与兑换的商品都必须被礼品卡兑换权益完全覆盖，否则不予放行（fail closed）
        foreach($need_map as $gid=>$need)
        {
            if(empty($remain[$gid]) || $remain[$gid] < $need)
            {
                return 0;
            }
        }
        return 1;
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
        // 已兑换实物卡密(data_type=3)可领取的商品与剩余件数
        $data = self::CardSecretValueGoodsData($user_id);
        $goods_left_num = [];
        if(!empty($data) && is_array($data))
        {
            foreach($data as $v)
            {
                if(!empty($v['not_use_goods_num']) && is_array($v['not_use_goods_num']))
                {
                    foreach($v['not_use_goods_num'] as $gid=>$num)
                    {
                        $goods_left_num[$gid] = (isset($goods_left_num[$gid]) ? $goods_left_num[$gid] : 0) + $num;
                    }
                }
            }
        }
        $goods_ids = array_keys(array_filter($goods_left_num));
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
        if(empty($goods['data']))
        {
            return [];
        }
        // 附加剩余可兑件数
        foreach($goods['data'] as &$gv)
        {
            $gv['exchange_num'] = isset($goods_left_num[$gv['id']]) ? $goods_left_num[$gv['id']] : 1;
        }
        return $goods['data'];
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
                                // 同一订单同一卡密可能兑换多个商品、以 订单id+商品id 作为使用记录key
                                $use_data_list = empty($card_secret['use_data']) ? [] : json_decode($card_secret['use_data'], true);
                                $use_data = [];
                                foreach($use_data_list as $udv)
                                {
                                    $use_data[$udv['order_id'].'_'.(isset($udv['goods_id']) ? $udv['goods_id'] : 0)] = $udv;
                                }
                                $use_key = $order_id.'_'.$ev['goods_id'];
                                if($type == 1)
                                {
                                    $use_data[$use_key] = [
                                        'order_id'     => $order_id,
                                        'order_no'     => $order_no,
                                        'goods_id'     => $ev['goods_id'],
                                        'goods_title'  => $ev['goods_title'],
                                        'goods_price'  => $ev['goods_price'],
                                        'goods_spec'   => $ev['goods_spec'],
                                        'stock'        => isset($ev['stock']) ? max(1, intval($ev['stock'])) : 1,
                                        'use_time'     => time(),
                                    ];
                                } else {
                                    // 释放该订单占用的全部使用记录
                                    foreach($use_data as $udk=>$udv)
                                    {
                                        if(intval($udv['order_id']) == intval($order_id))
                                        {
                                            unset($use_data[$udk]);
                                        }
                                    }
                                }
                                // 按件数计算是否已用完（部分使用时卡密仍可继续兑换剩余件数）
                                $goods_num_map = self::SecretValueGoodsParse($card_secret['secret_value']);
                                $total_num = array_sum($goods_num_map);
                                $used_num = 0;
                                foreach($use_data as $udv)
                                {
                                    $used_num += isset($udv['stock']) ? max(1, intval($udv['stock'])) : 1;
                                }
                                if(Db::name('PluginsGiftcardCardSecret')->where(['id'=>$ev['card_secret_id']])->update([
                                    'use_status'  => ($total_num > 0 && $used_num >= $total_num) ? 1 : 0,
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