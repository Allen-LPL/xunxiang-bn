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
namespace app\plugins\wallet\service;

use think\facade\Db;
use app\service\PluginsService;
use app\service\UserService;
use app\service\ResourcesService;
use app\service\PaymentService;

/**
 * 基础服务层
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class BaseService
{
    // 基础私有字段
    public static $base_config_private_field = [
        'wallet_edit_money_password',
    ];

    // 基础数据附件字段
    public static $base_config_attachment_field = [
        'default_center_head_bg_images_app',
    ];

    // 类型名称
    public static $business_type_name = 'wallet';

    /**
     * 基础配置信息保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-11-18
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function BaseConfigSave($params = [])
    {
        return PluginsService::PluginsDataSave(['plugins'=>'wallet', 'data'=>$params], self::$base_config_attachment_field);
    }

    /**
     * 基础配置信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-24
     * @desc    description
     * @param   [boolean]          $is_cache [是否缓存中读取]
     */
    public static function BaseConfig($is_cache = true)
    {
        $ret = PluginsService::PluginsData('wallet', '', $is_cache);
        if(!empty($ret['data']))
        {
            // 自定义充值赠送
            if(!empty($ret['data']['custom_recharge_give']))
            {
                $ret['data']['custom_recharge_give'] = explode("\n", $ret['data']['custom_recharge_give']);
            }

            // 充值规则说明
            if(!empty($ret['data']['recharge_desc']))
            {
                $ret['data']['recharge_desc'] = explode("\n", $ret['data']['recharge_desc']);
            }

            // 会员中心公告
            if(!empty($ret['data']['user_center_notice']))
            {
                $ret['data']['user_center_notice'] = explode("\n", $ret['data']['user_center_notice']);
            }

            // 充值可选支付方式
            if(!empty($ret['data']['recharge_can_payment']))
            {
                $ret['data']['recharge_can_payment'] = explode(',', $ret['data']['recharge_can_payment']);
            }

            // 提现类型
            if(isset($ret['data']['cash_type']))
            {
                $ret['data']['cash_type'] = ($ret['data']['cash_type'] == '') ? [] : explode(',', $ret['data']['cash_type']);
            }
        }
        return $ret;
    }

    /**
     * 后台权限菜单
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2025-11-21
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function AdminPowerMenu($params = [])
    {
        return [
            [
                'name'      => '基础配置编辑',
                'control'   => 'admin',
                'action'    => 'saveinfo',
                'item'      => [
                    ['name' => '保存', 'action' => 'save'],
                ],
            ],
            [
                'name'      => '钱包管理',
                'control'   => 'wallet',
                'action'    => 'index',
                'item'      => [
                    ['name' => '详情', 'action' => 'detail'],
                    ['name' => '钱包编辑页面', 'action' => 'saveinfo'],
                    ['name' => '钱包余额修改页面', 'action' => 'changeinfo'],
                    ['name' => '钱包编辑', 'action' => 'save'],
                    ['name' => '钱包余额修改', 'action' => 'change'],
                ],
            ],
            [
                'name'      => '账户明细',
                'control'   => 'walletlog',
                'action'    => 'index',
                'item'      => [
                    ['name' => '详情', 'action' => 'detail'],
                    ['name' => '删除', 'action' => 'delete'],
                ],
            ],
            [
                'name'      => '充值管理',
                'control'   => 'recharge',
                'action'    => 'index',
                'item'      => [
                    ['name' => '详情', 'action' => 'detail'],
                    ['name' => '支付', 'action' => 'pay'],
                    ['name' => '删除', 'action' => 'delete'],
                ],
            ],
            [
                'name'      => '提现管理',
                'control'   => 'cash',
                'action'    => 'index',
                'item'      => [
                    ['name' => '详情', 'action' => 'detail'],
                    ['name' => '审核页面', 'action' => 'auditinfo'],
                    ['name' => '审核', 'action' => 'audit'],
                    ['name' => '微信转账', 'action' => 'weixinpay'],
                    ['name' => '支付宝转账', 'action' => 'alipaypay'],
                    ['name' => '支付刷新', 'action' => 'payrefresh'],
                ],
            ],
            [
                'name'      => '提现支付日志',
                'control'   => 'cashpayment',
                'action'    => 'index',
                'item'      => [
                    ['name' => '详情', 'action' => 'detail'],
                    ['name' => '删除', 'action' => 'delete'],
                ],
            ],
            [
                'name'      => '配置信息',
                'control'   => 'config',
                'action'    => 'index',
                'item'      => [
                    ['name' => '保存', 'action' => 'save'],
                ],
            ],
            [
                'name'      => '转账记录',
                'control'   => 'transfer',
                'action'    => 'index',
                'item'      => [
                    ['name' => '详情', 'action' => 'detail'],
                ],
            ],
        ];
    }

    /**
     * 后台导航
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-12-06
     * @desc    description
     */
    public static function AdminNavMenuList($config = [])
    {
        return [
            [
                'name'      => '基础配置',
                'control'   => 'admin',
                'action'    => 'index',
            ],
            [
                'name'      => '钱包管理',
                'control'   => 'wallet',
                'action'    => 'index',
            ],
            [
                'name'      => '钱包明细',
                'control'   => 'walletlog',
                'action'    => 'index',
            ],
            [
                'name'      => '充值记录',
                'control'   => 'recharge',
                'action'    => 'index',
            ],
            [
                'name'      => '提现记录',
                'control'   => 'cash',
                'action'    => 'index',
            ],
            [
                'name'      => '转账记录',
                'control'   => 'transfer',
                'action'    => 'index',
            ],
        ];
    }

    /**
     * 静态数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2023-10-27
     * @desc    description
     * @param   [string]          $key [数据key]
     */
    public static function ConstData($key)
    {
        $data = [
            // 钱包状态
            'wallet_status_list' => [
                0 => ['value' => 0, 'name' => '正常', 'checked' => true],
                1 => ['value' => 1, 'name' => '异常'],
                2 => ['value' => 2, 'name' => '已注销'],
            ],

            // 业务类型
            'business_type_list' => [
                0 => ['value' => 0, 'name' => '系统', 'checked' => true],
                1 => ['value' => 1, 'name' => '充值'],
                2 => ['value' => 2, 'name' => '提现'],
                3 => ['value' => 3, 'name' => '消费'],
                4 => ['value' => 4, 'name' => '转账'],
            ],

            // 金额类型
            'wallet_money_type_list' => [
                0 => ['value' => 0, 'name' => '有效', 'checked' => true],
                1 => ['value' => 1, 'name' => '冻结'],
                2 => ['value' => 2, 'name' => '赠送'],
            ],

            // 钱包操作类型
            'wallet_operate_type_list' =>  [
                0 => ['value' => 0, 'name' => '减少', 'checked' => true],
                1 => ['value' => 1, 'name' => '增加'],
            ],

            // 充值赠送类型
            'recharge_give_type_list' =>  [
                0 => ['value' => 0, 'name' => '固定金额', 'checked' => true],
                1 => ['value' => 1, 'name' => '比例'],
            ],

            // 提现类型
            'cash_type_list' => [
                0 => ['value' => 0, 'name' => '其他方式'],
                1 => ['value' => 1, 'name' => '微信'],
                2 => ['value' => 2, 'name' => '支付宝'],
            ],

            // 提现状态
            'cash_status_list' => [
                0 => ['value' => 0, 'name' => '未打款', 'checked' => true],
                1 => ['value' => 1, 'name' => '待收款'],
                2 => ['value' => 2, 'name' => '已打款'],
                3 => ['value' => 3, 'name' => '打款失败'],
            ],

            // 提现支付方式
            'cash_payment_pay_type_list' => [
                0 => ['value' => 0, 'name' => '微信'],
                1 => ['value' => 1, 'name' => '支付宝'],
            ],

            // 提现支付状态
            'cash_payment_status_list' => [
                0 => ['value' => 0, 'name' => '待处理'],
                1 => ['value' => 1, 'name' => '待收款'],
                2 => ['value' => 2, 'name' => '已支付'],
                3 => ['value' => 3, 'name' => '已失败'],
                4 => ['value' => 4, 'name' => '已关闭'],
            ],

            // 充值支付状态
            'recharge_status_list' => [
                0 => ['value' => 0, 'name' => '未支付', 'checked' => true],
                1 => ['value' => 1, 'name' => '已支付'],
            ],

            // 微信提现场景数据
            'weixin_transfer_scene_report_list' => [
                // 现金营销
                '1000' => [
                    'name' => '现金营销',
                    'data' => [
                        [
                            'info_type'    => '活动名称',
                            'info_content' => '新会员有礼',
                        ],
                        [
                            'info_type'    => '奖励说明',
                            'info_content' => '抽奖一等奖',
                        ],
                    ],
                ],

                // 行政补贴
                '1002' => [
                    'name' => '行政补贴',
                    'data' => [
                        [
                            'info_type'    => '补贴类型',
                            'info_content' => '购车补贴',
                        ],
                    ],
                ],

                // 保险理赔
                '1004' => [
                    'name' => '保险理赔',
                    'data' => [
                        [
                            'info_type'    => '保险产品备案编号',
                            'info_content' => '01212121212',
                        ],
                        [
                            'info_type'    => '保险名称',
                            'info_content' => '意外险',
                        ],
                        [
                            'info_type'    => '保险操作单号',
                            'info_content' => '12121245442',
                        ],
                    ],
                ],

                // 佣金报酬
                '1005' => [
                    'name' => '佣金报酬',
                    'data' => [
                        [
                            'info_type'     => '岗位类型',
                            'info_content'  => '外卖员',
                        ],
                        [
                            'info_type'     => '报酬说明',
                            'info_content'  => '7月份配送费',
                        ],
                    ],
                ],

                // 采购货款
                '1009' => [
                    'name' => '采购货款',
                    'data' => [
                        [
                            'info_type'    => '采购商品名称',
                            'info_content' => '戴尔笔记本电脑',
                        ],
                    ],
                ],

                // 二手回收
                '1010' => [
                    'name' => '二手回收',
                    'data' => [
                        [
                            'info_type'    => '回收商品名称',
                            'info_content' => '塑料瓶',
                        ],
                    ],
                ],

                // 企业赔付
                '1011' => [
                    'name' => '企业赔付',
                    'data' => [
                        [
                            'info_type'    => '赔付原因',
                            'info_content' => '商品质量问题退款',
                        ],
                    ],
                ],

                // 公益补助
                '1013' => [
                    'name' => '公益补助',
                    'data' => [
                        [
                            'info_type'    => '公益活动名称',
                            'info_content' => '在民政部的备案名称',
                        ],
                        [
                            'info_type'    => '公益活动备案编号',
                            'info_content' => '在民政部的备案编号',
                        ],
                    ],
                ],
            ],
        ];
        return array_key_exists($key, $data) ? $data[$key] : null;
    }

    /**
     * 默认图片数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2023-07-27
     * @desc    description
     * @param   [array]          $config [配置信息]
     */
    public static function DefaultImagesData($config = [])
    {
        return [
            'default_center_head_bg_images_app'  => empty($config['default_center_head_bg_images_app']) ? StaticAttachmentUrl('app/head-bg.png') : $config['default_center_head_bg_images_app'],
        ];
    }

    /**
     * 用户可提现类型列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-09-19
     * @desc    description
     * @param   [array]      $plugins_config [插件配置]
     * @param   [array]      $params         [输入参数]
     */
    public static function UserCashTypeList($plugins_config = [], $params = [])
    {
        $result = [];
        if(!empty($plugins_config['cash_type']))
        {
            $cash_type_list = self::ConstData('cash_type_list');
            foreach($cash_type_list as $v)
            {
                if(in_array($v['value'], $plugins_config['cash_type']))
                {
                    // 非web和微信端，不存在用户openid则不能微信提现方式
                    if($v['value'] == 1 && empty($params['user_weixin_openid']) && !in_array(APPLICATION_CLIENT_TYPE, ['web', 'weixin']))
                    {
                        continue;
                    }

                    // 加入列表
                    $result[] = $v;
                }
            }
        }
        return $result;
    }

    /**
     * 用户openid值
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-09-19
     * @desc    description
     * @param   [int]           $user_id [用户id]
     */
    public static function UserWeixinOpenidValue($user_id)
    {
        $result = [
            'value' => '',
            'type' => '',
        ];
        $platform = Db::name('UserPlatform')->where(['user_id'=>$user_id])->column('weixin_openid,weixin_web_openid');
        if(!empty($platform))
        {
            foreach($platform as $v)
            {
                if(!empty($v['weixin_openid']))
                {
                    $result['value'] = $v['weixin_openid'];
                    $result['type'] = 'mini';
                    break;
                }
                if(!empty($v['weixin_web_openid']))
                {
                    $result['value'] = $v['weixin_web_openid'];
                    $result['type'] = 'web';
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * 钱包列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-04-30T00:13:14+0800
     * @param   [array]          $params [输入参数]
     */
    public static function WalletList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'id desc' : $params['order_by'];

        // 获取数据列表
        $data = self::WalletListHandle(Db::name('PluginsWallet')->field($field)->where($where)->limit($m, $n)->order($order_by)->select()->toArray());
        return DataReturn(MyLang('handle_success'), 0, $data);
    }

    /**
     * 钱包列表数据处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2023-06-28
     * @desc    description
     * @param   [array]          $data   [数据列表]
     * @param   [array]          $params [输入参数]
     */
    public static function WalletListHandle($data, $params = [])
    {
        if(!empty($data))
        {
            $wallet_status_list = self::ConstData('wallet_status_list');
            foreach($data as &$v)
            {
                // 用户信息
                $v['user'] = UserService::GetUserViewInfo($v['user_id']);

                // 状态
                $v['status_name'] = (isset($v['status']) && isset($wallet_status_list[$v['status']])) ? $wallet_status_list[$v['status']]['name'] : '未知';

                // 时间
                $v['add_time'] = empty($v['add_time']) ? '' : date('Y-m-d H:i:s', $v['add_time']);
                $v['upd_time'] = empty($v['upd_time']) ? '' : date('Y-m-d H:i:s', $v['upd_time']);
            }
        }
        return $data;
    }

    /**
     * 钱包总数
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $where [条件]
     */
    public static function WalletTotal($where = [])
    {
        return (int) Db::name('PluginsWallet')->where($where)->count();
    }

    /**
     * 钱包条件
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function WalletWhere($params = [])
    {
        $where = [];

        // 用户
        if(!empty($params['keywords']))
        {
            $user_ids = Db::name('User')->where('number_code|username|nickname|mobile|email', '=', $params['keywords'])->column('id');
            if(!empty($user_ids))
            {
                $where[] = ['user_id', 'in', $user_ids];
            } else {
                // 无数据条件，避免用户搜索条件没有数据造成的错觉
                $where[] = ['id', '=', 0];
            }
        }

        // 状态
        if(isset($params['status']) && $params['status'] > -1)
        {
            $where[] = ['status', '=', $params['status']];
        }

        return $where;
    }

    /**
     * 充值列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-04-30T00:13:14+0800
     * @param   [array]          $params [输入参数]
     */
    public static function RechargeList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'id desc' : $params['order_by'];

        // 获取数据列表
        $data = self::RechargeListHandle(Db::name('PluginsWalletRecharge')->field($field)->where($where)->limit($m, $n)->order($order_by)->select()->toArray());
        return DataReturn(MyLang('handle_success'), 0, $data);
    }

    /**
     * 充值列表数据处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2023-06-28
     * @desc    description
     * @param   [array]          $data   [数据列表]
     * @param   [array]          $params [输入参数]
     */
    public static function RechargeListHandle($data, $params = [])
    {
        if(!empty($data))
        {
            $user_type = isset($params['user_type']) ? $params['user_type'] : 'user';
            $common_gender_list = MyConst('common_gender_list');
            $recharge_status_list = self::ConstData('recharge_status_list');
            foreach($data as &$v)
            {
                // 用户信息
                $v['user'] = ($user_type == 'user') ? null : UserService::GetUserViewInfo($v['user_id']);

                // 支付状态
                $v['status_name'] = isset($v['status']) ? $recharge_status_list[$v['status']]['name'] : '';

                // 支付时间
                $v['pay_time'] = empty($v['pay_time']) ? '' : date('Y-m-d H:i:s', $v['pay_time']);

                // 创建时间
                $v['add_time'] = empty($v['add_time']) ? '' : date('Y-m-d H:i:s', $v['add_time']);
            }
        }
        return $data;
    }

    /**
     * 充值列表总数
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $where [条件]
     */
    public static function RechargeTotal($where = [])
    {
        return (int) Db::name('PluginsWalletRecharge')->where($where)->count();
    }

    /**
     * 充值列表条件
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function RechargeWhere($params = [])
    {
        $where = [];

        // 用户id
        if(!empty($params['user']))
        {
            $where[] = ['user_id', '=', $params['user']['id']];
        }

        // 关键字根据用户筛选
        if(!empty($params['keywords']))
        {
            if(empty($params['user']))
            {
                $user_ids = Db::name('User')->where('number_code|username|nickname|mobile|email', '=', $params['keywords'])->column('id');
                if(!empty($user_ids))
                {
                    $where[] = ['user_id', 'in', $user_ids];
                } else {
                    // 无数据条件，走单号条件
                    $where[] = ['recharge_no', '=', $params['keywords']];
                }
            }
        }

        // id
        if(!empty($params['id']))
        {
            $where[] = ['id', '=', intval($params['id'])];
        }
        // 订单号
        if(!empty($params['orderno']))
        {
            $where[] = ['recharge_no', '=', trim($params['orderno'])];
        }

        // 状态
        if(isset($params['status']) && $params['status'] > -1)
        {
            $where[] = ['status', '=', $params['status']];
        }

        return $where;
    }

    /**
     * 钱包明细列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-04-30T00:13:14+0800
     * @param   [array]          $params [输入参数]
     */
    public static function WalletLogList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'id desc' : $params['order_by'];

        // 获取数据列表
        $data = self::WalletLogListHandle(Db::name('PluginsWalletLog')->field($field)->where($where)->limit($m, $n)->order($order_by)->select()->toArray(), $params);
        return DataReturn(MyLang('handle_success'), 0, $data);
    }

    /**
     * 钱包明细列表数据处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2023-06-28
     * @desc    description
     * @param   [array]          $data   [数据列表]
     * @param   [array]          $params [输入参数]
     */
    public static function WalletLogListHandle($data, $params = [])
    {
        if(!empty($data))
        {
            $is_user = (!isset($params['is_user']) || $params['is_user'] == 1);
            $business_type_list = self::ConstData('business_type_list');
            $wallet_operate_type_list = self::ConstData('wallet_operate_type_list');
            $wallet_money_type_list = self::ConstData('wallet_money_type_list');
            foreach($data as &$v)
            {
                // 用户信息
                if($is_user)
                {
                    $v['user'] = UserService::GetUserViewInfo($v['user_id']);
                }
                
                // 业务类型
                $v['business_type_name'] = (isset($v['business_type']) && isset($business_type_list[$v['business_type']])) ? $business_type_list[$v['business_type']]['name'] : '未知';

                // 操作类型
                $v['operation_type_name'] = (isset($v['operation_type']) && isset($wallet_operate_type_list[$v['operation_type']])) ? $wallet_operate_type_list[$v['operation_type']]['name'] : '未知';

                // 金额类型
                $v['money_type_name'] = (isset($v['money_type']) && isset($wallet_money_type_list[$v['money_type']])) ? $wallet_money_type_list[$v['money_type']]['name'] : '未知';

                // 操作原因
                $v['msg'] = empty($v['msg']) ? '' : str_replace("\n", '<br />', $v['msg']);

                // 创建时间
                $v['add_time'] = empty($v['add_time']) ? '' : date('Y-m-d H:i:s', $v['add_time']);
            }
        }
        return $data;
    }

    /**
     * 钱包明细总数
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $where [条件]
     */
    public static function WalletLogTotal($where = [])
    {
        return (int) Db::name('PluginsWalletLog')->where($where)->count();
    }

    /**
     * 钱包明细条件
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function WalletLogWhere($params = [])
    {
        $where = [];

        // id
        if(!empty($params['id']))
        {
            $where[] = ['id', '=', intval($params['id'])];
        }
        
        // 用户id
        if(!empty($params['user']))
        {
            $where[] = ['user_id', '=', $params['user']['id']];
        }

        // 用户
        if(!empty($params['keywords']))
        {
            $user_ids = Db::name('User')->where('number_code|username|nickname|mobile|email', '=', $params['keywords'])->column('id');
            if(!empty($user_ids))
            {
                $where[] = ['user_id', 'in', $user_ids];
            } else {
                // 无数据条件，避免用户搜索条件没有数据造成的错觉
                $where[] = ['id', '=', 0];
            }
        }

        // 业务类型
        if(isset($params['business_type']) && $params['business_type'] > -1)
        {
            $where[] = ['business_type', '=', $params['business_type']];
        }

        // 操作类型
        if(isset($params['operation_type']) && $params['operation_type'] > -1)
        {
            $where[] = ['operation_type', '=', $params['operation_type']];
        }

        // 金额类型
        if(isset($params['money_type']) && $params['money_type'] > -1)
        {
            $where[] = ['money_type', '=', $params['money_type']];
        }

        return $where;
    }

    /**
     * 提现列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-04-30T00:13:14+0800
     * @param   [array]          $params [输入参数]
     */
    public static function CashList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'id desc' : $params['order_by'];

        // 获取数据列表
        $data = self::CashListHandle(Db::name('PluginsWalletCash')->field($field)->where($where)->limit($m, $n)->order($order_by)->select()->toArray());
        return DataReturn(MyLang('handle_success'), 0, $data);
    }

    /**
     * 钱包明细列表数据处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2023-06-28
     * @desc    description
     * @param   [array]          $data   [数据列表]
     * @param   [array]          $params [输入参数]
     */
    public static function CashListHandle($data, $params = [])
    {
        if(!empty($data))
        {
            $common_gender_list = MyConst('common_gender_list');
            $cash_status_list = self::ConstData('cash_status_list');
            $cash_type_list = self::ConstData('cash_type_list');
            foreach($data as &$v)
            {
                // 用户信息
                $v['user'] = UserService::GetUserViewInfo($v['user_id']);

                // 提现状态
                $v['status_name'] = isset($v['status']) ? $cash_status_list[$v['status']]['name'] : '';

                // 提现方式
                $v['cash_type_name'] = isset($v['cash_type']) ? $cash_type_list[$v['cash_type']]['name'] : '';

                // 备注
                $v['msg'] = empty($v['msg']) ? '' : str_replace("\n", '<br />', $v['msg']);

                // 手续费
                if($v['commission'] <= 0)
                {
                    $v['commission'] = '';
                }

                // 打款金额
                if($v['pay_money'] <= 0)
                {
                    $v['pay_money'] = '';
                }

                // 时间
                $v['pay_time'] = empty($v['pay_time']) ? '' : date('Y-m-d H:i:s', $v['pay_time']);
                $v['add_time'] = empty($v['add_time']) ? '' : date('Y-m-d H:i:s', $v['add_time']);
                $v['upd_time'] = empty($v['upd_time']) ? '' : date('Y-m-d H:i:s', $v['upd_time']);
            }
        }
        return $data;
    }

    /**
     * 提现列表总数
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $where [条件]
     */
    public static function CashTotal($where = [])
    {
        return (int) Db::name('PluginsWalletCash')->where($where)->count();
    }

    /**
     * 提现列表条件
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function CashWhere($params = [])
    {
        $where = [];

        // 用户id
        if(!empty($params['user']))
        {
            $where[] = ['user_id', '=', $params['user']['id']];
        }

        // id
        if(!empty($params['id']))
        {
            $where[] = ['id', '=', intval($params['id'])];
        }
        // 订单号
        if(!empty($params['orderno']))
        {
            $where[] = ['cash_no', '=', trim($params['orderno'])];
        }

        // 关键字根据用户筛选
        if(!empty($params['keywords']))
        {
            if(empty($params['user']))
            {
                $user_ids = Db::name('User')->where('number_code|username|nickname|mobile|email', '=', $params['keywords'])->column('id');
                if(!empty($user_ids))
                {
                    $where[] = ['user_id', 'in', $user_ids];
                } else {
                    // 无数据条件，走单号条件
                    $where[] = ['cash_no', '=', $params['keywords']];
                }
            }
        }

        // 状态
        if(isset($params['status']) && $params['status'] > -1)
        {
            $where[] = ['status', '=', $params['status']];
        }

        return $where;
    }

    /**
     * 用户提现审核数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-05-27
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function UserCashAuditData($params = [])
    {
        $data = null;
        $id = empty($params['id']) ? (empty($params['value']) ? '' : $params['value']) : $params['id'];
        if(!empty($id))
        {
            $data_params = [
                'm'         => 0,
                'n'         => 1,
                'where'     => [
                    'id' => intval($id),
                ],
            ];
            $ret = BaseService::CashList($data_params);
            if(!empty($ret['data']) && !empty($ret['data'][0]))
            {
                $data = $ret['data'][0];
                // 申请金额
                $data['apply_money'] = $data['money'];
                // 是否存在手续费
                if(isset($data['commission']) && $data['commission'] > 0)
                {
                    $data['money'] = PriceNumberFormat($data['money']-$data['commission']);
                }
            }
        }
        return $data;
    }

    /**
     * 转账列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-04-30T00:13:14+0800
     * @param   [array]          $params [输入参数]
     */
    public static function TransferList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'id desc' : $params['order_by'];

        // 获取数据列表
        $data = self::TransferListHandle(Db::name('PluginsWalletTransfer')->field($field)->where($where)->limit($m, $n)->order($order_by)->select()->toArray());
        return DataReturn(MyLang('handle_success'), 0, $data);
    }

    /**
     * 钱包明细列表数据处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2023-06-28
     * @desc    description
     * @param   [array]          $data   [数据列表]
     * @param   [array]          $params [输入参数]
     */
    public static function TransferListHandle($data, $params = [])
    {
        if(!empty($data))
        {
            $user_ids = array_unique(array_merge(array_filter(array_column($data, 'send_user_id')), array_filter(array_column($data, 'receive_user_id'))));
            $user_data = UserService::GetUserViewInfo($user_ids);
            foreach($data as &$v)
            {
                // 用户信息
                $v['send_user'] = (empty($user_data) || empty($user_data[$v['send_user_id']])) ? null : $user_data[$v['send_user_id']];
                $v['receive_user'] = (empty($user_data) || empty($user_data[$v['receive_user_id']])) ? null : $user_data[$v['receive_user_id']];

                // 时间
                $v['add_time'] = empty($v['add_time']) ? '' : date('Y-m-d H:i:s', $v['add_time']);
            }
        }
        return $data;
    }

    /**
     * 转账列表总数
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $where [条件]
     */
    public static function TransferTotal($where = [])
    {
        return (int) Db::name('PluginsWalletTransfer')->where($where)->count();
    }

    /**
     * 转账列表条件
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function TransferWhere($params = [])
    {
        $where = [];

        // 用户id
        if(!empty($params['user']))
        {
            $user_field = empty($params['user_field']) ? 'user_id' : $params['user_field'];
            $where[] = [$user_field, '=', $params['user']['id']];
        }

        // id
        if(!empty($params['id']))
        {
            $where[] = ['id', '=', intval($params['id'])];
        }
        // 订单号
        if(!empty($params['orderno']))
        {
            $where[] = ['transfer_no', '=', trim($params['orderno'])];
        }

        // 关键字根据用户筛选
        if(!empty($params['keywords']))
        {
            if(empty($params['user']))
            {
                $user_ids = Db::name('User')->where('number_code|username|nickname|mobile|email', '=', $params['keywords'])->column('id');
                if(!empty($user_ids))
                {
                    $where[] = ['user_id', 'in', $user_ids];
                } else {
                    // 无数据条件，走单号条件
                    $where[] = ['transfer_no', '=', $params['keywords']];
                }
            }
        }

        return $where;
    }

    /**
     * 支付方式获取
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-19
     * @desc    description
     * @param  [string]          $config [插件配置]
     */
    public static function BuyPaymentList($config = null)
    {
        // 未指定配置则读取
        if($config === null)
        {
            $base = self::BaseConfig();
            $config = $base['data'];
        }

        // 排除钱包支付并获取当前终端可使用的支付方式
        $not = ['WalletPay'];
        $where = [
            ['is_enable', '=', 1],
            ['is_open_user', '=', 1],
            ['payment', 'not in', $not],
        ];
        $data = PaymentService::BuyPaymentList(['where'=>$where]);

        // 是否存在支付方式限制
        if(!empty($data) && !empty($config['recharge_can_payment']) && is_array($config['recharge_can_payment']))
        {
            foreach($data as $k=>$v)
            {
                if(!in_array($v['payment'], $config['recharge_can_payment']))
                {
                    unset($data[$k]);
                }
            }
        }

        return empty($data) ? [] : array_values($data);
    }

    /**
     * 钱包余额修改密码
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-10-10
     * @desc    description
     * @param   [string]          $password [密码]
     */
    public static function WalletMoneyEditPassword($password)
    {
        return md5(strrev(md5($password)));
    }

    /**
     * 用户中心菜单
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-10-18
     * @desc    description
     * @param   [array]          $base [配置信息]
     */
    public static function UserCenterNav($base)
    {
        // 不同平台url
        $url_arr = [];
        if(APPLICATION == 'app')
        {
            $url_arr['wallet'] = '/pages/plugins/wallet/wallet-log/wallet-log';
            $url_arr['recharge'] = '/pages/plugins/wallet/user-recharge/user-recharge';
            $url_arr['cash'] = '/pages/plugins/wallet/user-cash/user-cash';
            $url_arr['transfer'] = '/pages/plugins/wallet/user-transfer/user-transfer';
        } else {
            $url_arr['wallet'] = PluginsHomeUrl('wallet', 'wallet', 'index');
            $url_arr['recharge'] = PluginsHomeUrl('wallet', 'recharge', 'index');
            $url_arr['cash'] = PluginsHomeUrl('wallet', 'cash', 'index');
            $url_arr['transfer'] = PluginsHomeUrl('wallet', 'transfer', 'index');
        }

        // 导航数据
        $data = [
            [
                'title'     => '账户明细',
                'control'   => 'wallet',
                'action'    => 'index',
                'url'       => $url_arr['wallet'],
            ],
        ];
        // 是否开启充值
        if(isset($base['is_enable_recharge']) && $base['is_enable_recharge'] == 1)
        {
            $data[] = [
                'title'     => '充值记录',
                'control'   => 'recharge',
                'action'    => 'index',
                'url'       => $url_arr['recharge'],
            ];
        }
        // 是否开启提现
        if(isset($base['is_enable_cash']) && $base['is_enable_cash'] == 1)
        {
            $data[] = [
                'title'     => '提现记录',
                'control'   => 'cash',
                'action'    => 'index',
                'url'       => $url_arr['cash'],
            ];
        }
        // 是否开启转账
        if(isset($base['is_enable_transfer']) && $base['is_enable_transfer'] == 1)
        {
            $data[] = [
                'title'     => '转账记录',
                'control'   => 'transfer',
                'action'    => 'index',
                'url'       => $url_arr['transfer'],
            ];
        }
        return $data;
    }

    /**
     * 充值配置数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2023-10-10
     * @desc    description
     * @param   [array]          $config [插件配置]
     */
    public static function RechargeConfigData($config)
    {
        $preset_data = [];
        if(!empty($config['custom_recharge_give']) && is_array($config['custom_recharge_give']))
        {
            $currency_symbol = ResourcesService::CurrencyDataSymbol(['is_only_currency_default'=>1]);
            foreach($config['custom_recharge_give'] as $v)
            {
                if(!empty($v))
                {
                    $temp = explode('+', $v);
                    $val = floatval($temp[0]);
                    if($val > 0)
                    {
                        $preset_data[] = [
                            'value' => $val,
                            'tips'  => empty($temp[1]) ? '' : '送'.$currency_symbol.$temp[1],
                        ];
                    }
                }
            }
        }
        return [
            'preset_data'    => $preset_data,
            'recharge_desc'  => empty($config['recharge_desc']) ? null : $config['recharge_desc'],
        ];
    }

    /**
     * 用户查询
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2023-10-23
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function UserQuery($params = [])
    {
        // 数据验证
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'keywords',
                'error_msg'         => '请输入用户信息',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'plugins_config',
                'error_msg'         => MyLang('plugins_config_error_tips'),
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }
        // 是否已开启转账
        if(!isset($params['plugins_config']['is_enable_transfer']) || $params['plugins_config']['is_enable_transfer'] != 1)
        {
            return DataReturn('管理员未开启转账功能', -1);
        }

        // 查询用户、整数则加上id字段
        $id_field = is_numeric($params['keywords']) ? 'id|' : '';
        $where = [
            [$id_field.'number_code|username|nickname|mobile|email', '=', $params['keywords']],
            ['status', '=', 0],
            ['is_delete_time', '=', 0],
            ['is_logout_time', '=', 0],
        ];
        $user = Db::name('User')->where($where)->find();
        if(empty($user))
        {
            return DataReturn('用户不存在', -1);
        }
        // 移除用户敏感数据
        unset($user['mobile'], $user['email']);
        return DataReturn('success', 0, UserService::UserHandle($user));
    }
}
?>