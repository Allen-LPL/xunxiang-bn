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
namespace app\plugins\points\service;

use think\facade\Db;
use app\service\QrCodeService;
use app\service\ResourcesService;
use app\service\UserService;
use app\service\IntegralService;

/**
 * 积分商城 - 二维码服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class ScanQrcodeService
{
    /**
     * 用户扫码数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-02-19
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function UserScanQrcodeData($params = [])
    {
        // 获取扫码数据、没有传参固定返回错误码 -10000
        if(empty($params['id']))
        {
            return DataReturn('没有参数', -10000);
        }

        // 是否已登录
        if(empty($params['user']))
        {
            return DataReturn('请先登录！', -100);
        }

        // 数据是否存在
        $data = Db::name('PluginsPointsScanQrcode')->where(['qrcode'=>$params['id']])->find();
        if(empty($data))
        {
            return DataReturn('没有相关扫码！', -1);
        }

        // 是否已使用
        if($data['is_use'] == 1)
        {
            $msg = (empty($params['plugins_config']) || empty($params['plugins_config']['scan_fail_tips'])) ? '已被扫码！' : $params['plugins_config']['scan_fail_tips'];
            return DataReturn($msg, -100);
        }
  
        // 处理积分
        Db::startTrans();
        try {
            // 积分增加
            if(!Db::name('User')->where(['id'=>$params['user']['id']])->inc('integral', $data['integral'])->update())
            {
                throw new \Exception('积分赠送失败、请稍后再试！', -100);
            }
            // 积分日志
            if(!IntegralService::UserIntegralLogAdd($params['user']['id'], $params['user']['integral'], $data['integral'], '积分商城扫码赠送', 1))
            {
                throw new \Exception('积分日志添加失败、请稍后再试！', -100);
            }
            // 更新使用积分数据
            if(!Db::name('PluginsPointsScanQrcode')->where(['id'=>$data['id']])->update([
                'is_use'    => 1,
                'user_id'   => $params['user']['id'],
                'use_time'  => time(),
                'upd_time'  => time(),
            ]))
            {
                throw new \Exception('扫码积分更新失败、请稍后再试！', -100);
            }
            if(!Db::name('PluginsPointsScan')->where(['id'=>$data['scan_id']])->inc('qrcode_use_count', 1)->update())
            {
                throw new \Exception('扫码积分更新失败、请稍后再试！', -100);
            }
            Db::commit();
            $msg = (empty($params['plugins_config']) || empty($params['plugins_config']['scan_success_tips'])) ? '恭喜获得{$integral}积分' : $params['plugins_config']['scan_success_tips'];
            return DataReturn(str_replace('{$integral}', $data['integral'], $msg), 0);
        } catch(\Exception $e) {
            Db::rollback();
            return DataReturn($e->getMessage(), $e->getCode());
        }
    }

    /**
     * 列表数据处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-02-06
     * @desc    description
     * @param   [array]          $data   [列表数据]
     * @param   [array]          $params [输入参数]
     */
    public static function DataListHandle($data, $params = [])
    {
        if(!empty($data))
        {
            // 用户列表
            $user_ids = array_unique(array_filter(array_column($data, 'user_id')));
            $user_list = empty($user_ids) ? [] : UserService::GetUserViewInfo($user_ids);
            foreach($data as &$v)
            {
                // 用户信息
                if(array_key_exists('user_id', $v))
                {
                    $v['user'] = (!empty($user_list) && is_array($user_list) && array_key_exists($v['user_id'], $user_list)) ? $user_list[$v['user_id']] : null;
                }

                // 批次数据
                if(array_key_exists('qrcode', $v))
                {
                    $file = DS.'download'.DS.'plugins_points'.DS.'qrcode_scan_'.$v['scan_id'].DS.$v['batch_id'].DS.$v['qrcode'].'.png';
                    $v['images'] = file_exists(ROOT.'public'.$file) ? ResourcesService::AttachmentPathViewHandle($file) : '';
                }
            }
        }
        return $data;
    }

    /**
     * 二维码生成
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-08
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function ScanQrcodeGenerate($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '扫码数据id为空',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 二维码数据
        $data = Db::name('PluginsPointsScanQrcode')->where(['id'=>intval($params['id'])])->find();
        if(empty($data))
        {
            return DataReturn('没有相关二维码数据', -1);
        }
        // 扫码数据
        $scan = Db::name('PluginsPointsScan')->where(['id'=>$data['scan_id']])->find();
        if(empty($scan))
        {
            return DataReturn('没有相关扫码数据', -1);
        }

        // 二维码生成处理
        return self::QrcodeGenerateHandle($scan, $data);
    }

    /**
     * 二维码生成处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-02-07
     * @desc    description
     * @param   [array]          $scan [扫码数据]
     * @param   [array]          $data [二维码数据]
     */
    public static function QrcodeGenerateHandle($scan, $data)
    {
        // 生成二维码参数
        $qrcode_params = [
            'url'       => 'pages/plugins/points/scan/scan',
            'query'     => 'id='.$data['qrcode'],
            'filename'  => $data['qrcode'].'.png',
            'platform'  => $scan['platform'],
            'path'      => DS.'download'.DS.'plugins_points'.DS.'qrcode_scan_'.$scan['id'].DS.$data['batch_id'].DS,
        ];
        // PC端则单独生成url地址
        if($scan['platform'] == 'pc')
        {
            $qrcode_params['url'] = PluginsHomeUrl('points', 'scan', 'index', ['id'=>$data['qrcode']]);
        }
        // 创建二维码
        return QrCodeService::QrCodeCreate($qrcode_params);
    }
}
?>