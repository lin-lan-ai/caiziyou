#!/usr/bin/env python3
"""
菜籽游 清理脚本
每15分钟通过 cron 调用，清除：
- 超过24小时的会话记录（private_chats + private_messages）
- 超过72小时的日志记录（account log files）
"""
import os
import sys
import glob
import time
import logging
import mysql.connector
from datetime import datetime, timedelta

logging.basicConfig(level=logging.INFO, format='%(asctime)s [%(levelname)s] %(message)s')
logger = logging.getLogger('cleanup')

DB_HOST = os.environ.get('DB_HOST', 'localhost')
DB_USER = os.environ.get('DB_USER', 'caiziyou')
DB_PASS = os.environ.get('DB_PASS', '')
DB_NAME = os.environ.get('DB_NAME', 'caiziyou_community_db')
DB = {'host': 'localhost', 'user': 'caiziyou_user', 'password': 'CaiziYou@2026', 'database': 'caiziyou_community_db', 'charset': 'utf8mb4'}
LOG_BASE = '/var/log/caiziyou/accounts'


def get_db():
    try:
        conn = mysql.connector.connect(**DB)
        return conn
    except Exception as e:
        logger.error(f"数据库连接失败: {e}")
        return None


def clean_chats():
    """删除超过24小时的会话记录"""
    conn = get_db()
    if not conn:
        return
    try:
        cursor = conn.cursor()
        cutoff = datetime.now() - timedelta(hours=24)
        logger.info(f"清理 {cutoff} 之前的聊天记录")
        # 先找要删除的 chat_ids
        cursor.execute("SELECT id FROM private_chats WHERE last_message_at IS NULL OR last_message_at < %s", (cutoff,))
        chat_ids = [row[0] for row in cursor.fetchall()]
        if not chat_ids:
            logger.info("没有需要清理的聊天记录")
            cursor.close()
            conn.close()
            return
        # 删除消息
        cursor.execute("DELETE FROM private_messages WHERE chat_id IN ({})".format(
            ','.join(['%s'] * len(chat_ids))
        ), chat_ids)
        msg_deleted = cursor.rowcount
        # 删除会话
        cursor.execute("DELETE FROM private_chats WHERE id IN ({})".format(
            ','.join(['%s'] * len(chat_ids))
        ), chat_ids)
        chat_deleted = cursor.rowcount
        conn.commit()
        logger.info(f"删除了 {chat_deleted} 个会话, {msg_deleted} 条消息")
        cursor.close()
        conn.close()
    except Exception as e:
        logger.error(f"清理聊天记录失败: {e}")


def clean_logs():
    """删除超过72小时的账户日志文件"""
    cutoff = time.time() - 72 * 3600
    total = 0
    for root, dirs, files in os.walk(LOG_BASE):
        for f in files:
            fp = os.path.join(root, f)
            try:
                mtime = os.path.getmtime(fp)
                if mtime < cutoff:
                    os.remove(fp)
                    total += 1
                    logger.debug(f"已删除日志: {fp}")
            except Exception as e:
                logger.error(f"删除日志文件失败 {fp}: {e}")
    logger.info(f"清理了 {total} 个日志文件")


if __name__ == '__main__':
    logger.info("=== 开始清理 ===")
    clean_chats()
    clean_logs()
    logger.info("=== 清理完成 ===")
