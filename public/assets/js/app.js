/* ===== 菜籽游 App — 纯前端逻辑 ===== */
/* ===== 配置 ===== */


let CURRENT_CHAT_ID = null;
let CHAT_POLL = null;
let ADMIN_TOKEN = sessionStorage.getItem('admin_token') || '';
let JUNIUS_POLL = null;

function byId(id){return document.getElementById(id)}
function qs(s,c){return(c||document).querySelector(s)}
function qsa(s,c){return(c||document).querySelectorAll(s)}
function esc(s){var d=document.createElement('div');d.appendChild(document.createTextNode(String(s)));return d.innerHTML}
function toast(m){
  var t=byId('toast');if(!t){t=document.createElement('div');t.id='toast';t.style.cssText='position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#1e1e2a;color:#eee;padding:10px 20px;border-radius:8px;z-index:99999;font-size:13px;border:1px solid #2a2a3a;transition:opacity.3s';document.body.appendChild(t)}
  t.textContent=m;t.style.opacity='1';clearTimeout(t._t);t._t=setTimeout(function(){t.style.opacity='0'},2500)
}

/* ===== 面板切换 ===== */
function showPanel(name){
  qsa('.float-panel').forEach(function(p){p.classList.remove('active')});
  var def=byId('panelDefault');if(def)def.style.display='none';
  var p=byId('panel-'+name);if(p)p.classList.add('active');
}
function togglePanel(name){
  var p=byId('panel-'+name);if(!p)return;
  if(p.classList.contains('active')){
    p.classList.remove('active');var def=byId('panelDefault');if(def)def.style.display='';
    qsa('.sidebar-item').forEach(function(s){s.classList.remove('active')});
  }else showPanel(name);
}

/* ===== 侧边栏 ===== */
function initSidebar(){
  if(USER_ROLE!=='admin'){
    qsa('.admin-only').forEach(function(el){el.style.display='none'});
    qsa('.admin-only-mobile').forEach(function(el){el.style.display='none'});
  }
  // 顶部导航 tab 点击（宽屏）
  qsa('.topbar-item').forEach(function(item){
    item.onclick=function(){switchTab(this)};
  });
}

function initTopbar(){
  var nt=byId('navTrigger'),nd=byId('navDropdown');
  if(nt)nt.onclick=function(e){e.stopPropagation();if(nd)nd.classList.toggle('open')};
  qsa('#navDropdown .dropdown-item').forEach(function(item){
    item.onclick=function(){switchTab(this);if(nd)nd.classList.remove('open')};
  });
  // 头像下拉
  var av=byId('topbarAvatar'),dd=byId('topbarDropdown');
  if(av)av.onclick=function(e){e.stopPropagation();if(dd)dd.classList.toggle('open');if(av)av.classList.toggle('open')};
  function closeAllPopups(e){
    if(nd&&!nd.contains(e.target)&&!nt.contains(e.target)){nd.classList.remove('open')}
    if(dd&&!dd.contains(e.target)&&!av.contains(e.target)){dd.classList.remove('open');av.classList.remove('open')}
  }
  document.addEventListener('click',closeAllPopups);
  // 退出
  var lo=byId('logoutBtn');
  if(lo)lo.onclick=function(){logout()};
}
function switchTab(el){
  try{
    var tab=el.getAttribute('data-tab');
    qsa('.topbar-item').forEach(function(s){s.classList.remove('active')});
    qsa('#navDropdown .dropdown-item').forEach(function(s){s.classList.remove('active')});
    var match=document.querySelector('.topbar-item[data-tab="'+tab+'"]');
    if(match)match.classList.add('active');
    var dmatch=document.querySelector('#navDropdown .dropdown-item[data-tab="'+tab+'"]');
    if(dmatch)dmatch.classList.add('active');
    showPanel(tab);
    if(tab==='discover'){renderCommunity();}

    if(tab==='service'){renderService();}
    if(tab==='group')renderMyCommunities();
    if(tab==='chat'){initChat();}
    if(tab==='admin')renderAdmin();
    if(tab==='vpn')renderVpn();
  }catch(e){console.error(e)}
}
function logout(){
  sessionStorage.removeItem('admin_token');
  // 服务端退出：清除 token + session
  fetch('/api/auth/logout',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({user_id:USER_ID})}).then(function(){
    window.location.href='/logout.php';
  }).catch(function(){window.location.href='/logout.php'});
}

/* ===== 设置 ===== */
var SETTINGS_LOADED = false;
function renderSettings(){
  var el=byId('settingsContent');if(!el||el.dataset.rendered)return;el.dataset.rendered='1';
  el.innerHTML=
    '<div class="settings-section"><h4 class="settings-section-title"><i class="fas fa-user-circle"></i> 基本资料</h4>'+
    '<div class="settings-card"><div class="settings-row"><span class="settings-label">头像</span><div class="settings-value"><div class="settings-avatar" id="settingsAvatar"><i class="fas fa-user"></i></div><input type="text" class="settings-input" id="settingsAvatarUrl" placeholder="头像URL..." style="flex:1"></div></div>'+
    '<div class="settings-row"><span class="settings-label">昵称</span><input type="text" class="settings-input" id="settingsNickname" placeholder="输入昵称"></div>'+
    '<div class="settings-row"><span class="settings-label">名片背景</span><input type="text" class="settings-input" id="settingsProfileBg" placeholder="渐变色如 #667eea,#764ba2" style="flex:1"></div>'+
    '<div style="display:flex;gap:6px;padding:4px 0 8px 76px"><input type="color" id="settingsProfileBg1" value="#667eea" style="width:36px;height:36px;border:none;border-radius:8px;cursor:pointer;padding:0"><input type="color" id="settingsProfileBg2" value="#764ba2" style="width:36px;height:36px;border:none;border-radius:8px;cursor:pointer;padding:0"><span style="font-size:11px;color:var(--text-dim);line-height:36px">选择渐变色</span></div>'+
    '<div class="settings-row"><span class="settings-label">ID</span><span class="settings-value" id="settingsUserId">'+USER_ID+'</span></div>'+
    '<div class="settings-row"><div class="btn btn-sm btn-accent" id="settingsSaveProfile">保存资料</div></div></div></div>'+
    '<div class="settings-section"><h4 class="settings-section-title"><i class="fas fa-key"></i> 账号令牌</h4>'+
    '<div class="settings-card">'+
    '<div class="settings-row"><span class="settings-label">当前密码</span><input type="password" class="settings-input" id="settingsCurPwd" placeholder="输入当前密码"></div>'+
    '<div class="settings-row"><span class="settings-label">新密码</span><input type="password" class="settings-input" id="settingsNewPwd" placeholder="输入新密码"></div>'+
    '<div class="settings-row"><span class="settings-label">确认新密码</span><input type="password" class="settings-input" id="settingsCfmPwd" placeholder="再次输入新密码"></div>'+
    '<div class="settings-row"><div class="btn btn-sm btn-accent" id="settingsChangePwd">更改密码</div></div></div></div>'+
    '<div class="settings-section"><h4 class="settings-section-title"><i class="fas fa-history"></i> 账号日志 <span style="font-size:12px;color:var(--text-dim);font-weight:400">（只读）</span></h4>'+
    '<div class="settings-card"><div id="settingsLogs"><p style="color:var(--text-dim);font-size:13px">加载中...</p></div></div></div>';
  bindSettings();
  loadSettingsProfile();
  loadSettingsLogs();
}
function bindSettings(){
  var sp=byId('settingsSaveProfile');
  if(sp)sp.onclick=function(){saveSettingsProfile()};
  var cp=byId('settingsChangePwd');
  if(cp)cp.onclick=function(){changePassword()};
  // 颜色选择器联动
  function syncBg(){var b1=byId('settingsProfileBg1'),b2=byId('settingsProfileBg2'),bg=byId('settingsProfileBg');if(b1&&b2&&bg)bg.value=b1.value+','+b2.value}
  var b1=byId('settingsProfileBg1');if(b1)b1.oninput=syncBg;
  var b2=byId('settingsProfileBg2');if(b2)b2.oninput=syncBg;
}
function loadSettingsProfile(){
  fetch('/api/user/profile?user_id='+USER_ID).then(function(r){return r.json()}).then(function(d){
    if(!d.success)return;
    var u=d.user||d.data||{};
    var ni=byId('settingsNickname');if(ni)ni.value=u.nickname||u.username||'';
    var ai=byId('settingsAvatarUrl');if(ai)ai.value=u.avatar_url||u.avatar||'';
    var bg=byId('settingsProfileBg');if(bg&&u.profile_bg)bg.value=u.profile_bg;
    var bg1=byId('settingsProfileBg1'),bg2=byId('settingsProfileBg2');
    if(u.profile_bg){var parts=u.profile_bg.split(',');if(bg1&&parts[0])bg1.value=parts[0].trim();if(bg2&&parts[1])bg2.value=parts[1].trim()}
    var id=byId('settingsUserId');if(id)id.textContent='#'+USER_ID+' '+(u.username||'');
  }).catch(function(){});
}
function saveSettingsProfile(){
  var ni=byId('settingsNickname'),ai=byId('settingsAvatarUrl');
  if(!ni)return;
  fetch('/api/user/update-profile',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({user_id:USER_ID,nickname:ni.value,avatar:ai?ai.value:'',profile_bg:byId('settingsProfileBg')?byId('settingsProfileBg').value:''})
  }).then(function(r){return r.json()}).then(function(d){
    if(d.success)toast('资料已保存');
    else toast('保存失败: '+(d.message||''));
  }).catch(function(){toast('保存失败')});
}
function changePassword(){
  var cur=byId('settingsCurPwd'),nw=byId('settingsNewPwd'),cf=byId('settingsCfmPwd');
  if(!cur||!nw||!cf)return;
  if(nw.value!==cf.value){toast('两次新密码不一致');return}
  if(nw.value.length<6){toast('新密码至少6位');return}
  fetch('/api/user/change-password',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({user_id:USER_ID,current_password:cur.value,new_password:nw.value})
  }).then(function(r){return r.json()}).then(function(d){
    if(d.success){toast('密码已更改');cur.value='';nw.value='';cf.value=''}
    else toast('更改失败: '+(d.message||''));
  }).catch(function(){toast('更改失败')});
}
function loadSettingsLogs(){
  var el=byId('settingsLogs');if(!el)return;
  fetch('/api/user/logs?user_id='+USER_ID).then(function(r){return r.json()}).then(function(d){
    if(!d.success||!d.logs||!d.logs.length){el.innerHTML='<p style="color:var(--text-dim);font-size:13px">暂无日志</p>';return}
    el.innerHTML=d.logs.map(function(l){return'<div class="log-row"><span class="log-date">'+esc(l.created_at||'')+'</span><span class="log-action">'+esc(l.action||l.type||'')+'</span><span class="log-detail">'+esc(l.detail||'')+'</span></div>'}).join('');
  }).catch(function(){el.innerHTML='<p style="color:var(--text-dim);font-size:13px">加载失败</p>'});
}

/* ===== 发现 ===== */
function loadFeed(){
  var el=byId('feedList');if(!el)return;
  fetch('/api/feed/list').then(function(r){return r.json()}).then(function(d){
    if(!d.success||!d.feeds||!d.feeds.length){el.innerHTML='<div class="placeholder-box"><i class="fas fa-broadcast-tower"></i><p>暂无动态</p></div>';return}
    el.innerHTML=d.feeds.map(function(f){return'<div class="feed-item"><div class="feed-user">'+esc(f.nickname||f.username)+'</div><div class="feed-content">'+esc(f.content||'')+'</div><div class="feed-time">'+esc(f.created_at||'')+'</div></div>'}).join('');
  }).catch(function(){el.innerHTML='<div class="placeholder-box"><p>加载失败</p></div>'});
}

/* ===== 服务 ===== */
function renderService(){
  var el=byId('serviceContent');if(!el||el.dataset.rendered)return;el.dataset.rendered='1';
  el.innerHTML=
    '<div class="tool-section"><h4 class="tool-section-title"><i class="fas fa-globe"></i> 公共服务</h4><div class="tool-grid">'+
    '<div class="tool-card"><h4 class="tool-card-title"><i class="fas fa-satellite-dish"></i> VPN节点</h4>'+
    '<div class="svc-info"><span class="svc-label">协议</span><code>Shadowsocks / WireGuard</code></div>'+
    '<div class="svc-info"><span class="svc-label">地址</span><code>154.64.255.112</code></div>'+
    '<div class="svc-info"><span class="svc-label">SS端口</span><code>44380</code> <span class="svc-label">WG端口</span><code>51820</code></div>'+
    '<div class="svc-info"><span class="svc-label">SS密码</span><code>juniusSS2026!</code></div>'+
    '<div class="svc-info"><span class="svc-label">加密</span><code>aes-256-gcm</code></div>'+
    '<div style="margin-top:8px;font-size:11px;color:var(--text-dim)"><i class="fas fa-shield-alt"></i> 配置信息已移至安全区域，联系管理员获取</div></div>'+
    '<div class="tool-card"><h4 class="tool-card-title"><i class="fas fa-hashtag"></i> JSON</h4><textarea class="tool-ta" id="jsonInput" rows="3" placeholder="输入JSON..."></textarea><div class="tool-buttons"><button class="btn btn-sm" id="formatJsonBtn">格式化</button><button class="btn btn-sm" id="minifyJsonBtn">压缩</button><button class="btn btn-sm" id="validateJsonBtn">验证</button></div><div class="tool-output" id="jsonOutput"></div></div>'+
    '<div class="tool-card"><h4 class="tool-card-title"><i class="fas fa-lock"></i> Base64</h4><textarea class="tool-ta" id="cryptoInput" rows="3" placeholder="输入文本..."></textarea><div class="tool-buttons"><button class="btn btn-sm" id="encryptBtn">编码</button><button class="btn btn-sm" id="decryptBtn">解码</button></div><div class="tool-output" id="cryptoOutput"></div></div>'+
    '<div class="tool-card"><h4 class="tool-card-title"><i class="fas fa-ruler"></i> 单位</h4><input class="tool-inp" id="unitValue" placeholder="数值"><div class="form-row" style="margin:4px 0"><select class="tool-sel" id="unitType"><option value="length">长度</option><option value="temp">温度</option></select><select class="tool-sel" id="unitFrom"><option value="m">米</option><option value="cm">厘米</option></select><select class="tool-sel" id="unitTo"><option value="cm">厘米</option><option value="m">米</option></select></div><button class="btn btn-sm" id="convertUnitBtn">换算</button><div class="tool-output" id="unitOutput"></div></div>'+
    '<div class="tool-card"><h4 class="tool-card-title"><i class="fas fa-palette"></i> 颜色</h4><input type="color" id="colorPicker" value="#007aff" style="height:40px;padding:0;width:100%"><div class="tool-buttons"><button class="btn btn-sm" id="copyHexBtn">HEX</button><button class="btn btn-sm" id="copyRgbBtn">RGB</button><button class="btn btn-sm" id="randomColorBtn">随机</button></div><div class="tool-output" id="colorOutput"></div></div>'+
    '</div></div>'+
    '<div class="tool-section"><h4 class="tool-section-title"><i class="fas fa-laptop"></i> 私有服务</h4><div class="tool-grid">'+
    '<div class="tool-card"><h4 class="tool-card-title"><i class="fas fa-robot"></i> Agent Client</h4><p class="svc-desc">Windows 端客户端，连接纵流私有网络</p><a href="/downloads/agent-client.exe" class="btn btn-accent svc-dl" style=""><i class="fas fa-download"></i> 下载</a></div>'+
    '<div class="tool-card"><h4 class="tool-card-title"><i class="fas fa-cloud-upload-alt"></i> 文件存储</h4><p class="svc-desc">安全可靠的文件托管</p><span class="svc-badge">即将上线</span></div>'+
    '<div class="tool-card"><h4 class="tool-card-title"><i class="fas fa-code"></i> 代码仓库</h4><p class="svc-desc">私有 Git 仓库与协作</p><span class="svc-badge">即将上线</span></div>'+
    '</div></div>'+
    '<div class="tool-section"><h4 class="tool-section-title"><i class="fas fa-users"></i> 团服务</h4><div class="tool-grid">'+
    '<div class="tool-card"><h4 class="tool-card-title"><i class="fas fa-comments"></i> 团聊天</h4><p class="svc-desc">团内即时通讯</p><span class="svc-badge">即将上线</span></div>'+
    '<div class="tool-card"><h4 class="tool-card-title"><i class="fas fa-calendar-alt"></i> 团日程</h4><p class="svc-desc">活动与日程管理</p><span class="svc-badge">即将上线</span></div>'+
    '<div class="tool-card"><h4 class="tool-card-title"><i class="fas fa-share-alt"></i> 团共享</h4><p class="svc-desc">资源协作共享</p><span class="svc-badge">即将上线</span></div>'+
    '</div></div>';
  bindTools();
}
function bindTools(){
  var f=byId('formatJsonBtn');if(f)f.onclick=function(){var i=byId('jsonInput'),o=byId('jsonOutput');if(!i||!o)return;try{o.textContent=JSON.stringify(JSON.parse(i.value),null,2)}catch(e){o.textContent='❌ '+e.message}};
  var m=byId('minifyJsonBtn');if(m)m.onclick=function(){var i=byId('jsonInput'),o=byId('jsonOutput');if(!i||!o)return;try{o.textContent=JSON.stringify(JSON.parse(i.value))}catch(e){o.textContent='❌ '+e.message}};
  var v=byId('validateJsonBtn');if(v)v.onclick=function(){var i=byId('jsonInput'),o=byId('jsonOutput');if(!i||!o)return;try{JSON.parse(i.value);o.textContent='✅ 有效'}catch(e){o.textContent='❌ '+e.message}};
  var enc=byId('encryptBtn');if(enc)enc.onclick=function(){var i=byId('cryptoInput'),o=byId('cryptoOutput');if(!i||!o)return;o.textContent=btoa(i.value)};
  var dec=byId('decryptBtn');if(dec)dec.onclick=function(){var i=byId('cryptoInput'),o=byId('cryptoOutput');if(!i||!o)return;try{o.textContent=atob(i.value)}catch(e){o.textContent='❌ 解码失败'}};
  var conv=byId('convertUnitBtn');if(conv)conv.onclick=function(){
    var v=byId('unitValue'),t=byId('unitType'),f=byId('unitFrom'),to=byId('unitTo'),o=byId('unitOutput');
    if(!v||!o)return;
    fetch('/api/tools/unit-convert',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({value:parseFloat(v.value)||0,fromUnit:f?f.value:'m',toUnit:to?to.value:'cm',type:t?t.value:'length'})}).then(function(r){return r.json()}).then(function(d){if(d.success)o.textContent=d.result||d.value;else o.textContent='❌ '+d.error}).catch(function(){o.textContent='❌ 请求失败'});
  };
  var picker=byId('colorPicker'),co=byId('colorOutput');
  if(picker&&co)picker.oninput=function(){var r=parseInt(picker.value.slice(1,3),16),g=parseInt(picker.value.slice(3,5),16),b=parseInt(picker.value.slice(5,7),16);co.innerHTML='HEX: '+picker.value+'<br>RGB: rgb('+r+','+g+','+b+')'};
  var ch=byId('copyHexBtn');if(ch)ch.onclick=function(){var p=byId('colorPicker');if(!p)return;navigator.clipboard.writeText(p.value).then(function(){toast('已复制')})};
  var cr=byId('copyRgbBtn');if(cr)cr.onclick=function(){var p=byId('colorPicker');if(!p)return;var h=p.value;navigator.clipboard.writeText('rgb('+parseInt(h.slice(1,3),16)+','+parseInt(h.slice(3,5),16)+','+parseInt(h.slice(5,7),16)+')').then(function(){toast('已复制')})};
  var rand=byId('randomColorBtn');if(rand)rand.onclick=function(){var p=byId('colorPicker'),o=byId('colorOutput');if(!p||!o)return;var c='#'+Math.floor(Math.random()*16777215).toString(16).padStart(6,'0');p.value=c;var r=parseInt(c.slice(1,3),16),g=parseInt(c.slice(3,5),16),b=parseInt(c.slice(5,7),16);o.innerHTML='HEX: '+c+'<br>RGB: rgb('+r+','+g+','+b+')'};
}
/* ===== 团 ===== */
function renderCommunity(){
  var el=byId('communityList');if(!el)return;
  // 先获取用户已加入的团ID集合
  var joinedSet={};
  fetch('/api/community/my-list?user_id='+USER_ID).then(function(r2){return r2.json()}).then(function(cd2){
    if(cd2.success&&cd2.communities)cd2.communities.forEach(function(c){joinedSet[String(c.id)]=true});
  }).catch(function(){}).then(function(){
  fetch('/api/community/posts?all=1').then(function(r){return r.json()}).then(function(d){
    if(!d.success||!d.posts||!d.posts.length){el.innerHTML='<div class="placeholder-box"><i class="fas fa-newspaper"></i><p>暂无团动态</p></div>';return}
    var html='<div style="margin-bottom:10px"><h3 style="font-size:16px;font-weight:600;color:var(--text-bright)"><i class="fas fa-layer-group"></i> 团动态</h3></div>';
    html+='<div id="postSummaryStats" style="display:flex;gap:12px;margin-bottom:14px;font-size:12px;color:var(--text-dim)"></div>';
    d.posts.forEach(function(p){
      html+='<div class="post-card" style="background:var(--bg2);border-radius:var(--radius-lg);overflow:hidden;box-shadow:var(--shadow);margin-bottom:14px;cursor:pointer;min-height:360px">';
          var cover=p.cover_url||'';
      if(!cover&&p.images){try{var imgs=JSON.parse(p.images);if(imgs&&imgs.length)cover=imgs[0]}catch(e){}}
      if(cover)html+='  <img src="'+cover+'" style="width:100%;height:360px;object-fit:cover;display:block">';
      html+='  <div style="padding:12px">';
      html+='    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">';
      html+='      <span style="font-size:11px;color:var(--accent);background:var(--accent-dim);padding:2px 8px;border-radius:10px">'+esc(p.community_name||'')+'</span>';
      html+='      <span style="font-size:11px;color:var(--text-dim)">'+(p.content_type==='video'?'<i class="fas fa-video"></i> 视频':(p.content_type==='document'?'<i class="fas fa-file-alt"></i> 文档':'<i class="fas fa-image"></i> 图片'))+'</span>';
      html+='    </div>';
      html+='    <div style="font-size:15px;font-weight:600;color:var(--text-bright)">'+esc(p.title)+'</div>';
      html+='    <div style="display:flex;align-items:center;gap:12px;font-size:11px;color:var(--text-dim);margin-top:4px">';
      html+='      <span><i class="far fa-heart"></i> '+(p.like_count||0)+'</span>';
      html+='      <span><i class="far fa-comment"></i> '+(p.comment_count||0)+'</span>';
      html+='      <span>'+esc(p.created_at||'')+'</span>';
      html+='    </div>';
      // 加入按钮
      html+='    <div style="margin-top:8px;text-align:right">';
      if(!joinedSet[String(p.community_id)])
        html+='      <button onclick="event.stopPropagation();joinCommunity('+p.community_id+')" style="padding:4px 14px;border-radius:12px;border:1px solid var(--accent);background:var(--accent-dim);color:var(--accent);font-size:12px;cursor:pointer"><i class="fas fa-plus"></i> 加入该团</button>';
      else
        html+='      <span style="font-size:11px;color:var(--accent)"><i class="fas fa-check"></i> 已加入</span>';
      html+='    </div>';
      html+='  </div>';
      html+='</div>';
    });
    el.innerHTML=html;
    // 绑定动态点击
    var pids=d.posts.map(function(p){return p.id});
    el.querySelectorAll('.post-card').forEach(function(w,i){
      w.onclick=function(){window.open('/post.php?id='+pids[i],'_blank')};
    });
    // 统计
    fetch('/api/post/stats').then(function(r){return r.json()}).then(function(s){
      if(s.success){var se=byId('postSummaryStats');if(se)se.innerHTML='<span><i class="far fa-file-alt"></i> '+s.post_total+' 条动态</span><span><i class="far fa-heart"></i> '+s.total_likes+' 赞</span><span><i class="far fa-comment"></i> '+s.total_comments+' 评论</span>'}
    }).catch(function(){});
  }).catch(function(){el.innerHTML='<div class="placeholder-box"><p>加载失败</p></div>'});
  }); // end of my-list then
}


function joinCommunity(cid){
  fetch('/api/community/join',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({community_id:cid,user_id:USER_ID})}).then(function(r){return r.json()}).then(function(d){
    if(d.success){toast(d.message||'已加入！');renderCommunity()}
    else toast(d.error||'加入失败');
  }).catch(function(){toast('请求失败')});
}

function renderMyCommunities(){
  var el=byId('myCommunityList');if(!el)return;
  fetch('/api/community/my-list?user_id='+USER_ID).then(function(r){return r.json()}).then(function(d){
    if(!d.success||!d.communities||!d.communities.length){el.innerHTML='<div class="placeholder-box"><i class="fas fa-users"></i><p>还没有加入任何团</p></div>';return}
    var html='<div style="margin-bottom:4px;font-size:13px;color:var(--text-dim)"><i class="fas fa-layer-group"></i> 我加入的团</div>';
    var colors=['#5b86e5','#36d1dc','#ff6b6b','#f093fb','#4facfe','#43e97b','#fa709a','#a18cd1'];
    d.communities.forEach(function(c){
      var isMine=c.creator_id===USER_ID;
      html+='<div class="my-community-item" data-cid="'+c.id+'">';
      html+='  <span class="my-community-dot" style="background:'+colors[c.id%colors.length]+'"></span>';
      html+='  <span class="my-community-name">'+esc(c.name)+'</span>';
      html+='  <span style="font-size:11px;color:var(--text-dim);margin-right:6px">#'+c.id+'</span>';
      if(isMine)html+='  <span class="my-community-tag">我的</span>';
      html+='  <span class="my-community-badge">'+esc(c.category||'其他')+'</span>';
      html+='</div>';
    });
    el.innerHTML=html;
    el.querySelectorAll('.my-community-item').forEach(function(w){
      w.onclick=function(){
        var cid=this.getAttribute('data-cid');
        window.open('/community/manage.php?id='+cid,'_blank');
      };
    });
  }).catch(function(){el.innerHTML='<div class="placeholder-box"><p>加载失败</p></div>'});
}

/* ===== 名片弹窗 ===== */
function showProfile(uid){
  if(!uid)return;
  var mo=byId('profileModal');
  mo.classList.add('active');
  mo.innerHTML='<div class="profile-card" style="background:#fff;border-radius:20px;overflow:hidden;width:380px;max-width:90vw;box-shadow:0 8px 40px rgba(0,0,0,0.2);position:relative;z-index:10000"><div class="pc-cover" id="pcCover" style="height:120px;background:linear-gradient(135deg,#667eea,#764ba2);position:relative"><div class="pc-avatar" id="pcAvatar" style="position:absolute;left:50%;bottom:-36px;transform:translateX(-50%);width:72px;height:72px;border-radius:50%;border:3px solid #fff;background:#fff;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1)"><i class="fas fa-spinner fa-spin" style="font-size:28px;display:flex;align-items:center;justify-content:center;width:100%;height:100%;color:var(--text-dim)"></i></div></div><div class="pc-body" style="padding:48px 20px 20px;text-align:center"><div class="pc-nick" id="pcNick" style="font-size:17px;font-weight:700;color:var(--text-bright)">\u52a0\u8f7d\u4e2d...</div><div class="pc-name" id="pcName" style="font-size:12px;color:var(--text-dim);margin-top:2px"></div><div class="pc-id" id="pcId" style="font-size:11px;color:var(--text-dim);margin-top:2px"></div><div class="pc-bio" id="pcBio" style="font-size:13px;color:#3a3a3c;margin-top:12px;padding:0 4px;line-height:1.5"></div><div id="pcBtnArea"></div></div></div>';
  fetch('/api/user/profile?user_id='+uid).then(function(r){return r.json()}).then(function(d){
    if(!d.success||!d.user){byId('pcNick').textContent='\u52a0\u8f7d\u5931\u8d25';return}
    var u=d.user;
    byId('pcNick').textContent=u.nickname||u.username||'\u7528\u6237#'+uid;
    byId('pcName').textContent='@'+(u.username||'');
    byId('pcId').textContent='ID: '+uid;
    byId('pcBio').textContent=u.bio||'\u8fd9\u4e2a\u4eba\u5f88\u61d2\uff0c\u4ec0\u4e48\u90fd\u6ca1\u5199';
    var bg=u.profile_bg||'#667eea,#764ba2';
    var cv=byId('pcCover');if(cv)cv.style.background='linear-gradient(135deg,'+bg+')';
    var avEl=byId('pcAvatar');
    if(u.avatar_url&&u.avatar_url!='/assets/images/default-avatar.png')avEl.innerHTML='<img src="'+u.avatar_url+'" style="width:100%;height:100%;object-fit:cover;border-radius:50%">';
    else avEl.innerHTML='<i class="fas fa-user" style="font-size:28px;color:var(--text-dim);display:flex;align-items:center;justify-content:center;width:100%;height:100%"></i>';
    var ba=byId('pcBtnArea');ba.innerHTML='';
    if(uid!=USER_ID){
      var btn=document.createElement('button');btn.style.cssText='padding:8px 20px;border-radius:20px;font-size:13px;margin-top:10px;background:var(--accent);color:#fff;border:none;cursor:pointer';
      btn.innerHTML='<i class="fas fa-user-plus"></i> 添加好友';
      btn.onclick=function(){fetch('/api/friends/apply',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({user_id:USER_ID,friend_id:uid})}).then(function(r){return r.json()}).then(function(d){if(d.success){toast('已发送申请');byId('profileModal').classList.remove('active')}else toast(d.error||'失败')}).catch(function(){toast('请求失败')})};
      ba.appendChild(btn);
    }
  }).catch(function(){byId('pcNick').textContent='\u8bf7\u6c42\u5931\u8d25'});
}

function sendFriendRequest(targetId){
  fetch('/api/friends/apply',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({user_id:USER_ID,friend_id:targetId})}).then(function(r){return r.json()}).then(function(d){
    if(d.success){toast('已发送好友申请');byId('profileModal').classList.remove('active')}
    else toast(d.error||'发送失败');
  }).catch(function(){toast('请求失败')});
}
function isWide(){return window.innerWidth>=768}

function initChat(){
  // 重置会话状态
  if(CHAT_POLL){clearInterval(CHAT_POLL);CHAT_POLL=null}
  if(window._chatEventSource){window._chatEventSource.close();window._chatEventSource=null}
  CURRENT_CHAT_ID=null;
  // 初始布局：窄屏只显示好友列表，宽屏双栏
  var cv=byId('chatConvCol'),ia=byId('chatInputArea');
  if(cv){cv.classList.remove('active');cv.style.display=isWide()?'flex':'none'}
  if(ia)ia.style.display='none';
  var ti=byId('chatConvTitle');
  if(ti)ti.textContent='选择一个好友开始聊天';
  var ms=byId('chatMessages');
  if(ms)ms.innerHTML='<div class="chat-empty">选择一个好友开始聊天</div>';
  // 加载数据
  loadFriendList();
  loadRequests();
  bindFriendSearch();
  // 聊天 tab 切换
  qsa('[data-ctab]').forEach(function(tab){
    tab.onclick=function(){
      qsa('[data-ctab]').forEach(function(t){t.classList.remove('active')});
      tab.classList.add('active');var ct=tab.getAttribute('data-ctab');
      qsa('.chat-list-scroll').forEach(function(s){s.style.display='none'});
      if(ct==='friends'){byId('friendList').style.display='';loadFriendList();}
      if(ct==='requests'){byId('requestList').style.display='';loadRequests();}
    };
  });
  // 返回按钮
  var bb=byId('chatBackBtn');if(bb)bb.onclick=backFromChat;
  // 窗口尺寸变化
  window.addEventListener('resize',onChatResize);
}
function onChatResize(){
  var cv=byId('chatConvCol'),bb=byId('chatBackBtn'),tit=byId('chatConvTitle'),ms=byId('chatMessages'),ia=byId('chatInputArea');if(!cv)return;
  if(isWide()){
    cv.classList.remove('active');
    cv.style.display='';
    if(bb)bb.style.display='none';
  } else {
    if(CURRENT_CHAT_ID){
      cv.style.display='flex';
      cv.classList.add('active');
      if(bb)bb.style.display='';
    } else {
      cv.style.display='none';
      cv.classList.remove('active');
      if(bb)bb.style.display='none';
      if(ia)ia.style.display='none';
      if(ms)ms.innerHTML='<div class="chat-empty">选择一个好友开始聊天</div>';
      if(tit)tit.textContent='选择一个好友开始聊天';
    }
  }
}
function backFromChat(){
  // 清除会话，回到好友列表初始状态
  if(CHAT_POLL){clearInterval(CHAT_POLL);CHAT_POLL=null}
  if(window._chatEventSource){window._chatEventSource.close();window._chatEventSource=null}
  CURRENT_CHAT_ID=null;
  var cv=byId('chatConvCol'),bb=byId('chatBackBtn'),ia=byId('chatInputArea'),ms=byId('chatMessages'),ti=byId('chatConvTitle'),cl=byId('chatListCol');
  if(cv){cv.classList.remove('active');cv.style.display=isWide()?'flex':'none'}
  if(cl)cl.style.display='';
  if(bb)bb.style.display='none';
  if(ia)ia.style.display='none';
  if(ms)ms.innerHTML='<div class="chat-empty">选择一个好友开始聊天</div>';
  if(ti)ti.textContent='选择一个好友开始聊天';
  // 好友 tab 设为激活
  qsa('[data-ctab]').forEach(function(t){t.classList.remove('active')});
  var ft=document.querySelector('[data-ctab="friends"]');
  if(ft)ft.classList.add('active');
  qsa('.chat-list-scroll').forEach(function(s){s.style.display='none'});
  var fl=byId('friendList');if(fl)fl.style.display='';
}
function navigateToConv(){
  var cv=byId('chatConvCol'),bb=byId('chatBackBtn'),ia=byId('chatInputArea'),cl=byId('chatListCol');
  if(ia)ia.style.display='flex';
  if(!isWide()){
    if(cl)cl.style.display='none';
    if(cv){cv.style.display='flex';cv.classList.add('active')}
    if(bb)bb.style.display='';
  }
}

function loadFriendList(searchTerm){
  var el=byId('friendList');if(!el)return;
  // 添加好友控件（只创建一次）
  var addEl=byId('friendAddBox');
  if(!addEl){
    var ab=document.createElement('div');ab.id='friendAddBox';
    ab.innerHTML='<div style="padding:8px 12px;border-bottom:1px solid rgba(0,0,0,0.04)"><div style="display:flex;gap:6px"><input id="friendAddInput" placeholder="搜索用户名或ID添加好友..." style="flex:1;border:1px solid rgba(0,0,0,0.08);border-radius:8px;padding:7px 10px;font-size:12px;outline:none"><button class="btn btn-sm btn-accent" id="friendAddDoBtn" style="font-size:11px;padding:6px 12px"><i class="fas fa-search"></i></button></div><div id="friendAddResult" style="font-size:11px;margin-top:4px;color:var(--text-dim)"></div></div>';
    el.parentNode.insertBefore(ab,el);
    byId('friendAddDoBtn').onclick=function(){doAddFriend()};
    byId('friendAddInput').onkeydown=function(e){if(e.key==='Enter')doAddFriend()};
  }
  fetch('/api/friends/requests?user_id='+USER_ID).then(function(r){return r.json()}).then(function(d){
    if(!d.success){el.innerHTML='<div class="placeholder-box" style="padding:20px"><p>暂无好友</p></div>';return}
    var list = [];
    if(d.outgoing) list = list.concat(d.outgoing.filter(function(f){return f.status==='accepted'}));
    if(d.incoming) list = list.concat(d.incoming.filter(function(f){return f.status==='accepted'}));
    // 去重
    var seen = {};
    list = list.filter(function(u){if(seen[u.user_id])return false;seen[u.user_id]=true;return true});
    if(!list||!list.length){el.innerHTML='<div class="placeholder-box" style="padding:20px"><p>暂无好友</p></div>';return}
    if(searchTerm&&searchTerm.trim()){
      var q=searchTerm.trim().toLowerCase();
      list=list.filter(function(u){return(u.nickname||'').toLowerCase().includes(q)||u.username.toLowerCase().includes(q)});
    }
    // 批量获取未读数和在线状态
    var friendIds=list.map(function(u){return u.user_id});
    Promise.all([
      fetch('/api/chat/unread-counts?user_id='+USER_ID+'&friend_ids='+friendIds.join(',')).then(function(r){return r.json()}),
      fetch('/api/friends/online-status?user_id='+USER_ID+'&friend_ids='+friendIds.join(',')).then(function(r){return r.json()})
    ]).then(function(results){
      var cd=results[0],od=results[1];
      if(cd.success&&cd.counts){
        list.forEach(function(u){u.unread=cd.counts[u.user_id]||0});
      }
      if(od.success&&od.statuses){
        list.forEach(function(u){u.is_online=od.statuses[u.user_id]||false});
      }
      renderFriendList(el,list);
    }).catch(function(){renderFriendList(el,list)});
  }).catch(function(){});
}

function renderFriendList(el,list){
  el.innerHTML=list.map(function(u){
    var name=esc(u.nickname||u.username);
    var onlineHtml=u.is_online?'<span class="online-dot"></span>':'';
    var badgeHtml=u.unread>0?'<span class="badge">'+u.unread+'</span>':'';
    return'<div class="chat-user-item" data-uid="'+u.user_id+'" data-name="'+name+'"><div class="avatar" onclick="event.stopPropagation();showProfile('+u.user_id+')">'+(u.avatar_url&&u.avatar_url!=='/assets/images/default-avatar.png'?'<img src="'+esc(u.avatar_url)+'" style="width:40px;height:40px;border-radius:50%;object-fit:cover;cursor:pointer">':'<i class="fas fa-user" style="cursor:pointer"></i>')+onlineHtml+'</div><div style="flex:1;min-width:0"><div class="friend-name">'+name+'</div><div class="friend-username">@'+esc(u.username)+'</div></div><div style="flex-shrink:0;display:flex;align-items:center;gap:4px">'+badgeHtml+'<button class="btn-unfriend" data-did="'+u.user_id+'" title="删除好友"><i class="fas fa-times"></i></button></div></div>';
  }).join('');
  el.querySelectorAll('.btn-unfriend').forEach(function(btn){
    btn.onclick=function(e){e.stopPropagation();var fid=parseInt(this.getAttribute('data-did'));if(!confirm('确定删除好友？'))return;fetch('/api/friends/remove',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({user_id:USER_ID,friend_id:fid})}).then(function(r){return r.json()}).then(function(d){if(d.success){toast('已删除好友');loadFriendList()}else toast(d.error||'删除失败')}).catch(function(){toast('删除失败')})};
  });
  el.querySelectorAll('.chat-user-item').forEach(function(item){
    item.onclick=function(){
      var uid=parseInt(item.getAttribute('data-uid'));
      var name=item.getAttribute('data-name');
      openChat(uid,name);
    };
  });
}
function bindFriendSearch(){
  var inp=byId('friendSearch');
  if(!inp)return;
  var _timer;
  inp.oninput=function(){clearTimeout(_timer);_timer=setTimeout(function(){loadFriendList(inp.value)},200)};
}
function loadRequests(){
  var el=byId('requestList');if(!el)return;
  // 添加好友表单（只创建一次）
  var formEl=byId('addFriendForm');
  if(!formEl){
    var f=document.createElement('div');f.id='addFriendForm';
    f.innerHTML='<div style="padding:10px;border-bottom:1px solid rgba(0,0,0,0.04)"><div style="display:flex;gap:8px"><input id="addFriendInput" placeholder="搜索用户名/ID..." style="flex:1;border:1px solid rgba(0,0,0,0.08);border-radius:8px;padding:8px 12px;font-size:13px;outline:none"><button class="btn btn-sm btn-accent" id="addFriendBtn"><i class="fas fa-search"></i> 搜索</button></div><div id="addFriendResult" style="font-size:12px;margin-top:6px"></div></div>';
    el.appendChild(f);
    byId('addFriendBtn').onclick=function(){
      var inp=byId('addFriendInput'),res=byId('addFriendResult');
      if(!inp||!res)return;
      var q=inp.value.trim();
      if(!q){res.textContent='请输入用户名/ID';res.style.color='var(--danger)';return}
      fetch('/api/friends/apply',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({user_id:USER_ID,query:q})}).then(function(r){return r.json()}).then(function(d){
        if(d.success){res.innerHTML='<span style="color:#34c759">\u2713 好友申请已发送</span>';inp.value='';loadRequests()}
        else{res.innerHTML='<span style="color:var(--danger)">'+(d.error||d.message||'发送失败')+'</span>'}
      }).catch(function(){res.innerHTML='<span style="color:var(--danger)">网络错误</span>'});
    };
  }
  // 容器
  var listEl=byId('requestListItems');
  if(!listEl){listEl=document.createElement('div');listEl.id='requestListItems';el.appendChild(listEl)}
  // 加载收到的申请 + 已发送的申请
  Promise.all([
    fetch('/api/friends/requests?user_id='+USER_ID).then(function(r){return r.json()}),
    fetch('/api/friends/pending-sent?user_id='+USER_ID).then(function(r){return r.json()})
  ]).then(function(results){
    var incoming=results[0],sent=results[1];
    var html='';
    // ===== 收到的申请 =====
    html+='<div style="padding:8px 12px;font-size:12px;color:var(--text-dim);font-weight:600;border-bottom:1px solid rgba(0,0,0,0.04);display:flex;align-items:center;gap:6px"><i class="fas fa-inbox"></i> 好友申请</div>';
    if(incoming.success&&incoming.incoming){
      var pendingIncoming = incoming.incoming.filter(function(r){return r.status==='pending'});
      if(pendingIncoming.length){
        html+=pendingIncoming.map(function(r){
        return'<div style="padding:10px 12px;border-bottom:1px solid rgba(0,0,0,0.03);display:flex;justify-content:space-between;align-items:center"><span style="cursor:pointer" onclick="showProfile('+r.from_user_id+')">'+esc(r.username||'用户')+'</span><div><button class="btn btn-sm btn-accent" data-req="'+r.id+'" data-act="accept">同意</button><button class="btn btn-sm btn-danger" data-req="'+r.id+'" data-act="reject" style="margin-left:4px">拒绝</button></div></div>';
      }).join('');
      }else{
        html+='<div style="padding:12px;font-size:13px;color:var(--text-dim);text-align:center;border-bottom:1px solid rgba(0,0,0,0.03)">暂无好友申请</div>';
      }
    }else{
      html+='<div style="padding:12px;font-size:13px;color:var(--text-dim);text-align:center;border-bottom:1px solid rgba(0,0,0,0.03)">暂无好友申请</div>';
    }
    // ===== 我发出的申请 =====
    html+='<div style="padding:8px 12px;font-size:12px;color:var(--text-dim);font-weight:600;border-bottom:1px solid rgba(0,0,0,0.04);display:flex;align-items:center;gap:6px;margin-top:8px"><i class="fas fa-paper-plane"></i> 我发出的申请</div>';
    if(sent.success&&sent.requests&&sent.requests.length){
      html+=sent.requests.map(function(r){
        return'<div style="padding:10px 12px;border-bottom:1px solid rgba(0,0,0,0.03);display:flex;align-items:center;gap:8px"><span>'+esc(r.nickname||r.username)+'</span><span style="font-size:11px;color:var(--accent);background:var(--accent-dim);padding:2px 8px;border-radius:10px">等待通过</span></div>';
      }).join('');
    }else{
      html+='<div style="padding:12px;font-size:13px;color:var(--text-dim);text-align:center">暂无已发送的申请</div>';
    }
    listEl.innerHTML=html;
    listEl.querySelectorAll('[data-req]').forEach(function(btn){
      btn.onclick=function(){
        fetch('/api/friends/handle',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({request_id:parseInt(this.getAttribute('data-req')),action:this.getAttribute('data-act')})}).then(function(r){return r.json()}).then(function(d){if(d.success){toast('操作成功');loadRequests();loadFriendList()}else toast('操作失败')}).catch(function(){});
      };
    });
  }).catch(function(){});
}
function openChat(uid,name){
  if(CHAT_POLL){clearInterval(CHAT_POLL);CHAT_POLL=null}
  if(window._chatEventSource){window._chatEventSource.close();window._chatEventSource=null}
  CURRENT_CHAT_ID=null;
  var ti=byId('chatConvTitle'),ia=byId('chatInputArea'),ms=byId('chatMessages'),inp=byId('chatInput');
  if(ti)ti.textContent='与 '+name+' 聊天中';
  if(ia)ia.style.display='flex';
  if(inp){inp.value='';setTimeout(function(){inp.focus()},100)}
  if(ia)ia.style.display='';
  if(ms)ms.innerHTML='<div class="chat-empty"><i class="fas fa-spinner fa-spin"></i> 正在建立连接...</div>';
  navigateToConv();
  fetch('/api/chat/create',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({user_id:USER_ID,target_id:uid})}).then(function(r){return r.json()}).then(function(d){
    if(!d.success){if(ms)ms.innerHTML='<div class="chat-empty">创建会话失败</div>';return}
    CURRENT_CHAT_ID=d.chat_id;
    loadMessages();
    tryStartSSE(CURRENT_CHAT_ID);
  }).catch(function(){if(ms)ms.innerHTML='<div class="chat-empty">请求失败</div>'});
}
function loadMessages(){
  if(!CURRENT_CHAT_ID)return;
  fetch('/api/chat/messages?chat_id='+CURRENT_CHAT_ID+'&user_id='+USER_ID).then(function(r){return r.json()}).then(function(d){
    if(!d.success||!d.messages)return;
    var el=byId('chatMessages');if(!el)return;
    el.innerHTML=d.messages.map(function(m){
      var isMe=parseInt(m.sender_id)===USER_ID;
      return'<div class="chat-msg '+(isMe?'me':'other')+'" data-msgid="'+m.id+'"><div class="bubble">'+esc(m.content)+'</div><div class="time">'+esc(m.created_at||'')+'</div></div>';
    }).join('');
    el.scrollTop=el.scrollHeight;
  }).catch(function(){});
}
function sendMessage(){
  if(!CURRENT_CHAT_ID)return;
  var inp=byId('chatInput');if(!inp||!inp.value.trim())return;
  var content=inp.value.trim();inp.value='';
  fetch('/api/chat/send',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({chat_id:CURRENT_CHAT_ID,sender_id:USER_ID,content:content})}).then(function(r){return r.json()}).then(function(d){
    if(d.success){loadMessages()}else toast('发送失败');
  }).catch(function(){toast('发送失败')});
}
function tryStartSSE(chatId){
  if(window._chatEventSource){window._chatEventSource.close();window._chatEventSource=null}
  try{
    var es=new EventSource('/api/chat/stream?chat_id='+chatId+'&user_id='+USER_ID);
    window._chatEventSource=es;
    es.onmessage=function(e){
      try{
        var msg=JSON.parse(e.data);
        appendMessage(msg);
      }catch(err){}
    };
    es.onerror=function(){
      es.close();window._chatEventSource=null;
      if(CURRENT_CHAT_ID&&CHAT_POLL===null){CHAT_POLL=setInterval(loadMessages,3000)}
    };
    es.onopen=function(){
      if(CHAT_POLL){clearInterval(CHAT_POLL);CHAT_POLL=null}
    };
  }catch(e){
    if(CURRENT_CHAT_ID&&CHAT_POLL===null){CHAT_POLL=setInterval(loadMessages,3000)}
  }
}
function appendMessage(msg){
  var el=byId('chatMessages');if(!el)return;
  if(el.querySelector('[data-msgid="'+msg.id+'"]'))return;
  var isMe=parseInt(msg.sender_id)===USER_ID;
  var div=document.createElement('div');
  div.className='chat-msg '+(isMe?'me':'other');
  div.setAttribute('data-msgid',msg.id);
  div.innerHTML='<div class="bubble">'+esc(msg.content)+'</div><div class="time">'+esc(msg.created_at||'')+'</div>';
  el.appendChild(div);
  el.scrollTop=el.scrollHeight;
}
/* ===== 启动时绑定事件 ===== */
function bindEvents(){
byId('sendChatBtn').onclick=sendMessage;
var ci=byId('chatInput');if(ci)ci.onkeydown=function(e){if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();sendMessage()}};
var cgb=byId('createGroupBtn');if(cgb)cgb.onclick=function(){var m=byId('createGroupModal');if(m)m.classList.add('active')};
var ccg=byId('cancelCreateGroup');if(ccg)ccg.onclick=function(){var m=byId('createGroupModal');if(m)m.classList.remove('active')};
var cgm=byId('createGroupModal');if(cgm)cgm.onclick=function(e){if(e.target===this)this.classList.remove('active')};
byId('submitCreateGroup').onclick=function(){
  var name=byId('createGroupName'),desc=byId('createGroupDesc'),cat=byId('createGroupCategory'),msg=byId('createGroupMsg'),banner=byId('createGroupBanner');
  if(!name||!name.value.trim()){msg.textContent='请输入团名称';return}
  // 如果有宣传图，先上传
  function doCreate(bannerUrl){
    var urlEl=byId('createGroupUrl');
    fetch('/api/community/create',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({user_id:USER_ID,name:name.value.trim(),description:desc?desc.value:'',category:cat?cat.value:'其他',banner:bannerUrl||'',site_url:urlEl?urlEl.value.trim():''})}).then(function(r){return r.json()}).then(function(d){
      if(d.success){toast(d.message||'申请已发送，等待管理员审核');byId('createGroupModal').classList.remove('active');name.value='';if(desc)desc.value='';if(msg)msg.textContent='';renderCommunity()}
      else{if(msg)msg.textContent=d.error||'创建失败'}
    }).catch(function(){if(msg)msg.textContent='请求失败'});
  }
  if(banner&&banner.files&&banner.files[0]){
    var reader=new FileReader();
    reader.onload=function(e){
      // 上传图片到服务器
      fetch('/api/upload/banner',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({image:e.target.result})}).then(function(r){return r.json()}).then(function(d){
        doCreate(d.url||'');
      }).catch(function(){doCreate('')});
    };
    reader.readAsDataURL(banner.files[0]);
  } else {
    doCreate('');
  }
};
}

/* ===== 管理面板 ===== */
function renderAdmin(){
  var el=byId('adminContent');if(!el)return;
  if(ADMIN_TOKEN){renderAdminContent();return}
  el.innerHTML=
    '<div class="admin-lock"><i class="fas fa-lock" style="font-size:40px;color:var(--accent-dim)"></i><p style="margin:12px 0;color:var(--text-bright)">管理面板已锁定</p>'+
    '<input id="adminUnlockPwd" type="password" placeholder="输入密码解锁..." style="max-width:280px;margin:0 auto 8px">'+
    '<div id="adminUnlockMsg" style="font-size:12px;color:var(--danger);margin-bottom:8px"></div>'+
    '<button class="btn btn-accent" id="adminUnlockBtn">解锁</button></div>';
  byId('adminUnlockBtn').onclick=function(){
    var pwd=byId('adminUnlockPwd'),msg=byId('adminUnlockMsg');
    if(!pwd||!pwd.value.trim()){if(msg)msg.textContent='请输入密码';return}
    fetch('/api/verify_pwd.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({password:pwd.value.trim()})}).then(function(r){return r.json()}).then(function(d){
      if(d.success&&d.token){ADMIN_TOKEN=d.token;sessionStorage.setItem('admin_token',d.token);renderAdminContent();toast('解锁成功')}
      else{if(msg)msg.textContent=d.error||'密码错误'}
    }).catch(function(){if(msg)msg.textContent='请求失败'});
  };
}
function renderAdminContent(){
  var el=byId('adminContent');if(!el||!ADMIN_TOKEN)return;
  el.innerHTML='<div class="placeholder-box"><i class="fas fa-spinner fa-spin"></i><p>加载中...</p></div>';
  // 统计数据
  fetch('/api/admin/pending-users', {headers: {'Authorization': 'Bearer '+ADMIN_TOKEN}}).then(function(r){return r.json()}).then(function(d){
    var pending=d&&d.users?d.users.length:0;
    fetch('/api/admin/users', {headers: {'Authorization': 'Bearer '+ADMIN_TOKEN}}).then(function(r2){return r2.json()}).then(function(d2){
      var total=d2&&d2.users?d2.users.length:0;
      el.innerHTML=
        '<div class="admin-stat-grid"><div class="admin-stat-card" data-asec="users" style="cursor:pointer"><div class="num">'+total+'</div><div class="label">总用户</div></div><div class="admin-stat-card" data-asec="pending" style="cursor:pointer"><div class="num">'+pending+'</div><div class="label">待审核</div></div><div class="admin-stat-card" data-asec="communities" style="cursor:pointer"><div class="num" id="pendingCommCount">?</div><div class="label">审核团</div></div><div class="admin-stat-card" data-asec="allcommunities" style="cursor:pointer"><div class="num" id="totalCommCount">?</div><div class="label">总团数</div></div><div class="admin-stat-card" data-asec="resets" style="cursor:pointer"><div class="num" id="pendingResetCount">0</div><div class="label">密码重置</div></div></div>'+
        '<div id="adminSectionContent"></div>';
      bindAdminTabs();
      loadPendingUsers();
    }).catch(function(){});
  }).catch(function(){});
}
function bindAdminTabs(){
  qsa('[data-asec]').forEach(function(t){
    t.onclick=function(){
      var sec=this.getAttribute('data-asec');
      if(sec==='pending')loadPendingUsers();
      else if(sec==='communities')loadPendingCommunities();
      else if(sec==='allcommunities')loadAllCommunities();
      else if(sec==='resets')loadPasswordResets();
      else loadUserList();
    };
  });
  loadPendingUsers();
  fetch('/api/community/pending-approvals?user_id='+USER_ID).then(function(r){return r.json()}).then(function(d){if(d.success&&d.communities){var el=byId('pendingCommCount');if(el)el.textContent=d.communities.length}}).catch(function(){});
  fetch('/api/community/admin-all?user_id='+USER_ID).then(function(r){return r.json()}).then(function(d){if(d.success){var el=byId('totalCommCount');if(el)el.textContent=d.total}}).catch(function(){});
  // Load pending password reset count
  if (ADMIN_TOKEN) {
    fetch('/api/admin/password-resets', {headers: {'Authorization': 'Bearer ' + ADMIN_TOKEN}})
      .then(function(r){return r.json()})
      .then(function(d){
        if (d.success && d.resets) {
          var cnt = d.resets.filter(function(r) { return r.status === 'pending'; }).length;
          var el = byId('pendingResetCount');
          if (el) el.textContent = cnt;
        }
      }).catch(function(){});
  }
}
function loadPendingUsers(){
  var el=byId('adminSectionContent');if(!el||!ADMIN_TOKEN)return;
  el.innerHTML='<div class="placeholder-box"><i class="fas fa-spinner fa-spin"></i><p>加载中...</p></div>';
  fetch('/api/admin/pending-users', {headers: {'Authorization': 'Bearer '+ADMIN_TOKEN}}).then(function(r){return r.json()}).then(function(d){
    if(!d.success){el.innerHTML='<div class="placeholder-box"><p>加载失败</p></div>';return}
    if(!d.users||!d.users.length){el.innerHTML='<div class="placeholder-box"><i class="fas fa-check-circle" style="color:var(--accent)"></i><p>暂无待审核用户</p></div>';return}
    el.innerHTML=d.users.map(function(u){
      return'<div class="card" style="display:flex;justify-content:space-between;align-items:center"><div><div style="color:var(--text-bright);font-weight:600">'+esc(u.username)+'</div><div style="color:var(--text-dim);font-size:12px">注册: '+esc(u.created_at||'')+'</div></div><div><button class="btn btn-sm btn-accent" data-aprv="'+u.id+'">通过</button><button class="btn btn-sm btn-danger" data-rej="'+u.id+'" style="margin-left:4px">拒绝</button></div></div>';
    }).join('');
    el.querySelectorAll('[data-aprv]').forEach(function(b){
      b.onclick=function(){fetch('/api/admin/approve-user/'+this.getAttribute('data-aprv'),{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({token:ADMIN_TOKEN})}).then(function(r){return r.json()}).then(function(d){if(d.success){toast('已通过');loadPendingUsers()}else toast(d.error||'操作失败')}).catch(function(){})};
    });
    el.querySelectorAll('[data-rej]').forEach(function(b){
      b.onclick=function(){fetch('/api/admin/reject-user/'+this.getAttribute('data-rej'),{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({token:ADMIN_TOKEN})}).then(function(r){return r.json()}).then(function(d){if(d.success){toast('已拒绝');loadPendingUsers()}else toast(d.error)})};
    });
  }).catch(function(){el.innerHTML='<div class="placeholder-box"><p>加载失败</p></div>'});
}
function loadUserList(){
  var el=byId('adminSectionContent');if(!el||!ADMIN_TOKEN)return;
  fetch('/api/admin/users?token='+ADMIN_TOKEN).then(function(r){return r.json()}).then(function(d){
    if(!d.success||!d.users){el.innerHTML='<div class="placeholder-box"><p>加载失败</p></div>';return}
    el.innerHTML=d.users.map(function(u){
      return'<div class="card" style="display:flex;justify-content:space-between;align-items:center"><div><div style="color:var(--text-bright)">'+esc(u.username)+'</div><div style="color:var(--text-dim);font-size:12px">'+esc(u.role||'user')+' · '+(u.status||'')+'</div></div><button class="btn btn-sm btn-danger" data-del="'+u.id+'">删除</button></div>';
    }).join('');
    el.querySelectorAll('[data-del]').forEach(function(b){
      b.onclick=function(){
        if(!confirm('确定删除该用户？'))return;
        fetch('/api/admin/delete-user/'+this.getAttribute('data-del'),{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({token:ADMIN_TOKEN})}).then(function(r){return r.json()}).then(function(d){if(d.success){toast('已删除');loadUserList()}else toast(d.error||'操作失败')}).catch(function(){});
      };
    });
  }).catch(function(){});
}
function loadPendingCommunities(){
  var el=byId('adminSectionContent');if(!el||!ADMIN_TOKEN)return;
  el.innerHTML='<div class="placeholder-box"><i class="fas fa-spinner fa-spin"></i><p>加载中...</p></div>';
  fetch('/api/community/pending-approvals?user_id='+USER_ID).then(function(r){return r.json()}).then(function(d){
    if(!d.success||!d.communities||!d.communities.length){el.innerHTML='<div class="placeholder-box"><i class="fas fa-check-circle" style="color:var(--accent)"></i><p>暂无待审核团</p></div>';return}
    el.innerHTML='<div style="margin-bottom:10px;font-size:12px;color:var(--text-dim)"><i class="fas fa-clock"></i> 待审核的团创建申请</div>'+
      d.communities.map(function(c){
        var nm=c.nickname||c.username||'用户#'+c.creator_id;
        return '<div class="card" style="display:flex;justify-content:space-between;align-items:center"><div><div style="color:var(--text-bright);font-weight:600">'+esc(c.name)+'</div><div style="color:var(--text-dim);font-size:12px">创建者: '+esc(nm)+'</div></div><div><button class="btn btn-sm btn-accent" data-aprv-c="'+c.id+'">通过</button><button class="btn btn-sm btn-danger" data-rej-c="'+c.id+'" style="margin-left:4px">拒绝</button></div></div>';
      }).join('');
    el.querySelectorAll('[data-aprv-c]').forEach(function(b){
      b.onclick=function(){fetch('/api/community/approve-create',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({community_id:this.getAttribute('data-aprv-c'),user_id:USER_ID,action:'approve'})}).then(function(r){return r.json()}).then(function(d){if(d.success){toast('已通过');loadPendingCommunities()}else toast(d.error||'操作失败')}).catch(function(){})};
    });
    el.querySelectorAll('[data-rej-c]').forEach(function(b){
      b.onclick=function(){fetch('/api/community/approve-create',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({community_id:this.getAttribute('data-rej-c'),user_id:USER_ID,action:'reject'})}).then(function(r){return r.json()}).then(function(d){if(d.success){toast('已拒绝');loadPendingCommunities()}else toast(d.error||'操作失败')}).catch(function(){})};
    });
  }).catch(function(){el.innerHTML='<div class="placeholder-box"><p>加载失败</p></div>'});
}


function loadAllCommunities(){
  var el=byId('adminSectionContent');if(!el||!ADMIN_TOKEN)return;
  el.innerHTML='<div class="placeholder-box"><i class="fas fa-spinner fa-spin"></i><p>加载中...</p></div>';
  fetch('/api/community/admin-all?user_id='+USER_ID).then(function(r){return r.json()}).then(function(d){
    if(!d.success||!d.communities||!d.communities.length){el.innerHTML='<div class="placeholder-box"><i class="fas fa-users" style="color:var(--text-dim)"></i><p>暂无团</p></div>';return}
    el.innerHTML='<div style="margin-bottom:10px;font-size:12px;color:var(--text-dim)">共 '+(d.total||d.communities.length)+' 个团</div>'+
      d.communities.map(function(c){
        var nm=c.nickname||c.username||'用户#'+c.creator_id;
        var st=c.status==='pending'?'<span style="color:orange">[待审核]</span>':c.status==='approved'?'<span style="color:var(--accent)">[已通过]</span>':'<span style="color:var(--danger)">[已拒绝]</span>';
        return '<div class="card" style="display:flex;justify-content:space-between;align-items:center"><div style="flex:1;overflow:hidden"><div style="color:var(--text-bright);font-weight:600">'+esc(c.name)+' '+st+'</div><div style="color:var(--text-dim);font-size:12px">创建者: '+esc(nm)+' · 成员: '+(c.member_count||0)+'</div></div><button class="btn btn-sm btn-danger" data-del-com="'+c.id+'" data-del-name="'+c.name+'">删除</button></div>';
      }).join('');
    el.querySelectorAll('[data-del-com]').forEach(function(b){
      b.onclick=function(){
        if(!confirm('确定删除团 "'+this.getAttribute('data-del-name')+'"？将删除所有数据！'))return;
        if(!confirm('再次确认？不可恢复！'))return;
        fetch('/api/community/dissolve',{method:'POST',headers:{"Content-Type":"application/json"},body:JSON.stringify({community_id:this.getAttribute('data-del-com'),user_id:USER_ID})}).then(function(r){return r.json()}).then(function(d){if(d.success){toast('已删除');loadAllCommunities()}else toast(d.error||'操作失败')}).catch(function(){toast('请求失败')});
      };
    });
  }).catch(function(){el.innerHTML='<div class="placeholder-box"><p>加载失败</p></div>'});
}

function loadPasswordResets() {
  var el = byId('adminSectionContent');
  if (!el || !ADMIN_TOKEN) return;
  el.innerHTML = '<div class="placeholder-box"><i class="fas fa-spinner fa-spin"></i><p>加载中...</p></div>';

  fetch('/api/admin/password-resets', {headers: {'Authorization': 'Bearer ' + ADMIN_TOKEN}})
    .then(function(r) { return r.json(); })
    .then(function(d) {
      if (!d.success || !d.resets || !d.resets.length) {
        el.innerHTML = '<div class="placeholder-box"><i class="fas fa-check-circle" style="color:var(--accent)"></i><p>暂无重置请求</p></div>';
        return;
      }
      el.innerHTML = '<div style="margin-bottom:10px;font-size:12px;color:var(--text-dim)"><i class="fas fa-key"></i> 密码重置请求</div>' +
        d.resets.map(function(r) {
          var statusMap = {'pending': '<span style="color:orange">待审核</span>', 'approved': '<span style="color:var(--accent)">已批准</span>', 'used': '<span style="color:var(--text-dim)">已使用</span>', 'expired': '<span style="color:var(--danger)">已过期</span>'};
          var actions = '';
          if (r.status === 'pending') {
            actions = '<button class="btn btn-sm btn-accent" data-aprv-r="'+r.id+'">批准</button><button class="btn btn-sm btn-danger" data-rej-r="'+r.id+'" style="margin-left:4px">拒绝</button>';
          }
          return '<div class="card" style="display:flex;justify-content:space-between;align-items:center"><div><div style="color:var(--text-bright);font-weight:600">'+esc(r.username)+'</div><div style="color:var(--text-dim);font-size:12px">请求时间: '+(r.requested_at||'')+' · '+(statusMap[r.status]||r.status)+'</div></div><div>'+actions+'</div></div>';
        }).join('');

      el.querySelectorAll('[data-aprv-r]').forEach(function(b) {
        b.onclick = function() {
          fetch('/api/admin/approve-reset', {method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+ADMIN_TOKEN},body:JSON.stringify({reset_id:parseInt(this.getAttribute('data-aprv-r')),action:'approve'})})
            .then(function(r){return r.json()}).then(function(d){if(d.success){toast('已批准');loadPasswordResets()}else toast(d.error||'操作失败')}).catch(function(){});
        };
      });
      el.querySelectorAll('[data-rej-r]').forEach(function(b) {
        b.onclick = function() {
          fetch('/api/admin/approve-reset', {method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+ADMIN_TOKEN},body:JSON.stringify({reset_id:parseInt(this.getAttribute('data-rej-r')),action:'reject'})})
            .then(function(r){return r.json()}).then(function(d){if(d.success){toast('已拒绝');loadPasswordResets()}else toast(d.error||'操作失败')}).catch(function(){});
        };
      });
    }).catch(function() {el.innerHTML = '<div class="placeholder-box"><p>加载失败</p></div>';});
}

/* 全局函数列表以供 manage.php 等页面引用 */
window.UID=USER_ID;
window.CID=0;

// ===== Notification Center =====
var NOTIF_POLL = null;

function initNotifications() {
  var bell = byId('notifBell');
  if (!bell) return;

  // Toggle dropdown
  bell.onclick = function(e) {
    e.stopPropagation();
    var dd = byId('notifDropdown');
    if (dd) dd.classList.toggle('active');
    if (dd && dd.classList.contains('active')) {
      loadNotifications();
    }
  };

  // Close dropdown when clicking outside
  document.addEventListener('click', function(e) {
    var dd = byId('notifDropdown');
    var b = byId('notifBell');
    if (dd && b && !b.contains(e.target)) {
      dd.classList.remove('active');
    }
  });

  // Mark all as read
  var markBtn = byId('notifMarkAllRead');
  if (markBtn) {
    markBtn.onclick = function(e) {
      e.stopPropagation();
      markAllNotificationsRead();
    };
  }

  // View more
  var more = byId('notifMore');
  if (more) {
    more.onclick = function(e) {
      e.stopPropagation();
      toast('通知列表已展开');
    };
  }

  // Start polling for unread count
  loadNotifCount();
  if (NOTIF_POLL) clearInterval(NOTIF_POLL);
  NOTIF_POLL = setInterval(loadNotifCount, 15000);
}

function loadNotifCount() {
  fetch('/api/user/notifications/count?user_id=' + USER_ID)
    .then(function(r) { return r.json(); })
    .then(function(d) {
      if (!d.success) return;
      var total = (d.unread_messages || 0) + (d.pending_requests || 0);
      // Also get unread notifications count
      fetch('/api/user/notifications/list?user_id=' + USER_ID + '&limit=1')
        .then(function(r2) { return r2.json(); })
        .then(function(d2) {
          if (d2.success) {
            total = d2.unread || 0;
          }
          updateNotifBadge(total);
        })
        .catch(function() {
          updateNotifBadge(total);
        });
    })
    .catch(function() {});
}

function updateNotifBadge(count) {
  var badge = byId('notifBadge');
  if (!badge) return;
  if (count > 0) {
    badge.style.display = 'flex';
    badge.textContent = count > 99 ? '99+' : count;
  } else {
    badge.style.display = 'none';
  }
}

function loadNotifications() {
  var list = byId('notifList');
  if (!list) return;
  list.innerHTML = '<div class="notif-loading"><i class="fas fa-spinner fa-spin"></i> 加载中...</div>';

  fetch('/api/user/notifications/list?user_id=' + USER_ID + '&limit=20')
    .then(function(r) { return r.json(); })
    .then(function(d) {
      if (!d.success || !d.notifications || !d.notifications.length) {
        list.innerHTML = '<div class="notif-empty"><i class="fas fa-bell-slash"></i><div>暂无通知</div></div>';
        return;
      }

      list.innerHTML = d.notifications.map(function(n) {
        var iconHtml = getNotifIcon(n.type);
        var time = n.created_at || '';
        var unreadCls = n.is_read ? '' : ' unread';
        return '<div class="notif-item' + unreadCls + '" data-nid="' + n.id + '">' +
          '<div class="notif-item-icon" style="background:' + iconHtml.bg + ';color:' + iconHtml.color + '">' +
          '<i class="fas ' + iconHtml.icon + '"></i></div>' +
          '<div class="notif-item-content">' +
          '<div class="notif-item-title">' + esc(n.title || '') + '</div>' +
          '<div class="notif-item-desc">' + esc(n.content || '') + '</div>' +
          '<div class="notif-item-time">' + esc(time) + '</div>' +
          '</div></div>';
      }).join('');

      // Click to mark as read
      list.querySelectorAll('.notif-item.unread').forEach(function(item) {
        item.onclick = function() {
          var nid = this.getAttribute('data-nid');
          fetch('/api/user/notifications/read', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: USER_ID, notification_id: parseInt(nid) })
          }).then(function(r) { return r.json(); }).then(function(d) {
            if (d.success) {
              item.classList.remove('unread');
              loadNotifCount();
            }
          }).catch(function() {});
        };
      });
    })
    .catch(function() {
      list.innerHTML = '<div class="notif-empty"><i class="fas fa-exclamation-triangle"></i><div>加载失败</div></div>';
    });
}

function getNotifIcon(type) {
  var icons = {
    'friend_request': { icon: 'fa-user-plus', bg: 'rgba(0,122,255,0.12)', color: '#007aff' },
    'friend_accepted': { icon: 'fa-user-check', bg: 'rgba(52,199,89,0.12)', color: '#34c759' },
    'friend_rejected': { icon: 'fa-user-times', bg: 'rgba(255,59,48,0.12)', color: '#ff3b30' },
    'community_post': { icon: 'fa-file-alt', bg: 'rgba(90,200,250,0.12)', color: '#5ac8fa' },
    'community_approved': { icon: 'fa-check-circle', bg: 'rgba(52,199,89,0.12)', color: '#34c759' },
    'community_rejected': { icon: 'fa-times-circle', bg: 'rgba(255,59,48,0.12)', color: '#ff3b30' },
    'community_joined': { icon: 'fa-users', bg: 'rgba(0,122,255,0.12)', color: '#007aff' },
    'community_join_request': { icon: 'fa-user-plus', bg: 'rgba(255,149,0,0.12)', color: '#ff9500' },
    'community_create_request': { icon: 'fa-plus-circle', bg: 'rgba(90,200,250,0.12)', color: '#5ac8fa' },
    'system': { icon: 'fa-info-circle', bg: 'rgba(142,142,147,0.12)', color: '#8e8e93' }
  };
  return icons[type] || { icon: 'fa-bell', bg: 'rgba(142,142,147,0.12)', color: '#8e8e93' };
}

function markAllNotificationsRead() {
  fetch('/api/user/notifications/read', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ user_id: USER_ID })
  }).then(function(r) { return r.json(); }).then(function(d) {
    if (d.success) {
      toast('已全部标记为已读');
      loadNotifCount();
      // Remove unread class from all items
      var items = qsa('.notif-item.unread');
      items.forEach(function(item) { item.classList.remove('unread'); });
    }
  }).catch(function() {});
}

// ===== Online Status =====
function startHeartbeat() {
  if (window._heartbeatInterval) clearInterval(window._heartbeatInterval);

  function beat() {
    fetch('/api/user/heartbeat', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({user_id: USER_ID})
    }).catch(function() {});
  }

  beat(); // Immediate first beat
  window._heartbeatInterval = setInterval(beat, 60000); // Every 60 seconds
}

// 初始化
initTopbar();

initNotifications();

initSidebar();
bindEvents();

// Start heartbeat for online status
startHeartbeat();

// 默认显示发现页
// showPanel('discover'); -- 默认留在欢迎页
