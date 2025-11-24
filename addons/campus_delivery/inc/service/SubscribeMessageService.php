<?php
/**
 * 微信订阅消息服务类
 */
defined('IN_IA') or exit('Access Denied');

class SubscribeMessageService {
    
    /**
     * 发送订阅消息
     * @param string $openid 用户OpenID
     * @param string $template_id 模板ID
     * @param string $page 跳转页面
     * @param array $data 模板数据
     * @return array
     */
    public function send($openid, $template_id, $page, $data) {
        global $_W;
        
        $account_api = WeAccount::create();
        $token = $account_api->getAccessToken();
        
        if (is_error($token)) {
            return $token;
        }
        
        $url = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token={$token}";
        
        $post_data = array(
            'touser' => $openid,
            'template_id' => $template_id,
            'page' => $page,
            'data' => $data,
            'miniprogram_state' => 'formal', // developer为开发版；trial为体验版；formal为正式版
            'lang' => 'zh_CN'
        );
        
        $response = ihttp_post($url, json_encode($post_data));
        
        if (is_error($response)) {
            return $response;
        }
        
        $result = json_decode($response['content'], true);
        
        return $result;
    }
    
    /**
     * 发送订单状态通知
     */
    public function sendOrderStatusNotice($openid, $order_no, $status_text, $remark = '') {
        // 需在后台配置模板ID
        global $_W;
        // 假设模板ID存储在模块配置中，或者硬编码
        // 模板结构示例：
        // character_string1: 订单号
        // phrase2: 订单状态
        // thing3: 备注
        
        $template_id = 'YOUR_TEMPLATE_ID'; // 实际开发中应从配置获取
        
        $data = array(
            'character_string1' => array('value' => $order_no),
            'phrase2' => array('value' => $status_text),
            'thing3' => array('value' => $remark ?: '点击查看详情'),
        );
        
        return $this->send($openid, $template_id, 'pages/order/detail?order_no='.$order_no, $data);
    }
}
