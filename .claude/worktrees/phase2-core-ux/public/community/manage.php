<?php
require_once __DIR__ . '/../../includes/community_config.php';
$loggedIn = isCommunityLoggedIn();
$user = $loggedIn ? getCurrentCommunityUser() : null;
$userId = $user ? ($user['id'] ?? null) : null;
if (!$loggedIn || !$userId) {
  header('Location: /login.php');
  echo '<p>请先登录</p>'; exit;
}
$communityId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$communityId) { header('Location: /'); exit; }
$resp = @file_get_contents('http://127.0.0.1:5000/api/community/detail?id=' . $communityId);
$data = json_decode($resp, true);
$community = $data['community'] ?? null;
if (!$community) { echo '<p>团不存在</p>'; exit; }
if ($community['status'] !== 'approved' && $userId != $community['creator_id']) {
  // 未审核通过只能创建者看
  $role = ($user['role'] ?? '');
  if ($role !== 'admin') {
    echo '<p>团正在审核中...</p>'; exit;
  }
}
$isCreator = ($userId == $community['creator_id']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?=htmlspecialchars($community['name'])?> - 团管理 - 菜籽游</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#f5f5f7;--bg2:#fff;--surface2:#e8e8ed;--accent:#007aff;--accent-dim:rgba(0,122,255,0.1);--danger:#ff3b30;--text:#1c1c1e;--text-dim:#8e8e93;--text-bright:#000;--radius:10px;--radius-lg:16px;--shadow:0 2px 12px rgba(0,0,0,0.06);--font:-apple-system,BlinkMacSystemFont,'SF Pro','Helvetica Neue',system-ui,sans-serif}
body{background:var(--bg);color:var(--text);font:14px/1.6 var(--font);-webkit-font-smoothing:antialiased;padding:20px;max-width:800px;margin:0 auto}
a{color:var(--accent);text-decoration:none}
.back{display:inline-flex;align-items:center;gap:6px;font-size:14px;color:var(--accent);margin-bottom:12px}
h1{font-size:20px;font-weight:700;margin-bottom:4px}
.card{background:var(--bg2);border-radius:var(--radius-lg);padding:16px;box-shadow:var(--shadow);margin-bottom:16px}
.card-title{font-size:15px;font-weight:600;margin-bottom:10px;display:flex;align-items:center;gap:6px}
.btn{display:inline-flex;align-items:center;gap:4px;padding:8px 16px;border-radius:8px;border:none;font-size:13px;cursor:pointer;background:var(--surface2);color:var(--text);transition:.15s}
.btn-accent{background:var(--accent);color:#fff}
.btn-danger{background:var(--danger);color:#fff}
.btn-sm{font-size:11px;padding:4px 10px}
.modal-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.4);z-index:9999;display:none;align-items:center;justify-content:center}
.modal-overlay.active{display:flex}
.modal-box{background:#fff;border-radius:var(--radius-lg);padding:24px;max-width:480px;width:90vw;max-height:90vh;overflow-y:auto;box-shadow:0 8px 40px rgba(0,0,0,0.15)}
.modal-box h3{font-size:17px;margin-bottom:14px}
.form-group{margin-bottom:12px}
.form-group label{display:block;font-size:12px;color:var(--text-dim);margin-bottom:4px;font-weight:500}
.toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#1e1e2a;color:#eee;padding:10px 20px;border-radius:8px;z-index:99999;font-size:13px}
.member-row{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--surface2)}
.member-row:last-child{border:none}
.member-avatar{width:34px;height:34px;border-radius:50%;overflow:hidden;flex-shrink:0;background:var(--surface2);display:flex;align-items:center;justify-content:center;color:var(--text-dim);cursor:pointer}
.member-avatar img{width:100%;height:100%;object-fit:cover}
.member-name{flex:1;font-size:13px;font-weight:500}
.member-role-tag{font-size:11px;color:var(--accent);background:var(--accent-dim);padding:2px 8px;border-radius:8px}
.post-item{padding:10px 0;border-bottom:1px solid var(--surface2)}
.post-item:last-child{border:none}
.post-item a{color:var(--text-bright);font-weight:500}
.post-item .meta{font-size:11px;color:var(--text-dim);margin-top:3px}

/* 名片卡片 */
.profile-card{background:#fff;border-radius:20px;overflow:hidden;width:380px;max-width:90vw;box-shadow:0 8px 40px rgba(0,0,0,0.2)}
.pc-cover{height:120px;background:linear-gradient(135deg,#667eea,#764ba2);position:relative}
.pc-avatar{position:absolute;left:50%;bottom:-36px;transform:translateX(-50%);width:72px;height:72px;border-radius:50%;border:3px solid #fff;background:#fff;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1)}
.pc-body{padding:48px 20px 20px;text-align:center}
.pc-nick{font-size:17px;font-weight:700;color:var(--text-bright)}
.pc-name{font-size:12px;color:var(--text-dim);margin-top:2px}
.pc-id{font-size:11px;color:var(--text-dim);margin-top:2px}
.pc-bio{font-size:13px;color:#3a3a3c;margin-top:12px;padding:0 4px;line-height:1.5}
</style>
</head>
<body>

<a class="back" href="/"><i class="fas fa-arrow-left"></i> 返回首页</a>
<h1><i class="fas fa-users"></i> <?=htmlspecialchars($community['name'])?></h1>
<div style="font-size:12px;color:var(--text-dim);margin-bottom:16px">ID: #<?=intval($community['id'])?></div>

<!-- 成员 -->
<div class="card">
  <div class="card-title"><i class="fas fa-users"></i> 成员</div>
  <div id="memberListEl">加载中...</div>
</div>

<!-- 动态 -->
<div class="card">
  <div class="card-title"><i class="fas fa-newspaper"></i> 动态 <span id="postCountEl"></span></div>
  <div id="postListEl" style="margin-bottom:10px"></div>
  <button class="btn btn-accent btn-sm" onclick="openModal()"><i class="fas fa-plus"></i> 发布</button>
</div>

<!-- 基本设置 -->
<div class="card">
  <div class="card-title"><i class="fas fa-cog"></i> 基本设置</div>
  <div class="form-group"><label>团名称</label><input id="sName" value="<?=htmlspecialchars($community['name'])?>" style="width:100%;padding:9px 10px;border:1px solid var(--surface2);border-radius:var(--radius);font-size:14px"></div>
  <div class="form-group"><label>简介</label><textarea id="sDesc" rows="2" style="width:100%;padding:9px 10px;border:1px solid var(--surface2);border-radius:var(--radius);font-size:14px;font-family:var(--font)"><?=htmlspecialchars($community['description']??'')?></textarea></div>
  <div class="form-group"><label>分类</label><select id="sCat" style="width:100%;padding:9px 10px;border:1px solid var(--surface2);border-radius:var(--radius);font-size:14px">
    <?php foreach(['游戏','技术','学习','生活','其他'] as $c): ?>
    <option value="<?=$c?>" <?=($community['category']??'')==$c?'selected':''?>><?=$c?></option>
    <?php endforeach; ?>
  </select></div>
  <button class="btn btn-accent btn-sm" onclick="saveSetting()">保存设置</button>
</div>

<!-- 加入方式 -->
<div class="card">
  <div class="card-title"><i class="fas fa-door-open"></i> 加入方式</div>
  <div class="form-group">
    <select id="sJoinType" style="width:100%;padding:9px 10px;border:1px solid var(--surface2);border-radius:var(--radius);font-size:14px">
      <option value="auto" <?=($community['join_type']??'approve')=='auto'?'selected':''?>>无需审核，直接加入</option>
      <option value="approve" <?=($community['join_type']??'approve')=='approve'?'selected':''?>>需要审核（团长批准）</option>
    </select>
  </div>
  <button class="btn btn-accent btn-sm" onclick="saveJoinType()">保存</button>
</div>

<!-- 动态权限 -->
<div class="card">
  <div class="card-title"><i class="fas fa-newspaper"></i> 动态发布权限</div>
  <div class="form-group">
    <select id="sPostType" style="width:100%;padding:9px 10px;border:1px solid var(--surface2);border-radius:var(--radius);font-size:14px">
      <option value="all" <?=($community['post_type']??'all')=='all'?'selected':''?>>任何人均可发布</option>
      <option value="admin" <?=($community['post_type']??'all')=='admin'?'selected':''?>>仅团长和管理员可发布</option>
    </select>
  </div>
  <button class="btn btn-accent btn-sm" onclick="savePostType()">保存</button>
</div>

<!-- 入团申请审核 -->
<div class="card" id="pendingRequestsCard" style="display:none">
  <div class="card-title"><i class="fas fa-clock"></i> 待审核的入团申请</div>
  <div id="pendingRequestList" style="font-size:13px"></div>
</div>

<!-- 官网 -->
<div class="card">
  <div class="card-title"><i class="fas fa-link"></i> 官网 <span style="font-size:11px;color:var(--text-dim);font-weight:400">（选填）</span></div>
  <div class="form-group"><input id="sUrl" value="<?=htmlspecialchars($community['site_url']??'')?>" placeholder="https://..." style="width:100%;padding:9px 10px;border:1px solid var(--surface2);border-radius:var(--radius);font-size:14px"></div>
  <button class="btn btn-accent btn-sm" onclick="saveUrl()">保存</button>
</div>

<?php if ($isCreator): ?>
<!-- 解散 -->
<div class="card" style="border:1px solid rgba(255,60,50,0.3)">
  <div class="card-title" style="color:var(--danger)"><i class="fas fa-trash-alt"></i> 危险操作</div>
  <p style="font-size:12px;color:var(--text-dim);margin-bottom:10px">解散后将删除所有数据且不可恢复</p>
  <button class="btn btn-danger btn-sm" onclick="dissolve()"><i class="fas fa-skull"></i> 解散团</button>
</div>
<?php endif; ?>

<!-- 发布弹窗 -->
<div class="modal-overlay" id="modalOverlay" onclick="if(event.target===this)this.classList.remove('active')">
  <div class="modal-box">
    <h3><i class="fas fa-edit"></i> 发布动态</h3>
    <div class="form-group"><label>标题 *</label><input id="fTitle" style="width:100%;padding:9px 10px;border:1px solid var(--surface2);border-radius:var(--radius);font-size:14px"></div>
    <div class="form-group"><label>简介</label><textarea id="fDesc" rows="2" style="width:100%;padding:9px 10px;border:1px solid var(--surface2);border-radius:var(--radius);font-size:14px;resize:vertical;font-family:var(--font)"></textarea></div>
    <div class="form-group"><label>封面</label><input type="file" id="fCover" accept="image/*" style="font-size:12px"></div>
    <div class="form-group"><label>类型</label><select id="fType" onchange="toggleType()" style="width:100%;padding:9px 10px;border:1px solid var(--surface2);border-radius:var(--radius);font-size:14px"><option value="image">图片（最多9张）</option><option value="video">视频</option><option value="document">文本文档</option></select></div>
    <div class="form-group" id="gVideo" style="display:none"><label>视频文件</label><input type="file" id="fVideo" accept="video/*" style="font-size:12px"><input id="fVideoUrl" placeholder="或粘贴视频直链..." style="width:100%;margin-top:6px;padding:8px 10px;border:1px solid var(--surface2);border-radius:var(--radius);font-size:13px"></div>
    <div class="form-group" id="gImage"><label>图片（可多选）</label><input type="file" id="fImage" accept="image/*" multiple style="font-size:12px"><div id="imgPreview" style="display:flex;flex-wrap:wrap;gap:4px;margin-top:6px"></div></div>
    <div class="form-group" id="gDoc" style="display:none"><label>文本文档 (.txt)</label><input type="file" id="fDoc" accept=".txt,text/plain" style="font-size:12px"><div style="font-size:11px;color:var(--text-dim);margin-top:4px">仅支持纯文本 .txt 文件</div></div>
    <div id="fMsg" style="font-size:12px;color:var(--danger);margin-bottom:6px"></div>
    <div style="display:flex;gap:8px;justify-content:flex-end">
      <button class="btn" onclick="document.getElementById('modalOverlay').classList.remove('active')">取消</button>
      <button class="btn btn-accent" id="btnSubmit" onclick="submitPost()">发布</button>
    </div>
  </div>
</div>

<!-- 上传进度浮窗 -->
<div id="uploadProgress" style="display:none;position:fixed;bottom:24px;right:24px;z-index:99999;background:#1e1e2a;border-radius:16px;padding:6px;box-shadow:0 4px 20px rgba(0,0,0,0.3);width:60px;height:60px;align-items:center;justify-content:center;">
  <svg viewBox="0 0 36 36" style="width:48px;height:48px;transform:rotate(-90deg)">
    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#333" stroke-width="3" stroke-linecap="round"/>
    <path id="uploadArc" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#00a1d6" stroke-width="3" stroke-linecap="round" stroke-dasharray="0,100"/>
  </svg>
  <div id="uploadPct" style="position:absolute;font-size:11px;color:#eee;font-weight:600">0%</div>
</div>

<script>
var CID=<?=$communityId?>,UID=<?=$userId?>,IS_CREATOR=<?=$isCreator?'true':'false'?>;

function g(id){return document.getElementById(id)}

// 成员
function loadMembers(){
  fetch('/api/community/members?community_id='+CID).then(function(r){return r.json()}).then(function(d){
    var el=g('memberListEl');
    if(!d.success||!d.members){el.innerHTML='加载失败';return}
    if(!d.members.length){el.innerHTML='暂无成员';return}
    el.innerHTML=d.members.map(function(m){
      var r=m.role=='creator'?'创建者':(m.role=='admin'?'管理':'成员');
      var n=m.username||'#'+m.user_id;
      var av=m.avatar_url&&m.avatar_url!='/assets/images/default-avatar.png'?'<img src="'+m.avatar_url+'">':'<i class="fas fa-user" style="font-size:14px"></i>';
      var extra='';
      if(IS_CREATOR&&m.user_id!=UID){
        if(m.role=='admin'){
          extra='<button class="btn btn-sm" style="margin-left:4px" onclick="transferOwner('+m.user_id+')">转让</button><button class="btn btn-sm" style="margin-left:4px;color:var(--danger)" onclick="setAdmin('+m.user_id+',\'remove\')">取消管理</button>';
        }else if(m.role=='member'){
          extra='<button class="btn btn-sm" style="margin-left:4px" onclick="setAdmin('+m.user_id+',\'set\')">设为管理</button><button class="btn btn-sm btn-danger" onclick="kickMember('+m.user_id+')" style="margin-left:4px">踢出</button>';
        }
      }
      return '<div class="member-row"><div class="member-avatar" onclick="showProf('+m.user_id+')">'+av+'</div><span class="member-name">'+n+'</span><span class="member-role-tag">'+r+'</span>'+extra+'</div>';
    }).join('');
  }).catch(function(){g('memberListEl').innerHTML='加载失败'});
}

// 动态
function loadPosts(){
  fetch('/api/community/posts?community_id='+CID).then(function(r){return r.json()}).then(function(d){
    var el=g('postListEl'),ct=g('postCountEl');
    if(!d.success){el.innerHTML='';return}
    ct.textContent='('+(d.total||0)+'条)';
    if(!d.posts.length){el.innerHTML='<div style="font-size:12px;color:var(--text-dim);padding:10px 0">暂无动态</div>';return}
    el.innerHTML=d.posts.map(function(p){
      var ic=p.content_type==='video'?'<i class="fas fa-video"></i>':(p.content_type==='document'?'<i class="fas fa-file-alt"></i>':'<i class="fas fa-image"></i>');
      return '<div class="post-item"><a href="/post.php?id='+p.id+'" target="_blank">'+esc(p.title)+'</a> '+ic+' <span style="font-size:11px;color:var(--text-dim)">♥ '+(p.like_count||0)+' 💬 '+(p.comment_count||0)+'</span><div class="meta"><span onclick="delPost('+p.id+')" style="color:var(--danger);cursor:pointer;float:right"><i class="fas fa-trash"></i></span>'+esc(p.created_at||'')+'</div></div>';
    }).join('');
  }).catch(function(){});
}

function esc(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')}

function delPost(id){
  if(!confirm('删除此动态？'))return;
  fetch('/api/community/post/delete',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({post_id:id,community_id:CID,user_id:UID})}).then(function(r){return r.json()}).then(function(d){
    if(d.success){toast('已删除');loadPosts()}else toast(d.error||'失败');
  }).catch(function(){toast('请求失败')});
}

function toast(m){var t=document.createElement('div');t.className='toast';t.textContent=m;document.body.appendChild(t);setTimeout(function(){t.remove()},2200)}

// ===== 上传进度 =====
var _uploadQueue={total:0,done:0};
function _showProgress(pct){
  var el=document.getElementById('uploadProgress');
  if(!el)return;
  el.style.display='flex';
  var arc=document.getElementById('uploadArc');
  var txt=document.getElementById('uploadPct');
  var p=Math.min(Math.round(pct),100);
  if(arc)arc.setAttribute('stroke-dasharray',(p/100*100)+',100');
  if(txt)txt.textContent=p+'%';
  if(p>=100)setTimeout(function(){el.style.display='none'},1200);
}
function _hideProgress(){
  var el=document.getElementById('uploadProgress');
  if(el)el.style.display='none';
}

loadMembers();
loadPendingRequests();
loadPosts();
// 发布
var _coverUrl='';
function openModal(){
  g('fTitle').value='';g('fDesc').value='';g('fCover').value='';g('fImage').value='';g('fVideo').value='';g('imgPreview').innerHTML='';g('fMsg').textContent='';
  _coverUrl='';g('btnSubmit').disabled=false;
  if(g('fVideoUrl'))g('fVideoUrl').value='';
  g('fType').value='image';toggleType();g('modalOverlay').classList.add('active');
}
function toggleType(){
  var t=g('fType').value;
  g('gVideo').style.display=t==='video'?'':'none';
  g('gImage').style.display=t==='image'?'':'none';
  g('gDoc').style.display=t==='document'?'':'none';
  // 视频文件大小检测
  if(t==='video'){
    var vi=g('fVideo');
    vi.onchange=function(){
      if(this.files&&this.files[0]&&this.files[0].size>500*1024*1024){
        var msg=g('fVideoUrl').parentNode.querySelector('.size-warn')||document.createElement('div');
        msg.className='size-warn';msg.style.cssText='font-size:11px;color:var(--danger);margin-top:4px';
        msg.textContent='? 文件超过500MB，建议粘贴视频链接代替';
        g('fVideoUrl').parentNode.appendChild(msg);
      }else{
        var old=g('fVideoUrl').parentNode.querySelector('.size-warn');
        if(old)old.remove();
      }
    };
  }
}
// 图片预览
g('fImage').onchange=function(){
  var pv=g('imgPreview');pv.innerHTML='';
  Array.from(this.files).slice(0,9).forEach(function(f){
    var r=new FileReader();
    r.onload=function(e){var d=document.createElement('div');d.style.cssText='width:56px;height:56px;border-radius:6px;overflow:hidden;border:1px solid #ddd';d.innerHTML='<img src="'+e.target.result+'" style="width:100%;height:100%;object-fit:cover">';pv.appendChild(d)};
    r.readAsDataURL(f);
  });
};

function submitPost(){
  var title=g('fTitle').value.trim(),desc=g('fDesc').value.trim(),type=g('fType').value;
  if(!title){g('fMsg').textContent='请输入标题';return}
  var cf=g('fCover').files[0],vf=g('fVideo').files[0],ifs=Array.from(g('fImage').files).slice(0,9);
  var videoUrl=g('fVideoUrl')?g('fVideoUrl').value.trim():'';
  if(type==='video'&&!vf&&!videoUrl){g('fMsg').textContent='请选视频或粘贴链接';return}
  if(type==='image'&&!ifs.length){g('fMsg').textContent='请选图片';return}
  if(type==='document'&&!g('fDoc').files.length){g('fMsg').textContent='请选文本文档';return}
  if(window._sub){console.log('_sub locked');window._sub=false;return;}window._sub=true;
  // 关弹窗，显示进度圈
  g('modalOverlay').classList.remove('active');
  _showProgress(0);
  console.log('submitPost start',type,vf&&vf.name,vf&&vf.size,videoUrl);
  // 重置表单
  g('fTitle').value='';g('fDesc').value='';g('fCover').value='';g('fImage').value='';g('fVideo').value='';if(g('fVideoUrl'))g('fVideoUrl').value='';g('imgPreview').innerHTML='';g('fMsg').textContent='';
  // 开始后台上传
  var up=[];var coverU='';var imgs=[];var videoU='';var docContent='';
  var total=0,jobs=0;
  if(cf)total++;if(type==='video'&&vf)total++;if(type==='image')total+=ifs.length;if(type==='document'&&g('fDoc').files.length)total++;
  function upDone(){jobs++;if(total>0)_showProgress(jobs/total*100);}
  function uploadFile(file){
    var fd=new FormData();fd.append('file',file);
    return fetch('/api/upload/post',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){upDone();if(d.success)return d.url;throw new Error(d.error||'上传失败')});
  }
  var ps=[];
  if(cf)ps.push(uploadFile(cf).then(function(u){coverU=u}).catch(function(){}));
  if(type==='video'){
    if(vf){
      // 大文件用 XHR 直接发二进制
      if(vf.size>500*1024*1024){toast('文件过大(>500MB)，建议用视频链接');window._sub=false;return}
      ps.push(new Promise(function(resolve){
        var xhr=new XMLHttpRequest();
        xhr.open('POST','/api/upload/post',true);
        xhr.setRequestHeader('X-File-Name',encodeURIComponent(vf.name));
        xhr.setRequestHeader('X-File-Size',vf.size);
        xhr.onprogress=function(e){if(e.lengthComputable)window._upPct=e.loaded/e.total;var p=jobs/total*100;_showProgress(p+(window._upPct||0)/total*100)};
        xhr.onload=function(){upDone();try{var d=JSON.parse(xhr.responseText);if(d.success)videoU=d.url}catch(e){}resolve()};
        xhr.onerror=function(){console.log('xhr error');upDone();resolve()};
        xhr.send(vf);
      }));
    } else if(videoUrl)videoU=videoUrl;
  }
  if(type==='image')ifs.forEach(function(f,i){ps.push(uploadFile(f).then(function(u){imgs[i]=u}).catch(function(){}))});
  if(type==='document'&&g('fDoc').files.length){
    var docFile=g('fDoc').files[0];
    ps.push(new Promise(function(resolve){
      var r=new FileReader();
      r.onload=function(e){docContent=e.target.result;upDone();resolve()};
      r.readAsText(docFile);
    }));
  }
  Promise.all(ps).then(function(){
    console.log('all ps done, videoU:',videoU);
    var body={community_id:CID,title:title,description:desc,content_type:type,cover_url:coverU,images:imgs.filter(function(u){return u}),content_url:videoU,user_id:UID};
    if(type==='document')body.document_content=docContent;
    fetch('/api/community/post/create',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)}).then(function(r){return r.json()}).then(function(d){
      window._sub=false;
      console.log('create result:',d);
      if(d.success){_showProgress(100);loadPosts();toast('发布成功')}else toast(d.error||'发布失败');
    }).catch(function(){window._sub=false;toast('发布失败')});
  }).catch(function(){window._sub=false;console.log('ps failed')});
}

// 名片
function showProf(uid){
  if(!uid)return;
  var mo=document.getElementById('profileModal');
  if(!mo){
    mo=document.createElement('div');mo.id='profileModal';mo.className='modal-overlay';
    mo.onclick=function(e){if(e.target===this)this.classList.remove('active')};
    document.body.appendChild(mo);
  }
  mo.classList.add('active');
  mo.innerHTML='<div class="profile-card" style="background:#fff;border-radius:20px;overflow:hidden;width:380px;max-width:90vw;box-shadow:0 8px 40px rgba(0,0,0,0.2);position:relative;z-index:10000"><div class="pc-cover" id="pcCover" style="height:120px;background:linear-gradient(135deg,#667eea,#764ba2);position:relative"><div class="pc-avatar" id="pcAvatar" style="position:absolute;left:50%;bottom:-36px;transform:translateX(-50%);width:72px;height:72px;border-radius:50%;border:3px solid #fff;background:#fff;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1)"><i class="fas fa-spinner fa-spin" style="font-size:28px;display:flex;align-items:center;justify-content:center;width:100%;height:100%;color:var(--text-dim)"></i></div></div><div class="pc-body" style="padding:48px 20px 20px;text-align:center"><div class="pc-nick" id="pcNick" style="font-size:17px;font-weight:700;color:var(--text-bright)">\u52a0\u8f7d\u4e2d...</div><div class="pc-name" id="pcName" style="font-size:12px;color:var(--text-dim);margin-top:2px"></div><div class="pc-id" id="pcId" style="font-size:11px;color:var(--text-dim);margin-top:2px"></div><div class="pc-bio" id="pcBio" style="font-size:13px;color:#3a3a3c;margin-top:12px;padding:0 4px;line-height:1.5"></div><div id="pcBtnArea"></div></div></div>';
  fetch('/api/user/profile?user_id='+uid).then(function(r){return r.json()}).then(function(d){
    if(!d.success||!d.user){var el=document.getElementById('pcNick');if(el)el.textContent='\u52a0\u8f7d\u5931\u8d25';return}
    var u=d.user;
    var el=document.getElementById('pcNick');if(el)el.textContent=u.nickname||u.username||'\u7528\u6237#'+uid;
    el=document.getElementById('pcName');if(el)el.textContent='@'+(u.username||'');
    el=document.getElementById('pcId');if(el)el.textContent='ID: '+uid;
    el=document.getElementById('pcBio');if(el)el.textContent=u.bio||'\u8fd9\u4e2a\u4eba\u5f88\u61d2\uff0c\u4ec0\u4e48\u90fd\u6ca1\u5199';
    var bg=u.profile_bg||'#667eea,#764ba2';
    var cv=document.getElementById('pcCover');if(cv)cv.style.background='linear-gradient(135deg,'+bg+')';
    var avEl=document.getElementById('pcAvatar');
    if(u.avatar_url&&u.avatar_url!='/assets/images/default-avatar.png')avEl.innerHTML='<img src="'+u.avatar_url+'" style="width:100%;height:100%;object-fit:cover;border-radius:50%">';
    else avEl.innerHTML='<i class="fas fa-user" style="font-size:28px;color:var(--text-dim);display:flex;align-items:center;justify-content:center;width:100%;height:100%"></i>';
    var ba=document.getElementById('pcBtnArea');if(ba)ba.innerHTML='';
    if(uid!=UID&&ba){
      fetch('/api/friends/check?user_id='+UID+'&target_id='+uid).then(function(r2){return r2.json()}).then(function(fd){
        if(fd.success&&fd.status==='not_friend'){
          var btn=document.createElement('button');btn.style.cssText='padding:8px 20px;border-radius:20px;font-size:13px;margin-top:10px;background:var(--accent);color:#fff;border:none;cursor:pointer';
          btn.innerHTML='<i class="fas fa-user-plus"></i> \u6dfb\u52a0\u597d\u53cb';
          btn.onclick=function(){fetch('/api/friends/apply',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({from_user_id:UID,to_user_id:uid})}).then(function(r){return r.json()}).then(function(d){if(d.success){toast('\u5df2\u53d1\u9001\u7533\u8bf7');mo.classList.remove('active')}else toast(d.error||'\u5931\u8d25')}).catch(function(){toast('\u8bf7\u6c42\u5931\u8d25')})};
          ba.appendChild(btn);
        }
      }).catch(function(){});
    }
  }).catch(function(){var el=document.getElementById('pcNick');if(el)el.textContent='\u8bf7\u6c42\u5931\u8d25'});
}


// 设置
function saveSetting(){
  var n=g('sName').value.trim(),d=g('sDesc').value.trim(),c=g('sCat').value;
  if(!n){toast('名称不能为空');return}
  fetch('/api/community/update',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({community_id:CID,user_id:UID,name:n,description:d,category:c})}).then(function(r){return r.json()}).then(function(r2){toast(r2.success?'已保存':r2.error||'失败')}).catch(function(){toast('请求失败')});
}
function saveUrl(){
  fetch('/api/community/update',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({community_id:CID,user_id:UID,site_url:g('sUrl').value.trim()})}).then(function(r){return r.json()}).then(function(r2){toast(r2.success?'已保存':r2.error||'失败')}).catch(function(){toast('请求失败')});
}
function saveJoinType(){
  fetch('/api/community/update',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({community_id:CID,user_id:UID,join_type:g('sJoinType').value})}).then(function(r){return r.json()}).then(function(r2){toast(r2.success?'已保存':r2.error||'失败')}).catch(function(){toast('请求失败')});
}
function savePostType(){
  fetch('/api/community/update',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({community_id:CID,user_id:UID,post_type:g('sPostType').value})}).then(function(r){return r.json()}).then(function(r2){toast(r2.success?'已保存':r2.error||'失败')}).catch(function(){toast('请求失败')});
}
function loadPendingRequests(){
  var card=document.getElementById('pendingRequestsCard');
  var list=document.getElementById('pendingRequestList');
  if(!card||!list)return;
  fetch('/api/community/join-requests?community_id='+CID).then(function(r){return r.json()}).then(function(d){
    if(!d.success||!d.requests||!d.requests.length){card.style.display='none';return}
    card.style.display='';
    list.innerHTML=d.requests.map(function(rq){
      var nm=rq.nickname||rq.username||'用户#'+rq.user_id;
      return '<div style="display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid var(--surface2)">'+
        '<span style="flex:1;font-size:13px">'+esc(nm)+'</span>'+
        '<button class="btn btn-sm btn-accent" onclick="approveJoin('+rq.user_id+',\'approve\')">通过</button>'+
        '<button class="btn btn-sm" style="color:var(--danger)" onclick="approveJoin('+rq.user_id+',\'reject\')">拒绝</button>'+
        '</div>';
    }).join('');
  }).catch(function(){});
}
function approveJoin(targetId,action){
  fetch('/api/community/approve-join',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({community_id:CID,user_id:UID,target_id:targetId,action:action})}).then(function(r){return r.json()}).then(function(d){
    if(d.success){toast(d.message||'操作成功');loadPendingRequests();loadMembers()}
    else toast(d.error||'操作失败');
  }).catch(function(){toast('请求失败')});
}
function transferOwner(targetId){
  if(!confirm('确定将团长转让给该管理员？'))return;
  fetch('/api/community/transfer',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({community_id:CID,user_id:UID,target_id:targetId})}).then(function(r){return r.json()}).then(function(d){if(d.success){toast('团长已转让');loadMembers();location.reload()}else toast(d.error||'操作失败')}).catch(function(){toast('请求失败')});
}
function setAdmin(targetId,action){
  var label=action==='set'?'设为管理员':'取消管理员';
  if(!confirm('确定'+label+'？'))return;
  fetch('/api/community/set-admin',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({community_id:CID,user_id:UID,target_id:targetId,action:action})}).then(function(r){return r.json()}).then(function(d){if(d.success){toast(d.message||label+'成功');loadMembers()}else toast(d.error||'操作失败')}).catch(function(){toast('请求失败')});
}
function kickMember(targetId){
  if(!confirm('确定踢出该成员？'))return;
  fetch('/api/community/kick',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({community_id:CID,user_id:UID,target_id:targetId})}).then(function(r){return r.json()}).then(function(d){if(d.success){toast('已踢出');loadMembers()}else toast(d.error||'操作失败')}).catch(function(){toast('请求失败')});
}
function dissolve(){
  if(!confirm('确定解散？不可恢复！'))return;
  if(!confirm('再次确认？'))return;
  fetch('/api/community/dissolve',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({community_id:CID,user_id:UID})}).then(function(r){return r.json()}).then(function(r2){if(r2.success){toast('已解散');setTimeout(function(){location.href='/'},1200)}else toast(r2.error||'失败')}).catch(function(){toast('请求失败')});
}

</script>
</body>
</html>
