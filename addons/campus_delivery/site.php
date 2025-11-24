<?php
/**
 * 校园外卖平台 - Web端控制器
 */
defined('IN_IA') or exit('Access Denied');

class Campus_deliveryModuleSite extends WeModuleSite {
    
    /**
     * 商家管理
     */
    public function doWebDashboard() {
        global $_W;
        
        // 1. 基础统计
        // 今日订单
        $today_start = strtotime(date('Y-m-d'));
        $today_orders = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('campus_order') . " WHERE uniacid = :uniacid AND create_time >= :start", array(':uniacid' => $_W['uniacid'], ':start' => $today_start));
        
        // 今日营收 (已支付订单)
        $today_revenue = pdo_fetchcolumn("SELECT SUM(final_price) FROM " . tablename('campus_order') . " WHERE uniacid = :uniacid AND status > 0 AND pay_time >= :start", array(':uniacid' => $_W['uniacid'], ':start' => $today_start));
        $today_revenue = $today_revenue ?: 0.00;
        
        // 总订单
        $total_orders = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('campus_order') . " WHERE uniacid = :uniacid", array(':uniacid' => $_W['uniacid']));
        
        // 总营收
        $total_revenue = pdo_fetchcolumn("SELECT SUM(final_price) FROM " . tablename('campus_order') . " WHERE uniacid = :uniacid AND status > 0", array(':uniacid' => $_W['uniacid']));
        $total_revenue = $total_revenue ?: 0.00;
        
        // 2. 待处理事项
        $pending_orders = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('campus_order') . " WHERE uniacid = :uniacid AND status = 1", array(':uniacid' => $_W['uniacid'])); // 待接单
        $pending_errands = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('campus_errand') . " WHERE uniacid = :uniacid AND status = 0", array(':uniacid' => $_W['uniacid'])); // 待接单
        $pending_posts = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('campus_post') . " WHERE uniacid = :uniacid AND status = 0", array(':uniacid' => $_W['uniacid'])); // 待审核
        
        // 3. 最近订单 (前5条)
        $recent_orders = pdo_fetchall("SELECT * FROM " . tablename('campus_order') . " WHERE uniacid = :uniacid ORDER BY id DESC LIMIT 5", array(':uniacid' => $_W['uniacid']));
        
        include $this->template('dashboard');
    }

    public function doWebMerchant() {
        global $_W, $_GPC;
        
        $operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
        
        if ($operation == 'display') {
            // 商家列表
            $pindex = max(1, intval($_GPC['page']));
            $psize = 20;
            
            $condition = "uniacid = :uniacid";
            $params = array(':uniacid' => $_W['uniacid']);
            
            if (!empty($_GPC['keyword'])) {
                $condition .= " AND name LIKE :keyword";
                $params[':keyword'] = "%{$_GPC['keyword']}%";
            }
            
            $list = pdo_fetchall("SELECT * FROM " . tablename('campus_merchant') . 
                " WHERE {$condition} ORDER BY id DESC LIMIT " . ($pindex - 1) * $psize . ", {$psize}", $params);
            
            $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('campus_merchant') . " WHERE {$condition}", $params);
            $pager = pagination($total, $pindex, $psize);
            
            include $this->template('merchant');
        }
        
        if ($operation == 'post') {
            // 添加/编辑商家
            $id = intval($_GPC['id']);
            
            if (checksubmit('submit')) {
                $data = array(
                    'uniacid' => $_W['uniacid'],
                    'name' => trim($_GPC['name']),
                    'logo' => trim($_GPC['logo']),
                    'description' => trim($_GPC['description']),
                    'phone' => trim($_GPC['phone']),
                    'address' => trim($_GPC['address']),
                    'latitude' => floatval($_GPC['latitude']),
                    'longitude' => floatval($_GPC['longitude']),
                    'status' => intval($_GPC['status']),
                    'profit_rate' => floatval($_GPC['profit_rate']),
                    'update_time' => TIMESTAMP,
                );
                
                if (empty($id)) {
                    $data['create_time'] = TIMESTAMP;
                    $data['rating'] = 5.0;
                    pdo_insert('campus_merchant', $data);
                    $id = pdo_insertid();
                } else {
                    pdo_update('campus_merchant', $data, array('id' => $id));
                }
                
                message('保存成功', $this->createWebUrl('merchant'), 'success');
            }
            
            if (!empty($id)) {
                $item = pdo_get('campus_merchant', array('id' => $id));
            }
            
            include $this->template('merchant_post');
        }
        
        if ($operation == 'delete') {
            $id = intval($_GPC['id']);
            pdo_delete('campus_merchant', array('id' => $id, 'uniacid' => $_W['uniacid']));
            message('删除成功', referer(), 'success');
        }
    }

    /**
     * 商品分类管理
     */
    public function doWebCategory() {
        global $_W, $_GPC;
        
        $operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
        
        if ($operation == 'display') {
            $pindex = max(1, intval($_GPC['page']));
            $psize = 20;
            
            $condition = "c.status = 1"; // Simplified for demo, usually filtered by merchant
            $params = array();
            
            if (!empty($_GPC['merchant_id'])) {
                $condition = "c.merchant_id = :mid";
                $params[':mid'] = intval($_GPC['merchant_id']);
            }
            
            $list = pdo_fetchall("SELECT c.*, m.name as merchant_name FROM " . tablename('campus_food_category') . " c " .
                "LEFT JOIN " . tablename('campus_merchant') . " m ON c.merchant_id = m.id " .
                "WHERE {$condition} ORDER BY c.sort DESC, c.id DESC LIMIT " . ($pindex - 1) * $psize . ", {$psize}", $params);
            
            $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('campus_food_category') . " c WHERE {$condition}", $params);
            $pager = pagination($total, $pindex, $psize);
            
            // 获取所有商家供筛选
            $merchants = pdo_fetchall("SELECT id, name FROM " . tablename('campus_merchant') . " WHERE uniacid = :uniacid", array(':uniacid' => $_W['uniacid']));
            
            include $this->template('category');
        }
        
        if ($operation == 'post') {
            $id = intval($_GPC['id']);
            
            if (checksubmit('submit')) {
                $data = array(
                    'merchant_id' => intval($_GPC['merchant_id']),
                    'name' => trim($_GPC['name']),
                    'sort' => intval($_GPC['sort']),
                    'status' => intval($_GPC['status']),
                );
                
                if (empty($id)) {
                    pdo_insert('campus_food_category', $data);
                } else {
                    pdo_update('campus_food_category', $data, array('id' => $id));
                }
                message('保存成功', $this->createWebUrl('category'), 'success');
            }
            
            if (!empty($id)) {
                $item = pdo_get('campus_food_category', array('id' => $id));
            }
            
            $merchants = pdo_fetchall("SELECT id, name FROM " . tablename('campus_merchant') . " WHERE uniacid = :uniacid", array(':uniacid' => $_W['uniacid']));
            
            include $this->template('category_post');
        }
        
        if ($operation == 'delete') {
            $id = intval($_GPC['id']);
            pdo_delete('campus_food_category', array('id' => $id));
            pdo_delete('campus_food', array('category_id' => $id)); // 删除分类下的商品? 或者保留
            message('删除成功', referer(), 'success');
        }
    }

    /**
     * 商品管理
     */
    public function doWebFood() {
        global $_W, $_GPC;
        
        $operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
        
        if ($operation == 'display') {
            $pindex = max(1, intval($_GPC['page']));
            $psize = 20;
            
            $condition = "f.status != -1";
            $params = array();
            
            if (!empty($_GPC['keyword'])) {
                $condition .= " AND f.name LIKE :keyword";
                $params[':keyword'] = "%{$_GPC['keyword']}%";
            }
            
            if (!empty($_GPC['merchant_id'])) {
                $condition .= " AND f.merchant_id = :mid";
                $params[':mid'] = intval($_GPC['merchant_id']);
            }
            
            $list = pdo_fetchall("SELECT f.*, m.name as merchant_name, c.name as category_name FROM " . tablename('campus_food') . " f " .
                "LEFT JOIN " . tablename('campus_merchant') . " m ON f.merchant_id = m.id " .
                "LEFT JOIN " . tablename('campus_food_category') . " c ON f.category_id = c.id " .
                "WHERE {$condition} ORDER BY f.id DESC LIMIT " . ($pindex - 1) * $psize . ", {$psize}", $params);
            
            $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('campus_food') . " f WHERE {$condition}", $params);
            $pager = pagination($total, $pindex, $psize);
            
            $merchants = pdo_fetchall("SELECT id, name FROM " . tablename('campus_merchant') . " WHERE uniacid = :uniacid", array(':uniacid' => $_W['uniacid']));
            
            include $this->template('food');
        }
        
        if ($operation == 'post') {
            $id = intval($_GPC['id']);
            
            if (checksubmit('submit')) {
                $data = array(
                    'merchant_id' => intval($_GPC['merchant_id']),
                    'category_id' => intval($_GPC['category_id']),
                    'name' => trim($_GPC['name']),
                    'image' => trim($_GPC['image']),
                    'price' => floatval($_GPC['price']),
                    'original_price' => floatval($_GPC['original_price']),
                    'description' => trim($_GPC['description']),
                    'stock' => intval($_GPC['stock']),
                    'sort' => intval($_GPC['sort']),
                    'status' => intval($_GPC['status']),
                    'update_time' => TIMESTAMP,
                );
                
                if (empty($id)) {
                    $data['create_time'] = TIMESTAMP;
                    pdo_insert('campus_food', $data);
                } else {
                    pdo_update('campus_food', $data, array('id' => $id));
                }
                message('保存成功', $this->createWebUrl('food'), 'success');
            }
            
            if (!empty($id)) {
                $item = pdo_get('campus_food', array('id' => $id));
                $categories = pdo_fetchall("SELECT * FROM " . tablename('campus_food_category') . " WHERE merchant_id = :mid", array(':mid' => $item['merchant_id']));
            }
            
            $merchants = pdo_fetchall("SELECT id, name FROM " . tablename('campus_merchant') . " WHERE uniacid = :uniacid", array(':uniacid' => $_W['uniacid']));
            
            include $this->template('food_post');
        }
        
        if ($operation == 'delete') {
            $id = intval($_GPC['id']);
            pdo_delete('campus_food', array('id' => $id));
            message('删除成功', referer(), 'success');
        }
        
        if ($operation == 'get_category') {
            $merchant_id = intval($_GPC['merchant_id']);
            $categories = pdo_fetchall("SELECT id, name FROM " . tablename('campus_food_category') . " WHERE merchant_id = :mid AND status = 1", array(':mid' => $merchant_id));
            echo json_encode($categories);
            exit;
        }
    }
    
    /**
     * 订单管理
     */
    public function doWebOrder() {
        global $_W, $_GPC;
        
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        
        $condition = "o.uniacid = :uniacid";
        $params = array(':uniacid' => $_W['uniacid']);
        
        if (!empty($_GPC['keyword'])) {
            $condition .= " AND o.order_no LIKE :keyword";
            $params[':keyword'] = "%{$_GPC['keyword']}%";
        }
        
        if (isset($_GPC['status']) && $_GPC['status'] !== '') {
            $condition .= " AND o.status = :status";
            $params[':status'] = intval($_GPC['status']);
        }
        
        $list = pdo_fetchall("SELECT o.*, m.name as merchant_name FROM " . tablename('campus_order') . " o " .
            "LEFT JOIN " . tablename('campus_merchant') . " m ON o.merchant_id = m.id " .
            "WHERE {$condition} ORDER BY o.id DESC LIMIT " . ($pindex - 1) * $psize . ", {$psize}", $params);
        
        foreach ($list as &$item) {
            $item['items'] = pdo_fetchall("SELECT * FROM " . tablename('campus_order_item') . " WHERE order_id = :id", 
                array(':id' => $item['id']));
        }
        unset($item);
        
        $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('campus_order') . " o WHERE {$condition}", $params);
        $pager = pagination($total, $pindex, $psize);
        
        include $this->template('order');
    }
    
    /**
     * 跑腿管理
     */
    public function doWebErrand() {
        global $_W, $_GPC;
        
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        
        $condition = "uniacid = :uniacid";
        $params = array(':uniacid' => $_W['uniacid']);
        
        if (isset($_GPC['status']) && $_GPC['status'] !== '') {
            $condition .= " AND status = :status";
            $params[':status'] = intval($_GPC['status']);
        }
        
        $list = pdo_fetchall("SELECT * FROM " . tablename('campus_errand') . 
            " WHERE {$condition} ORDER BY id DESC LIMIT " . ($pindex - 1) * $psize . ", {$psize}", $params);
        
        $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('campus_errand') . " WHERE {$condition}", $params);
        $pager = pagination($total, $pindex, $psize);
        
        include $this->template('errand');
    }
    
    /**
     * 校园墙管理
     */
    public function doWebPost() {
        global $_W, $_GPC;
        
        $operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
        
        if ($operation == 'display') {
            $pindex = max(1, intval($_GPC['page']));
            $psize = 20;
            
            $condition = "uniacid = :uniacid";
            $params = array(':uniacid' => $_W['uniacid']);
            
            if (isset($_GPC['status']) && $_GPC['status'] !== '') {
                $condition .= " AND status = :status";
                $params[':status'] = intval($_GPC['status']);
            }
            
            $list = pdo_fetchall("SELECT * FROM " . tablename('campus_post') . 
                " WHERE {$condition} ORDER BY id DESC LIMIT " . ($pindex - 1) * $psize . ", {$psize}", $params);
            
            $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('campus_post') . " WHERE {$condition}", $params);
            $pager = pagination($total, $pindex, $psize);
            
            include $this->template('post');
        }
        
        if ($operation == 'approve') {
            $id = intval($_GPC['id']);
            pdo_update('campus_post', array('status' => 1), array('id' => $id, 'uniacid' => $_W['uniacid']));
            message('审核通过', referer(), 'success');
        }
        
        if ($operation == 'delete') {
            $id = intval($_GPC['id']);
            pdo_update('campus_post', array('status' => 2), array('id' => $id, 'uniacid' => $_W['uniacid']));
            message('删除成功', referer(), 'success');
        }
    }
    
    /**
     * 基础设置
     */
    public function doWebSettings() {
        global $_W, $_GPC;
        
        $settings = $this->module['config'];
        
        if (checksubmit('submit')) {
            $config = array(
                'delivery_fee' => floatval($_GPC['delivery_fee']),
                'min_order_amount' => floatval($_GPC['min_order_amount']),
                'platform_profit_rate' => floatval($_GPC['platform_profit_rate']),
                'rider_profit_rate' => floatval($_GPC['rider_profit_rate']),
                'merchant_profit_rate' => floatval($_GPC['merchant_profit_rate']),
                'errand_platform_rate' => floatval($_GPC['errand_platform_rate']),
            );
            
            $this->saveSettings($config);
            message('设置保存成功', referer(), 'success');
        }
        
        include $this->template('settings');
    }
    
    /**
     * 分账配置
     */
    public function doWebProfit_config() {
        global $_W, $_GPC;
        
        $settings = $this->module['config'];
        
        if (checksubmit('submit')) {
            $config = array(
                'wechat_mchid' => trim($_GPC['wechat_mchid']),
                'wechat_api_key' => trim($_GPC['wechat_api_key']),
                'wechat_apiclient_cert' => trim($_GPC['wechat_apiclient_cert']),
                'wechat_apiclient_key' => trim($_GPC['wechat_apiclient_key']),
                'auto_sharing' => intval($_GPC['auto_sharing']),
            );
            
            $this->saveSettings(array_merge($settings, $config));
            message('分账配置保存成功', referer(), 'success');
        }
        
        include $this->template('profit_config');
    }
}
