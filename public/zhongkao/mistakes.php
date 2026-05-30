<?php session_start(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>错题本 - 中考备战</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    :root { --primary: #4f46e5; --success: #22c55e; --danger: #ef4444; --warning: #f59e0b; --bg: #f8fafc; --card: #fff; --text: #1e293b; --text-dim: #64748b; --border: #e2e8f0; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: -apple-system, sans-serif; background: var(--bg); color: var(--text); }
    .topbar { background: var(--card); border-bottom: 1px solid var(--border); padding: 12px 20px; position: sticky; top: 0; z-index: 100; }
    .topbar-inner { max-width: 1000px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; }
    .topbar-back { color: var(--text-dim); text-decoration: none; }
    .topbar-brand { font-weight: 600; color: var(--primary); }
    .container { max-width: 1000px; margin: 0 auto; padding: 20px; }
    .page-title { font-size: 24px; font-weight: 700; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; }
    
    /* 统计卡片 */
    .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .stat-card { background: var(--card); border-radius: 12px; padding: 20px; text-align: center; border: 1px solid var(--border); }
    .stat-value { font-size: 32px; font-weight: 700; }
    .stat-value.total { color: var(--danger); }
    .stat-value.reviewed { color: var(--warning); }
    .stat-value.mastered { color: var(--success); }
    .stat-label { font-size: 14px; color: var(--text-dim); margin-top: 4px; }
    
    /* 筛选器 */
    .filters { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
    .filter-btn { padding: 8px 16px; border-radius: 20px; border: 1px solid var(--border); background: var(--card); cursor: pointer; transition: .2s; }
    .filter-btn:hover, .filter-btn.active { background: var(--primary); color: white; border-color: var(--primary); }
    
    /* 错题列表 */
    .mistake-list { display: flex; flex-direction: column; gap: 16px; }
    .mistake-card { background: var(--card); border-radius: 16px; border: 1px solid var(--border); overflow: hidden; }
    .mistake-header { padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; }
    .mistake-header:hover { background: var(--bg); }
    .mistake-info { display: flex; align-items: center; gap: 12px; }
    .mistake-subject { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; }
    .mistake-subject.math { background: #dbeafe; color: #2563eb; }
    .mistake-subject.physics { background: #fee2e2; color: #dc2626; }
    .mistake-subject.chemistry { background: #f3e8ff; color: #9333ea; }
    .mistake-date { color: var(--text-dim); font-size: 14px; }
    .mistake-status { display: flex; align-items: center; gap: 8px; }
    .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; }
    .status-badge.new { background: #fee2e2; color: #dc2626; }
    .status-badge.reviewing { background: #fef3c7; color: #d97706; }
    .status-badge.mastered { background: #dcfce7; color: #16a34a; }
    .mistake-expand { color: var(--text-dim); transition: .2s; }
    .mistake-expand.open { transform: rotate(180deg); }
    
    .mistake-content { padding: 0 20px 20px; display: none; }
    .mistake-content.open { display: block; }
    
    .mistake-question { background: var(--bg); border-radius: 12px; padding: 16px; margin-bottom: 16px; line-height: 1.8; }
    .mistake-question strong { color: var(--danger); }
    
    .mistake-answer { margin-bottom: 16px; }
    .answer-label { font-weight: 600; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
    .answer-label.correct { color: var(--success); }
    .answer-label.wrong { color: var(--danger); }
    
    .mistake-analysis { background: #f0fdf4; border-radius: 12px; padding: 16px; margin-bottom: 16px; border-left: 4px solid var(--success); }
    .analysis-title { font-weight: 600; margin-bottom: 8px; color: var(--success); }
    
    .mistake-actions { display: flex; gap: 10px; justify-content: flex-end; }
    .btn { padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; font-size: 14px; transition: .2s; }
    .btn-primary { background: var(--primary); color: white; }
    .btn-success { background: var(--success); color: white; }
    .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); }
    .btn-outline:hover { border-color: var(--primary); color: var(--primary); }
  </style>
</head>
<body>
  <div class="topbar">
    <div class="topbar-inner">
      <a href="index.php" class="topbar-back"><i class="fas fa-arrow-left"></i> 返回</a>
      <div class="topbar-brand">错题本</div>
      <div></div>
    </div>
  </div>
  
  <div class="container">
    <div class="page-title">
      <span><i class="fas fa-exclamation-triangle"></i> 错题本</span>
      <button class="btn btn-primary"><i class="fas fa-plus"></i> 手动添加</button>
    </div>
    
    <!-- 统计卡片 -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-value total">23</div>
        <div class="stat-label">总错题数</div>
      </div>
      <div class="stat-card">
        <div class="stat-value reviewed">15</div>
        <div class="stat-label">待复习</div>
      </div>
      <div class="stat-card">
        <div class="stat-value mastered">8</div>
        <div class="stat-label">已掌握</div>
      </div>
    </div>
    
    <!-- 筛选器 -->
    <div class="filters">
      <div class="filter-btn active">全部</div>
      <div class="filter-btn">待复习</div>
      <div class="filter-btn">已掌握</div>
      <div class="filter-btn">数学</div>
      <div class="filter-btn">物理</div>
      <div class="filter-btn">化学</div>
    </div>
    
    <!-- 错题列表 -->
    <div class="mistake-list">
      <!-- 错题1 -->
      <div class="mistake-card">
        <div class="mistake-header" onclick="toggleMistake(this)">
          <div class="mistake-info">
            <span class="mistake-subject math">数学</span>
            <span>二次函数图像与性质</span>
          </div>
          <div class="mistake-status">
            <span class="status-badge new">待复习</span>
            <span class="mistake-date">2026-05-28</span>
            <i class="fas fa-chevron-down mistake-expand"></i>
          </div>
        </div>
        <div class="mistake-content">
          <div class="mistake-question">
            <strong>题目：</strong>已知二次函数 y = x² - 2x - 3，求该函数的顶点坐标和与x轴的交点。
          </div>
          
          <div class="mistake-answer">
            <div class="answer-label wrong"><i class="fas fa-times"></i> 我的答案</div>
            <div>顶点坐标 (1, -3)，与x轴交点 (-1, 0) 和 (3, 0)</div>
          </div>
          
          <div class="mistake-answer">
            <div class="answer-label correct"><i class="fas fa-check"></i> 正确答案</div>
            <div>顶点坐标 (1, -4)，与x轴交点 (-1, 0) 和 (3, 0)</div>
          </div>
          
          <div class="mistake-analysis">
            <div class="analysis-title"><i class="fas fa-lightbulb"></i> 解题分析</div>
            <div>
              <p>1. 顶点坐标的计算：</p>
              <p>• x = -b/2a = 2/2 = 1</p>
              <p>• y = 1² - 2×1 - 3 = 1 - 2 - 3 = <strong>-4</strong></p>
              <p>• 所以顶点是 (1, -4)</p>
              <br>
              <p>2. 与x轴交点：</p>
              <p>• 令 y = 0，得 x² - 2x - 3 = 0</p>
              <p>• (x-3)(x+1) = 0</p>
              <p>• x = 3 或 x = -1</p>
            </div>
          </div>
          
          <div class="mistake-actions">
            <button class="btn btn-outline"><i class="fas fa-redo"></i> 再做一次</button>
            <button class="btn btn-success"><i class="fas fa-check"></i> 标记已掌握</button>
          </div>
        </div>
      </div>
      
      <!-- 错题2 -->
      <div class="mistake-card">
        <div class="mistake-header" onclick="toggleMistake(this)">
          <div class="mistake-info">
            <span class="mistake-subject physics">物理</span>
            <span>欧姆定律计算</span>
          </div>
          <div class="mistake-status">
            <span class="status-badge reviewing">复习中</span>
            <span class="mistake-date">2026-05-25</span>
            <i class="fas fa-chevron-down mistake-expand"></i>
          </div>
        </div>
        <div class="mistake-content">
          <div class="mistake-question">
            <strong>题目：</strong>一个电阻 R₁ = 10Ω 与 R₂ = 20Ω 串联接在 6V 的电源上，求通过 R₁ 的电流和 R₁ 两端的电压。
          </div>
          
          <div class="mistake-answer">
            <div class="answer-label wrong"><i class="fas fa-times"></i> 我的答案</div>
            <div>I = 0.6A，U₁ = 6V</div>
          </div>
          
          <div class="mistake-answer">
            <div class="answer-label correct"><i class="fas fa-check"></i> 正确答案</div>
            <div>I = 0.2A，U₁ = 2V</div>
          </div>
          
          <div class="mistake-analysis">
            <div class="analysis-title"><i class="fas fa-lightbulb"></i> 解题分析</div>
            <div>
              <p>串联电路特点：电流处处相等</p>
              <p>1. 总电阻 R = R₁ + R₂ = 10 + 20 = 30Ω</p>
              <p>2. 总电流 I = U/R = 6/30 = <strong>0.2A</strong></p>
              <p>3. R₁ 两端电压 U₁ = IR₁ = 0.2 × 10 = <strong>2V</strong></p>
            </div>
          </div>
          
          <div class="mistake-actions">
            <button class="btn btn-outline"><i class="fas fa-redo"></i> 再做一次</button>
            <button class="btn btn-success"><i class="fas fa-check"></i> 标记已掌握</button>
          </div>
        </div>
      </div>
    </div>
  </div>
  
</body>
</html>
<script src="api_client.js"></script>
<script>
let currentStatus = '';

async function loadMistakes() {
  try {
    const data = await ZhongkaoAPI.getMistakes(currentStatus);
    const container = document.querySelector('.mistake-list');
    
    if (!data.mistakes || data.mistakes.length === 0) {
      container.innerHTML = '<div style="text-align:center;padding:40px;color:#999">暂无错题</div>';
      return;
    }
    
    const subjectNames = {math:'数学',physics:'物理',english:'英语',chinese:'语文'};
    const subjectClasses = {math:'math',physics:'physics'};
    const statusNames = {new:'待复习',reviewing:'复习中',mastered:'已掌握'};
    const statusClasses = {new:'new',reviewing:'reviewing',mastered:'mastered'};
    
    let html = '';
    data.mistakes.forEach(m => {
      const options = m.options ? JSON.parse(m.options) : [];
      const date = new Date(m.created_at).toLocaleDateString('zh-CN');
      
      html += `
        <div class="mistake-card">
          <div class="mistake-header" onclick="toggleMistake(this)">
            <div class="mistake-info">
              <span class="mistake-subject ${subjectClasses[m.subject] || ''}">${subjectNames[m.subject] || m.subject}</span>
              <span>${m.question_text.substring(0, 30)}...</span>
            </div>
            <div class="mistake-status">
              <span class="status-badge ${statusClasses[m.status]}">${statusNames[m.status]}</span>
              <span class="mistake-date">${date}</span>
              <i class="fas fa-chevron-down mistake-expand"></i>
            </div>
          </div>
          <div class="mistake-content">
            <div class="mistake-question"><strong>题目：</strong>${m.question_text}</div>
            <div class="mistake-answer">
              <div class="answer-label wrong"><i class="fas fa-times"></i> 我的答案</div>
              <div>${m.user_answer}</div>
            </div>
            <div class="mistake-answer">
              <div class="answer-label correct"><i class="fas fa-check"></i> 正确答案</div>
              <div>${m.correct_answer}</div>
            </div>
            ${m.explanation ? `
            <div class="mistake-analysis">
              <div class="analysis-title"><i class="fas fa-lightbulb"></i> 解析</div>
              <div>${m.explanation}</div>
            </div>` : ''}
            <div class="mistake-actions">
              <button class="btn btn-outline" onclick="updateStatus(${m.id}, 'reviewing')"><i class="fas fa-redo"></i> 标记复习中</button>
              <button class="btn btn-success" onclick="updateStatus(${m.id}, 'mastered')"><i class="fas fa-check"></i> 标记已掌握</button>
            </div>
          </div>
        </div>`;
    });
    container.innerHTML = html;
    
    // 更新统计
    const total = data.mistakes.length;
    const newCount = data.mistakes.filter(m => m.status === 'new').length;
    const mastered = data.mistakes.filter(m => m.status === 'mastered').length;
    document.querySelector('.stat-value.total').textContent = total;
    document.querySelector('.stat-value.reviewed').textContent = newCount;
    document.querySelector('.stat-value.mastered').textContent = mastered;
  } catch(e) {
    console.error('加载错题失败:', e);
  }
}

function toggleMistake(el) {
  const content = el.nextElementSibling;
  const expand = el.querySelector('.mistake-expand');
  content.classList.toggle('open');
  expand.classList.toggle('open');
}

async function updateStatus(id, status) {
  await ZhongkaoAPI.updateMistakeStatus(id, status);
  loadMistakes();
}

// 筛选
document.querySelectorAll('.filter-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    currentStatus = this.dataset.status || '';
    loadMistakes();
  });
});

document.addEventListener('DOMContentLoaded', loadMistakes);
</script>
