# Junius AI 开发规范 v1.0

## 三个管理项目

| 项目 | 路径 | 用途 |
|------|------|------|
| **菜籽游** | `/var/www/caiziyou/` | 主站开发（PHP+Flask） |
| **电子书** | `/var/www/ebook/` | 电子书写作 + RAG 知识库 + AI Skills |
| **工作区** | `/root/.openclaw/workspace/` | 记忆文件、配置、Skill 源码（非Git） |

> ⚠️ 工作区不是 Git 仓库，修改不会自动被跟踪。重要改动需手动记录。

## Git 工作流

### 1. 每次改动必须 commit

无论多小的改动，改完之后必须：
```bash
git add 改动的文件
git commit -m "类型: 简短描述" -m "详细说明"
```

### 2. commit message 格式

```
类型: 简短描述（50字以内）

详细说明（可选）
```

类型：`修复` `功能` `重构` `文档` `配置` `工具`

### 3. 涉及 RAG 知识库时

如果改动后需要让 AI 记住这条知识，**必须**调用注入脚本：

```bash
python3 /var/www/ebook/scripts/inject_knowledge.py \
  --content "经验/配置/教训的内容" \
  --tags "标签1,标签2" \
  --source "来源" \
  --category "分类"
```

### 4. 增量和全量重建

- **增量注入**：单条知识 → `inject_knowledge.py`（只在电子书项目的 RAG 数据库里操作）
- **全量重建**：新增了大量 .md 文件后 → 在电子书目录下跑 `python3 /var/www/ebook/scripts/init_rag.py`

### 5. 三个项目各自的变更流程

**菜籽游项目（/var/www/caiziyou/）：**
1. 改代码
2. 测试
3. `git add . && git commit -m "本次改动说明"`
4. 如果有教训需要 AI 记住 → 注入到 RAG

**电子书项目（/var/www/ebook/）：**
1. Claude Code 写内容
2. `git add . && git commit -m "章节/功能变更"`
3. 生成 PDF 后也可提交到 Git

**工作区文件（/root/.openclaw/workspace/）：**
- 不是 Git 仓库，修改后要主动通知 Junius
- 重要的配置/教训改动 → 注入到 RAG 知识库

### 6. RAG 查询

任何时候需要检索 AI 记忆：
```bash
python3 /var/www/ebook/scripts/rag_query.py "查询内容"
```

Claude Code 在电子书项目下也可以通过 `rag-memory` Skill 查询。

---

**版本：v1.0 | 最后更新：2026-05-27**
