#!/bin/bash
cd /var/www/caiziyou/api
source venv/bin/activate
export FLASK_APP=app.py
export FLASK_ENV=production
# 从 .env 文件读取（app.py 自动加载）
python3 app.py