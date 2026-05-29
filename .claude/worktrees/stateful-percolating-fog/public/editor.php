<?php
/**
 * 公众号文档编辑器 - 原型页面
 * 菜籽游x纵流社群
 */
require_once __DIR__ . '/../includes/community_config.php';
$user = null;
$loggedIn = isCommunityLoggedIn();
if ($loggedIn) {
    $user = getCurrentCommunityUser();
}
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>公众号文档编辑器 - 菜籽游</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#f0f0f2;--bg2:#fff;--text:#1c1c1e;--text-dim:#8e8e93;--accent:#007aff;--accent-dim:rgba(0,122,255,0.1);--danger:#ff3b30;--green:#34c759;--orange:#ff9500;--border:rgba(0,0,0,0.08);--radius:10px;--font:-apple-system,BlinkMacSystemFont,'SF Pro','Helvetica Neue',system-ui,sans-serif}
html,body{height:100%;background:var(--bg);font:14px/1.6 var(--font);color:var(--text)}
body{display:flex;flex-direction:column}
/* 顶部导航 */
.topbar{display:flex;align-items:center;height:48px;background:rgba(255,255,255,0.85);-webkit-backdrop-filter:blur(16px);backdrop-filter:blur(16px);border-bottom:1px solid var(--border);padding:0 16px;flex-shrink:0;z-index:100;gap:12px}
.topbar-logo{font-weight:700;font-size:16px;color:var(--text)}
.topbar-nav{display:flex;gap:4px;flex:1}
.topbar-nav a{color:var(--text-dim);text-decoration:none;padding:6px 12px;border-radius:6px;font-size:13px;transition:.15s}
.topbar-nav a:hover{color:var(--text);background:rgba(0,0,0,0.04)}
.topbar-nav a.active{color:var(--accent);background:var(--accent-dim)}
.topbar-right{display:flex;align-items:center;gap:10px;font-size:13px;color:var(--text-dim)}
/* 主布局 - 编辑区+预览区并排 */
.main{display:flex;flex:1;overflow:hidden}
/* 编辑区 */
.editor{flex:1;display:flex;flex-direction:column;overflow:hidden;border-right:1px solid var(--border)}
.editor-toolbar{display:flex;align-items:center;gap:6px;padding:10px 16px;background:var(--bg2);border-bottom:1px solid var(--border);flex-wrap:wrap;flex-shrink:0}
.editor-toolbar button,.editor-toolbar select{height:32px;padding:0 10px;border:1px solid var(--border);border-radius:6px;background:var(--bg2);font-size:13px;cursor:pointer;color:var(--text);transition:.12s;display:inline-flex;align-items:center;gap:4px}
.editor-toolbar button:hover{background:var(--accent-dim);border-color:var(--accent)}
.editor-toolbar button.active{background:var(--accent-dim);border-color:var(--accent);color:var(--accent)}
.editor-toolbar .sep{width:1px;height:20px;background:var(--border);margin:0 2px}
.editor-toolbar input[type="color"]{width:32px;height:32px;padding:2px;border:1px solid var(--border);border-radius:6px;cursor:pointer}
#editorContent{flex:1;overflow-y:auto;padding:24px 32px;background:var(--bg2);font-size:15px;line-height:1.7;outline:none;min-height:0}
#editorContent:empty::before{content:'开始编写公众号推文...';color:var(--text-dim)}
/* 预览区 */
.preview{width:400px;min-width:380px;max-width:420px;display:flex;flex-direction:column;overflow:hidden;background:#f5f5f7}
.preview-header{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid var(--border);flex-shrink:0}
.preview-header h3{font-size:14px;font-weight:600}
.preview-actions{display:flex;gap:6px}
.preview-actions button{height:30px;padding:0 12px;border:none;border-radius:6px;font-size:12px;cursor:pointer;transition:.12s;display:inline-flex;align-items:center;gap:4px}
.btn-encode{background:var(--accent);color:#fff}
.btn-encode:hover{opacity:.85}
.btn-copy{background:var(--bg2);border:1px solid var(--border)!important;color:var(--text)}
.btn-copy:hover{background:var(--accent-dim)}
.st-btn{width:30px;height:30px;padding:0;border:1px solid var(--border);border-radius:6px;background:var(--bg2);cursor:pointer;color:var(--text);font-size:13px;transition:.12s;display:inline-flex;align-items:center;justify-content:center}
.st-btn:hover{background:var(--accent-dim);border-color:var(--accent)}
.st-btn.active{background:var(--accent-dim);border-color:var(--accent);color:var(--accent)}
.btn-save{background:var(--green);color:#fff}
.btn-save:hover{opacity:.85}
/* 手机模拟预览 */
.phone-frame{flex:1;overflow-y:auto;padding:20px 16px;scroll-behavior:smooth}
.phone-frame::-webkit-scrollbar{width:4px}
.phone-frame::-webkit-scrollbar-thumb{background:var(--border);border-radius:2px}
/* 预览-公众号卡片样式 */
.pub-card{background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 6px rgba(0,0,0,0.06);margin-bottom:16px}
.pub-cover{width:100%;aspect-ratio:16/9;object-fit:cover;display:block;background:#e8e8ed}
.pub-cover-empty{width:100%;aspect-ratio:16/9;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;color:#fff;font-size:32px;opacity:.6}
.pub-card-body{padding:14px 16px}
.pub-title{font-size:17px;font-weight:600;color:#1c1c1e;line-height:1.4;margin-bottom:6px}
.pub-desc{font-size:13px;color:#8e8e93;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.pub-meta{display:flex;align-items:center;gap:8px;padding:10px 16px 12px;font-size:11px;color:#b0b0b0}
.pub-meta .dot{width:3px;height:3px;border-radius:50%;background:#c0c0c0}
/* 预览-文章正文 */
.pub-article{padding:0 4px}
.pub-article h1{font-size:20px;font-weight:700;color:#1c1c1e;line-height:1.4;margin-bottom:12px}
.pub-article h2{font-size:17px;font-weight:600;color:#1c1c1e;margin:20px 0 10px;padding-left:10px;border-left:3px solid var(--accent)}
.pub-article h3{font-size:15px;font-weight:600;color:#3a3a3c;margin:16px 0 8px}
.pub-article p{font-size:15px;color:#3a3a3c;line-height:1.75;margin-bottom:10px}
.pub-article .highlight{color:var(--accent);font-weight:600}
.pub-article .quote{border-left:3px solid var(--green);padding:10px 14px;margin:12px 0;background:#f9f9fb;border-radius:0 6px 6px 0;font-size:14px;color:#636366}
.pub-article .img-wrap{margin:14px 0;border-radius:8px;overflow:hidden}
.pub-article .img-wrap img{width:100%;display:block}
.pub-article .img-wrap .caption{font-size:12px;color:#8e8e93;text-align:center;padding:6px 0 2px}
.pub-article .divider{text-align:center;color:#c0c0c0;margin:16px 0;font-size:12px;letter-spacing:4px}
.pub-article .btn-link{display:inline-flex;align-items:center;gap:6px;padding:10px 24px;background:var(--accent);color:#fff!important;border-radius:22px;font-size:14px;font-weight:500;margin:8px 0;text-decoration:none}
.pub-article .btn-link:hover{opacity:.85}
/* 编码/解码区域 */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);-webkit-backdrop-filter:blur(4px);backdrop-filter:blur(4px);z-index:200;align-items:center;justify-content:center}
.modal-overlay.active{display:flex}
.modal-box{background:#fff;border-radius:16px;width:90%;max-width:520px;max-height:80vh;overflow-y:auto;padding:24px;box-shadow:0 8px 40px rgba(0,0,0,0.15)}
.modal-box h3{font-size:17px;font-weight:600;margin-bottom:16px}
.modal-box textarea{width:100%;min-height:120px;padding:12px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:monospace;resize:vertical;outline:none}
.modal-box textarea:focus{border-color:var(--accent)}
.modal-box .modal-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:16px}
.modal-box .modal-actions button{padding:8px 20px;border:none;border-radius:8px;font-size:13px;cursor:pointer}
/* 解码页模式 */
.decode-mode .editor{display:none}
.decode-mode .preview{width:100%;max-width:none;min-width:0}
.decode-mode .topbar .editor-actions{display:none}
/* 无解码数据提示 */
.placeholder-box{display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;color:var(--text-dim);text-align:center;padding:40px}
.placeholder-box i{font-size:48px;margin-bottom:16px;opacity:.4}
.placeholder-box p{font-size:15px}
/* 响应式 */
@media(max-width:860px){.main{flex-direction:column}.preview{width:100%;min-width:0;max-width:none;max-height:50vh;border-top:1px solid var(--border)}#editorContent{padding:16px}}
@media(max-width:480px){.editor-toolbar{gap:4px;padding:8px 10px}.editor-toolbar button,.editor-toolbar select{height:28px;padding:0 8px;font-size:12px}.preview{max-height:45vh}}
/* 编码/解码徽章 */
.code-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:500}
.code-badge.cb-encode{background:var(--accent-dim);color:var(--accent)}
.code-badge.cb-decode{background:rgba(52,199,89,0.12);color:var(--green)}
</style>
</head>
<body>
<div class="topbar">
  <span class="topbar-logo">📝 公众号编辑器</span>
  <div class="topbar-nav">
    <a href="index_app.php"><i class="fas fa-arrow-left"></i> 返回</a>
  </div>
  <div class="topbar-right">
    <span id="userInfo"><?=$loggedIn ? htmlspecialchars($user['username']??'') : '未登录'?></span>
  </div>
</div>
<div class="main" id="appMain">
  <!-- 编辑区 -->
  <div class="editor" id="editorPane">
    <div class="editor-toolbar" id="toolbar">
      <!-- 样式组合输入区 -->
      <div class="style-input-row" style="display:flex;width:100%;gap:6px;align-items:center;flex-wrap:wrap;padding:8px 0;border-bottom:1px solid var(--border);margin-bottom:6px">
        <input id="styleTextInput" type="text" placeholder="输入文字内容..." style="flex:1;min-width:120px;padding:8px 12px;border:1px solid var(--border);border-radius:6px;font-size:14px;outline:none;background:var(--bg2)">
        <span style="font-size:12px;color:var(--text-dim)">样式：</span>
        <button id="stBold" class="st-btn" data-css="font-weight:700" title="加粗"><i class="fas fa-bold"></i></button>
        <button id="stItalic" class="st-btn" data-css="font-style:italic" title="斜体"><i class="fas fa-italic"></i></button>
        <button id="stUnderline" class="st-btn" data-css="text-decoration:underline" title="下划线"><i class="fas fa-underline"></i></button>
        <input type="color" id="stColor" value="#007aff" title="文字颜色" style="width:30px;height:30px;padding:1px;border:1px solid var(--border);border-radius:4px;cursor:pointer">
        <button id="stInsertBtn" class="btn-encode" style="height:30px;padding:0 14px;border:none;border-radius:6px;font-size:12px;cursor:pointer;background:var(--accent);color:#fff;display:inline-flex;align-items:center;gap:4px"><i class="fas fa-plus"></i> 插入</button>
      </div>
      <!-- 传统快捷按钮 -->
      <div style="display:flex;align-items:center;gap:4px;flex-wrap:wrap;width:100%">
        <button id="tbBold" title="加粗（选中文字）"><i class="fas fa-bold"></i></button>
        <button id="tbItalic" title="斜体（选中文字）"><i class="fas fa-italic"></i></button>
        <button id="tbUnderline" title="下划线（选中文字）"><i class="fas fa-underline"></i></button>
        <div class="sep"></div>
        <button id="tbH1" title="一级标题">H1</button>
        <button id="tbH2" title="二级标题">H2</button>
        <button id="tbH3" title="三级标题">H3</button>
        <div class="sep"></div>
        <button id="tbQuote" title="引用"><i class="fas fa-quote-left"></i></button>
        <button id="tbDivider" title="分割线"><i class="fas fa-minus"></i></button>
        <button id="tbLink" title="行内链接"><i class="fas fa-link"></i></button>
        <div class="sep"></div>
        <button id="tbImage" title="插入图片"><i class="fas fa-image"></i></button>
        <button id="tbTable" title="插入表格"><i class="fas fa-table"></i></button>
        <div class="sep"></div>
        <button id="tbClear" title="清除格式"><i class="fas fa-eraser"></i></button>
      </div>
    </div>
    <div id="editorContent" contenteditable="true"></div>
  </div>
  <!-- 预览区 -->
  <div class="preview" id="previewPane">
    <div class="preview-header">
      <h3><i class="fas fa-mobile-alt"></i> 预览</h3>
      <div class="preview-actions">
        <button class="btn-encode" id="encodeBtn"><i class="fas fa-lock"></i> 编码</button>
        <button class="btn-decode" id="decodeBtn" style="background:var(--green);color:#fff;padding:0 12px;border:none;border-radius:6px;font-size:12px;cursor:pointer"><i class="fas fa-unlock"></i> 解码</button>
      </div>
    </div>
    <div class="phone-frame" id="phoneFrame">
      <!-- 文章封面card -->
      <div class="pub-card" id="pubCoverCard">
        <div class="pub-cover-empty" id="pubCoverPlaceholder"><i class="fas fa-image"></i></div>
        <img class="pub-cover" id="pubCoverImg" style="display:none">
        <div class="pub-card-body">
          <div class="pub-title" id="pubTitle">公众号文章标题</div>
          <div class="pub-desc" id="pubDesc">文章摘要 / 引导语</div>
        </div>
        <div class="pub-meta"><span>菜籽游</span><span class="dot"></span><span>预览</span></div>
      </div>
      <!-- 文章正文 -->
      <div class="pub-article" id="pubArticle">
        <p style="color:var(--text-dim);text-align:center;padding:30px 0">点击上方编辑器开始编写...</p>
      </div>
    </div>
  </div>
</div>

<!-- 编码结果弹窗 -->
<div class="modal-overlay" id="encodeModal" onclick="if(event.target===this)closeModal()">
  <div class="modal-box">
    <h3><i class="fas fa-lock" style="color:var(--accent)"></i> 编码结果</h3>
    <p style="font-size:13px;color:var(--text-dim);margin-bottom:12px">点击复制编码，发送给别人即可在菜籽游解码查看。</p>
    <textarea id="encodeOutput" readonly onclick="this.select()" placeholder="生成中..."></textarea>
    <div class="modal-actions">
      <button onclick="closeModal()" style="background:#f2f2f7">关闭</button>
      <button id="copyEncodeBtn" style="background:var(--accent);color:#fff"><i class="fas fa-copy"></i> 复制编码</button>
    </div>
  </div>
</div>

<!-- 解码弹窗 -->
<div class="modal-overlay" id="decodeModal" onclick="if(event.target===this)closeModal()">
  <div class="modal-box">
    <h3><i class="fas fa-unlock" style="color:var(--green)"></i> 解码查看</h3>
    <p style="font-size:13px;color:var(--text-dim);margin-bottom:12px">粘贴别人发给你的编码，即可查看排版后的公众号文章。</p>
    <textarea id="decodeInput" placeholder="粘贴编码..." style="min-height:100px"></textarea>
    <div id="decodeResult" style="display:none"></div>
    <div class="modal-actions">
      <button onclick="closeModal()" style="background:#f2f2f7">关闭</button>
      <button id="decodeSubmitBtn" style="background:var(--green);color:#fff"><i class="fas fa-play"></i> 解码预览</button>
    </div>
  </div>
</div>

<script>
/* ===== 公众号文档编辑器 - JS ===== */

// ---- 常量与密钥 ----
var SITE_KEY = 'cziyo_mp_2026'; // 只有菜籽游知道的密钥，可定期更换

// ---- 工具函数 ----
function byId(id){return document.getElementById(id)}

// 简单 XOR + Base64 编码（非加密，仅防随手可读）
function encodeDoc(data){
  var json = JSON.stringify(data);
  var key = SITE_KEY;
  var out = '';
  for(var i=0;i<json.length;i++){
    var kc = key.charCodeAt(i % key.length);
    out += String.fromCharCode(json.charCodeAt(i) ^ kc);
  }
  return btoa(unescape(encodeURIComponent(out)));
}

function decodeDoc(code){
  try{
    var raw = decodeURIComponent(escape(atob(code)));
    var key = SITE_KEY;
    var out = '';
    for(var i=0;i<raw.length;i++){
      var kc = key.charCodeAt(i % key.length);
      out += String.fromCharCode(raw.charCodeAt(i) ^ kc);
    }
    return JSON.parse(out);
  }catch(e){return null}
}

// 从编辑器HTML提取文档数据结构
function extractDoc(){
  var title = byId('pubTitle').textContent || '无标题';
  var desc = byId('pubDesc').textContent || '';
  var editor = byId('editorContent');
  // 提取meta
  var meta = {title: title, desc: desc, cover: ''};
  // 提取正文HTML
  var bodyHTML = editor.innerHTML;
  return {meta: meta, body: bodyHTML};
}

// 应用文档数据到预览
function applyDoc(doc){
  if(!doc) return;
  var meta = doc.meta || {};
  var body = doc.body || '';
  if(meta.title) byId('pubTitle').textContent = meta.title;
  if(meta.desc) byId('pubDesc').textContent = meta.desc;
  if(meta.cover){
    byId('pubCoverImg').src = meta.cover;
    byId('pubCoverImg').style.display = 'block';
    byId('pubCoverPlaceholder').style.display = 'none';
  }else{
    byId('pubCoverImg').style.display = 'none';
    byId('pubCoverPlaceholder').style.display = 'flex';
  }
  if(body && body.trim()){
    byId('pubArticle').innerHTML = body;
  }else{
    byId('pubArticle').innerHTML = '<p style="color:var(--text-dim);text-align:center;padding:30px 0">暂无正文内容</p>';
  }
}

// ---- 编辑器核心插入函数（纯 DOM API，不依赖 execCommand） ----
function insertHTMLAtCursor(htmlStr){
  var ed=byId('editorContent');ed.focus();
  var tmp=document.createElement('div');tmp.innerHTML=htmlStr;
  var fragment=document.createDocumentFragment();
  while(tmp.firstChild)fragment.appendChild(tmp.firstChild);
  var sel=window.getSelection();
  if(sel.rangeCount){
    var range=sel.getRangeAt(0);
    range.deleteContents();
    range.insertNode(fragment);
    range.collapse(false);
    sel.removeAllRanges();sel.addRange(range);
  }else{ed.appendChild(fragment)}
  ed.focus();
}
function insertSpanAtCursor(text,extraStyle){
  var el=document.createElement('span');
  if(extraStyle)el.setAttribute('style',extraStyle);
  el.textContent=text;
  var ed=byId('editorContent');ed.focus();
  var sel=window.getSelection();
  if(sel.rangeCount){
    var range=sel.getRangeAt(0);
    range.deleteContents();
    range.insertNode(el);
    range.setStartAfter(el);range.setEndAfter(el);
    sel.removeAllRanges();sel.addRange(range);
  }else{ed.appendChild(el)}
  ed.focus();
}
function toggleBlock(tag,styleStr){
  var ed=byId('editorContent');ed.focus();
  var sel=window.getSelection();
  if(sel.rangeCount){
    var range=sel.getRangeAt(0);
    var el=document.createElement(tag);
    if(styleStr)el.setAttribute('style',styleStr);
    el.textContent='在此输入'+(tag==='H1'?'一级':tag==='H2'?'二级':'三级')+'标题';
    range.deleteContents();range.insertNode(el);
    range.setStartAfter(el);range.setEndAfter(el);
    sel.removeAllRanges();sel.addRange(range);
  }
  ed.focus();
}

function initToolbar(){
  var ed = byId('editorContent');

  // 加粗/斜体/下划线
  byId('tbBold').onclick = function(){
    var sel=window.getSelection();
    if(sel&&sel.toString().trim()){
      insertSpanAtCursor(sel.toString(),'font-weight:700');
    }else{
      insertSpanAtCursor('加粗文字','font-weight:700');
    }
  };
  byId('tbItalic').onclick = function(){
    var sel=window.getSelection();
    if(sel&&sel.toString().trim()){
      insertSpanAtCursor(sel.toString(),'font-style:italic');
    }else{
      insertSpanAtCursor('斜体文字','font-style:italic');
    }
  };
  byId('tbUnderline').onclick = function(){
    var sel=window.getSelection();
    if(sel&&sel.toString().trim()){
      insertSpanAtCursor(sel.toString(),'text-decoration:underline');
    }else{
      insertSpanAtCursor('下划线文字','text-decoration:underline');
    }
  };

  // 标题
  byId('tbH1').onclick = function(){toggleBlock('H1','font-size:20px;font-weight:700;color:#1c1e1e;margin:0 0 12px;line-height:1.4')};
  byId('tbH2').onclick = function(){toggleBlock('H2','font-size:17px;font-weight:600;color:#1c1e1e;margin:20px 0 10px;padding-left:10px;border-left:3px solid #007aff')};
  byId('tbH3').onclick = function(){toggleBlock('H3','font-size:15px;font-weight:600;color:#3a3a3c;margin:16px 0 8px')};

  // 引用
  byId('tbQuote').onclick = function(){
    insertHTMLAtCursor('<blockquote style="border-left:3px solid #34c759;padding:10px 14px;margin:12px 0;background:#f9f9fb;border-radius:0 6px 6px 0;font-size:14px;color:#636366">引用内容</blockquote>');
  };

  // 分割线
  byId('tbDivider').onclick = function(){
    insertHTMLAtCursor('<div style="text-align:center;color:#c0c0c0;margin:16px 0;font-size:12px;letter-spacing:4px">···</div>');
  };

  // 行内超链接
  byId('tbLink').onclick = function(){
    var sel=window.getSelection();
    var selText=sel&&sel.toString().trim();
    var text=prompt('链接文字：',selText||'点击查看');
    var url=prompt('链接地址：','https://');
    if(text&&url){
      insertHTMLAtCursor('<a href="'+url+'" target="_blank" style="color:#007aff;text-decoration:underline">'+text+'</a> ');
    }
  };

  // 图片插入
  byId('tbImage').onclick = function(){
    var url=prompt('图片URL：','https://');
    var caption=prompt('图片说明（可选）：','');
    if(url){
      var cap=caption?'<div class="caption" style="font-size:12px;color:#8e8e93;text-align:center;padding:6px 0 2px">'+caption+'</div>':'';
      insertHTMLAtCursor('<div class="img-wrap" style="margin:14px 0;border-radius:8px;overflow:hidden"><img src="'+url+'" style="width:100%;display:block">'+cap+'</div>');
    }
  };

  // 表格（先弹窗让用户设置行数列数）
  byId('tbTable').onclick = function(){
    var rows=parseInt(prompt('行数：','4'))||4;
    var cols=parseInt(prompt('列数：','3'))||3;
    if(rows<1)rows=1;if(cols<1)cols=1;
    var html='<table style="width:100%;border-collapse:collapse;margin:14px 0;font-size:14px" contenteditable="false">';
    for(var r=0;r<rows;r++){
      html+='<tr>';
      for(var c=0;c<cols;c++){
        var tag=r===0?'th':'td';
        var st=r===0?'background:#f5f5f7;font-weight:600;':'';
        st+='border:1px solid #e0e0e0;padding:8px 10px;text-align:left';
        html+='<'+tag+' style="'+st+'" contenteditable="true">'+(r===0?'标题':'内容')+'</'+tag+'>';
      }
      html+='</tr>';
    }
    html+='</table><div style="font-size:12px;color:var(--text-dim);margin-bottom:8px"><i class="fas fa-edit"></i> 点击单元格编辑内容</div>';
    insertHTMLAtCursor(html);
  };

  // 高亮重点
  byId('tbHighlight').onclick = function(){
    var color=byId('tbColor').value;
    var sel=window.getSelection();
    if(sel&&sel.toString().trim()){
      insertSpanAtCursor(sel.toString(),'color:'+color+';font-weight:600');
    }else{
      insertSpanAtCursor('重点文字','color:'+color+';font-weight:600');
    }
  };

  // 文字颜色
  byId('tbColor').onchange = function(){
    var sel=window.getSelection();
    if(sel&&sel.toString().trim()){
      insertSpanAtCursor(sel.toString(),'color:'+this.value);
    }else{
      insertSpanAtCursor('有色文字','color:'+this.value);
    }
  };

  // 清除格式 — 简单处理：用纯文本替换选择区域
  byId('tbClear').onclick = function(){
    var sel=window.getSelection();
    if(sel&&sel.toString().trim()){
      insertSpanAtCursor(sel.toString(),'');
    }else{
      toast('先选中要清除格式的文字');
    }
  };

  // ---- 样式组合输入面板 ----
  // ---- 样式组合面板 ----
  // 用简单直接的方案：点击按钮切换 active 类，颜色选择器直接记录
  var STYLE_CFG={};
  byId('stBold').onclick=function(){
    this.classList.toggle('active');
    STYLE_CFG.bold=this.classList.contains('active')?'font-weight:700':null;
  };
  byId('stItalic').onclick=function(){
    this.classList.toggle('active');
    STYLE_CFG.italic=this.classList.contains('active')?'font-style:italic':null;
  };
  byId('stUnderline').onclick=function(){
    this.classList.toggle('active');
    STYLE_CFG.uline=this.classList.contains('active')?'text-decoration:underline':null;
  };
  byId('stColor').oninput=function(){
    STYLE_CFG.color='color:'+this.value;
  };
  byId('stInsertBtn').onclick=function(){
    var input=byId('styleTextInput');
    var text=input.value.trim();
    if(!text){toast('请先输入文字内容');return}
    var styles=[];
    for(var k in STYLE_CFG){if(STYLE_CFG[k])styles.push(STYLE_CFG[k])}
    var styleStr=styles.join(';');
    var el=document.createElement('span');
    if(styleStr)el.setAttribute('style',styleStr);
    el.textContent=text;
    var ed=byId('editorContent');ed.focus();
    var sel=window.getSelection();
    if(sel.rangeCount){
      var range=sel.getRangeAt(0);
      range.deleteContents();
      range.insertNode(el);
      range.setStartAfter(el);range.setEndAfter(el);
      sel.removeAllRanges();sel.addRange(range);
    }else{ed.appendChild(el)}
    input.value='';
    STYLE_CFG={};
    document.querySelectorAll('.st-btn').forEach(function(b){b.classList.remove('active')});
    ed.focus();
  };
  byId('styleTextInput').onkeydown=function(e){if(e.key==='Enter')byId('stInsertBtn').click()};
  // ---- Meta编辑（双击标题/摘要可编辑） ----
  byId('pubTitle').onclick = function(){
    var val = prompt('编辑标题：', this.textContent);
    if(val !== null) this.textContent = val || '公众号文章标题';
  };
  byId('pubDesc').onclick = function(){
    var val = prompt('编辑摘要：', this.textContent);
    if(val !== null) this.textContent = val || '文章摘要 / 引导语';
  };

  // ---- 编辑区变化时同步预览 ----
  ed.addEventListener('input', function(){
    var html = ed.innerHTML;
    if(html && html.trim()){
      byId('pubArticle').innerHTML = html;
    }else{
      byId('pubArticle').innerHTML = '<p style="color:var(--text-dim);text-align:center;padding:30px 0">暂无正文内容</p>';
    }
  });
}

// ---- Toast ----
function toast(m){
  var t=byId('toast');
  if(!t){
    t=document.createElement('div');t.id='toast';
    t.style.cssText='position:fixed;bottom:30px;left:50%;transform:translateX(-50%);background:#1e1e2a;color:#eee;padding:10px 20px;border-radius:8px;z-index:9999;font-size:13px;transition:opacity.3s';
    document.body.appendChild(t);
  }
  t.textContent=m;t.style.opacity='1';clearTimeout(t._t);
  t._t=setTimeout(function(){t.style.opacity='0'},2500);
}

// ---- 编码 ----
function doEncode(){
  var doc = extractDoc();
  if(!doc.body || !doc.body.trim()){
    toast('编辑器内容为空，请先写内容');
    return;
  }
  var code = encodeDoc(doc);
  byId('encodeOutput').value = code;
  byId('encodeModal').classList.add('active');
  byId('encodeOutput').select();
}

// ---- 解码 ----
function doDecode(){
  var input = byId('decodeInput').value.trim();
  if(!input){
    toast('请粘贴编码');
    return;
  }
  var doc = decodeDoc(input);
  if(!doc){
    toast('解码失败：编码无效或来源不匹配');
    return;
  }
  // 应用解码结果
  applyDoc(doc);
  // 同步编辑器内容（解码模式也把内容写回编辑器，方便继续编辑）
  var ed = byId('editorContent');
  if(doc.body && doc.body.trim()){
    ed.innerHTML = doc.body;
  }
  if(doc.meta && doc.meta.title){
    byId('pubTitle').textContent = doc.meta.title;
  }
  if(doc.meta && doc.meta.desc){
    byId('pubDesc').textContent = doc.meta.desc;
  }
  if(doc.meta && doc.meta.cover){
    byId('pubCoverImg').src = doc.meta.cover;
    byId('pubCoverImg').style.display = 'block';
    byId('pubCoverPlaceholder').style.display = 'none';
  }
  closeModal();
  toast('✅ 解码成功，已加载到编辑器');
}

// ---- Modal ----
function closeModal(){
  document.querySelectorAll('.modal-overlay').forEach(function(m){m.classList.remove('active')});
}

// ---- 复制 ----
function initCopy(){
  byId('copyEncodeBtn').onclick = function(){
    var ta = byId('encodeOutput');
    ta.select();
    try{
      document.execCommand('copy');
      toast('✅ 已复制编码');
    }catch(e){
      toast('复制失败，请手动复制');
    }
  };
}

// ---- 表单 ----
function initDecode(){
  byId('decodeSubmitBtn').onclick = doDecode;
  byId('decodeInput').onkeydown = function(e){
    if(e.key === 'Enter' && e.ctrlKey) doDecode();
  };
}

// ---- 初始化 ----
function init(){
  initToolbar();
  // initStylePanel 已内联到 initToolbar 末尾
  initCopy();
  initDecode();
  byId('encodeBtn').onclick = doEncode;
  byId('decodeBtn').onclick = function(){
    byId('decodeModal').classList.add('active');
  };
  // 关闭弹窗全局事件
  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') closeModal();
  });
}

document.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>
