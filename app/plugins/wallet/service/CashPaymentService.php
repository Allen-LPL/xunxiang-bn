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
use app\plugins\wallet\service\CashService;

/**
 * 钱包 - 钱包余额提支付服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class CashPaymentService
{
    /**
     * 转账释放
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-09-19
     * @desc    description
     * @param   [array]          $cash           [提现数据]
     * @param   [array]          $params         [输入参数]
     */
    public static function CashPaymentRelease($cash, $params = [])
    {
        Db::name('PluginsWalletCashPayment')->where([
            ['user_id', '=', $cash['user_id']],
            ['wallet_id', '=', $cash['wallet_id']],
            ['cash_id', '=', $cash['id']],
            ['status', '=', 0]
        ])->update([
            'status'    => 3,
            'reason'    => '未处理关闭',
            'upd_time'  => time(),
        ]);
    }

    /**
     * 转账添加
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-09-19
     * @desc    description
     * @param   [array]          $cash           [提现数据]
     * @param   [string]         $pay_no         [支付单号]
     * @param   [array]          $request_params [请求数据]
     * @param   [array]          $params         [输入参数]
     */
    public static function CashPaymentInsert($cash, $pay_no, $request_params, $params = [])
    {
        $data = [
            'user_id'         => $cash['user_id'],
            'wallet_id'       => $cash['wallet_id'],
            'cash_id'         => $cash['id'],
            'cash_no'         => $cash['cash_no'],
            'pay_no'          => $pay_no,
            'pay_type'        => $cash['cash_type']-1,
            'status'          => 0,
            'request_params'  => is_array($request_params) ? json_encode($request_params, JSON_UNESCAPED_UNICODE) : $request_params,
            'add_time'        => time(),
        ];
        $data_id = Db::name('PluginsWalletCashPayment')->insertGetId($data);
        if($data_id > 0)
        {
            return DataReturn('success', 0, $data_id);
        }
        return DataReturn('转账日志添加失败', -1);
    }

    /**
     * 转账回调
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-09-19
     * @desc    description
     * @param   [array]        $cash                    [提现数据]
     * @param   [int]          $data_id                 [数据id]
     * @param   [int]          $start_time              [开始时间]
     * @param   [string]       $response                [回调数据]
     * @param   [int]          $status                  [状态]
     * @param   [string]       $reason                  [原因]
     * @param   [string]       $out_order_no            [外部单号]
     */
    public static function CashPaymentResponse($cash, $data_id, $start_time, $response, $status, $reason, $out_order_no)
    {
        switch($status)
        {
            // 待用户收款
            case 1 :
                $ret = CashService::CashStatusUpdate([
                    'id'      => $cash['id'],
                    'status'  => 1,
                ]);
                break;

            // 支付成功
            case 2 :
                $ret = CashService::CashAudit([
                    'id'         => $cash['id'],
                    'pay_money'  => $cash['money'],
                    'type'       => 'agree',
                    'msg'        => '平台自动打款('.$out_order_no.')',
                ]);
                if($ret['code'] == 0)
                {
                    $ret['msg'] = MyLang('pay_success');
                }
                break;

            // 失败
            case 3 :
                $ret = CashService::CashStatusUpdate([
                    'id'      => $cash['id'],
                    'status'  => 0,
                ]);
                break;
        }
        if(!empty($ret) && $ret['code'] == 0)
        {
            $upd_data = [
                'status'         => $status,
                'reason'         => $reason,
                'response_data'  => empty($response) ? '' : (is_array($response) ? json_encode($response, JSON_UNESCAPED_UNICODE) : $response),
                'out_order_no'   => $out_order_no,
                'tsc'            => time()-$start_time,
                'upd_time'       => time(),
            ];
            if(Db::name('PluginsWalletCashPayment')->where(['id'=>$data_id])->update($upd_data) !== false)
            {
                return $ret;
            } else {
                return DataReturn('转账日志更新失败', -1);
            }
        }
        return DataReturn('转账日志回调失败('.$reason.')', -1);
    }

    /**
     * 转账刷新
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-09-19
     * @desc    description
     * @param   [array]        $cash                    [提现数据]
     * @param   [int]          $data_id                 [数据id]
     * @param   [int]          $status                  [状态]
     * @param   [string]       $reason                  [原因]
     * @param   [string]       $out_order_no            [外部单号]
     */
    public static function CashPaymentRefresh($cash, $data_id, $status, $reason, $out_order_no)
    {
        switch($status)
        {
            // 待用户收款
            case 1 :
                $ret = CashService::CashStatusUpdate([
                    'id'      => $cash['id'],
                    'status'  => 1,
                ]);
                break;

            // 支付成功
            case 2 :
                $ret = CashService::CashAudit([
                    'id'         => $cash['id'],
                    'pay_money'  => $cash['money'],
                    'type'       => 'agree',
                    'msg'        => '平台自动打款('.$out_order_no.')',
                ]);
                if($ret['code'] == 0)
                {
                    $ret['msg'] = MyLang('pay_success');
                }
                break;

            // 失败
            case 3 :
                $ret = CashService::CashStatusUpdate([
                    'id'      => $cash['id'],
                    'status'  => 0,
                ]);
                break;
        }
        if(!empty($ret) && $ret['code'] == 0)
        {
            $upd_data = [
                'status'    => $status,
                'reason'    => $reason,
                'upd_time'  => time(),
            ];
            if(Db::name('PluginsWalletCashPayment')->where(['id'=>$data_id])->update($upd_data) !== false)
            {
                return $ret;
            } else {
                return DataReturn('转账日志更新失败', -1);
            }
        }
        return DataReturn('转账日志回调失败('.$reason.')', -1);
    }

    /**
     * 删除
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-11-18
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function CashPaymentDelete($params = [])
    {
        // 参数是否有误
        if(empty($params['ids']))
        {
            return DataReturn(MyLang('data_id_error_tips'), -1);
        }
        // 是否数组
        if(!is_array($params['ids']))
        {
            $params['ids'] = explode(',', $params['ids']);
        }

        // 删除操作
        if(Db::name('PluginsWalletCashPayment')->where(['id'=>$params['ids']])->delete())
        {
            return DataReturn(MyLang('delete_success'), 0);
        }
        return DataReturn(MyLang('delete_fail'), -100);
    }

    /**
     * 清空全部
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-11-18
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function CashPaymentAllDelete($params = [])
    {
        $where = [
            ['id', '>', 0]
        ];
        if(Db::name('PluginsWalletCashPayment')->where($where)->delete() === false)
        {
            return DataReturn(MyLang('operate_fail'), -100);
        }
        return DataReturn(MyLang('operate_success'), 0);
    }
}
?>