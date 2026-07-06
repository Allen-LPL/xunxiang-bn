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
namespace app\api\controller;

use app\service\ApiService;
use app\service\SystemBaseService;
use app\service\PaymentService;
use app\service\OrderService;
use app\service\GoodsCommentsService;
use app\service\ConfigService;
use app\service\ResourcesService;
use app\service\ExpressService;
use app\service\wx\WxSubscribeMessageService;

/**
 * 供货商订单
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Ordersupplier extends Common
{
    /**
     * 构造方法
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-03T12:39:08+0800
     */
    public function __construct()
    {
        // 调用父类前置方法
        parent::__construct();

        // 是否登录
        //$this->IsLogin();
    }

    /**
     * 列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2017-02-22T16:50:32+0800
     */
    public function Index()
    {
        /*$access_token = WxSubscribeMessageService::getAccessToken();
        print_r($access_token);
        echo 'a--';exit;*/
        // 参数
        $params = $this->data_request;
        //$params['user'] = $this->user;
        $params['user_type'] = 'user';

        // 条件
        $where = OrderService::OrderListWhere($params);

        // 获取总数
        $total = OrderService::OrderTotal($where);
        $page_total = ceil($total/$this->page_size);
        $start = intval(($this->page-1)*$this->page_size);

        // 获取列表
        $data_params = [
            'm'                 => $start,
            'n'                 => $this->page_size,
            'where'             => $where,
            'is_orderaftersale' => 1,
            'is_operate'        => 1,
        ];
        //$data = OrderService::OrderList($data_params);
        $data_params['user_id'] = $this->user['id'];
        $data = OrderService::OrdersupplierList($data_params);

        // 支付方式
        $payment_list = PaymentService::BuyPaymentList(['is_enable'=>1, 'is_open_user'=>1]);

        // 返回数据
        $result = [
            'total'               => $total,
            'page_total'          => $page_total,
            'data'                => $data['data'],
            'payment_list'        => $payment_list,
            'default_payment_id'  => PaymentService::BuyDefaultPayment($params),
        ];
        return ApiService::ApiDataReturn(SystemBaseService::DataReturn($result));
    }

    /**
     * 快递公司列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-28
     * @desc    description
     */
    public function ExpressList()
    {
        $result = ExpressService::ExpressList();
        return ApiService::ApiDataReturn(SystemBaseService::DataReturn($result));
    }

    /**
     * 订单发货、取货、服务
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-28
     * @desc    description
     */
    public function Delivery()
    {
        // 发货操作
        $params = $this->data_request;
        $params['creator'] = $this->user['id'];
        $params['creator_name'] = $this->user['nickname'];
        $params['user_type'] = 'supplier';
        
        //判断是否当前订单商品供货商
        $params['user_id'] = $this->user['id'];
        $res = OrderService::getOrderandsupplier($params);
        if($res['status']==2){
            $ret = DataReturn(MyLang('params_error_no_supplier'), -1);
            return ApiService::ApiDataReturn($ret);
        }elseif($res['status']==3){
            $ret = DataReturn(MyLang('params_error_no_order_supplier'), -1);
            return ApiService::ApiDataReturn($ret);
        }

        return ApiService::ApiDataReturn(SystemBaseService::DataReturn(OrderService::OrderDelivery($params)));
    }

    /**
     * 订单收货
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-28
     * @desc    description
     */
    public function Collect()
    {
        return ApiService::ApiDataReturn(SystemBaseService::DataReturn(['当前接口暂未开放']));
        // 收货操作
        $params = $this->data_request;
        $params['user_id'] = $params['value'];
        $params['creator'] = $this->user['id'];
        $params['creator_name'] = $this->user['nickname'];
        $params['user_type'] = 'supplier';

        //判断是否当前订单商品供货商
        $params['user_id'] = $this->user['id'];
        $res = OrderService::getOrderandsupplier($params);
        if($res['status']==2){
            $ret = DataReturn(MyLang('params_error_no_supplier'), -1);
            return ApiService::ApiDataReturn($ret);
        }elseif($res['status']==3){
            $ret = DataReturn(MyLang('params_error_no_order_supplier'), -1);
            return ApiService::ApiDataReturn($ret);
        }

        return ApiService::ApiDataReturn(SystemBaseService::DataReturn(OrderService::OrderCollect($params)));
    }
}
?>