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
namespace app\plugins\wallet\api;

use app\plugins\wallet\api\Common;
use app\plugins\wallet\service\BaseService;
use app\plugins\wallet\service\TransferService;

/**
 * 钱包 - 转账记录
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Transfer extends Common
{
    /**
     * 构造方法
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-11-30
     * @desc    description
     */
    public function __construct($params = [])
    {
        parent::__construct($params);

        // 是否已经登录
        IsUserLogin();
    }

    /**
     * 列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-19
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        // 条件
        $params['user'] = $this->user;
        $params['user_type'] = 'user';
        $params['user_field'] = 'send_user_id';
        $where = BaseService::TransferWhere($params);

        // 获取总数
        $total = BaseService::TransferTotal($where);
        $page_total = ceil($total/$this->page_size);
        $start = intval(($this->page-1)*$this->page_size);

        // 获取列表
        $data_params = array(
            'm'             => $start,
            'n'             => $this->page_size,
            'where'         => $where,
        );
        $data = BaseService::TransferList($data_params);

        // 返回数据
        $result = [
            'total'             => $total,
            'page_total'        => $page_total,
            'data'              => $data['data'],
        ];
        return DataReturn('success', 0, $result);
    }

    /**
     * 获取详情
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-01-07
     * @param   [array]          $params [输入参数]
     */
    public function Detail($params = [])
    {
        // 参数
        if(empty($params['id']))
        {
            return DataReturn(MyLang('params_error_tips'), -1);
        }

        // 条件
        $params['user'] = $this->user;
        $params['user_type'] = 'user';
        $params['user_field'] = 'send_user_id';
        $where = BaseService::TransferWhere($params);

        // 获取列表
        $data_params = array(
            'm'         => 0,
            'n'         => 1,
            'where'     => $where,
        );
        $ret = BaseService::TransferList($data_params);
        if(!empty($ret['data'][0]))
        {
            // 返回信息
            $result = [
                'data'      => $ret['data'][0],
            ];

            return DataReturn('success', 0, $result);
        }
        return DataReturn(MyLang('data_no_exist_or_delete_error_tips'), -100);
    }

    /**
     * 转账
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-08
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Save($params = [])
    {
        $params['user'] = $this->user;
        $params['user_wallet'] = $this->user_wallet;
        $params['operate_id'] = $this->user['id'];
        $params['operate_name'] = $this->user['user_name_view'];
        $params['plugins_config'] = $this->plugins_config;
        return TransferService::TransferSave($params);
    }

    /**
     * 用户查询
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-08
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function UserQuery($params = [])
    {
        $params['plugins_config'] = $this->plugins_config;
        return BaseService::UserQuery($params);
    }
}
?>