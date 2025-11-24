<?php
/**
 * 微信分账服务类
 */
defined('IN_IA') or exit('Access Denied');

class ProfitSharingService {
    
    private $mchid;           // 商户号
    private $serial_no;       // 商户API证书序列号
    private $private_key;     // 商户私钥
    private $wechat_cert;     // 微信支付平台证书（用于验签，暂未实现自动下载，需配置）
    
    public function __construct($config) {
        $this->mchid = $config['wechat_mchid'];
        $this->serial_no = $config['wechat_serial_no']; // 新增配置：证书序列号
        // 加载私钥
        if (file_exists($config['wechat_apiclient_key'])) {
            $this->private_key = file_get_contents($config['wechat_apiclient_key']);
        }
    }
    
    /**
     * 添加分账接收方 (API v3)
     * POST https://api.mch.weixin.qq.com/v3/profitsharing/receivers/add
     */
    public function addReceiver($receiver) {
        $url = 'https://api.mch.weixin.qq.com/v3/profitsharing/receivers/add';
        
        $data = array(
            'appid' => $receiver['appid'],
            'type' => 'MERCHANT_ID',
            'account' => $receiver['account'],
            'name' => $receiver['name'], // 只有是个人时才需要加密name，这里简化处理
            'relation_type' => $receiver['relation_type'] ?: 'SERVICE_PROVIDER',
        );
        
        return $this->request('POST', $url, $data);
    }
    
    /**
     * 请求分账 (API v3)
     * POST https://api.mch.weixin.qq.com/v3/profitsharing/orders
     */
    public function profitSharing($params) {
        $url = 'https://api.mch.weixin.qq.com/v3/profitsharing/orders';
        
        $receivers = array();
        foreach ($params['receivers'] as $receiver) {
            $receivers[] = array(
                'type' => 'MERCHANT_ID',
                'account' => $receiver['account'],
                'amount' => intval($receiver['amount'] * 100), // 转为分
                'description' => $receiver['description'],
            );
        }
        
        $data = array(
            'appid' => $params['appid'],
            'out_order_no' => $params['out_order_no'],
            'transaction_id' => $params['transaction_id'],
            'receivers' => $receivers,
            'unfreeze_unsplit' => true, // 分账后解冻剩余资金
        );
        
        return $this->request('POST', $url, $data);
    }
    
    /**
     * 查询分账结果 (API v3)
     * GET https://api.mch.weixin.qq.com/v3/profitsharing/orders/{out_order_no}
     */
    public function queryProfitSharing($transaction_id, $out_order_no) {
        $url = "https://api.mch.weixin.qq.com/v3/profitsharing/orders/{$out_order_no}?transaction_id={$transaction_id}";
        return $this->request('GET', $url);
    }
    
    /**
     * 解冻剩余资金 (API v3)
     * POST https://api.mch.weixin.qq.com/v3/profitsharing/orders/unfreeze
     */
    public function finishProfitSharing($transaction_id, $out_order_no) {
        $url = 'https://api.mch.weixin.qq.com/v3/profitsharing/orders/unfreeze';
        
        $data = array(
            'transaction_id' => $transaction_id,
            'out_order_no' => $out_order_no,
            'description' => '分账完成，解冻剩余资金',
        );
        
        return $this->request('POST', $url, $data);
    }
    
    /**
     * 订单完成后自动分账
     */
    public function autoSharingForOrder($order_id, $transaction_id) {
        global $_W;
        
        // 获取订单信息
        $order = pdo_get('campus_order', array('id' => $order_id));
        if (empty($order)) {
            return false;
        }
        
        // 获取商家信息
        $merchant = pdo_get('campus_merchant', array('id' => $order['merchant_id']));
        
        // 计算分账金额
        $total_amount = $order['final_price'];
        $merchant_rate = floatval($merchant['profit_rate']) ?: 70;
        $rider_rate = 20;
        $platform_rate = 100 - $merchant_rate - $rider_rate;
        
        $merchant_amount = $total_amount * $merchant_rate / 100;
        $rider_amount = $total_amount * $rider_rate / 100;
        $platform_amount = $total_amount * $platform_rate / 100;
        
        // 准备分账接收方
        $receivers = array();
        
        // 商家分账
        if ($merchant_amount > 0 && !empty($merchant['wechat_sub_mchid'])) {
            $receivers[] = array(
                'account' => $merchant['wechat_sub_mchid'],
                'amount' => $merchant_amount,
                'description' => '商家分账',
            );
        }
        
        // 骑手分账
        if ($rider_amount > 0 && !empty($order['rider_id'])) {
            $rider = pdo_get('mc_members', array('uid' => $order['rider_id']));
            if (!empty($rider['wechat_sub_mchid'])) {
                $receivers[] = array(
                    'account' => $rider['wechat_sub_mchid'],
                    'amount' => $rider_amount,
                    'description' => '骑手分账',
                );
            }
        }
        
        if (empty($receivers)) {
            return false;
        }
        
        // 执行分账
        $out_order_no = 'PS' . $order['order_no'];
        $result = $this->profitSharing(array(
            'appid' => $_W['account']['key'],
            'transaction_id' => $transaction_id,
            'out_order_no' => $out_order_no,
            'receivers' => $receivers,
        ));
        
        // 记录分账结果
        $status = 3; // 默认失败
        if (isset($result['state']) && $result['state'] == 'FINISHED') {
            $status = 2; // 完成
        } elseif (isset($result['state']) && $result['state'] == 'PROCESSING') {
            $status = 1; // 处理中
        }
        
        $sharing_data = array(
            'order_id' => $order_id,
            'order_no' => $order['order_no'],
            'transaction_id' => $transaction_id,
            'total_amount' => $total_amount,
            'merchant_amount' => $merchant_amount,
            'rider_amount' => $rider_amount,
            'platform_amount' => $platform_amount,
            'status' => $status,
            'finish_time' => ($status == 2) ? TIMESTAMP : 0,
            'create_time' => TIMESTAMP,
        );
        
        pdo_insert('campus_profit_sharing', $sharing_data);
        
        return $status != 3;
    }
    
    /**
     * 发送API v3请求
     */
    private function request($method, $url, $data = array()) {
        $body = '';
        if ($method == 'POST' && !empty($data)) {
            $body = json_encode($data);
        }
        
        $authorization = $this->getAuthorization($method, $url, $body);
        
        $headers = array(
            'Authorization: ' . $authorization,
            'Accept: application/json',
            'Content-Type: application/json',
            'User-Agent: WeEngine-CampusDelivery/1.0'
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 生产环境建议开启并配置平台证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code >= 200 && $http_code < 300) {
            return json_decode($response, true);
        } else {
            // 记录错误日志
            return array('code' => 'FAIL', 'message' => $response);
        }
    }
    
    /**
     * 生成Authorization头
     */
    private function getAuthorization($method, $url, $body) {
        $timestamp = time();
        $nonce = $this->getNonceStr();
        $url_parts = parse_url($url);
        $canonical_url = $url_parts['path'] . (!empty($url_parts['query']) ? '?' . $url_parts['query'] : '');
        
        $message = $method . "\n" .
                   $canonical_url . "\n" .
                   $timestamp . "\n" .
                   $nonce . "\n" .
                   $body . "\n";
                   
        openssl_sign($message, $signature, $this->private_key, 'sha256WithRSAEncryption');
        $signature = base64_encode($signature);
        
        return sprintf(
            'WECHATPAY2-SHA256-RSA2048 mchid="%s",nonce_str="%s",signature="%s",timestamp="%d",serial_no="%s"',
            $this->mchid, $nonce, $signature, $timestamp, $this->serial_no
        );
    }
    
    private function getNonceStr($length = 32) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
}
