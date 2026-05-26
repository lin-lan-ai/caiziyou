#!/bin/bash
cd /var/www/caiziyou/api
source venv/bin/activate
export FLASK_APP=app.py
export FLASK_ENV=production
export SECRET_KEY="caiziyou-secret-key-2026"
python3 app.py