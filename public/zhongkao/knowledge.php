<?php session_start(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>知识点梳理 - 中考备战</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    :root { --primary: #4f46e5; --bg: #f8fafc; --card: #fff; --text: #1e293b; --text-dim: #64748b; --border: #e2e8f0; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: -apple-system, sans-serif; background: var(--bg); color: var(--text); }
    .topbar { background: var(--card); border-bottom: 1px solid var(--border); padding: 12px 20px; position: sticky; top: 0; z-index: 100; }
    .topbar-inner { max-width: 1000px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; }
    .topbar-back { color: var(--text-dim); text-decoration: none; }
    .topbar-brand { font-weight: 600; color: var(--primary); }
    .container { max-width: 1000px; margin: 0 auto; padding: 20px; }
    .page-title { font-size: 24px; font-weight: 700; margin-bottom: 24px; }
    
    /* 科目选择 */
    .subject-tabs { display: flex; gap: 10px; margin-bottom: 24px; flex-wrap: wrap; }
    .subject-tab { padding: 10px 20px; border-radius: 10px; border: 1px solid var(--border); background: var(--card); cursor: pointer; transition: .2s; }
    .subject-tab:hover, .subject-tab.active { background: var(--primary); color: white; border-color: var(--primary); }
    
    /* 知识点列表 */
    .knowledge-section { background: var(--card); border-radius: 16px; border: 1px solid var(--border); margin-bottom: 20px; overflow: hidden; }
    .section-header { padding: 16px 20px; background: var(--bg); cursor: pointer; display: flex; align-items: center; justify-content: space-between; }
    .section-header:hover { background: #e2e8f0; }
    .section-title { font-weight: 600; display: flex; align-items: center; gap: 10px; }
    .section-count { background: var(--primary); color: white; padding: 2px 8px; border-radius: 10px; font-size: 12px; }
    .section-content { padding: 20px; display: none; }
    .section-content.open { display: block; }
    
    .knowledge-item { padding: 12px 0; border-bottom: 1px solid var(--border); }
    .knowledge-item:last-child { border-bottom: none; }
    .knowledge-title { font-weight: 500; margin-bottom: 6px; display: flex; align-items: center; gap: 8px; }
    .knowledge-desc { color: var(--text-dim); font-size: 14px; line-height: 1.6; }
    .tag { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; margin-right: 6px; }
    .tag.important { background: #fee2e2; color: #dc2626; }
    .tag.normal { background: #dbeafe; color: #2563eb; }
    .tag.easy { background: #dcfce7; color: #16a34a; }
  </style>
</head>
<body>
  <div class="topbar">
    <div class="topbar-inner">
      <a href="index.php" class="topbar-back"><i class="fas fa-arrow-left"></i> 返回</a>
      <div class="topbar-brand">知识点梳理</div>
      <div></div>
    </div>
  </div>
  
  <div class="container">
    <div class="page-title"><i class="fas fa-book"></i> 知识点梳理</div>
    
    <!-- 科目选择 -->
    <div class="subject-tabs">
      <div class="subject-tab active" onclick="switchSubject(this, 'math')">📐 数学</div>
      <div class="subject-tab" onclick="switchSubject(this, 'chinese')">📝 语文</div>
      <div class="subject-tab" onclick="switchSubject(this, 'english')">📚 英语</div>
      <div class="subject-tab" onclick="switchSubject(this, 'physics')">🔬 物理</div>
      <div class="subject-tab" onclick="switchSubject(this, 'chemistry')">🧪 化学</div>
      <div class="subject-tab" onclick="switchSubject(this, 'history')">🏛️ 历史</div>
      <div class="subject-tab" onclick="switchSubject(this, 'politics')">🌍 政治</div>
    </div>
    
    <!-- 数学知识点 -->
    <div id="math" class="subject-content">
      <div class="knowledge-section">
        <div class="section-header" onclick="toggleSection(this)">
          <div class="section-title">
            <i class="fas fa-calculator"></i> 代数
            <span class="section-count">12 个知识点</span>
          </div>
          <i class="fas fa-chevron-down"></i>
        </div>
        <div class="section-content">
          <div class="knowledge-item">
            <div class="knowledge-title">
              <span class="tag important">重点</span> 二次函数
            </div>
            <div class="knowledge-desc">
              y = ax² + bx + c (a≠0)<br>
              • 开口方向：a>0 向上，a<0 向下<br>
              • 顶点坐标：(-b/2a, (4ac-b²)/4a)<br>
              • 对称轴：x = -b/2a<br>
              • 与x轴交点：Δ = b²-4ac
            </div>
          </div>
          <div class="knowledge-item">
            <div class="knowledge-title">
              <span class="tag important">重点</span> 一元二次方程
            </div>
            <div class="knowledge-desc">
              ax² + bx + c = 0 (a≠0)<br>
              • 求根公式：x = (-b ± √Δ) / 2a<br>
              • 韦达定理：x₁ + x₂ = -b/a，x₁ · x₂ = c/a<br>
              • 判别式：Δ = b²-4ac
            </div>
          </div>
          <div class="knowledge-item">
            <div class="knowledge-title">
              <span class="tag normal">中等</span> 不等式
            </div>
            <div class="knowledge-desc">
              • 一元一次不等式<br>
              • 一元一次不等式组<br>
              • 在数轴上表示解集
            </div>
          </div>
        </div>
      </div>
      
      <div class="knowledge-section">
        <div class="section-header" onclick="toggleSection(this)">
          <div class="section-title">
            <i class="fas fa-shapes"></i> 几何
            <span class="section-count">15 个知识点</span>
          </div>
          <i class="fas fa-chevron-down"></i>
        </div>
        <div class="section-content">
          <div class="knowledge-item">
            <div class="knowledge-title">
              <span class="tag important">重点</span> 相似三角形
            </div>
            <div class="knowledge-desc">
              • 判定定理：AA、SAS、SSS<br>
              • 性质：对应边成比例，对应角相等<br>
              • 面积比 = 相似比的平方
            </div>
          </div>
          <div class="knowledge-item">
            <div class="knowledge-title">
              <span class="tag important">重点</span> 圆
            </div>
            <div class="knowledge-desc">
              • 垂径定理<br>
              • 圆周角定理<br>
              • 切线的判定和性质<br>
              • 弧长和扇形面积
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- 其他科目内容（默认隐藏） -->
    <div id="chinese" class="subject-content" style="display:none">
      <div class="knowledge-section">
        <div class="section-header" onclick="toggleSection(this)">
          <div class="section-title"><i class="fas fa-pen"></i> 古诗词 <span class="section-count">20 首</span></div>
          <i class="fas fa-chevron-down"></i>
        </div>
        <div class="section-content">
          <div class="knowledge-item">
            <div class="knowledge-title"><span class="tag important">必背</span> 《望岳》杜甫</div>
            <div class="knowledge-desc">岱宗夫如何？齐鲁青未了。造化钟神秀，阴阳割昏晓。荡胸生曾云，决眦入归鸟。会当凌绝顶，一览众山小。</div>
          </div>
        </div>
      </div>
    </div>
    
    <div id="english" class="subject-content" style="display:none">
      <div class="knowledge-section">
        <div class="section-header" onclick="toggleSection(this)">
          <div class="section-title"><i class="fas fa-spell-check"></i> 核心词汇 <span class="section-count">500+</span></div>
          <i class="fas fa-chevron-down"></i>
        </div>
        <div class="section-content">
          <div class="knowledge-item">
            <div class="knowledge-title"><span class="tag important">高频</span> 动词短语</div>
            <div class="knowledge-desc">look forward to, depend on, give up, turn on/off, take place...</div>
          </div>
        </div>
      </div>
    </div>
    
    <div id="physics" class="subject-content" style="display:none">
      <div class="knowledge-section">
        <div class="section-header" onclick="toggleSection(this)">
          <div class="section-title"><i class="fas fa-bolt"></i> 电学 <span class="section-count">8 个知识点</span></div>
          <i class="fas fa-chevron-down"></i>
        </div>
        <div class="section-content">
          <div class="knowledge-item">
            <div class="knowledge-title"><span class="tag important">重点</span> 欧姆定律</div>
            <div class="knowledge-desc">I = U/R<br>• 串联电路：I = I₁ = I₂，U = U₁ + U₂<br>• 并联电路：U = U₁ = U₂，I = I₁ + I₂</div>
          </div>
        </div>
      </div>
    </div>
    
    <div id="chemistry" class="subject-content" style="display:none">化学知识点...</div>
    <div id="history" class="subject-content" style="display:none">历史知识点...</div>
    <div id="politics" class="subject-content" style="display:none">政治知识点...</div>
  </div>
  
  <script>
    function switchSubject(el, subject) {
      document.querySelectorAll('.subject-tab').forEach(t => t.classList.remove('active'));
      el.classList.add('active');
      document.querySelectorAll('.subject-content').forEach(c => c.style.display = 'none');
      document.getElementById(subject).style.display = 'block';
    }
    
    function toggleSection(el) {
      const content = el.nextElementSibling;
      content.classList.toggle('open');
      const icon = el.querySelector('.fa-chevron-down');
      icon.style.transform = content.classList.contains('open') ? 'rotate(180deg)' : '';
    }
  </script>
</body>
</html>
