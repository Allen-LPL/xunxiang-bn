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
namespace app\service\wx;

use think\facade\Cache;

/**
 * 微信小程序订阅消息服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-07-11
 * @desc    description
 */
class WxSubscribeMessageService
{
    // 小程序配置，放config/app.php或.env
    private static $appId = 'wx9358405f8626c144';
    private static $appSecret = '8a461fd1f25160a09bf8df1e8036e28f';

    // 1. 获取access_token，缓存2小时
    public static function getAccessToken()
    {
        $cacheKey = 'wxmini_access_token';
        $token = Cache::get($cacheKey);
        if ($token) return $token;

        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".self::$appId."&secret=".self::$appSecret;
        $res = file_get_contents($url);
        $data = json_decode($res,true);print_r($data);exit;
        if(isset($data['access_token'])){
            Cache::set($cacheKey, $data['access_token'], 7000);
            return $data['access_token'];
        }

        return false;
    }

    // 2. 发送订阅消息（订单提醒核心方法）
    /**
     * @param string $openid 用户openid
     * @param string $templateId 订阅模板ID
     * @param array $data 模板填充数据
     * @param string $page 点击消息跳转小程序页面
     * @return array
     */
    public static function sendSubscribeMsg(string $openid, string $templateId, array $data, string $page = '/pages/order/order')
    {
        $accessToken = self::getAccessToken();
        if(!$accessToken)
            return $accessToken;

        $url = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token={$accessToken}";
        $postData = [
            'touser' => $openid,
            'template_id' => $templateId,
            'page' => $page,
            'data' => $data
        ];
        $res = Http::postJson($url, $postData);
        return json_decode($res,true);
    }
}
?>