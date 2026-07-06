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
namespace app\plugins\giftcard\api;

use app\plugins\giftcard\api\Common;
use app\plugins\giftcard\service\CardSecretService;

/**
 * 礼品卡 - 兑换
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-10
 * @desc    description
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
        return DataReturn('success', 0, FormModuleData($params));
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
        $result = FormModuleData($params);
        if(empty($result) || empty($result['data']))
        {
            return DataReturn(MyLang('no_data'), -1);
        }
        return DataReturn('success', 0, $result);
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

    /**
     * 已兑换未领取的实物商品清单
     * @author  Devil
     * @date    2026-07-01
     * @param   [array]          $params [输入参数]
     */
    public function ExchangeGoods($params = [])
    {
        $data = CardSecretService::UserExchangeGoodsList($this->user['id']);
        return DataReturn('success', 0, $data);
    }

    /**
     * 兑换记录列表（近期，支持分页 page/limit）
     * @author  Devil
     * @date    2026-07-01
     * @param   [array]          $params [输入参数]
     */
    public function ExchangeRecord($params = [])
    {
        $page = empty($params['page']) ? 1 : max(1, intval($params['page']));
        $limit = empty($params['limit']) ? 10 : min(50, max(1, intval($params['limit'])));
        $data = CardSecretService::UserExchangeRecordList($this->user['id'], $page, $limit);
        return DataReturn('success', 0, $data);
    }


}
?>