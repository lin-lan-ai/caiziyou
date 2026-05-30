<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>中考备战 - 菜籽游</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    :root {
      --primary: #4f46e5;
      --primary-light: #818cf8;
      --success: #22c55e;
      --warning: #f59e0b;
      --danger: #ef4444;
      --bg: #f8fafc;
      --card: #ffffff;
      --text: #1e293b;
      --text-dim: #64748b;
      --border: #e2e8f0;
    }
    
    * { margin: 0; padding: 0; box-sizing: border-box; }
    
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
    }
    
    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
    }
    
    /* 顶部导航 */
    .topbar {
      background: var(--card);
      border-bottom: 1px solid var(--border);
      padding: 12px 20px;
      position: sticky;
      top: 0;
      z-index: 100;
    }
    
    .topbar-inner {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    
    .topbar-brand {
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 600;
      color: var(--primary);
    }
    
    .topbar-brand i { font-size: 24px; }
    
    .topbar-back {
      color: var(--text-dim);
      text-decoration: none;
      font-size: 14px;
      transition: .2s;
    }
    
    .topbar-back:hover { color: var(--primary); }
    
    /* 倒计时横幅 */
    .countdown-banner {
      background: linear-gradient(135deg, var(--primary), var(--primary-light));
      color: white;
      border-radius: 16px;
      padding: 30px;
      margin-bottom: 24px;
      text-align: center;
    }
    
    .countdown-title {
      font-size: 18px;
      opacity: 0.9;
      margin-bottom: 10px;
    }
    
    .countdown-days {
      font-size: 72px;
      font-weight: 800;
      line-height: 1;
    }
    
    .countdown-unit {
      font-size: 24px;
      opacity: 0.8;
    }
    
    .countdown-date {
      margin-top: 10px;
      opacity: 0.7;
      font-size: 14px;
    }
    
    /* 功能网格 */
    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
      margin-bottom: 24px;
    }
    
    .feature-card {
      background: var(--card);
      border-radius: 16px;
      padding: 24px;
      border: 1px solid var(--border);
      transition: .3s;
      cursor: pointer;
      text-decoration: none;
      color: var(--text);
    }
    
    .feature-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 24px rgba(0,0,0,0.1);
    }
    
    .feature-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      margin-bottom: 16px;
    }
    
    .feature-icon.plan { background: #dbeafe; color: #2563eb; }
    .feature-icon.knowledge { background: #dcfce7; color: #16a34a; }
    .feature-icon.practice { background: #fef3c7; color: #d97706; }
    .feature-icon.mistakes { background: #fee2e2; color: #dc2626; }
    .feature-icon.stats { background: #f3e8ff; color: #9333ea; }
    .feature-icon.tools { background: #e0f2fe; color: #0891b2; }
    
    .feature-title {
      font-size: 18px;
      font-weight: 600;
      margin-bottom: 8px;
    }
    
    .feature-desc {
      color: var(--text-dim);
      font-size: 14px;
      line-height: 1.5;
    }
    
    /* 学习统计卡片 */
    .stats-section {
      background: var(--card);
      border-radius: 16px;
      padding: 24px;
      border: 1px solid var(--border);
      margin-bottom: 24px;
    }
    
    .stats-title {
      font-size: 18px;
      font-weight: 600;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 16px;
    }
    
    .stat-item {
      text-align: center;
      padding: 16px;
      background: var(--bg);
      border-radius: 12px;
    }
    
    .stat-value {
      font-size: 32px;
      font-weight: 700;
      color: var(--primary);
    }
    
    .stat-label {
      font-size: 14px;
      color: var(--text-dim);
      margin-top: 4px;
    }
    
    /* 科目进度 */
    .subjects-section {
      background: var(--card);
      border-radius: 16px;
      padding: 24px;
      border: 1px solid var(--border);
    }
    
    .subject-item {
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 12px 0;
      border-bottom: 1px solid var(--border);
    }
    
    .subject-item:last-child { border-bottom: none; }
    
    .subject-icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
    }
    
    .subject-info { flex: 1; }
    
    .subject-name {
      font-weight: 500;
      margin-bottom: 4px;
    }
    
    .subject-progress {
      height: 8px;
      background: var(--bg);
      border-radius: 4px;
      overflow: hidden;
    }
    
    .subject-progress-bar {
      height: 100%;
      border-radius: 4px;
      transition: width .3s;
    }
    
    .subject-percent {
      font-weight: 600;
      color: var(--primary);
      min-width: 50px;
      text-align: right;
    }
    
    /* 响应式 */
    @media (max-width: 768px) {
      .countdown-days { font-size: 48px; }
      .features-grid { grid-template-columns: 1fr; }
      .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }
  
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
  <!-- 顶部导航 -->
  <div class="topbar">
    <div class="topbar-inner">
      <div class="topbar-brand">
        <i class="fas fa-graduation-cap"></i>
        <span>中考备战</span>
      </div>
      <a href="/" class="topbar-back">
        <i class="fas fa-arrow-left"></i> 返回主站
      </a>
    </div>
  </div>
  
  <div class="container">
    <!-- 倒计时横幅 -->
    <div class="countdown-banner">
      <div class="countdown-title">距离 2026 年中考还有</div>
      <div class="countdown-days" id="countdown">--</div>
      <div class="countdown-unit">天</div>
      <div class="countdown-date">2026年6月XX日</div>
    </div>
    
    <!-- 功能网格 -->
    <div class="features-grid">
      <a href="plan.php" class="feature-card">
        <div class="feature-icon plan">
          <i class="fas fa-calendar-alt"></i>
        </div>
        <div class="feature-title">学习计划</div>
        <div class="feature-desc">制定每日/每周复习计划，按科目分配时间，跟踪完成情况</div>
      </a>
      
      <a href="knowledge.php" class="feature-card">
        <div class="feature-icon knowledge">
          <i class="fas fa-book"></i>
        </div>
        <div class="feature-title">知识点梳理</div>
        <div class="feature-desc">各科核心知识点整理，思维导图，重点难点标注</div>
      </a>
      
      <a href="practice.php" class="feature-card">
        <div class="feature-icon practice">
          <i class="fas fa-pen-fancy"></i>
        </div>
        <div class="feature-title">真题练习</div>
        <div class="feature-desc">历年中考真题、模拟题，自动批改，错题分析</div>
      </a>
      
      <a href="mistakes.php" class="feature-card">
        <div class="feature-icon mistakes">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="feature-title">错题本</div>
        <div class="feature-desc">自动收集错题，分类整理，定期复习提醒</div>
      </a>
      
      <a href="stats.php" class="feature-card">
        <div class="feature-icon stats">
          <i class="fas fa-chart-bar"></i>
        </div>
        <div class="feature-title">学习统计</div>
        <div class="feature-desc">学习时长、正确率、进步趋势，数据可视化</div>
      </a>
      
      <a href="tools.php" class="feature-card">
        <div class="feature-icon tools">
          <i class="fas fa-tools"></i>
        </div>
        <div class="feature-title">实用工具</div>
        <div class="feature-desc">番茄钟、公式表、单词本、作文素材库</div>
      </a>
    </div>
    
    <!-- 学习统计 -->
    <div class="stats-section">
      <div class="stats-title">
        <i class="fas fa-trophy"></i>
        今日学习统计
      </div>
      <div class="stats-grid">
        <div class="stat-item">
          <div class="stat-value" id="studyTime">--</div>
          <div class="stat-label">学习时长(分钟)</div>
        </div>
        <div class="stat-item">
          <div class="stat-value" id="questions">--</div>
          <div class="stat-label">做题数量</div>
        </div>
        <div class="stat-item">
          <div class="stat-value" id="accuracy">--%</div>
          <div class="stat-label">正确率</div>
        </div>
        <div class="stat-item">
          <div class="stat-value" id="streak">--</div>
          <div class="stat-label">连续学习(天)</div>
        </div>
      </div>
    </div>
    
    <!-- 科目进度 -->
    <div class="subjects-section">
      <div class="stats-title">
        <i class="fas fa-tasks"></i>
        科目复习进度
      </div>
      
      <div class="subject-item">
        <div class="subject-icon" style="background: #dbeafe; color: #2563eb;">📐</div>
        <div class="subject-info">
          <div class="subject-name">数学</div>
          <div class="subject-progress">
            <div class="subject-progress-bar" style="width: 65%; background: #2563eb;"></div>
          </div>
        </div>
        <div class="subject-percent">65%</div>
      </div>
      
      <div class="subject-item">
        <div class="subject-icon" style="background: #dcfce7; color: #16a34a;">📝</div>
        <div class="subject-info">
          <div class="subject-name">语文</div>
          <div class="subject-progress">
            <div class="subject-progress-bar" style="width: 50%; background: #16a34a;"></div>
          </div>
        </div>
        <div class="subject-percent">50%</div>
      </div>
      
      <div class="subject-item">
        <div class="subject-icon" style="background: #fef3c7; color: #d97706;">📚</div>
        <div class="subject-info">
          <div class="subject-name">英语</div>
          <div class="subject-progress">
            <div class="subject-progress-bar" style="width: 70%; background: #d97706;"></div>
          </div>
        </div>
        <div class="subject-percent">70%</div>
      </div>
      
      <div class="subject-item">
        <div class="subject-icon" style="background: #fee2e2; color: #dc2626;">🔬</div>
        <div class="subject-info">
          <div class="subject-name">物理</div>
          <div class="subject-progress">
            <div class="subject-progress-bar" style="width: 45%; background: #dc2626;"></div>
          </div>
        </div>
        <div class="subject-percent">45%</div>
      </div>
      
      <div class="subject-item">
        <div class="subject-icon" style="background: #f3e8ff; color: #9333ea;">🧪</div>
        <div class="subject-info">
          <div class="subject-name">化学</div>
          <div class="subject-progress">
            <div class="subject-progress-bar" style="width: 40%; background: #9333ea;"></div>
          </div>
        </div>
        <div class="subject-percent">40%</div>
      </div>
      
      <div class="subject-item">
        <div class="subject-icon" style="background: #e0f2fe; color: #0891b2;">🏛️</div>
        <div class="subject-info">
          <div class="subject-name">历史</div>
          <div class="subject-progress">
            <div class="subject-progress-bar" style="width: 55%; background: #0891b2;"></div>
          </div>
        </div>
        <div class="subject-percent">55%</div>
      </div>
      
      <div class="subject-item">
        <div class="subject-icon" style="background: #fce7f3; color: #db2777;">🌍</div>
        <div class="subject-info">
          <div class="subject-name">政治</div>
          <div class="subject-progress">
            <div class="subject-progress-bar" style="width: 60%; background: #db2777;"></div>
          </div>
        </div>
        <div class="subject-percent">60%</div>
      </div>
    </div>
  </div>
  
  <script>
    // 计算距离中考的天数
    function updateCountdown() {
      const examDate = new Date('2026-06-15'); // 假设中考日期
      const today = new Date();
      const diff = examDate - today;
      const days = Math.ceil(diff / (1000 * 60 * 60 * 24));
      document.getElementById('countdown').textContent = days > 0 ? days : 0;
    }
    
    // 模拟学习数据（实际从后端获取）
    function updateStats() {
      document.getElementById('studyTime').textContent = '128';
      document.getElementById('questions').textContent = '45';
      document.getElementById('accuracy').textContent = '78%';
      document.getElementById('streak').textContent = '7';
    }
    
    updateCountdown();
    updateStats();
    
    // 每天更新倒计时
    setInterval(updateCountdown, 1000 * 60 * 60 * 24);
  </script>
<script src="/zhongkao/api_client.js"></script>
<script>
// 加载统计数据
async function loadStats() {
  try {
    const data = await ZhongkaoAPI.getStats();
    if (data.today) {
      document.getElementById("studyTime").textContent = data.today.study_minutes || 0;
      document.getElementById("questions").textContent = data.today.questions_done || 0;
      const accuracy = data.today.questions_done > 0 ? Math.round(data.today.questions_correct / data.today.questions_done * 100) : 0;
      document.getElementById("accuracy").textContent = accuracy + "%";
    }
    document.getElementById("streak").textContent = data.streak || 0;
  } catch(e) { console.error("加载统计失败:", e); }
}

// 加载科目进度
async function loadProgress() {
  try {
    const data = await ZhongkaoAPI.getProgress();
    const container = document.querySelector(".subjects-section");
    if (!container || !data.progress) return;
    
    const subjectNames = {math:"数学",chinese:"语文",english:"英语",physics:"物理",chemistry:"化学",history:"历史",politics:"政治"};
    const subjectIcons = {math:"📐",chinese:"📝",english:"📚",physics:"🔬",chemistry:"🧪",history:"🏛️",politics:"🌍"};
    const subjectColors = {math:"#2563eb",chinese:"#16a34a",english:"#d97706",physics:"#dc2626",chemistry:"#9333ea",history:"#0891b2",politics:"#db2777"};
    
    let html = "<div class=\"stats-title\"><i class=\"fas fa-tasks\"></i> 科目复习进度</div>";
    data.progress.forEach(p => {
      const name = subjectNames[p.subject] || p.subject;
      const icon = subjectIcons[p.subject] || "📖";
      const color = subjectColors[p.subject] || "#666";
      html += `
        <div class="subject-item">
          <div class="subject-icon" style="background: ${color}20; color: ${color}">${icon}</div>
          <div class="subject-info">
            <div class="subject-name">${name}</div>
            <div class="subject-progress">
              <div class="subject-progress-bar" style="width: ${p.percent}%; background: ${color};"></div>
            </div>
          </div>
          <div class="subject-percent">${p.percent}%</div>
        </div>`;
    });
    container.innerHTML = html;
  } catch(e) { console.error("加载进度失败:", e); }
}

// 页面加载时执行
document.addEventListener("DOMContentLoaded", () => {
  loadStats();
  loadProgress();
});
</script>
</body>
</html>

<script src="api_client.js"></script>
<script>
async function loadData() {
  try {
    // 加载统计
    const stats = await ZhongkaoAPI.getStats();
    if (stats.today) {
      document.getElementById('studyTime').textContent = stats.today.study_minutes || 0;
      document.getElementById('questions').textContent = stats.today.questions_done || 0;
      const acc = stats.today.questions_done > 0 
        ? Math.round(stats.today.questions_correct / stats.today.questions_done * 100) 
        : 0;
      document.getElementById('accuracy').textContent = acc + '%';
    }
    document.getElementById('streak').textContent = stats.streak || 0;
    
    // 加载进度
    const prog = await ZhongkaoAPI.getProgress();
    if (prog.progress && prog.progress.length > 0) {
      const names = {math:'数学',chinese:'语文',english:'英语',physics:'物理',chemistry:'化学',history:'历史',politics:'政治'};
      const icons = {math:'📐',chinese:'📝',english:'📚',physics:'🔬',chemistry:'🧪',history:'🏛️',politics:'🌍'};
      const colors = {math:'#2563eb',chinese:'#16a34a',english:'#d97706',physics:'#dc2626',chemistry:'#9333ea',history:'#0891b2',politics:'#db2777'};
      
      let html = '<div class="stats-title"><i class="fas fa-tasks"></i> 科目复习进度</div>';
      prog.progress.forEach(p => {
        const n = names[p.subject] || p.subject;
        const i = icons[p.subject] || '📖';
        const c = colors[p.subject] || '#666';
        html += `
          <div class="subject-item">
            <div class="subject-icon" style="background:${c}20;color:${c}">${i}</div>
            <div class="subject-info">
              <div class="subject-name">${n}</div>
              <div class="subject-progress">
                <div class="subject-progress-bar" style="width:${p.percent}%;background:${c}"></div>
              </div>
            </div>
            <div class="subject-percent">${p.percent}%</div>
          </div>`;
      });
      document.querySelector('.subjects-section').innerHTML = html;
    }
  } catch(e) {
    console.error('加载数据失败:', e);
  }
}

document.addEventListener('DOMContentLoaded', loadData);
</script>
