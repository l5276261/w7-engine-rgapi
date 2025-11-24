<?php
/**
 * 校园外卖平台 - 卸载脚本
 */
defined('IN_IA') or exit('Access Denied');

// 删除所有表
pdo_run("DROP TABLE IF EXISTS " . tablename('campus_merchant'));
pdo_run("DROP TABLE IF EXISTS " . tablename('campus_food_category'));
pdo_run("DROP TABLE IF EXISTS " . tablename('campus_food'));
pdo_run("DROP TABLE IF EXISTS " . tablename('campus_order'));
pdo_run("DROP TABLE IF EXISTS " . tablename('campus_order_item'));
pdo_run("DROP TABLE IF EXISTS " . tablename('campus_errand'));
pdo_run("DROP TABLE IF EXISTS " . tablename('campus_post'));
pdo_run("DROP TABLE IF EXISTS " . tablename('campus_comment'));
pdo_run("DROP TABLE IF EXISTS " . tablename('campus_schedule'));
pdo_run("DROP TABLE IF EXISTS " . tablename('campus_course'));
pdo_run("DROP TABLE IF EXISTS " . tablename('campus_profit_sharing'));
