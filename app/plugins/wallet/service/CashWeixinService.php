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
namespace app\plugins\wallet\service;

use think\facade\Db;
use app\service\AppMiniUserService;
use app\service\PluginsDataConfigService;
use app\plugins\wallet\service\BaseService;
use app\plugins\wallet\service\CashPaymentService;

/**
 * 钱包 - 钱包余额提现到微信服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class CashWeixinService
{
    // 配置信息
    public static $config;

    /**
     * 初始化
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2025-07-05
     * @desc    description
     */
    public static function Init()
    {
        // 数据配置
        self::$config = PluginsDataConfigService::DataConfigData('wallet');

        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'weixin_merchant_id',
                'error_msg'         => '商户号未配置',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'weixin_api_key_v3',
                'error_msg'         => 'api安全密钥v3未配置',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'weixin_transfer_scene_id',
                'error_msg'         => '转账场景未配置',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'weixin_serial_no',
                'error_msg'         => '商户证书序列号未配置',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'weixin_private_key',
                'error_msg'         => '商户私钥证书未配置',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'weixin_out_pub_id',
                'error_msg'         => '微信平台支付公钥ID未配置',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'weixin_out_pub_key',
                'error_msg'         => '微信平台支付公钥证书未配置',
            ],
        ];
        $ret = ParamsChecked(self::$config, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }
        return DataReturn('success', 0);
    }

    /**
     * 微信转账创建
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-09-19
     * @desc    description
     * @param   [array]          $cash   [提现数据]
     * @param   [array]          $params [输入参数]
     */
    public static function TransferCreate($cash, $params = [])
    {
        // 初始化
        $ret = self::Init();
        if($ret['code'] != 0)
        {
            return $ret;
        }
        if(empty($cash['bank_accounts']))
        {
            return DataReturn('微信openid为空', -1);
        }

        // appid
        $appid = ($cash['accounts_other_type'] == 'web') ? (isset(self::$config['weixin_appid']) ? self::$config['weixin_appid'] : '') : AppMiniUserService::AppMiniConfig('common_app_mini_weixin_appid');
        if(empty($appid))
        {
            return DataReturn('配置appid为空', -1);
        }

        // 转账数据
        $pay_no = $cash['cash_no'].GetNumberCode(6);
        $data = [
            'appid'                        => $appid,
            'out_bill_no'                  => $pay_no,
            'transfer_scene_id'            => self::$config['weixin_transfer_scene_id'],
            'openid'                       => $cash['bank_accounts'],
            'user_name'                    => ($cash['money'] < 0.3) ? '' : self::ContentEncrypt($cash['bank_username']),
            'transfer_amount'              => (int) (($cash['money']*1000)/10),
            'transfer_remark'              => '用户提现',
            'transfer_scene_report_infos'  => self::TransferSceneReportInfos(self::$config['weixin_transfer_scene_id']),
        ];

        // 先释放原来为处理的数据
        CashPaymentService::CashPaymentRelease($cash);

        // 转账数据添加
        $insert = CashPaymentService::CashPaymentInsert($cash, $pay_no, $data, $params);
        if($insert['code'] != 0)
        {
            return $insert;
        }
        $start_time = time();

        // 请求接口
        $ret = self::WeixinRequest('/v3/fund-app/mch-transfer/transfer-bills', $data, 'POST', false);

        // 回调处理
        $status = 3;
        $reason = '异常错误';
        $out_order_no = '';
        if($ret['code'] == 0)
        {
            $response = json_decode($ret['data'], true);
            // ACCEPTED 已受理, PROCESSING 转账中, SUCCESS 已完成, FAIL 失败
            if(isset($response['state']) && in_array($response['state'], ['ACCEPTED', 'PROCESSING', 'SUCCESS']))
            {
                $status = 2;
                $reason = '';

            // 待用户收款
            // WAIT_USER_CONFIRM: 待收款用户确认，可拉起微信收款确认页面进行收款确认
            // TRANSFERING: 转账中，可拉起微信收款确认页面再次重试确认收款
            } else if(isset($response['state']) && in_array($response['state'], ['WAIT_USER_CONFIRM', 'TRANSFERING']))
            {
                $status = 1;
                $reason = '';
            } else {
                $reason = empty($response['message']) ? $ret['data'] : $response['message'];
                if(!empty($response['code']))
                {
                    $reason .= '('.$response['code'].')';
                }
            }
            $out_order_no = isset($response['transfer_bill_no']) ? $response['transfer_bill_no'] : '';
        } else {
            $reason = $ret['msg'];
        }

        // 转账数据回调
        return CashPaymentService::CashPaymentResponse($cash, $insert['data'], $start_time, $ret['data'], $status, $reason, $out_order_no);
    }

    /**
     * 转账场景数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2025-07-04
     * @desc    description
     * @param   [string]          $transfer_scene_id [转账场景id]
     */
    public static function TransferSceneReportInfos($transfer_scene_id)
    {
        $arr = BaseService::ConstData('weixin_transfer_scene_report_list');
        return (isset($arr[$transfer_scene_id]) && isset($arr[$transfer_scene_id]['data'])) ? $arr[$transfer_scene_id]['data'] : [];
    }

    /**
     * 支付刷新
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-09-19
     * @desc    description
     * @param   [array]          $cash      [提现数据]
     * @param   [array]          $cash_log  [提现日志数据]
     * @param   [array]          $params    [输入参数]
     */
    public static function PayRefresh($cash, $cash_log, $params = [])
    {
        // 初始化
        $ret = self::Init();
        if($ret['code'] != 0)
        {
            return $ret;
        }
        if(empty($cash_log['out_order_no']))
        {
            return DataReturn('没有支付平台单号', -1);
        }

        // 请求接口
        $ret = self::WeixinRequest('/v3/fund-app/mch-transfer/transfer-bills/transfer-bill-no/'.$cash_log['out_order_no'], '', 'GET', false);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 回调处理
        $status = 3;
        $reason = '异常错误';
        $out_order_no = '';
        $response = json_decode($ret['data'], true);
        // ACCEPTED 已受理, PROCESSING 转账中, SUCCESS 已完成, FAIL 失败
        if(isset($response['state']) && in_array($response['state'], ['ACCEPTED', 'PROCESSING', 'SUCCESS']))
        {
            $status = 2;
            $reason = '';

        // 待用户收款
        // WAIT_USER_CONFIRM: 待收款用户确认，可拉起微信收款确认页面进行收款确认
        // TRANSFERING: 转账中，可拉起微信收款确认页面再次重试确认收款
        } else if(isset($response['state']) && in_array($response['state'], ['WAIT_USER_CONFIRM', 'TRANSFERING']))
        {
            $status = 1;
            $reason = '';
        } else {
            $reason = empty($response['message']) ? (empty($response['fail_reason']) ? $ret['data'] : $response['fail_reason']) : $response['message'];
            if(!empty($response['code']))
            {
                $reason .= '('.$response['code'].')';
            }
        }
        $out_order_no = isset($response['transfer_bill_no']) ? $response['transfer_bill_no'] : '';

        // 转账数据回调
        return CashPaymentService::CashPaymentRefresh($cash, $cash_log['id'], $status, $reason, $out_order_no);
    }

    /**
     * 商户证书私钥
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-09-20
     * @desc    description
     */
    public static function PrivateKey()
    {
        $result = '';
        if(stripos(self::$config['weixin_private_key'], '-----') === false)
        {
            $result = "-----BEGIN PRIVATE KEY-----\n";
            $result .= wordwrap(self::$config['weixin_private_key'], 64, "\n", true);
            $result .= "\n-----END PRIVATE KEY-----";
        } else {
            $result = self::$config['weixin_private_key'];
        }
        return $result;
    }

    /**
     * 商户证书公钥
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-09-20
     * @desc    description
     */
    public static function WeixinPublicKey()
    {
        $result = '';
        if(stripos(self::$config['weixin_out_pub_key'], '-----') === false)
        {
            $result = "-----BEGIN PUBLIC KEY-----\n";
            $result .= wordwrap(self::$config['weixin_out_pub_key'], 64, "\n", true);
            $result .= "\n-----END PUBLIC KEY-----";
        } else {
            $result = self::$config['weixin_out_pub_key'];
        }
        return $result;
    }

    /**
     * 内容加密
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-09-20
     * @desc    description
     * @param   [string]          $str [需要加密的内容字符串]
     */
    public static function ContentEncrypt($str)
    {
        if(openssl_public_encrypt($str, $encrypted, self::WeixinPublicKey(), OPENSSL_PKCS1_OAEP_PADDING))
        {
            return base64_encode($encrypted);
        }
        return '';
    }

    /**
     * 接口请求
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-09-20
     * @desc    description
     * @param   [string]          $path    [接口路径地址]
     * @param   [array]           $body    [请求数据]
     * @param   [string]          $method  [请求类型]
     * @param   [boolean]         $is_json [是否json格式返回原数据]
     */
    public static function WeixinRequest($path, $body = '', $method = 'GET', $is_json = true)
    {
        // 请求签名+token
        $timestamp = time();
        $nonce = strtoupper(RandomString(32));
        $body_json = empty($body) ? '' : json_encode($body);
        $message = $method."\n".$path."\n".$timestamp."\n".$nonce."\n".$body_json."\n";
        openssl_sign($message, $raw_sign, self::PrivateKey(), OPENSSL_ALGO_SHA256);
        $sign = base64_encode($raw_sign);
        $schema = 'WECHATPAY2-SHA256-RSA2048';
        $token = sprintf('mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"', self::$config['weixin_merchant_id'], $nonce, $timestamp, self::$config['weixin_serial_no'], $sign);

        // 头信息
        $header = [
            'Authorization: '.$schema.' '.$token,
            'Accept: application/json',
            'User-Agent: */*',
            'Wechatpay-Serial: '.self::ContentEncrypt(self::$config['weixin_out_pub_id']),
            'wechatpay-serial: '.self::$config['weixin_out_pub_id'],
        ];

        // 请求接口
        $url = 'https://api.mch.weixin.qq.com';
        $ret = CurlPost($url.$path, $body, 1, 30, $method, $header);
        if(!empty($ret['data']) && $is_json)
        {
            $ret['data'] = json_decode($ret['data'], true);
        }
        return $ret;
    }
}
?>