"""
账户日志模块
每人四个日志分类，写入独立文件：
  - recharge.log   充值记录
  - purchase.log   购买记录
  - balance.log    账户变更记录
  - operation.log  操作记录
"""

import os
import json
from datetime import datetime
import logging

logger = logging.getLogger(__name__)

LOG_BASE = '/var/log/caiziyou/accounts'

CATEGORIES = {
    'recharge':  '充值记录',
    'purchase':  '购买记录',
    'balance':   '账户变更记录',
    'operation': '操作记录',
}

CATEGORY_FILES = {
    'recharge':  'recharge.log',
    'purchase':  'purchase.log',
    'balance':   'balance.log',
    'operation': 'operation.log',
}

def _ensure_user_dir(user_id):
    """确保用户日志目录存在"""
    user_dir = os.path.join(LOG_BASE, str(user_id))
    os.makedirs(user_dir, exist_ok=True)
    return user_dir


def write_log(user_id, category, action, detail, extra=None):
    """
    写入一条日志记录
    
    Args:
        user_id: 用户ID
        category: 日志分类 (recharge/purchase/balance/operation)
        action:   操作类型，如 "充值"、"购买"、"变更"、"登录" 等
        detail:   详情描述
        extra:    额外JSON数据 (可选)
    """
    if category not in CATEGORY_FILES:
        raise ValueError(f"未知日志分类: {category}，可选: {', '.join(CATEGORY_FILES.keys())}")
    
    user_dir = _ensure_user_dir(user_id)
    log_file = os.path.join(user_dir, CATEGORY_FILES[category])
    
    record = {
        'time': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
        'action': action,
        'detail': detail,
        'category': category,
    }
    if extra:
        record['extra'] = extra
    
    try:
        with open(log_file, 'a', encoding='utf-8') as f:
            f.write(json.dumps(record, ensure_ascii=False) + '\n')
        return True
    except Exception as e:
        logger.error(f"写入账户日志失败 user={user_id} cat={category}: {e}")
        return False


def read_logs(user_id, category=None, limit=100, offset=0):
    """
    读取日志记录
    
    Args:
        user_id:   用户ID
        category:  分类过滤 (None=读取所有分类)
        limit:     返回条数上限
        offset:    跳过条数
    
    Returns:
        list[dict]: 日志记录列表（按时间倒序）
    """
    user_dir = os.path.join(LOG_BASE, str(user_id))
    if not os.path.isdir(user_dir):
        return []
    
    all_records = []
    
    cats = [category] if category else CATEGORY_FILES.keys()
    for cat in cats:
        if cat not in CATEGORY_FILES:
            continue
        log_file = os.path.join(user_dir, CATEGORY_FILES[cat])
        if not os.path.isfile(log_file):
            continue
        try:
            with open(log_file, 'r', encoding='utf-8') as f:
                for line in f:
                    line = line.strip()
                    if not line:
                        continue
                    try:
                        record = json.loads(line)
                        all_records.append(record)
                    except json.JSONDecodeError:
                        continue
        except Exception as e:
            logger.error(f"读取账户日志失败 user={user_id} cat={cat}: {e}")
    
    # 按时间倒序排列
    all_records.sort(key=lambda r: r.get('time', ''), reverse=True)
    
    # 分页
    return all_records[offset:offset + limit]


def read_logs_grouped(user_id, limit_per_category=50):
    """
    按分类分组读取日志
    
    Returns:
        dict: {category: [records]}
    """
    result = {}
    for cat in CATEGORY_FILES:
        result[cat] = read_logs(user_id, category=cat, limit=limit_per_category)
    return result


def get_categories():
    """返回所有分类信息"""
    return {k: v for k, v in CATEGORIES.items()}
