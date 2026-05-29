<?php
require_once __DIR__ . '/../includes/community_config.php';
if (!isCommunityLoggedIn()) { communityRedirect('login.php'); }
$user = getCurrentCommunityUser();
$userId = getCurrentCommunityUserId();
$userRole = $user['role'] ?? 'user';

// Scan downloads directory
$downloadsDir = __DIR__ . '/downloads';
$files = [];
if (is_dir($downloadsDir)) {
    $items = scandir($downloadsDir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $downloadsDir . '/' . $item;
        if (is_file($path)) {
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            $size = filesize($path);
            $mtime = filemtime($path);
            // human-readable size
            if ($size >= 1073741824) $hSize = round($size / 1073741824, 1) . ' GB';
            elseif ($size >= 1048576) $hSize = round($size / 1048576, 1) . ' MB';
            elseif ($size >= 1024) $hSize = round($size / 1024, 1) . ' KB';
            else $hSize = $size . ' B';
            
            $files[] = [
                'name' => $item,
                'ext' => $ext,
                'size' => $size,
                'hSize' => $hSize,
                'mtime' => $mtime,
                'mtimeFmt' => date('Y-m-d H:i', $mtime),
                'url' => '/downloads/' . rawurlencode($item)
            ];
        }
    }
    // Sort by mtime descending (newest first)
    usort($files, function($a, $b) { return $b['mtime'] - $a['mtime']; });
}

function fileIcon($ext) {
    $map = [
        'pdf' => 'fa-file-pdf',
        'doc' => 'fa-file-word', 'docx' => 'fa-file-word',
        'xls' => 'fa-file-excel', 'xlsx' => 'fa-file-excel',
        'zip' => 'fa-file-archive', 'rar' => 'fa-file-archive', '7z' => 'fa-file-archive', 'tar' => 'fa-file-archive', 'gz' => 'fa-file-archive',
        'jpg' => 'fa-file-image', 'jpeg' => 'fa-file-image', 'png' => 'fa-file-image', 'gif' => 'fa-file-image', 'webp' => 'fa-file-image', 'svg' => 'fa-file-image',
        'mp4' => 'fa-file-video', 'avi' => 'fa-file-video', 'mkv' => 'fa-file-video',
        'mp3' => 'fa-file-audio', 'wav' => 'fa-file-audio', 'flac' => 'fa-file-audio',
        'exe' => 'fa-file-code', 'msi' => 'fa-file-code',
        'txt' => 'fa-file-alt', 'md' => 'fa-file-alt',
        'php' => 'fa-file-code', 'js' => 'fa-file-code', 'py' => 'fa-file-code',
    ];
    return $map[$ext] ?? 'fa-file';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.5">
<title>文件管理 · 菜籽游</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#f5f5f7;--bg2:#ffffff;--surface:rgba(255,255,255,0.7);--surface2:#e8e8ed;--accent:#007aff;--accent-dim:rgba(0,122,255,0.12);--text:#1c1c1e;--text-dim:#8e8e93;--text-bright:#000;--danger:#ff3b30;--radius:10px;--radius-lg:16px;--font:-apple-system,BlinkMacSystemFont,'SF Pro','Helvetica Neue',system-ui,sans-serif;--shadow:0 2px 12px rgba(0,0,0,0.06)}
html,body{height:100%;background:var(--bg);color:var(--text);font:15px/1.6 var(--font);-webkit-font-smoothing:antialiased}
body{padding-top:60px}
a{color:var(--accent);text-decoration:none}
::-webkit-scrollbar{width:6px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:var(--surface2);border-radius:3px}
.topbar{display:flex;align-items:center;height:52px;flex-shrink:0;background:rgba(255,255,255,0.8);-webkit-backdrop-filter:blur(20px);backdrop-filter:blur(20px);border-bottom:1px solid rgba(0,0,0,0.06);padding:0 16px;z-index:9999;position:fixed;top:0;left:0;right:0}
.topbar-left{display:flex;align-items:center;gap:8px}
.topbar-back{display:flex;align-items:center;gap:4px;padding:6px 10px;border-radius:6px;cursor:pointer;color:var(--text-dim);font-size:13px;font-weight:500;transition:.2s}
.topbar-back:hover{color:var(--accent);background:var(--accent-dim)}
.topbar-logo{font-weight:700;font-size:17px;color:var(--text-bright)}
.page-wrap{max-width:960px;margin:0 auto;padding:24px 16px}
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px}
.page-title{font-size:22px;font-weight:700;color:var(--text-bright);display:flex;align-items:center;gap:10px}
.page-title i{color:var(--accent)}
.file-count{font-size:13px;color:var(--text-dim);font-weight:400;background:var(--surface2);padding:2px 10px;border-radius:20px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border:none;border-radius:8px;font-size:13px;font-weight:500;cursor:pointer;transition:.15s;background:var(--surface2);color:var(--text)}
.btn:hover{opacity:.8}
.btn-accent{background:var(--accent);color:#fff}
.btn-danger{background:var(--danger);color:#fff}
.file-list{display:flex;flex-direction:column;gap:8px}
.file-card{display:flex;align-items:center;gap:14px;padding:14px 18px;background:var(--bg2);border-radius:var(--radius-lg);box-shadow:var(--shadow);transition:.15s;border:1px solid rgba(0,0,0,0.04)}
.file-card:hover{box-shadow:0 4px 16px rgba(0,0,0,0.08);transform:translateY(-1px)}
.file-icon{width:42px;height:42px;border-radius:10px;background:var(--accent-dim);display:flex;align-items:center;justify-content:center;color:var(--accent);font-size:20px;flex-shrink:0}
.file-icon.pdf{background:#fff0e6;color:#ff6b35}
.file-icon.image{background:#f0f7ff;color:#34a853}
.file-icon.archive{background:#fef7e0;color:#f9ab00}
.file-icon.code{background:#f0e6ff;color:#7c3aed}
.file-icon.audio{background:#e6f8ee;color:#1a73e8}
.file-icon.video{background:#fce8e6;color:#ea4335}
.file-info{flex:1;min-width:0}
.file-name{font-size:14px;font-weight:600;color:var(--text-bright);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.file-meta{font-size:12px;color:var(--text-dim);margin-top:2px;display:flex;gap:10px;flex-wrap:wrap}
.file-actions{display:flex;gap:6px;flex-shrink:0}
.file-actions .btn{font-size:12px;padding:6px 14px}
.empty-state{text-align:center;padding:60px 20px;color:var(--text-dim)}
.empty-state i{font-size:48px;margin-bottom:16px;color:var(--surface2)}
.empty-state p{font-size:15px}
/* Upload modal */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.4);-webkit-backdrop-filter:blur(4px);backdrop-filter:blur(4px);z-index:99999;display:none;align-items:center;justify-content:center}
.modal-overlay.active{display:flex}
.modal-box{background:#fff;border-radius:20px;padding:28px;width:90%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,.15)}
.modal-box h3{font-size:18px;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.form-group{margin-bottom:14px}
.form-group label{font-size:13px;font-weight:500;color:var(--text-dim);display:block;margin-bottom:4px}
.form-group input,.form-group textarea{width:100%;padding:10px 14px;border:1.5px solid rgba(0,0,0,0.08);border-radius:10px;font-size:14px;font-family:var(--font);outline:none;transition:.15s;background:var(--bg)}
.form-group input:focus,.form-group textarea:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-dim)}
.form-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:18px}
.upload-progress{display:none;margin:12px 0;padding:10px;background:var(--accent-dim);border-radius:8px;font-size:13px;color:var(--accent);text-align:center}
.upload-progress.active{display:block}
.toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#1e1e2a;color:#eee;padding:10px 20px;border-radius:8px;z-index:99999;font-size:13px;border:1px solid #2a2a3a;transition:opacity.3s;pointer-events:none}
.copy-input{position:absolute;left:-9999px}
</style>
</head>
<body>
<div class="topbar">
  <div class="topbar-left">
    <a class="topbar-back" href="/index_app.php"><i class="fas fa-arrow-left"></i> 返回</a>
    <span class="topbar-logo">📁 文件管理</span>
  </div>
</div>

<div class="page-wrap">
  <div class="page-header">
    <div class="page-title">
      <i class="fas fa-cloud-download-alt"></i> 下载中心
      <span class="file-count"><?= count($files) ?> 个文件</span>
    </div>
    <div style="display:flex;gap:8px">
      <button class="btn" onclick="location.reload()"><i class="fas fa-sync-alt"></i> 刷新</button>
      <button class="btn btn-accent" id="uploadBtn"><i class="fas fa-upload"></i> 上传</button>
    </div>
  </div>

  <?php if (empty($files)): ?>
    <div class="empty-state">
      <i class="fas fa-folder-open"></i>
      <p>暂无文件，点击上传添加</p>
    </div>
  <?php else: ?>
    <div class="file-list" id="fileList">
      <?php foreach ($files as $f):
        $icon = fileIcon($f['ext']);
        $iconClass = '';
        if (in_array($f['ext'], ['pdf'])) $iconClass = 'pdf';
        elseif (in_array($f['ext'], ['jpg','jpeg','png','gif','webp','svg'])) $iconClass = 'image';
        elseif (in_array($f['ext'], ['zip','rar','7z','tar','gz'])) $iconClass = 'archive';
        elseif (in_array($f['ext'], ['php','js','py','html','css'])) $iconClass = 'code';
        elseif (in_array($f['ext'], ['mp3','wav','flac'])) $iconClass = 'audio';
        elseif (in_array($f['ext'], ['mp4','avi','mkv'])) $iconClass = 'video';
      ?>
      <div class="file-card" data-file="<?= htmlspecialchars($f['name'], ENT_QUOTES) ?>">
        <div class="file-icon <?= $iconClass ?>"><i class="fas <?= $icon ?>"></i></div>
        <div class="file-info">
          <div class="file-name" title="<?= htmlspecialchars($f['name']) ?>"><?= htmlspecialchars($f['name']) ?></div>
          <div class="file-meta">
            <span><?= $f['hSize'] ?></span>
            <span><?= $f['mtimeFmt'] ?></span>
            <span style="color:var(--accent)">cc</span>
          </div>
        </div>
        <div class="file-actions">
          <button class="btn btn-accent" onclick="window.open('<?= $f['url'] ?>','_blank')"><i class="fas fa-download"></i> 下载</button>
          <button class="btn" onclick="copyLink('<?= $f['url'] ?>')"><i class="fas fa-link"></i></button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Upload Modal -->
<div class="modal-overlay" id="uploadModal">
  <div class="modal-box">
    <h3><i class="fas fa-upload" style="color:var(--accent)"></i> 上传文件</h3>
    <div class="form-group">
      <label>选择文件</label>
      <input type="file" id="fileInput" multiple style="font-size:13px">
    </div>
    <div class="upload-progress" id="uploadProgress">上传中...</div>
    <div class="form-actions">
      <button class="btn" onclick="document.getElementById('uploadModal').classList.remove('active')">取消</button>
      <button class="btn btn-accent" id="startUploadBtn"><i class="fas fa-cloud-upload-alt"></i> 开始上传</button>
    </div>
  </div>
</div>

<script>
function copyLink(url) {
  var full = window.location.origin + url;
  if (navigator.clipboard) {
    navigator.clipboard.writeText(full).then(function() { toast('链接已复制'); });
  } else {
    var inp = document.createElement('input');
    inp.value = full;
    inp.className = 'copy-input';
    document.body.appendChild(inp);
    inp.select();
    document.execCommand('copy');
    document.body.removeChild(inp);
    toast('链接已复制');
  }
}

function toast(msg) {
  var t = document.getElementById('toast');
  if (!t) {
    t = document.createElement('div');
    t.className = 'toast';
    t.id = 'toast';
    document.body.appendChild(t);
  }
  t.textContent = msg;
  t.style.opacity = '1';
  clearTimeout(t._t);
  t._t = setTimeout(function() { t.style.opacity = '0'; }, 2500);
}

document.getElementById('uploadBtn').onclick = function() {
  document.getElementById('uploadModal').classList.add('active');
};

document.getElementById('uploadModal').onclick = function(e) {
  if (e.target === this) this.classList.remove('active');
};

document.getElementById('startUploadBtn').onclick = function() {
  var input = document.getElementById('fileInput');
  var files = input.files;
  if (!files || files.length === 0) {
    toast('请选择文件');
    return;
  }
  var prog = document.getElementById('uploadProgress');
  prog.classList.add('active');
  prog.textContent = '正在上传 0/' + files.length + '...';
  
  var done = 0, total = files.length;
  function uploadNext(i) {
    if (i >= total) {
      prog.textContent = '上传完成！';
      setTimeout(function() {
        document.getElementById('uploadModal').classList.remove('active');
        location.reload();
      }, 800);
      return;
    }
    var fd = new FormData();
    fd.append('file', files[i]);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/upload_file.php', true);
    xhr.onload = function() {
      done++;
      prog.textContent = '正在上传 ' + done + '/' + total + '...';
      uploadNext(i + 1);
    };
    xhr.onerror = function() {
      done++;
      toast('上传失败: ' + files[i].name);
      prog.textContent = '正在上传 ' + done + '/' + total + '...';
      uploadNext(i + 1);
    };
    xhr.send(fd);
  }
  uploadNext(0);
};

// Drag and drop support
document.addEventListener('dragover', function(e) { e.preventDefault(); });
document.addEventListener('drop', function(e) {
  e.preventDefault();
  var files = e.dataTransfer.files;
  if (files && files.length > 0) {
    document.getElementById('fileInput').files = files;
    document.getElementById('uploadModal').classList.add('active');
  }
});
</script>
</body>
</html>
