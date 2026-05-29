<?php
require_once __DIR__ . '/../includes/community_config.php';
$postId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$postId) { header('Location: /'); exit; }

$resp = @file_get_contents('http://127.0.0.1:5000/api/community/posts?id=' . $postId);
$data = json_decode($resp, true);
$post = ($data && $data['success'] && $data['post']) ? $data['post'] : null;
if (!$post) { echo '<p style="text-align:center;padding:40px;color:#8e8e93">内容不存在</p>'; exit; }

$user = getCurrentCommunityUser();
$userId = getCurrentCommunityUserId();
$isLoggedIn = $userId > 0;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?=htmlspecialchars($post['title'])?> - 菜籽游</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#f4f4f4;--bg2:#fff;--accent:#00a1d6;--accent-dim:rgba(0,161,214,0.12);--accent-hover:#23ade5;--text:#18191c;--text-dim:#9499a0;--text-bright:#000;--radius:10px;--radius-lg:14px;--font:-apple-system,BlinkMacSystemFont,'SF Pro','Helvetica Neue',system-ui,sans-serif;--shadow:0 1px 2px rgba(0,0,0,0.1);--shadow-lg:0 2px 8px rgba(0,0,0,0.1)}
html,body{height:100%;background:var(--bg);color:var(--text);font:15px/1.6 var(--font);-webkit-font-smoothing:antialiased}
a{color:var(--accent);text-decoration:none}

/* 顶部导航 */
.top-bar{position:sticky;top:0;z-index:50;background:#fff;box-shadow:var(--shadow)}
.top-bar-inner{max-width:1360px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;padding:0 20px;height:56px}
.top-bar-l{display:flex;align-items:center;gap:12px}
.top-bar-logo{font-size:18px;font-weight:700;color:var(--accent)}
.top-bar-logo i{font-size:20px}
.top-bar-r{display:flex;align-items:center;gap:12px}
.top-bar-user{width:32px;height:32px;border-radius:50%;overflow:hidden;background:#eee;display:flex;align-items:center;justify-content:center;color:var(--text-dim);font-size:14px;cursor:pointer}
.top-bar-user img{width:100%;height:100%;object-fit:cover}

/* 主布局 */
.main-layout{max-width:1360px;margin:0 auto;padding:20px;display:flex;gap:20px}

/* 左侧 */
.content-left{flex:1;min-width:0}

/* 内容区 */
.content-card{background:#fff;border-radius:var(--radius-lg);overflow:hidden;box-shadow:var(--shadow)}
.content-card + .content-card{margin-top:16px}

/* 媒体区 */
.media-area{position:relative;width:100%;max-height:600px;overflow:hidden;background:#000}
.media-area video{display:block;width:100%;max-height:600px}
.media-area img{display:block;width:100%;max-height:600px;object-fit:contain}
.media-area .image-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:2px;padding:2px}
.media-area .image-grid img{width:100%;max-height:500px;object-fit:contain;background:#000;cursor:pointer;transition:opacity .2s}
.media-area .image-grid img:hover{opacity:.85}
/* 图片全屏查看 */
.img-fullscreen-overlay{position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,.92);z-index:99999;display:flex;align-items:center;justify-content:center;cursor:zoom-out}.img-fullscreen-overlay img{max-width:95vw;max-height:95vh;object-fit:contain;border-radius:8px;box-shadow:0 4px 40px rgba(0,0,0,.5)}.img-fullscreen-toolbar{position:fixed;bottom:30px;left:50%;transform:translateX(-50%);display:flex;gap:12px;z-index:100000}.img-fullscreen-toolbar button{background:rgba(255,255,255,.85);color:#000;border:none;border-radius:20px;padding:8px 18px;font-size:13px;cursor:pointer;backdrop-filter:blur(8px);transition:all .2s;font-weight:500}.img-fullscreen-toolbar button:hover{background:#fff}
.media-area .gradient-cover{height:240px;display:flex;align-items:center;justify-content:center;font-size:72px;color:rgba(255,255,255,0.4);font-weight:700}
.document-viewer{background:#fafafa;border-bottom:1px solid #f0f0f0}
.doc-toolbar{display:flex;align-items:center;gap:10px;padding:12px 20px;background:#fff;border-bottom:1px solid #e8e8e8;position:sticky;top:0;z-index:5}
.doc-icon{width:36px;height:36px;border-radius:8px;background:var(--accent-dim);display:flex;align-items:center;justify-content:center;color:var(--accent);font-size:16px}
.doc-filename{font-size:13px;font-weight:500;color:var(--text);flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.doc-download-btn{font-size:12px;padding:5px 14px;border-radius:6px;background:var(--accent);color:#fff;border:none;cursor:pointer;text-decoration:none}
.doc-content{padding:16px 24px;max-height:600px;overflow-y:auto}
.doc-content pre{font-size:13px;line-height:1.7;color:#333;white-space:pre-wrap;word-wrap:break-word;font-family:'SF Mono','Cascadia Code','Menlo',monospace;margin:0}

/* 标题区 */
.title-section{padding:16px 20px 12px}
.title-section h1{font-size:20px;font-weight:700;line-height:1.4}
.meta-row{display:flex;align-items:center;gap:16px;margin-top:8px;font-size:13px;color:var(--text-dim)}

/* UP主栏 */
.up-section{display:flex;align-items:center;justify-content:space-between;padding:12px 20px;border-top:1px solid #f0f0f0}
.up-left{display:flex;align-items:center;gap:10px;cursor:pointer}
.up-avatar{width:40px;height:40px;border-radius:50%;overflow:hidden;flex-shrink:0;background:#eee;display:flex;align-items:center;justify-content:center;color:var(--text-dim);font-size:16px}
.up-avatar img{width:100%;height:100%;object-fit:cover}
.up-info{font-size:14px;font-weight:500;line-height:1.3}
.up-info small{display:block;font-size:12px;color:var(--text-dim);font-weight:400}

/* 点赞区 */
.action-bar{display:flex;align-items:center;gap:12px;padding:12px 20px;border-top:1px solid #f0f0f0}
.like-btn{display:flex;align-items:center;gap:6px;padding:6px 16px;border-radius:20px;border:none;cursor:pointer;font-size:13px;font-weight:500;transition:.15s;background:#f4f4f4;color:var(--text-dim)}
.like-btn:hover{opacity:.8}
.like-btn.liked{background:var(--accent-dim);color:var(--accent)}
.like-btn i{font-size:15px}

/* 简介 */
.desc-section{padding:16px 20px;font-size:14px;line-height:1.7;color:#555;border-top:1px solid #f0f0f0}

/* 评论区 */
.comments-section{padding:16px 20px}
.comments-title{font-size:15px;font-weight:600;margin-bottom:12px}

.comment-input-area{display:flex;gap:8px;margin-bottom:16px}
.comment-input-area textarea{flex:1;border:1px solid #e4e4e4;border-radius:8px;padding:8px 12px;font-size:13px;outline:none;resize:none;font-family:inherit;line-height:1.5;min-height:36px}
.comment-input-area textarea:focus{border-color:var(--accent)}
.comment-input-area button{padding:6px 16px;background:var(--accent);color:#fff;border:none;border-radius:8px;font-size:13px;cursor:pointer;white-space:nowrap}
.comment-input-area button:disabled{opacity:.5;cursor:default}

.comment-item{display:flex;gap:10px;padding:10px 0;border-bottom:1px solid #f4f4f4}
.comment-avatar{width:32px;height:32px;border-radius:50%;overflow:hidden;flex-shrink:0;background:#eee;display:flex;align-items:center;justify-content:center;color:var(--text-dim);font-size:12px}
.comment-avatar img{width:100%;height:100%;object-fit:cover}
.comment-body{flex:1;min-width:0}
.comment-author{font-size:13px;font-weight:500;display:flex;align-items:center;gap:8px}
.comment-author .comment-del{font-size:11px;color:var(--text-dim);cursor:pointer;border:none;background:none;padding:0}
.comment-author .comment-del:hover{color:var(--danger)}
.comment-text{font-size:13px;color:#555;margin-top:2px;line-height:1.5}
.comment-time{font-size:11px;color:var(--text-dim);margin-top:4px}

/* 右侧 */
.side-right{width:280px;flex-shrink:0;display:flex;flex-direction:column;gap:16px}

/* UP主侧栏卡 */
.up-side-card{background:#fff;border-radius:var(--radius-lg);padding:20px;box-shadow:var(--shadow)}
.up-side-card .up-row{display:flex;align-items:center;gap:12px}
.up-side-card .up-row .up-avatar{width:48px;height:48px;font-size:20px}
.up-side-card .up-row .up-info{font-size:15px}
.up-side-card .up-row .up-info small{font-size:12px}
.up-side-btn{width:100%;margin-top:12px;padding:7px 0;border-radius:8px;border:none;font-size:13px;cursor:pointer;background:var(--accent);color:#fff;transition:.15s}

/* 加载占位 */
.loading-box{padding:40px;text-align:center;color:var(--text-dim);font-size:14px}

/* 名片弹窗 */
.modal-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:9999;display:none;align-items:center;justify-content:center}
.modal-overlay.active{display:flex}
.toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#1e1e2a;color:#eee;padding:10px 20px;border-radius:8px;z-index:99999;font-size:13px;border:1px solid #2a2a3a}

/* 响应式 */
@media(max-width:900px){
  .main-layout{flex-direction:column;padding:12px}
  .side-right{width:100%}
  .content-card{border-radius:var(--radius)}
  .title-section h1{font-size:18px}
}
</style>
</head>
<body>

<!-- 顶部导航 -->
<header class="top-bar">
  <div class="top-bar-inner">
    <div class="top-bar-l">
      <a href="/" class="top-bar-logo"><i class="fa fa-play-circle"></i> 菜籽游</a>
    </div>
    <div class="top-bar-r">
      <?php if ($isLoggedIn): ?>
      <div class="top-bar-user" onclick="window.open('/','_self')">
        <i class="fas fa-home"></i>
      </div>
      <?php else: ?>
      <a href="/login.php" style="font-size:13px;color:var(--accent)">登录</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<!-- 主布局 -->
<div class="main-layout">

  <!-- 左侧 -->
  <div class="content-left">

    <!-- 媒体区 -->
    <div class="content-card">
      <div class="media-area">
        <?php if ($post['content_type'] === 'video'): ?>
          <video controls autoplay loop>
            <source src="<?=htmlspecialchars($post['content_url'])?>" type="video/mp4">
          </video>
        <?php endif; ?>
        <?php
          $images = [];
          if ($post['images']) {
            $images = json_decode($post['images'], true);
          }
          if ($post['cover_url']) {
            array_unshift($images, $post['cover_url']);
          }
        ?>
        <?php if ($post['content_type'] === 'image' && $images): ?>
          <div class="image-grid">
            <?php foreach ($images as $img): ?>
            <img src="<?=htmlspecialchars($img)?>" loading="lazy" onclick="viewImage(this.src)">
            <?php endforeach; ?>
          </div>
        <?php elseif ($post['content_type'] === 'document' && $post['content_url']): ?>
          <?php
            $docContent = @file_get_contents($_SERVER['DOCUMENT_ROOT'] . $post['content_url']);
          ?>
          <div class="document-viewer">
            <div class="doc-toolbar">
              <span class="doc-icon"><i class="fas fa-file-alt"></i></span>
              <span class="doc-filename"><?=htmlspecialchars(basename($post['content_url']))?></span>
              <a href="<?=htmlspecialchars($post['content_url'])?>" class="doc-download-btn" download><i class="fas fa-download"></i> 下载</a>
            </div>
            <div class="doc-content"><?php if ($docContent !== false): ?><pre><?=htmlspecialchars($docContent)?></pre><?php else: ?><p style="color:var(--text-dim);padding:20px;text-align:center">文档内容无法加载</p><?php endif; ?></div>
          </div>
        <?php elseif (!$images && $post['content_type'] !== 'video'): ?>
          <?php
            $colors = ['#5b86e5','#36d1dc','#ff6b6b','#f093fb','#4facfe','#43e97b','#fa709a','#a18cd1'];
            $ci = $postId % count($colors);
          ?>
          <div class="gradient-cover" style="background:linear-gradient(135deg,<?=$colors[$ci]?>,<?=$colors[($ci+3)%count($colors)]?>)">
            <?=htmlspecialchars(mb_substr($post['title'],0,1,'UTF-8')?:'·')?>
          </div>
        <?php endif; ?>
      </div>

      <!-- 标题 -->
      <div class="title-section">
        <h1><?=htmlspecialchars($post['title'])?></h1>
        <div class="meta-row">
          <span class="community-tag" id="communityTag" style="cursor:pointer;display:flex;align-items:center;gap:4px">
            <i class="fas fa-users"></i> <?=htmlspecialchars($post['community_name']??'')?> <span id="communityJoinHint" style="font-size:11px"></span>
          </span>
          <span><?=htmlspecialchars(substr($post['created_at']??'',0,10))?></span>
        </div>
      </div>

      <!-- 描述 -->
      <?php if ($post['description']): ?>
      <div class="desc-section"><?=nl2br(htmlspecialchars($post['description']))?></div>
      <?php endif; ?>

      <!-- UP主栏 -->
      <div class="up-section">
        <div class="up-left" id="authorTag">
          <div class="up-avatar">
            <?php if ($post['author_avatar'] && $post['author_avatar'] !== '/assets/images/default-avatar.png'): ?>
            <img src="<?=htmlspecialchars($post['author_avatar'])?>">
            <?php else: ?>
            <i class="fas fa-user"></i>
            <?php endif; ?>
          </div>
          <div class="up-info">
            <?=htmlspecialchars($post['author_nickname']??'用户#'.$post['created_by'])?>
            <small>ID: <?=intval($post['created_by'])?></small>
          </div>
        </div>
      </div>

      <!-- 点赞 -->
      <div class="action-bar">
        <button class="like-btn" id="likeBtn" onclick="toggleLike()">
          <i class="far fa-heart" id="likeIcon"></i>
          <span id="likeCount">0</span>
        </button>
      </div>

      <!-- 评论 -->
      <div class="comments-section">
        <div class="comments-title">评论</div>
        <?php if ($isLoggedIn): ?>
        <div class="comment-input-area">
          <textarea id="commentInput" rows="1" placeholder="发一条友善的评论~" oninput="this.style.height='';this.style.height=Math.min(this.scrollHeight,120)+'px'"></textarea>
          <button id="commentSubmitBtn" onclick="submitComment()">发送</button>
        </div>
        <?php else: ?>
        <div style="text-align:center;padding:12px;font-size:13px;color:var(--text-dim)">
          <a href="/login.php">登录</a> 后才能评论
        </div>
        <?php endif; ?>
        <div id="commentList"></div>
      </div>

    </div>
  </div>

  <!-- 右侧 -->
  <div class="side-right">
    <div class="up-side-card">
      <div class="up-row" id="authorSideTag" style="cursor:pointer">
        <div class="up-avatar">
          <?php if ($post['author_avatar'] && $post['author_avatar'] !== '/assets/images/default-avatar.png'): ?>
          <img src="<?=htmlspecialchars($post['author_avatar'])?>">
          <?php else: ?>
          <i class="fas fa-user"></i>
          <?php endif; ?>
        </div>
        <div class="up-info">
          <?=htmlspecialchars($post['author_nickname']??'用户#'.$post['created_by'])?>
          <small>ID: <?=intval($post['created_by'])?></small>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
function viewImage(src){
  var ov=document.createElement('div');ov.className='img-fullscreen-overlay';
  ov.innerHTML='<img src="'+src+'" onclick="closeFullscreen()"><div class="img-fullscreen-toolbar"><button onclick="event.stopPropagation();closeFullscreen()"><i class="fas fa-times"></i> 关闭</button><button onclick="event.stopPropagation();downloadImage(this)"><i class="fas fa-download"></i> 下载</button></div>';
  document.body.appendChild(ov);
  ov.onclick=function(){closeFullscreen()};
}
function closeFullscreen(){
  var el=document.querySelector('.img-fullscreen-overlay');
  if(el){el.remove();}
}
function downloadImage(btn){
  var img=btn.closest('.img-fullscreen-overlay').querySelector('img');
  var a=document.createElement('a');a.href=img.src;a.download=img.src.split('/').pop()||'image';a.click();
toast('正在下载');
}
var POST_ID = <?=$postId?>;
var USER_ID = <?=$userId ?: 0?>;
var UID = <?=$post['created_by']?>;

function esc(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;')}
function $(id){return document.getElementById(id)}
function toast(m){var t=document.createElement('div');t.className='toast';t.textContent=m;document.body.appendChild(t);setTimeout(function(){t.remove()},2000)}

// ===== 点赞 =====
var liked = false;

function loadLikes(){
  var url='/api/post/likes?post_id='+POST_ID;
  if(USER_ID)url+='&user_id='+USER_ID;
  fetch(url).then(function(r){return r.json()}).then(function(d){
    if(!d.success)return;
    $('likeCount').textContent=d.count||0;
    liked=d.liked||false;
    var btn=$('likeBtn'),icon=$('likeIcon');
    if(liked){btn.classList.add('liked');icon.className='fas fa-heart'}
    else{btn.classList.remove('liked');icon.className='far fa-heart'}
  }).catch(function(){});
}

function toggleLike(){
  if(!USER_ID){toast('请先登录');return}
  fetch('/api/post/like',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({post_id:POST_ID,user_id:USER_ID})}).then(function(r){return r.json()}).then(function(d){
    if(!d.success){toast(d.error||'操作失败');return}
    liked=d.liked;
    var btn=$('likeBtn'),icon=$('likeIcon');
    if(liked){btn.classList.add('liked');icon.className='fas fa-heart';$('likeCount').textContent=parseInt($('likeCount').textContent)+1}
    else{btn.classList.remove('liked');icon.className='far fa-heart';$('likeCount').textContent=Math.max(0,parseInt($('likeCount').textContent)-1)}
  }).catch(function(){toast('请求失败')});
}

// ===== 评论 =====
function loadComments(){
  fetch('/api/post/comments?post_id='+POST_ID).then(function(r){return r.json()}).then(function(d){
    var el=$('commentList');
    if(!d.success||!d.comments||!d.comments.length){el.innerHTML='<div style="padding:16px 0;text-align:center;color:var(--text-dim);font-size:13px">暂无评论</div>';return}
    el.innerHTML=d.comments.map(function(c){
      var av=c.avatar_url&&c.avatar_url!=='/assets/images/default-avatar.png'?'<img src="'+c.avatar_url+'" onclick="showProfile('+c.user_id+')" style="cursor:pointer">':'<i class="fas fa-user" style="font-size:12px;cursor:pointer" onclick="showProfile('+c.user_id+')"></i>';
      var name=c.nickname||c.username||'用户#'+c.user_id;
      var delBtn=(c.user_id===USER_ID)?'<button class="comment-del" onclick="deleteComment('+c.id+')">删除</button>':'';
      return'<div class="comment-item"><div class="comment-avatar">'+av+'</div><div class="comment-body"><div class="comment-author">'+esc(name)+delBtn+'</div><div class="comment-text">'+esc(c.content)+'</div><div class="comment-time">'+esc(c.created_at||'')+'</div></div></div>';
    }).join('');
  }).catch(function(){});
}

function submitComment(){
  var inp=$('commentInput');
  if(!inp)return;
  var content=inp.value.trim();
  if(!content){toast('请输入评论内容');return}
  var btn=$('commentSubmitBtn');btn.disabled=true;
  fetch('/api/post/comment',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({post_id:POST_ID,user_id:USER_ID,content:content})}).then(function(r){return r.json()}).then(function(d){
    if(d.success){inp.value='';inp.style.height='auto';loadComments()}
    else toast(d.error||'发布失败');
    btn.disabled=false;
  }).catch(function(){toast('请求失败');btn.disabled=false});
}

function deleteComment(cid){
  if(!confirm('确定删除评论？'))return;
  fetch('/api/post/comment/delete',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({comment_id:cid,user_id:USER_ID})}).then(function(r){return r.json()}).then(function(d){
    if(d.success){toast('已删除');loadComments()}
    else toast(d.error||'删除失败');
  }).catch(function(){toast('请求失败')});
}

// ===== 团加入 =====
$('communityTag').onclick=function(e){
  e.stopPropagation();
  var cid=<?=$post['community_id']?>;
  fetch('/api/community/my-list?user_id='+USER_ID).then(function(r){return r.json()}).then(function(d){
    if(d.success&&d.communities){
      var joined=d.communities.some(function(c){return c.id==cid});
      if(joined){toast('已加入该团');return}
    }
    fetch('/api/community/join',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({community_id:cid,user_id:USER_ID})}).then(function(r){return r.json()}).then(function(d2){
      if(d2.success){toast(d2.message||'已加入！');$('communityJoinHint').textContent='✓'}
      else toast(d2.error||'加入失败')
    }).catch(function(){toast('请求失败')});
  }).catch(function(){toast('请求失败')});
};

// ===== 名片 =====
function showProfile(uid){
  if(!uid)return;
  var mo=$('profileModal');
  if(!mo){
    mo=document.createElement('div');mo.id='profileModal';mo.className='modal-overlay';
    mo.onclick=function(e){if(e.target===this)this.classList.remove('active')};
    document.body.appendChild(mo);
  }
  mo.classList.add('active');
  mo.innerHTML='<div class="profile-card" style="background:#fff;border-radius:20px;overflow:hidden;width:380px;max-width:90vw;box-shadow:0 8px 40px rgba(0,0,0,0.2);position:relative;z-index:10000"><div class="pc-cover" id="pcCover" style="height:120px;background:linear-gradient(135deg,#667eea,#764ba2);position:relative"><div class="pc-avatar" id="pcAvatar" style="position:absolute;left:50%;bottom:-36px;transform:translateX(-50%);width:72px;height:72px;border-radius:50%;border:3px solid #fff;background:#fff;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1)"><i class="fas fa-spinner fa-spin" style="font-size:28px;display:flex;align-items:center;justify-content:center;width:100%;height:100%;color:var(--text-dim)"></i></div></div><div class="pc-body" style="padding:48px 20px 20px;text-align:center"><div class="pc-nick" id="pcNick" style="font-size:17px;font-weight:700;color:var(--text-bright)">\u52a0\u8f7d\u4e2d...</div><div class="pc-name" id="pcName" style="font-size:12px;color:var(--text-dim);margin-top:2px"></div><div class="pc-id" id="pcId" style="font-size:11px;color:var(--text-dim);margin-top:2px"></div><div class="pc-bio" id="pcBio" style="font-size:13px;color:#3a3a3c;margin-top:12px;padding:0 4px;line-height:1.5"></div><div id="pcBtnArea"></div></div></div>';
  fetch('/api/user/profile?user_id='+uid).then(function(r){return r.json()}).then(function(d){
    if(!d.success||!d.user){var el=$('pcNick');if(el)el.textContent='\u52a0\u8f7d\u5931\u8d25';return}
    var u=d.user;
    var el=$('pcNick');if(el)el.textContent=u.nickname||u.username||'\u7528\u6237#'+uid;
    el=$('pcName');if(el)el.textContent='@'+(u.username||'');
    el=$('pcId');if(el)el.textContent='ID: '+uid;
    el=$('pcBio');if(el)el.textContent=u.bio||'\u8fd9\u4e2a\u4eba\u5f88\u61d2\uff0c\u4ec0\u4e48\u90fd\u6ca1\u5199';
    var bg=u.profile_bg||'#667eea,#764ba2';
    var cv=$('pcCover');if(cv)cv.style.background='linear-gradient(135deg,'+bg+')';
    var avEl=$('pcAvatar');
    if(u.avatar_url&&u.avatar_url!='/assets/images/default-avatar.png')avEl.innerHTML='<img src="'+u.avatar_url+'" style="width:100%;height:100%;object-fit:cover;border-radius:50%">';
    else avEl.innerHTML='<i class="fas fa-user" style="font-size:28px;color:var(--text-dim);display:flex;align-items:center;justify-content:center;width:100%;height:100%"></i>';
    var ba=$('pcBtnArea');ba.innerHTML='';
    if(uid!=USER_ID){
      var btn=document.createElement('button');btn.style.cssText='padding:8px 20px;border-radius:20px;font-size:13px;margin-top:10px;background:var(--accent);color:#fff;border:none;cursor:pointer';
      btn.innerHTML='<i class="fas fa-user-plus"></i> \u6dfb\u52a0\u597d\u53cb';
      btn.onclick=function(){fetch('/api/friends/apply',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({user_id:USER_ID,friend_id:uid})}).then(function(r){return r.json()}).then(function(d2){if(d2.success){toast('\u5df2\u53d1\u9001\u7533\u8bf7');mo.classList.remove('active')}else toast(d2.error||'\u5931\u8d25')}).catch(function(){toast('\u8bf7\u6c42\u5931\u8d25')})};
      ba.appendChild(btn);
    }
  }).catch(function(){var el=$('pcNick');if(el)el.textContent='\u8bf7\u6c42\u5931\u8d25'});
}

// 头像/UP主点击出名片
if($('authorTag'))$('authorTag').onclick=function(){showProfile(UID)};
if($('authorSideTag'))$('authorSideTag').onclick=function(){showProfile(UID)};

// ===== 入口 =====
loadLikes();
loadComments();
</script>
</body>
</html>
