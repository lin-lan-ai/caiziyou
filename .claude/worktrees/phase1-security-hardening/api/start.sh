#!/bin/bash
cd /var/www/caiziyou/api

# 从 .env 加载环境变量
set -a; source /var/www/caiziyou/.env; set +a

# 激活虚拟环境（如果使用）
# source venv/bin/activate

# 安装依赖
pip3 install -r requirements.txt

# 启动Flask应用
export FLASK_APP=app.py
export FLASK_ENV=production

# 使用gunicorn生产环境运行
gunicorn --bind 0.0.0.0:5000 --workers 4 --threads 2 --timeout 120 app:app

# 或者直接使用Flask开发服务器
# python3 app.py