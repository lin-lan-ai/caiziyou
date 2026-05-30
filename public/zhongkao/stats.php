<?php session_start(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>学习统计 - 中考备战</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    :root { --primary: #4f46e5; --success: #22c55e; --bg: #f8fafc; --card: #fff; --text: #1e293b; --text-dim: #64748b; --border: #e2e8f0; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: -apple-system, sans-serif; background: var(--bg); color: var(--text); }
    .topbar { background: var(--card); border-bottom: 1px solid var(--border); padding: 12px 20px; position: sticky; top: 0; z-index: 100; }
    .topbar-inner { max-width: 1000px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; }
    .topbar-back { color: var(--text-dim); text-decoration: none; }
    .topbar-brand { font-weight: 600; color: var(--primary); }
    .container { max-width: 1000px; margin: 0 auto; padding: 20px; }
    .page-title { font-size: 24px; font-weight: 700; margin-bottom: 24px; }
    
    /* 统计卡片 */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .stat-card { background: var(--card); border-radius: 16px; padding: 24px; border: 1px solid var(--border); }
    .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 16px; }
    .stat-icon.time { background: #dbeafe; color: #2563eb; }
    .stat-icon.questions { background: #dcfce7; color: #16a34a; }
    .stat-icon.accuracy { background: #fef3c7; color: #d97706; }
    .stat-icon.streak { background: #f3e8ff; color: #9333ea; }
    .stat-value { font-size: 36px; font-weight: 700; color: var(--primary); }
    .stat-label { color: var(--text-dim); margin-top: 4px; }
    .stat-change { font-size: 14px; margin-top: 8px; }
    .stat-change.up { color: var(--success); }
    .stat-change.down { color: #ef4444; }
    
    /* 图表区域 */
    .chart-section { background: var(--card); border-radius: 16px; padding: 24px; border: 1px solid var(--border); margin-bottom: 24px; }
    .chart-title { font-size: 18px; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
    .chart-container { position: relative; height: 300px; }
    
    /* 科目进度 */
    .subject-progress { background: var(--card); border-radius: 16px; padding: 24px; border: 1px solid var(--border); }
    .subject-item { display: flex; align-items: center; gap: 16px; padding: 12px 0; border-bottom: 1px solid var(--border); }
    .subject-item:last-child { border-bottom: none; }
    .subject-name { width: 80px; font-weight: 500; }
    .subject-bar { flex: 1; height: 12px; background: var(--bg); border-radius: 6px; overflow: hidden; }
    .subject-fill { height: 100%; border-radius: 6px; transition: width .5s; }
    .subject-percent { width: 60px; text-align: right; font-weight: 600; }
  
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

/* 修复页面标题 */
.page-title {
  position: sticky;
  top: 56px;
  z-index: 999;
  background: var(--bg);
  padding: 16px 0;
  margin-bottom: 20px;
}
</style>
</head>
<body>
  <div class="topbar">
    <div class="topbar-inner">
      <a href="index.php" class="topbar-back"><i class="fas fa-arrow-left"></i> 返回</a>
      <div class="topbar-brand">学习统计</div>
      <div></div>
    </div>
  </div>
  
  <div class="container">
    <div class="page-title"><i class="fas fa-chart-bar"></i> 学习统计</div>
    
    <!-- 统计卡片 -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon time"><i class="fas fa-clock"></i></div>
        <div class="stat-value">128</div>
        <div class="stat-label">今日学习时长(分钟)</div>
        <div class="stat-change up"><i class="fas fa-arrow-up"></i> 比昨天多 23 分钟</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon questions"><i class="fas fa-pen"></i></div>
        <div class="stat-value">45</div>
        <div class="stat-label">今日做题数量</div>
        <div class="stat-change up"><i class="fas fa-arrow-up"></i> 比昨天多 12 题</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon accuracy"><i class="fas fa-bullseye"></i></div>
        <div class="stat-value">78%</div>
        <div class="stat-label">今日正确率</div>
        <div class="stat-change up"><i class="fas fa-arrow-up"></i> 提升 5%</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon streak"><i class="fas fa-fire"></i></div>
        <div class="stat-value">7</div>
        <div class="stat-label">连续学习天数</div>
        <div class="stat-change up"><i class="fas fa-arrow-up"></i> 继续保持！</div>
      </div>
    </div>
    
    <!-- 学习时长趋势 -->
    <div class="chart-section">
      <div class="chart-title"><i class="fas fa-chart-line"></i> 本周学习时长趋势</div>
      <div class="chart-container">
        <canvas id="timeChart"></canvas>
      </div>
    </div>
    
    <!-- 正确率趋势 -->
    <div class="chart-section">
      <div class="chart-title"><i class="fas fa-chart-pie"></i> 各科正确率</div>
      <div class="chart-container">
        <canvas id="accuracyChart"></canvas>
      </div>
    </div>
    
    <!-- 科目进度 -->
    <div class="subject-progress">
      <div class="chart-title"><i class="fas fa-tasks"></i> 科目复习进度</div>
      
      <div class="subject-item">
        <div class="subject-name">数学</div>
        <div class="subject-bar"><div class="subject-fill" style="width: 65%; background: #2563eb;"></div></div>
        <div class="subject-percent">65%</div>
      </div>
      <div class="subject-item">
        <div class="subject-name">语文</div>
        <div class="subject-bar"><div class="subject-fill" style="width: 50%; background: #16a34a;"></div></div>
        <div class="subject-percent">50%</div>
      </div>
      <div class="subject-item">
        <div class="subject-name">英语</div>
        <div class="subject-bar"><div class="subject-fill" style="width: 70%; background: #d97706;"></div></div>
        <div class="subject-percent">70%</div>
      </div>
      <div class="subject-item">
        <div class="subject-name">物理</div>
        <div class="subject-bar"><div class="subject-fill" style="width: 45%; background: #dc2626;"></div></div>
        <div class="subject-percent">45%</div>
      </div>
      <div class="subject-item">
        <div class="subject-name">化学</div>
        <div class="subject-bar"><div class="subject-fill" style="width: 40%; background: #9333ea;"></div></div>
        <div class="subject-percent">40%</div>
      </div>
      <div class="subject-item">
        <div class="subject-name">历史</div>
        <div class="subject-bar"><div class="subject-fill" style="width: 55%; background: #0891b2;"></div></div>
        <div class="subject-percent">55%</div>
      </div>
      <div class="subject-item">
        <div class="subject-name">政治</div>
        <div class="subject-bar"><div class="subject-fill" style="width: 60%; background: #db2777;"></div></div>
        <div class="subject-percent">60%</div>
      </div>
    </div>
  </div>
  
  <script>
    // 学习时长趋势图
    new Chart(document.getElementById('timeChart'), {
      type: 'line',
      data: {
        labels: ['周一', '周二', '周三', '周四', '周五', '周六', '周日'],
        datasets: [{
          label: '学习时长(分钟)',
          data: [90, 120, 105, 135, 128, 150, 128],
          borderColor: '#4f46e5',
          backgroundColor: 'rgba(79, 70, 229, 0.1)',
          fill: true,
          tension: 0.4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } }
      }
    });
    
    // 各科正确率饼图
    new Chart(document.getElementById('accuracyChart'), {
      type: 'doughnut',
      data: {
        labels: ['数学', '语文', '英语', '物理', '化学', '历史', '政治'],
        datasets: [{
          data: [82, 75, 88, 65, 70, 72, 78],
          backgroundColor: ['#2563eb', '#16a34a', '#d97706', '#dc2626', '#9333ea', '#0891b2', '#db2777']
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'right' } }
      }
    });
  </script>
</body>
</html>
