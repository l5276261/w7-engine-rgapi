<?php
/**
 * 校园外卖平台 - 升级脚本
 */
defined('IN_IA') or exit('Access Denied');

// 版本号，每次升级时更新
$version = '1.0.0';

// 预留升级逻辑，后续版本迭代时在此添加数据库变更
// 示例：
// if (version_compare($current_version, '1.1.0', '<')) {
//     // 添加新字段
//     pdo_run("ALTER TABLE " . tablename('campus_merchant') . " ADD COLUMN `new_field` VARCHAR(255) DEFAULT ''");
// }
