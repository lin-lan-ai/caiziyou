<?php session_start(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>真题练习 - 中考备战</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    :root { --primary: #4f46e5; --success: #22c55e; --danger: #ef4444; --bg: #f8fafc; --card: #fff; --text: #1e293b; --text-dim: #64748b; --border: #e2e8f0; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: -apple-system, sans-serif; background: var(--bg); color: var(--text); }
    
    /* 固定顶部 */
    .topbar { 
      position: sticky; 
      top: 0; 
      z-index: 1000; 
      background: var(--card); 
      border-bottom: 1px solid var(--border); 
      padding: 12px 20px; 
      box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
    }
    .topbar-inner { max-width: 800px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; }
    .topbar-back { color: var(--text-dim); text-decoration: none; }
    .topbar-brand { font-weight: 600; color: var(--primary); }
    
    .container { max-width: 800px; margin: 0 auto; padding: 20px; }
    
    /* 固定标题 */
    .page-title { 
      position: sticky; 
      top: 56px; 
      z-index: 999; 
      background: var(--bg); 
      padding: 16px 0; 
      font-size: 24px; 
      font-weight: 700; 
    }
    
    /* 筛选器 */
    .filters { 
      position: sticky; 
      top: 110px; 
      z-index: 998; 
      background: var(--bg); 
      padding: 12px 0; 
      display: flex; 
      gap: 10px; 
      flex-wrap: wrap; 
    }
    .filter-btn { 
      padding: 8px 16px; 
      border-radius: 20px; 
      border: 1px solid var(--border); 
      background: var(--card); 
      cursor: pointer; 
      transition: .2s; 
    }
    .filter-btn:hover, .filter-btn.active { 
      background: var(--primary); 
      color: white; 
      border-color: var(--primary); 
    }
    
    /* 试卷列表 */
    .exam-list { display: flex; flex-direction: column; gap: 16px; }
    .exam-card { 
      background: var(--card); 
      border-radius: 16px; 
      border: 1px solid var(--border); 
      padding: 20px; 
      transition: .2s; 
      cursor: pointer; 
    }
    .exam-card:hover { 
      box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
      transform: translateY(-2px); 
    }
    .exam-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
    .exam-title { font-size: 18px; font-weight: 600; }
    .exam-badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 500; }
    .exam-badge.real { background: #dbeafe; color: #2563eb; }
    .exam-badge.simulate { background: #dcfce7; color: #16a34a; }
    .exam-badge.special { background: #fef3c7; color: #d97706; }
    .exam-info { display: flex; gap: 20px; color: var(--text-dim); font-size: 14px; margin-bottom: 12px; }
    .exam-info span { display: flex; align-items: center; gap: 4px; }
    .exam-progress { height: 6px; background: var(--bg); border-radius: 3px; overflow: hidden; }
    .exam-progress-bar { height: 100%; background: var(--primary); border-radius: 3px; transition: width .3s; }
    .exam-actions { display: flex; justify-content: space-between; align-items: center; margin-top: 12px; }
    .exam-score { font-size: 24px; font-weight: 700; color: var(--primary); }
    .exam-score small { font-size: 14px; color: var(--text-dim); font-weight: normal; }
    .btn { padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; font-size: 14px; transition: .2s; }
    .btn-primary { background: var(--primary); color: white; }
    .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); }
    
    .loading { text-align: center; padding: 40px; color: var(--text-dim); }
  </style>
</head>
<body>
  <div class="topbar">
    <div class="topbar-inner">
      <a href="index.php" class="topbar-back"><i class="fas fa-arrow-left"></i> 返回</a>
      <div class="topbar-brand">真题练习</div>
      <div></div>
    </div>
  </div>
  
  <div class="container">
    <div class="page-title"><i class="fas fa-pen-fancy"></i> 真题练习</div>
    
    <div class="filters">
      <div class="filter-btn active" data-subject="">全部</div>
      <div class="filter-btn" data-subject="math">数学</div>
      <div class="filter-btn" data-subject="english">英语</div>
      <div class="filter-btn" data-subject="physics">物理</div>
    </div>
    
    <div class="exam-list" id="examList">
      <div class="loading"><i class="fas fa-spinner fa-spin"></i> 加载试卷中...</div>
    </div>
  </div>
  
  <script src="api_client.js"></script>
  <script>
    let currentSubject = '';
    
    async function loadExams() {
      try {
        const data = await ZhongkaoAPI.getExams(currentSubject);
        const container = document.getElementById('examList');
        
        if (!data.exams || data.exams.length === 0) {
          container.innerHTML = '<div class="loading">暂无试卷</div>';
          return;
        }
        
        const typeNames = {real:'真题', simulate:'模拟', special:'专项'};
        const typeClasses = {real:'real', simulate:'simulate', special:'special'};
        const subjectNames = {math:'数学', english:'英语', physics:'物理', chinese:'语文'};
        
        let html = '';
        data.exams.forEach(exam => {
          const progress = exam.progress || 0;
          const badge = typeNames[exam.type] || exam.type;
          const badgeClass = typeClasses[exam.type] || '';
          
          html += `
            <div class="exam-card" onclick="startExam(${exam.id})">
              <div class="exam-header">
                <div class="exam-title">${exam.title}</div>
                <span class="exam-badge ${badgeClass}">${exam.year}${badge}</span>
              </div>
              <div class="exam-info">
                <span><i class="fas fa-clock"></i> ${exam.duration}分钟</span>
                <span><i class="fas fa-question-circle"></i> ${exam.question_count}题</span>
                <span><i class="fas fa-star"></i> ${exam.total_score}分</span>
              </div>
              <div class="exam-progress">
                <div class="exam-progress-bar" style="width: ${progress}%"></div>
              </div>
              <div class="exam-actions">
                <div class="exam-score">${exam.best_score || '--'} <small>/ ${exam.total_score}分</small></div>
                <button class="btn btn-primary">${progress > 0 ? '继续做题' : '开始练习'}</button>
              </div>
            </div>`;
        });
        container.innerHTML = html;
      } catch(e) {
        console.error('加载试卷失败:', e);
        document.getElementById('examList').innerHTML = '<div class="loading">加载失败，请重试</div>';
      }
    }
    
    function startExam(id) {
      window.location.href = 'exam.php?id=' + id;
    }
    
    // 筛选功能
    document.querySelectorAll('.filter-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        currentSubject = this.dataset.subject;
        loadExams();
      });
    });
    
    document.addEventListener('DOMContentLoaded', loadExams);
  </script>
</body>
</html>
