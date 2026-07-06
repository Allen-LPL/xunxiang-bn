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

/**
 * 积分商城 - 商品服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class PointsGoodsService
{
    /**
     * 商品积分保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-11-03
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function PointsGoodsSave($params = [])
    {
        // 参数校验
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'goods_id',
                'error_msg'         => MyLang('goods_id_error_tips'),
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 更新条件
        $where = ['id'=>intval($params['goods_id'])];

        // 用户类型
        $user_type = empty($params['user_type']) ? 'user' : $params['user_type'];
        if($user_type == 'shop')
        {
            $where['shop_id'] = BaseService::ShopID();
        }

        // 数据更新
        $data = [
            'plugins_points_exchange_integral'  => empty($params['plugins_points_exchange_integral']) ? 0 : intval($params['plugins_points_exchange_integral']),
            'plugins_points_exchange_price'     => empty($params['plugins_points_exchange_price']) ? 0 : floatval($params['plugins_points_exchange_price']),
            'upd_time'                          => time(),
        ];
        if(Db::name('Goods')->where($where)->update($data))
        {
            return DataReturn(MyLang('operate_success'), 0);
        }
        return DataReturn(MyLang('update_fail'), -1);
    }
}
?>