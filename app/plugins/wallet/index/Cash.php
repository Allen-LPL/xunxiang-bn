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

use app\service\PluginsDataConfigService;
use app\service\SeoService;
use app\plugins\wallet\index\Common;
use app\plugins\wallet\service\BaseService;
use app\plugins\wallet\service\CashService;

/**
 * 钱包 - 余额提现
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Cash extends Common
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
        IsUserLogin();
    }

    /**
     * 余额提现
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-03-15T23:51:50+0800
     * @param   [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        // 浏览器名称
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('余额提现 - 我的钱包', 1));
        return MyView('../../../plugins/wallet/view/index/cash/index');
    }

    /**
     * 余额提现详情
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
        return MyView('../../../plugins/wallet/view/index/cash/detail');
    }

    /**
     * 余额提现 - 安全验证
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-03-15T23:51:50+0800
     * @param   [array]          $params [输入参数]
     */
    public function AuthInfo($params = [])
    {
        // 是否开启提现申请
        if(!isset($this->plugins_config['is_enable_cash']) || $this->plugins_config['is_enable_cash'] != 1)
        {
            MyViewAssign('msg', '暂时关闭了提现申请');
            return MyView('public/tips_error');
        }

        // 认证方式
        MyViewAssign('check_account_list', CashService::UserCheckAccountList($this->user));

        // 浏览器名称
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('余额提现 - 我的钱包', 1));
        return MyView('../../../plugins/wallet/view/index/cash/authinfo');
    }

    /**
     * 余额提现 - 提现信息填写页面
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-03-15T23:51:50+0800
     * @param   [array]          $params [输入参数]
     */
    public function CreateInfo($params = [])
    {
        // 是否开启提现申请
        if(!isset($this->plugins_config['is_enable_cash']) || $this->plugins_config['is_enable_cash'] != 1)
        {
            MyViewAssign('msg', '暂时关闭了提现申请');
            return MyView('public/tips_error');
        }

        // 安全校验
        $ret = CashService::CashAuthCheck(['user'=>$this->user, 'plugins_config'=>$this->plugins_config]);
        $check_status = ($ret['code'] == 0) ? $ret['data'] : 0;
        MyViewAssign('check_status', $check_status);

        // 安全校验通过
        $can_cash_max_money = 0;
        if($check_status == 1)
        {
            // 可提现最大金额
            $can_cash_max_money = CashService::CanCashMaxMoney($this->user_wallet, $this->plugins_config);
        }
        MyViewAssign('can_cash_max_money', $can_cash_max_money);

        // 当前用户是否已存在openid
        $user_weixin_openid_ret = BaseService::UserWeixinOpenidValue($this->user['id']);
        MyViewAssign('user_weixin_openid', $user_weixin_openid_ret['value']);

        // 默认提现信息
        MyViewAssign('default_data', CashService::CachDefaultData($this->user['id']));
        // 提现类型列表
        MyViewAssign('user_cash_type_list', BaseService::UserCashTypeList($this->plugins_config, ['user_weixin_openid'=>$user_weixin_openid_ret['value']]));
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('余额提现 - 我的钱包', 1));
        return MyView('../../../plugins/wallet/view/index/cash/createinfo');
    }

    /**
     * 余额提现 - 用户提现领取页面
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-03-15T23:51:50+0800
     * @param   [array]          $params [输入参数]
     */
    public function ReceiveInfo($params = [])
    {
        if(IsWeixinEnv())
        {
            $params['user'] = $this->user;
            $ret = CashService::CashReceiveData($params);
            if($ret['code'] == 0)
            {
                // 获取微信配置信息
                $data_config = PluginsDataConfigService::DataConfigData('wallet');
                if(!empty($data_config['weixin_appid']) && !empty($data_config['weixin_app_secret']))
                {
                    $obj = new \base\Wechat($data_config['weixin_appid'], $data_config['weixin_app_secret']);
                    $package = $obj->GetSignPackage();
                    MyViewAssign([
                        'data'     => $ret['data'],
                        'package'  => $package,
                    ]);
                } else {
                    MyViewAssign('error_msg', '未配置服务号appid或secret');
                }
            } else {
                MyViewAssign('error_msg', $ret['msg']);
            }
        } else {
            MyViewAssign('error_msg', '请在微信app中打开！');
        }
        MyViewAssign([
            'is_header'            => 0,
            'is_footer'            => 0,
            'home_seo_site_title'  => SeoService::BrowserSeoTitle('提现领取 - 我的钱包', 1),
        ]);
        return MyView('../../../plugins/wallet/view/index/cash/receiveinfo');
    }

    /**
     * 验证码显示
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-08
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function VerifyEntry($params = [])
    {
        $params = [
                'width'           => 100,
                'height'          => 28,
                'use_point_back'  => false,
                'key_prefix'      => 'wallet_cash',
                'expire_time'     => MyC('common_verify_expire_time'),
            ];
        $verify = new \base\Verify($params);
        $verify->Entry();
    }

    /**
     * 验证码发送
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-08
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function VerifySend($params = [])
    {
        $params['user'] = $this->user;
        $params['plugins_config'] = $this->plugins_config;
        return CashService::VerifySend($params);
    }

    /**
     * 验证码校验
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-08
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function VerifyCheck($params = [])
    {
        $params['user'] = $this->user;
        $params['plugins_config'] = $this->plugins_config;
        return CashService::VerifyCheck($params);
    }

    /**
     * 提现创建
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-08
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Create($params = [])
    {
        $params['user'] = $this->user;
        $params['plugins_config'] = $this->plugins_config;
        return CashService::CashCreate($params);
    }

    /**
     * 支付刷新
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-06
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function PayRefresh($params = [])
    {
        // 开始处理
        $params['user'] = $this->user;
        $params['plugins_config'] = $this->plugins_config;
        return CashService::PayRefresh($params);
    }
}
?>