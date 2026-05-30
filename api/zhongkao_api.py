from flask import Blueprint, request, jsonify, session
import json
from datetime import datetime, date
import os
from dotenv import load_dotenv

load_dotenv('/var/www/caiziyou/.env')

zhongkao_bp = Blueprint('zhongkao', __name__)

DB_CONFIG = {
    'host': os.environ.get('COMMUNITY_DB_HOST', 'localhost'),
    'user': os.environ.get('COMMUNITY_DB_USER', 'caiziyou'),
    'password': os.environ.get('COMMUNITY_DB_PASS', ''),
    'database': os.environ.get('COMMUNITY_DB_NAME', 'caiziyou_community_db'),
    'charset': 'utf8mb4'
}

def get_db():
    import mysql.connector
    return mysql.connector.connect(**DB_CONFIG)

def get_user_id():
    return session.get('user_id', 1)

@zhongkao_bp.route('/api/zhongkao/plans', methods=['GET'])
def get_plans():
    user_id = get_user_id()
    plan_date = request.args.get('date', date.today().isoformat())
    db = get_db()
    cursor = db.cursor(dictionary=True)
    cursor.execute('SELECT * FROM zhongkao_plans WHERE user_id = %s AND plan_date = %s ORDER BY start_time', (user_id, plan_date))
    plans = cursor.fetchall()
    cursor.close()
    db.close()
    for plan in plans:
        if plan.get('start_time'): plan['start_time'] = str(plan['start_time'])[:5]
        if plan.get('end_time'): plan['end_time'] = str(plan['end_time'])[:5]
    return jsonify({'plans': plans})

@zhongkao_bp.route('/api/zhongkao/plans', methods=['POST'])
def add_plan():
    user_id = get_user_id()
    data = request.json
    db = get_db()
    cursor = db.cursor()
    cursor.execute('INSERT INTO zhongkao_plans (user_id, subject, title, start_time, end_time, plan_date) VALUES (%s,%s,%s,%s,%s,%s)',
        (user_id, data['subject'], data['title'], data['start_time'], data['end_time'], data.get('plan_date', date.today().isoformat())))
    db.commit()
    plan_id = cursor.lastrowid
    cursor.close()
    db.close()
    return jsonify({'id': plan_id})

@zhongkao_bp.route('/api/zhongkao/plans/<int:pid>/toggle', methods=['POST'])
def toggle_plan(pid):
    user_id = get_user_id()
    db = get_db()
    cursor = db.cursor()
    cursor.execute('UPDATE zhongkao_plans SET completed = NOT completed WHERE id = %s AND user_id = %s', (pid, user_id))
    db.commit()
    cursor.close()
    db.close()
    return jsonify({'ok': True})

@zhongkao_bp.route('/api/zhongkao/plans/<int:pid>', methods=['DELETE'])
def delete_plan(pid):
    user_id = get_user_id()
    db = get_db()
    cursor = db.cursor()
    cursor.execute('DELETE FROM zhongkao_plans WHERE id = %s AND user_id = %s', (pid, user_id))
    db.commit()
    cursor.close()
    db.close()
    return jsonify({'ok': True})

@zhongkao_bp.route('/api/zhongkao/knowledge', methods=['GET'])
def get_knowledge():
    subject = request.args.get('subject', 'math')
    db = get_db()
    cursor = db.cursor(dictionary=True)
    cursor.execute('SELECT * FROM zhongkao_knowledge WHERE subject = %s ORDER BY category, sort_order', (subject,))
    items = cursor.fetchall()
    cursor.close()
    db.close()
    grouped = {}
    for item in items:
        cat = item['category']
        if cat not in grouped: grouped[cat] = []
        grouped[cat].append(item)
    return jsonify({'knowledge': grouped})

@zhongkao_bp.route('/api/zhongkao/exams', methods=['GET'])
def get_exams():
    subject = request.args.get('subject')
    user_id = get_user_id()
    db = get_db()
    cursor = db.cursor(dictionary=True)
    if subject:
        cursor.execute('SELECT * FROM zhongkao_exams WHERE subject = %s ORDER BY year DESC', (subject,))
    else:
        cursor.execute('SELECT * FROM zhongkao_exams ORDER BY year DESC')
    exams = cursor.fetchall()
    for exam in exams:
        cursor.execute('SELECT COUNT(*) as cnt FROM zhongkao_answers WHERE user_id=%s AND exam_id=%s', (user_id, exam['id']))
        r = cursor.fetchone()
        exam['answered'] = r['cnt'] if r else 0
        exam['progress'] = round(exam['answered']/exam['question_count']*100) if exam['question_count']>0 else 0
    cursor.close()
    db.close()
    return jsonify({'exams': exams})

@zhongkao_bp.route('/api/zhongkao/exams/<int:eid>', methods=['GET'])
def get_exam(eid):
    db = get_db()
    cursor = db.cursor(dictionary=True)
    cursor.execute('SELECT * FROM zhongkao_exams WHERE id=%s', (eid,))
    exam = cursor.fetchone()
    cursor.execute('SELECT * FROM zhongkao_questions WHERE exam_id=%s ORDER BY question_number', (eid,))
    questions = cursor.fetchall()
    for q in questions:
        if q.get('options'): q['options'] = json.loads(q['options'])
    cursor.close()
    db.close()
    return jsonify({'exam': exam, 'questions': questions})

@zhongkao_bp.route('/api/zhongkao/answer', methods=['POST'])
def submit_answer():
    user_id = get_user_id()
    data = request.json
    db = get_db()
    cursor = db.cursor(dictionary=True)
    cursor.execute('SELECT correct_answer, score FROM zhongkao_questions WHERE id=%s', (data['question_id'],))
    q = cursor.fetchone()
    is_correct = 1 if data['answer'] == q['correct_answer'] else 0
    cursor.execute('INSERT INTO zhongkao_answers (user_id,exam_id,question_id,user_answer,is_correct) VALUES (%s,%s,%s,%s,%s) ON DUPLICATE KEY UPDATE user_answer=%s,is_correct=%s',
        (user_id, data['exam_id'], data['question_id'], data['answer'], is_correct, data['answer'], is_correct))
    if not is_correct:
        cursor.execute('INSERT IGNORE INTO zhongkao_mistakes (user_id,question_id,exam_id,user_answer,correct_answer) VALUES (%s,%s,%s,%s,%s)',
            (user_id, data['question_id'], data['exam_id'], data['answer'], q['correct_answer']))
    db.commit()
    cursor.close()
    db.close()
    return jsonify({'is_correct': is_correct, 'correct_answer': q['correct_answer']})

@zhongkao_bp.route('/api/zhongkao/mistakes', methods=['GET'])
def get_mistakes():
    user_id = get_user_id()
    status = request.args.get('status')
    db = get_db()
    cursor = db.cursor(dictionary=True)
    sql = 'SELECT m.*, q.question_text, q.question_type, q.options, q.correct_answer, q.explanation, e.title as exam_title, e.subject FROM zhongkao_mistakes m JOIN zhongkao_questions q ON m.question_id=q.id JOIN zhongkao_exams e ON m.exam_id=e.id WHERE m.user_id=%s'
    params = [user_id]
    if status:
        sql += ' AND m.status=%s'
        params.append(status)
    sql += ' ORDER BY m.created_at DESC'
    cursor.execute(sql, params)
    mistakes = cursor.fetchall()
    for m in mistakes:
        if m.get('options'): m['options'] = json.loads(m['options'])
    cursor.close()
    db.close()
    return jsonify({'mistakes': mistakes})

@zhongkao_bp.route('/api/zhongkao/mistakes/<int:mid>/status', methods=['POST'])
def update_mistake(mid):
    user_id = get_user_id()
    data = request.json
    db = get_db()
    cursor = db.cursor()
    cursor.execute('UPDATE zhongkao_mistakes SET status=%s WHERE id=%s AND user_id=%s', (data['status'], mid, user_id))
    db.commit()
    cursor.close()
    db.close()
    return jsonify({'ok': True})

@zhongkao_bp.route('/api/zhongkao/stats', methods=['GET'])
def get_stats():
    user_id = get_user_id()
    today = date.today()
    db = get_db()
    cursor = db.cursor(dictionary=True)
    cursor.execute('SELECT * FROM zhongkao_stats WHERE user_id=%s AND stat_date=%s', (user_id, today))
    today_stat = cursor.fetchone() or {'study_minutes':0,'questions_done':0,'questions_correct':0}
    cursor.execute('SELECT COUNT(DISTINCT stat_date) as streak FROM zhongkao_stats WHERE user_id=%s AND stat_date>=%s AND questions_done>0', (user_id, today))
    streak = cursor.fetchone()['streak']
    cursor.execute('SELECT stat_date,study_minutes,questions_done,questions_correct FROM zhongkao_stats WHERE user_id=%s AND stat_date>=%s ORDER BY stat_date', (user_id, today))
    week = cursor.fetchall()
    cursor.execute('''SELECT e.subject, COUNT(*) as total, SUM(CASE WHEN a.is_correct=1 THEN 1 ELSE 0 END) as correct
        FROM zhongkao_answers a JOIN zhongkao_questions q ON a.question_id=q.id JOIN zhongkao_exams e ON a.exam_id=e.id WHERE a.user_id=%s GROUP BY e.subject''', (user_id,))
    subjects = cursor.fetchall()
    cursor.close()
    db.close()
    for w in week: w['stat_date'] = w['stat_date'].isoformat()
    return jsonify({'today': today_stat, 'streak': streak, 'week': week, 'subjects': subjects})

@zhongkao_bp.route('/api/zhongkao/progress', methods=['GET'])
def get_progress():
    user_id = get_user_id()
    db = get_db()
    cursor = db.cursor(dictionary=True)
    cursor.execute('''SELECT e.subject, COUNT(DISTINCT q.id) as total, COUNT(DISTINCT a.question_id) as answered
        FROM zhongkao_exams e JOIN zhongkao_questions q ON e.id=q.exam_id
        LEFT JOIN zhongkao_answers a ON q.id=a.question_id AND a.user_id=%s GROUP BY e.subject''', (user_id,))
    progress = cursor.fetchall()
    cursor.close()
    db.close()
    for p in progress: p['percent'] = round(p['answered']/p['total']*100) if p['total']>0 else 0
    return jsonify({'progress': progress})
