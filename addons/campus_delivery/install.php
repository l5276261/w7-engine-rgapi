<?php
/**
 * 校园外卖平台 - 数据库安装脚本
 */
defined('IN_IA') or exit('Access Denied');

// 商家表
$sql = "CREATE TABLE IF NOT EXISTS " . tablename('campus_merchant') . " (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '商家名称',
  `logo` varchar(255) NOT NULL DEFAULT '' COMMENT '商家Logo',
  `description` text COMMENT '商家简介',
  `phone` varchar(20) NOT NULL DEFAULT '' COMMENT '联系电话',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '商家地址',
  `latitude` decimal(10,6) NOT NULL DEFAULT '0.000000' COMMENT '纬度',
  `longitude` decimal(10,6) NOT NULL DEFAULT '0.000000' COMMENT '经度',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 0:停用 1:营业 2:休息',
  `rating` decimal(3,2) NOT NULL DEFAULT '5.00' COMMENT '评分',
  `profit_rate` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '平台抽成比例(%)',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uniacid` (`uniacid`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商家表';";
pdo_run($sql);

// 商品分类表
$sql = "CREATE TABLE IF NOT EXISTS " . tablename('campus_food_category') . " (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `merchant_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '分类名称',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`id`),
  KEY `merchant_id` (`merchant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商品分类表';";
pdo_run($sql);

// 商品表
$sql = "CREATE TABLE IF NOT EXISTS " . tablename('campus_food') . " (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `merchant_id` int(11) NOT NULL DEFAULT '0',
  `category_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '商品名称',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '商品图片',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '价格',
  `original_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '原价',
  `description` text COMMENT '商品描述',
  `stock` int(11) NOT NULL DEFAULT '-1' COMMENT '库存 -1:无限',
  `sales` int(11) NOT NULL DEFAULT '0' COMMENT '销量',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 0:下架 1:上架',
  `create_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `merchant_id` (`merchant_id`),
  KEY `category_id` (`category_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商品表';";
pdo_run($sql);

// 订单表
$sql = "CREATE TABLE IF NOT EXISTS " . tablename('campus_order') . " (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) NOT NULL DEFAULT '0',
  `order_no` varchar(32) NOT NULL DEFAULT '' COMMENT '订单号',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `merchant_id` int(11) NOT NULL DEFAULT '0' COMMENT '商家ID',
  `total_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '商品总价',
  `delivery_fee` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '配送费',
  `final_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实付金额',
  `delivery_address` varchar(255) NOT NULL DEFAULT '' COMMENT '配送地址',
  `delivery_phone` varchar(20) NOT NULL DEFAULT '' COMMENT '收货电话',
  `delivery_name` varchar(50) NOT NULL DEFAULT '' COMMENT '收货人',
  `remark` text COMMENT '备注',
  `rider_id` int(11) NOT NULL DEFAULT '0' COMMENT '骑手ID',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态 0:待支付 1:待接单 2:备餐中 3:配送中 4:已完成 5:已取消',
  `pay_time` int(11) NOT NULL DEFAULT '0' COMMENT '支付时间',
  `accept_time` int(11) NOT NULL DEFAULT '0' COMMENT '接单时间',
  `complete_time` int(11) NOT NULL DEFAULT '0' COMMENT '完成时间',
  `create_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`),
  KEY `uniacid` (`uniacid`),
  KEY `user_id` (`user_id`),
  KEY `merchant_id` (`merchant_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单表';";
pdo_run($sql);

// 订单明细表
$sql = "CREATE TABLE IF NOT EXISTS " . tablename('campus_order_item') . " (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL DEFAULT '0' COMMENT '订单ID',
  `food_id` int(11) NOT NULL DEFAULT '0' COMMENT '商品ID',
  `food_name` varchar(100) NOT NULL DEFAULT '' COMMENT '商品名称',
  `food_image` varchar(255) NOT NULL DEFAULT '' COMMENT '商品图片',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '单价',
  `quantity` int(11) NOT NULL DEFAULT '1' COMMENT '数量',
  `total_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '小计',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单明细表';";
pdo_run($sql);

// 跑腿任务表
$sql = "CREATE TABLE IF NOT EXISTS " . tablename('campus_errand') . " (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) NOT NULL DEFAULT '0',
  `task_no` varchar(32) NOT NULL DEFAULT '' COMMENT '任务编号',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '发布者ID',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '任务标题',
  `description` text COMMENT '任务描述',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '任务类型 1:帮我取 2:帮我送 3:帮我买 4:其他',
  `pickup_address` varchar(255) NOT NULL DEFAULT '' COMMENT '取货地址',
  `delivery_address` varchar(255) NOT NULL DEFAULT '' COMMENT '送达地址',
  `contact_phone` varchar(20) NOT NULL DEFAULT '' COMMENT '联系电话',
  `reward` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '赏金',
  `rider_id` int(11) NOT NULL DEFAULT '0' COMMENT '接单骑手ID',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态 0:待接单 1:进行中 2:已完成 3:已取消',
  `accept_time` int(11) NOT NULL DEFAULT '0' COMMENT '接单时间',
  `complete_time` int(11) NOT NULL DEFAULT '0' COMMENT '完成时间',
  `create_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_no` (`task_no`),
  KEY `uniacid` (`uniacid`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='跑腿任务表';";
pdo_run($sql);

// 帖子表
$sql = "CREATE TABLE IF NOT EXISTS " . tablename('campus_post') . " (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '发布者ID',
  `content` text COMMENT '帖子内容',
  `images` text COMMENT '图片JSON数组',
  `category` varchar(50) NOT NULL DEFAULT '' COMMENT '分类 表白/吐槽/求助/闲聊',
  `is_anonymous` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否匿名',
  `views` int(11) NOT NULL DEFAULT '0' COMMENT '浏览量',
  `likes` int(11) NOT NULL DEFAULT '0' COMMENT '点赞数',
  `comments` int(11) NOT NULL DEFAULT '0' COMMENT '评论数',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态 0:待审核 1:已发布 2:已删除',
  `create_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uniacid` (`uniacid`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='帖子表';";
pdo_run($sql);

// 评论表
$sql = "CREATE TABLE IF NOT EXISTS " . tablename('campus_comment') . " (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL DEFAULT '0' COMMENT '帖子ID',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '评论者ID',
  `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT '父评论ID 0:一级评论',
  `content` text COMMENT '评论内容',
  `create_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='评论表';";
pdo_run($sql);

// 课程表
$sql = "CREATE TABLE IF NOT EXISTS " . tablename('campus_schedule') . " (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '创建者ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '课程表名称',
  `semester` varchar(50) NOT NULL DEFAULT '' COMMENT '学期',
  `share_code` varchar(16) NOT NULL DEFAULT '' COMMENT '分享码',
  `is_public` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否公开',
  `use_count` int(11) NOT NULL DEFAULT '0' COMMENT '被应用次数',
  `create_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `share_code` (`share_code`),
  KEY `uniacid` (`uniacid`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='课程表';";
pdo_run($sql);

// 课程明细
$sql = "CREATE TABLE IF NOT EXISTS " . tablename('campus_course') . " (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_id` int(11) NOT NULL DEFAULT '0' COMMENT '课程表ID',
  `course_name` varchar(100) NOT NULL DEFAULT '' COMMENT '课程名称',
  `teacher` varchar(50) NOT NULL DEFAULT '' COMMENT '教师',
  `location` varchar(100) NOT NULL DEFAULT '' COMMENT '上课地点',
  `week_day` tinyint(1) NOT NULL DEFAULT '1' COMMENT '星期几 1-7',
  `start_section` tinyint(2) NOT NULL DEFAULT '1' COMMENT '开始节次',
  `end_section` tinyint(2) NOT NULL DEFAULT '1' COMMENT '结束节次',
  `weeks` varchar(100) NOT NULL DEFAULT '' COMMENT '周次 如:1-16',
  PRIMARY KEY (`id`),
  KEY `schedule_id` (`schedule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='课程明细';";
pdo_run($sql);

// 分账记录表
$sql = "CREATE TABLE IF NOT EXISTS " . tablename('campus_profit_sharing') . " (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL DEFAULT '0' COMMENT '订单ID',
  `order_no` varchar(32) NOT NULL DEFAULT '' COMMENT '订单号',
  `transaction_id` varchar(64) NOT NULL DEFAULT '' COMMENT '微信订单号',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '总金额',
  `merchant_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '商家分账',
  `rider_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '骑手分账',
  `platform_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '平台分账',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '分账状态 0:待分账 1:分账中 2:已完成 3:失败',
  `finish_time` int(11) NOT NULL DEFAULT '0' COMMENT '完成时间',
  `create_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `order_no` (`order_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='分账记录表';";
pdo_run($sql);

// 初始化默认配置
$default_config = array(
    'delivery_fee' => 3.00,
    'min_order_amount' => 10.00,
    'platform_profit_rate' => 10.00,
    'rider_profit_rate' => 20.00,
    'merchant_profit_rate' => 70.00,
    'errand_platform_rate' => 10.00,
);

// 将配置保存到模块设置中
// 这些配置会在module.php的settingsDisplay方法中使用
