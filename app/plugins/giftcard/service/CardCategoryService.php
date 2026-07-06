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

/**
 * 礼品卡 - 礼品卡分类服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-03-31
 * @desc    description
 */
class CardCategoryService
{
    /**
     * 分类列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-01
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function CardCategoryAll($params = [])
    {
        // 条件参数
        $where = [
            'is_enable' => isset($params['is_enable']) ? intval($params['is_enable']) : 1,
        ];

        // 获取数据
        $field = empty($params['field']) ? 'id,pid,icon,name,sort,is_enable' : $params['field'];
        return self::DataHandle(Db::name('PluginsGiftcardCardCategory')->field($field)->where($where)->order('sort asc, id asc')->select()->toArray());
    }

    /**
     * 获取分类节点数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-31
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function CardCategoryNodeSon($params = [])
    {
        // 条件参数
        $where = [
            'pid'   => isset($params['id']) ? intval($params['id']) : 0,
        ];

        // 获取数据
        $field = 'id,pid,icon,name,sort,is_enable';
        $data = Db::name('PluginsGiftcardCardCategory')->field($field)->where($where)->order('sort asc, id asc')->select()->toArray();
        if(!empty($data))
        {
            $data = self::DataHandle($data);
            foreach($data as &$v)
            {
                $v['is_son']    = (Db::name('PluginsGiftcardCardCategory')->where(['pid'=>$v['id']])->count() > 0) ? 'ok' : 'no';
                $v['json']      = json_encode($v);
            }
            return DataReturn(MyLang('operate_success'), 0, $data);
        }
        return DataReturn(MyLang('no_data'), -100);
    }

    /**
     * 数据处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-31
     * @desc    description
     * @param   [array]          $data [二维数组]
     */
    public static function DataHandle($data)
    {
        if(!empty($data) && is_array($data))
        {
            foreach($data as &$v)
            {
                if(is_array($v))
                {
                    if(array_key_exists('icon', $v))
                    {
                        $v['icon'] = ResourcesService::AttachmentPathViewHandle($v['icon']);
                    }
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
     * @date    2021-03-31
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function CardCategorySave($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'length',
                'key_name'          => 'name',
                'checked_data'      => '1,16',
                'error_msg'         => MyLang('cardcategory.form_item_name_message'),
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 其它附件
        $data_fields = ['icon'];
        $attachment = ResourcesService::AttachmentParams($params, $data_fields);

        // 数据
        $data = [
            'name'          => $params['name'],
            'pid'           => isset($params['pid']) ? intval($params['pid']) : 0,
            'sort'          => isset($params['sort']) ? intval($params['sort']) : 0,
            'is_enable'     => isset($params['is_enable']) ? intval($params['is_enable']) : 0,
            'icon'          => $attachment['data']['icon'],
        ];

        // 父级id宇当前id不能相同
        if(!empty($params['id']) && $params['id'] == $data['pid'])
        {
            return DataReturn(MyLang('cardcategory.category_pid_current_equal_tips'), -10);
        }

        // 添加/编辑
        if(empty($params['id']))
        {
            $data['add_time'] = time();
            $data['id'] = Db::name('PluginsGiftcardCardCategory')->insertGetId($data);
            if($data['id'] <= 0)
            {
                return DataReturn(MyLang('insert_fail'), -100);
            }
        } else {
            $data['upd_time'] = time();
            if(Db::name('PluginsGiftcardCardCategory')->where(['id'=>intval($params['id'])])->update($data) === false)
            {
                return DataReturn(MyLang('edit_fail'), -100);
            } else {
                $data['id'] = $params['id'];
            }
        }

        $res = self::DataHandle([$data]);
        return DataReturn(MyLang('operate_success'), 0, $res[0]);
    }

    /**
     * 状态更新
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-06T21:31:53+0800
     * @param    [array]          $params [输入参数]
     */
    public static function CardCategoryStatusUpdate($params = [])
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
        $where = [
            ['id', '=', intval($params['id'])],
        ];
        if(Db::name('PluginsGiftcardCardCategory')->where($where)->update([$params['field']=>intval($params['state']), 'upd_time'=>time()]))
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
     * @date    2021-03-31
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function CardCategoryDelete($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => MyLang('data_id_error_tips'),
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 获取分类下所有分类id
        $ids = self::CardCategoryItemsIds([$params['id']]);
        if(Db::name('PluginsGiftcardCardCategory')->where(['id'=>$ids])->delete())
        {
            return DataReturn(MyLang('delete_success'), 0);
        }
        return DataReturn(MyLang('delete_fail'), -100);
    }

    /**
     * 获取分类下的所有分类id
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-31
     * @desc    description
     * @param   [array]          $ids       [分类id数组]
     * @param   [int]            $is_enable [是否启用 null, 0否, 1是]
     * @param   [string]         $order_by  [排序, 默认sort asc]
     */
    public static function CardCategoryItemsIds($ids = [], $is_enable = null, $order_by = 'sort asc')
    {
        if(!is_array($ids))
        {
            $ids = explode(',', $ids);
        }
        $where = ['pid'=>$ids];
        if($is_enable !== null)
        {
            $where['is_enable'] = $is_enable;
        }
        $data = Db::name('PluginsGiftcardCardCategory')->where($where)->order($order_by)->column('id');
        if(!empty($data))
        {
            $temp = self::CardCategoryItemsIds($data, $is_enable, $order_by);
            if(!empty($temp))
            {
                $data = array_merge($data, $temp);
            }
        }
        $data = empty($data) ? $ids : array_unique(array_merge($ids, $data));
        return $data;
    }
}
?>