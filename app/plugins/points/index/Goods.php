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
namespace app\plugins\points\index;

use app\plugins\points\index\Common;
use app\plugins\points\service\BaseService;
use app\plugins\points\service\PointsGoodsService;

/**
 * 积分商城 - 商品积分兑换管理
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-10
 * @desc    description
 */
class Goods extends Common
{
    /**
     * 积分兑换页面
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Index($params = [])
    {
        $data = empty($params['gid']) ? [] : BaseService::ExchangeIntegralGoodsData($params['gid']);
        MyViewAssign([
            'data'       => $data,
            'is_header'  => 0,
            'is_footer'  => 0,
        ]);
        return MyView('../../../plugins/points/view/index/goods/index');
    }

    /**
     * 保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-11-30
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Save($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return ViewError();
        }

        $params['user_type'] = 'shop';
        return PointsGoodsService::PointsGoodsSave($params);
    }
}
?>