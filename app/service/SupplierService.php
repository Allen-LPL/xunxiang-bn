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
namespace app\service;

use think\facade\Db;
use app\service\GoodsService;
use app\service\ResourcesService;

/**
 * 供货商服务层
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class SupplierService
{
    /**
     * 供货商列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-06T21:31:53+0800
     * @param    [array]          $params [输入参数]
     */
    public static function SupplierList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'sort,id asc' : trim($params['order_by']);
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;

        // 供货商列表读取前钩子
        $hook_name = 'plugins_service_supplier_list_begin';
        MyEventTrigger($hook_name, [
            'hook_name'     => $hook_name,
            'is_backend'    => true,
            'params'        => &$params,
            'where'         => &$where,
            'field'         => &$field,
            'order_by'      => &$order_by,
            'm'             => &$m,
            'n'             => &$n,
        ]);

        // 获取列表
        $data = Db::name('Supplier')->where($where)->field($field)->order($order_by)->limit($m, $n)->select()->toArray();
        return DataReturn(MyLang('handle_success'), 0, self::SupplierListHandle($data, $params));
    }

    /**
     * 数据处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-11
     * @desc    description
     * @param   [array]          $data      [列表数据]
     * @param   [array]          $params    [输入参数]
     */
    public static function SupplierListHandle($data, $params = [])
    {
        if(!empty($data))
        {
            // 字段列表
            $keys = ArrayKeys($data);

            // 数据处理
            foreach($data as $k=>&$v)
            {
                // 增加索引
                $v['data_index'] = $k+1;

                // url
                if(isset($v['id']))
                {
                    $v['url'] = (APPLICATION == 'web') ? MyUrl('index/search/index', ['supplier'=>$v['id']]) : '/pages/goods-search/goods-search?supplier='.$v['id'];
                }

                // logo
                /*if(isset($v['logo']))
                {
                    $v['logo'] = ResourcesService::AttachmentPathViewHandle($v['logo']);
                }*/

                // 供货商官方地址
                if(isset($v['name']))
                {
                    $v['name'] = empty($v['name']) ? null : $v['name'];
                }

                // 时间
                if(isset($v['add_time']))
                {
                    $v['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
                }
                if(isset($v['upd_time']))
                {
                    $v['upd_time'] = empty($v['upd_time']) ? '' : date('Y-m-d H:i:s', $v['upd_time']);
                }
            }
        }
        return $data;
    }

    /**
     * 供货商总数
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-10T22:16:29+0800
     * @param    [array]          $where [条件]
     */
    public static function SupplierTotal($where)
    {
        return (int) Db::name('Supplier')->where($where)->count();
    }

    /**
     * 获取所有供货商
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-10T22:16:29+0800
     * @param    [array]          $where [条件]
     */
    public static function CategorySupplier($params = [])
    {
        return Db::name('Supplier')->field('id,name')->where(['is_enable'=>1])->order('sort asc')->select()->toArray();
    }

    /**
     * 获取供货商名称
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-19
     * @desc    description
     * @param   [array|int]          $supplier_ids [供货商id]
     */
    public static function SupplierName($supplier_ids = 0)
    {
        if(empty($supplier_ids))
        {
            return null;
        }

        // 参数处理查询数据
        if(is_array($supplier_ids))
        {
            $supplier_ids = array_filter(array_unique($supplier_ids));
        }
        if(!empty($supplier_ids))
        {
            $data = Db::name('Supplier')->where(['id'=>$supplier_ids])->column('name', 'id');
        }

        // id数组则直接返回
        if(is_array($supplier_ids))
        {
            return empty($data) ? [] : $data;
        }
        return (!empty($data) && is_array($data) && array_key_exists($supplier_ids, $data)) ? $data[$supplier_ids] : null;
    }

    /**
     * 保存
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-18
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function SupplierSave($params = [])
    {
        // 请求类型
        $p = [
            [
                'checked_type'      => 'length',
                'key_name'          => 'name',
                'checked_data'      => '1,80',
                'error_msg'         => MyLang('common_service.supplier.form_item_name_message'),
            ],
            [
                'checked_type'      => 'unique',
                'key_name'          => 'name',
                'checked_data'      => 'Supplier',
                'checked_key'       => 'id',
                'error_msg'         => MyLang('common_service.supplier.save_name_already_exist_tips'),
            ],
            [
                'checked_type'      => 'unique',
                'key_name'          => 'contact',
                'checked_data'      => 'Supplier',
                'checked_key'       => 'id',
                'error_msg'         => MyLang('common_service.supplier.form_item_contact_message'),
            ],
            [
                'checked_type'      => 'unique',
                'key_name'          => 'mobile',
                'checked_data'      => 'Supplier',
                'checked_key'       => 'id',
                'error_msg'         => MyLang('common_service.supplier.form_item_mobile_message'),
            ],
            [
                'checked_type'      => 'unique',
                'key_name'          => 'openid',
                'checked_data'      => 'Supplier',
                'checked_key'       => 'id',
                'error_msg'         => MyLang('common_service.supplier.form_item_openid_message'),
            ],
            [
                'checked_type'      => 'max',
                'key_name'          => 'sort',
                'checked_data'      => 255,
                'is_checked'        => 1,
                'error_msg'         => MyLang('form_sort_message'),
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 附件
        /*$data_fields = ['logo'];
        $attachment = ResourcesService::AttachmentParams($params, $data_fields);*/

        // 数据
        $data = [
            'name'              => $params['name'],
            'contact'          => $params['contact'],
            //'logo'              => $attachment['data']['logo'],
            'mobile'            => empty($params['mobile']) ? '' : $params['mobile'],
            'openid'            => empty($params['openid']) ? '' : $params['openid'],
            'sort'              => intval($params['sort']),
            'is_enable'         => isset($params['is_enable']) ? intval($params['is_enable']) : 0,
        ];

        // 供货商保存处理钩子
        $hook_name = 'plugins_service_supplier_save_handle';
        $ret = EventReturnHandle(MyEventTrigger($hook_name, [
            'hook_name'     => $hook_name,
            'is_backend'    => true,
            'params'        => &$params,
            'data'          => &$data,
            'data_id'       => isset($params['id']) ? intval($params['id']) : 0,
        ]));
        if(isset($ret['code']) && $ret['code'] != 0)
        {
            return $ret;
        }

        // 启动事务
        Db::startTrans();

        // 捕获异常
        try {
            if(empty($params['id']))
            {
                $data['add_time'] = time();
                $supplier_id = Db::name('Supplier')->insertGetId($data);
                if($supplier_id <= 0)
                {
                    throw new \Exception(MyLang('insert_fail'));
                }
            } else {
                $data['upd_time'] = time();
                $supplier_id = intval($params['id']);
                if(Db::name('Supplier')->where(['id'=>$supplier_id])->update($data) === false)
                {
                    throw new \Exception(MyLang('edit_fail'));
                }
            }

            // 提交事务
            Db::commit();
            return DataReturn(MyLang('operate_success'), 0);
        } catch(\Exception $e) {
            Db::rollback();
            return DataReturn($e->getMessage(), -1);
        }
    }

    /**
     * 删除
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-18
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function SupplierDelete($params = [])
    {
        // 参数是否有误
        if(empty($params['ids']))
        {
            return DataReturn(MyLang('data_id_error_tips'), -1);
        }
        // 是否数组
        if(!is_array($params['ids']))
        {
            $params['ids'] = explode(',', $params['ids']);
        }

        // 删除操作
        if(Db::name('Supplier')->where(['id'=>$params['ids']])->delete())
        {
            return DataReturn(MyLang('delete_success'), 0);
        }
        return DataReturn(MyLang('delete_fail'), -100);
    }

    /**
     * 状态更新
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-06T21:31:53+0800
     * @param    [array]          $params [输入参数]
     */
    public static function SupplierStatusUpdate($params = [])
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

        // 数据更新
        if(Db::name('Supplier')->where(['id'=>intval($params['id'])])->update([$params['field']=>intval($params['state']), 'upd_time'=>time()]))
        {
            return DataReturn(MyLang('operate_success'), 0);
        }
        return DataReturn(MyLang('operate_fail'), -100);
    }

    /**
     * 指定读取供货商列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]         $params    [输入参数]
     */
    public static function AppointSupplierList($params = [])
    {
        $result = [];
        if(!empty($params['supplier_ids']))
        {
            // 非数组则转为数组
            if(!is_array($params['supplier_ids']))
            {
                $params['supplier_ids'] = explode(',', $params['supplier_ids']);
            }

            // 基础条件
            $params['where'] = [
                ['is_enable', '=', 1],
                ['id', 'in', array_unique($params['supplier_ids'])]
            ];
            $params['m'] = 0;
            $params['n'] = 0;
            $params['field'] = '*';

            // 获取数据
            $params['is_appoint_supplier_list'] = 1;
            $ret = self::SupplierList($params);
            if(!empty($ret['data']))
            {
                $temp = array_column($ret['data'], null, 'id');
                foreach($params['supplier_ids'] as $id)
                {
                    if(!empty($id) && array_key_exists($id, $temp))
                    {
                        $result[] = $temp[$id];
                    }
                }
            }
        }
        return $result;
    }

    /**
     * 自动读取供货商列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]         $params [输入参数]
     */
    public static function AutoSupplierList($params = [])
    {
        // 基础条件
        $params['where'] = [
            ['is_enable', '=', 1],
        ];

        // 供货商关键字
        if(!empty($params['supplier_keywords']))
        {
            $params['where'][] = ['name|contact|mobile|openid', 'like', '%'.$params['supplier_keywords'].'%'];
        }

        // 排序
        $order_by_type_list = MyConst('common_supplier_order_by_type_list');
        $order_by_rule_list = MyConst('common_data_order_by_rule_list');
        $order_by_type = !isset($params['supplier_order_by_type']) || !array_key_exists($params['supplier_order_by_type'], $order_by_type_list) ? $order_by_type_list[0]['value'] : $order_by_type_list[$params['supplier_order_by_type']]['value'];
        $order_by_rule = !isset($params['supplier_order_by_rule']) || !array_key_exists($params['supplier_order_by_rule'], $order_by_rule_list) ? $order_by_rule_list[0]['value'] : $order_by_rule_list[$params['supplier_order_by_rule']]['value'];
        $params['order_by'] = $order_by_type.' '.$order_by_rule;
        $params['m'] = 0;
        $params['n'] = empty($params['supplier_number']) ? 10 : intval($params['supplier_number']);
        $params['field'] = '*';

        // 获取数据
        $params['is_auto_supplier_list'] = 1;
        $ret = self::SupplierList($params);
        return empty($ret['data']) ? [] : $ret['data'];
    }
}
?>