#!/usr/bin/env python3
"""
菜籽游用户审核API - Python Flask后端
提供用户审核、工具计算等后端功能
"""

from flask import Flask, request, jsonify, session, Response
from flask_cors import CORS
import mysql.connector
import json
import os
import hashlib
import jwt
import datetime
from functools import wraps
import time
import logging
import uuid
from dotenv import load_dotenv
from PIL import Image
import io

# 加载 .env 文件（从 api/ 目录向上找一级）
load_dotenv(os.path.join(os.path.dirname(__file__), '..', '.env'))

# 简单内存限流
_ratelimit_store = {}
def rate_limit(limit=10, per=60):
    def decorator(f):
        @wraps(f)
        def wrapper(*args, **kwargs):
            key = f.__name__ + ':' + request.remote_addr or 'unknown'
            now = time.time()
            old = _ratelimit_store.get(key, [])
            old = [t for t in old if t > now - per]
            if len(old) >= limit:
                return jsonify({'error': '请求过快，请稍后再试'}), 429
            old.append(now)
            _ratelimit_store[key] = old
            return f(*args, **kwargs)
        return wrapper
    return decorator

import uuid

from account_logger import write_log, read_logs, read_logs_grouped, get_categories as get_log_categories
from qq_push import send_group_message_retry

# 配置日志
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

def compress_image(image_data, max_size=1920, quality=85, target_format=None):
    """
    Compress image data using Pillow.

    Args:
        image_data: Raw bytes of the image
        max_size: Maximum dimension (width or height) in pixels
        quality: JPEG/WebP quality (1-100)
        target_format: Force output format ('JPEG', 'PNG', 'WEBP', or None to auto-detect)

    Returns:
        Compressed bytes, or original bytes if compression fails or not an image
    """
    try:
        img = Image.open(io.BytesIO(image_data))

        # Preserve original format if not specified
        if target_format is None:
            target_format = img.format or 'JPEG'

        # Convert RGBA to RGB for JPEG
        if target_format == 'JPEG' and img.mode in ('RGBA', 'P'):
            img = img.convert('RGB')
        elif img.mode == 'P':
            img = img.convert('RGBA')

        # Resize if larger than max_size
        if max(img.width, img.height) > max_size:
            ratio = max_size / max(img.width, img.height)
            new_size = (int(img.width * ratio), int(img.height * ratio))
            img = img.resize(new_size, Image.LANCZOS)

        # Strip all metadata (EXIF, ICC, etc.)
        img.info.clear()

        # Save compressed
        buf = io.BytesIO()
        save_kwargs = {'format': target_format}

        if target_format in ('JPEG',):
            save_kwargs['quality'] = quality
            save_kwargs['optimize'] = True
        elif target_format == 'WEBP':
            save_kwargs['quality'] = quality
        elif target_format == 'PNG':
            save_kwargs['optimize'] = True

        img.save(buf, **save_kwargs)
        compressed = buf.getvalue()

        # Only return compressed if it's actually smaller
        if len(compressed) < len(image_data) * 0.9:  # At least 10% reduction
            return compressed
        return image_data

    except Exception as e:
        logger.warning(f"Image compression failed (non-critical): {e}")
        return image_data

app = Flask(__name__)
CORS(app, supports_credentials=True)

# 所有 API 响应禁止缓存（防浏览器/proxy缓存动态数据）
@app.after_request
def add_no_cache(response):
    response.headers['Cache-Control'] = 'no-store, no-cache, must-revalidate, proxy-revalidate, private'
    response.headers['Pragma'] = 'no-cache'
    response.headers['Expires'] = '0'
    return response

# 配置
app.config['SECRET_KEY'] = os.environ.get('JWT_SECRET', os.environ.get('SECRET_KEY', 'caiziyou-secret-key-2026'))
app.config['DATABASE_CONFIG'] = {
    'host': os.environ.get('COMMUNITY_DB_HOST', 'localhost'),
    'user': os.environ.get('COMMUNITY_DB_USER', 'caiziyou_user'),
    'password': os.environ.get('COMMUNITY_DB_PASS', 'CaiziYou@2026'),
    'database': os.environ.get('COMMUNITY_DB_NAME', 'caiziyou_community_db'),
    'charset': 'utf8mb4'
}

def get_db_connection():
    """获取数据库连接"""
    try:
        conn = mysql.connector.connect(**app.config['DATABASE_CONFIG'])
        return conn
    except mysql.connector.Error as err:
        logger.error(f"数据库连接失败: {err}")
        return None

def get_setting(key, default='false'):
    try:
        conn = get_db_connection()
        if not conn:
            return default
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT setting_value FROM system_settings WHERE setting_key = %s", (key,))
        row = cursor.fetchone()
        cursor.close(); conn.close()
        return row['setting_value'] if row else default
    except:
        return default


def add_operation_log(user_id, username, action, target_type=None, target_id=None, detail=None):
    """记录操作日志"""
    try:
        conn = get_db_connection()
        if not conn:
            return
        cursor = conn.cursor()
        ip = request.remote_addr if request else 'unknown'
        cursor.execute(
            "INSERT INTO operation_logs (user_id, username, action, target_type, target_id, detail, ip) VALUES (%s, %s, %s, %s, %s, %s, %s)",
            (user_id, username, action, target_type, target_id, detail, ip)
        )
        conn.commit()
        cursor.close(); conn.close()
    except Exception as e:
        logger.error(f"写入操作日志失败: {e}")


def token_required(f):
    """JWT令牌验证装饰器"""
    @wraps(f)
    def decorated(*args, **kwargs):
        token = None
        
        # 从请求头获取令牌
        if 'Authorization' in request.headers:
            token = request.headers['Authorization'].split(" ")[1] if " " in request.headers['Authorization'] else None
        if not token and request.is_json:
            try:
                token = request.json.get('token')
            except: pass
        if not token:
            return jsonify({'error': '令牌缺失'}), 401
        
        try:
            # 解码令牌
            data = jwt.decode(token, app.config['SECRET_KEY'], algorithms=['HS256'])
            current_user_id = data['user_id']
            
            # 验证用户是否存在且是管理员
            conn = get_db_connection()
            if not conn:
                return jsonify({'error': '数据库连接失败'}), 500
                
            cursor = conn.cursor(dictionary=True)
            cursor.execute("""
                SELECT id, username, role, status 
                FROM users 
                WHERE id = %s AND role IN ('admin', 'moderator') AND status = 'active'
            """, (current_user_id,))
            user = cursor.fetchone()
            cursor.close()
            conn.close()
            
            if not user:
                return jsonify({'error': '用户无权限'}), 403
                
        except jwt.ExpiredSignatureError:
            return jsonify({'error': '令牌已过期'}), 401
        except jwt.InvalidTokenError:
            return jsonify({'error': '无效令牌'}), 401
        except Exception as e:
            logger.error(f"令牌验证错误: {e}")
            return jsonify({'error': '令牌验证失败'}), 401
        
        return f(user, *args, **kwargs)
    
    return decorated

@app.route('/api/health', methods=['GET'])
def health_check():
    """健康检查"""
    return jsonify({'status': 'ok', 'service': 'caiziyou-api'})

@app.route('/api/login', methods=['POST'])
def login():
    """用户登录"""
    try:
        data = request.get_json()
        username = data.get('username')
        password = data.get('password')
        
        if not username or not password:
            return jsonify({'error': '用户名和密码不能为空'}), 400
        
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT id, username, password_hash, role, status, registration_status
            FROM users 
            WHERE username = %s
        """, (username,))
        user = cursor.fetchone()
        cursor.close()
        conn.close()
        
        if not user:
            return jsonify({'error': '用户不存在'}), 401
        
        # 检查账户状态
        if user['status'] != 'active':
            return jsonify({'error': '账户已被禁用'}), 403
        
        # 检查注册状态
        if user['registration_status'] != 'approved':
            return jsonify({'error': '账户等待管理员审核'}), 403
        
        # 验证密码（这里需要与PHP的密码哈希兼容）
        # 注意：实际使用时需要确保PHP和Python使用相同的哈希算法
        import bcrypt
        
        # 记录操作日志
        write_log(user['id'], 'operation', '登录', f"用户 {user['username']} 登录成功")
        
        if bcrypt.checkpw(password.encode('utf-8'), user['password_hash'].encode('utf-8')):
            # 生成JWT令牌
            token = jwt.encode({
                'user_id': user['id'],
                'username': user['username'],
                'role': user['role'],
                'exp': datetime.datetime.utcnow() + datetime.timedelta(hours=24)
            }, app.config['SECRET_KEY'], algorithm='HS256')
            
            return jsonify({
                'success': True,
                'token': token,
                'user': {
                    'id': user['id'],
                    'username': user['username'],
                    'role': user['role']
                }
            })
        else:
            return jsonify({'error': '密码错误'}), 401
            
    except Exception as e:
        logger.error(f"登录错误: {e}")
        return jsonify({'error': '登录失败'}), 500

@app.route('/api/admin/pending-users', methods=['GET'])
@token_required
def get_pending_users(current_user):
    """获取待审核用户列表"""
    try:
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT 
                id, username, email, full_name, user_note, 
                registration_status, created_at
            FROM users 
            WHERE registration_status = 'pending'
            ORDER BY created_at DESC
        """)
        users = cursor.fetchall()
        
        # 获取统计数据
        cursor.execute("SELECT COUNT(*) as total FROM users")
        stats_total = cursor.fetchone()
        cursor.execute("SELECT COUNT(*) as active FROM users WHERE status = 'active'")
        stats_active = cursor.fetchone()
        cursor.execute("SELECT COUNT(*) as pending FROM users WHERE registration_status = 'pending'")
        stats_pending = cursor.fetchone()
        cursor.execute("SELECT COUNT(*) as communities FROM communities WHERE is_public = 1")
        stats_communities = cursor.fetchone()
        cursor.close()
        conn.close()
        
        return jsonify({
            'success': True,
            'count': len(users),
            'users': users,
            'stats': {
                'total_users': stats_total['total'] if stats_total else 0,
                'active_users': stats_active['active'] if stats_active else 0,
                'pending_users': stats_pending['pending'] if stats_pending else 0,
                'communities': stats_communities['communities'] if stats_communities else 0
            }
        })
        
    except Exception as e:
        logger.error(f"获取待审核用户错误: {e}")
        return jsonify({'error': '获取数据失败'}), 500

@app.route('/api/admin/approve-user/<int:user_id>', methods=['POST'])
@token_required
def approve_user(current_user, user_id):
    """审核通过用户"""
    try:
        data = request.get_json()
        review_note = data.get('review_note', '')
        
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        
        cursor = conn.cursor()
        cursor.execute("""
            UPDATE users 
            SET registration_status = 'approved',
                reviewed_by = %s,
                reviewed_at = NOW(),
                review_note = %s,
                status = 'active'
            WHERE id = %s AND registration_status = 'pending'
        """, (current_user['id'], review_note, user_id))
        
        conn.commit()
        affected_rows = cursor.rowcount
        cursor.close()
        conn.close()
        
        if affected_rows > 0:
            logger.info(f"用户 {user_id} 已由 {current_user['username']} 审核通过")
            write_log(current_user['id'], 'operation', '审核用户', f"通过用户 #{user_id} 的注册申请", {'target_user': user_id})
            write_log(user_id, 'operation', '注册审核', '账户已被管理员审核通过')
            return jsonify({'success': True, 'message': '用户审核通过'})
        else:
            return jsonify({'error': '用户不存在或已被审核'}), 404
            
    except Exception as e:
        logger.error(f"审核用户错误: {e}")
        return jsonify({'error': '审核失败'}), 500

@app.route('/api/admin/reject-user/<int:user_id>', methods=['POST'])
@token_required
def reject_user(current_user, user_id):
    """审核拒绝用户"""
    try:
        data = request.get_json()
        review_note = data.get('review_note', '')
        
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        
        cursor = conn.cursor()
        # 拒绝用户：直接删除（释放用户名和邮箱）
        cursor.execute("""
            DELETE FROM users 
            WHERE id = %s AND registration_status = 'pending'
        """, (user_id,))
        
        conn.commit()
        affected_rows = cursor.rowcount
        cursor.close()
        conn.close()
        
        if affected_rows > 0:
            logger.info(f"用户 {user_id} 已由 {current_user['username']} 审核拒绝")
            write_log(current_user['id'], 'operation', '审核用户', f"拒绝用户 #{user_id} 的注册申请", {'target_user': user_id})
            return jsonify({'success': True, 'message': '用户审核拒绝'})
        else:
            return jsonify({'error': '用户不存在或已被审核'}), 404
            
    except Exception as e:
        logger.error(f"拒绝用户错误: {e}")
        return jsonify({'error': '操作失败'}), 500

@app.route('/api/admin/users', methods=['GET'])
@token_required
def admin_get_users(current_user):
    """获取所有用户列表（管理员用）"""
    try:
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT id, username, email, full_name, nickname, role, status,
                   registration_status, created_at, last_login
            FROM users
            ORDER BY created_at DESC
        """)
        users = cursor.fetchall()
        cursor.close()
        conn.close()
        
        # 日期格式化
        for u in users:
            if isinstance(u.get('created_at'), datetime.datetime):
                u['created_at'] = u['created_at'].strftime('%Y-%m-%d %H:%M')
            if isinstance(u.get('last_login'), datetime.datetime):
                u['last_login'] = u['last_login'].strftime('%Y-%m-%d %H:%M') if u['last_login'] else None
        
        return jsonify({
            'success': True,
            'count': len(users),
            'users': users
        })
    except Exception as e:
        logger.error(f"获取用户列表错误: {e}")
        return jsonify({'error': '获取数据失败'}), 500


@app.route('/api/admin/delete-user/<int:user_id>', methods=['POST'])
@token_required
def admin_delete_user(current_user, user_id):
    """管理员注销（删除）用户"""
    try:
        if current_user['id'] == user_id:
            return jsonify({'error': '不能删除自己'}), 400
        
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        
        cursor = conn.cursor()
        cursor.execute("DELETE FROM user_profiles WHERE user_id = %s", (user_id,))
        cursor.execute("DELETE FROM user_sessions WHERE user_id = %s", (user_id,))
        cursor.execute("DELETE FROM community_members WHERE user_id = %s", (user_id,))
        cursor.execute("DELETE FROM users WHERE id = %s", (user_id,))
        conn.commit()
        
        affected = cursor.rowcount
        cursor.close()
        conn.close()
        
        if affected > 0:
            logger.info(f"用户 {user_id} 已被管理员 {current_user['username']} 删除")
            write_log(current_user['id'], 'operation', '删除用户', f"管理员删除了用户 #{user_id}", {'target_user': user_id})
            return jsonify({'success': True, 'message': '用户已删除'})
        else:
            return jsonify({'error': '用户不存在'}), 404
    except Exception as e:
        logger.error(f"删除用户错误: {e}")
        return jsonify({'error': '删除失败'}), 500


@app.route('/api/tools/json-format', methods=['POST'])
def json_format():
    """JSON格式化工具"""
    try:
        data = request.get_json()
        json_str = data.get('json', '')
        action = data.get('action', 'format')  # format or minify
        
        if not json_str:
            return jsonify({'error': 'JSON内容不能为空'}), 400
        
        import json as json_lib
        parsed = json_lib.loads(json_str)
        
        if action == 'minify':
            result = json_lib.dumps(parsed, separators=(',', ':'))
        else:  # format
            result = json_lib.dumps(parsed, indent=2, ensure_ascii=False)
        
        return jsonify({
            'success': True,
            'result': result,
            'action': action
        })
        
    except json_lib.JSONDecodeError as e:
        return jsonify({'error': f'JSON格式错误: {str(e)}'}), 400
    except Exception as e:
        logger.error(f"JSON工具错误: {e}")
        return jsonify({'error': '处理失败'}), 500

@app.route('/api/tools/base64', methods=['POST'])
def base64_tool():
    """Base64编码/解码工具"""
    try:
        data = request.get_json()
        text = data.get('text', '')
        action = data.get('action', 'encode')  # encode or decode
        
        if not text:
            return jsonify({'error': '文本内容不能为空'}), 400
        
        import base64
        
        if action == 'encode':
            result = base64.b64encode(text.encode('utf-8')).decode('utf-8')
        else:  # decode
            try:
                result = base64.b64decode(text).decode('utf-8')
            except:
                return jsonify({'error': 'Base64解码失败，请检查输入'}), 400
        
        return jsonify({
            'success': True,
            'result': result,
            'action': action
        })
        
    except Exception as e:
        logger.error(f"Base64工具错误: {e}")
        return jsonify({'error': '处理失败'}), 500

@app.route('/api/tools/unit-convert', methods=['POST'])
def unit_convert():
    """单位转换工具"""
    try:
        data = request.get_json()
        value = float(data.get('value', 0))
        from_unit = data.get('from_unit', 'px')
        to_unit = data.get('to_unit', 'rem')
        
        conversions = {
            'px_to_rem': 0.0625,
            'rem_to_px': 16,
            'px_to_em': 0.0625,
            'em_to_px': 16,
            'cm_to_inch': 0.393701,
            'inch_to_cm': 2.54,
            'kb_to_mb': 0.0009765625,
            'mb_to_kb': 1024
        }
        
        # 温度转换（特殊处理）
        if from_unit == 'celsius' and to_unit == 'fahrenheit':
            result = (value * 9/5) + 32
            return jsonify({
                'success': True,
                'result': round(result, 2),
                'from': f"{value} {from_unit}",
                'to': f"{round(result, 2)} {to_unit}"
            })
        if from_unit == 'fahrenheit' and to_unit == 'celsius':
            result = (value - 32) * 5/9
            return jsonify({
                'success': True,
                'result': round(result, 2),
                'from': f"{value} {from_unit}",
                'to': f"{round(result, 2)} {to_unit}"
            })
        
        key = f"{from_unit}_to_{to_unit}"
        if key in conversions:
            result = value * conversions[key]
        else:
            # 尝试反向转换
            reverse_key = f"{to_unit}_to_{from_unit}"
            if reverse_key in conversions:
                result = value / conversions[reverse_key]
            else:
                return jsonify({'error': f'不支持 {from_unit} 到 {to_unit} 的转换'}), 400
        
        return jsonify({
            'success': True,
            'result': round(result, 4),
            'from': f"{value} {from_unit}",
            'to': f"{round(result, 4)} {to_unit}"
        })
        
    except ValueError:
        return jsonify({'error': '请输入有效的数值'}), 400
    except Exception as e:
        logger.error(f"单位转换错误: {e}")
        return jsonify({'error': '转换失败'}), 500

@app.route('/api/tools/color-convert', methods=['POST'])
def color_convert():
    """颜色转换工具"""
    try:
        data = request.get_json()
        color = data.get('color', '#000000')
        format_type = data.get('format', 'hex')  # hex, rgb, hsl
        
        import re
        
        # 验证和解析颜色
        hex_pattern = r'^#?([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$'
        rgb_pattern = r'^rgb\((\d{1,3}),\s*(\d{1,3}),\s*(\d{1,3})\)$'
        
        result = {}
        
        if re.match(hex_pattern, color.replace('#', '')):
            # HEX颜色
            hex_color = color.replace('#', '')
            if len(hex_color) == 3:
                hex_color = ''.join([c*2 for c in hex_color])
            
            r = int(hex_color[0:2], 16)
            g = int(hex_color[2:4], 16)
            b = int(hex_color[4:6], 16)
            
            result['hex'] = f"#{hex_color.upper()}"
            result['rgb'] = f"rgb({r}, {g}, {b})"
            
            # 转换为HSL
            r_norm = r / 255
            g_norm = g / 255
            b_norm = b / 255
            
            cmax = max(r_norm, g_norm, b_norm)
            cmin = min(r_norm, g_norm, b_norm)
            delta = cmax - cmin
            
            if delta == 0:
                h = 0
            elif cmax == r_norm:
                h = 60 * (((g_norm - b_norm) / delta) % 6)
            elif cmax == g_norm:
                h = 60 * (((b_norm - r_norm) / delta) + 2)
            else:
                h = 60 * (((r_norm - g_norm) / delta) + 4)
            
            l = (cmax + cmin) / 2
            s = 0 if delta == 0 else delta / (1 - abs(2 * l - 1))
            
            result['hsl'] = f"hsl({round(h)}, {round(s*100)}%, {round(l*100)}%)"
            
        elif re.match(rgb_pattern, color):
            # RGB颜色
            match = re.match(rgb_pattern, color)
            r, g, b = map(int, match.groups())
            
            # 转换为HEX
            hex_color = f"#{r:02x}{g:02x}{b:02x}".upper()
            result['hex'] = hex_color
            result['rgb'] = color
            
        else:
            return jsonify({'error': '无效的颜色格式'}), 400
        
        return jsonify({
            'success': True,
            'result': result.get(format_type, result['hex']),
            'formats': result
        })
        
    except Exception as e:
        logger.error(f"颜色转换错误: {e}")
        return jsonify({'error': '转换失败'}), 500

# ======== 好友系统 API ========

@app.route('/api/friends/pending-sent', methods=['GET'])
def friends_pending_sent():
    """我发出的待处理好友申请"""
    try:
        user_id = request.args.get('user_id', type=int)
        if not user_id:
            return jsonify({'success': True, 'requests': []})
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT fr.id, fr.to_user_id as friend_id, u.username, u.nickname, u.avatar_url,
                   fr.created_at, fr.status
            FROM friend_requests fr
            JOIN users u ON fr.to_user_id = u.id
            WHERE fr.from_user_id = %s AND fr.status = 'pending'
            ORDER BY fr.created_at DESC
        """, (user_id,))
        requests = cursor.fetchall()
        cursor.close(); conn.close()
        return jsonify({'success': True, 'requests': requests})
    except Exception as e:
        logger.error(f"获取待处理申请错误: {e}")
        return jsonify({'error': '获取失败'}), 500


@app.route('/api/friends/search', methods=['GET'])
def friends_search():
    """搜索用户（昵称/用户名/好友码）"""
    try:
        q = request.args.get('q', '').strip()
        user_id = request.args.get('user_id', type=int)
        if not q or not user_id:
            return jsonify({'success': True, 'users': []})
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        pattern = '%' + q + '%'
        cursor.execute("""
            SELECT id, username, nickname, friend_code
            FROM users
            WHERE (username LIKE %s OR nickname LIKE %s OR friend_code LIKE %s)
                AND status = 'active'
            ORDER BY username
            LIMIT 20
        """, (pattern, pattern, pattern))
        users = cursor.fetchall()
        # 标记好友关系和申请状态
        for u in users:
            cursor.execute("""
                SELECT status FROM friend_requests
                WHERE ((from_user_id = %s AND to_user_id = %s)
                    OR (from_user_id = %s AND to_user_id = %s))
                    AND status IN ('accepted', 'pending')
            """, (user_id, u['id'], u['id'], user_id))
            req = cursor.fetchone()
            if req:
                if req['status'] == 'accepted':
                    u['is_friend'] = True
                else:
                    u['has_request'] = True
        cursor.close(); conn.close()
        return jsonify({'success': True, 'users': users})
    except Exception as e:
        logger.error(f"搜索用户错误: {e}")
        return jsonify({'error': '搜索失败'}), 500


@app.route('/api/friends/apply', methods=['POST'])
def friends_apply():
    """发送好友申请（支持 query 搜索用户名/ID）"""
    try:
        data = request.get_json()
        from_id = data.get('user_id') or data.get('from_user_id')
        query = data.get('query', '').strip()
        to_id = data.get('to_user_id') or data.get('friend_id')
        # 如果有 query 参数，先搜索用户
        if not to_id and query:
            conn = get_db_connection()
            if not conn:
                return jsonify({'error': '数据库连接失败'}), 500
            cursor = conn.cursor(dictionary=True)
            try:
                # 尝试按 ID 或用户名搜索
                qid = int(query) if query.isdigit() else None
                if qid:
                    cursor.execute("SELECT id FROM users WHERE id = %s", (qid,))
                else:
                    cursor.execute("SELECT id FROM users WHERE username = %s", (query,))
                user = cursor.fetchone()
                if not user:
                    cursor.close(); conn.close()
                    return jsonify({'error': '未找到该用户', 'query': query}), 404
                to_id = user['id']
            except:
                cursor.close(); conn.close()
                return jsonify({'error': '搜索失败'}), 500
            cursor.close(); conn.close()
        if not from_id or not to_id or from_id == to_id:
            return jsonify({'error': '参数无效'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        # 检查是否已经是好友
        cursor.execute("""
            SELECT id, status FROM friend_requests
            WHERE ((from_user_id = %s AND to_user_id = %s)
                OR (from_user_id = %s AND to_user_id = %s))
                AND status = 'accepted'
        """, (from_id, to_id, to_id, from_id))
        if cursor.fetchone():
            cursor.close(); conn.close()
            return jsonify({'error': '已经是好友了'}), 400
        # 检查对方是否已发送申请
        cursor.execute("""
            SELECT id FROM friend_requests
            WHERE from_user_id = %s AND to_user_id = %s AND status = 'pending'
        """, (to_id, from_id))
        reverse_req = cursor.fetchone()
        if reverse_req:
            cursor.execute("UPDATE friend_requests SET status = 'accepted' WHERE id = %s", (reverse_req['id'],))
            cursor.execute("""INSERT INTO notifications (user_id, type, title, content, related_user_id) SELECT %s, 'friend_accepted', '好友申请通过', CONCAT(username, ' 通过了你的好友申请'), %s FROM users WHERE id = %s""", (to_id, from_id, from_id))
            conn.commit()
            cursor.close(); conn.close()
            write_log(from_id, 'operation', '好友自动同意', f"和用户 #{to_id} 互加好友（自动同意对方已发送的申请）")
            write_log(to_id, 'operation', '好友自动同意', f"和用户 #{from_id} 互加好友（对方自动同意你已发送的申请）")
            return jsonify({'success': True, 'message': '对方已经向你发送过申请，自动同意，你们已经是好友了'}), 200
        # 检查是否已有申请（任何状态）
        cursor.execute("""
            SELECT id, status FROM friend_requests
            WHERE from_user_id = %s AND to_user_id = %s
        """, (from_id, to_id))
        existing = cursor.fetchone()
        if existing:
            if existing['status'] == 'pending':
                cursor.close(); conn.close()
                return jsonify({'error': '已发送过申请，请等待对方处理'}), 400
            elif existing['status'] == 'rejected':
                # 被拒绝后重新发送，更新状态为 pending
                cursor.execute("UPDATE friend_requests SET status = 'pending', updated_at = NOW() WHERE id = %s", (existing['id'],))
                cursor.execute("""INSERT INTO notifications (user_id, type, title, content, related_user_id) SELECT %s, 'friend_request', '好友申请', CONCAT(username, ' 请求添加你为好友'), %s FROM users WHERE id = %s""", (to_id, from_id, from_id))
                conn.commit()
                cursor.close(); conn.close()
                return jsonify({'success': True, 'message': '好友申请已重新发送'}), 200
        
        # 创建申请
        cursor.execute("""
            INSERT INTO friend_requests (from_user_id, to_user_id, status)
            VALUES (%s, %s, 'pending')
        """, (from_id, to_id))
        cursor.execute("""INSERT INTO notifications (user_id, type, title, content, related_user_id) SELECT %s, 'friend_request', '好友申请', CONCAT(username, ' 请求添加你为好友'), %s FROM users WHERE id = %s""", (to_id, from_id, from_id))
        conn.commit()
        cursor.close(); conn.close()
        write_log(from_id, 'operation', '好友申请', f"向用户 #{to_id} 发送了好友申请")
        return jsonify({'success': True, 'message': '好友申请已发送'})
    except Exception as e:
        logger.error(f"好友申请错误: {e}")
        return jsonify({'error': '申请失败'}), 500


@app.route('/api/friends/requests', methods=['GET'])
def friends_requests():
    """获取好友申请列表（收到的+发出的）"""
    try:
        user_id = request.args.get('user_id', type=int)
        if not user_id:
            return jsonify({'error': '缺少用户ID'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        # 收到的待处理申请
        cursor.execute("""
            SELECT f.id, f.status, f.created_at,
                   u.id as user_id, u.username, u.nickname, u.friend_code, u.avatar_url,
                   u.last_active_at
            FROM friend_requests f
            JOIN users u ON f.from_user_id = u.id
            WHERE f.to_user_id = %s
            ORDER BY f.created_at DESC
        """, (user_id,))
        incoming = cursor.fetchall()
        # 发出的申请（包含所有状态）
        cursor.execute("""
            SELECT f.id, f.status, f.created_at,
                   u.id as user_id, u.username, u.nickname, u.friend_code, u.avatar_url,
                   u.last_active_at
            FROM friend_requests f
            JOIN users u ON f.to_user_id = u.id
            WHERE f.from_user_id = %s
            ORDER BY f.created_at DESC
        """, (user_id,))
        outgoing = cursor.fetchall()
        cursor.close(); conn.close()
        # Compute is_online based on last_active_at (online if active within 2 minutes)
        two_minutes_ago = datetime.datetime.now() - datetime.timedelta(minutes=2)
        for friend in incoming:
            last = friend.get('last_active_at')
            friend['is_online'] = bool(last) and last > two_minutes_ago
        for friend in outgoing:
            last = friend.get('last_active_at')
            friend['is_online'] = bool(last) and last > two_minutes_ago
        return jsonify({'success': True, 'incoming': incoming, 'outgoing': outgoing})
    except Exception as e:
        logger.error(f"好友申请列表错误: {e}")
        return jsonify({'error': '获取失败'}), 500


@app.route('/api/friends/handle', methods=['POST'])
def friends_handle():
    """处理好友申请（同意/拒绝）"""
    try:
        data = request.get_json()
        request_id = data.get('request_id')
        action = data.get('action')
        if not request_id or action not in ('accept', 'reject'):
            return jsonify({'error': '参数无效'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        new_status = 'accepted' if action == 'accept' else 'rejected'
        cursor.execute("""
            UPDATE friend_requests SET status = %s WHERE id = %s AND status = 'pending'
        """, (new_status, request_id))
        # 给发起方发送通知
        cursor.execute("""
            SELECT fr.from_user_id, fr.to_user_id, u.username
            FROM friend_requests fr
            JOIN users u ON fr.to_user_id = u.id
            WHERE fr.id = %s
        """, (request_id,))
        req_detail = cursor.fetchone()
        if req_detail:
            if action == 'reject':
                cursor.execute("""
                    INSERT INTO notifications (user_id, type, title, content, related_user_id)
                    SELECT %s, 'friend_rejected', '好友申请被拒绝',
                           CONCAT(username, ' 拒绝了你的好友申请'), %s
                    FROM users WHERE id = %s
                """, (req_detail['from_user_id'], req_detail['to_user_id'], req_detail['to_user_id']))
            elif action == 'accept':
                cursor.execute("""
                    INSERT INTO notifications (user_id, type, title, content, related_user_id)
                    SELECT %s, 'friend_accepted', '好友申请通过',
                           CONCAT(username, ' 通过了你的好友申请'), %s
                    FROM users WHERE id = %s
                """, (req_detail['from_user_id'], req_detail['to_user_id'], req_detail['to_user_id']))
        conn.commit()
        cursor.close(); conn.close()
        
        if req_detail:
            act_label = '同意' if action == 'accept' else '拒绝'
            write_log(req_detail['to_user_id'], 'operation', act_label+'好友', f"{act_label}了来自用户 #{req_detail['from_user_id']} 的好友申请")
        
        return jsonify({'success': True, 'message': '操作成功'})
    except Exception as e:
        logger.error(f"处理好友申请错误: {e}")
        return jsonify({'error': '操作失败'}), 500


@app.route('/api/friends/check', methods=['GET'])
def friends_check():
    """检查好友关系"""
    try:
        user_id = request.args.get('user_id', type=int)
        target_id = request.args.get('target_id', type=int)
        if not user_id or not target_id:
            return jsonify({'error': '参数缺失'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT status FROM friend_requests
            WHERE ((from_user_id = %s AND to_user_id = %s) OR (from_user_id = %s AND to_user_id = %s))
            AND status = 'accepted'
        """, (user_id, target_id, target_id, user_id))
        if cursor.fetchone():
            cursor.close(); conn.close()
            return jsonify({'success': True, 'status': 'friend'})
        cursor.execute("""
            SELECT status FROM friend_requests
            WHERE ((from_user_id = %s AND to_user_id = %s) OR (from_user_id = %s AND to_user_id = %s))
            AND status = 'pending'
        """, (user_id, target_id, target_id, user_id))
        if cursor.fetchone():
            cursor.close(); conn.close()
            return jsonify({'success': True, 'status': 'pending'})
        cursor.close(); conn.close()
        return jsonify({'success': True, 'status': 'not_friend'})
    except Exception as e:
        logger.error(f"好友检查错误: {e}")
        return jsonify({'error': '查询失败'}), 500


@app.route('/api/friends/remove', methods=['POST'])
@app.route('/api/friends/delete', methods=['POST'])
def friends_remove():
    """删除好友"""
    try:
        data = request.get_json()
        user_id = data.get('user_id')
        friend_id = data.get('friend_id')
        logger.info(f"删除好友请求: user_id={user_id}, friend_id={friend_id}, raw_data={data}")
        if not user_id or not friend_id or user_id == friend_id:
            return jsonify({'error': '参数无效'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor()
        # 删除好友关系（删除双方之间的 accepted 申请）
        cursor.execute("""
            DELETE FROM friend_requests
            WHERE ((from_user_id = %s AND to_user_id = %s)
                OR (from_user_id = %s AND to_user_id = %s))
                AND status = 'accepted'
        """, (user_id, friend_id, friend_id, user_id))
        # 同时删除私聊会话
        if user_id > friend_id:
            user_id, friend_id = friend_id, user_id
        cursor.execute("""
            DELETE FROM private_chats
            WHERE user1_id = %s AND user2_id = %s
        """, (user_id, friend_id))
        conn.commit()
        cursor.close(); conn.close()
        write_log(user_id, 'operation', '删除好友', f"删除了好友 #{friend_id}")
        return jsonify({'success': True, 'message': '已删除好友'})
    except Exception as e:
        logger.error(f"删除好友错误: {e}")
        return jsonify({'error': '删除失败'}), 500


@app.route('/api/friends/online-status', methods=['GET'])
def friends_online_status():
    """Check online status of friends"""
    try:
        user_id = request.args.get('user_id', type=int)
        friend_ids = request.args.get('friend_ids', '')
        if not user_id or not friend_ids:
            return jsonify({'error': '参数缺失'}), 400

        ids = [int(x) for x in friend_ids.split(',') if x.strip().isdigit()]
        if not ids:
            return jsonify({'success': True, 'statuses': {}})

        conn = get_db_connection()
        if not conn:
            return jsonify({'error': 'Connection failed'}), 500

        cursor = conn.cursor(dictionary=True)

        # User is online if last_active_at is within the last 5 minutes
        placeholders = ','.join(['%s'] * len(ids))
        cursor.execute(f"""
            SELECT id,
                   CASE WHEN last_active_at IS NOT NULL
                        AND last_active_at > NOW() - INTERVAL 5 MINUTE
                   THEN 1 ELSE 0 END as is_online
            FROM users
            WHERE id IN ({placeholders})
        """, ids)

        statuses = {}
        for row in cursor.fetchall():
            statuses[row['id']] = bool(row['is_online'])

        cursor.close()
        conn.close()

        return jsonify({'success': True, 'statuses': statuses})
    except Exception as e:
        logger.error(f"Online status error: {e}")
        return jsonify({'error': '查询失败'}), 500


# ======== 团 API ========


@app.route('/api/community/create', methods=['POST'])
def community_create():
    """申请创建团（需管理员审核）"""
    try:
        data = request.get_json()
        user_id = data.get('user_id')
        name = data.get('name', '').strip()
        description = data.get('description', '').strip()
        category = data.get('category', '其他').strip()
        banner = data.get('banner', '').strip()
        site_url = data.get('site_url', '').strip()
        if not user_id or not name:
            return jsonify({'error': '参数缺失'}), 400
        # 检测团名重复
        import re
        if not re.match(r'^[a-zA-Z\u4e00-\u9fff]+$', name):
            return jsonify({'error': '团名称仅允许中文汉字和英文字母'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT id FROM communities WHERE name = %s AND status != 'rejected'", (name,))
        if cursor.fetchone():
            cursor.close(); conn.close()
            return jsonify({'error': '团名已存在'}), 400
        cursor.execute("INSERT INTO communities (name, description, site_url, category, creator_id, unique_id, avatar_url, status) VALUES (%s, %s, %s, %s, %s, %s, %s, 'pending')", (name, description, site_url, category, user_id, str(uuid.uuid4())[:8], banner or '/assets/images/community-default.jpg'))
        community_id = cursor.lastrowid
        # 创建者自动成为成员
        cursor.execute("INSERT INTO community_members (community_id, user_id, role, join_status) VALUES (%s, %s, 'creator', 'approved')", (community_id, user_id))
        # 通知管理员
        cursor.execute("SELECT id FROM users WHERE role = 'admin'")
        admins = cursor.fetchall()
        for admin in admins:
            cursor.execute("""INSERT INTO notifications (user_id, type, title, content) VALUES (%s, 'community_create_request', '创建团申请', %s)""", (admin['id'], f"用户 #{user_id} 申请创建团: {name}"))
        conn.commit()
        write_log(user_id, 'operation', '申请创建团', f"申请创建团 #{community_id} - {name}")
        cursor.close(); conn.close()
        return jsonify({'success': True, 'message': '申请已发送，等待管理员审核', 'community_id': community_id})
    except Exception as e:
        logger.error(f"创建团错误: {e}")
        return jsonify({'error': '创建失败'}), 500


@app.route('/api/community/list', methods=['GET'])
def community_list():
    """团列表（含当前用户加入状态）"""
    try:
        user_id = request.args.get('user_id', type=int)
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT c.*, (SELECT COUNT(*) FROM community_members WHERE community_id = c.id) as member_count
            FROM communities c WHERE c.status = 'approved' ORDER BY c.created_at DESC
        """)
        communities = cursor.fetchall()
        if user_id:
            cursor.execute("SELECT community_id FROM community_members WHERE user_id = %s AND join_status = 'approved'", (user_id,))
            joined = set(r['community_id'] for r in cursor.fetchall())
            for c in communities:
                c['joined'] = c['id'] in joined
        cursor.close(); conn.close()
        return jsonify({'success': True, 'communities': communities})
    except Exception as e:
        logger.error(f"团列表错误: {e}")
        return jsonify({'error': '获取失败'}), 500


@app.route('/api/community/my-list', methods=['GET'])
def community_my_list():
    """当前用户已加入的团列表"""
    try:
        user_id = request.args.get('user_id', type=int)
        if not user_id:
            return jsonify({'error': '参数缺失'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT c.*, cm.role as member_role, (SELECT COUNT(*) FROM community_members WHERE community_id = c.id) as member_count
            FROM communities c
            JOIN community_members cm ON c.id = cm.community_id
            WHERE cm.user_id = %s AND cm.join_status = 'approved'
            ORDER BY cm.joined_at DESC
        """, (user_id,))
        communities = cursor.fetchall()
        cursor.close(); conn.close()
        return jsonify({'success': True, 'communities': communities})
    except Exception as e:
        logger.error(f"我的团列表错误: {e}")
        return jsonify({'error': '获取失败'}), 500


@app.route('/api/community/detail', methods=['GET'])
def community_detail():
    """团详情"""
    try:
        cid = request.args.get('id', type=int)
        if not cid:
            return jsonify({'error': '参数缺失'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT c.*, (SELECT COUNT(*) FROM community_members WHERE community_id = c.id) as member_count
            FROM communities c WHERE c.id = %s
        """, (cid,))
        community = cursor.fetchone()
        cursor.close(); conn.close()
        if not community:
            return jsonify({'error': '团不存在'}), 404
        return jsonify({'success': True, 'community': community})
    except Exception as e:
        logger.error(f"团详情错误: {e}")
        return jsonify({'error': '获取失败'}), 500


@app.route('/api/community/update', methods=['POST'])
def community_update():
    """更新团设置"""
    try:
        data = request.get_json()
        cid = data.get('community_id')
        user_id = data.get('user_id')
        if not cid or not user_id:
            return jsonify({'error': '参数缺失'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT creator_id FROM communities WHERE id = %s", (cid,))
        com = cursor.fetchone()
        if not com:
            cursor.close(); conn.close()
            return jsonify({'error': '团不存在'}), 404
        if com['creator_id'] != user_id:
            cursor.close(); conn.close()
            return jsonify({'error': '无权限'}), 403
        updates = []
        params = []
        for field in ['name', 'description', 'category', 'site_url', 'avatar_url', 'banners', 'join_type', 'post_type']:
            val = data.get(field)
            if val is not None:
                updates.append(f"{field} = %s")
                params.append(val)
        if not updates:
            cursor.close(); conn.close()
            return jsonify({'success': True, 'message': '没有需要更新的字段'})
        params.append(cid)
        cursor.execute(f"UPDATE communities SET {', '.join(updates)} WHERE id = %s", params)
        conn.commit()
        cursor.close(); conn.close()
        return jsonify({'success': True})
    except Exception as e:
        logger.error(f"团更新错误: {e}")
        return jsonify({'error': '更新失败'}), 500


@app.route('/api/community/dissolve', methods=['POST'])
def community_dissolve():
    """解散团"""
    try:
        data = request.get_json()
        cid = data.get('community_id')
        user_id = data.get('user_id')
        if not cid or not user_id:
            return jsonify({'error': '参数缺失'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT creator_id FROM communities WHERE id = %s", (cid,))
        com = cursor.fetchone()
        if not com:
            cursor.close(); conn.close()
            return jsonify({'error': '团不存在'}), 404
        # 允许创建者或管理员解散
        if com['creator_id'] != user_id:
            cursor.execute("SELECT role FROM users WHERE id = %s", (user_id,))
            u = cursor.fetchone()
            if not u or u['role'] != 'admin':
                cursor.close(); conn.close()
                return jsonify({'error': '无权限'}), 403
        cursor.execute("DELETE FROM post_likes WHERE post_id IN (SELECT id FROM community_posts WHERE community_id = %s)", (cid,))
        cursor.execute("DELETE FROM post_comments WHERE post_id IN (SELECT id FROM community_posts WHERE community_id = %s)", (cid,))
        cursor.execute("DELETE FROM community_posts WHERE community_id = %s", (cid,))
        cursor.execute("DELETE FROM community_members WHERE community_id = %s", (cid,))
        cursor.execute("DELETE FROM communities WHERE id = %s", (cid,))
        add_operation_log(user_id, str(user_id), 'dissolve_community', 'community', cid, f"解散了团 #{cid}")
        conn.commit()
        cursor.close(); conn.close()
        return jsonify({'success': True})
    except Exception as e:
        logger.error(f"解散团错误: {e}")
        return jsonify({'error': '解散失败'}), 500


@app.route('/api/community/pending-approvals', methods=['GET'])
def community_pending_approvals():
    """管理员获取待审核的创建团申请"""
    try:
        user_id = request.args.get('user_id', type=int)
        if not user_id:
            return jsonify({'error': '参数缺失'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        # 验证是管理员
        cursor.execute("SELECT role FROM users WHERE id = %s", (user_id,))
        u = cursor.fetchone()
        if not u or u['role'] != 'admin':
            cursor.close(); conn.close()
            return jsonify({'error': '无权限'}), 403
        cursor.execute("""
            SELECT c.*, u.username, u.nickname
            FROM communities c
            LEFT JOIN users u ON c.creator_id = u.id
            WHERE c.status = 'pending'
            ORDER BY c.created_at DESC
        """)
        communities = cursor.fetchall()
        cursor.close(); conn.close()
        return jsonify({'success': True, 'communities': communities})
    except Exception as e:
        logger.error(f"待审核团列表错误: {e}")
        return jsonify({'error': '获取失败'}), 500


@app.route('/api/community/admin-all', methods=['GET'])
def community_admin_all():
    """管理员获取所有团列表（含数量）"""
    try:
        user_id = request.args.get('user_id', type=int)
        if not user_id:
            return jsonify({'error': '参数缺失'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT role FROM users WHERE id = %s", (user_id,))
        u = cursor.fetchone()
        if not u or u['role'] != 'admin':
            cursor.close(); conn.close()
            return jsonify({'error': '无权限'}), 403
        cursor.execute("""
            SELECT c.*, u.username, u.nickname,
                   (SELECT COUNT(*) FROM community_members WHERE community_id = c.id) as member_count
            FROM communities c
            LEFT JOIN users u ON c.creator_id = u.id
            WHERE c.status IN ('approved', 'pending')
            ORDER BY c.created_at DESC
        """)
        communities = cursor.fetchall()
        total = len(communities)
        cursor.close(); conn.close()
        return jsonify({'success': True, 'total': total, 'communities': communities})
    except Exception as e:
        logger.error(f"管理员团列表错误: {e}")
        return jsonify({'error': '获取失败'}), 500


@app.route('/api/community/approve-create', methods=['POST'])
def community_approve_create():
    """管理员审核创建团申请"""
    try:
        data = request.get_json()
        community_id = data.get('community_id')
        user_id = data.get('user_id')  # 操作管理员
        action = data.get('action', 'approve')  # approve / reject
        if not community_id or not user_id:
            return jsonify({'error': '参数缺失'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        # 验证是管理员
        cursor.execute("SELECT role FROM users WHERE id = %s", (user_id,))
        u = cursor.fetchone()
        if not u or u['role'] != 'admin':
            cursor.close(); conn.close()
            return jsonify({'error': '无权限'}), 403
        # 获取团信息
        cursor.execute("SELECT name, creator_id FROM communities WHERE id = %s", (community_id,))
        comm = cursor.fetchone()
        if not comm:
            cursor.close(); conn.close()
            return jsonify({'error': '团不存在'}), 404
        creator_id = comm['creator_id']
        name = comm['name']
        if action == 'approve':
            cursor.execute("UPDATE communities SET status = 'approved' WHERE id = %s", (community_id,))
            cursor.execute("""INSERT INTO notifications (user_id, type, title, content) VALUES (%s, 'community_approved', '创建团通过', %s)""", (creator_id, f'你的团 "{name}" 已通过审核'))
            msg = '已通过'
        else:
            cursor.execute("UPDATE communities SET status = 'rejected' WHERE id = %s", (community_id,))
            cursor.execute("""INSERT INTO notifications (user_id, type, title, content) VALUES (%s, 'community_rejected', '创建团被拒', %s)""", (creator_id, f'你的团 "{name}" 申请未通过'))
            msg = '已拒绝'
        conn.commit()
        cursor.close(); conn.close()
        add_operation_log(user_id, str(user_id), 'approve_community', 'community', community_id, '审核团 #' + str(community_id) + ' -> ' + action)
        return jsonify({'success': True, 'message': msg})
    except Exception as e:
        logger.error(f"审核创建团错误: {e}")
        return jsonify({'error': '操作失败'}), 500


@app.route('/api/community/transfer', methods=['POST'])
def community_transfer():
    """转让团长给团内的管理员"""
    try:
        data = request.get_json()
        community_id = data.get('community_id')
        user_id = data.get('user_id')  # 当前团长
        target_id = data.get('target_id')  # 新团长
        if not community_id or not user_id or not target_id:
            return jsonify({'error': '参数缺失'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        # 验证操作者是团长
        cursor.execute("SELECT creator_id FROM communities WHERE id = %s", (community_id,))
        com = cursor.fetchone()
        if not com or com['creator_id'] != user_id:
            cursor.close(); conn.close()
            return jsonify({'error': '无权限，仅团长可转让'}), 403
        # 验证目标是在当前团且已是管理员
        cursor.execute("SELECT role FROM community_members WHERE community_id = %s AND user_id = %s AND join_status = 'approved'", (community_id, target_id))
        member = cursor.fetchone()
        if not member or member['role'] != 'admin':
            cursor.close(); conn.close()
            return jsonify({'error': '目标不是团管理员'}), 400
        # 执行转让
        cursor.execute("UPDATE communities SET creator_id = %s WHERE id = %s", (target_id, community_id))
        cursor.execute("UPDATE community_members SET role = 'creator' WHERE community_id = %s AND user_id = %s", (community_id, target_id))
        cursor.execute("UPDATE community_members SET role = 'admin' WHERE community_id = %s AND user_id = %s", (community_id, user_id))
        conn.commit()
        cursor.close(); conn.close()
        add_operation_log(user_id, str(user_id), 'transfer_owner', 'community', community_id, '转让团长给 ' + str(target_id))
        return jsonify({'success': True, 'message': '团长已转让'})
    except Exception as e:
        logger.error(f"转让团长错误: {e}")
        return jsonify({'error': '操作失败'}), 500


@app.route('/api/community/join', methods=['POST'])
def community_join():
    """加入团，根据 join_type 决定是否需审核"""
    try:
        data = request.get_json()
        cid = data.get('community_id')
        user_id = data.get('user_id')
        if not cid or not user_id:
            return jsonify({'error': '参数缺失'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        # 检查是否已加入
        cursor.execute("SELECT id, join_status FROM community_members WHERE community_id = %s AND user_id = %s", (cid, user_id))
        existing = cursor.fetchone()
        if existing:
            if existing['join_status'] == 'approved':
                cursor.close(); conn.close()
                return jsonify({'error': '已加入'}), 400
            elif existing['join_status'] == 'pending':
                cursor.close(); conn.close()
                return jsonify({'error': '已申请，请等待审核'}), 400
        # 查团的加入方式
        cursor.execute("SELECT join_type, name, creator_id FROM communities WHERE id = %s", (cid,))
        comm = cursor.fetchone()
        if not comm:
            cursor.close(); conn.close()
            return jsonify({'error': '团不存在'}), 404
        join_type = comm['join_type']
        if join_type == 'auto':
            status = 'approved'
            cursor.execute("INSERT INTO community_members (community_id, user_id, role, join_status) VALUES (%s, %s, 'member', 'approved')", (cid, user_id))
            cursor.execute("UPDATE communities SET member_count = member_count + 1 WHERE id = %s", (cid,))
            # 通知
            cursor.execute("""INSERT INTO notifications (user_id, type, title, content) VALUES (%s, 'community_joined', '加入团', %s)""", (user_id, '你已加入团: '+comm['name']))
            conn.commit()
            cursor.close(); conn.close()
            return jsonify({'success': True, 'message': '已加入！'})
        else:
            # 需要审核
            cursor.execute("INSERT INTO community_members (community_id, user_id, role, join_status) VALUES (%s, %s, 'member', 'pending')", (cid, user_id))
            # 通知团长
            cursor.execute("""INSERT INTO notifications (user_id, type, title, content) SELECT %s, 'community_join_request', '加入申请', CONCAT('用户 #', %s, ' 申请加入团: ', %s) FROM DUAL WHERE %s > 0""", (comm['creator_id'], user_id, comm['name'], user_id))
            conn.commit()
            cursor.close(); conn.close()
            return jsonify({'success': True, 'message': '申请已发送，等待团长审核'})
    except Exception as e:
        logger.error(f"加入团错误: {e}")
        return jsonify({'error': '加入失败'}), 500


@app.route('/api/community/members', methods=['GET'])
def community_members():
    """获取团成员列表"""
    try:
        cid = request.args.get('community_id', type=int)
        if not cid:
            return jsonify({'error': '参数缺失'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT cm.user_id, cm.role, cm.join_status, cm.joined_at, u.username, u.avatar_url
            FROM community_members cm
            LEFT JOIN users u ON cm.user_id = u.id
            WHERE cm.community_id = %s AND cm.join_status = 'approved'
            ORDER BY cm.joined_at ASC
        """, (cid,))
        members = cursor.fetchall()
        cursor.close(); conn.close()
        return jsonify({'success': True, 'members': members})
    except Exception as e:
        logger.error(f"成员列表错误: {e}")
        return jsonify({'error': '获取失败'}), 500


@app.route('/api/community/join-requests', methods=['GET'])
def community_join_requests():
    """获取团待审核的入团申请"""
    try:
        cid = request.args.get('community_id', type=int)
        if not cid:
            return jsonify({'error': '参数缺失'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT cm.user_id, cm.joined_at, u.username, u.nickname, u.avatar_url
            FROM community_members cm
            LEFT JOIN users u ON cm.user_id = u.id
            WHERE cm.community_id = %s AND cm.join_status = 'pending'
            ORDER BY cm.joined_at ASC
        """, (cid,))
        requests = cursor.fetchall()
        cursor.close(); conn.close()
        return jsonify({'success': True, 'requests': requests})
    except Exception as e:
        logger.error(f"申请列表错误: {e}")
        return jsonify({'error': '获取失败'}), 500


@app.route('/api/community/approve-join', methods=['POST'])
def community_approve_join():
    """同意或拒绝入团申请"""
    try:
        data = request.get_json()
        cid = data.get('community_id')
        user_id = data.get('user_id')  # 操作人
        target_id = data.get('target_id')
        action = data.get('action', 'approve')  # approve / reject
        if not cid or not user_id or not target_id:
            return jsonify({'error': '参数缺失'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        # 验证操作者是团长或管理员
        cursor.execute("""
            SELECT cm.role FROM community_members cm
            WHERE cm.community_id = %s AND cm.user_id = %s AND cm.join_status = 'approved'
        """, (cid, user_id))
        operator = cursor.fetchone()
        if not operator or operator['role'] not in ('creator', 'admin'):
            cursor.close(); conn.close()
            return jsonify({'error': '无权操作'}), 403
        if action == 'approve':
            cursor.execute("UPDATE community_members SET join_status = 'approved' WHERE community_id = %s AND user_id = %s AND join_status = 'pending'", (cid, target_id))
            cursor.execute("UPDATE communities SET member_count = member_count + 1 WHERE id = %s", (cid,))
            if cursor.rowcount > 0:
                cursor.execute("""INSERT INTO notifications (user_id, type, title, content) VALUES (%s, 'community_joined', '加入申请通过', '你已获批准加入团')""", (target_id,))
            msg = '已通过'
        else:
            cursor.execute("DELETE FROM community_members WHERE community_id = %s AND user_id = %s AND join_status = 'pending'", (cid, target_id))
            if cursor.rowcount > 0:
                cursor.execute("""INSERT INTO notifications (user_id, type, title, content) VALUES (%s, 'community_rejected', '加入申请被拒', '你的入团申请未被通过')""", (target_id,))
            msg = '已拒绝'
        conn.commit()
        cursor.close(); conn.close()
        return jsonify({'success': True, 'message': msg})
    except Exception as e:
        logger.error(f"审核申请错误: {e}")
        return jsonify({'error': '操作失败'}), 500


@app.route('/api/community/kick', methods=['POST'])
def community_kick():
    """踢出成员"""
    try:
        data = request.get_json()
        cid = data.get('community_id')
        user_id = data.get('user_id')
        target_id = data.get('target_id')
        if not cid or not user_id or not target_id:
            return jsonify({'error': '参数缺失'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        # 验证操作者权限
        cursor.execute("SELECT creator_id FROM communities WHERE id = %s", (cid,))
        com = cursor.fetchone()
        if not com:
            cursor.close(); conn.close()
            return jsonify({'error': '团不存在'}), 404
        if com['creator_id'] != user_id:
            cursor.close(); conn.close()
            return jsonify({'error': '无权限'}), 403
        if com['creator_id'] == target_id:
            cursor.close(); conn.close()
            return jsonify({'error': '不能踢出自己'}), 400
        cursor.execute("DELETE FROM community_members WHERE community_id = %s AND user_id = %s AND join_status = 'approved'", (cid, target_id))
        conn.commit()
        cursor.close(); conn.close()
        add_operation_log(user_id, str(user_id), 'kick_member', 'community', cid, f"踢出了成员 {target_id}")
        return jsonify({'success': True})
    except Exception as e:
        logger.error(f"踢出错误: {e}")
        return jsonify({'error': '踢出失败'}), 500


@app.route('/api/community/set-admin', methods=['POST'])
def community_set_admin():
    """团长设置/取消管理员"""
    try:
        data = request.get_json()
        cid = data.get('community_id')
        user_id = data.get('user_id')  # 团长
        target_id = data.get('target_id')  # 目标用户
        action = data.get('action', 'set')  # set / remove
        if not cid or not user_id or not target_id:
            return jsonify({'error': '参数缺失'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT creator_id FROM communities WHERE id = %s", (cid,))
        com = cursor.fetchone()
        if not com or com['creator_id'] != user_id:
            cursor.close(); conn.close()
            return jsonify({'error': '无权限'}), 403
        if target_id == com['creator_id']:
            cursor.close(); conn.close()
            return jsonify({'error': '不能操作团长'}), 400
        if action == 'set':
            cursor.execute("UPDATE community_members SET role = 'admin' WHERE community_id = %s AND user_id = %s AND join_status = 'approved'", (cid, target_id))
        else:
            cursor.execute("UPDATE community_members SET role = 'member' WHERE community_id = %s AND user_id = %s AND join_status = 'approved'", (cid, target_id))
        conn.commit()
        cursor.close(); conn.close()
        return jsonify({'success': True, 'message': '已设为管理员' if action=='set' else '已取消管理员'})
    except Exception as e:
        logger.error(f"设置管理员错误: {e}")
        return jsonify({'error': '操作失败'}), 500


# ======== 用户资料 & 账户日志 API ========

@app.route('/api/user/profile', methods=['GET'])
def user_profile():
    """获取用户资料"""
    try:
        user_id = request.args.get('user_id', type=int)
        if not user_id:
            return jsonify({'error': '缺少用户ID'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT id, username, email, nickname, avatar_url, profile_bg, bio, unique_id, role, status,
                   registration_status, created_at
            FROM users WHERE id = %s
        """, (user_id,))
        user = cursor.fetchone()
        cursor.close(); conn.close()
        if not user:
            return jsonify({'error': '用户不存在'}), 404
        return jsonify({'success': True, 'user': user})
    except Exception as e:
        logger.error(f"获取用户资料错误: {e}")
        return jsonify({'error': '获取失败'}), 500


@app.route('/api/user/update-profile', methods=['POST'])
def user_update_profile():
    """更新用户资料（昵称/头像/名片背景）"""
    try:
        data = request.get_json()
        user_id = data.get('user_id')
        nickname = data.get('nickname', '').strip()
        avatar = data.get('avatar', '').strip()
        profile_bg = data.get('profile_bg', '').strip()
        if not user_id:
            return jsonify({'error': '缺少用户ID'}), 400
        if nickname and nickname.isdigit():
            return jsonify({'error': '昵称不能为纯数字'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor()
        updates = []
        params = []
        if nickname:
            cursor.execute("SELECT id FROM users WHERE nickname = %s AND id != %s", (nickname, user_id))
            if cursor.fetchone():
                cursor.close(); conn.close()
                return jsonify({'error': '昵称已被使用'}), 400
            updates.append("nickname = %s")
            params.append(nickname)
        if avatar:
            updates.append("avatar_url = %s")
            params.append(avatar)
        if profile_bg:
            updates.append("profile_bg = %s")
            params.append(profile_bg)
        if not updates:
            cursor.close(); conn.close()
            return jsonify({'success': True, 'message': '无需更新'})
        params.append(user_id)
        cursor.execute(f"UPDATE users SET {', '.join(updates)} WHERE id = %s", params)
        conn.commit(); cursor.close(); conn.close()
        
        write_log(user_id, 'operation', '资料修改', f"更新资料: {', '.join(updates)}")
        return jsonify({'success': True, 'message': '资料已更新'})
    except Exception as e:
        logger.error(f"更新资料错误: {e}")
        return jsonify({'error': '更新失败'}), 500


@app.route('/api/user/change-password', methods=['POST'])
def user_change_password():
    """更改密码"""
    try:
        import bcrypt
        data = request.get_json()
        user_id = data.get('user_id')
        current_pwd = data.get('current_password', '')
        new_pwd = data.get('new_password', '')
        if not user_id or not current_pwd or not new_pwd:
            return jsonify({'error': '参数不完整'}), 400
        if len(new_pwd) < 6:
            return jsonify({'error': '新密码至少6位'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT id, password_hash, username FROM users WHERE id = %s", (user_id,))
        user = cursor.fetchone()
        if not user:
            cursor.close(); conn.close()
            return jsonify({'error': '用户不存在'}), 404
        if not bcrypt.checkpw(current_pwd.encode('utf-8'), user['password_hash'].encode('utf-8')):
            cursor.close(); conn.close()
            return jsonify({'error': '当前密码错误'}), 403
        new_hash = bcrypt.hashpw(new_pwd.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')
        cursor.execute("UPDATE users SET password_hash = %s WHERE id = %s", (new_hash, user_id))
        conn.commit(); cursor.close(); conn.close()
        
        write_log(user_id, 'operation', '密码更改', '用户更改了登录密码')
        return jsonify({'success': True, 'message': '密码已更改'})
    except Exception as e:
        logger.error(f"更改密码错误: {e}")
        return jsonify({'error': '更改失败'}), 500


@app.route('/api/user/logs', methods=['GET'])
def user_logs():
    """获取用户日志
    Query params:
        user_id:    用户ID（必填）
        category:   分类过滤（可选: recharge/purchase/balance/operation，默认全部）
        limit:      返回条数上限（默认100）
    """
    try:
        user_id = request.args.get('user_id', type=int)
        category = request.args.get('category')
        limit = request.args.get('limit', 100, type=int)
        
        if not user_id:
            return jsonify({'error': '缺少用户ID'}), 400
        
        if category and category not in ('recharge', 'purchase', 'balance', 'operation'):
            return jsonify({'error': '无效的分类，可选: recharge/purchase/balance/operation'}), 400
        
        records = read_logs(user_id, category=category, limit=limit)
        categories = get_log_categories()
        
        return jsonify({
            'success': True,
            'count': len(records),
            'logs': records,
            'categories': categories
        })
    except Exception as e:
        logger.error(f"获取用户日志错误: {e}")
        return jsonify({'error': '获取失败'}), 500


@app.route('/api/user/logs/stats', methods=['GET'])
def user_logs_stats():
    """获取各分类日志统计"""
    try:
        user_id = request.args.get('user_id', type=int)
        if not user_id:
            return jsonify({'error': '缺少用户ID'}), 400
        
        grouped = read_logs_grouped(user_id, limit_per_category=99999)
        stats = {cat: len(records) for cat, records in grouped.items()}
        
        return jsonify({
            'success': True,
            'stats': stats,
            'categories': get_log_categories()
        })
    except Exception as e:
        logger.error(f"获取日志统计错误: {e}")
        return jsonify({'error': '获取失败'}), 500


@app.route('/api/user/notifications/count', methods=['GET'])
def user_notifications_count():
    """获取用户未读角标数（未读消息 + 待处理好友申请）"""
    try:
        user_id = request.args.get('user_id', type=int)
        if not user_id:
            return jsonify({'error': '缺少用户ID'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        # 未读消息数（别人发给我的未读消息）
        cursor.execute("""
            SELECT COUNT(*) as cnt FROM private_messages pm
            JOIN private_chats pc ON pm.chat_id = pc.id
            WHERE (pc.user1_id = %s OR pc.user2_id = %s)
              AND pm.sender_id != %s AND pm.is_read = FALSE
        """, (user_id, user_id, user_id))
        unread_messages = cursor.fetchone()['cnt']
        # 待处理好友申请（我收到的 pending 请求）
        cursor.execute("""
            SELECT COUNT(*) as cnt FROM friend_requests
            WHERE to_user_id = %s AND status = 'pending'
        """, (user_id,))
        pending_requests = cursor.fetchone()['cnt']
        # 未读系统通知数
        cursor.execute("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = %s AND is_read = FALSE", (user_id,))
        unread_notifications = cursor.fetchone()['cnt']
        cursor.close(); conn.close()
        return jsonify({
            'success': True,
            'unread_messages': unread_messages,
            'pending_requests': pending_requests,
            'unread_notifications': unread_notifications
        })
    except Exception as e:
        logger.error(f"获取未读角标错误: {e}")
        return jsonify({'error': '获取失败'}), 500


@app.route('/api/user/notifications/list', methods=['GET'])
def user_notifications_list():
    """Get user notifications with pagination"""
    try:
        user_id = request.args.get('user_id', type=int)
        page = request.args.get('page', 1, type=int)
        limit = request.args.get('limit', 20, type=int)
        if not user_id:
            return jsonify({'error': 'Missing user_id'}), 400

        conn = get_db_connection()
        if not conn:
            return jsonify({'error': 'Connection failed'}), 500

        cursor = conn.cursor(dictionary=True)
        offset = (page - 1) * limit

        # Get total count
        cursor.execute("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = %s", (user_id,))
        total = cursor.fetchone()['cnt']

        # Get notifications with optional related user info
        cursor.execute("""
            SELECT n.*, u.username as related_username, u.nickname as related_nickname, u.avatar_url as related_avatar
            FROM notifications n
            LEFT JOIN users u ON n.related_user_id = u.id
            WHERE n.user_id = %s
            ORDER BY n.created_at DESC
            LIMIT %s OFFSET %s
        """, (user_id, limit, offset))
        notifications = cursor.fetchall()

        # Format dates
        for n in notifications:
            if isinstance(n.get('created_at'), datetime.datetime):
                n['created_at'] = n['created_at'].strftime('%Y-%m-%d %H:%M:%S')
            if isinstance(n.get('read_at'), datetime.datetime):
                n['read_at'] = n['read_at'].strftime('%Y-%m-%d %H:%M:%S')

        # Get unread count
        cursor.execute("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = %s AND is_read = FALSE", (user_id,))
        unread = cursor.fetchone()['cnt']

        cursor.close()
        conn.close()

        return jsonify({
            'success': True,
            'notifications': notifications,
            'total': total,
            'unread': unread,
            'page': page,
            'limit': limit,
            'pages': (total + limit - 1) // limit if total > 0 else 0
        })
    except Exception as e:
        logger.error(f"Get notifications error: {e}")
        return jsonify({'error': '获取失败'}), 500


@app.route('/api/user/notifications/read', methods=['POST'])
def user_notifications_read():
    """Mark notification as read (single or all)"""
    try:
        data = request.get_json()
        user_id = data.get('user_id')
        notification_id = data.get('notification_id')  # Optional: single notification

        if not user_id:
            return jsonify({'error': 'Missing user_id'}), 400

        conn = get_db_connection()
        if not conn:
            return jsonify({'error': 'Connection failed'}), 500

        cursor = conn.cursor()
        if notification_id:
            cursor.execute(
                "UPDATE notifications SET is_read = TRUE, read_at = NOW() WHERE id = %s AND user_id = %s",
                (notification_id, user_id)
            )
        else:
            cursor.execute(
                "UPDATE notifications SET is_read = TRUE, read_at = NOW() WHERE user_id = %s AND is_read = FALSE",
                (user_id,)
            )
        conn.commit()
        affected = cursor.rowcount
        cursor.close()
        conn.close()

        return jsonify({'success': True, 'marked_read': affected})
    except Exception as e:
        logger.error(f"Mark read error: {e}")
        return jsonify({'error': '操作失败'}), 500


@app.route('/api/user/notifications/dismiss', methods=['POST'])
def user_notifications_dismiss():
    """Dismiss (delete) a single notification"""
    try:
        data = request.get_json()
        user_id = data.get('user_id')
        notification_id = data.get('notification_id')

        if not user_id or not notification_id:
            return jsonify({'error': 'Missing parameters'}), 400

        conn = get_db_connection()
        if not conn:
            return jsonify({'error': 'Connection failed'}), 500

        cursor = conn.cursor()
        cursor.execute(
            "DELETE FROM notifications WHERE id = %s AND user_id = %s",
            (notification_id, user_id)
        )
        conn.commit()
        affected = cursor.rowcount
        cursor.close()
        conn.close()

        if affected == 0:
            return jsonify({'error': 'Notification not found'}), 404

        return jsonify({'success': True, 'dismissed': notification_id})
    except Exception as e:
        logger.error(f"Dismiss notification error: {e}")
        return jsonify({'error': '操作失败'}), 500


@app.route('/api/user/heartbeat', methods=['POST'])
def user_heartbeat():
    """Update user's last_active_at timestamp"""
    try:
        data = request.get_json()
        user_id = data.get('user_id')
        if not user_id:
            return jsonify({'error': 'Missing user_id'}), 400

        conn = get_db_connection()
        if not conn:
            return jsonify({'error': 'Connection failed'}), 500

        cursor = conn.cursor()
        cursor.execute("UPDATE users SET last_active_at = NOW() WHERE id = %s", (user_id,))
        conn.commit()
        cursor.close()
        conn.close()

        return jsonify({'success': True})
    except Exception as e:
        logger.error(f"Heartbeat error: {e}")
        return jsonify({'error': '更新失败'}), 500


# ======== 聊天 API ========


@app.route('/api/chat/unread-counts', methods=['GET'])
def chat_unread_counts():
    """批量查询好友未读消息数"""
    try:
        user_id = request.args.get('user_id', type=int)
        friend_ids = request.args.get('friend_ids', '')
        if not user_id or not friend_ids:
            return jsonify({'error': '参数缺失'}), 400
        ids = [int(x) for x in friend_ids.split(',') if x.strip().isdigit()]
        if not ids:
            return jsonify({'success': True, 'counts': {}})
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        result = {}
        for fid in ids:
            u1, u2 = (user_id, fid) if user_id < fid else (fid, user_id)
            cursor.execute("""
                SELECT COUNT(*) as cnt FROM private_messages pm
                JOIN private_chats pc ON pm.chat_id = pc.id
                WHERE pc.user1_id = %s AND pc.user2_id = %s
                  AND pm.sender_id = %s AND pm.is_read = 0
            """, (u1, u2, fid))
            row = cursor.fetchone()
            unread = row['cnt'] if row and row['cnt'] else 0
            cursor.execute("""
                SELECT COUNT(*) as cnt FROM private_messages pm
                JOIN private_chats pc ON pm.chat_id = pc.id
                WHERE pc.user1_id = %s AND pc.user2_id = %s
                  AND pm.sender_id = %s AND pm.is_read = 0
            """, (u1, u2, fid))
            row2 = cursor.fetchone()
            unread += row2['cnt'] if row2 and row2['cnt'] else 0
            result[str(fid)] = unread
        cursor.close(); conn.close()
        return jsonify({'success': True, 'counts': result})
    except Exception as e:
        logger.error(f"未读计数错误: {e}")
        return jsonify({'error': '查询失败'}), 500


@app.route('/api/upload/banner', methods=['POST'])
@rate_limit(limit=5, per=60)
def upload_banner():
    """宣传图上传（Base64）"""
    try:
        data = request.get_json()
        image = data.get('image', '')
        if not image:
            return jsonify({'error': '参数缺失'}), 400
        if ',' in image:
            image = image.split(',')[1]
        import base64
        try:
            img_data = base64.b64decode(image)
        except:
            return jsonify({'error': '图片数据无效'}), 400
        # Compress banner (max 1920px, quality 85)
        img_data = compress_image(img_data, max_size=1920, quality=85)
        upload_dir = '/var/www/caiziyou/public/uploads/banners'
        os.makedirs(upload_dir, exist_ok=True)
        filename = f'banner_{int(datetime.datetime.utcnow().timestamp())}.png'
        filepath = os.path.join(upload_dir, filename)
        with open(filepath, 'wb') as f:
            f.write(img_data)
        return jsonify({'success': True, 'url': '/uploads/banners/' + filename})
    except Exception as e:
        logger.error(f"宣传图上传错误: {e}")
        return jsonify({'error': '上传失败'}), 500


@app.route('/api/upload/post', methods=['POST'])
@rate_limit(limit=5, per=60)
def upload_post():
    """团动态文件上传，支持 Base64 和 multipart/form-data（二进制）"""
    try:
        raw = None
        is_chunk = False
        chunk_idx = None
        total_chunks = None
        merge_filename = None
        # 支持原始二进制流（XHR send Blob）
        if 'X-File-Name' in request.headers:
            raw = request.get_data()
        elif request.content_type and 'multipart/form-data' in request.content_type:
            if 'chunk' in request.form:
                # 切片上传模式
                is_chunk = True
                chunk_idx = int(request.form.get('chunk'))
                total_chunks = int(request.form.get('total', 1))
                merge_filename = request.form.get('filename', '')
            if 'file' not in request.files:
                return jsonify({'error': '参数缺失'}), 400
            f = request.files['file']
            raw = f.read()
        else:
            # Base64 上传（兼容旧版）
            data = request.get_json()
            file_data = data.get('file', '')
            if not file_data:
                return jsonify({'error': '参数缺失'}), 400
            if ',' in file_data:
                file_data = file_data.split(',')[1]
            import base64
            try:
                raw = base64.b64decode(file_data)
            except:
                return jsonify({'error': '文件数据无效'}), 400
        if raw is None or len(raw) == 0:
            return jsonify({'error': '文件数据无效'}), 400
        upload_dir = '/var/www/caiziyou/public/uploads/posts'
        os.makedirs(upload_dir, exist_ok=True)
        if is_chunk:
            os.makedirs(CHUNK_DIR, exist_ok=True)
            # 用合并后的文件名确定基础名
            base_name = merge_filename or f'post_{int(datetime.datetime.utcnow().timestamp())}_{uuid.uuid4().hex[:4]}'
            ext = os.path.splitext(base_name)[1] or '.mp4'
            final_name = f'post_{int(datetime.datetime.utcnow().timestamp())}_{uuid.uuid4().hex[:4]}{ext}'
            chunk_path = os.path.join(CHUNK_DIR, f'{final_name}_chunk_{chunk_idx}')
            with open(chunk_path, 'wb') as f:
                f.write(raw)
            logger.info(f'保存切片 {chunk_idx}/{total_chunks} => {chunk_path} ({len(raw)} bytes)')
            return jsonify({'success': True, 'url': '', 'chunk': chunk_idx, 'total': total_chunks})
        # 非切片：正常检测类型
        ext = '.bin'
        if raw[:4] == b'\x89PNG':
            ext = '.png'
        elif raw[:2] in (b'\xff\xd8',):
            ext = '.jpg'
        elif raw[:4] == b'\x00\x00\x00\x18ftyp' or raw[:4] == b'\x00\x00\x00\x1cftyp' or raw[:4] == b'ftyp':
            ext = '.mp4'
        elif raw[:4] == b'%PDF':
            ext = '.pdf'
        elif raw[:6] == b'GIF89a' or raw[:6] == b'GIF87a':
            ext = '.gif'
        elif raw[:4] == b'RIFF' and raw[8:12] == b'WEBP':
            ext = '.webp'
        # MP4 检测增强：检查 ftyp box
        if ext == '.bin' and len(raw) > 12:
            ftyp_bytes = raw[4:8] if raw[:4] == b'\x00\x00\x00\x18' or raw[:4] == b'\x00\x00\x00\x1c' else raw[:4]
            if raw.find(b'ftyp') >= 0 and raw.find(b'moov') >= 0 or raw.find(b'mdat') >= 0:
                ext = '.mp4'
        filename = f'post_{int(datetime.datetime.utcnow().timestamp())}_{uuid.uuid4().hex[:4]}{ext}'
        filepath = os.path.join(upload_dir, filename)
        # Compress images (not video/documents)
        if ext in ('.jpg', '.png', '.webp'):
            raw = compress_image(raw, max_size=1920, quality=85)
        with open(filepath, 'wb') as f:
            f.write(raw)
        detected = 'video' if ext == '.mp4' else ('image' if ext in ('.png','.jpg','.gif','.webp') else 'document')
        logger.info(f"上传文件: {filename} ({detected}, {len(raw)} bytes)")
        return jsonify({'success': True, 'url': '/uploads/posts/' + filename, 'detected_type': detected})
    except Exception as e:
        logger.error(f"动态文件上传错误: {e}")
        return jsonify({'error': '上传失败'}), 500


CHUNK_DIR = '/var/www/caiziyou/public/uploads/chunks'

@app.route('/api/upload/post/merge', methods=['POST'])
def upload_post_merge():
    """合并切片文件"""
    try:
        filename = request.form.get('filename', '')
        chunks = int(request.form.get('chunks', 0))
        if not filename or not chunks:
            return jsonify({'error': '参数缺失'}), 400
        os.makedirs(CHUNK_DIR, exist_ok=True)
        upload_dir = '/var/www/caiziyou/public/uploads/posts'
        os.makedirs(upload_dir, exist_ok=True)
        # 根据文件名推断扩展名
        ext = os.path.splitext(filename)[1] or '.mp4'
        out_name = f'post_{int(datetime.datetime.utcnow().timestamp())}_{uuid.uuid4().hex[:4]}{ext}'
        out_path = os.path.join(upload_dir, out_name)
        with open(out_path, 'wb') as out:
            for i in range(chunks):
                chunk_path = os.path.join(CHUNK_DIR, f'{out_name}_chunk_{i}')
                if os.path.exists(chunk_path):
                    with open(chunk_path, 'rb') as f:
                        out.write(f.read())
                    os.remove(chunk_path)
                else:
                    logger.warning(f'切片缺失: {chunk_path}')
        logger.info(f'合并文件: {out_name}')
        return jsonify({'success': True, 'url': '/uploads/posts/' + out_name})
    except Exception as e:
        logger.error(f'合并错误: {e}')
        return jsonify({'error': '合并失败'}), 500


@app.route('/api/upload/avatar', methods=['POST'])
def upload_avatar():
    """头像上传（Base64）"""
    try:
        data = request.get_json()
        user_id = data.get('user_id')
        image = data.get('image', '')
        if not user_id or not image:
            return jsonify({'error': '参数缺失'}), 400
        if ',' in image:
            image = image.split(',')[1]
        import base64
        try:
            img_data = base64.b64decode(image)
        except:
            return jsonify({'error': '图片数据无效'}), 400
        # Compress avatar (max 512px, quality 85)
        img_data = compress_image(img_data, max_size=512, quality=85)
        upload_dir = '/var/www/caiziyou/public/uploads/avatars'
        os.makedirs(upload_dir, exist_ok=True)
        filename = f'avatar_{user_id}_{int(datetime.datetime.utcnow().timestamp())}.png'
        filepath = os.path.join(upload_dir, filename)
        with open(filepath, 'wb') as f:
            f.write(img_data)
        url = f'/uploads/avatars/{filename}'
        conn = get_db_connection()
        if conn:
            cursor = conn.cursor()
            cursor.execute("UPDATE users SET avatar_url = %s WHERE id = %s", (url, user_id))
            conn.commit()
            cursor.close(); conn.close()
        write_log(user_id, 'operation', '头像更新', '上传了新头像')
        return jsonify({'success': True, 'url': url})
    except Exception as e:
        logger.error(f"头像上传错误: {e}")
        return jsonify({'error': '上传失败'}), 500


@app.route('/api/chat/create', methods=['POST'])
def chat_create():
    """创建或获取私聊会话"""
    try:
        data = request.get_json()
        user_id = data.get('user_id')
        target_id = data.get('target_id')
        if not user_id or not target_id or user_id == target_id:
            return jsonify({'error': '参数无效'}), 400
        u1, u2 = (user_id, target_id) if user_id < target_id else (target_id, user_id)
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT id, user1_id, user2_id FROM private_chats WHERE user1_id = %s AND user2_id = %s", (u1, u2))
        chat = cursor.fetchone()
        if not chat:
            cursor.execute("INSERT INTO private_chats (user1_id, user2_id) VALUES (%s, %s)", (u1, u2))
            conn.commit()
            chat_id = cursor.lastrowid
        else:
            chat_id = chat['id']
        cursor.close(); conn.close()
        write_log(user_id, 'operation', '发起聊天', f"与用户 #{target_id} 开始聊天")
        return jsonify({'success': True, 'chat_id': chat_id})
    except Exception as e:
        logger.error(f"创建会话错误: {e}")
        return jsonify({'error': '创建失败'}), 500


@app.route('/api/chat/send', methods=['POST'])
def chat_send():
    """发送消息"""
    try:
        data = request.get_json()
        chat_id = data.get('chat_id')
        sender_id = data.get('sender_id')
        content = data.get('content', '').strip()
        msg_type = data.get('message_type', 'text')
        if not chat_id or not sender_id or (not content and msg_type == 'text'):
            return jsonify({'error': '参数无效'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor()
        cursor.execute("INSERT INTO private_messages (chat_id, sender_id, message_type, content) VALUES (%s, %s, %s, %s)", (chat_id, sender_id, msg_type, content))
        cursor.execute("UPDATE private_chats SET last_message_at = NOW() WHERE id = %s", (chat_id,))
        conn.commit()
        msg_id = cursor.lastrowid
        cursor.close(); conn.close()
        write_log(sender_id, 'operation', '发送消息', f"在会话 #{chat_id} 中发送了一条消息")
        return jsonify({'success': True, 'message_id': msg_id})
    except Exception as e:
        logger.error(f"发送消息错误: {e}")
        return jsonify({'error': '发送失败'}), 500


@app.route('/api/chat/messages', methods=['GET'])
def chat_messages():
    """获取聊天消息并标记已读"""
    try:
        chat_id = request.args.get('chat_id', type=int)
        user_id = request.args.get('user_id', type=int)
        if not chat_id:
            return jsonify({'error': '缺少参数'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        if user_id:
            cursor.execute("UPDATE private_messages SET is_read = TRUE, read_at = NOW() WHERE chat_id = %s AND sender_id != %s AND is_read = FALSE", (chat_id, user_id))
            conn.commit()
        cursor.execute("SELECT id, chat_id, sender_id, message_type, content, media_url, is_read, created_at FROM private_messages WHERE chat_id = %s ORDER BY created_at ASC LIMIT 100", (chat_id,))
        rows = cursor.fetchall()
        cursor.close(); conn.close()
        return jsonify({'success': True, 'messages': rows, 'count': len(rows)})
    except Exception as e:
        logger.error(f"获取消息错误: {e}")
        return jsonify({'error': '获取失败'}), 500


@app.route('/api/chat/stream')
def chat_stream():
    """SSE endpoint for real-time chat messages"""
    chat_id = request.args.get('chat_id', type=int)
    user_id = request.args.get('user_id', type=int)

    if not chat_id:
        return jsonify({'error': 'Missing chat_id'}), 400
    if not user_id:
        return jsonify({'error': 'Missing user_id'}), 400

    def generate():
        last_id = 0
        while True:
            try:
                conn = get_db_connection()
                if conn:
                    cursor = conn.cursor(dictionary=True)
                    cursor.execute("""
                        SELECT id, chat_id, sender_id, message_type, content, media_url, is_read, created_at
                        FROM private_messages
                        WHERE chat_id = %s AND id > %s
                        ORDER BY id ASC
                    """, (chat_id, last_id))
                    messages = cursor.fetchall()
                    cursor.close()
                    conn.close()

                    for msg in messages:
                        if msg['id'] > last_id:
                            last_id = msg['id']
                        data = json.dumps(msg, default=str)
                        yield f"data: {data}\n\n"

                    # Also mark messages as read
                    if messages:
                        conn = get_db_connection()
                        if conn:
                            cursor = conn.cursor()
                            cursor.execute(
                                "UPDATE private_messages SET is_read = TRUE, read_at = NOW() WHERE chat_id = %s AND sender_id != %s AND is_read = FALSE",
                                (chat_id, user_id)
                            )
                            conn.commit()
                            cursor.close()
                            conn.close()

            except Exception as e:
                logger.error(f"SSE stream error: {e}")

            time.sleep(1.5)

    response = Response(generate(), mimetype='text/event-stream')
    response.headers['Cache-Control'] = 'no-cache'
    response.headers['X-Accel-Buffering'] = 'no'
    response.headers['Connection'] = 'keep-alive'
    return response


@app.route('/api/chat/conversations', methods=['GET'])
def chat_conversations():
    """获取会话列表（含未读和最后消息）"""
    try:
        user_id = request.args.get('user_id', type=int)
        if not user_id:
            return jsonify({'error': '缺少用户ID'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT pc.id,
                   CASE WHEN pc.user1_id = %s THEN pc.user2_id ELSE pc.user1_id END AS friend_id,
                   u.username, u.nickname, u.avatar_url, pc.last_message_at,
                   (SELECT content FROM private_messages WHERE chat_id = pc.id ORDER BY created_at DESC LIMIT 1) AS last_message,
                   (SELECT COUNT(*) FROM private_messages WHERE chat_id = pc.id AND sender_id != %s AND is_read = FALSE) AS unread
            FROM private_chats pc
            JOIN users u ON u.id = CASE WHEN pc.user1_id = %s THEN pc.user2_id ELSE pc.user1_id END
            WHERE pc.user1_id = %s OR pc.user2_id = %s
            ORDER BY pc.last_message_at DESC
        """, (user_id, user_id, user_id, user_id, user_id))
        convos = cursor.fetchall()
        cursor.close(); conn.close()
        return jsonify({'success': True, 'conversations': convos})
    except Exception as e:
        logger.error(f"获取会话列表错误: {e}")
        return jsonify({'error': '获取失败'}), 500


# ======== 团动态 API ========

POST_UPLOAD_DIR = '/var/www/caiziyou/public/uploads/posts'

@app.route('/api/community/posts', methods=['GET'])
def community_posts():
    """获取团动态列表"""
    try:
        community_id = request.args.get('community_id', type=int)
        post_id = request.args.get('id', type=int)
        all_posts = request.args.get('all', type=int)
        page = request.args.get('page', 1, type=int)
        limit = request.args.get('limit', 20, type=int)
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        if post_id:
            cursor.execute("""
                SELECT cp.*, c.name as community_name, c.avatar_url as community_avatar,
                u.nickname as author_nickname, u.avatar_url as author_avatar, u.username as author_username,
                (SELECT COUNT(*) FROM post_likes WHERE post_id = cp.id) as like_count,
                (SELECT COUNT(*) FROM post_comments WHERE post_id = cp.id) as comment_count
                FROM community_posts cp
                JOIN communities c ON cp.community_id = c.id
                LEFT JOIN users u ON cp.created_by = u.id
                WHERE cp.id = %s
            """, (post_id,))
            post = cursor.fetchone()
            cursor.close(); conn.close()
            if post:
                return jsonify({'success': True, 'post': post})
            else:
                return jsonify({'error': '不存在'}), 404
        elif all_posts:
            cursor.execute("""
                SELECT cp.*, c.name as community_name, c.avatar_url as community_avatar,
                (SELECT COUNT(*) FROM post_likes WHERE post_id = cp.id) as like_count,
                (SELECT COUNT(*) FROM post_comments WHERE post_id = cp.id) as comment_count
                FROM community_posts cp
                JOIN communities c ON cp.community_id = c.id
                ORDER BY cp.created_at DESC
                LIMIT %s OFFSET %s
            """, (limit, (page-1)*limit))
            posts = cursor.fetchall()
            cursor.execute("SELECT COUNT(*) as total FROM community_posts")
            total = cursor.fetchone()['total']
        elif community_id:
            cursor.execute("""
                SELECT cp.*, c.name as community_name, c.avatar_url as community_avatar,
                (SELECT COUNT(*) FROM post_likes WHERE post_id = cp.id) as like_count,
                (SELECT COUNT(*) FROM post_comments WHERE post_id = cp.id) as comment_count
                FROM community_posts cp
                JOIN communities c ON cp.community_id = c.id
                WHERE cp.community_id = %s
                ORDER BY cp.created_at DESC
                LIMIT %s OFFSET %s
            """, (community_id, limit, (page-1)*limit))
            posts = cursor.fetchall()
            cursor.execute("SELECT COUNT(*) as total FROM community_posts WHERE community_id = %s", (community_id,))
            total = cursor.fetchone()['total']
        else:
            cursor.close(); conn.close()
            return jsonify({'error': '参数缺失'}), 400
        cursor.close(); conn.close()
        return jsonify({'success': True, 'posts': posts, 'total': total, 'page': page, 'limit': limit})
    except Exception as e:
        logger.error(f"获取团动态错误: {e}")
        return jsonify({'error': '获取失败'}), 500


@app.route('/api/community/post/create', methods=['POST'])
def community_post_create():
    """创建团动态"""
    try:
        data = request.get_json()
        community_id = data.get('community_id')
        title = data.get('title', '').strip()
        description = data.get('description', '').strip()
        content_type = data.get('content_type', 'image')
        content_url = data.get('content_url', '').strip()  # 视频URL
        images = data.get('images', [])  # 多图数组
        cover_url = data.get('cover_url', '').strip()
        document_content = data.get('document_content', '')
        user_id = data.get('user_id')
        if not community_id or not title:
            return jsonify({'error': '参数缺失（标题为必填）'}), 400
        if content_type == 'video' and not content_url:
            logger.warning(f"社区动态创建: video but no content_url, data={data}")
            return jsonify({'error': '视频类型需要上传视频文件'}), 400
        if content_type == 'image' and isinstance(images, list) and len(images) > 9:
            return jsonify({'error': '图片最多9张'}), 400
        if content_type == 'document':
            if not document_content:
                return jsonify({'error': '文档内容为空'}), 400
            # 保存文档内容为文件
            doc_filename = f'post_{int(datetime.datetime.utcnow().timestamp())}_{uuid.uuid4().hex[:4]}.txt'
            doc_upload_dir = '/var/www/caiziyou/public/uploads/posts'
            os.makedirs(doc_upload_dir, exist_ok=True)
            doc_path = os.path.join(doc_upload_dir, doc_filename)
            with open(doc_path, 'w', encoding='utf-8') as f:
                f.write(document_content)
            content_url = '/uploads/posts/' + doc_filename
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        # 检查发布权限
        cursor.execute("SELECT post_type, creator_id FROM communities WHERE id = %s", (community_id,))
        community = cursor.fetchone()
        if not community:
            cursor.close(); conn.close()
            return jsonify({'error': '团不存在'}), 404
        if community['post_type'] == 'admin':
            # 仅管理员/创建者可发布
            cursor.execute("SELECT role FROM community_members WHERE community_id = %s AND user_id = %s AND join_status = 'approved'", (community_id, user_id))
            member = cursor.fetchone()
            if not member or member['role'] not in ('admin', 'creator'):
                cursor.close(); conn.close()
                return jsonify({'error': '仅管理员可发布动态'}), 403
        cursor = conn.cursor()
        images_json = json.dumps(images) if isinstance(images, list) and images else None
        cursor.execute("""
            INSERT INTO community_posts (community_id, title, description, content_type, content_url, images, cover_url, created_by)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
        """, (community_id, title, description or None, content_type, content_url or None, images_json, cover_url or None, user_id or 0))
        post_id = cursor.lastrowid
        conn.commit()
        # 给全体团员发通知
        try:
            cursor2 = conn.cursor(dictionary=True)
            cursor2.execute("""
                SELECT cm.user_id, u.nickname, u.username
                FROM community_members cm
                JOIN users u ON cm.user_id = u.id
                WHERE cm.community_id = %s AND cm.user_id != %s
            """, (community_id, user_id))
            members = cursor2.fetchall()
            for member in members:
                try:
                    cursor2.execute("""
                        INSERT INTO notifications (user_id, type, title, content)
                        VALUES (%s, 'community_post', '团新动态', %s)
                    """, (member['user_id'], title))
                except:
                    pass
            cursor2.close()
        except:
            pass

        # ======== QQ群消息推送 ========
        try:
            cursorQQ = conn.cursor(dictionary=True)
            cursorQQ.execute("SELECT qq_group_openid FROM communities WHERE id = %s", (community_id,))
            com_qq = cursorQQ.fetchone()
            cursorQQ.close()
            if com_qq and com_qq.get('qq_group_openid'):
                # 获取发布人昵称
                publisher_name = ''
                if user_id:
                    cursorQQ2 = conn.cursor(dictionary=True)
                    cursorQQ2.execute("SELECT nickname, username FROM users WHERE id = %s", (user_id,))
                    user_info = cursorQQ2.fetchone()
                    cursorQQ2.close()
                    if user_info:
                        publisher_name = user_info.get('nickname') or user_info.get('username')
                
                dynamic_url = f"https://cziyo.club/community/post/{post_id}"
                publish_time_str = datetime.datetime.now().strftime('%Y-%m-%d %H:%M')
                
                # 异步推送（不阻塞响应）
                import threading
                def do_push():
                    send_group_message_retry(
                        com_qq['qq_group_openid'],
                        title,
                        dynamic_url,
                        publisher_name,
                        publish_time_str
                    )
                t = threading.Thread(target=do_push, daemon=True)
                t.start()
            else:
                logger.info(f"团 {community_id} 未配置QQ群，跳过推送")
        except Exception as qq_err:
            logger.error(f"QQ推送发送异常: {qq_err}")
        # ======== QQ推送结束 ========

        cursor.close(); conn.close()
        return jsonify({'success': True, 'post_id': post_id})
    except Exception as e:
        logger.error(f"创建团动态错误: {e}")
        return jsonify({'error': '创建失败'}), 500


@app.route('/api/community/post/delete', methods=['POST'])
def community_post_delete():
    """删除团动态"""
    try:
        data = request.get_json()
        post_id = data.get('post_id')
        community_id = data.get('community_id')
        user_id = data.get('user_id')
        if not post_id:
            return jsonify({'error': '参数缺失'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        # 验证权限
        cursor.execute("SELECT creator_id FROM communities WHERE id = %s", (community_id,))
        com = cursor.fetchone()
        if not com or com['creator_id'] != user_id:
            cursor.close(); conn.close()
            return jsonify({'error': '无权限'}), 403
        cursor.execute("DELETE FROM community_posts WHERE id = %s", (post_id,))
        conn.commit()
        cursor.close(); conn.close()
        return jsonify({'success': True})
    except Exception as e:
        logger.error(f"删除团动态错误: {e}")
        return jsonify({'error': '删除失败'}), 500


# ======== 评论 API ========

@app.route('/api/post/comments', methods=['GET'])
def post_comments():
    """获取评论列表"""
    try:
        post_id = request.args.get('post_id', type=int)
        if not post_id:
            return jsonify({'error': '参数缺失'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT pc.*, u.nickname, u.username, u.avatar_url
            FROM post_comments pc
            JOIN users u ON pc.user_id = u.id
            WHERE pc.post_id = %s
            ORDER BY pc.created_at ASC
        """, (post_id,))
        comments = cursor.fetchall()
        cursor.close(); conn.close()
        return jsonify({'success': True, 'comments': comments})
    except Exception as e:
        logger.error(f"获取评论错误: {e}")
        return jsonify({'error': '获取失败'}), 500


@app.route('/api/post/comment', methods=['POST'])
def post_comment_create():
    """创建评论"""
    try:
        data = request.get_json()
        post_id = data.get('post_id')
        user_id = data.get('user_id')
        content = data.get('content', '').strip()
        if not post_id or not user_id or not content:
            return jsonify({'error': '参数缺失'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor()
        cursor.execute("INSERT INTO post_comments (post_id, user_id, content) VALUES (%s, %s, %s)", (post_id, user_id, content))
        comment_id = cursor.lastrowid
        conn.commit()
        cursor.close(); conn.close()
        return jsonify({'success': True, 'comment_id': comment_id})
    except Exception as e:
        logger.error(f"创建评论错误: {e}")
        return jsonify({'error': '创建失败'}), 500


@app.route('/api/post/comment/delete', methods=['POST'])
def post_comment_delete():
    """删除评论"""
    try:
        data = request.get_json()
        comment_id = data.get('comment_id')
        user_id = data.get('user_id')
        if not comment_id or not user_id:
            return jsonify({'error': '参数缺失'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT pc.user_id, pc.post_id, cp.created_by
            FROM post_comments pc
            JOIN community_posts cp ON pc.post_id = cp.id
            WHERE pc.id = %s
        """, (comment_id,))
        comment = cursor.fetchone()
        if not comment:
            cursor.close(); conn.close()
            return jsonify({'error': '评论不存在'}), 404
        if comment['user_id'] != user_id and comment['created_by'] != user_id:
            cursor.close(); conn.close()
            return jsonify({'error': '无权限'}), 403
        cursor.execute("DELETE FROM post_comments WHERE id = %s", (comment_id,))
        conn.commit()
        cursor.close(); conn.close()
        return jsonify({'success': True})
    except Exception as e:
        logger.error(f"删除评论错误: {e}")
        return jsonify({'error': '删除失败'}), 500


# ======== 点赞 API ========

@app.route('/api/post/likes', methods=['GET'])
def post_likes():
    """获取点赞状态"""
    try:
        post_id = request.args.get('post_id', type=int)
        user_id = request.args.get('user_id', type=int)
        if not post_id:
            return jsonify({'error': '参数缺失'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT COUNT(*) as count FROM post_likes WHERE post_id = %s", (post_id,))
        count = cursor.fetchone()['count']
        liked = False
        if user_id:
            cursor.execute("SELECT id FROM post_likes WHERE post_id = %s AND user_id = %s", (post_id, user_id))
            liked = cursor.fetchone() is not None
        cursor.close(); conn.close()
        return jsonify({'success': True, 'count': count, 'liked': liked})
    except Exception as e:
        logger.error(f"获取点赞错误: {e}")
        return jsonify({'error': '获取失败'}), 500


@app.route('/api/post/stats', methods=['GET'])
def post_total_stats():
    """全局统计：总动态数、总点赞数、总评论数"""
    try:
        community_id = request.args.get('community_id', type=int)
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        post_filter = ''
        params = []
        if community_id:
            post_filter = ' WHERE community_id = %s'
            params.append(community_id)
        cursor.execute(f"SELECT COUNT(*) as total FROM community_posts{post_filter}", params)
        post_total = cursor.fetchone()['total']
        cursor.execute(f"""
            SELECT COALESCE(SUM(lc), 0) as total_likes, COALESCE(SUM(cc), 0) as total_comments
            FROM (
                SELECT
                    (SELECT COUNT(*) FROM post_likes WHERE post_id = cp.id) as lc,
                    (SELECT COUNT(*) FROM post_comments WHERE post_id = cp.id) as cc
                FROM community_posts cp{post_filter}
            ) t
        """, params)
        row = cursor.fetchone()
        cursor.close(); conn.close()
        return jsonify({
            'success': True,
            'post_total': post_total,
            'total_likes': row['total_likes'],
            'total_comments': row['total_comments']
        })
    except Exception as e:
        logger.error(f"全局统计错误: {e}")
        return jsonify({'error': '获取失败'}), 500


@app.route('/api/post/like', methods=['POST'])
def post_like_toggle():
    """切换点赞/取消点赞"""
    try:
        data = request.get_json()
        post_id = data.get('post_id')
        user_id = data.get('user_id')
        if not post_id or not user_id:
            return jsonify({'error': '参数缺失'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT id FROM post_likes WHERE post_id = %s AND user_id = %s", (post_id, user_id))
        existing = cursor.fetchone()
        if existing:
            cursor.execute("DELETE FROM post_likes WHERE id = %s", (existing['id'],))
            conn.commit()
            cursor.close(); conn.close()
            return jsonify({'success': True, 'liked': False, 'action': 'unliked'})
        else:
            cursor.execute("INSERT INTO post_likes (post_id, user_id) VALUES (%s, %s)", (post_id, user_id))
            conn.commit()
            cursor.close(); conn.close()
            return jsonify({'success': True, 'liked': True, 'action': 'liked'})
    except Exception as e:
        logger.error(f"点赞错误: {e}")
        return jsonify({'error': '操作失败'}), 500


@app.route('/api/community/qq-group', methods=['POST'])
def community_set_qq_group():
    """配置团的QQ群OpenID"""
    try:
        data = request.get_json()
        community_id = data.get('community_id')
        qq_group_openid = data.get('qq_group_openid', '').strip()
        user_id = data.get('user_id')
        
        if not community_id:
            return jsonify({'error': '参数缺失'}), 400
        
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        
        # 验证权限（仅团长/管理员可配置）
        cursor.execute("""
            SELECT cm.role, u.nickname
            FROM community_members cm
            JOIN users u ON cm.user_id = u.id
            WHERE cm.community_id = %s AND cm.user_id = %s AND cm.join_status = 'approved'
        """, (community_id, user_id))
        member = cursor.fetchone()
        if not member or member['role'] not in ('admin', 'creator'):
            cursor.close(); conn.close()
            return jsonify({'error': '仅团长或管理员可配置'}), 403
        
        # 更新QQ群配置
        if qq_group_openid:
            cursor.execute("UPDATE communities SET qq_group_openid = %s WHERE id = %s", (qq_group_openid, community_id))
        else:
            cursor.execute("UPDATE communities SET qq_group_openid = NULL WHERE id = %s", (community_id,))
        conn.commit()
        cursor.close(); conn.close()
        
        action = '绑定' if qq_group_openid else '解绑'
        return jsonify({'success': True, 'message': f'QQ群{action}成功'})
    except Exception as e:
        logger.error(f"配置QQ群错误: {e}")
        return jsonify({'error': '配置失败'}), 500


@app.route('/api/community/qq-group', methods=['GET'])
def community_get_qq_group():
    """获取团的QQ群配置"""
    try:
        community_id = request.args.get('community_id')
        if not community_id:
            return jsonify({'error': '参数缺失'}), 400
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT id, name, qq_group_openid FROM communities WHERE id = %s", (community_id,))
        community = cursor.fetchone()
        cursor.close(); conn.close()
        if not community:
            return jsonify({'error': '团不存在'}), 404
        return jsonify({
            'success': True,
            'community': community
        })
    except Exception as e:
        logger.error(f"获取QQ群配置错误: {e}")
        return jsonify({'error': '获取失败'}), 500


@app.route('/api/search', methods=['GET'])
def search():
    """Unified search across posts, users, and communities"""
    try:
        q = request.args.get('q', '').strip()
        search_type = request.args.get('type', 'all')  # all, posts, users, communities
        page = request.args.get('page', 1, type=int)
        limit = request.args.get('limit', 20, type=int)

        if not q or len(q) < 1:
            return jsonify({'error': '请输入搜索关键词'}), 400

        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500

        cursor = conn.cursor(dictionary=True)
        result = {}
        offset = (page - 1) * limit

        # Search posts
        if search_type in ('all', 'posts'):
            try:
                cursor.execute("""
                    SELECT cp.id, cp.title, cp.description, cp.content_type, cp.created_at,
                           c.name as community_name, c.id as community_id,
                           MATCH(cp.title, cp.description) AGAINST(%s IN BOOLEAN MODE) as relevance
                    FROM community_posts cp
                    JOIN communities c ON cp.community_id = c.id
                    WHERE MATCH(cp.title, cp.description) AGAINST(%s IN BOOLEAN MODE)
                    ORDER BY relevance DESC
                    LIMIT %s OFFSET %s
                """, (q + '*', q + '*', limit, offset))
                posts = cursor.fetchall()

                cursor.execute("""
                    SELECT COUNT(*) as cnt FROM community_posts cp
                    WHERE MATCH(cp.title, cp.description) AGAINST(%s IN BOOLEAN MODE)
                """, (q + '*',))
                post_total = cursor.fetchone()['cnt']

                result['posts'] = {
                    'items': posts,
                    'total': post_total,
                    'type': 'posts'
                }
            except Exception as e:
                logger.warning(f"Post search error: {e}")
                result['posts'] = {'items': [], 'total': 0, 'error': str(e), 'type': 'posts'}

        # Search users
        if search_type in ('all', 'users'):
            try:
                cursor.execute("""
                    SELECT id, username, nickname, avatar_url, bio, unique_id,
                           MATCH(username, nickname) AGAINST(%s IN BOOLEAN MODE) as relevance
                    FROM users
                    WHERE MATCH(username, nickname) AGAINST(%s IN BOOLEAN MODE)
                       OR username LIKE %s
                    ORDER BY relevance DESC
                    LIMIT %s OFFSET %s
                """, (q + '*', q + '*', '%' + q + '%', limit, offset))
                users = cursor.fetchall()

                cursor.execute("""
                    SELECT COUNT(*) as cnt FROM users
                    WHERE MATCH(username, nickname) AGAINST(%s IN BOOLEAN MODE)
                       OR username LIKE %s
                """, (q + '*', '%' + q + '%'))
                user_total = cursor.fetchone()['cnt']

                result['users'] = {
                    'items': users,
                    'total': user_total,
                    'type': 'users'
                }
            except Exception as e:
                logger.warning(f"User search error: {e}")
                result['users'] = {'items': [], 'total': 0, 'error': str(e), 'type': 'users'}

        # Search communities
        if search_type in ('all', 'communities'):
            try:
                cursor.execute("""
                    SELECT c.*,
                           MATCH(c.name, c.description) AGAINST(%s IN BOOLEAN MODE) as relevance
                    FROM communities c
                    WHERE MATCH(c.name, c.description) AGAINST(%s IN BOOLEAN MODE)
                       OR c.name LIKE %s
                    ORDER BY relevance DESC
                    LIMIT %s OFFSET %s
                """, (q + '*', q + '*', '%' + q + '%', limit, offset))
                communities = cursor.fetchall()

                cursor.execute("""
                    SELECT COUNT(*) as cnt FROM communities
                    WHERE MATCH(name, description) AGAINST(%s IN BOOLEAN MODE)
                       OR name LIKE %s
                """, (q + '*', '%' + q + '%'))
                comm_total = cursor.fetchone()['cnt']

                result['communities'] = {
                    'items': communities,
                    'total': comm_total,
                    'type': 'communities'
                }
            except Exception as e:
                logger.warning(f"Community search error: {e}")
                result['communities'] = {'items': [], 'total': 0, 'error': str(e), 'type': 'communities'}

        cursor.close()
        conn.close()

        return jsonify({
            'success': True,
            'query': q,
            'results': result
        })

    except Exception as e:
        logger.error(f"Search error: {e}")
        return jsonify({'error': '搜索失败'}), 500


# ======== 密码重置 API ========

@app.route('/api/auth/forgot-password', methods=['POST'])
def forgot_password():
    """Request password reset - generates token"""
    try:
        data = request.get_json()
        username = data.get('username', '').strip()

        if not username:
            return jsonify({'error': '请输入用户名'}), 400

        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500

        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT id, username, email FROM users WHERE username = %s AND status = 'active'", (username,))
        user = cursor.fetchone()

        if not user:
            cursor.close()
            conn.close()
            # Don't reveal whether user exists - return generic message
            return jsonify({'success': True, 'message': '如果账户存在，重置链接已生成'})

        # Generate token
        token = hashlib.sha256(f"{user['id']}:{user['username']}:{datetime.datetime.utcnow().timestamp()}:{os.urandom(16).hex()}".encode()).hexdigest()

        # Set expiry to 24 hours
        expires_at = datetime.datetime.now() + datetime.timedelta(hours=24)

        # Store reset request (status='pending' requires admin approval)
        cursor.execute("""
            INSERT INTO password_resets (user_id, token, status, expires_at)
            VALUES (%s, %s, 'pending', %s)
        """, (user['id'], token, expires_at))

        conn.commit()
        cursor.close()
        conn.close()

        write_log(user['id'], 'operation', '密码重置请求', '用户请求了密码重置')

        # Return the token in the response (so user can use it directly)
        # In production, this would be emailed. Here we show it on screen.
        return jsonify({
            'success': True,
            'message': '重置请求已提交，请联系管理员审核，或直接使用重置链接',
            'token': token,
            'reset_url': f"/reset_password.php?token={token}"
        })

    except Exception as e:
        logger.error(f"Forgot password error: {e}")
        return jsonify({'error': '处理失败'}), 500


@app.route('/api/auth/reset-password', methods=['POST'])
def reset_password():
    """Reset password using token"""
    try:
        data = request.get_json()
        token = data.get('token', '').strip()
        new_password = data.get('new_password', '').strip()

        if not token or not new_password:
            return jsonify({'error': '参数不完整'}), 400

        if len(new_password) < 6:
            return jsonify({'error': '密码至少6位'}), 400

        conn = get_db_connection()
        if not conn:
            return jsonify({'error': '数据库连接失败'}), 500

        cursor = conn.cursor(dictionary=True)

        # Find valid token (approved or auto mode)
        cursor.execute("""
            SELECT pr.*, u.username
            FROM password_resets pr
            JOIN users u ON pr.user_id = u.id
            WHERE pr.token = %s AND pr.status IN ('pending', 'approved') AND pr.expires_at > NOW()
        """, (token,))
        reset = cursor.fetchone()

        if not reset:
            cursor.close()
            conn.close()
            return jsonify({'error': '重置链接无效或已过期'}), 400

        # Auto-approve pending tokens on use (simplified flow)
        if reset['status'] == 'pending':
            cursor.execute("UPDATE password_resets SET status = 'approved', approved_at = NOW() WHERE id = %s", (reset['id'],))

        # Hash new password
        import bcrypt
        new_hash = bcrypt.hashpw(new_password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')

        # Update password
        cursor.execute("UPDATE users SET password_hash = %s WHERE id = %s", (new_hash, reset['user_id']))

        # Mark token as used
        cursor.execute("UPDATE password_resets SET status = 'used', used_at = NOW() WHERE id = %s", (reset['id'],))

        conn.commit()
        cursor.close()
        conn.close()

        write_log(reset['user_id'], 'operation', '密码重置', '用户通过重置链接更改了密码')

        return jsonify({'success': True, 'message': '密码已重置，请使用新密码登录'})

    except Exception as e:
        logger.error(f"Reset password error: {e}")
        return jsonify({'error': '重置失败'}), 500


@app.route('/api/auth/reset-info', methods=['GET'])
def reset_info():
    """Get info about a reset token"""
    try:
        token = request.args.get('token', '').strip()
        if not token:
            return jsonify({'error': 'Missing token'}), 400

        conn = get_db_connection()
        if not conn:
            return jsonify({'error': 'Connection failed'}), 500

        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT pr.status, pr.expires_at, u.username
            FROM password_resets pr
            JOIN users u ON pr.user_id = u.id
            WHERE pr.token = %s
        """, (token,))
        reset = cursor.fetchone()
        cursor.close()
        conn.close()

        if not reset:
            return jsonify({'error': 'Invalid token'}), 404

        if reset['expires_at'] < datetime.datetime.now():
            return jsonify({'error': 'Token expired'}), 400

        return jsonify({
            'success': True,
            'username': reset['username'],
            'status': reset['status'],
            'valid': reset['status'] in ('pending', 'approved')
        })

    except Exception as e:
        logger.error(f"Reset info error: {e}")
        return jsonify({'error': '查询失败'}), 500


@app.route('/api/admin/password-resets', methods=['GET'])
@token_required
def admin_password_resets(current_user):
    """Admin - list all password reset requests"""
    try:
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': 'Connection failed'}), 500

        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT pr.*, u.username, u.nickname
            FROM password_resets pr
            JOIN users u ON pr.user_id = u.id
            ORDER BY pr.requested_at DESC
            LIMIT 50
        """)
        resets = cursor.fetchall()

        for r in resets:
            if isinstance(r.get('requested_at'), datetime.datetime):
                r['requested_at'] = r['requested_at'].strftime('%Y-%m-%d %H:%M')
            if isinstance(r.get('expires_at'), datetime.datetime):
                r['expires_at'] = r['expires_at'].strftime('%Y-%m-%d %H:%M')

        cursor.close()
        conn.close()

        return jsonify({'success': True, 'resets': resets})
    except Exception as e:
        logger.error(f"Admin password resets error: {e}")
        return jsonify({'error': '获取失败'}), 500


@app.route('/api/admin/approve-reset', methods=['POST'])
@token_required
def admin_approve_reset(current_user):
    """Admin approve/reject a password reset request"""
    try:
        data = request.get_json()
        reset_id = data.get('reset_id')
        action = data.get('action', 'approve')

        if not reset_id:
            return jsonify({'error': 'Missing reset_id'}), 400

        conn = get_db_connection()
        if not conn:
            return jsonify({'error': 'Connection failed'}), 500

        cursor = conn.cursor()
        if action == 'approve':
            cursor.execute(
                "UPDATE password_resets SET status = 'approved', approved_by = %s, approved_at = NOW() WHERE id = %s AND status = 'pending'",
                (current_user['id'], reset_id)
            )
        else:
            cursor.execute(
                "UPDATE password_resets SET status = 'expired' WHERE id = %s AND status = 'pending'",
                (reset_id,)
            )
        conn.commit()
        affected = cursor.rowcount
        cursor.close()
        conn.close()

        return jsonify({
            'success': True,
            'affected': affected,
            'message': '已批准' if action == 'approve' else '已拒绝'
        })
    except Exception as e:
        logger.error(f"Admin approve reset error: {e}")
        return jsonify({'error': '操作失败'}), 500


# Ensure last_active_at column exists (safer approach)
try:
    conn = get_db_connection()
    if conn:
        cursor = conn.cursor()
        # Check if column exists by selecting it
        try:
            cursor.execute("SELECT last_active_at FROM users LIMIT 1")
        except:
            # Column doesn't exist — add it
            try:
                cursor.execute("ALTER TABLE users ADD COLUMN last_active_at DATETIME DEFAULT NULL")
                conn.commit()
                logger.info("Added last_active_at column to users table")
            except Exception as alter_err:
                logger.warning(f"Could not add last_active_at column: {alter_err}")
        cursor.close()
        conn.close()
except Exception as e:
    logger.warning(f"last_active_at migration check failed: {e}")

# Create FULLTEXT indexes for search
try:
    conn = get_db_connection()
    if conn:
        cursor = conn.cursor()
        cursor.execute("ALTER TABLE community_posts ADD FULLTEXT INDEX ft_posts_search (title, description)")
        cursor.execute("ALTER TABLE users ADD FULLTEXT INDEX ft_users_search (username, nickname)")
        cursor.execute("ALTER TABLE communities ADD FULLTEXT INDEX ft_communities_search (name, description)")
        conn.commit()
        cursor.close()
        conn.close()
except Exception as e:
    logger.warning(f"Could not create FULLTEXT indexes (may already exist): {e}")

# Create password_resets table on startup
if __name__ == '__main__':
    try:
        conn = get_db_connection()
        if conn:
            cursor = conn.cursor()
            cursor.execute("""
                CREATE TABLE IF NOT EXISTS password_resets (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT NOT NULL,
                    token VARCHAR(64) UNIQUE NOT NULL,
                    status ENUM('pending', 'approved', 'used', 'expired') DEFAULT 'pending',
                    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    approved_by INT DEFAULT NULL,
                    approved_at DATETIME DEFAULT NULL,
                    used_at DATETIME DEFAULT NULL,
                    expires_at DATETIME NOT NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_token (token),
                    INDEX idx_user_id (user_id),
                    INDEX idx_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            """)
            conn.commit()
            cursor.close()
            conn.close()
            logger.info("password_resets table ensured")
    except Exception as e:
        logger.error(f"Failed to create password_resets table: {e}")

    app.run(host='0.0.0.0', port=5000, debug=False)