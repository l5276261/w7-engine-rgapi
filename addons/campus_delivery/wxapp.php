<?php
/**
 * 校园外卖平台 - 小程序API控制器
 */
defined('IN_IA') or exit('Access Denied');

class Campus_deliveryModuleWxapp extends WeModuleWxapp {
    
    /**
     * 商家列表
     */
    public function doMobileMerchantList() {
        global $_W, $_GPC;
        
        $page = max(1, intval($_GPC['page']));
        $pagesize = 20;
        
        $condition = "uniacid = :uniacid AND status = 1";
        $params = array(':uniacid' => $_W['uniacid']);
        
        if (!empty($_GPC['keyword'])) {
            $condition .= " AND name LIKE :keyword";
            $params[':keyword'] = "%{$_GPC['keyword']}%";
        }
        
        $list = pdo_fetchall("SELECT id, name, logo, description, address, rating FROM " . tablename('campus_merchant') . 
            " WHERE {$condition} ORDER BY rating DESC, id DESC LIMIT " . ($page - 1) * $pagesize . ", {$pagesize}", $params);
        
        $this->result(0, '获取成功', array(
            'list' => $list,
            'page' => $page,
        ));
    }
    
    /**
     * 商家详情
     */
    public function doMobileMerchantDetail() {
        global $_W, $_GPC;
        
        $id = intval($_GPC['id']);
        $merchant = pdo_get('campus_merchant', array('id' => $id, 'uniacid' => $_W['uniacid']));
        
        if (empty($merchant)) {
            $this->result(1, '商家不存在');
        }
        
        // 获取商品分类
        $categories = pdo_fetchall("SELECT * FROM " . tablename('campus_food_category') . 
            " WHERE merchant_id = :mid AND status = 1 ORDER BY sort ASC", array(':mid' => $id));
        
        $this->result(0, '获取成功', array(
            'merchant' => $merchant,
            'categories' => $categories,
        ));
    }
    
    /**
     * 商品列表
     */
    public function doMobileFoodList() {
        global $_W, $_GPC;
        
        $merchant_id = intval($_GPC['merchant_id']);
        $category_id = intval($_GPC['category_id']);
        
        $condition = "merchant_id = :mid AND status = 1";
        $params = array(':mid' => $merchant_id);
        
        if ($category_id > 0) {
            $condition .= " AND category_id = :cid";
            $params[':cid'] = $category_id;
        }
        
        $list = pdo_fetchall("SELECT * FROM " . tablename('campus_food') . 
            " WHERE {$condition} ORDER BY sort ASC, id DESC", $params);
        
        $this->result(0, '获取成功', array('list' => $list));
    }
    
    /**
     * 创建订单
     */
    public function doMobileOrderCreate() {
        global $_W, $_GPC;
        
        $user_id = intval($_W['member']['uid']);
        $merchant_id = intval($_GPC['merchant_id']);
        $items = json_decode(html_entity_decode($_GPC['items']), true);
        $delivery_address = trim($_GPC['delivery_address']);
        $delivery_name = trim($_GPC['delivery_name']);
        $delivery_phone = trim($_GPC['delivery_phone']);
        $remark = trim($_GPC['remark']);
        
        if (empty($items) || !is_array($items)) {
            $this->result(1, '请选择商品');
        }
        
        // 计算订单金额
        $total_price = 0;
        $order_items = array();
        
        foreach ($items as $item) {
            $food = pdo_get('campus_food', array('id' => $item['food_id']));
            if (empty($food) || $food['status'] != 1) {
                $this->result(1, '商品不存在或已下架');
            }
            
            $quantity = intval($item['quantity']);
            $item_total = $food['price'] * $quantity;
            $total_price += $item_total;
            
            $order_items[] = array(
                'food_id' => $food['id'],
                'food_name' => $food['name'],
                'food_image' => $food['image'],
                'price' => $food['price'],
                'quantity' => $quantity,
                'total_price' => $item_total,
            );
        }
        
        // 配送费
        $settings = $this->module['config'];
        $delivery_fee = floatval($settings['delivery_fee']) ?: 3.00;
        $final_price = $total_price + $delivery_fee;
        
        // 生成订单号
        $order_no = 'FO' . date('YmdHis') . random(6, true);
        
        // 创建订单
        $order_data = array(
            'uniacid' => $_W['uniacid'],
            'order_no' => $order_no,
            'user_id' => $user_id,
            'merchant_id' => $merchant_id,
            'total_price' => $total_price,
            'delivery_fee' => $delivery_fee,
            'final_price' => $final_price,
            'delivery_address' => $delivery_address,
            'delivery_name' => $delivery_name,
            'delivery_phone' => $delivery_phone,
            'remark' => $remark,
            'status' => 0,
            'create_time' => TIMESTAMP,
        );
        
        pdo_insert('campus_order', $order_data);
        $order_id = pdo_insertid();
        
        // 插入订单明细
        foreach ($order_items as &$oi) {
            $oi['order_id'] = $order_id;
            pdo_insert('campus_order_item', $oi);
        }
        
        $this->result(0, '订单创建成功', array(
            'order_id' => $order_id,
            'order_no' => $order_no,
            'final_price' => $final_price,
        ));
    }
    
    /**
     * 我的订单列表
     */
    public function doMobileOrderList() {
        global $_W, $_GPC;
        
        $user_id = intval($_W['member']['uid']);
        $page = max(1, intval($_GPC['page']));
        $pagesize = 10;
        
        $condition = "o.user_id = :uid";
        $params = array(':uid' => $user_id);
        
        if (isset($_GPC['status']) && $_GPC['status'] !== '') {
            $condition .= " AND o.status = :status";
            $params[':status'] = intval($_GPC['status']);
        }
        
        $list = pdo_fetchall("SELECT o.*, m.name as merchant_name, m.logo as merchant_logo FROM " . 
            tablename('campus_order') . " o LEFT JOIN " . tablename('campus_merchant') . " m ON o.merchant_id = m.id " .
            "WHERE {$condition} ORDER BY o.id DESC LIMIT " . ($page - 1) * $pagesize . ", {$pagesize}", $params);
        
        foreach ($list as &$order) {
            $order['items'] = pdo_fetchall("SELECT * FROM " . tablename('campus_order_item') . " WHERE order_id = :oid", 
                array(':oid' => $order['id']));
        }
        
        $this->result(0, '获取成功', array('list' => $list));
    }
    
    /**
     * 订单详情
     */
    public function doMobileOrderDetail() {
        global $_W, $_GPC;
        
        $order_id = intval($_GPC['id']);
        $user_id = intval($_W['member']['uid']);
        
        $order = pdo_get('campus_order', array('id' => $order_id, 'user_id' => $user_id));
        if (empty($order)) {
            $this->result(1, '订单不存在');
        }
        
        $merchant = pdo_get('campus_merchant', array('id' => $order['merchant_id']));
        $order['merchant_name'] = $merchant['name'];
        $order['merchant_logo'] = $merchant['logo'];
        
        $order['items'] = pdo_fetchall("SELECT * FROM " . tablename('campus_order_item') . " WHERE order_id = :oid", 
            array(':oid' => $order_id));
        
        $this->result(0, '获取成功', $order);
    }
    
    /**
     * 取消订单
     */
    public function doMobileOrderCancel() {
        global $_W, $_GPC;
        
        $order_id = intval($_GPC['id']);
        $user_id = intval($_W['member']['uid']);
        
        $order = pdo_get('campus_order', array('id' => $order_id, 'user_id' => $user_id));
        if (empty($order)) {
            $this->result(1, '订单不存在');
        }
        
        if ($order['status'] > 2) {
            $this->result(1, '当前状态不允许取消');
        }
        
        pdo_update('campus_order', array('status' => 5), array('id' => $order_id));
        
        $this->result(0, '取消成功');
    }
    
    /**
     * 发布跑腿任务
     */
    public function doMobileErrandCreate() {
        global $_W, $_GPC;
        
        $user_id = intval($_W['member']['uid']);
        
        $data = array(
            'uniacid' => $_W['uniacid'],
            'task_no' => 'ER' . date('YmdHis') . random(6, true),
            'user_id' => $user_id,
            'title' => trim($_GPC['title']),
            'description' => trim($_GPC['description']),
            'type' => intval($_GPC['type']),
            'pickup_address' => trim($_GPC['pickup_address']),
            'delivery_address' => trim($_GPC['delivery_address']),
            'contact_phone' => trim($_GPC['contact_phone']),
            'reward' => floatval($_GPC['reward']),
            'status' => 0,
            'create_time' => TIMESTAMP,
        );
        
        pdo_insert('campus_errand', $data);
        $task_id = pdo_insertid();
        
        $this->result(0, '发布成功', array('task_id' => $task_id));
    }
    
    /**
     * 跑腿任务列表
     */
    public function doMobileErrandList() {
        global $_W, $_GPC;
        
        $page = max(1, intval($_GPC['page']));
        $pagesize = 20;
        
        $condition = "uniacid = :uniacid AND status = 0";
        $params = array(':uniacid' => $_W['uniacid']);
        
        $list = pdo_fetchall("SELECT * FROM " . tablename('campus_errand') . 
            " WHERE {$condition} ORDER BY id DESC LIMIT " . ($page - 1) * $pagesize . ", {$pagesize}", $params);
        
        $this->result(0, '获取成功', array('list' => $list));
    }
    
    /**
     * 接单
     */
    public function doMobileErrandAccept() {
        global $_W, $_GPC;
        
        $task_id = intval($_GPC['id']);
        $rider_id = intval($_W['member']['uid']);
        
        $task = pdo_get('campus_errand', array('id' => $task_id));
        if (empty($task) || $task['status'] != 0) {
            $this->result(1, '任务不存在或已被接单');
        }
        
        pdo_update('campus_errand', array(
            'rider_id' => $rider_id,
            'status' => 1,
            'accept_time' => TIMESTAMP,
        ), array('id' => $task_id));
        
        $this->result(0, '接单成功');
    }
    
    /**
     * 完成任务
     */
    public function doMobileErrandComplete() {
        global $_W, $_GPC;
        
        $task_id = intval($_GPC['id']);
        $rider_id = intval($_W['member']['uid']);
        
        $task = pdo_get('campus_errand', array('id' => $task_id, 'rider_id' => $rider_id]);
        if (empty($task)) {
            $this->result(1, '任务不存在');
        }
        
        pdo_update('campus_errand', array(
            'status' => 2,
            'complete_time' => TIMESTAMP,
        ), array('id' => $task_id));
        
        $this->result(0, '任务完成');
    }
    
    /**
     * 我的跑腿记录
     */
    public function doMobileMyErrands() {
        global $_W, $_GPC;
        
        $user_id = intval($_W['member']['uid']);
        $type = trim($_GPC['type']); // publish: 我发布的, accept: 我接的
        
        $condition = "uniacid = :uniacid";
        $params = array(':uniacid' => $_W['uniacid']);
        
        if ($type == 'publish') {
            $condition .= " AND user_id = :uid";
            $params[':uid'] = $user_id;
        } else {
            $condition .= " AND rider_id = :uid";
            $params[':uid'] = $user_id;
        }
        
        $list = pdo_fetchall("SELECT * FROM " . tablename('campus_errand') . 
            " WHERE {$condition} ORDER BY id DESC", $params);
        
        $this->result(0, '获取成功', array('list' => $list));
    }
    
    /**
     * 发布帖子
     */
    public function doMobilePostCreate() {
        global $_W, $_GPC;
        
        $user_id = intval($_W['member']['uid']);
        
        $data = array(
            'uniacid' => $_W['uniacid'],
            'user_id' => $user_id,
            'content' => trim($_GPC['content']),
            'images' => json_encode($_GPC['images']),
            'category' => trim($_GPC['category']),
            'is_anonymous' => intval($_GPC['is_anonymous']),
            'status' => 0, // 待审核
            'create_time' => TIMESTAMP,
        );
        
        pdo_insert('campus_post', $data);
        $post_id = pdo_insertid();
        
        $this->result(0, '发布成功，等待审核', array('post_id' => $post_id));
    }
    
    /**
     * 帖子列表
     */
    public function doMobilePostList() {
        global $_W, $_GPC;
        
        $page = max(1, intval($_GPC['page']));
        $pagesize = 20;
        
        $condition = "uniacid = :uniacid AND status = 1";
        $params = array(':uniacid' => $_W['uniacid']);
        
        if (!empty($_GPC['category'])) {
            $condition .= " AND category = :category";
            $params[':category'] = trim($_GPC['category']);
        }
        
        $list = pdo_fetchall("SELECT * FROM " . tablename('campus_post') . 
            " WHERE {$condition} ORDER BY id DESC LIMIT " . ($page - 1) * $pagesize . ", {$pagesize}", $params);
        
        foreach ($list as &$post) {
            $post['images'] = json_decode($post['images'], true);
        }
        
        $this->result(0, '获取成功', array('list' => $list));
    }
    
    /**
     * 帖子详情
     */
    public function doMobilePostDetail() {
        global $_W, $_GPC;
        
        $post_id = intval($_GPC['id']);
        
        $post = pdo_get('campus_post', array('id' => $post_id, 'uniacid' => $_W['uniacid']));
        if (empty($post)) {
            $this->result(1, '帖子不存在');
        }
        
        $post['images'] = json_decode($post['images'], true);
        
        // 增加浏览量
        pdo_update('campus_post', array('views' => $post['views'] + 1), array('id' => $post_id));
        
        $this->result(0, '获取成功', $post);
    }
    
    /**
     * 点赞
     */
    public function doMobilePostLike() {
        global $_W, $_GPC;
        
        $post_id = intval($_GPC['id']);
        
        $post = pdo_get('campus_post', array('id' => $post_id));
        if (empty($post)) {
            $this->result(1, '帖子不存在');
        }
        
        pdo_update('campus_post', array('likes' => $post['likes'] + 1), array('id' => $post_id));
        
        $this->result(0, '点赞成功');
    }
    
    /**
     * 发表评论
     */
    public function doMobileCommentCreate() {
        global $_W, $_GPC;
        
        $user_id = intval($_W['member']['uid']);
        
        $data = array(
            'post_id' => intval($_GPC['post_id']),
            'user_id' => $user_id,
            'parent_id' => intval($_GPC['parent_id']),
            'content' => trim($_GPC['content']),
            'create_time' => TIMESTAMP,
        );
        
        pdo_insert('campus_comment', $data);
        
        // 更新评论数
        $post = pdo_get('campus_post', array('id' => $data['post_id']));
        pdo_update('campus_post', array('comments' => $post['comments'] + 1), array('id' => $data['post_id']));
        
        $this->result(0, '评论成功');
    }
    
    /**
     * 评论列表
     */
    public function doMobileCommentList() {
        global $_W, $_GPC;
        
        $post_id = intval($_GPC['post_id']);
        
        $list = pdo_fetchall("SELECT * FROM " . tablename('campus_comment') . 
            " WHERE post_id = :pid ORDER BY id ASC", array(':pid' => $post_id));
        
        $this->result(0, '获取成功', array('list' => $list));
    }
    
    /**
     * 创建课程表
     */
    public function doMobileScheduleCreate() {
        global $_W, $_GPC;
        
        $user_id = intval($_W['member']['uid']);
        
        $data = array(
            'uniacid' => $_W['uniacid'],
            'user_id' => $user_id,
            'name' => trim($_GPC['name']),
            'semester' => trim($_GPC['semester']),
            'share_code' => strtoupper(random(8)),
            'is_public' => intval($_GPC['is_public']),
            'create_time' => TIMESTAMP,
        );
        
        pdo_insert('campus_schedule', $data);
        $schedule_id = pdo_insertid();
        
        $this->result(0, '创建成功', array('schedule_id' => $schedule_id));
    }
    
    /**
     * 我的课程表
     */
    public function doMobileSchedule() {
        global $_W, $_GPC;
        
        $user_id = intval($_W['member']['uid']);
        
        $schedule = pdo_get('campus_schedule', array('user_id' => $user_id, 'uniacid' => $_W['uniacid']));
        if (empty($schedule)) {
            $this->result(0, '暂无课程表', array('schedule' => null, 'courses' => array()));
        }
        
        $courses = pdo_fetchall("SELECT * FROM " . tablename('campus_course') . 
            " WHERE schedule_id = :sid ORDER BY week_day ASC, start_section ASC", 
            array(':sid' => $schedule['id']));
        
        $this->result(0, '获取成功', array('schedule' => $schedule, 'courses' => $courses));
    }
    
    /**
     * 添加课程
     */
    public function doMobileCourseAdd() {
        global $_W, $_GPC;
        
        $data = array(
            'schedule_id' => intval($_GPC['schedule_id']),
            'course_name' => trim($_GPC['course_name']),
            'teacher' => trim($_GPC['teacher']),
            'location' => trim($_GPC['location']),
            'week_day' => intval($_GPC['week_day']),
            'start_section' => intval($_GPC['start_section']),
            'end_section' => intval($_GPC['end_section']),
            'weeks' => trim($_GPC['weeks']),
        );
        
        pdo_insert('campus_course', $data);
        
        $this->result(0, '添加成功');
    }
    
    /**
     * 编辑课程
     */
    public function doMobileCourseEdit() {
        global $_W, $_GPC;
        
        $course_id = intval($_GPC['id']);
        
        $data = array(
            'course_name' => trim($_GPC['course_name']),
            'teacher' => trim($_GPC['teacher']),
            'location' => trim($_GPC['location']),
            'week_day' => intval($_GPC['week_day']),
            'start_section' => intval($_GPC['start_section']),
            'end_section' => intval($_GPC['end_section']),
            'weeks' => trim($_GPC['weeks']),
        );
        
        pdo_update('campus_course', $data, array('id' => $course_id));
        
        $this->result(0, '更新成功');
    }
    
    /**
     * 删除课程
     */
    public function doMobileCourseDelete() {
        global $_W, $_GPC;
        
        $course_id = intval($_GPC['id']);
        pdo_delete('campus_course', array('id' => $course_id));
        
        $this->result(0, '删除成功');
    }
    
    /**
     * 生成分享码
     */
    public function doMobileScheduleShare() {
        global $_W, $_GPC;
        
        $user_id = intval($_W['member']['uid']);
        
        $schedule = pdo_get('campus_schedule', array('user_id' => $user_id, 'uniacid' => $_W['uniacid']));
        if (empty($schedule)) {
            $this->result(1, '课程表不存在');
        }
        
        pdo_update('campus_schedule', array('is_public' => 1), array('id' => $schedule['id']));
        
        $this->result(0, '生成成功', array('share_code' => $schedule['share_code']));
    }
    
    /**
     * 导入课程表（通过分享码）
     */
    public function doMobileScheduleImport() {
        global $_W, $_GPC;
        
        $user_id = intval($_W['member']['uid']);
        $share_code = strtoupper(trim($_GPC['share_code']));
        
        $source_schedule = pdo_get('campus_schedule', array('share_code' => $share_code, 'is_public' => 1));
        if (empty($source_schedule)) {
            $this->result(1, '分享码无效');
        }
        
        // 检查是否已有课程表
        $my_schedule = pdo_get('campus_schedule', array('user_id' => $user_id, 'uniacid' => $_W['uniacid']));
        
        if (empty($my_schedule)) {
            // 创建新课程表
            $new_schedule = array(
                'uniacid' => $_W['uniacid'],
                'user_id' => $user_id,
                'name' => $source_schedule['name'],
                'semester' => $source_schedule['semester'],
                'share_code' => strtoupper(random(8)),
                'is_public' => 0,
                'create_time' => TIMESTAMP,
            );
            pdo_insert('campus_schedule', $new_schedule);
            $my_schedule_id = pdo_insertid();
        } else {
            // 清空现有课程
            pdo_delete('campus_course', array('schedule_id' => $my_schedule['id']));
            $my_schedule_id = $my_schedule['id'];
        }
        
        // 复制课程
        $courses = pdo_fetchall("SELECT * FROM " . tablename('campus_course') . 
            " WHERE schedule_id = :sid", array(':sid' => $source_schedule['id']));
        
        foreach ($courses as $course) {
            unset($course['id']);
            $course['schedule_id'] = $my_schedule_id;
            pdo_insert('campus_course', $course);
        }
        
        // 增加使用次数
        pdo_update('campus_schedule', 
            array('use_count' => $source_schedule['use_count'] + 1), 
            array('id' => $source_schedule['id']));
        
        $this->result(0, '导入成功');
    }
    
    /**
     * 统一返回结果
     */
    private function result($errno, $message, $data = array()) {
        $result = array(
            'errno' => $errno,
            'message' => $message,
            'data' => $data,
        );
        die(json_encode($result));
    }
}
