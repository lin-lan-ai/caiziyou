<?php
session_start();
$exam_id = intval($_GET['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>答题 - 中考备战</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    :root { --primary: #4f46e5; --success: #22c55e; --danger: #ef4444; --bg: #f8fafc; --card: #fff; --text: #1e293b; --text-dim: #64748b; --border: #e2e8f0; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: -apple-system, sans-serif; background: var(--bg); color: var(--text); }
    .topbar { background: var(--card); border-bottom: 1px solid var(--border); padding: 12px 20px; position: sticky; top: 0; z-index: 100; }
    .topbar-inner { max-width: 900px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; }
    .topbar-back { color: var(--text-dim); text-decoration: none; }
    .topbar-brand { font-weight: 600; color: var(--primary); }
    .topbar-timer { font-size: 18px; font-weight: 600; color: var(--danger); }
    .container { max-width: 900px; margin: 0 auto; padding: 20px; }
    .exam-header { background: var(--card); border-radius: 16px; padding: 24px; border: 1px solid var(--border); margin-bottom: 24px; }
    .exam-title { font-size: 24px; font-weight: 700; margin-bottom: 12px; }
    .exam-info { display: flex; gap: 20px; color: var(--text-dim); }
    .progress-bar { height: 8px; background: var(--bg); border-radius: 4px; margin: 16px 0; }
    .progress-fill { height: 100%; background: var(--primary); border-radius: 4px; transition: width .3s; }
    .progress-text { font-size: 14px; color: var(--text-dim); text-align: center; }
    .question-card { background: var(--card); border-radius: 16px; padding: 24px; border: 1px solid var(--border); margin-bottom: 20px; }
    .question-number { font-size: 14px; color: var(--primary); font-weight: 600; margin-bottom: 8px; }
    .question-type { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; margin-left: 8px; }
    .question-type.choice { background: #dbeafe; color: #2563eb; }
    .question-type.fill { background: #dcfce7; color: #16a34a; }
    .question-text { font-size: 16px; line-height: 1.8; margin-bottom: 20px; }
    .options { display: flex; flex-direction: column; gap: 12px; }
    .option { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border: 2px solid var(--border); border-radius: 12px; cursor: pointer; transition: .2s; }
    .option:hover { border-color: var(--primary); background: rgba(79,70,229,0.05); }
    .option.selected { border-color: var(--primary); background: rgba(79,70,229,0.1); }
    .option.correct { border-color: var(--success); background: rgba(34,197,94,0.1); }
    .option.wrong { border-color: var(--danger); background: rgba(239,68,68,0.1); }
    .option-letter { width: 32px; height: 32px; border-radius: 50%; background: var(--bg); display: flex; align-items: center; justify-content: center; font-weight: 600; }
    .option.selected .option-letter { background: var(--primary); color: white; }
    .fill-input { width: 100%; padding: 12px 16px; border: 2px solid var(--border); border-radius: 10px; font-size: 16px; }
    .fill-input:focus { outline: none; border-color: var(--primary); }
    .explanation { background: #f0fdf4; border-radius: 12px; padding: 16px; margin-top: 16px; border-left: 4px solid var(--success); display: none; }
    .explanation.show { display: block; }
    .btn-group { display: flex; gap: 12px; justify-content: center; margin-top: 24px; }
    .btn { padding: 12px 24px; border-radius: 10px; border: none; font-size: 16px; cursor: pointer; }
    .btn-primary { background: var(--primary); color: white; }
    .btn-outline { background: transparent; border: 2px solid var(--border); }
    .btn-success { background: var(--success); color: white; }
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 200; align-items: center; justify-content: center; }
    .modal.open { display: flex; }
    .modal-content { background: var(--card); border-radius: 16px; padding: 32px; text-align: center; max-width: 400px; }
    .modal-score { font-size: 48px; font-weight: 800; color: var(--primary); margin: 16px 0; }
    .loading { text-align: center; padding: 40px; color: var(--text-dim); }
  
/* 修复顶部导航 */
.topbar {
  position: sticky;
  top: 0;
  z-index: 1000;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  background: white;
}
.container {
  padding-top: 20px;
  padding-bottom: 40px;
}
</style>
</head>
<body>
  <div class="topbar">
    <div class="topbar-inner">
      <a href="practice.php" class="topbar-back"><i class="fas fa-arrow-left"></i> 返回</a>
      <div class="topbar-brand">答题中</div>
      <div class="topbar-timer" id="timer">--:--:--</div>
    </div>
  </div>
  
  <div class="container">
    <div class="exam-header">
      <div class="exam-title" id="examTitle">加载中...</div>
      <div class="exam-info" id="examInfo"></div>
      <div class="progress-bar">
        <div class="progress-fill" id="progressFill" style="width: 0%"></div>
      </div>
      <div class="progress-text">已完成 <span id="answered">0</span> / <span id="totalQuestions">0</span> 题</div>
    </div>
    
    <div id="questionsContainer">
      <div class="loading"><i class="fas fa-spinner fa-spin"></i> 加载题目中...</div>
    </div>
    
    <div class="btn-group">
      <button class="btn btn-outline" onclick="showAllAnswers()"><i class="fas fa-eye"></i> 查看答案</button>
      <button class="btn btn-primary" onclick="submitExam()"><i class="fas fa-paper-plane"></i> 提交试卷</button>
    </div>
  </div>
  
  <div class="modal" id="resultModal">
    <div class="modal-content">
      <div style="font-size:64px;margin-bottom:16px">🎉</div>
      <div style="font-size:24px;font-weight:700;margin-bottom:8px">试卷完成！</div>
      <div class="modal-score" id="finalScore">--</div>
      <div>正确率：<span id="accuracy">--</span></div>
      <div class="btn-group" style="margin-top:20px">
        <button class="btn btn-outline" onclick="location.href='practice.php'">返回列表</button>
        <button class="btn btn-success" onclick="location.href='mistakes.php'">查看错题</button>
      </div>
    </div>
  </div>
  
  <script src="api_client.js"></script>
  <script>
    const examId = <?=$exam_id?>;
    let examData = null;
    let questions = [];
    let userAnswers = {};
    let answeredCount = 0;
    
    async function loadExam() {
      try {
        const data = await ZhongkaoAPI.getExam(examId);
        examData = data.exam;
        questions = data.questions;
        
        // 更新标题
        document.getElementById('examTitle').textContent = examData.title;
        document.getElementById('examInfo').innerHTML = `
          <span><i class="fas fa-clock"></i> ${examData.duration}分钟</span>
          <span><i class="fas fa-question-circle"></i> ${questions.length}题</span>
          <span><i class="fas fa-star"></i> ${examData.total_score}分</span>
        `;
        document.getElementById('totalQuestions').textContent = questions.length;
        
        // 设置倒计时
        let timeLeft = examData.duration * 60;
        setInterval(() => {
          if (timeLeft > 0) {
            timeLeft--;
            const h = Math.floor(timeLeft / 3600);
            const m = Math.floor((timeLeft % 3600) / 60);
            const s = timeLeft % 60;
            document.getElementById('timer').textContent = 
              String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
          }
        }, 1000);
        
        // 渲染题目
        renderQuestions();
      } catch(e) {
        console.error('加载试卷失败:', e);
        document.getElementById('questionsContainer').innerHTML = '<div class="loading">加载失败，请重试</div>';
      }
    }
    
    function renderQuestions() {
      let html = '';
      questions.forEach((q, idx) => {
        const typeClass = q.question_type === 'choice' ? 'choice' : 'fill';
        const typeText = q.question_type === 'choice' ? '选择题' : '填空题';
        
        html += `<div class="question-card" id="q${q.id}">
          <div class="question-number">第 ${q.question_number} 题 <span class="question-type ${typeClass}">${typeText}</span></div>
          <div class="question-text">${q.question_text}</div>`;
        
        if (q.question_type === 'choice' && q.options) {
          html += '<div class="options">';
          const letters = ['A', 'B', 'C', 'D'];
          q.options.forEach((opt, i) => {
            html += `<div class="option" onclick="selectOption(this, ${q.id}, '${letters[i]}')">
              <div class="option-letter">${letters[i]}</div>
              <div>${opt.replace(/^[A-D]\.\s*/, '')}</div>
            </div>`;
          });
          html += '</div>';
        } else {
          html += `<input type="text" class="fill-input" placeholder="请输入答案" oninput="updateFill(this, ${q.id})">`;
        }
        
        html += `<div class="explanation" id="explain${q.id}">
          <div style="font-weight:600;color:var(--success);margin-bottom:8px"><i class="fas fa-lightbulb"></i> 解析</div>
          <div>${q.explanation || '暂无解析'}</div>
        </div></div>`;
      });
      
      document.getElementById('questionsContainer').innerHTML = html;
    }
    
    function selectOption(el, qId, letter) {
      const card = document.getElementById('q' + qId);
      card.querySelectorAll('.option').forEach(o => o.classList.remove('selected'));
      el.classList.add('selected');
      userAnswers[qId] = letter;
      answeredCount = Object.keys(userAnswers).length;
      updateProgress();
    }
    
    function updateFill(el, qId) {
      userAnswers[qId] = el.value.trim();
      answeredCount = Object.values(userAnswers).filter(v => v).length;
      updateProgress();
    }
    
    function updateProgress() {
      document.getElementById('answered').textContent = answeredCount;
      document.getElementById('progressFill').style.width = (answeredCount / questions.length * 100) + '%';
    }
    
    async function showAllAnswers() {
      for (const q of questions) {
        const card = document.getElementById('q' + q.id);
        const explain = document.getElementById('explain' + q.id);
        explain.classList.add('show');
        
        const userAns = userAnswers[q.id];
        if (userAns !== q.correct_answer) {
          if (q.question_type === 'choice') {
            card.querySelectorAll('.option').forEach(o => {
              if (o.querySelector('.option-letter').textContent === q.correct_answer) {
                o.classList.add('correct');
              }
            });
          }
        }
      }
    }
    
    async function submitExam() {
      let correct = 0;
      for (const q of questions) {
        if (userAnswers[q.id] === q.correct_answer) correct++;
      }
      
      const score = Math.round(correct / questions.length * examData.total_score);
      const accuracy = Math.round(correct / questions.length * 100);
      
      document.getElementById('finalScore').textContent = score + ' 分';
      document.getElementById('accuracy').textContent = accuracy + '%';
      document.getElementById('resultModal').classList.add('open');
    }
    
    document.addEventListener('DOMContentLoaded', loadExam);
  </script>
</body>
</html>
