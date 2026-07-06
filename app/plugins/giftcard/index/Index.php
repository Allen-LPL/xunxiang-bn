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
namespace app\plugins\giftcard\index;

use app\service\SeoService;
use app\plugins\giftcard\index\Common;
use app\plugins\giftcard\service\CardSecretService;

/**
 * 礼品卡 - 兑换
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Index extends Common
{
    /**
     * 构造方法
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-11-30
     * @desc    description
     */
    public function __construct($params = [])
    {
        parent::__construct($params);

        // 是否已经登录
        IsUserLogin();
    }

    /**
     * 兑换页面
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('我的礼品卡', 1));
        return MyView('../../../plugins/giftcard/view/index/index/index');
    }

    /**
     * 详情
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-03-15T23:51:50+0800
     * @param    [array]          $params [输入参数]
     */
    public function Detail($params = [])
    {
        MyViewAssign([
            'is_header' => 0,
            'is_footer' => 0,
        ]);
        return MyView('../../../plugins/giftcard/view/index/index/detail');
    }

    /**
     * 兑换页面
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function ExchangeInfo($params = [])
    {
        // 默认不加载视频扫码组件
        MyViewAssign('is_load_video_scan', 1);
        MyViewAssign('is_header', 0);
        MyViewAssign('is_footer', 0);
        return MyView('../../../plugins/giftcard/view/index/index/exchangeinfo');
    }

    /**
     * 兑换
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-06
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Exchange($params = [])
    {
        $params['user'] = $this->user;
        $params['plugins_config'] = $this->plugins_config;
        return CardSecretService::CardSecretExchange($params);
    }
}
?>