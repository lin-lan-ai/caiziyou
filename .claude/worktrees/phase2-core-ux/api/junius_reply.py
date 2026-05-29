#!/usr/bin/env python3
"""
Junius 实时回复守护进程。
轮询 junius_chat 表中 pending 的消息，通过 Flask API 写入回复。
"""
import pymysql
import time
import json
import urllib.request

DB_CONFIG = {
    'host': 'localhost',
    'user': 'caiziyou_user',
    'password': 'CaiziYou@2026',
    'database': 'caiziyou_community_db',
    'charset': 'utf8mb4',
}

REPLY_API = 'http://127.0.0.1:5000/api/junius/reply'

# 简单回复映射（后续可对接 AI）
REPLIES = {
    '你好': '你好！我是 Junius，你的 AI 助手。有什么可以帮你？',
    'hello': 'Hello! I\'m Junius, your AI assistant. How can I help you?',
    'help': '我可以帮你回答问题、聊天、查资料。你想聊什么？',
    '你是谁': '我是 Junius，名字来源于拉丁语 "卷轴"，代表知识和古典根基。我是菜籽游网站的 AI 助手。',
    '测试': '测试收到！一切正常 ✅',
    '你好Junius': '你好！我是 Junius，菜籽游的 AI 助手。有什么可以帮你的？',
}

def get_pending():
    conn = pymysql.connect(**DB_CONFIG)
    cursor = conn.cursor(pymysql.cursors.DictCursor)
    cursor.execute("""
        SELECT jc.id, jc.user_id, jc.message, u.username, u.nickname
        FROM junius_chat jc
        JOIN users u ON jc.user_id = u.id
        WHERE jc.status = 'pending'
        ORDER BY jc.created_at ASC
        LIMIT 5
    """)
    rows = cursor.fetchall()
    cursor.close()
    conn.close()
    return rows

def send_reply(msg_id, reply_text):
    data = json.dumps({'message_id': msg_id, 'reply': reply_text}).encode('utf-8')
    req = urllib.request.Request(REPLY_API, data=data, headers={'Content-Type': 'application/json'})
    resp = urllib.request.urlopen(req)
    return json.loads(resp.read())

def get_reply(message):
    """基于消息内容生成回复"""
    msg_lower = message.lower().strip()
    # 精确匹配
    if msg_lower in REPLIES:
        return REPLIES[msg_lower]
    # 包含匹配
    for key, reply in REPLIES.items():
        if key in message:
            return reply
    # 默认回复
    return f"收到你的消息: 「{message[:50]}」\n我是 Junius，后续会接入更聪明的 AI 大脑。现在先简单回复你 😊"

print("[Junius] 实时回复守护进程启动，每2秒轮询...")

while True:
    try:
        pending = get_pending()
        for msg in pending:
            reply = get_reply(msg['message'])
            result = send_reply(msg['id'], reply)
            if result.get('success'):
                print(f"  [{msg['username']}] {msg['message'][:30]} → 已回复")
            else:
                print(f"  [{msg['username']}] 回复失败: {result}")
    except Exception as e:
        print(f"  [错误] {e}")
    time.sleep(2)
