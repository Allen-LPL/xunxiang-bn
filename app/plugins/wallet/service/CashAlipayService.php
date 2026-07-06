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
use app\service\PluginsDataConfigService;
use app\plugins\wallet\service\CashPaymentService;

/**
 * 钱包 - 钱包余额提现到支付宝服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class CashAlipayService
{
    // 配置信息
    public static $config;

    /**
     * 支付宝转账创建
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
        // 数据配置
        self::$config = PluginsDataConfigService::DataConfigData('wallet');

        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'alipay_appid',
                'error_msg'         => 'appid未配置',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'alipay_cert_content',
                'error_msg'         => '应用证书未配置',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'alipay_out_root_cert_content',
                'error_msg'         => '支付宝ROOT证书未配置',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'alipay_out_cert_content',
                'error_msg'         => '支付宝证书未配置',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'alipay_rsa_private',
                'error_msg'         => '应用私钥未配置',
            ],
        ];
        $ret = ParamsChecked(self::$config, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }
        if(empty($cash['bank_accounts']))
        {
            return DataReturn('支付宝账户为空', -1);
        }
        if(empty($cash['bank_username']))
        {
            return DataReturn('支付宝账户姓名为空', -1);
        }

        // 转账数据
        $data = [
            'app_id'                => self::$config['alipay_appid'],
            'method'                => 'alipay.fund.trans.uni.transfer',
            'format'                => 'JSON',
            'charset'               => 'utf-8',
            'sign_type'             => 'RSA2',
            'timestamp'             => date('Y-m-d H:i:s'),
            'version'               => '1.0',
            'app_cert_sn'           => self::GetCertSNFromContent(self::$config['alipay_cert_content']),
            'alipay_root_cert_sn'   => self::GetRootCertSNFromContent(self::$config['alipay_out_root_cert_content']),
        ];
        $pay_no = $cash['cash_no'].GetNumberCode(6);
        $biz_content = [
            'out_biz_no'    => $pay_no,
            'trans_amount'  => $cash['money'],
            'biz_scene'     => 'DIRECT_TRANSFER',
            'product_code'  => 'TRANS_ACCOUNT_NO_PWD',
            'order_title'   => '钱包提现',
            'payee_info'    => [
                'identity'       => $cash['bank_accounts'],
                'identity_type'  => 'ALIPAY_LOGON_ID',
                'name'           => $cash['bank_username'],
            ],
            'business_params'=> '{"payer_show_name_use_alias":"true"}',
        ];
        $data['biz_content'] = json_encode($biz_content, JSON_UNESCAPED_UNICODE);

        // 生成签名参数+签名
        $data['sign'] = self::MyRsaSign(self::GetSignContent($data));

        // 先释放原来为处理的数据
        CashPaymentService::CashPaymentRelease($cash);

        // 转账数据添加
        $insert = CashPaymentService::CashPaymentInsert($cash, $pay_no, $data, $params);
        if($insert['code'] != 0)
        {
            return $insert;
        }
        $start_time = time();

        // 执行请求
        $ret = self::HttpRequest('https://openapi.alipay.com/gateway.do', $data);
        $key = str_replace('.', '_', $data['method']).'_response';

        // 回调处理
        $status = 3;
        $reason = '异常错误';
        $out_order_no = '';
        if(isset($ret[$key]['code']) && $ret[$key]['code'] == 10000)
        {
            // 验证签名
            if(self::SyncRsaVerify($ret, $key))
            {
                $status = 2;
                $reason = '';
                $out_order_no = $ret[$key]['order_id'];
            } else {
                $reason = '回调签名验证错误';
            }
        } else {
            $reason = $ret[$key]['sub_msg'].'['.$ret[$key]['sub_code'].']';
        }

        // 转账数据回调
        return CashPaymentService::CashPaymentResponse($cash, $insert['data'], $start_time, $ret, $status, $reason, $out_order_no);
    }

    /**
     * 获取签名内容
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-03-15
     * @desc    description
     * @param   [array]          $params [需要签名的参数]
     */
    public static function GetSignContent($params)
    {
        ksort($params);
        $string = "";
        $i = 0;
        foreach($params as $k => $v)
        {
            if(!empty($v) && "@" != substr($v, 0, 1))
            {
                if ($i == 0) {
                    $string .= "$k" . "=" . "$v";
                } else {
                    $string .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }
        unset($k, $v);
        return $string;
    }

    /**
     * 签名字符串
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2017-09-24T08:38:28+0800
     * @param    [string]                   $prestr [需要签名的字符串]
     * @return   [string]                           [签名结果]
     */
    public static function MyRsaSign($prestr)
    {
        if(stripos(self::$config['alipay_rsa_private'], '-----') === false)
        {
            $res = "-----BEGIN RSA PRIVATE KEY-----\n";
            $res .= wordwrap(self::$config['alipay_rsa_private'], 64, "\n", true);
            $res .= "\n-----END RSA PRIVATE KEY-----";
        } else {
            $res = self::$config['alipay_rsa_private'];
        }
        return openssl_sign($prestr, $sign, $res, OPENSSL_ALGO_SHA256) ? base64_encode($sign) : null;
    }

    /**
     * 从证书内容中提取序列号
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-09-15
     * @desc    description
     * @param   [string]          $cert_content [证书内容发]
     * @return  [string]                        [序列号]
     */
    public static function GetCertSNFromContent($cert_content)
    {
        $ssl = openssl_x509_parse($cert_content);
        return md5(self::ArrayToString(array_reverse($ssl['issuer'])).$ssl['serialNumber']);
    }

    /**
     * 数组转字符串
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-09-15
     * @desc    description
     * @param   [array]          $array [数组]
     * @return  [string]                [字符串]
     */
    public static function ArrayToString($array)
    {
        $string = [];
        if ($array && is_array($array))
        {
            foreach($array as $key=>$value)
            {
                $string[] = $key.'='.$value;
            }
        }
        return implode(',', $string);
    }

    /**
     * 提取根证书序列号
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-09-15
     * @desc    description
     * @param   [string]          $cert_content [证书]
     * @return  [string]                        [序列号]
     */
    public static function GetRootCertSNFromContent($cert_content)
    {
        $array = explode("-----END CERTIFICATE-----", $cert_content);
        $sn = null;
        for($i = 0; $i < count($array) - 1; $i++)
        {
            $ssl[$i] = openssl_x509_parse($array[$i] . "-----END CERTIFICATE-----");
            if(strpos($ssl[$i]['serialNumber'],'0x') === 0){
                $ssl[$i]['serialNumber'] = self::Hex2dec($ssl[$i]['serialNumberHex']);
            }
            if($ssl[$i]['signatureTypeLN'] == "sha1WithRSAEncryption" || $ssl[$i]['signatureTypeLN'] == "sha256WithRSAEncryption")
            {
                if($sn == null)
                {
                    $sn = md5(self::ArrayToString(array_reverse($ssl[$i]['issuer'])) . $ssl[$i]['serialNumber']);
                } else {

                    $sn = $sn . "_" . md5(self::ArrayToString(array_reverse($ssl[$i]['issuer'])) . $ssl[$i]['serialNumber']);
                }
            }
        }
        return $sn;
    }
    
    /**
     * 0x转高精度数字
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-09-15
     * @desc    description
     * @param   [int]          $hex [数字]
     * @return  [int|string]        [转换的数据]
     */
    public static function Hex2dec($hex)
    {
        $dec = 0;
        $len = strlen($hex);
        for($i = 1; $i <= $len; $i++)
        {
            $dec = bcadd($dec, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));
        }
        return $dec;
    }

    /**
     * 从证书中提取公钥
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-10-11
     * @desc    description
     * @return  [string]          [公钥]
     */
    public static function GetPublicKey()
    {
        $pkey = openssl_pkey_get_public(self::$config['alipay_out_cert_content']);
        $keyData = openssl_pkey_get_details($pkey);
        $public_key = str_replace('-----BEGIN PUBLIC KEY-----', '', $keyData['key']);
        $public_key = trim(str_replace('-----END PUBLIC KEY-----', '', $public_key));
        return $public_key;
    }

    /**
     * 同步返回签名验证
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2017-09-25T13:13:39+0800
     * @param    [array]                   $data [返回数据]
     * @param    [boolean]                 $key  [数据key]
     */
    public static function SyncRsaVerify($data, $key)
    {
        $string = json_encode($data[$key], JSON_UNESCAPED_UNICODE);
        return self::OutRsaVerify($string, $data['sign']);
    }

    /**
     * 支付宝验证签名
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2017-09-24T08:39:50+0800
     * @param    [string]                   $prestr [需要签名的字符串]
     * @param    [string]                   $sign   [签名结果]
     * @return   [boolean]                          [正确true, 错误false]
     */
    public static function OutRsaVerify($prestr, $sign)
    {
        $public_key = self::GetPublicKey();
        if(stripos($public_key, '-----') === false)
        {
            $res = "-----BEGIN PUBLIC KEY-----\n";
            $res .= wordwrap($public_key, 64, "\n", true);
            $res .= "\n-----END PUBLIC KEY-----";
        } else {
            $res = $public_key;
        }
        $pkeyid = openssl_pkey_get_public($res);
        $sign = base64_decode($sign);
        if($pkeyid)
        {
            $verify = openssl_verify($prestr, $sign, $pkeyid, OPENSSL_ALGO_SHA256);
            unset($pkeyid);
        }
        return (isset($verify) && $verify == 1) ? true : false;
    }

    /**
     * 网络请求
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2017-09-25T09:10:46+0800
     * @param    [string]          $url  [请求url]
     * @param    [array]           $data [发送数据]
     * @return   [mixed]                 [请求返回数据]
     */
    public static function HttpRequest($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $body_string = '';
        if(is_array($data) && 0 < count($data))
        {
            foreach($data as $k => $v)
            {
                $body_string .= $k.'='.urlencode($v).'&';
            }
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body_string);
        }
        $headers = array('content-type: application/x-www-form-urlencoded;charset=UTF-8');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $reponse = curl_exec($ch);
        if(curl_errno($ch))
        {
            return false;
        } else {
            $http_status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if(200 !== $http_status_code)
            {
                return false;
            }
        }
        curl_close($ch);
        return json_decode($reponse, true);
    }
}
?>