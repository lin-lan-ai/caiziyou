# 菜籽游 v1 — 2026-05-01 17:50

## 前端
- `public/index_app.php` — 纯前端 SPA（HTML骨架 + 内联CSS）
- `public/assets/js/app.js` — 全部 JS 逻辑（24KB）
- 浅色仿 Apple UI 主题
- 5个面板：发现/服务/团/消息/管理
- 消息面板响应式布局（宽屏双栏/窄屏单页栈式）
- 节点教程页面 `public/sub/`

## 后端
- `api/app.py` — Flask API（44KB，30+路由）
- `api/junius_reply.py` — Junius 自动回复
- Nginx 反代到 Flask 5000

## 数据库
- `caiziyou_community_db` — 14张表
- 用户系统、好友、群组、私聊、动态等

## 其他
- OpenClaw 配置
- agent-client.exe (Windows 端)
