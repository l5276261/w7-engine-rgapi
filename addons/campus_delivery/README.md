# 校园外卖平台开发文档

## 项目简介

基于微擎（WeEngine）框架开发的校园外卖平台，整合外卖、跑腿、校园墙、课程表等校园生活服务功能，支持微信支付分账。

## 已完成功能模块

### 1. 基础架构 ✅

#### 模块配置
- `manifest.xml`: 模块清单配置，定义菜单和页面入口
- `module.php`: 模块基类，处理...配置管理
- `install.php`: 数据库安装脚本（11张核心表）
- `uninstall.php`: 卸载脚本
- `upgrade.php`: 升级脚本框架

#### 数据库表结构（11张表）
1. **campus_merchant** - 商家表
2. **campus_food_category** - 商品分类表
3. **campus_food** - 商品表
4. **campus_order** - 订单表
5. **campus_order_item** - 订单明细表
6. **campus_errand** - 跑腿任务表
7. **campus_post** - 帖子表
8. **campus_comment** - 评论表
9. **campus_schedule** - 课程表
10. **campus_course** - 课程明细
11. **campus_profit_sharing** - 分账记录表

### 2. Web管理端（site.php）✅

已实现的管理功能：
- `doWebMerchant()` - 商家管理（列表、添加、编辑、删除）
- `doWebOrder()` - 订单管理（列表、详情、筛选）
- `doWebErrand()` - 跑腿管理
- `doWebPost()` - 校园墙管理（帖子审核、删除）
- `doWebSettings()` - 基础设置
- `doWebProfit_config()` - 分账配置

### 3. 小程序API（wxapp.php）✅

#### 外卖模块接口
- `doMobileMerchantList` - 商家列表
- `doMobileMerchantDetail` - 商家详情
- `doMobileFoodList` - 商品列表
- `doMobileOrderCreate` - 创建订单
- `doMobileOrderList` - 我的订单
- `doMobileOrderDetail` - 订单详情
- `doMobileOrderCancel` - 取消订单

#### 跑腿模块接口
- `doMobileErrandCreate` - 发布任务
- `doMobileErrandList` - 任务列表
- `doMobileErrandAccept` - 接单
- `doMobileErrandComplete` - 完成任务
- `doMobileMyErrands` - 我的跑腿记录

#### 校园墙模块接口
- `doMobilePostCreate` - 发布帖子
- `doMobilePostList` - 帖子列表
- `doMobilePostDetail` - 帖子详情
- `doMobilePostLike` - 点赞
- `doMobileCommentCreate` - 发表评论
- `doMobileCommentList` - 评论列表

#### 课程表模块接口
- `doMobileScheduleCreate` - 创建课程表
- `doMobileSchedule` - 我的课程表
- `doMobileCourseAdd` - 添加课程
- `doMobileCourseEdit` - 编辑课程
- `doMobileCourseDelete` - 删除课程
- `doMobileScheduleShare` - 生成分享码
- `doMobileScheduleImport` - 导入课程表（通过分享码）

### 4. 微信分账服务 ✅

**文件**: `inc/service/ProfitSharingService.php`

核心功能：
- `addReceiver()` - 添加分账接收方
- `profitSharing()` - 执行分账
- `queryProfitSharing()` - 查询分账结果
- `finishProfitSharing()` - 完结分账
- `autoSharingForOrder()` - 订单完成后自动分账

分账比例（可配置）：
- 外卖订单：商家70% + 骑手20% + 平台10%
- 跑腿任务：骑手90% + 平台10%

### 5. Web端模板示例 ✅
- `template/settings.html` - 系统配置页面
- `template/merchant.html` - 商家列表页面

---

## 部署说明

### 1. 上传模块
将 `addons/campus_delivery/` 目录上传到微擎框架的 `addons/` 目录下。

### 2. 安装模块
在微擎管理后台：
1. 进入"模块管理"
2. 找到"校园外卖平台"模块
3. 点击"安装"
4. 系统会自动执行 `install.php` 创建数据库表

### 3. 配置参数
安装后进入模块设置页面，配置：
- 配送费
- 最低起送金额
- 商家/骑手/平台分成比例
- 微信分账参数（商户号、API密钥、证书等）

### 4. 添加示例数据
- 添加商家
- 添加商品分类和商品
- 测试订单流程

---

## API接口调用示例

### 小程序端调用示例

#### 获取商家列表
```javascript
wx.request({
  url: 'https://your-domain/app/index.php',
  data: {
    i: uniacid,
    c: 'entry',
    m: 'campus_delivery',
    do: 'MobileMerchantList',
    page: 1
  },
  success: function(res) {
    console.log(res.data.data.list);
  }
})
```

#### 创建订单
```javascript
wx.request({
  url: 'https://your-domain/app/index.php',
  method: 'POST',
  data: {
    i: uniacid,
    c: 'entry',
    m: 'campus_delivery',
    do: 'MobileOrderCreate',
    merchant_id: 1,
    items: JSON.stringify([
      {food_id: 1, quantity: 2},
      {food_id: 2, quantity: 1}
    ]),
    delivery_address: '宿舍楼A栋101',
    delivery_name: '张三',
    delivery_phone: '13800138000',
    remark: '少放辣'
  },
  success: function(res) {
    if (res.data.errno == 0) {
      // 调起微信支付
      wx.requestPayment({
        // 支付参数
      })
    }
  }
})
```

---

## 下一步开发建议

### 1. 完善Web端模板
创建更多管理页面模板：
- `merchant_post.html` - 商家编辑页面
- `order.html` - 订单列表页面
- `errand.html` - 跑腿管理页面
- `post.html` - 校园墙管理页面
- `profit_config.html` - 分账配置页面

### 2. 微信支付集成
- 集成微擎内置的微信支付模块
- 实现支付回调处理
- 订单状态自动更新

### 3. 订阅消息推送
配置小程序订阅消息：
- 订单状态变更通知
- 跑腿任务接单通知
- 帖子审核结果通知

### 4. 骑手端功能
开发骑手专用页面：
- 待接单列表
- 配送中订单
- 收益统计

### 5. 数据统计
添加数据分析功能：
- 订单统计
- 商家排行
- 用户活跃度
- 收益报表

### 6. 小程序前端开发
基于API接口开发小程序前端界面：
- 外卖点餐流程
- 跑腿发布/接单
- 校园墙社交
- 课程表管理

---

## 技术架构

### 后端框架
- **WeEngine 3.0** - 微擎框架
- **PHP** >= 7.0
- **MySQL** >= 5.6
- **Redis**（可选，用于缓存）

### 前端技术
- 微信小程序原生开发
- WeUI组件库

### 支付与分账
- 微信支付V3 API
- 微信分账API

---

## 联系方式

如有问题请查阅微擎官方文档：https://wiki.w7.com
