#!/usr/bin/env python3
"""清理超过7天的上传文件及对应数据库记录"""
import os
import time
import json
import logging
import mysql.connector
import re

LOG_FILE = os.path.join(os.path.dirname(__file__), 'cleanup.log')
UPLOAD_DIRS = [
    '/var/www/caiziyou/public/uploads/posts',
    '/var/www/caiziyou/public/uploads/banners',
]
CUTOFF = 7 * 86400  # 7 days

logging.basicConfig(
    filename=LOG_FILE, level=logging.INFO,
    format='%(asctime)s %(message)s', datefmt='%Y-%m-%d %H:%M:%S'
)

def db_conn():
    return mysql.connector.connect(
        host='localhost', user='caiziyou_user',
        password='CaiziYou@2026', database='caiziyou_community_db'
    )

def collect_files():
    """收集 uploads 目录下所有文件及其修改时间"""
    files = {}
    for d in UPLOAD_DIRS:
        if not os.path.isdir(d):
            continue
        for fname in os.listdir(d):
            fpath = os.path.join(d, fname)
            if os.path.isfile(fpath):
                mtime = os.path.getmtime(fpath)
                files[f'/uploads/{os.path.basename(d)}/{fname}'] = (fpath, mtime)
    return files

def clean():
    now = time.time()
    files = collect_files()
    stale_urls = set()
    deleted_count = 0

    # 删除过期的文件
    for url, (fpath, mtime) in files.items():
        if now - mtime > CUTOFF:
            try:
                os.remove(fpath)
                stale_urls.add(url)
                deleted_count += 1
                logging.info(f'删除过期文件: {url}')
            except Exception as e:
                logging.error(f'删除失败 {url}: {e}')

    # 清理数据库对应记录
    if stale_urls:
        try:
            conn = db_conn()
            cursor = conn.cursor()
            for url in stale_urls:
                # 清理 community_posts 中的 cover_url 和 content_url 和 images
                cursor.execute(
                    "UPDATE community_posts SET cover_url = NULL WHERE cover_url = %s",
                    (url,)
                )
                cursor.execute(
                    "UPDATE community_posts SET content_url = NULL WHERE content_url = %s",
                    (url,)
                )
                # images 是 JSON 数组，需要遍历处理
                cursor.execute(
                    "SELECT id, images FROM community_posts WHERE images IS NOT NULL"
                )
                for row in cursor.fetchall():
                    pid, img_json = row
                    if not img_json:
                        continue
                    try:
                        imgs = json.loads(img_json)
                        if isinstance(imgs, list) and url in imgs:
                            imgs = [i for i in imgs if i != url]
                            if imgs:
                                cursor.execute(
                                    "UPDATE community_posts SET images = %s WHERE id = %s",
                                    (json.dumps(imgs), pid)
                                )
                            else:
                                cursor.execute(
                                    "UPDATE community_posts SET images = NULL WHERE id = %s",
                                    (pid,)
                                )
                    except:
                        pass
            conn.commit()
            cursor.close()
            conn.close()
            logging.info(f'已清理 {len(stale_urls)} 个文件对应的数据库记录')
        except Exception as e:
            logging.error(f'数据库清理失败: {e}')

    # 清理空目录中的 .gitkeep 之类
    for d in UPLOAD_DIRS:
        if os.path.isdir(d) and not os.listdir(d):
            with open(os.path.join(d, '.gitkeep'), 'w') as f:
                f.write('')

    logging.info(f'清理完成: 删除 {deleted_count} 个文件')
    print(f'清理完成: 删除 {deleted_count} 个文件')

if __name__ == '__main__':
    clean()
