# PROJECT README: WeEngine 3.0 独立系统示例

## 📋 项目概述

**w7-engine-rgapi** 是基于 **微擎（WeEngine）3.0** 框架开发的独立系统示例项目。

### 核心特性
- ✅ 基于微擎 2.0 框架构建，可与 2.0 应用无缝串联
- ✅ 独立部署系统，支持云端容器化部署（Kubernetes/Docker）
- ✅ 示例模块 `demo_rgapi`，展示从 API 授权到支付的完整应用流程
- ✅ 支持微信公众号与小程序双端应用

---

## 🗂️ 项目架构

### 一、目录结构

```
w7-engine-rgapi/
├── framework/              # 核心框架
│   ├── bootstrap.inc.php   # 框架启动文件
│   ├── const.inc.php       # 常量定义
│   ├── library/            # 核心类库（933个文件）
│   ├── model/              # 数据模型层
│   ├── function/           # 公共函数库
│   ├── builtin/            # 内置模块
│   └── table/              # 数据表结构定义
│
├── addons/                 # 应用模块目录
│   └── demo_rgapi/         # 示例应用（本项目核心）
│       ├── manifest.xml    # 模块配置清单
│       ├── site.php        # Web端逻辑控制器
│       ├── wxapp.php       # 小程序API控制器
│       ├── module.php      # 模块基类
│       ├── processor.php   # 消息处理器
│       ├── install.php     # 安装脚本
│       ├── upgrade.php     # 升级脚本
│       ├── template/       # Web端模板文件
│       └── demo_rgapi_wxapp/  # 小程序源码
│
├── app/                    # 应用层代码
│   ├── common/             # 公共模块
│   ├── source/             # 业务逻辑控制器（33个）
│   ├── themes/             # 前端主题模板（25个）
│   └── resource/           # 静态资源（153个）
│
├── web/                    # Web管理端（696个文件）
│   ├── source/             # 控制器逻辑（40个）
│   ├── themes/             # 后台界面模板
│   └── resource/           # 后台静态资源
│
├── payment/                # 支付模块集成（26个）
├── data/                   # 数据存储目录
├── attachment/             # 上传附件目录
├── upgrade/                # 升级脚本
│
├── api.php                 # 微信消息接口入口（核心）
├── index.php               # 前端入口
├── install.php             # 系统安装程序
├── manifest.yaml           # 云端部署配置（Docker/K8s）
├── Dockerfile              # 容器镜像定义
├── .env.example            # 环境配置模板
└── README.md               # 开发说明文档
```

---

### 二、核心文件说明

| 文件 | 用途 | 重要程度 |
|------|------|---------|
| `api.php` | 微信公众号消息处理引擎入口，处理用户消息、事件推送 | ⭐⭐⭐⭐⭐ |
| `index.php` | 前端应用主入口 | ⭐⭐⭐⭐ |
| `manifest.yaml` | 云端部署配置（容器化参数、环境变量、菜单绑定） | ⭐⭐⭐⭐⭐ |
| `framework/bootstrap.inc.php` | 框架初始化入口 | ⭐⭐⭐⭐⭐ |
| `addons/demo_rgapi/site.php` | Web端业务逻辑控制器 | ⭐⭐⭐⭐ |
| `addons/demo_rgapi/wxapp.php` | 小程序API接口 | ⭐⭐⭐⭐ |
| `.env` | 本地开发环境配置（**不提交到版本库**） | ⭐⭐⭐⭐ |

---

## 🚀 快速开始

### 环境要求
- **PHP**: >= 7.0
- **MySQL**: >= 5.6
- **Redis**: >= 3.0（可选，建议生产环境开启）
- **Web服务器**: Nginx / Apache

### 本地开发步骤

#### 1️⃣ 克隆项目
```bash
git clone <repository-url>
cd w7-engine-rgapi
```

#### 2️⃣ 配置环境变量
```bash
cp .env.example .env
```

编辑 `.env` 文件：
```ini
# 数据库配置
MYSQL_HOST=127.0.0.1
MYSQL_PORT=3306
MYSQL_USERNAME=root
MYSQL_PASSWORD=your_password
MYSQL_DATABASE=we7_engine_rgapi

# 缓存引擎（mysql / redis）
PROJECT_CACHE=mysql

# Redis配置（如使用Redis缓存）
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# 安全密钥（建议随机生成）
PROJECT_AUTH_KEY=your_random_key
PROJECT_COOKIE_KEY=your_cookie_prefix

# 开发模式（生产环境设为0）
LOCAL_DEVELOP=1
APP_DEBUG=1
```

> ⚠️ **重要提示**：`.env` 文件仅用于本地开发，上传到开发者中心时必须删除。

#### 3️⃣ 数据库初始化
通过浏览器访问：
```
http://your-domain/install.php
```
按照向导完成安装。

#### 4️⃣ 访问应用

**前端访问**：
```
http://your-domain/
```

**管理后台菜单路由**：

根据 `manifest.yaml` 配置，本地开发需手动访问以下路由：

| 菜单 | URL |
|------|-----|
| 应用管理 | `http://your-domain/web/index.php?c=module&a=display&do=switch_module` |
| 站点设置 | `http://your-domain/web/index.php?c=system&a=setting&do=basic` |
| 平台管理 | `http://your-domain/web/index.php?c=account&a=manage&do=display` |

---

## 🏗️ 系统架构详解

### 1. 消息处理引擎（api.php）

`api.php` 是微信公众号消息处理的核心引擎，采用 **WeEngine 类** 实现消息分发：

```
微信服务器 → api.php → WeEngine::start()
                ↓
          verify签名 → parse消息 → analyze分析
                ↓
          关键词匹配 / 事件响应
                ↓
          调用模块处理 → 返回响应
```

**核心流程**：
1. **签名验证**：`checkSign()` 验证消息来源
2. **消息解析**：支持加密消息解密（AES）
3. **消息分析**：
   - 文本消息 → 关键词匹配
   - 事件消息 → 订阅/点击菜单/扫码等事件
   - 上下文管理 → Session锁定场景
4. **模块分发**：按优先级调用对应模块的 `receive()` 方法
5. **响应加密**：支持消息加密返回

### 2. 模块系统（addons/demo_rgapi）

**模块结构说明**：

| 文件 | 作用 | 关键方法 |
|------|------|---------|
| `manifest.xml` | 模块配置清单（菜单、权限、支持平台） | - |
| `site.php` | Web端业务控制器 | `doWeb*()` 方法 |
| `wxapp.php` | 小程序API控制器 | `doMobile*()` 方法 |
| `module.php` | 模块基类（继承 `WeModule`） | `fieldsFormDisplay()`, `fieldsFormSubmit()` |
| `processor.php` | 消息处理器（继承 `WeModuleProcessor`） | `respond()` 处理文本消息 |

**示例功能（基于 `manifest.xml`）**：
- ✅ 获取 AccessToken
- ✅ 微信授权Code换Token
- ✅ 小程序日记列表（CRUD示例）
- ✅ 微信支付集成
- ✅ 内购系统（参数配置、商品管理、订单管理）

### 3. 云端部署（manifest.yaml）

配置项说明：

```yaml
platform:
  container:
    containerPort: 80           # 容器端口
    minNum: 1                   # 最小实例数
    maxNum: 20                  # 最大实例数（自动扩缩容）
    cpu: 0.5                    # CPU核心数
    mem: 1                      # 内存（GB）
    policyType: cpu             # 扩容策略（基于CPU使用率）
    policyThreshold: 80         # 扩容阈值（80%）
  
  startParams:                  # 环境变量注入
    - name: MYSQL_HOST
      values_text: '%MYSQL_HOST%'
      module_name: w7_mysql
    - name: REDIS_HOST
      values_text: '%REDIS_HOST%'
      module_name: w7_redis
    # ...更多环境变量
  
  volumes:
    - mountPath: /home/WeEngine/attachment
      type: diskStorage          # 持久化存储卷

bindings:
  - title: 创始人端
    framework: iframe            # 嵌入式iframe框架
    menu:
      - title: 应用管理
        do: /web/index.php?c=module&a=display&do=switch_module
        icon: wxapp-setting
```

---

## 🔧 开发指南

### 替换示例应用为自己的应用

按照官方文档说明，将 `demo_rgapi` 替换为你的应用标识即可：

1. **重命名目录**：
   ```bash
   mv addons/demo_rgapi addons/your_app_name
   ```

2. **修改 `manifest.xml`**：
   ```xml
   <identifie><![CDATA[your_app_name]]></identifie>
   <name><![CDATA[你的应用名称]]></name>
   ```

3. **更新根目录 manifest.yaml`**：
   ```yaml
   application:
     identifie: your_app_name
   ```

4. **修改类文件命名空间**（若使用命名空间）。

### 本地调试技巧

1. **开启调试模式**：
   ```ini
   # .env
   APP_DEBUG=1
   LOCAL_DEVELOP=1
   ```

2. **查看日志**：
   - 微信消息日志：`data/logs/`
   - 应用日志：`data/logs/`

3. **使用微信开发者工具**：
   - 配置服务器URL为本地内网穿透地址（如 ngrok）
   - 调试 `api.php` 消息处理

### 性能优化建议

| 优化项 | 方案 | 配置 |
|--------|------|------|
| 缓存 | 使用Redis替代MySQL缓存 | `PROJECT_CACHE=redis` |
| 静态资源 | CDN加速 | 配置 `$_W['attachurl']` |
| 数据库 | 索引优化、读写分离 | - |
| 容器化 | 开启自动扩缩容 | `manifest.yaml` 中 `maxNum` |

---

## 📦 部署到云端

### 通过开发者中心部署

1. **删除本地配置文件**：
   ```bash
   rm .env
   ```

2. **打包项目**：
   ```bash
   zip -r your_app.zip . -x "*.git*" -x "data/*"
   ```

3. **上传到 [开发者中心](https://dev.w7.cc)**

4. **配置云端环境变量**：
   - 在开发者中心配置 MySQL、Redis等服务
   - 系统会自动注入 `manifest.yaml` 中定义的环境变量

5. **发布应用**。

---

## 🔗 相关资源

- **官方文档**：[https://wiki.w7.com/document/35/7302](https://wiki.w7.com/document/35/7302)
- **开发者中心**：[https://dev.w7.cc](https://dev.w7.cc)
- **微擎论坛**：获取更多模块开发经验

---

## 📝 许可协议

本项目遵循微擎团队的开源协议。

---

## 💡 总结

这是一个**生产级别的独立系统框架**，包含：
- ✅ 完整的微信公众号/小程序消息处理引擎
- ✅ 模块化架构（addons系统）
- ✅ 云原生部署支持（容器化、自动扩缩容）
- ✅ 支付系统集成示例
- ✅ 完善的开发工具链（安装向导、升级脚本）

开发者可基于此框架快速构建自己的独立应用，并发布到微擎云平台。
