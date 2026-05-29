# 菜籽游系统功能增强

## ✅ 已完成的功能

### 1. 注册功能增强
- ✅ 添加了"备注"字段（500字符，管理员可见）
- ✅ 用户注册后状态为"pending"（待审核）
- ✅ 移除自动登录，需要管理员审核通过
- ✅ 数据库添加了审核相关字段：
  - `user_note` - 用户注册备注
  - `registration_status` - 注册状态（pending/approved/rejected）
  - `reviewed_by` - 审核人ID
  - `reviewed_at` - 审核时间
  - `review_note` - 审核备注

### 2. Python后端API
位置：`/var/www/caiziyou/api/`
- ✅ `app.py` - Flask应用主文件
- ✅ `requirements.txt` - Python依赖
- ✅ `start.sh` - 启动脚本
- ✅ `/etc/systemd/system/caiziyou-api.service` - 系统服务

#### API功能：
- **用户审核**：
  - `GET /api/admin/pending-users` - 获取待审核用户
  - `POST /api/admin/approve-user/<id>` - 审核通过
  - `POST /api/admin/reject-user/<id>` - 审核拒绝
- **工具API**：
  - `POST /api/tools/json-format` - JSON格式化/压缩
  - `POST /api/tools/base64` - Base64编码/解码
  - `POST /api/tools/unit-convert` - 单位转换
  - `POST /api/tools/color-convert` - 颜色转换
- **用户认证**：
  - `POST /api/login` - 用户登录（JWT令牌）

### 3. 前端集成
- ✅ `api_tools.js` - 工具箱使用Python API
- ✅ `admin_approve.js` - 管理员审核界面
- ✅ 修改`index_app.php`引入新的JS文件

## 🚀 部署步骤

### 1. 安装Python环境
```bash
apt-get update
apt-get install -y python3-pip python3-venv
cd /var/www/caiziyou/api
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
```

### 2. 启动API服务
```bash
# 方法1：直接运行
cd /var/www/caiziyou/api
python3 app.py

# 方法2：使用systemd服务
systemctl daemon-reload
systemctl enable caiziyou-api
systemctl start caiziyou-api
systemctl status caiziyou-api
```

### 3. 测试API
```bash
# 健康检查
curl http://localhost:5000/api/health

# JSON格式化测试
curl -X POST http://localhost:5000/api/tools/json-format \
  -H "Content-Type: application/json" \
  -d '{"json": "{\"name\":\"test\"}", "action": "format"}'
```

## 🔧 功能使用说明

### 用户注册流程
1. 用户访问 `/register.php`
2. 填写表单（包括备注字段）
3. 提交后状态为"待审核"
4. 管理员在管理面板审核

### 管理员审核流程
1. 管理员登录管理面板
2. 查看待审核用户列表
3. 点击"通过"或"拒绝"
4. 填写审核备注
5. 用户状态更新

### 工具箱使用
- 所有工具现在调用Python API
- 支持JSON格式化/压缩
- Base64编码/解码
- 单位转换（px↔rem等）
- 颜色格式转换

## 📁 文件结构
```
/var/www/caiziyou/
├── api/                    # Python后端
│   ├── app.py             # Flask应用
│   ├── requirements.txt   # 依赖
│   └── start.sh          # 启动脚本
├── public/
│   ├── index_app.php     # 主应用页面
│   ├── register.php      # 注册页面（已增强）
│   ├── api_tools.js      # 工具箱API集成
│   └── admin_approve.js  # 管理员审核界面
└── includes/
    └── config.php        # 数据库配置
```

## 🔐 安全说明
1. 用户密码使用bcrypt哈希
2. API使用JWT令牌认证
3. 管理员操作需要令牌验证
4. 数据库连接使用参数化查询
5. 输入验证和转义

## 🐛 故障排除

### API无法启动
```bash
# 检查端口占用
netstat -tlnp | grep :5000

# 检查Python依赖
pip list | grep -E "Flask|mysql|jwt"

# 查看日志
journalctl -u caiziyou-api -f
```

### 数据库连接失败
1. 检查`config.php`中的数据库配置
2. 确保MySQL服务运行
3. 验证用户权限

### 前端无法调用API
1. 检查API服务是否运行
2. 查看浏览器控制台错误
3. 验证CORS配置