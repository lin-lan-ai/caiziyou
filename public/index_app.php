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
:root{--bg:#f5f5f7;--bg2:#ffffff;--bg3:#eeedf0;--surface:rgba(255,255,255,0.7);--surface2:#e8e8ed;--accent:#007aff;--accent-dim:rgba(0,122,255,0.12);--text:#1c1c1e;--text-dim:#8e8e93;--text-bright:#000;--danger:#ff3b30;--sidebar-w:120px;--radius:10px;--radius-lg:16px;--font:-apple-system,BlinkMacSystemFont,'SF Pro','Helvetica Neue',system-ui,sans-serif;--shadow:0 2px 12px rgba(0,0,0,0.06);--shadow-lg:0 8px 30px rgba(0,0,0,0.1)}
html,body{height:100%;background:var(--bg);color:var(--text);font:15px/1.6 var(--font);overflow:hidden;-webkit-font-smoothing:antialiased}
body{padding-top:60px}
a{color:var(--accent);text-decoration:none}
::-webkit-scrollbar{width:6px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:var(--surface2);border-radius:3px}
#app{display:flex;flex-direction:column;height:100vh;width:100vw}
.topbar{display:flex;align-items:center;height:52px;flex-shrink:0;background:rgba(255,255,255,0.8);-webkit-backdrop-filter:blur(20px);backdrop-filter:blur(20px);border-bottom:1px solid rgba(0,0,0,0.06);padding:0 16px;z-index:9999;position:fixed;top:0;left:0;right:0}
.topbar-left{display:flex;align-items:center;margin-right:24px;flex-shrink:0}
.topbar-logo{font-weight:700;font-size:17px;color:var(--text-bright);letter-spacing:-0.3px}
.topbar-nav{display:flex;align-items:center;gap:2px;flex:1}
.topbar-item{display:flex;align-items:center;gap:6px;padding:8px 14px;cursor:pointer;transition:.2s;color:var(--text-dim);font-size:13px;font-weight:500;border-radius:6px}
.topbar-item i{font-size:15px}
.topbar-item:hover{color:var(--text);background:rgba(0,0,0,0.04)}
.topbar-item.active{color:var(--accent);background:var(--accent-dim)}
.topbar-item span{white-space:nowrap}
.topbar-right{display:flex;align-items:center;position:relative;margin-left:16px;flex-shrink:0}
.topbar-nav-mobile{display:none;align-items:center;position:relative}
.topbar-nav-trigger{display:flex;align-items:center;gap:4px;background:none;border:none;color:var(--text-dim);font-size:15px;cursor:pointer;padding:6px 10px;border-radius:6px;transition:.2s}
.topbar-nav-trigger:hover{color:var(--text);background:rgba(0,0,0,0.04)}
.topbar-nav-trigger i{font-size:16px}
.topbar-nav-dropdown{position:absolute;top:calc(100% + 8px);left:0;background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.12);border:1px solid rgba(0,0,0,0.06);min-width:160px;display:none;z-index:50;overflow:hidden}
.topbar-nav-dropdown.open{display:block}
.topbar-avatar{cursor:pointer;border-radius:50%;border:2px solid transparent;transition:.2s;display:flex}
.topbar-avatar:hover{border-color:var(--accent-dim)}
.topbar-avatar.open{border-color:var(--accent)}
.topbar-dropdown{position:absolute;top:calc(100% + 8px);right:0;background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.12);border:1px solid rgba(0,0,0,0.06);min-width:140px;display:none;z-index:50;overflow:hidden}
.topbar-dropdown.open{display:block}
.dropdown-item{display:flex;align-items:center;gap:8px;padding:10px 16px;cursor:pointer;transition:.1s;font-size:13px;color:var(--text)}
.dropdown-item:hover{background:var(--accent-dim)}
.settings-section{margin-bottom:24px}
.settings-section-title{font-size:15px;font-weight:600;color:var(--text-bright);margin-bottom:12px;display:flex;align-items:center;gap:8px}
.settings-card{background:#fff;border-radius:14px;border:1px solid rgba(0,0,0,0.04);padding:16px;box-shadow:0 1px 4px rgba(0,0,0,0.04)}
.settings-row{display:flex;align-items:center;gap:12px;padding:8px 0;border-bottom:1px solid rgba(0,0,0,0.04)}
.settings-row:last-child{border-bottom:none}
.settings-label{font-size:13px;color:var(--text-dim);flex-shrink:0;width:80px}
.settings-value{font-size:14px;color:var(--text);display:flex;align-items:center;gap:8px}
.settings-input{border:1px solid rgba(0,0,0,0.08);border-radius:8px;padding:8px 12px;font-size:14px;outline:none;transition:.2s;background:var(--bg);color:var(--text)}
.settings-input:focus{border-color:var(--accent)}
.settings-avatar{width:40px;height:40px;border-radius:50%;background:var(--accent-dim);display:flex;align-items:center;justify-content:center;color:var(--accent);font-size:18px;flex-shrink:0}
.log-row{display:flex;gap:12px;padding:8px 0;font-size:13px;border-bottom:1px solid rgba(0,0,0,0.03)}
.log-row:last-child{border-bottom:none}
.log-date{color:var(--text-dim);white-space:nowrap;flex-shrink:0}
.log-action{color:var(--accent);flex-shrink:0}
.log-detail{color:var(--text)}
.content-area{flex:1;position:relative;overflow:hidden;background:var(--bg)}
.panel-default{position:absolute;inset:16px;display:flex;align-items:center;justify-content:center;z-index:0;text-align:center}
.panel-default i{font-size:52px;color:var(--accent);margin-bottom:16px}
.panel-default p{font-size:18px;color:var(--text-bright)}
.panel-default small{font-size:13px;color:var(--text-dim);margin-top:8px}
.float-panel{position:absolute;inset:0;background:var(--bg);display:none;flex-direction:column;overflow:hidden;z-index:10;animation:panelIn .2s cubic-bezier(.25,.1,.25,1)}
.float-panel.active{display:flex!important}
@keyframes panelIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
.panel-bar{display:flex;align-items:center;gap:12px;padding:14px 24px;background:rgba(255,255,255,0.65);-webkit-backdrop-filter:blur(20px);backdrop-filter:blur(20px);border-bottom:1px solid rgba(0,0,0,0.06);flex-shrink:0;font-weight:600;font-size:16px;color:var(--text-bright);z-index:2}
.panel-bar.admin-panel{padding-left:88px}
.panel-bar span{flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.panel-bar-close{background:rgba(0,0,0,0.06);border:none;color:var(--text-dim);font-size:20px;cursor:pointer;transition:.2s;line-height:1;padding:2px 10px;border-radius:20px;flex-shrink:0}
.panel-bar-close:hover{background:rgba(0,0,0,0.12);color:var(--danger)}
.panel-scroll{flex:1;overflow-y:auto;padding:24px}
.card{background:var(--surface);border:1px solid rgba(0,0,0,0.04);border-radius:var(--radius-lg);padding:16px;margin-bottom:12px;transition:.2s;box-shadow:var(--shadow)}
.card:hover{border-color:var(--accent);box-shadow:var(--shadow-lg)}
.placeholder-box{text-align:center;padding:60px 20px;color:var(--text-dim)}
.placeholder-box i{font-size:40px;margin-bottom:12px;color:var(--accent-dim)}

.tool-output{background:rgba(0,0,0,0.03);border-radius:var(--radius);padding:8px 12px;font-size:13px;color:var(--accent);font-family:monospace;word-break:break-all;margin-top:6px}
.proxy-card{background:var(--surface);border:1px solid rgba(0,0,0,0.04);border-radius:var(--radius-lg);padding:16px;margin-bottom:10px;box-shadow:var(--shadow)}
.proxy-card h4{color:var(--accent);margin-bottom:8px}
.proxy-card p{font-size:13px;color:var(--text-dim)}
.community-avatar{width:40px;height:40px;border-radius:50%;background:var(--accent-dim);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.community-name{font-weight:600;color:var(--text-bright)}
.community-cat{font-size:11px;color:var(--text-dim)}
.community-desc{font-size:13px;color:var(--text);margin:4px 0}
.community-stats{display:flex;gap:16px;font-size:12px;color:var(--text-dim);margin-top:6px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 20px;border:none;border-radius:var(--radius);cursor:pointer;font-size:14px;font-weight:500;transition:.2s;background:rgba(0,0,0,0.07);color:var(--text)}
.btn-sm{padding:3px 10px;font-size:11px}.btn:hover{background:rgba(0,0,0,0.12)}
.btn-accent{background:var(--accent);color:#fff}
.btn-accent:hover{background:#0062cc}
.btn-danger{background:var(--danger);color:#fff}
.btn-sm{padding:5px 14px;font-size:13px}
input,textarea,select{background:rgba(0,0,0,0.03);border:1px solid rgba(0,0,0,0.1);border-radius:var(--radius);color:var(--text);padding:10px 14px;font-size:14px;width:100%;outline:none;transition:border-color .2s;font-family:var(--font)}
input:focus,textarea:focus,select:focus{border-color:var(--accent)}
textarea{resize:vertical;min-height:60px}
.form-group{margin-bottom:12px}
.form-group label{display:block;font-size:12px;color:var(--text-dim);margin-bottom:4px}
.form-row{display:flex;gap:8px}
.form-row>*{flex:1}
.modal-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.4);z-index:9999;display:none;align-items:center;justify-content:center;-webkit-backdrop-filter:blur(8px);backdrop-filter:blur(8px)}
.modal-overlay.active{display:flex}
.modal-box{background:rgba(255,255,255,0.85);-webkit-backdrop-filter:blur(40px);backdrop-filter:blur(40px);border-radius:var(--radius-lg);padding:24px;width:420px;max-width:90vw;max-height:80vh;overflow-y:auto;border:1px solid rgba(0,0,0,0.06);box-shadow:var(--shadow-lg)}
.modal-box h3{margin-bottom:16px;color:var(--text-bright)}
.community-card .card-header{display:flex;align-items:center;gap:14px;margin-bottom:10px}
.community-avatar{width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,var(--accent-dim),rgba(0,122,255,0.06));display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:20px;color:var(--accent)}
.community-name{font-weight:700;color:var(--text-bright);font-size:16px}
.community-cat{display:inline-block;font-size:11px;color:var(--accent);background:var(--accent-dim);padding:2px 10px;border-radius:20px;margin-top:2px}
.community-desc{font-size:14px;color:var(--text);margin:6px 0;line-height:1.5}
.community-stats{display:flex;gap:20px;font-size:13px;color:var(--text-dim);margin-top:8px}
.svc-desc{font-size:13px;color:var(--text-dim);margin:6px 0 10px}
.svc-badge{display:inline-block;font-size:11px;color:var(--accent);background:var(--accent-dim);padding:2px 10px;border-radius:20px}
.svc-info{display:flex;justify-content:space-between;align-items:center;padding:4px 0;font-size:13px}
.svc-info .svc-label{color:var(--text-dim)}
.svc-info code{font-size:12px;color:var(--text)}
.svc-dl{width:100%;justify-content:center}
/* ===== 导航角标 ===== */
.tab-badge{display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;border-radius:9px;background:var(--danger);color:#fff;font-size:11px;font-weight:600;padding:0 5px;margin-left:4px;line-height:1}
.topbar-item .tab-badge{position:absolute;top:2px;right:-2px;min-width:16px;height:16px;font-size:10px;border-radius:8px;padding:0 4px}
.topbar-item{position:relative}
.topbar-nav-mobile .tab-badge{display:inline-flex}
.chat-app{display:flex;height:100%}
.chat-list-col{width:30%;min-width:200px;max-width:360px;flex-shrink:0;border-right:1px solid rgba(0,0,0,0.06);display:flex;flex-direction:column;background:var(--bg2);z-index:9999;position:relative}
.chat-list-tabs{display:flex;border-bottom:1px solid rgba(0,0,0,0.06);padding:0;margin:0}
.chat-list-tab{flex:1;padding:10px 4px;text-align:center;cursor:pointer;font-size:13px;color:var(--text-dim);transition:.2s;background:none;border:none;box-sizing:border-box;white-space:nowrap;display:inline-flex;align-items:center;justify-content:center;gap:4px}
.chat-list-tab.active{color:var(--accent);border-bottom:2px solid var(--accent);padding-bottom:8px}
.chat-list-scroll{flex:1;overflow-y:auto}
.chat-list-search{display:flex;align-items:center;gap:8px;padding:8px 12px;margin:8px 8px 0;background:var(--bg);border-radius:8px;border:1px solid rgba(0,0,0,0.04);color:var(--text-dim);font-size:13px}
.chat-list-search i{color:var(--text-dim);font-size:13px}
.feed-item{background:var(--surface);border-radius:var(--radius-lg);padding:16px 18px;margin-bottom:10px;border:1px solid rgba(0,0,0,0.04);box-shadow:var(--shadow)}
.feed-user{font-weight:600;color:var(--text-bright);margin-bottom:4px;font-size:14px}
.feed-content{font-size:14px;color:var(--text);margin-bottom:6px}
.feed-time{font-size:12px;color:var(--text-dim)}
.chat-user-item{padding:12px 14px;cursor:pointer;display:flex;align-items:center;gap:10px;border-bottom:1px solid rgba(0,0,0,0.04);transition:.2s}
.chat-user-item:hover{background:var(--accent-dim)}
.chat-user-item .avatar{width:40px;height:40px;border-radius:50%;background:var(--accent-dim);display:flex;align-items:center;justify-content:center;flex-shrink:0;position:relative}
.btn-unfriend{width:28px;height:28px;border-radius:50%;border:none;background:transparent;color:var(--text-dim);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:.15s;font-size:12px;flex-shrink:0}
.btn-unfriend:hover{background:var(--danger);color:#fff}
.chat-user-item .online-dot{width:10px;height:10px;border-radius:50%;background:#34c759;border:2px solid var(--bg2);position:absolute;bottom:-2px;right:-2px}
.chat-user-item .badge{min-width:18px;height:18px;border-radius:9px;background:var(--danger);color:#fff;font-size:11px;font-weight:600;display:flex;align-items:center;justify-content:center;padding:0 5px;margin-left:auto}
.chat-conv-col{flex:1;display:flex;flex-direction:column;min-width:0}
.chat-conv-header{display:flex;align-items:center;gap:8px;padding:14px 20px;border-bottom:1px solid rgba(0,0,0,0.06);font-weight:600;color:var(--text-bright);font-size:15px}
.chat-back-btn{background:none;border:none;color:var(--accent);font-size:18px;cursor:pointer;padding:0 4px}
.chat-messages{flex:1;overflow-y:auto;padding:16px 20px}
.chat-empty{text-align:center;padding:40px;color:var(--text-dim);font-size:14px}
.chat-msg{margin-bottom:14px}
.chat-msg .bubble{display:inline-block;padding:10px 16px;border-radius:18px;max-width:70%;font-size:15px;line-height:1.45}
.chat-msg.me{text-align:right}
.chat-msg.me .bubble{background:var(--accent);color:#fff}
.chat-msg.other .bubble{background:#e8e8ed;color:var(--text)}
.chat-msg .time{font-size:11px;color:var(--text-dim);margin-top:3px}
.chat-input-area{display:flex;gap:8px;padding:12px 16px;border-top:1px solid rgba(0,0,0,0.06)}
.chat-input-area textarea{flex:1;min-height:38px;max-height:80px}
.chat-input-area button{flex-shrink:0}
.tool-section{margin-bottom:28px}
.tool-section-title{font-size:16px;color:var(--accent);margin-bottom:14px;display:flex;align-items:center;gap:8px;font-weight:600}
.tool-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px}
.tool-card{background:var(--surface);border:1px solid rgba(0,0,0,0.04);border-radius:var(--radius-lg);padding:18px;box-shadow:var(--shadow)}
.tool-card-title{font-size:14px;font-weight:600;color:var(--text-bright);margin-bottom:10px;display:flex;align-items:center;gap:6px}
.tool-card .tool-inp,.tool-card .tool-ta,.tool-card .tool-sel{margin-bottom:8px}
.tool-buttons{display:flex;flex-wrap:wrap;gap:6px;margin:6px 0}
.admin-lock{text-align:center;padding:50px 20px}
.admin-lock input{max-width:280px;margin:14px auto}
.admin-stat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:14px;margin-bottom:20px}
.admin-stat-card{background:var(--surface);border:1px solid rgba(0,0,0,0.04);border-radius:var(--radius-lg);padding:20px;text-align:center;box-shadow:var(--shadow)}
.admin-stat-card .num{font-size:32px;font-weight:700;color:var(--accent)}
.admin-stat-card .label{font-size:13px;color:var(--text-dim);margin-top:4px}
.friend-card{display:flex;align-items:center;gap:12px;padding:12px;background:var(--bg2);border-radius:12px;border:1px solid rgba(0,0,0,0.04);margin-top:8px}
.friend-card-avatar{width:44px;height:44px;border-radius:50%;background:var(--accent-dim);display:flex;align-items:center;justify-content:center;color:var(--accent);font-size:18px;flex-shrink:0}
.friend-card-name{font-weight:600;font-size:14px;color:var(--text-bright)}
.friend-card-username{font-size:12px;color:var(--text-dim);margin-top:2px}
.friend-card-id{font-size:11px;color:var(--accent);background:var(--accent-dim);padding:1px 6px;border-radius:10px;margin-left:4px}
@media(max-width:768px){.topbar-item{display:none!important}.topbar-nav-mobile{display:flex!important}.topbar-logo{font-size:15px}.tool-grid{grid-template-columns:1fr}.admin-stat-grid{grid-template-columns:repeat(2,1fr)}
.chat-app{position:relative}
.chat-list-col{width:100%!important;min-width:0!important;border-right:none!important}
.chat-conv-col{position:fixed;top:60px;left:0;right:0;bottom:0;z-index:9998;background:var(--bg);display:none;flex-direction:column}
.chat-conv-col.active{display:flex}}
@media(max-width:480px){.topbar-item{padding:4px 8px;font-size:11px}.topbar-item span{display:none}.panel-scroll{padding:12px}}
/* 团卡片 - 纵向排列 */
.community-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px;padding:4px 0}
/* 轮播 */
.carousel-box{position:relative;overflow:hidden}
.carousel-track{display:flex;transition:transform .5s ease;height:100%;width:100%}
.carousel-slide{min-width:100%;height:140px;flex-shrink:0}
.carousel-dots{position:absolute;bottom:8px;left:50%;transform:translateX(-50%);display:flex;gap:5px}
.carousel-dots .dot{width:6px;height:6px;border-radius:50%;background:rgba(255,255,255,0.5);transition:.2s}
.carousel-dots .dot.active{background:#fff;width:10px}
.community-card-wrap{overflow:hidden;border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);cursor:pointer;transition:transform.2s;background:var(--bg2)}
.community-card-wrap:hover{transform:translateY(-4px)}
.community-card-banner{height:160px;position:relative;display:flex;align-items:flex-end;padding:14px;overflow:hidden}
.community-card-name{background:rgba(0,0,0,0.45);color:#fff;padding:6px 14px;border-radius:8px;font-size:15px;font-weight:600;backdrop-filter:blur(4px)}
.community-join-btn{font-size:12px;color:#fff;background:var(--accent);border:none;padding:4px 14px;border-radius:14px;cursor:pointer;white-space:nowrap;transition:.15s;font-weight:500}
.community-join-btn:hover{opacity:.85}
.community-card-wrap .community-card-banner{margin-bottom:0}
/* 我的团列表 */
.my-community-item{display:flex;align-items:center;gap:10px;padding:12px 14px;background:var(--bg2);border-radius:var(--radius);margin-bottom:6px;cursor:pointer;transition:background.15s;border:1px solid rgba(0,0,0,0.04)}
.my-community-item:hover{background:var(--surface)}
.my-community-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
.my-community-name{flex:1;font-size:14px;color:var(--text-bright);font-weight:500}
.my-community-badge{font-size:11px;color:var(--accent);background:var(--accent-dim);padding:2px 8px;border-radius:10px}
.my-community-tag{font-size:11px;color:var(--accent);background:var(--accent-dim);padding:2px 8px;border-radius:10px;margin-right:4px}
.friend-name{color:var(--text-bright);font-size:14px}
.friend-username{color:var(--text-dim);font-size:12px}
/* 宽屏下聊天区浮动 */
@media(min-width:769px){.chat-conv-col{position:fixed;top:60px;left:360px;right:0;bottom:0;z-index:9998;background:var(--bg);display:flex;flex-direction:column}}

/* 名片卡片 Apple 风格 */
.profile-card{background:#fff;border-radius:20px;overflow:hidden;width:400px;max-width:88vw;box-shadow:0 20px 60px rgba(0,0,0,.15),0 0 0 1px rgba(0,0,0,.04);text-align:center;max-height:90vh}
.profile-cover{height:110px;background:linear-gradient(135deg,#667eea,#764ba2);position:relative}
.profile-avatar{position:absolute;bottom:-36px;left:50%;margin-left:-36px;width:72px;height:72px;border-radius:50%;overflow:hidden;background:#fff;border:3px solid #fff;box-shadow:0 4px 12px rgba(0,0,0,.12)}
.profile-body{padding:44px 20px 16px}
.profile-nick{font-size:20px;font-weight:700;color:#1c1c1e;margin-bottom:2px}
.profile-username{font-size:13px;color:#8e8e93;margin-bottom:4px}
.profile-id{font-size:11px;color:#8e8e93;background:#f2f2f7;display:inline-block;padding:2px 12px;border-radius:20px;margin-bottom:10px}
.profile-bio{font-size:13px;color:#3a3a3c;margin-bottom:14px;padding:0 4px;line-height:1.5;min-height:1em}
.profile-actions{display:flex;gap:8px;justify-content:center;margin-bottom:10px}
.profile-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 20px;border:none;border-radius:22px;font-size:14px;font-weight:500;cursor:pointer;transition:.15s;background:#f2f2f7;color:#1c1c1e}
.profile-btn:hover{opacity:.8}
.profile-btn-primary{background:#007aff;color:#fff}
</style>
/* Notification Bell */
.topbar-notif {
  position: relative;
  cursor: pointer;
  margin-right: 16px;
  display: flex;
  align-items: center;
}
.topbar-notif i {
  font-size: 20px;
  color: var(--text-dim);
  transition: color 0.2s;
}
.topbar-notif:hover i {
  color: var(--accent);
}
.notif-badge {
  position: absolute;
  top: -4px;
  right: -6px;
  min-width: 16px;
  height: 16px;
  border-radius: 8px;
  background: var(--danger);
  color: #fff;
  font-size: 10px;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
  line-height: 1;
  border: 2px solid var(--bg);
}
.notif-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  right: -8px;
  width: 360px;
  max-height: 480px;
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 8px 30px rgba(0,0,0,0.15);
  border: 1px solid rgba(0,0,0,0.06);
  display: none;
  z-index: 1001;
  overflow: hidden;
}
.notif-dropdown.active {
  display: block;
  animation: slideDown 0.2s ease;
}
.notif-dropdown-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 16px;
  border-bottom: 1px solid rgba(0,0,0,0.06);
  font-weight: 600;
  font-size: 14px;
  color: var(--text-bright);
}
.notif-mark-read {
  background: none;
  border: none;
  color: var(--accent);
  font-size: 12px;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 6px;
  transition: background 0.2s;
}
.notif-mark-read:hover {
  background: var(--accent-dim);
}
.notif-dropdown-list {
  overflow-y: auto;
  max-height: 360px;
}
.notif-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 16px;
  border-bottom: 1px solid rgba(0,0,0,0.04);
  transition: background 0.15s;
  cursor: pointer;
}
.notif-item:hover {
  background: #f8f8fa;
}
.notif-item.unread {
  background: var(--accent-dim);
}
.notif-item.unread:hover {
  background: rgba(0,122,255,0.1);
}
.notif-item-icon {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 14px;
}
.notif-item-content {
  flex: 1;
  min-width: 0;
}
.notif-item-title {
  font-size: 13px;
  color: var(--text);
  font-weight: 500;
  line-height: 1.4;
}
.notif-item-desc {
  font-size: 12px;
  color: var(--text-dim);
  margin-top: 2px;
  line-height: 1.3;
}
.notif-item-time {
  font-size: 11px;
  color: var(--text-dim);
  margin-top: 4px;
}
.notif-loading {
  text-align: center;
  padding: 24px;
  color: var(--text-dim);
  font-size: 13px;
}
.notif-empty {
  text-align: center;
  padding: 32px 20px;
  color: var(--text-dim);
  font-size: 13px;
}
.notif-empty i {
  font-size: 28px;
  margin-bottom: 8px;
  color: var(--accent-dim);
}
.notif-dropdown-footer {
  padding: 10px 16px;
  text-align: center;
  border-top: 1px solid rgba(0,0,0,0.06);
  font-size: 12px;
  color: var(--accent);
  cursor: pointer;
}
.notif-dropdown-footer:hover {
  background: #f8f8fa;
}
/* Search */
.topbar-search {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  cursor: pointer;
  transition: background 0.2s;
  margin: 0 4px;
  flex-shrink: 0;
}
.topbar-search:hover {
  background: rgba(0,0,0,0.06);
}
.topbar-search i {
  font-size: 16px;
  color: var(--text-dim);
}
/* Search Panel */
.search-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.3);
  z-index: 99999;
  display: none;
  align-items: flex-start;
  justify-content: center;
  padding-top: 80px;
  -webkit-backdrop-filter: blur(4px);
  backdrop-filter: blur(4px);
}
.search-overlay.active {
  display: flex;
}
.search-modal {
  background: #fff;
  border-radius: 16px;
  width: 600px;
  max-width: 90vw;
  max-height: 70vh;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0,0,0,0.2);
  display: flex;
  flex-direction: column;
}
.search-input-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 16px 20px;
  border-bottom: 1px solid rgba(0,0,0,0.06);
}
.search-input-wrap i {
  color: var(--text-dim);
  font-size: 16px;
}
.search-input-wrap input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 16px;
  background: transparent;
  color: var(--text);
  font-family: var(--font);
}
.search-input-wrap input::placeholder {
  color: var(--text-dim);
}
.search-close {
  background: none;
  border: none;
  font-size: 20px;
  color: var(--text-dim);
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 6px;
}
.search-close:hover {
  background: rgba(0,0,0,0.06);
}
.search-results {
  flex: 1;
  overflow-y: auto;
  padding: 8px 0;
}
.search-section-title {
  padding: 8px 20px 4px;
  font-size: 12px;
  font-weight: 600;
  color: var(--text-dim);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.search-result-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 20px;
  cursor: pointer;
  transition: background 0.15s;
}
.search-result-item:hover {
  background: #f8f8fa;
}
.search-result-icon {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 14px;
  color: var(--accent);
  background: var(--accent-dim);
}
.search-result-info {
  flex: 1;
  min-width: 0;
}
.search-result-title {
  font-size: 14px;
  color: var(--text);
  font-weight: 500;
}
.search-result-desc {
  font-size: 12px;
  color: var(--text-dim);
  margin-top: 1px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.search-empty {
  text-align: center;
  padding: 40px 20px;
  color: var(--text-dim);
  font-size: 13px;
}
.search-loading {
  text-align: center;
  padding: 40px 20px;
  color: var(--text-dim);
  font-size: 13px;
}
</style>
<script>USER_ID=<?=$userId?>;USER_ROLE='<?=$userRole?>'</script>
</head>
<body>
<div id="app">
  <div class="topbar" id="topbar">
    <div class="topbar-left">
      <span class="topbar-logo">菜籽游</span>
      <div class="topbar-nav-mobile" id="topbarNavMobile">
        <button class="topbar-nav-trigger" id="navTrigger"><i class="fas fa-bars"></i> <span>导航</span> <span class="tab-badge" id="navMobileBadge" style="display:none">0</span></button>
        <div class="topbar-nav-dropdown" id="navDropdown">
          <div class="dropdown-item" data-tab="discover"><i class="fas fa-compass"></i> 发现 <span class="tab-badge" id="mobileDiscoverBadge" style="display:none">0</span></div>
          <div class="dropdown-item" data-tab="service"><i class="fas fa-concierge-bell"></i> 服务</div>
          <div class="dropdown-item" data-tab="group"><i class="fas fa-users"></i> 团</div>
          <div class="dropdown-item" data-tab="chat"><i class="fas fa-comment-dots"></i> 消息 <span class="tab-badge" id="mobileChatBadge" style="display:none">0</span></div>
          <div class="dropdown-item admin-only-mobile" data-tab="admin"><i class="fas fa-shield-alt"></i> 管理</div>
          <div class="dropdown-item" onclick="window.location.href='/files.php'"><i class="fas fa-cloud-download-alt"></i> 文件</div>

        </div>
      </div>
      </div>
    <div class="topbar-nav" id="topbarNav">
      <div class="topbar-item active" data-tab="discover"><i class="fas fa-compass"></i><span>发现<span class="tab-badge" id="discoverBadge" style="display:none">0</span></span></div>
      <div class="topbar-item" data-tab="service"><i class="fas fa-concierge-bell"></i><span>服务</span></div>
      <div class="topbar-item" data-tab="group"><i class="fas fa-users"></i><span>团</span></div>
      <div class="topbar-item" data-tab="chat"><i class="fas fa-comment-dots"></i><span>消息<span class="tab-badge" id="chatBadge" style="display:none">0</span></span></div>
      <div class="topbar-item admin-only" data-tab="admin"><i class="fas fa-shield-alt"></i><span>管理</span></div>
      <div class="topbar-item" onclick="window.location.href='/files.php'"><i class="fas fa-cloud-download-alt"></i><span>文件</span></div>

      <div class="topbar-search" id="topbarSearch">
        <i class="fas fa-search"></i>
      </div>
    </div>
    <div class="topbar-right" id="topbarRight">
      <!-- Notification Bell -->
      <div class="topbar-notif" id="notifBell">
        <i class="fas fa-bell"></i>
        <span class="notif-badge" id="notifBadge" style="display:none">0</span>
        <div class="notif-dropdown" id="notifDropdown">
          <div class="notif-dropdown-header">
            <span>通知</span>
            <button class="notif-mark-read" id="notifMarkAllRead">全部已读</button>
          </div>
          <div class="notif-dropdown-list" id="notifList">
            <div class="notif-loading">加载中...</div>
          </div>
          <div class="notif-dropdown-footer">
            <span id="notifMore">查看全部</span>
          </div>
        </div>
      </div>
      <div class="topbar-avatar" id="topbarAvatar">
        <img src="<?=htmlspecialchars($user['avatar_url'] ?? '/assets/images/default-avatar.png')?>" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" style="width:32px;height:32px;border-radius:50%;object-fit:cover">
        <div style="display:none;width:32px;height:32px;border-radius:50%;background:var(--accent-dim);align-items:center;justify-content:center"><i class="fas fa-user" style="color:var(--accent);font-size:14px"></i></div>
      </div>
      <div class="topbar-dropdown" id="topbarDropdown">
        <a class="dropdown-item" href="/settings.php"><i class="fas fa-cog"></i> 设置</a>
        <div class="dropdown-item" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> 退出登录</div>
      </div>
    </div>
  </div>
  <div class="content-area">
    <div class="panel-default" id="panelDefault">
      <div><i class="fas fa-compass"></i><p>欢迎来到菜籽游</p><small>点击左侧菜单探索各项功能</small></div>
    </div>
    <div class="float-panel" id="panel-discover">
      <div class="panel-bar"><span><i class="fas fa-compass"></i> 发现</span></div>
      <div class="panel-scroll" id="feedList">
        <div id="communityList"></div>
        <div style="height:120px"></div>
      </div>
    </div>
    <div class="float-panel" id="panel-service">
      <div class="panel-bar"><span><i class="fas fa-concierge-bell"></i> 公共服务</span></div>
      <div class="panel-scroll" id="serviceContent"></div>
    </div>
    <div class="float-panel" id="panel-group">
      <div class="panel-bar"><span><i class="fas fa-users"></i> 团 · 聚合</span></div>
      <div class="panel-scroll">
        <div style="text-align:right;margin-bottom:12px"><button class="btn btn-accent" id="createGroupBtn"><i class="fas fa-plus"></i> 创建团</button></div>
        <div id="myCommunityList"></div>
      </div>
    </div>
    <div class="float-panel" id="panel-chat">
      <div class="panel-bar"><span><i class="fas fa-comment-dots"></i> 消息</span></div>
      <div class="chat-app" id="chatApp">
        <div class="chat-list-col" id="chatListCol">
          <div class="chat-list-tabs">
            <button class="chat-list-tab active" data-ctab="friends">好友 <span class="tab-badge" id="friendTabBadge" style="display:none">0</span></button>
            <button class="chat-list-tab" data-ctab="requests">申请 <span class="tab-badge" id="requestTabBadge" style="display:none">0</span></button>
          </div>
          <div class="chat-list-scroll" id="friendList"></div>
          <div class="chat-list-scroll" id="requestList" style="display:none"></div>
        </div>
        <div class="chat-conv-col" id="chatConvCol">
          <div class="chat-conv-header" id="chatConvHeader">
            <button class="chat-back-btn" id="chatBackBtn" style="display:none"><i class="fas fa-arrow-left"></i></button>
            <span id="chatConvTitle">选择一个好友开始聊天</span>
          </div>
          <div class="chat-messages" id="chatMessages">
            <div class="chat-empty">选择一个好友开始聊天</div>
          </div>
          <div class="chat-input-area" id="chatInputArea" style="display:none">
            <textarea id="chatInput" placeholder="输入消息..." rows="1"></textarea>
            <button class="btn btn-accent" id="sendChatBtn"><i class="fas fa-paper-plane"></i></button>
          </div>
        </div>
      </div>
      </div>
    </div>
    <div class="float-panel" id="panel-admin">
      <div class="panel-bar admin-panel"><span><i class="fas fa-shield-alt"></i> 管理面板</span></div>
      <div class="panel-scroll" id="adminContent"></div>
    </div>
  </div>
</div>
<div class="modal-overlay" id="createGroupModal">
  <div class="modal-box">
    <h3><i class="fas fa-plus-circle" style="color:var(--accent)"></i> 创建团</h3>
    <div class="form-group"><label>团名称</label><input id="createGroupName" placeholder="输入团名称"></div>
    <div class="form-group"><label>简介</label><textarea id="createGroupDesc" placeholder="介绍一下你的团..." rows="3"></textarea></div>
    <div class="form-group"><label>分类</label><select id="createGroupCategory"><option value="游戏">游戏</option><option value="技术">技术</option><option value="学习">学习</option><option value="生活">生活</option><option value="其他">其他</option></select></div>
    <div class="form-group"><label>宣传图</label><input type="file" id="createGroupBanner" accept="image/*" style="font-size:13px"></div>
    <div class="form-group"><label>官网地址</label><input id="createGroupUrl" placeholder="https://...（选填，新标签页打开）"></div>
    <div id="createGroupMsg" style="font-size:12px;color:var(--danger);margin-bottom:8px"></div>
    <div style="display:flex;gap:8px;justify-content:flex-end">
      <button class="btn" id="cancelCreateGroup">取消</button>
      <button class="btn btn-accent" id="submitCreateGroup">创建</button>
    </div>
  </div>
</div>
<!-- 名片弹窗 -->
<div class="modal-overlay" id="profileModal" onclick="if(event.target===this)this.classList.remove('active')">
  <div class="profile-card">
    <div class="profile-cover">
      <div id="profileAvatar" class="profile-avatar"></div>
    </div>
    <div class="profile-body">
      <div id="profileNick" class="profile-nick"></div>
      <div id="profileName" class="profile-username"></div>
      <div id="profileId" class="profile-id"></div>
      <div id="profileBio" class="profile-bio"></div>
      <div class="profile-actions">
        <button class="profile-btn profile-btn-primary" id="profileAddFriendBtn" style="display:none"><i class="fas fa-user-plus"></i> 添加好友</button>
      </div>
    </div>
  </div>
</div>

<script src="/assets/js/app.js?v=20260510v70"></script>
</body>
</html>
