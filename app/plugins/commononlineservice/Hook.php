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
namespace app\plugins\commononlineservice;

use app\service\PluginsService;

/**
 * 在线客服 - 钩子入口
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Hook
{
    /**
     * 应用响应入口
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-09T14:25:44+0800
     * @param    [array]                    $params [输入参数]
     */
    public function handle($params = [])
    {
        // 是否控制器钩子
        // is_backend 当前为后端业务处理
        // hook_name 钩子名称
        if(isset($params['is_backend']) && $params['is_backend'] === true && !empty($params['hook_name']))
        {
            // 参数一   描述
            // 参数二   0 为处理成功, 负数为失败
            // 参数三   返回数据
            return DataReturn('返回描述', 0);

        // 默认返回视图
        } else {
            if(!empty($params['hook_name']))
            {
                switch($params['hook_name'])
                {
                    case 'plugins_css' :
                        $ret = 'static/plugins/commononlineservice/css/index/style.css';
                        break;

                    case 'plugins_js' :
                        $ret = 'static/plugins/commononlineservice/js/index/style.js';
                        break;

                    case 'plugins_view_common_bottom_footer' :
                        $ret = $this->html($params);
                        break;

                    default :
                        $ret = '';
                }
                return $ret;
            } else {
                return '';
            }
        }
    }

    /**
     * 视图
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-06T16:16:34+0800
     * @param    [array]          $params [输入参数]
     */
    public function html($params = [])
    {
        // 当前模块/控制器/方法
        $module_name = RequestModule();
        $controller_name = RequestController();
        $action_name = RequestAction();

        // 获取应用数据
        $ret = PluginsService::PluginsData('commononlineservice', ['qrcode_images', 'online_service_btn']);
        if($ret['code'] == 0 && !empty($ret['data']))
        {
            // 非全局
            if($ret['data']['is_overall'] != 1)
            {
                // 非首页则空
                if($module_name.$controller_name.$action_name != 'indexindexindex')
                {
                    return '';
                }
            }

            // qq和whatsapp客服
            $online_service_field = ['online_service_qq', 'online_service_whatsapp'];
            foreach($online_service_field as $field)
            {
                $online_service = empty($ret['data'][$field]) ? [] : explode("\n", $ret['data'][$field]);
                $online_service_data = [];
                if(!empty($online_service))
                {
                    foreach($online_service as $v)
                    {
                        $items = explode('|', $v);
                        if(count($items) == 2)
                        {
                            $online_service_data[] = $items;
                        } else {
                            $online_service_data[] = [0=>'', 1=>$v];
                        }
                    }
                }
                $ret['data'][$field] = $online_service_data;
            }

            // 在线客服
            if(isset($ret['data']['is_plugins_chat']) && $ret['data']['is_plugins_chat'] == 1)
            {
                $chat = CallPluginsServiceMethod('chat', 'BaseService', 'ChatUrl', [], []);
                if(!empty($chat) && !isset($chat['code']))
                {
                    $ret['data']['plugins_chat_data'] = $chat;
                }
            }

            return MyView('../../../plugins/commononlineservice/view/index/public/content', [
                'data'           => $ret['data'],
                'qq_icon'        => StaticAttachmentUrl('qq.png'),
                'whatsapp_icon'  => StaticAttachmentUrl('whatsapp.png'),
            ]);
        }
        return '';
    }
}
?>