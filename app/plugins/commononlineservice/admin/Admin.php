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
namespace app\plugins\commononlineservice\admin;

use app\service\PluginsService;

/**
 * 在线客服 - 管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Admin
{
    // 基础数据附件字段
    public $base_config_attachment_field = ['qrcode_images', 'online_service_btn'];

    /**
     * 首页
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function index($params = [])
    {
        $ret = PluginsService::PluginsData('commononlineservice', $this->base_config_attachment_field);
        if($ret['code'] == 0)
        {
            // 数据处理
            if(!empty($ret['data']))
            {
                // qq
                if(!empty($ret['data']['online_service_qq']))
                {
                    $ret['data']['online_service_qq'] = empty($ret['data']['online_service_qq']) ? '' : str_replace("\n", '<br />', $ret['data']['online_service_qq']);
                }
                // whatsapp
                if(!empty($ret['data']['online_service_whatsapp']))
                {
                    $ret['data']['online_service_whatsapp'] = empty($ret['data']['online_service_whatsapp']) ? '' : str_replace("\n", '<br />', $ret['data']['online_service_whatsapp']);
                }
            }

            MyViewAssign('data', $ret['data']);
            return MyView('../../../plugins/commononlineservice/view/admin/admin/index');
        } else {
            return $ret['msg'];
        }
    }

    /**
     * 编辑页面
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function saveinfo($params = [])
    {
        $ret = PluginsService::PluginsData('commononlineservice', $this->base_config_attachment_field);
        if($ret['code'] == 0)
        {
            MyViewAssign('data', $ret['data']);
            return MyView('../../../plugins/commononlineservice/view/admin/admin/saveinfo');
        } else {
            return $ret['msg'];
        }
    }

    /**
     * 数据保存
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function save($params = [])
    {
        return PluginsService::PluginsDataSave(['plugins'=>'commononlineservice', 'data'=>$params], $this->base_config_attachment_field);
    }
}
?>