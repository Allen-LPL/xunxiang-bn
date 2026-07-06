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
namespace app\plugins\points\api;

use app\plugins\points\api\Common;
use app\plugins\points\service\BaseService;
use app\plugins\points\service\ScanQrcodeService;

/**
 * 积分商城 - 扫码
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-10
 * @desc    description
 */
class Scan extends Common
{
    /**
     * 首页
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Index($params = [])
    {
        $params['user'] = $this->user;
        $params['plugins_config'] = $this->plugins_config;
        $ret = ScanQrcodeService::UserScanQrcodeData($params);
        return DataReturn('success', 0, [
            'data'                 => ['status'=>$ret['code'], 'msg'=>$ret['msg']],
            'base'                 => $this->plugins_config,
            // 默认图片
            'default_images_data'  => BaseService::DefaultImagesData($this->plugins_config),
        ]);
    }
}
?>