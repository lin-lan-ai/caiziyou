<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>学习计划 - 中考备战</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    :root {
      --primary: #4f46e5;
      --success: #22c55e;
      --warning: #f59e0b;
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
    }
    
    .topbar {
      background: var(--card);
      border-bottom: 1px solid var(--border);
      padding: 12px 20px;
      position: sticky;
      top: 0;
      z-index: 100;
    }
    
    .topbar-inner {
      max-width: 800px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    
    .topbar-brand {
      font-weight: 600;
      color: var(--primary);
    }
    
    .topbar-back {
      color: var(--text-dim);
      text-decoration: none;
    }
    
    .container {
      max-width: 800px;
      margin: 0 auto;
      padding: 20px;
    }
    
    .page-title {
      font-size: 24px;
      font-weight: 700;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    /* 添加计划按钮 */
    .add-btn {
      background: var(--primary);
      color: white;
      border: none;
      padding: 12px 24px;
      border-radius: 10px;
      font-size: 16px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: .2s;
    }
    
    .add-btn:hover { background: #4338ca; }
    
    /* 日期选择器 */
    .date-nav {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 20px;
      margin-bottom: 24px;
    }
    
    .date-nav button {
      background: none;
      border: 1px solid var(--border);
      padding: 8px 16px;
      border-radius: 8px;
      cursor: pointer;
      transition: .2s;
    }
    
    .date-nav button:hover {
      background: var(--primary);
      color: white;
      border-color: var(--primary);
    }
    
    .date-current {
      font-size: 18px;
      font-weight: 600;
    }
    
    /* 计划列表 */
    .plan-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    
    .plan-item {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 16px;
      display: flex;
      align-items: center;
      gap: 16px;
      transition: .2s;
    }
    
    .plan-item:hover {
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    
    .plan-checkbox {
      width: 24px;
      height: 24px;
      border-radius: 50%;
      border: 2px solid var(--border);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: .2s;
    }
    
    .plan-checkbox.checked {
      background: var(--success);
      border-color: var(--success);
      color: white;
    }
    
    .plan-content { flex: 1; }
    
    .plan-subject {
      display: inline-block;
      padding: 2px 8px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: 500;
      margin-bottom: 4px;
    }
    
    .plan-subject.math { background: #dbeafe; color: #2563eb; }
    .plan-subject.chinese { background: #dcfce7; color: #16a34a; }
    .plan-subject.english { background: #fef3c7; color: #d97706; }
    .plan-subject.physics { background: #fee2e2; color: #dc2626; }
    .plan-subject.chemistry { background: #f3e8ff; color: #9333ea; }
    
    .plan-title {
      font-weight: 500;
      margin-bottom: 4px;
    }
    
    .plan-title.completed {
      text-decoration: line-through;
      color: var(--text-dim);
    }
    
    .plan-time {
      font-size: 14px;
      color: var(--text-dim);
    }
    
    .plan-actions {
      display: flex;
      gap: 8px;
    }
    
    .plan-actions button {
      background: none;
      border: none;
      color: var(--text-dim);
      cursor: pointer;
      padding: 4px 8px;
      border-radius: 4px;
      transition: .2s;
    }
    
    .plan-actions button:hover {
      background: var(--bg);
      color: var(--text);
    }
    
    /* 模态框 */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      z-index: 200;
      align-items: center;
      justify-content: center;
    }
    
    .modal.open { display: flex; }
    
    .modal-content {
      background: var(--card);
      border-radius: 16px;
      padding: 24px;
      width: 90%;
      max-width: 500px;
    }
    
    .modal-title {
      font-size: 20px;
      font-weight: 600;
      margin-bottom: 20px;
    }
    
    .form-group {
      margin-bottom: 16px;
    }
    
    .form-label {
      display: block;
      margin-bottom: 6px;
      font-weight: 500;
    }
    
    .form-input, .form-select, .form-textarea {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid var(--border);
      border-radius: 8px;
      font-size: 16px;
    }
    
    .form-textarea { min-height: 100px; resize: vertical; }
    
    .form-actions {
      display: flex;
      gap: 12px;
      justify-content: flex-end;
      margin-top: 20px;
    }
    
    .btn {
      padding: 10px 20px;
      border-radius: 8px;
      font-size: 16px;
      cursor: pointer;
      border: none;
      transition: .2s;
    }
    
    .btn-primary { background: var(--primary); color: white; }
    .btn-primary:hover { background: #4338ca; }
    
    .btn-secondary { background: var(--bg); color: var(--text); }
    .btn-secondary:hover { background: var(--border); }
    
    /* 响应式 */
    @media (max-width: 768px) {
      .plan-item { flex-direction: column; align-items: flex-start; }
      .plan-actions { width: 100%; justify-content: flex-end; }
    }
  </style>
</head>
<body>
  <div class="topbar">
    <div class="topbar-inner">
      <a href="index.php" class="topbar-back"><i class="fas fa-arrow-left"></i> 返回</a>
      <div class="topbar-brand">学习计划</div>
      <div></div>
    </div>
  </div>
  
  <div class="container">
    <div class="page-title">
      <i class="fas fa-calendar-alt"></i>
      学习计划
      <button class="add-btn" onclick="openModal()">
        <i class="fas fa-plus"></i> 添加计划
      </button>
    </div>
    
    <!-- 日期导航 -->
    <div class="date-nav">
      <button onclick="changeDate(-1)"><i class="fas fa-chevron-left"></i></button>
      <span class="date-current" id="currentDate">2026年5月30日</span>
      <button onclick="changeDate(1)"><i class="fas fa-chevron-right"></i></button>
    </div>
    
    <!-- 计划列表 -->
    <div class="plan-list" id="planList">
      <!-- 示例计划 -->
      <div class="plan-item">
        <div class="plan-checkbox" onclick="toggleComplete(this)"></div>
        <div class="plan-content">
          <span class="plan-subject math">数学</span>
          <div class="plan-title">复习二次函数图像与性质</div>
          <div class="plan-time"><i class="fas fa-clock"></i> 09:00 - 10:30</div>
        </div>
        <div class="plan-actions">
          <button title="编辑"><i class="fas fa-edit"></i></button>
          <button title="删除"><i class="fas fa-trash"></i></button>
        </div>
      </div>
      
      <div class="plan-item">
        <div class="plan-checkbox" onclick="toggleComplete(this)"></div>
        <div class="plan-content">
          <span class="plan-subject english">英语</span>
          <div class="plan-title">背诵 Unit 8 单词 + 阅读理解 2 篇</div>
          <div class="plan-time"><i class="fas fa-clock"></i> 10:30 - 12:00</div>
        </div>
        <div class="plan-actions">
          <button title="编辑"><i class="fas fa-edit"></i></button>
          <button title="删除"><i class="fas fa-trash"></i></button>
        </div>
      </div>
      
      <div class="plan-item">
        <div class="plan-checkbox checked" onclick="toggleComplete(this)">
          <i class="fas fa-check" style="font-size: 12px;"></i>
        </div>
        <div class="plan-content">
          <span class="plan-subject chinese">语文</span>
          <div class="plan-title completed">默写古诗词 5 首</div>
          <div class="plan-time"><i class="fas fa-clock"></i> 14:00 - 15:00</div>
        </div>
        <div class="plan-actions">
          <button title="编辑"><i class="fas fa-edit"></i></button>
          <button title="删除"><i class="fas fa-trash"></i></button>
        </div>
      </div>
      
      <div class="plan-item">
        <div class="plan-checkbox" onclick="toggleComplete(this)"></div>
        <div class="plan-content">
          <span class="plan-subject physics">物理</span>
          <div class="plan-title">电学综合练习题 10 道</div>
          <div class="plan-time"><i class="fas fa-clock"></i> 15:30 - 17:00</div>
        </div>
        <div class="plan-actions">
          <button title="编辑"><i class="fas fa-edit"></i></button>
          <button title="删除"><i class="fas fa-trash"></i></button>
        </div>
      </div>
      
      <div class="plan-item">
        <div class="plan-checkbox" onclick="toggleComplete(this)"></div>
        <div class="plan-content">
          <span class="plan-subject chemistry">化学</span>
          <div class="plan-title">酸碱盐实验视频 + 笔记整理</div>
          <div class="plan-time"><i class="fas fa-clock"></i> 19:00 - 20:30</div>
        </div>
        <div class="plan-actions">
          <button title="编辑"><i class="fas fa-edit"></i></button>
          <button title="删除"><i class="fas fa-trash"></i></button>
        </div>
      </div>
    </div>
  </div>
  
  <!-- 添加计划模态框 -->
  <div class="modal" id="addModal">
    <div class="modal-content">
      <div class="modal-title">添加学习计划</div>
      
      <div class="form-group">
        <label class="form-label">科目</label>
        <select class="form-select" id="subject">
          <option value="math">数学</option>
          <option value="chinese">语文</option>
          <option value="english">英语</option>
          <option value="physics">物理</option>
          <option value="chemistry">化学</option>
          <option value="history">历史</option>
          <option value="politics">政治</option>
        </select>
      </div>
      
      <div class="form-group">
        <label class="form-label">计划内容</label>
        <textarea class="form-textarea" id="content" placeholder="例如：复习二次函数图像与性质"></textarea>
      </div>
      
      <div class="form-group">
        <label class="form-label">时间</label>
        <div style="display: flex; gap: 10px;">
          <input type="time" class="form-input" id="startTime" value="09:00">
          <span style="line-height: 40px;">至</span>
          <input type="time" class="form-input" id="endTime" value="10:00">
        </div>
      </div>
      
      <div class="form-actions">
        <button class="btn btn-secondary" onclick="closeModal()">取消</button>
        <button class="btn btn-primary" onclick="addPlan()">添加</button>
      </div>
    </div>
  </div>
  
  <script>
    // 切换完成状态
    function toggleComplete(el) {
      el.classList.toggle('checked');
      const title = el.nextElementSibling.querySelector('.plan-title');
      title.classList.toggle('completed');
      
      if (el.classList.contains('checked')) {
        el.innerHTML = '<i class="fas fa-check" style="font-size: 12px;"></i>';
      } else {
        el.innerHTML = '';
      }
      
      // 保存到后端
      savePlans();
    }
    
    // 打开模态框
    function openModal() {
      document.getElementById('addModal').classList.add('open');
    }
    
    // 关闭模态框
    function closeModal() {
      document.getElementById('addModal').classList.remove('open');
    }
    
    // 添加计划
    function addPlan() {
      const subject = document.getElementById('subject').value;
      const content = document.getElementById('content').value;
      const startTime = document.getElementById('startTime').value;
      const endTime = document.getElementById('endTime').value;
      
      if (!content) {
        alert('请输入计划内容');
        return;
      }
      
      const subjectNames = {
        math: '数学', chinese: '语文', english: '英语',
        physics: '物理', chemistry: '化学', history: '历史', politics: '政治'
      };
      
      const planHtml = `
        <div class="plan-item">
          <div class="plan-checkbox" onclick="toggleComplete(this)"></div>
          <div class="plan-content">
            <span class="plan-subject ${subject}">${subjectNames[subject]}</span>
            <div class="plan-title">${content}</div>
            <div class="plan-time"><i class="fas fa-clock"></i> ${startTime} - ${endTime}</div>
          </div>
          <div class="plan-actions">
            <button title="编辑"><i class="fas fa-edit"></i></button>
            <button title="删除"><i class="fas fa-trash"></i></button>
          </div>
        </div>
      `;
      
      document.getElementById('planList').insertAdjacentHTML('beforeend', planHtml);
      closeModal();
      savePlans();
      
      // 清空表单
      document.getElementById('content').value = '';
    }
    
    // 保存计划到后端
    function savePlans() {
      const plans = [];
      document.querySelectorAll('.plan-item').forEach(item => {
        plans.push({
          subject: item.querySelector('.plan-subject').textContent,
          title: item.querySelector('.plan-title').textContent,
          time: item.querySelector('.plan-time').textContent.replace(/[^\d:-]/g, ''),
          completed: item.querySelector('.plan-checkbox').classList.contains('checked')
        });
      });
      
      await ZhongkaoAPI.addPlan({subject, title: content, start_time: startTime, end_time: endTime});
      console.log('保存计划:', plans);
    }
    
    // 日期导航
    let currentDate = new Date();
    
    function changeDate(delta) {
      currentDate.setDate(currentDate.getDate() + delta);
      document.getElementById('currentDate').textContent = 
        currentDate.toLocaleDateString('zh-CN', { year: 'numeric', month: 'long', day: 'numeric' });
      
      // TODO: 从后端加载该日期的计划
    }
    
    // 初始化日期
    document.getElementById('currentDate').textContent = 
      currentDate.toLocaleDateString('zh-CN', { year: 'numeric', month: 'long', day: 'numeric' });
  </script>
</body>
</html>
