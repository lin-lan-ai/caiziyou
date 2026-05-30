#!/usr/bin/env python3
import mysql.connector
from dotenv import load_dotenv
import os
import json
from datetime import date, timedelta

load_dotenv('/var/www/caiziyou/.env')

config = {
    'host': os.environ.get('COMMUNITY_DB_HOST', 'localhost'),
    'user': os.environ.get('COMMUNITY_DB_USER', 'caiziyou_community'),
    'password': os.environ.get('COMMUNITY_DB_PASS', ''),
    'database': os.environ.get('COMMUNITY_DB_NAME', 'caiziyou_community_db'),
    'charset': 'utf8mb4'
}

db = mysql.connector.connect(**config)
cursor = db.cursor()

print("插入测试数据...")

# 试卷
exams = [
    ("2025年中考数学真题", "math", 2025, "real", 120, 120, 25),
    ("2024年中考物理真题", "physics", 2024, "real", 90, 100, 20),
    ("2025年中考英语真题", "english", 2025, "real", 100, 120, 30),
    ("数学模拟卷(一)", "math", 2026, "simulate", 120, 120, 25),
    ("物理电学专项", "physics", 2026, "special", 60, 50, 10),
]

for e in exams:
    cursor.execute("INSERT IGNORE INTO zhongkao_exams (title,subject,year,type,duration,total_score,question_count) VALUES (%s,%s,%s,%s,%s,%s,%s)", e)

# 题目
questions = [
    # 数学真题
    (1, 1, "choice", "下列计算正确的是()", json.dumps(["A. a²+a²=a⁴","B. a³·a²=a⁶","C. (-a²)³=-a⁶","D. a⁸÷a²=a⁴"]), "C", "幂的运算法则", 4),
    (1, 2, "choice", "一组数据3,5,7,8,8的中位数是()", json.dumps(["A. 5","B. 7","C. 8","D. 6"]), "B", "中位数是排序后中间的数", 4),
    (1, 3, "fill", "分解因式: x²-9=______", None, "(x+3)(x-3)", "平方差公式", 4),
    (1, 4, "choice", "二次函数y=x²-2x+1的顶点坐标是()", json.dumps(["A. (1,0)","B. (0,1)","C. (-1,0)","D. (1,1)"]), "A", "配方法求顶点", 4),
    (1, 5, "fill", "若√(x-2)有意义，则x的取值范围是______", None, "x≥2", "被开方数≥0", 4),
    # 物理真题
    (2, 1, "choice", "下列属于导体的是()", json.dumps(["A. 橡胶","B. 铜","C. 塑料","D. 玻璃"]), "B", "金属是导体", 5),
    (2, 2, "choice", "欧姆定律公式是()", json.dumps(["A. I=U/R","B. U=IR","C. R=U/I","D. 以上都是"]), "D", "欧姆定律三种形式", 5),
    (2, 3, "fill", "1kΩ=______Ω", None, "1000", "单位换算", 5),
    (2, 4, "choice", "串联电路中电流()", json.dumps(["A. 处处相等","B. 分流","C. 不确定","D. 为零"]), "A", "串联电路特点", 5),
    (2, 5, "fill", "电功率的单位是______", None, "瓦特(W)", "电功率单位", 5),
]

for q in questions:
    cursor.execute("INSERT IGNORE INTO zhongkao_questions (exam_id,question_number,question_type,question_text,options,correct_answer,explanation,score) VALUES (%s,%s,%s,%s,%s,%s,%s,%s)", q)

# 知识点
knowledge = [
    ("math", "代数", "二次函数", "y=ax²+bx+c (a≠0)\n顶点: (-b/2a, (4ac-b²)/4a)\n对称轴: x=-b/2a", "important", 1),
    ("math", "代数", "一元二次方程", "ax²+bx+c=0\n求根公式: x=(-b±√Δ)/2a\n判别式: Δ=b²-4ac", "important", 2),
    ("math", "几何", "相似三角形", "判定: AA、SAS、SSS\n性质: 对应边成比例\n面积比=相似比²", "important", 3),
    ("math", "几何", "圆", "垂径定理\n圆周角定理\n切线判定与性质", "normal", 4),
    ("math", "函数", "反比例函数", "y=k/x (k≠0)\n图像: 双曲线\n性质: k>0在一三象限", "normal", 5),
    ("physics", "力学", "牛顿第一定律", "物体在不受力时保持静止或匀速直线运动\n惯性: 物体保持运动状态的性质", "important", 1),
    ("physics", "电学", "欧姆定律", "I=U/R\n串联: I=I₁=I₂, U=U₁+U₂\n并联: U=U₁=U₂, I=I₁+I₂", "important", 2),
    ("physics", "电学", "电功率", "P=UI=I²R=U²/R\n单位: 瓦特(W)\n1kW=1000W", "normal", 3),
    ("physics", "光学", "光的折射", "折射定律\n入射角与折射角关系\n光从空气到水: 折射角<入射角", "normal", 4),
    ("physics", "热学", "比热容", "Q=cmΔt\n单位: J/(kg·℃)\n水的比热容最大", "easy", 5),
]

for k in knowledge:
    cursor.execute("INSERT IGNORE INTO zhongkao_knowledge (subject,category,title,content,importance,sort_order) VALUES (%s,%s,%s,%s,%s,%s)", k)

# 学习计划
today = date.today()
tomorrow = today + timedelta(days=1)
plans = [
    (1, "math", "复习二次函数图像与性质", "09:00", "10:30", today),
    (1, "english", "背诵Unit8单词+阅读理解2篇", "10:30", "12:00", today),
    (1, "physics", "电学综合练习题10道", "15:00", "16:30", today),
    (1, "chinese", "古诗词默写5首", "09:00", "10:00", tomorrow),
    (1, "chemistry", "酸碱盐实验笔记整理", "14:00", "15:30", tomorrow),
    (1, "math", "一元二次方程专项练习", "16:00", "17:30", tomorrow),
]

for p in plans:
    cursor.execute("INSERT IGNORE INTO zhongkao_plans (user_id,subject,title,start_time,end_time,plan_date) VALUES (%s,%s,%s,%s,%s,%s)", p)

# 学习统计（最近7天）
for i in range(7):
    d = today - timedelta(days=i)
    minutes = 90 + (i * 15) % 60
    done = 20 + (i * 7) % 30
    correct = int(done * (0.65 + (i * 0.05) % 0.2))
    cursor.execute("INSERT IGNORE INTO zhongkao_stats (user_id,stat_date,study_minutes,questions_done,questions_correct) VALUES (1,%s,%s,%s,%s)", (d, minutes, done, correct))

# 答题记录（模拟做过数学真题的前5题）
for qid in range(1, 6):
    cursor.execute("SELECT correct_answer FROM zhongkao_questions WHERE id=%s", (qid,))
    ans = cursor.fetchone()
    if ans:
        user_ans = ans[0] if qid % 3 != 0 else "wrong"  # 2/3 正确
        is_correct = 1 if user_ans == ans[0] else 0
        cursor.execute("INSERT IGNORE INTO zhongkao_answers (user_id,exam_id,question_id,user_answer,is_correct) VALUES (1,1,%s,%s,%s)", (qid, user_ans, is_correct))

# 错题（从答错的题自动生成）
cursor.execute("INSERT IGNORE INTO zhongkao_mistakes (user_id,question_id,exam_id,user_answer,correct_answer,status) SELECT user_id,question_id,exam_id,user_answer,'C','new' FROM zhongkao_answers WHERE is_correct=0 AND user_id=1")

db.commit()
cursor.close()
db.close()
print("测试数据插入完成!")
