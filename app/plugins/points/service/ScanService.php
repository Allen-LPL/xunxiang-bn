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
use app\plugins\points\service\ScanQrcodeService;

/**
 * 积分商城 - 扫码服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class ScanService
{
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
            foreach($data as &$v)
            {
                // 批次数据
                if(array_key_exists('batch_data', $v))
                {
                    $v['batch_data'] = empty($v['batch_data']) ? [] : json_decode($v['batch_data'], true);
                }
            }
        }
        return $data;
    }

    /**
     * 保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-08-30
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function ScanSave($params = [])
    {
        // 参数校验
        $p = [
            [
                'checked_type'      => 'isset',
                'key_name'          => 'plugins_config',
                'error_msg'         => MyLang('plugins_config_error_tips'),
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'name',
                'error_msg'         => '请填写名称',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 获取信息
        $info = empty($params['id']) ? null : Db::name('PluginsPointsScan')->where(['id'=>intval($params['id'])])->find();
        // 数据为空或者没有二维码数量则需要验证数据
        if(empty($info) || empty($info['qrcode_count']))
        {
            // 参数校验
            $p = [
                [
                    'checked_type'      => 'empty',
                    'key_name'          => 'platform',
                    'error_msg'         => MyLang('form_platform_message'),
                ],
                [
                    'checked_type'      => 'empty',
                    'key_name'          => 'brand_category_id',
                    'error_msg'         => '请选择品牌分类',
                ],
                [
                    'checked_type'      => 'empty',
                    'key_name'          => 'brand_id',
                    'error_msg'         => '请选择品牌',
                ],
                [
                    'checked_type'      => 'empty',
                    'key_name'          => 'integral',
                    'error_msg'         => '请填写赠送积分',
                ],
            ];
            $ret = ParamsChecked($params, $p);
            if($ret !== true)
            {
                return DataReturn($ret, -1);
            }
        }

        // 数据
        $data = [
            'name'       => $params['name'],
            'is_enable'  => isset($params['is_enable']) && $params['is_enable'] == 1 ? 1 : 0,
        ];

        // 数据为空或者没有二维码数量则可以编辑
        if(empty($info) || empty($info['qrcode_count']))
        {
            $data['platform']           = $params['platform'];
            $data['brand_category_id']  = intval($params['brand_category_id']);
            $data['brand_id']           = intval($params['brand_id']);
            $data['integral']           = intval($params['integral']);
        }
        try {
            if(empty($info))
            {
                $data['add_time'] = time();
                $scan_id = Db::name('PluginsPointsScan')->insertGetId($data);
                if($scan_id <= 0)
                {
                    throw new \Exception(MyLang('insert_fail'));
                }
            } else {
                $data['upd_time'] = time();
                if(!Db::name('PluginsPointsScan')->where(['id'=>$info['id']])->update($data))
                {
                    throw new \Exception(MyLang('update_fail'));
                }
            }
            return DataReturn(MyLang('operate_success'), 0);
        } catch(\Exception $e) {
            return DataReturn($e->getMessage(), -1);
        }
    }

    /**
     * 状态更新
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-08
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function ScanStatusUpdate($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => MyLang('data_id_error_tips'),
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'field',
                'error_msg'         => MyLang('operate_field_error_tips'),
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'state',
                'checked_data'      => [0,1],
                'error_msg'         => MyLang('form_status_range_message'),
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 状态更新
        if(Db::name('PluginsPointsScan')->where(['id'=>intval($params['id'])])->update([$params['field']=>intval($params['state'])]))
        {
           return DataReturn(MyLang('operate_success'));
        }
        return DataReturn(MyLang('operate_fail'), -100);
    }

    /**
     * 删除
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-08
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function ScanDelete($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'ids',
                'error_msg'         => MyLang('data_id_error_tips'),
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }
        // 是否数组
        if(!is_array($params['ids']))
        {
            $params['ids'] = explode(',', $params['ids']);
        }

        // 捕获异常
        Db::startTrans();
        try {
            // 扫码数据
            if(!Db::name('PluginsPointsScan')->where(['id'=>$params['ids']])->delete())
            {
                throw new \Exception(MyLang('delete_fail'));
            }
            // 二维码数据
            if(Db::name('PluginsPointsScanQrcode')->where(['scan_id'=>$params['ids']])->delete() === false)
            {
                throw new \Exception(MyLang('delete_fail'));
            }

            // 删除图片
            foreach($params['ids'] as $id)
            {
                \base\FileUtil::UnlinkDir(ROOT.'public'.DS.'download'.DS.'plugins_points'.DS.'qrcode_scan_'.$id);
            }
            
            Db::commit();
            return DataReturn(MyLang('delete_success'), 0);
        } catch(\Exception $e) {
            Db::rollback();
            return DataReturn($e->getMessage(), -1);
        }
    }

    /**
     * 二维码数量生成
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-08
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function ScanGenerate($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'scan_id',
                'error_msg'         => '扫码数据id为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'number',
                'error_msg'         => '请填写生成数量',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 扫码数据
        $scan = Db::name('PluginsPointsScan')->where(['id'=>intval($params['scan_id']), 'is_enable'=>1])->find();
        if(empty($scan))
        {
            return DataReturn('没有相关扫码数据', -1);
        }
        $batch_data = empty($scan['batch_data']) ? [] : json_decode($scan['batch_data'], true);

        // 批次数据
        $batch_id = date('YmdHis').$scan['id'].GetNumberCode(6);
        $batch_name = '(第'.(count($batch_data)+1).'批'.$params['number'].'个)'.date('Y-m-d H:i:s');

        // 二维码写入数据
        $data = [
            'scan_id'   => $scan['id'],
            'batch_id'  => $batch_id,
            'integral'  => $scan['integral'],
            'add_time'  => time(),
        ];

        // 存储路径
        $path = 'download'.DS.'plugins_points'.DS.'qrcode_scan_'.$scan['id'].DS.$batch_id.DS;

        // 捕获异常
        Db::startTrans();
        try {
            // 循环生成
            for($i=0; $i<$params['number']; $i++)
            {
                // 添加数据
                unset($data['id']);
                $data['id'] = Db::name('PluginsPointsScanQrcode')->insertGetId($data);
                if(empty($data['id']))
                {
                    throw new \Exception('二维码数据添加失败');
                }
                $data['qrcode'] = GetNumberCode(3).$scan['id'].$data['id'].GetNumberCode(3);

                // 二维码生成处理
                $ret = ScanQrcodeService::QrcodeGenerateHandle($scan, $data);
                if($ret['code'] == 0)
                {
                    // 扫码增加二维码总数
                    if(!Db::name('PluginsPointsScan')->where(['id'=>$scan['id']])->inc('qrcode_count')->update())
                    {
                        throw new \Exception('扫码二维码总数增加失败');
                    }
                    // 二维码增加标识
                    if(!Db::name('PluginsPointsScanQrcode')->where(['id'=>$data['id']])->update(['qrcode'=>$data['qrcode']]))
                    {
                        throw new \Exception('二维码标识更新失败');
                    }
                } else {
                    throw new \Exception($ret['msg']);
                }
            }

            // 批次数据更新
            $batch_data[] = [
                'batch_id'    => $batch_id,
                'batch_name'  => $batch_name,
            ];
            if(!Db::name('PluginsPointsScan')->where(['id'=>$scan['id']])->update(['batch_data'=>json_encode($batch_data, JSON_UNESCAPED_UNICODE), 'upd_time'=>time()]))
            {
                throw new \Exception('扫码批次数据更新失败');
            }

            Db::commit();
            return DataReturn(MyLang('created_success'), 0);
        } catch(\Exception $e) {
            Db::rollback();
            return DataReturn($e->getMessage(), -1);
        }
    }

    /**
     * 二维码下载
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-08
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function ScanDownload($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'scan_id',
                'error_msg'         => '扫码数据id为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'batch_id',
                'error_msg'         => '批次数据id为空',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 扫码数据
        $scan = Db::name('PluginsPointsScan')->where(['id'=>intval($params['scan_id']), 'is_enable'=>1])->find();
        if(empty($scan))
        {
            return DataReturn('没有相关扫码数据', -1);
        }
        // 批次数据
        $batch_data = empty($scan['batch_data']) ? [] : array_column(json_decode($scan['batch_data'], true), 'batch_name', 'batch_id');
        if(!array_key_exists($params['batch_id'], $batch_data))
        {
            return DataReturn('没有相关批次数据', -1);
        }

        // 二维码目录
        $old_dir = ROOT.'public'.DS.'download'.DS.'plugins_points'.DS.'qrcode_scan_'.$scan['id'].DS.$params['batch_id'];
        if(!is_dir($old_dir))
        {
            return DataReturn('批次二维码目录不存在', -1);
        }

        // 存放压缩包目录不存在则创建
        $new_dir = ROOT.'public'.DS.'download'.DS.'plugins_points'.DS.'download_scan_'.$scan['id'].DS;
        if(!is_dir($new_dir))
        {
            \base\FileUtil::CreateDir($new_dir);
        }
        // 新的压缩包路径文件名称
        $new_file = $new_dir.$params['batch_id'].'.zip';

        // 生成压缩包
        $zip = new \base\ZipFolder();
        if(!$zip->zip($new_file, $old_dir))
        {
            return DataReturn(MyLang('form_generate_zip_message'), -100);
        }

        // 开始下载
        if(\base\FileUtil::DownloadFile($new_file, $batch_data[$params['batch_id']].'.zip', true))
        {
            return DataReturn(MyLang('download_success'), 0);
        }
        return DataReturn(MyLang('download_fail'), -100);      
    }
}
?>