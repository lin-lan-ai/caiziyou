<?php
session_start();
$exam_id = $_GET['id'] ?? 'unknown';
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
    
    /* 试卷头部 */
    .exam-header { background: var(--card); border-radius: 16px; padding: 24px; border: 1px solid var(--border); margin-bottom: 24px; }
    .exam-title { font-size: 24px; font-weight: 700; margin-bottom: 12px; }
    .exam-info { display: flex; gap: 20px; color: var(--text-dim); }
    
    /* 进度条 */
    .progress-bar { height: 8px; background: var(--bg); border-radius: 4px; margin: 16px 0; overflow: hidden; }
    .progress-fill { height: 100%; background: var(--primary); border-radius: 4px; transition: width .3s; }
    .progress-text { font-size: 14px; color: var(--text-dim); text-align: center; }
    
    /* 题目 */
    .question-card { background: var(--card); border-radius: 16px; padding: 24px; border: 1px solid var(--border); margin-bottom: 20px; }
    .question-number { font-size: 14px; color: var(--primary); font-weight: 600; margin-bottom: 8px; }
    .question-type { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; margin-left: 8px; }
    .question-type.choice { background: #dbeafe; color: #2563eb; }
    .question-type.fill { background: #dcfce7; color: #16a34a; }
    .question-type.calc { background: #fef3c7; color: #d97706; }
    .question-text { font-size: 16px; line-height: 1.8; margin-bottom: 20px; }
    
    /* 选项 */
    .options { display: flex; flex-direction: column; gap: 12px; }
    .option { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border: 2px solid var(--border); border-radius: 12px; cursor: pointer; transition: .2s; }
    .option:hover { border-color: var(--primary); background: rgba(79, 70, 229, 0.05); }
    .option.selected { border-color: var(--primary); background: rgba(79, 70, 229, 0.1); }
    .option.correct { border-color: var(--success); background: rgba(34, 197, 94, 0.1); }
    .option.wrong { border-color: var(--danger); background: rgba(239, 68, 68, 0.1); }
    .option-letter { width: 32px; height: 32px; border-radius: 50%; background: var(--bg); display: flex; align-items: center; justify-content: center; font-weight: 600; }
    .option.selected .option-letter { background: var(--primary); color: white; }
    .option.correct .option-letter { background: var(--success); color: white; }
    .option.wrong .option-letter { background: var(--danger); color: white; }
    
    /* 填空题 */
    .fill-input { width: 100%; padding: 12px 16px; border: 2px solid var(--border); border-radius: 10px; font-size: 16px; transition: .2s; }
    .fill-input:focus { outline: none; border-color: var(--primary); }
    
    /* 解答题 */
    .answer-textarea { width: 100%; min-height: 150px; padding: 12px 16px; border: 2px solid var(--border); border-radius: 10px; font-size: 16px; resize: vertical; transition: .2s; }
    .answer-textarea:focus { outline: none; border-color: var(--primary); }
    
    /* 解析 */
    .explanation { background: #f0fdf4; border-radius: 12px; padding: 16px; margin-top: 16px; border-left: 4px solid var(--success); display: none; }
    .explanation.show { display: block; }
    .explanation-title { font-weight: 600; color: var(--success); margin-bottom: 8px; }
    
    /* 按钮 */
    .btn-group { display: flex; gap: 12px; justify-content: center; margin-top: 24px; }
    .btn { padding: 12px 24px; border-radius: 10px; border: none; font-size: 16px; cursor: pointer; transition: .2s; }
    .btn-primary { background: var(--primary); color: white; }
    .btn-primary:hover { background: #4338ca; }
    .btn-outline { background: transparent; border: 2px solid var(--border); color: var(--text); }
    .btn-outline:hover { border-color: var(--primary); color: var(--primary); }
    .btn-success { background: var(--success); color: white; }
    
    /* 提交模态框 */
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 200; align-items: center; justify-content: center; }
    .modal.open { display: flex; }
    .modal-content { background: var(--card); border-radius: 16px; padding: 32px; text-align: center; max-width: 400px; }
    .modal-icon { font-size: 64px; margin-bottom: 16px; }
    .modal-title { font-size: 24px; font-weight: 700; margin-bottom: 8px; }
    .modal-score { font-size: 48px; font-weight: 800; color: var(--primary); margin: 16px 0; }
  </style>
</head>
<body>
  <div class="topbar">
    <div class="topbar-inner">
      <a href="practice.php" class="topbar-back"><i class="fas fa-arrow-left"></i> 返回</a>
      <div class="topbar-brand">答题中</div>
      <div class="topbar-timer" id="timer">01:30:00</div>
    </div>
  </div>
  
  <div class="container">
    <div class="exam-header">
      <div class="exam-title" id="examTitle">2025年某市中考数学真题</div>
      <div class="exam-info">
        <span><i class="fas fa-clock"></i> 120分钟</span>
        <span><i class="fas fa-question-circle"></i> 25题</span>
        <span><i class="fas fa-star"></i> 120分</span>
      </div>
      <div class="progress-bar">
        <div class="progress-fill" id="progressFill" style="width: 0%"></div>
      </div>
      <div class="progress-text">已完成 <span id="answered">0</span> / 25 题</div>
    </div>
    
    <!-- 题目1 -->
    <div class="question-card" id="q1">
      <div class="question-number">第 1 题 <span class="question-type choice">选择题</span></div>
      <div class="question-text">下列计算正确的是（　　）</div>
      <div class="options">
        <div class="option" onclick="selectOption(this, 1)">
          <div class="option-letter">A</div>
          <div>a² + a² = a⁴</div>
        </div>
        <div class="option" onclick="selectOption(this, 1)">
          <div class="option-letter">B</div>
          <div>a³ · a² = a⁶</div>
        </div>
        <div class="option" onclick="selectOption(this, 1)">
          <div class="option-letter">C</div>
          <div>(-a²)³ = -a⁶</div>
        </div>
        <div class="option" onclick="selectOption(this, 1)">
          <div class="option-letter">D</div>
          <div>a⁸ ÷ a² = a⁴</div>
        </div>
      </div>
      <div class="explanation" id="explain1">
        <div class="explanation-title"><i class="fas fa-lightbulb"></i> 解析</div>
        <div>正确答案：C<br>A. a² + a² = 2a²（合并同类项）<br>B. a³ · a² = a⁵（同底数幂相乘，指数相加）<br>C. (-a²)³ = -a⁶（幂的乘方，指数相乘）✓<br>D. a⁸ ÷ a² = a⁶（同底数幂相除，指数相减）</div>
      </div>
    </div>
    
    <!-- 题目2 -->
    <div class="question-card" id="q2">
      <div class="question-number">第 2 题 <span class="question-type choice">选择题</span></div>
      <div class="question-text">已知一组数据 3, 5, 7, 8, 8，下列说法正确的是（　　）</div>
      <div class="options">
        <div class="option" onclick="selectOption(this, 2)">
          <div class="option-letter">A</div>
          <div>中位数是 7</div>
        </div>
        <div class="option" onclick="selectOption(this, 2)">
          <div class="option-letter">B</div>
          <div>众数是 7</div>
        </div>
        <div class="option" onclick="selectOption(this, 2)">
          <div class="option-letter">C</div>
          <div>平均数是 6</div>
        </div>
        <div class="option" onclick="selectOption(this, 2)">
          <div class="option-letter">D</div>
          <div>方差是 4</div>
        </div>
      </div>
      <div class="explanation" id="explain2">
        <div class="explanation-title"><i class="fas fa-lightbulb"></i> 解析</div>
        <div>正确答案：A<br>排序后：3, 5, 7, 8, 8<br>中位数：7（中间位置的数）✓<br>众数：8（出现次数最多的数）<br>平均数：(3+5+7+8+8)/5 = 6.2</div>
      </div>
    </div>
    
    <!-- 题目3 -->
    <div class="question-card" id="q3">
      <div class="question-number">第 3 题 <span class="question-type fill">填空题</span></div>
      <div class="question-text">分解因式：x² - 9 = ______</div>
      <input type="text" class="fill-input" placeholder="请输入答案" oninput="updateFill(this, 3)">
      <div class="explanation" id="explain3">
        <div class="explanation-title"><i class="fas fa-lightbulb"></i> 解析</div>
        <div>正确答案：(x+3)(x-3)<br>这是平方差公式：a² - b² = (a+b)(a-b)</div>
      </div>
    </div>
    
    <div class="btn-group">
      <button class="btn btn-outline" onclick="showAllAnswers()"><i class="fas fa-eye"></i> 查看答案</button>
      <button class="btn btn-primary" onclick="submitExam()"><i class="fas fa-paper-plane"></i> 提交试卷</button>
    </div>
  </div>
  
  <!-- 提交结果模态框 -->
  <div class="modal" id="resultModal">
    <div class="modal-content">
      <div class="modal-icon">🎉</div>
      <div class="modal-title">试卷完成！</div>
      <div class="modal-score" id="finalScore">--</div>
      <div>正确率：<span id="accuracy">--</span></div>
      <div class="btn-group" style="margin-top: 20px;">
        <button class="btn btn-outline" onclick="location.href='practice.php'">返回列表</button>
        <button class="btn btn-success" onclick="location.href='mistakes.php'">查看错题</button>
      </div>
    </div>
  </div>
  
  <script>
    const answers = { 1: 'C', 2: 'A', 3: '(x+3)(x-3)' };
    const userAnswers = {};
    let answeredCount = 0;
    
    // 选择题
    function selectOption(el, qId) {
      const card = document.getElementById('q' + qId);
      card.querySelectorAll('.option').forEach(o => o.classList.remove('selected'));
      el.classList.add('selected');
      
      const letter = el.querySelector('.option-letter').textContent;
      userAnswers[qId] = letter;
      answeredCount = Object.keys(userAnswers).length;
      updateProgress();
    }
    
    // 填空题
    function updateFill(el, qId) {
      userAnswers[qId] = el.value.trim();
      answeredCount = Object.values(userAnswers).filter(v => v).length;
      updateProgress();
    }
    
    // 更新进度
    function updateProgress() {
      document.getElementById('answered').textContent = answeredCount;
      document.getElementById('progressFill').style.width = (answeredCount / 25 * 100) + '%';
    }
    
    // 查看答案
    function showAllAnswers() {
      document.querySelectorAll('.explanation').forEach(e => e.classList.add('show'));
      
      // 标记对错
      for (let qId in answers) {
        const card = document.getElementById('q' + qId);
        if (userAnswers[qId] === answers[qId]) {
          // 正确
        } else {
          // 错误，高亮正确答案
          card.querySelectorAll('.option').forEach(o => {
            if (o.querySelector('.option-letter').textContent === answers[qId]) {
              o.classList.add('correct');
            }
          });
        }
      }
    }
    
    // 提交试卷
    function submitExam() {
      let correct = 0;
      for (let qId in answers) {
        if (userAnswers[qId] === answers[qId]) correct++;
      }
      
      const total = Object.keys(answers).length;
      const score = Math.round(correct / total * 120);
      const accuracy = Math.round(correct / total * 100);
      
      document.getElementById('finalScore').textContent = score + ' 分';
      document.getElementById('accuracy').textContent = accuracy + '%';
      document.getElementById('resultModal').classList.add('open');
    }
    
    // 倒计时
    let timeLeft = 120 * 60;
    setInterval(() => {
      if (timeLeft > 0) {
        timeLeft--;
        const h = Math.floor(timeLeft / 3600);
        const m = Math.floor((timeLeft % 3600) / 60);
        const s = timeLeft % 60;
        document.getElementById('timer').textContent = 
          String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
      }
    }, 1000);
  </script>
</body>
</html>
