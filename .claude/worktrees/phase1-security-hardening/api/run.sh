#!/bin/bash
cd /var/www/caiziyou/api

# 从 .env 加载环境变量
set -a; source /var/www/caiziyou/.env; set +a

source venv/bin/activate
export FLASK_APP=app.py
export FLASK_ENV=production
python3 app.py