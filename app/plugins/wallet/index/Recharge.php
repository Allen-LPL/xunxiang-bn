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
namespace app\plugins\wallet\index;

use app\service\SeoService;
use app\plugins\wallet\index\Common;
use app\plugins\wallet\service\PayService;
use app\plugins\wallet\service\RechargeService;

/**
 * 钱包 - 充值
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Recharge extends Common
{
    /**
     * 构造方法
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-07-17
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function __construct($params = [])
    {
        parent::__construct($params);

        // 是否已经登录
        if(!isset($this->plugins_action_name) || $this->plugins_action_name != 'respond')
        {
            IsUserLogin();
        }
    }

    /**
     * 充值明细
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-03-15T23:51:50+0800
     * @param   [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        // 浏览器名称
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('充值明细 - 我的钱包', 1));
        return MyView('../../../plugins/wallet/view/index/recharge/index');
    }

    /**
     * 充值明细详情
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-03-15T23:51:50+0800
     * @param   [array]          $params [输入参数]
     */
    public function Detail($params = [])
    {
        MyViewAssign('is_header', 0);
        MyViewAssign('is_footer', 0);
        return MyView('../../../plugins/wallet/view/index/recharge/detail');
    }

    /**
     * 充值订单创建
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-03-15T23:51:50+0800
     * @param   [array]          $params [输入参数]
     */
    public function Create($params = [])
    {
        // 是否开启充值
        if(isset($this->plugins_config['is_enable_recharge']) && $this->plugins_config['is_enable_recharge'] == 0)
        {
            return DataReturn('暂时关闭了在线充值', -1);
        }

        // 用户
        $params['user'] = $this->user;
        $params['plugins_config'] = $this->plugins_config;
        $params['user_wallet'] = $this->user_wallet;
        $params['operate_id'] = $this->user['id'];
        $params['operate_name'] = $this->user['user_name_view'];
        return RechargeService::RechargeCreate($params);
    }

    /**
     * 支付
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-03-15T23:51:50+0800
     * @param   [array]          $params [输入参数]
     */
    public function Pay($params = [])
    {
        $params['user'] = $this->user;
        $params['plugins_config'] = $this->plugins_config;
        $params['operate_id'] = $this->user['id'];
        $params['operate_name'] = $this->user['user_name_view'];
        $ret = PayService::Pay($params);
        if($ret['code'] == 0)
        {
            // 是否直接成功、则直接进入提示页面并指定支付状态
            if(isset($ret['data']['is_success']) && $ret['data']['is_success'] == 1)
            {
                return MyRedirect(PluginsHomeUrl('wallet', 'recharge', 'respond', ['appoint_status'=>0]));
            }
            return MyRedirect($ret['data']['data']);
        }
        return MyView('public/tips_error', ['msg'=>$ret['msg']]);
    }

    /**
     * 支付状态校验
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-03-15T23:51:50+0800
     * @param   [array]          $params [输入参数]
     */
    public function PayCheck($params = [])
    {
        if(input('post.'))
        {
            $params['user'] = $this->user;
            $params['plugins_config'] = $this->plugins_config;
            return PayService::RechargePayCheck($params);
        }
        return MyView('public/tips_error', ['msg'=>MyLang('illegal_access_tips')]);
    }

    /**
     * 支付同步页面
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-29
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Respond($params = [])
    {
        // 是否自定义状态
        if(isset($params['appoint_status']))
        {
            $ret = ($params['appoint_status'] == 0) ? DataReturn(MyLang('pay_success'), 0) : DataReturn(MyLang('pay_fail'), -100);
        } else {
            // 获取支付回调数据
            $params['user'] = $this->user;
            $ret = PayService::Respond($params);
        }

        // 自定义链接
        MyViewAssign([
            'to_url'    => PluginsHomeUrl('wallet', 'recharge', 'index'),
            'to_title'  => '充值明细',
            'msg'       => $ret['msg'],
        ]);

        // 状态
        if($ret['code'] == 0)
        {
            return MyView('public/tips_success');
        }
        return MyView('public/tips_error');
    }

    /**
     * 充值纪录删除
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-14
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Delete($params = [])
    {
        $params['user'] = $this->user;
        $params['plugins_config'] = $this->plugins_config;
        return RechargeService::RechargeDelete($params);
    }
}
?>