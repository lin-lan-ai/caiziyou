"""
QQ群消息推送模块
负责向 QQ 开放平台 API 发送群消息。
使用 OpenClaw 配置的 QQ 机器人凭证。

配置项（从数据库 system_settings 表读取）：
- qq_bot_app_id: QQ机器人AppID
- qq_bot_secret: QQ机器人Secret

调用方式：
    from qq_push import send_group_message
    success = await send_group_message(group_openid, title, url, publisher, publish_time)
"""

import json
import time
import hashlib
import logging
import os
import sys

# 添加项目路径
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

logger = logging.getLogger('qq_push')

# QQ开放平台API
API_BASE = "https://api.sgroup.qq.com"
TOKEN_URL = "https://bots.qq.com/app/getAppAccessToken"

# 缓存access_token
_token_cache = {
    "access_token": None,
    "expires_at": 0
}

# 硬编码机器人凭证（从OpenClaw配置获取）
QQ_APP_ID = "1903871577"
QQ_SECRET = "HkEiDiEkHoMuT3dEpR3gJxbGvbHyfN5o"


def get_access_token():
    """获取QQ机器人access_token（带缓存）"""
    now = time.time()
    if _token_cache["access_token"] and now < _token_cache["expires_at"] - 60:
        return _token_cache["access_token"]

    try:
        import requests
        resp = requests.post(TOKEN_URL, json={
            "appId": QQ_APP_ID,
            "clientSecret": QQ_SECRET
        }, timeout=10)
        data = resp.json()
        token = data.get("access_token")
        expires_in = int(data.get("expires_in", 7200))
        _token_cache["access_token"] = token
        _token_cache["expires_at"] = now + expires_in
        return token
    except Exception as e:
        logger.error(f"获取access_token失败: {e}")
        return None


def send_group_message(group_openid, title, url, publisher="", publish_time=""):
    """
    向指定QQ群发送动态推送消息
    
    参数:
        group_openid: str - QQ群的OpenID
        title: str - 动态标题
        url: str - 动态链接
        publisher: str - 发布人昵称
        publish_time: str - 发布时间
        
    返回:
        dict - {"success": bool, "message": str}
    """
    token = get_access_token()
    if not token:
        return {"success": False, "message": "获取access_token失败"}

    # 构建消息内容
    content_parts = []
    content_parts.append("【团新动态】")
    if publisher:
        content_parts.append(f"👤 {publisher}")
    content_parts.append(f"📄 {title}")
    if publish_time:
        content_parts.append(f"⏰ {publish_time}")
    content_parts.append(f"🔗 {url}")

    message_content = "\n".join(content_parts)

    try:
        import requests
        resp = requests.post(
            f"{API_BASE}/v2/groups/{group_openid}/messages",
            headers={
                "Authorization": f"QQBot {token}",
                "Content-Type": "application/json"
            },
            json={
                "content": message_content,
                "msg_type": 0  # 文本消息
            },
            timeout=10
        )
        result = resp.json()
        
        if resp.status_code == 200 and "id" in result:
            logger.info(f"群消息推送成功: {title} -> group={group_openid}")
            return {"success": True, "message_id": result.get("id")}
        else:
            logger.warning(f"群消息推送失败: {resp.status_code} {result}")
            return {"success": False, "message": str(result)}
            
    except Exception as e:
        logger.error(f"群消息推送异常: {e}")
        return {"success": False, "message": str(e)}


def send_group_message_retry(group_openid, title, url, publisher="", publish_time="", max_retries=3):
    """带重试的群消息推送"""
    for attempt in range(max_retries):
        result = send_group_message(group_openid, title, url, publisher, publish_time)
        if result["success"]:
            return result
        if attempt < max_retries - 1:
            wait = 2 ** attempt
            logger.info(f"推送失败，{wait}秒后重试 (第{attempt+1}次)")
            time.sleep(wait)
    return result
