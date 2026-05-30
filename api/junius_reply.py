#!/usr/bin/env python3
"""
Junius 实时回复守护进程 v2。
轮询 private_messages 表中发给 Junius 的未读消息，通过写入回复。
"""
import pymysql
import time
import json
import datetime

DB_CONFIG = {
    'host': 'localhost',
    'user': 'caiziyou_user',
    'password': 'CaiziYou@2026',
    'database': 'caiziyou_community_db',
    'charset': 'utf8mb4',
}

BOT_USER_ID = 21  # Junius AI助手

# 简单回复映射（后续可对接 AI）
REPLIES = {
    '你好': '你好！我是 Junius，你的 AI 助手。有什么可以帮你？',
    'hello': 'Hello! I\'m Junius, your AI assistant. How can I help you?',
    'help': '我可以帮你回答问题、聊天、查资料。你想聊什么？',
    '你是谁': '我是 Junius，名字来源于拉丁语 "卷轴"，代表知识和古典根基。我是菜籽游网站的 AI 助手。',
    '测试': '测试收到！一切正常 ✅',
}

def get_pending():
    """获取发给 Junius 的未读消息"""
    conn = pymysql.connect(**DB_CONFIG)
    cursor = conn.cursor(pymysql.cursors.DictCursor)
    cursor.execute("""
        SELECT pm.id, pm.chat_id, pm.sender_id, pm.content, u.username, u.nickname
        FROM private_messages pm
        JOIN users u ON pm.sender_id = u.id
        WHERE pm.sender_id != %s
        AND pm.is_read = 0
        AND pm.chat_id IN (
            SELECT id FROM private_chats 
            WHERE user1_id = %s OR user2_id = %s
        )
        ORDER BY pm.created_at ASC
        LIMIT 10
    """, (BOT_USER_ID, BOT_USER_ID, BOT_USER_ID))
    rows = cursor.fetchall()
    cursor.close()
    conn.close()
    return rows

def send_reply(chat_id, reply_text):
    """在同一个聊天中插入回复消息"""
    conn = pymysql.connect(**DB_CONFIG)
    cursor = conn.cursor()
    now = datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    cursor.execute("""
        INSERT INTO private_messages (chat_id, sender_id, message_type, content, is_read, created_at)
        VALUES (%s, %s, 'text', %s, 0, %s)
    """, (chat_id, BOT_USER_ID, reply_text, now))
    # 更新聊天的最后消息时间
    cursor.execute("""
        UPDATE private_chats SET last_message_at = %s WHERE id = %s
    """, (now, chat_id))
    conn.commit()
    cursor.close()
    conn.close()
    return True

def mark_read(msg_id):
    """标记消息为已读"""
    conn = pymysql.connect(**DB_CONFIG)
    cursor = conn.cursor()
    now = datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    cursor.execute("""
        UPDATE private_messages SET is_read = 1, read_at = %s WHERE id = %s
    """, (now, msg_id))
    conn.commit()
    cursor.close()
    conn.close()

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

print("[Junius v2] 实时回复守护进程启动，每3秒轮询...")

while True:
    try:
        pending = get_pending()
        for msg in pending:
            reply = get_reply(msg['content'])
            success = send_reply(msg['chat_id'], reply)
            if success:
                mark_read(msg['id'])
                print(f"  [{msg['nickname']}] {msg['content'][:30]} → 已回复")
            else:
                print(f"  [{msg['nickname']}] 回复失败")
    except Exception as e:
        print(f"  [错误] {e}")
    time.sleep(3)
