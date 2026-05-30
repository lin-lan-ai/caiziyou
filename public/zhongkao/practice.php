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
    .topbar { background: var(--card); border-bottom: 1px solid var(--border); padding: 12px 20px; position: sticky; top: 0; z-index: 100; }
    .topbar-inner { max-width: 800px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; }
    .topbar-back { color: var(--text-dim); text-decoration: none; }
    .topbar-brand { font-weight: 600; color: var(--primary); }
    .container { max-width: 800px; margin: 0 auto; padding: 20px; }
    .page-title { font-size: 24px; font-weight: 700; margin-bottom: 24px; }
    
    /* 试卷列表 */
    .exam-list { display: flex; flex-direction: column; gap: 16px; }
    .exam-card { background: var(--card); border-radius: 16px; border: 1px solid var(--border); padding: 20px; transition: .2s; cursor: pointer; }
    .exam-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); transform: translateY(-2px); }
    .exam-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
    .exam-title { font-size: 18px; font-weight: 600; }
    .exam-badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 500; }
    .exam-badge.year { background: #dbeafe; color: #2563eb; }
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
    .btn-primary:hover { background: #4338ca; }
    .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); }
    .btn-outline:hover { border-color: var(--primary); color: var(--primary); }
    
    /* 筛选器 */
    .filters { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
    .filter-btn { padding: 8px 16px; border-radius: 20px; border: 1px solid var(--border); background: var(--card); cursor: pointer; transition: .2s; }
    .filter-btn:hover, .filter-btn.active { background: var(--primary); color: white; border-color: var(--primary); }
  
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
      <div class="topbar-brand">真题练习</div>
      <div></div>
    </div>
  </div>
  
  <div class="container">
    <div class="page-title"><i class="fas fa-pen-fancy"></i> 真题练习</div>
    
    <!-- 筛选器 -->
    <div class="filters">
      <div class="filter-btn active">全部</div>
      <div class="filter-btn">数学</div>
      <div class="filter-btn">语文</div>
      <div class="filter-btn">英语</div>
      <div class="filter-btn">物理</div>
      <div class="filter-btn">化学</div>
    </div>
    
    <!-- 试卷列表 -->
    <div class="exam-list">
      <div class="exam-card" onclick="startExam('2025-math')">
        <div class="exam-header">
          <div class="exam-title">2025年某市中考数学真题</div>
          <span class="exam-badge year">2025真题</span>
        </div>
        <div class="exam-info">
          <span><i class="fas fa-clock"></i> 120分钟</span>
          <span><i class="fas fa-question-circle"></i> 25题</span>
          <span><i class="fas fa-star"></i> 120分</span>
        </div>
        <div class="exam-progress">
          <div class="exam-progress-bar" style="width: 100%"></div>
        </div>
        <div class="exam-actions">
          <div class="exam-score">108 <small>/ 120分</small></div>
          <button class="btn btn-outline">再做一次</button>
        </div>
      </div>
      
      <div class="exam-card" onclick="startExam('2024-english')">
        <div class="exam-header">
          <div class="exam-title">2024年某市中考英语真题</div>
          <span class="exam-badge year">2024真题</span>
        </div>
        <div class="exam-info">
          <span><i class="fas fa-clock"></i> 100分钟</span>
          <span><i class="fas fa-question-circle"></i> 65题</span>
          <span><i class="fas fa-star"></i> 120分</span>
        </div>
        <div class="exam-progress">
          <div class="exam-progress-bar" style="width: 60%"></div>
        </div>
        <div class="exam-actions">
          <div class="exam-score">-- <small>/ 120分</small></div>
          <button class="btn btn-primary">继续做题</button>
        </div>
      </div>
      
      <div class="exam-card" onclick="startExam('simulate-physics')">
        <div class="exam-header">
          <div class="exam-title">物理电学专项模拟卷</div>
          <span class="exam-badge simulate">模拟题</span>
        </div>
        <div class="exam-info">
          <span><i class="fas fa-clock"></i> 90分钟</span>
          <span><i class="fas fa-question-circle"></i> 20题</span>
          <span><i class="fas fa-star"></i> 100分</span>
        </div>
        <div class="exam-progress">
          <div class="exam-progress-bar" style="width: 0%"></div>
        </div>
        <div class="exam-actions">
          <div class="exam-score">-- <small>/ 100分</small></div>
          <button class="btn btn-primary">开始练习</button>
        </div>
      </div>
      
      <div class="exam-card" onclick="startExam('special-chemistry')">
        <div class="exam-header">
          <div class="exam-title">化学酸碱盐专题训练</div>
          <span class="exam-badge special">专题</span>
        </div>
        <div class="exam-info">
          <span><i class="fas fa-clock"></i> 60分钟</span>
          <span><i class="fas fa-question-circle"></i> 15题</span>
          <span><i class="fas fa-star"></i> 50分</span>
        </div>
        <div class="exam-progress">
          <div class="exam-progress-bar" style="width: 0%"></div>
        </div>
        <div class="exam-actions">
          <div class="exam-score">-- <small>/ 50分</small></div>
          <button class="btn btn-primary">开始练习</button>
        </div>
      </div>
      
      <div class="exam-card" onclick="startExam('2023-chinese')">
        <div class="exam-header">
          <div class="exam-title">2023年某市中考语文真题</div>
          <span class="exam-badge year">2023真题</span>
        </div>
        <div class="exam-info">
          <span><i class="fas fa-clock"></i> 150分钟</span>
          <span><i class="fas fa-question-circle"></i> 20题</span>
          <span><i class="fas fa-star"></i> 120分</span>
        </div>
        <div class="exam-progress">
          <div class="exam-progress-bar" style="width: 0%"></div>
        </div>
        <div class="exam-actions">
          <div class="exam-score">-- <small>/ 120分</small></div>
          <button class="btn btn-primary">开始练习</button>
        </div>
      </div>
    </div>
  </div>
  
  <script>
    function startExam(id) {
      // 跳转到答题页面
      window.location.href = 'exam.php?id=' + id;
    }
    
    // 筛选功能
    document.querySelectorAll('.filter-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        // TODO: 根据筛选条件过滤试卷
      });
    });
// 从 API 加载试卷列表
async function loadExams() {
  try {
    const data = await ZhongkaoAPI.getExams();
    // TODO: 渲染试卷列表
  } catch(e) { console.error(e); }
}
document.addEventListener("DOMContentLoaded", loadExams);
  </script>
</body>
</html>
