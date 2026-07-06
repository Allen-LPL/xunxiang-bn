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
use app\service\ResourcesService;
use app\plugins\giftcard\service\BaseService;
use app\plugins\giftcard\service\CardSecretService;

/**
 * 礼品卡 - 礼品卡服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class CardService
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
            $coupon = array_column(BaseService::CouponList(), null, 'id');
            foreach($data as &$v)
            {
                // 批次数据
                if(array_key_exists('batch_data', $v))
                {
                    $v['batch_data'] = empty($v['batch_data']) ? [] : json_decode($v['batch_data'], true);
                }

                // 优惠券
                if(isset($v['data_type']) && $v['data_type'] == 1)
                {
                    $v['secret_value_text'] = (empty($coupon) || !isset($coupon[$v['secret_value']])) ? '' : $coupon[$v['secret_value']]['name'];
                } else {
                    $v['secret_value_text'] = $v['secret_value'];
                }
            }
        }
        return $data;
    }

    /**
     * 保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-08-30
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function CardSave($params = [])
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
                'key_name'          => 'name',
                'error_msg'         => '请填写名称',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 获取信息
        $info = empty($params['id']) ? null : Db::name('PluginsGiftcardCard')->where(['id'=>intval($params['id'])])->find();
        // 数据为空或者没有卡密数量则需要验证数据
        if(empty($info) || empty($info['card_count']))
        {
            // 参数校验
            $p = [
                [
                    'checked_type'      => 'empty',
                    'key_name'          => 'category_id',
                    'error_msg'         => '请选择礼品卡分类',
                ],
                [
                    'checked_type'      => 'in',
                    'key_name'          => 'data_type',
                    'error_msg'         => '卡密类型范围值有误',
                    'checked_data'      => array_column(BaseService::ConstData('card_data_type_list'), 'value'),
                ],
                [
                    'checked_type'      => 'in',
                    'key_name'          => 'generate_type',
                    'error_msg'         => '生成方式范围值有误',
                    'checked_data'      => array_column(BaseService::ConstData('card_generate_type_list'), 'value'),
                ],
                [
                    'checked_type'      => 'length',
                    'key_name'          => 'prefix',
                    'error_msg'         => '礼品卡前缀最多60个字符',
                    'checked_data'      => 60,
                ],
            ];
            // 根据数据类型验证卡密值
            $value_field = '';
            if(isset($params['data_type']))
            {
                switch($params['data_type'])
                {
                    // 钱包
                    case 0 :
                        $value_field = 'money';
                        $p[] = [
                            'checked_type'      => 'empty',
                            'key_name'          => $value_field,
                            'error_msg'         => '请填写充值金额',
                        ];
                        break;

                    // 优惠券
                    case 1 :
                        $value_field = 'coupon_id';
                        $p[] = [
                            'checked_type'      => 'empty',
                            'key_name'          => $value_field,
                            'error_msg'         => '请选择优惠券',
                        ];
                        break;

                    // 积分
                    case 2 :
                        $value_field = 'points';
                        $p[] = [
                            'checked_type'      => 'empty',
                            'key_name'          => $value_field,
                            'error_msg'         => '请填写可兑换积分',
                        ];
                        break;

                    // 商品
                    case 3 :
                        $value_field = 'goods';
                        $p[] = [
                            'checked_type'      => 'empty',
                            'key_name'          => $value_field,
                            'error_msg'         => '请填写可兑换商品id',
                        ];
                        break;
                }
            }
            if(empty($value_field))
            {
                return DataReturn('卡密类型有误', -1);
            }
            $ret = ParamsChecked($params, $p);
            if($ret !== true)
            {
                return DataReturn($ret, -1);
            }
        }

        // 数据
        $data = [
            'name'       => $params['name'],
            'is_enable'  => isset($params['is_enable']) && $params['is_enable'] == 1 ? 1 : 0,
            'note'       => empty($params['note']) ? '' : $params['note'],
        ];

        // 数据为空或者没有卡密数量则可以编辑
        if(empty($info) || empty($info['card_count']))
        {
            $data['category_id']    = intval($params['category_id']);
            $data['data_type']      = intval($params['data_type']);
            $data['generate_type']  = intval($params['generate_type']);
            $data['prefix']         = empty($params['prefix']) ? '' : strtoupper($params['prefix']);
            $data['secret_value']   = isset($params[$value_field]) ? $params[$value_field] : '';
        }
        try {
            if(empty($info))
            {
                $data['add_time'] = time();
                $card_id = Db::name('PluginsGiftcardCard')->insertGetId($data);
                if($card_id <= 0)
                {
                    throw new \Exception(MyLang('insert_fail'));
                }
            } else {
                $data['upd_time'] = time();
                if(!Db::name('PluginsGiftcardCard')->where(['id'=>$info['id']])->update($data))
                {
                    throw new \Exception(MyLang('update_fail'));
                }
            }
            return DataReturn(MyLang('operate_success'), 0);
        } catch(\Exception $e) {
            return DataReturn($e->getMessage(), -1);
        }
    }

    /**
     * 状态更新
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-08
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function CardStatusUpdate($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => MyLang('data_id_error_tips'),
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'field',
                'error_msg'         => MyLang('operate_field_error_tips'),
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'state',
                'checked_data'      => [0,1],
                'error_msg'         => MyLang('form_status_range_message'),
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 状态更新
        if(Db::name('PluginsGiftcardCard')->where(['id'=>intval($params['id'])])->update([$params['field']=>intval($params['state'])]))
        {
           return DataReturn(MyLang('operate_success'));
        }
        return DataReturn(MyLang('operate_fail'), -100);
    }

    /**
     * 删除
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-08
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function CardDelete($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'ids',
                'error_msg'         => MyLang('data_id_error_tips'),
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }
        // 是否数组
        if(!is_array($params['ids']))
        {
            $params['ids'] = explode(',', $params['ids']);
        }

        // 捕获异常
        Db::startTrans();
        try {
            // 礼品卡数据
            if(!Db::name('PluginsGiftcardCard')->where(['id'=>$params['ids']])->delete())
            {
                throw new \Exception(MyLang('delete_fail'));
            }
            // 卡密数据
            if(Db::name('PluginsGiftcardCardSecret')->where(['card_id'=>$params['ids']])->delete() === false)
            {
                throw new \Exception(MyLang('delete_fail'));
            }

            // 删除图片
            foreach($params['ids'] as $id)
            {
                \base\FileUtil::UnlinkDir(ROOT.'public'.DS.'download'.DS.'plugins_giftcard'.DS.'qrcode_card_'.$id);
            }
            
            Db::commit();
            return DataReturn(MyLang('delete_success'), 0);
        } catch(\Exception $e) {
            Db::rollback();
            return DataReturn($e->getMessage(), -1);
        }
    }

    /**
     * 卡密数量生成
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-08
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function CardGenerate($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'card_id',
                'error_msg'         => '礼品卡数据id为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'number',
                'error_msg'         => '请填写生成数量',
            ],
            [
                'checked_type'      => 'max',
                'key_name'          => 'number',
                'checked_data'      => 10000,
                'error_msg'         => '生成数量最大10000数值',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 礼品卡数据
        $card = Db::name('PluginsGiftcardCard')->where(['id'=>intval($params['card_id']), 'is_enable'=>1])->find();
        if(empty($card))
        {
            return DataReturn('没有相关礼品卡数据', -1);
        }
        $batch_data = empty($card['batch_data']) ? [] : json_decode($card['batch_data'], true);

        // 批次数据
        $batch_id = date('YmdHis').$card['id'].GetNumberCode(6);
        $batch_name = '(第'.(count($batch_data)+1).'批'.$params['number'].'个)'.date('Y-m-d H:i:s');

        // 卡密写入数据
        $data = [
            'card_id'       => $card['id'],
            'batch_id'      => $batch_id,
            'data_type'     => $card['data_type'],
            'secret_value'  => $card['secret_value'],
            'use_data'      => '',
            'add_time'      => time(),
        ];

        // 卡密最大字符串长度
        $max = 16;
        if(!empty($card['prefix']))
        {
            $max -= strlen($card['prefix']);
        }

        // 捕获异常
        Db::startTrans();
        try {
            // 循环生成
            for($i=0; $i<$params['number']; $i++)
            {
                // 添加数据
                unset($data['id']);
                $data['id'] = Db::name('PluginsGiftcardCardSecret')->insertGetId($data);
                if(empty($data['id']))
                {
                    throw new \Exception('卡密数据添加失败');
                }

                // 礼品卡增加卡密总数
                if(!Db::name('PluginsGiftcardCard')->where(['id'=>$card['id']])->inc('card_count')->update())
                {
                    throw new \Exception('礼品卡密总数增加失败');
                }

                // 卡密生成
                if($card['generate_type'] == 1)
                {
                    $string = $card['id'].RandomString(3).$data['id'];
                    $len = strlen($string);
                    if($len < $max)
                    {
                        $temp_len = ($max-$len)/2;
                        $string = $card['prefix'].RandomString(ceil($temp_len)).$string.RandomString(floor($temp_len));
                    }
                } else {
                    $string = $card['id'].GetNumberCode(3).$data['id'];
                    $len = strlen($string);
                    if($len < $max)
                    {
                        $temp_len = ($max-$len)/2;
                        $string = $card['prefix'].GetNumberCode(ceil($temp_len)).$string.GetNumberCode(floor($temp_len));
                    }
                }
                $data['secret_key'] = implode('-', str_split(strtoupper($string), 4));
                if(!Db::name('PluginsGiftcardCardSecret')->where(['id'=>$data['id']])->update(['secret_key'=>$data['secret_key']]))
                {
                    throw new \Exception('卡密标识更新失败');
                }

                // 生成二维码
                $ret = CardSecretService::CardSecretGenerateHandle($data);
                if($ret['code'] != 0)
                {
                    throw new \Exception($ret['msg']);
                }
            }

            // 批次数据更新
            $batch_data[] = [
                'batch_id'    => $batch_id,
                'batch_name'  => $batch_name,
            ];
            if(!Db::name('PluginsGiftcardCard')->where(['id'=>$card['id']])->update(['batch_data'=>json_encode($batch_data, JSON_UNESCAPED_UNICODE), 'upd_time'=>time()]))
            {
                throw new \Exception('礼品卡批次数据更新失败');
            }

            Db::commit();
            return DataReturn(MyLang('created_success'), 0);
        } catch(\Exception $e) {
            Db::rollback();
            return DataReturn($e->getMessage(), -1);
        }
    }

    /**
     * 卡密下载
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-08
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function CardDownload($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'card_id',
                'error_msg'         => '礼品卡数据id为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'batch_id',
                'error_msg'         => '批次数据id为空',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 礼品卡数据
        $card = Db::name('PluginsGiftcardCard')->where(['id'=>intval($params['card_id'])])->find();
        if(empty($card))
        {
            return DataReturn('没有相关礼品卡数据', -1);
        }
        // 批次数据
        $batch_data = empty($card['batch_data']) ? [] : array_column(json_decode($card['batch_data'], true), 'batch_name', 'batch_id');
        if(!array_key_exists($params['batch_id'], $batch_data))
        {
            return DataReturn('没有相关批次数据', -1);
        }

        // 卡密
        $card_secret = Db::name('PluginsGiftcardCardSecret')->where(['card_id'=>$card['id'], 'batch_id'=>$params['batch_id']])->field('secret_key')->select()->toArray();
        if(empty($card_secret))
        {
            return DataReturn('没有相关批次卡密数据', -1);
        }

        // Excel驱动导出数据
        $title = [
            'secret_key'   =>  [
                'name' => '卡密',
                'type' => 'string',
            ],
        ];
        $excel = new \base\Excel(array('filename'=>$params['batch_id'], 'title'=>$title, 'data'=>$card_secret, 'msg'=>'没有相关数据'));
        return $excel->Export();
    }
}
?>