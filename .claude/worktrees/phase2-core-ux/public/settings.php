<?php
require_once __DIR__ . '/../includes/community_config.php';
if (!isCommunityLoggedIn()) { communityRedirect('login.php'); }
$user = getCurrentCommunityUser();
$userId = getCurrentCommunityUserId();
$userRole = $user['role'] ?? 'user';
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.5">
<meta http-equiv="Cache-Control" content="no-cache,no-store,must-revalidate">
<title>菜籽游 ヽ(✿ﾟ▽ﾟ)ノ</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#f5f5f7;--bg2:#ffffff;--bg3:#eeedf0;--surface:rgba(255,255,255,0.7);--surface2:#e8e8ed;--accent:#007aff;--accent-dim:rgba(0,122,255,0.12);--text:#1c1c1e;--text-dim:#8e8e93;--text-bright:#000;--danger:#ff3b30;--radius:10px;--radius-lg:16px;--font:-apple-system,BlinkMacSystemFont,'SF Pro','Helvetica Neue',system-ui,sans-serif;--shadow:0 2px 12px rgba(0,0,0,0.06);--shadow-lg:0 8px 30px rgba(0,0,0,0.1)}
html,body{height:100%;background:var(--bg);color:var(--text);font:15px/1.6 var(--font);-webkit-font-smoothing:antialiased}
a{color:var(--accent);text-decoration:none}
::-webkit-scrollbar{width:6px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:var(--surface2);border-radius:3px}
#app{display:flex;flex-direction:column;min-height:100vh}
.topbar{display:flex;align-items:center;height:52px;flex-shrink:0;background:rgba(255,255,255,0.8);-webkit-backdrop-filter:blur(20px);backdrop-filter:blur(20px);border-bottom:1px solid rgba(0,0,0,0.06);padding:0 16px;z-index:30;position:sticky;top:0}
.topbar-left{display:flex;align-items:center;margin-right:24px;flex-shrink:0}
.topbar-logo{font-weight:700;font-size:17px;color:var(--text-bright);letter-spacing:-0.3px}
.topbar-nav{display:flex;align-items:center;gap:2px;flex:1}
.topbar-item{display:flex;align-items:center;gap:6px;padding:8px 14px;cursor:pointer;transition:.2s;color:var(--text-dim);font-size:13px;font-weight:500;border-radius:6px;text-decoration:none}
.topbar-item i{font-size:15px}
.topbar-item:hover{color:var(--text);background:rgba(0,0,0,0.04)}
.topbar-item.active{color:var(--accent);background:var(--accent-dim)}
.topbar-item span{white-space:nowrap}
.topbar-right{display:flex;align-items:center;position:relative;margin-left:16px;flex-shrink:0}
.topbar-avatar{cursor:pointer;border-radius:50%;border:2px solid transparent;transition:.2s;display:flex}
.topbar-avatar:hover{border-color:var(--accent-dim)}
.topbar-dropdown{position:absolute;top:calc(100% + 8px);right:0;background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.12);border:1px solid rgba(0,0,0,0.06);min-width:140px;display:none;z-index:50;overflow:hidden}
.topbar-dropdown.open{display:block}
.dropdown-item{display:flex;align-items:center;gap:8px;padding:10px 16px;cursor:pointer;transition:.1s;font-size:13px;color:var(--text)}
.dropdown-item:hover{background:var(--accent-dim)}
.content-area{flex:1;padding:0;background:var(--bg)}
.settings-page{max-width:640px;margin:0 auto;padding:32px 20px}
.settings-page h2{font-size:22px;font-weight:700;color:var(--text-bright);margin-bottom:24px;display:flex;align-items:center;gap:8px}
.settings-section{margin-bottom:32px}
.settings-section-title{font-size:14px;font-weight:600;color:var(--text-dim);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:12px;display:flex;align-items:center;gap:8px}
.settings-card{background:#fff;border-radius:14px;border:1px solid rgba(0,0,0,0.04);padding:4px 0;box-shadow:0 1px 4px rgba(0,0,0,0.04)}
.settings-row{display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid rgba(0,0,0,0.04)}
.settings-row:last-child{border-bottom:none}
.settings-row.row-col{flex-direction:column;align-items:stretch;gap:8px}
.settings-label{font-size:14px;color:var(--text-dim);flex-shrink:0;width:88px}
.settings-value{font-size:14px;color:var(--text);display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.settings-input{border:1px solid rgba(0,0,0,0.08);border-radius:8px;padding:8px 12px;font-size:14px;outline:none;transition:.2s;background:var(--bg);color:var(--text);width:100%}
.settings-input:focus{border-color:var(--accent)}
.settings-avatar{width:48px;height:48px;border-radius:50%;background:var(--accent-dim);display:flex;align-items:center;justify-content:center;color:var(--accent);font-size:20px;flex-shrink:0}
.settings-id{font-size:13px;color:var(--text-dim)}
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border:none;border-radius:8px;cursor:pointer;transition:.15s;font-size:14px;background:var(--bg3);color:var(--text)}
.btn:hover{opacity:.85}
.btn-accent{background:var(--accent);color:#fff}
.btn-sm{padding:6px 14px;font-size:13px}
.log-table{width:100%;font-size:13px}
.log-table th{text-align:left;padding:10px 16px;color:var(--text-dim);font-weight:500;border-bottom:1px solid rgba(0,0,0,0.04)}
.log-table td{padding:10px 16px;border-bottom:1px solid rgba(0,0,0,0.03);color:var(--text)}
.log-table tr:last-child td{border-bottom:none}
.log-row{display:flex;gap:12px;padding:12px 16px;font-size:13px;border-bottom:1px solid rgba(0,0,0,0.03)}
.log-row:last-child{border-bottom:none}
.log-date{color:var(--text-dim);white-space:nowrap;flex-shrink:0;width:150px}
.log-action{color:var(--accent);flex-shrink:0;width:80px;font-weight:500}
.log-detail{color:var(--text)}
.log-header{display:flex;gap:12px;padding:10px 16px;font-size:12px;color:var(--text-dim);font-weight:500;border-bottom:1px solid rgba(0,0,0,0.04)}
.log-header span{flex-shrink:0}
.log-header .lh-date{width:150px}
.log-header .lh-action{width:80px}
.log-tabs{display:flex;gap:0;padding:8px 12px;border-bottom:1px solid rgba(0,0,0,0.04);overflow-x:auto;-webkit-overflow-scrolling:touch}
.log-tab{padding:6px 14px;font-size:13px;cursor:pointer;border-radius:8px;transition:.15s;color:var(--text-dim);white-space:nowrap;font-weight:500}
.log-tab:hover{color:var(--text);background:rgba(0,0,0,0.04)}
.log-tab.active{color:var(--accent);background:var(--accent-dim)}
.log-empty{text-align:center;padding:40px 16px;color:var(--text-dim);font-size:13px}
.log-empty i{font-size:24px;display:block;margin-bottom:8px;color:var(--surface2)}
.success-msg{color:#34c759;font-size:13px;padding:4px 0;display:none}
.error-msg{color:var(--danger);font-size:13px;padding:4px 0;display:none}
.pwd-btn-row{display:flex;justify-content:flex-end;padding:8px 16px}
.avatar-upload-area{display:flex;gap:12px;align-items:flex-start;flex:1}
.avatar-preview-wrap{position:relative;width:96px;height:96px;flex-shrink:0}
.avatar-preview-wrap .settings-avatar{width:96px;height:96px;font-size:32px;border-radius:12px}
.avatar-preview-wrap img{width:96px;height:96px;border-radius:12px;object-fit:cover}
</style>
</head>
<body>
<div id="app">
  <div class="topbar">
    <div class="topbar-left">
      <a href="/index_app.php" class="topbar-logo"><i class="fas fa-leaf"></i> 纵流</a>
    </div>
    <div class="topbar-nav" style="flex:1"></div>
    <div class="topbar-right">
      <div class="topbar-avatar" id="topbarAvatar">
        <img src="<?=htmlspecialchars($user['avatar_url'] ?? '/assets/images/default-avatar.png')?>" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" style="width:32px;height:32px;border-radius:50%;object-fit:cover">
        <div style="display:none;width:32px;height:32px;border-radius:50%;background:var(--accent-dim);align-items:center;justify-content:center"><i class="fas fa-user" style="color:var(--accent);font-size:14px"></i></div>
      </div>
      <div class="topbar-dropdown" id="topbarDropdown">
        <div class="dropdown-item" onclick="location.href='/settings.php'"><i class="fas fa-cog"></i> 设置</div>
        <div class="dropdown-item" onclick="logout()"><i class="fas fa-sign-out-alt"></i> 退出登录</div>
      </div>
    </div>
  </div>
  <div class="content-area">
    <div class="settings-page">
      <h2><i class="fas fa-cog" style="color:var(--accent)"></i> 设置</h2>

      <!-- 基本资料 -->
      <div class="settings-section">
        <div class="settings-section-title"><i class="fas fa-user-circle"></i> 基本资料</div>
        <div class="settings-card" id="profileContent">
          <div class="settings-row">
            <span class="settings-label">头像</span>
            <div class="settings-value">
              <div class="avatar-upload-area">
                <div class="avatar-preview-wrap" id="avatarPreviewWrap">
                  <div class="settings-avatar" id="avatarPreview"><i class="fas fa-user"></i></div>

                </div>
                <div style="flex:1;display:flex;flex-direction:column;gap:6px">
                  <input type="text" class="settings-input" id="avatarUrl" placeholder="头像URL..." value="<?=htmlspecialchars($user['avatar_url'] ?? '/assets/images/default-avatar.png')?>">
                  <div style="display:flex;gap:6px">
                    <label class="btn btn-sm" style="cursor:pointer"><i class="fas fa-upload"></i> 上传 <input type="file" id="avatarFileInput" accept="image/*" style="display:none"></label>
                    </div>
                </div>
              </div>
            </div>
          </div>
          <div class="settings-row">
            <span class="settings-label">昵称</span>
            <input type="text" class="settings-input" id="nickname" placeholder="输入昵称" style="max-width:300px">
          </div>
          <div class="settings-row">
            <span class="settings-label">ID</span>
            <span class="settings-value"><span id="userIdDisplay">#<?=$userId?></span> <span class="settings-id" id="usernameDisplay"></span></span>
          </div>
          <div class="settings-row" style="border-bottom:none;justify-content:flex-end;padding-top:4px">
            <button class="btn btn-accent btn-sm" id="saveProfileBtn"><i class="fas fa-check"></i> 保存</button>
            <span class="success-msg" id="profileSuccess">✓ 已保存</span>
            <span class="error-msg" id="profileError"></span>
          </div>
        </div>
      </div>

      <!-- 账号令牌 -->
      <div class="settings-section">
        <div class="settings-section-title"><i class="fas fa-key"></i> 账号令牌</div>
        <div class="settings-card">
          <div class="settings-row" style="flex-wrap:wrap">
            <span class="settings-label" style="width:100%;margin-bottom:4px">当前密码</span>
            <input type="password" class="settings-input" id="curPwd" placeholder="输入当前密码" style="max-width:300px">
          </div>
          <div class="settings-row" style="flex-wrap:wrap;border-bottom:none">
            <span class="settings-label" style="width:100%;margin-bottom:4px">新密码</span>
            <input type="password" class="settings-input" id="newPwd" placeholder="至少6位" style="max-width:300px">
            <input type="password" class="settings-input" id="cfmPwd" placeholder="再次输入" style="max-width:300px">
          </div>
          <div class="pwd-btn-row" style="justify-content:space-between;align-items:center">
            <span class="success-msg" id="pwdSuccess">✓ 密码已更改</span>
            <span class="error-msg" id="pwdError"></span>
            <button class="btn btn-accent btn-sm" id="changePwdBtn"><i class="fas fa-save"></i> 更改密码</button>
          </div>
        </div>
      </div>

      <!-- 账号日志 -->
      <div class="settings-section">
        <div class="settings-section-title"><i class="fas fa-history"></i> 账户日志 <span style="font-size:12px;color:var(--text-dim);font-weight:400;text-transform:none">（文件的日志存放于服务器）</span></div>
        <div class="settings-card" id="logContent">
          <div class="log-tabs" id="logTabs">
            <div class="log-tab active" data-cat="">全部</div>
            <div class="log-tab" data-cat="recharge">充值</div>
            <div class="log-tab" data-cat="purchase">购买</div>
            <div class="log-tab" data-cat="balance">账户变更</div>
            <div class="log-tab" data-cat="operation">操作记录</div>
          </div>
          <div id="logStats" style="padding:8px 16px;font-size:12px;color:var(--text-dim);border-bottom:1px solid rgba(0,0,0,0.04);display:none"></div>
          <div class="log-header" id="logHeader" style="display:none">
            <span class="lh-date">时间</span>
            <span class="lh-action">动作</span>
            <span style="flex:1">详情</span>
          </div>
          <div id="logList"><div class="log-row"><span style="color:var(--text-dim)">加载中...</span></div></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
var USER_ID=<?=$userId ?? 'null'?>;
var USER_ROLE='<?=$userRole ?? 'user'?>';

// ===== 工具函数 =====
function byId(id){return document.getElementById(id)}
function esc(s){var d=document.createElement('div');d.appendChild(document.createTextNode(String(s)));return d.innerHTML}
function toast(m){
  var t=byId('toast');if(!t){t=document.createElement('div');t.id='toast';t.style.cssText='position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#1c1c1e;color:#eee;padding:10px 20px;border-radius:10px;z-index:99999;font-size:13px;transition:opacity.3s';document.body.appendChild(t)}
  t.textContent=m;t.style.opacity='1';clearTimeout(t._t);t._t=setTimeout(function(){t.style.opacity='0'},2500)
}
function logout(){fetch('/logout.php').then(function(){location.href='/login.php'}).catch(function(){location.href='/login.php'})}

// ===== 头像下拉 =====
var av=byId('topbarAvatar'),dd=byId('topbarDropdown');
if(av)av.onclick=function(e){e.stopPropagation();if(dd)dd.classList.toggle('open');if(av)av.classList.toggle('open')};
document.addEventListener('click',function(e){
  if(dd&&!dd.contains(e.target)&&!av.contains(e.target)){dd.classList.remove('open');av.classList.remove('open')}
});

// ===== 加载资料 =====
fetch('/api/user/profile?user_id='+USER_ID).then(function(r){return r.json()}).then(function(d){
  if(!d.success||!d.user)return;
  var u=d.user;
  if(byId('nickname'))byId('nickname').value=u.nickname||u.username||'';
  if(byId('avatarUrl'))byId('avatarUrl').value=u.avatar_url||'';
  if(byId('usernameDisplay'))byId('usernameDisplay').textContent=u.username;
  if(byId('userIdDisplay'))byId('userIdDisplay').textContent='#'+USER_ID;
}).catch(function(){});

// 同步头像预览
function setAvatarPreview(imgUrl){
  var v=byId('avatarPreview');
  if(!v)return;
  v.innerHTML=imgUrl?'<img src="'+esc(imgUrl)+'" onerror="this.style.display=\'none\';this.innerHTML=\'<i class=\\\'fas fa-user\\\'></i>\'" style="width:96px;height:96px;border-radius:12px;object-fit:cover">':'<i class="fas fa-user"></i>';
  if(byId('avatarUrl'))byId('avatarUrl').value=imgUrl||'';
}
var avUrl=byId('avatarUrl');
if(avUrl)avUrl.oninput=function(){setAvatarPreview(this.value.trim())};

// 头像上传（直接上传原图）
var fileInput=byId('avatarFileInput');
if(fileInput)fileInput.onchange=function(){
  var file=this.files[0];if(!file)return;
  var reader=new FileReader();
  reader.onload=function(e){
    var dataUrl=e.target.result;
    var v=byId('avatarPreview');
    if(v)v.innerHTML='<img src="'+dataUrl+'" style="width:96px;height:96px;border-radius:12px;object-fit:contain">';
    fetch('/api/upload/avatar',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({user_id:USER_ID,image:dataUrl})}).then(function(r){return r.json()}).then(function(d){
      if(d.success&&d.url){setAvatarPreview(d.url);if(byId('avatarUrl'))byId('avatarUrl').value=d.url;byId('saveProfileBtn').click();toast('头像已更新')}
      else toast(d.error||'上传失败')
    }).catch(function(){toast('上传失败')});
  };
  reader.readAsDataURL(file);
};

// ===== 保存资料 =====
byId('saveProfileBtn').onclick=function(){
  var ni=byId('nickname'),ai=byId('avatarUrl');
  if(!ni)return;
  if(/^\d+$/.test(ni.value)){byId('profileError').textContent='昵称不能为纯数字';byId('profileError').style.display='inline';byId('profileSuccess').style.display='none';return}
  fetch('/api/user/update-profile',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({user_id:USER_ID,nickname:ni.value,avatar:ai?ai.value:''})
  }).then(function(r){return r.json()}).then(function(d){
    if(d.success){
      byId('profileSuccess').style.display='inline';
      byId('profileError').style.display='none';
      setTimeout(function(){byId('profileSuccess').style.display='none'},2000);
    }else{
      byId('profileError').textContent='保存失败: '+(d.message||d.error||'');
      byId('profileError').style.display='inline';
      byId('profileSuccess').style.display='none';
    }
  }).catch(function(){
    byId('profileError').textContent='网络错误';
    byId('profileError').style.display='inline';
    byId('profileSuccess').style.display='none';
  });
};

// ===== 更改密码 =====
byId('changePwdBtn').onclick=function(){
  var cur=byId('curPwd'),nw=byId('newPwd'),cf=byId('cfmPwd');
  if(!cur||!nw||!cf)return;
  byId('pwdError').style.display='none';byId('pwdSuccess').style.display='none';
  if(nw.value.length<6){byId('pwdError').textContent='新密码至少6位';byId('pwdError').style.display='inline';return}
  if(nw.value!==cf.value){byId('pwdError').textContent='两次新密码不一致';byId('pwdError').style.display='inline';return}
  fetch('/api/user/change-password',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({user_id:USER_ID,current_password:cur.value,new_password:nw.value})
  }).then(function(r){return r.json()}).then(function(d){
    if(d.success){
      byId('pwdSuccess').style.display='inline';
      cur.value='';nw.value='';cf.value='';
      setTimeout(function(){byId('pwdSuccess').style.display='none'},2000);
    }else{
      byId('pwdError').textContent=d.error||d.message||'更改失败';
      byId('pwdError').style.display='inline';
    }
  }).catch(function(){
    byId('pwdError').textContent='网络错误';
    byId('pwdError').style.display='inline';
  });
};

// ===== 账户日志（带分类标签） =====
var LOG_CATEGORIES = {'recharge':'充值记录','purchase':'购买记录','balance':'账户变更记录','operation':'操作记录','':'全部日志'};
var LOG_LABELS = {'recharge':'事件','purchase':'购买项','balance':'变更项','operation':'操作'};
var activeCat = '';

function renderLogs(logs, cat){
  var el=byId('logList');if(!el)return;
  if(!logs||!logs.length){
    var icon=cat==='recharge'?'fa-credit-card':cat==='purchase'?'fa-shopping-cart':cat==='balance'?'fa-scale-balanced':cat==='operation'?'fa-screwdriver-wrench':'fa-inbox';
    el.innerHTML='<div class="log-empty"><i class="fas '+icon+'"></i>暂无'+LOG_CATEGORIES[cat]+'</div>';return
  }
  el.innerHTML=logs.map(function(l){
    return '<div class="log-row"><span class="log-date">'+esc(l.time||'')+'</span><span class="log-action">'+esc(l.action||'')+'</span><span class="log-detail">'+esc(l.detail||'')+'</span></div>';
  }).join('');
}

function loadLogs(cat){
  activeCat=cat||'';
  var url='/api/user/logs?user_id='+USER_ID+'&limit=200'+(cat?'&category='+cat:'');
  var el=byId('logList');if(el)el.innerHTML='<div class="log-row"><span style="color:var(--text-dim)">加载中...</span></div>';
  fetch(url).then(function(r){return r.json()}).then(function(d){
    if(activeCat!==(cat||''))return; // 防止异步错乱
    var header=byId('logHeader');
    if(header)header.style.display=cat?'':'none';
    if(!d.success){renderLogs([],cat);return}
    renderLogs(d.logs||[],cat);
  }).catch(function(){
    if(activeCat!==(cat||''))return;
    renderLogs([],cat);
  });
}

// 分类标签点击
var tabs=byId('logTabs');
if(tabs){
  tabs.addEventListener('click',function(e){
    var tab=e.target.closest('.log-tab');
    if(!tab)return;
    tabs.querySelectorAll('.log-tab').forEach(function(t){t.classList.remove('active')});
    tab.classList.add('active');
    loadLogs(tab.dataset.cat);
  });
}

// 加载统计
fetch('/api/user/logs/stats?user_id='+USER_ID).then(function(r){return r.json()}).then(function(d){
  if(!d.success||!d.stats)return;
  var parts=[];
  for(var k in d.stats){
    if(k==='')continue;
    parts.push((d.categories&&d.categories[k]?d.categories[k]:k)+': '+d.stats[k]+'条');
  }
  if(parts.length){
    var st=byId('logStats');
    if(st){st.style.display='block';st.textContent='📊 '+parts.join(' | ');}
  }
}).catch(function(){});

// 初始加载全部
loadLogs('');

// ===== 管理员导航项显示 =====
if(USER_ROLE!=='admin'){
  var ad=byId('adminNavItem');if(ad)ad.style.display='none';
}
</script>
</body>
</html>
