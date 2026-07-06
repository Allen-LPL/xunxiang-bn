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
namespace app\plugins\giftcard\admin;

use app\plugins\giftcard\admin\Common;
use app\plugins\giftcard\service\BaseService;
use app\plugins\giftcard\service\CardService;
use app\plugins\giftcard\service\CardCategoryService;

/**
 * 礼品卡 - 礼品卡
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Card extends Common
{
    /**
     * 列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        return MyView('../../../plugins/giftcard/view/admin/card/index');
    }

    /**
     * 详情
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-03-15T23:51:50+0800
     * @param   [array]          $params [输入参数]
     */
    public function Detail($params = [])
    {
        return MyView('../../../plugins/giftcard/view/admin/card/detail');
    }

    /**
     * 添加/编辑页面
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function SaveInfo($params = [])
    {
        // 礼品卡分类
        MyViewAssign('category_list', CardCategoryService::CardCategoryAll());

        // 优惠券列表
        MyViewAssign('coupon_list', BaseService::CouponList());

        // 静态数据
        MyViewAssign('card_data_type_list', BaseService::ConstData('card_data_type_list'));
        MyViewAssign('card_generate_type_list', BaseService::ConstData('card_generate_type_list'));
        return MyView('../../../plugins/giftcard/view/admin/card/saveinfo');
    }

    /**
     * 下载页面
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function DownloadInfo($params = [])
    {
        return MyView('../../../plugins/giftcard/view/admin/card/downloadinfo');
    }

    /**
     * 下载二维码
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Download($params = [])
    {
        $ret = CardService::CardDownload($params);
        if(isset($ret['code']) && $ret['code'] != 0)
        {
            MyViewAssign('msg', $ret['msg']);
            return MyView('public/tips_error');
        }
    }

    /**
     * 保存
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-06
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Save($params = [])
    {
        $params['plugins_config'] = $this->plugins_config;
        return CardService::CardSave($params);
    }

    /**
     * 删除
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Delete($params = [])
    {
        $params['plugins_config'] = $this->plugins_config;
        return CardService::CardDelete($params);
    }

    /**
     * 状态更新
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function StatusUpdate($params = [])
    {
        $params['plugins_config'] = $this->plugins_config;
        return CardService::CardStatusUpdate($params);
    }

    /**
     * 二维码数量生成
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Generate($params = [])
    {
        $params['plugins_config'] = $this->plugins_config;
        return CardService::CardGenerate($params);
    }
}
?>