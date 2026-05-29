// 管理员审核功能
function initAdminPanelExternal() {
    console.log('初始化管理员面板（外部API版本）');
    console.log('初始化管理员面板');
    
    const API_BASE = 'http://localhost:5000/api/admin';
    
    // 获取管理员令牌（这里需要从登录状态获取）
    function getAdminToken() {
        // 实际应用中应该从安全的存储中获取
        return localStorage.getItem('admin_token') || '';
    }
    
    // 加载待审核用户
    async function loadPendingUsers() {
        const token = getAdminToken();
        if (!token) {
            console.log('未找到管理员令牌');
            return;
        }
        
        try {
            const response = await fetch(`${API_BASE}/pending-users`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            if (data.success) {
                renderPendingUsers(data.users);
            } else {
                console.error('获取待审核用户失败:', data.error);
            }
        } catch (error) {
            console.error('加载待审核用户错误:', error);
        }
    }
    
    // 渲染待审核用户列表
    function renderPendingUsers(users) {
        const container = document.getElementById('pendingUsersList');
        if (!container) return;
        
        if (users.length === 0) {
            container.innerHTML = `
                <div class="placeholder-box" style="margin:0;padding:20px">
                    <i class="fas fa-users"></i>
                    <p>暂无待审核用户</p>
                </div>
            `;
            return;
        }
        
        let html = '<div class="pending-users-grid">';
        
        users.forEach(user => {
            const registerTime = new Date(user.created_at).toLocaleString('zh-CN');
            
            html += `
                <div class="pending-user-card" data-user-id="${user.id}">
                    <div class="pending-user-header">
                        <div class="pending-user-avatar">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <div class="pending-user-info">
                            <div class="pending-user-name">${escapeHtml(user.full_name || user.username)}</div>
                            <div class="pending-user-username">@${escapeHtml(user.username)}</div>
                        </div>
                    </div>
                    
                    <div class="pending-user-details">
                        <div class="detail-item">
                            <i class="fas fa-envelope"></i>
                            <span>${escapeHtml(user.email)}</span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-clock"></i>
                            <span>注册时间: ${registerTime}</span>
                        </div>
                        ${user.user_note ? `
                        <div class="detail-item">
                            <i class="fas fa-sticky-note"></i>
                            <span>备注: ${escapeHtml(user.user_note)}</span>
                        </div>
                        ` : ''}
                    </div>
                    
                    <div class="pending-user-actions">
                        <button class="btn btn-success btn-sm approve-btn" data-user-id="${user.id}">
                            <i class="fas fa-check"></i> 通过
                        </button>
                        <button class="btn btn-outline btn-sm reject-btn" data-user-id="${user.id}">
                            <i class="fas fa-times"></i> 拒绝
                        </button>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        container.innerHTML = html;
        
        // 绑定按钮事件
        bindUserActions();
    }
    
    // 绑定用户操作按钮
    function bindUserActions() {
        // 通过按钮
        document.querySelectorAll('.approve-btn').forEach(btn => {
            btn.onclick = async function() {
                const userId = this.getAttribute('data-user-id');
                const reviewNote = prompt('请输入审核备注（可选）:', '');
                
                if (reviewNote === null) return; // 用户取消
                
                await approveUser(userId, reviewNote);
            };
        });
        
        // 拒绝按钮
        document.querySelectorAll('.reject-btn').forEach(btn => {
            btn.onclick = async function() {
                const userId = this.getAttribute('data-user-id');
                const reviewNote = prompt('请输入拒绝原因:', '');
                
                if (reviewNote === null) return; // 用户取消
                if (!reviewNote.trim()) {
                    alert('请填写拒绝原因');
                    return;
                }
                
                await rejectUser(userId, reviewNote);
            };
        });
    }
    
    // 审核通过用户
    async function approveUser(userId, reviewNote) {
        const token = getAdminToken();
        
        try {
            const response = await fetch(`${API_BASE}/approve-user/${userId}`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ review_note: reviewNote })
            });
            
            const data = await response.json();
            if (data.success) {
                alert('用户审核通过！');
                loadPendingUsers(); // 刷新列表
            } else {
                alert('审核失败: ' + data.error);
            }
        } catch (error) {
            console.error('审核用户错误:', error);
            alert('审核失败，请检查网络连接');
        }
    }
    
    // 审核拒绝用户
    async function rejectUser(userId, reviewNote) {
        const token = getAdminToken();
        
        try {
            const response = await fetch(`${API_BASE}/reject-user/${userId}`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ review_note: reviewNote })
            });
            
            const data = await response.json();
            if (data.success) {
                alert('用户已拒绝！');
                loadPendingUsers(); // 刷新列表
            } else {
                alert('操作失败: ' + data.error);
            }
        } catch (error) {
            console.error('拒绝用户错误:', error);
            alert('操作失败，请检查网络连接');
        }
    }
    
    // HTML转义函数
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // 添加CSS样式
    const style = document.createElement('style');
    style.textContent = `
        .pending-users-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }
        
        .pending-user-card {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(69, 243, 255, 0.2);
            border-radius: 8px;
            padding: 16px;
            transition: all 0.3s ease;
        }
        
        .pending-user-card:hover {
            border-color: rgba(69, 243, 255, 0.4);
            box-shadow: 0 0 15px rgba(69, 243, 255, 0.1);
        }
        
        .pending-user-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(69, 243, 255, 0.1);
        }
        
        .pending-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(69, 243, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--neon-cyan);
        }
        
        .pending-user-info {
            flex: 1;
        }
        
        .pending-user-name {
            color: var(--text-primary);
            font-weight: 600;
            font-size: 1rem;
        }
        
        .pending-user-username {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }
        
        .pending-user-details {
            margin-bottom: 16px;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
            color: var(--text-secondary);
            font-size: 0.85rem;
        }
        
        .detail-item i {
            width: 16px;
            color: var(--neon-cyan);
        }
        
        .pending-user-actions {
            display: flex;
            gap: 8px;
        }
        
        .admin-login-form {
            max-width: 400px;
            margin: 20px auto;
            padding: 20px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--neon-cyan);
            border-radius: 8px;
        }
        
        .admin-login-form h4 {
            color: var(--neon-cyan);
            margin-bottom: 16px;
            text-align: center;
        }
        
        .admin-login-form .form-group {
            margin-bottom: 16px;
        }
        
        .admin-login-form .form-label {
            display: block;
            color: var(--text-primary);
            margin-bottom: 6px;
            font-size: 0.9rem;
        }
        
        .admin-login-form .form-input {
            width: 100%;
            padding: 10px;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(69, 243, 255, 0.2);
            color: var(--text-primary);
            border-radius: 4px;
            font-family: inherit;
        }
        
        .admin-login-form .form-input:focus {
            outline: none;
            border-color: var(--neon-cyan);
        }
    `;
    document.head.appendChild(style);
    
    // 检查是否已登录
    const token = getAdminToken();
    if (token) {
        loadPendingUsers();
    } else {
        // 显示登录表单
        showAdminLoginForm();
    }
}

// 显示管理员登录表单
function showAdminLoginForm() {
    const container = document.getElementById('pendingUsersList');
    if (!container) return;
    
    container.innerHTML = `
        <div class="admin-login-form">
            <h4><i class="fas fa-shield-alt"></i> 管理员登录</h4>
            <div class="form-group">
                <label class="form-label">用户名</label>
                <input type="text" id="adminUsername" class="form-input" placeholder="管理员用户名">
            </div>
            <div class="form-group">
                <label class="form-label">密码</label>
                <input type="password" id="adminPassword" class="form-input" placeholder="管理员密码">
            </div>
            <button class="btn btn-success" id="adminLoginBtn" style="width:100%">
                <i class="fas fa-sign-in-alt"></i> 登录
            </button>
            <div class="form-hint" style="margin-top:12px;text-align:center;color:var(--text-secondary);font-size:0.8rem">
                需要使用管理员账户登录
            </div>
        </div>
    `;
    
    // 绑定登录按钮
    document.getElementById('adminLoginBtn').onclick = async function() {
        const username = document.getElementById('adminUsername').value;
        const password = document.getElementById('adminPassword').value;
        
        if (!username || !password) {
            alert('请输入用户名和密码');
            return;
        }
        
        await adminLogin(username, password);
    };
}

// 管理员登录
async function adminLogin(username, password) {
    try {
        const response = await fetch('http://localhost:5000/api/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password })
        });
        
        const data = await response.json();
        if (data.success && data.user.role === 'admin') {
            // 保存令牌
            localStorage.setItem('admin_token', data.token);
            localStorage.setItem('admin_user', JSON.stringify(data.user));
            
            // 重新初始化管理员面板
            initAdminPanel();
        } else {
            alert('登录失败: ' + (data.error || '非管理员账户'));
        }
    } catch (error) {
        console.error('管理员登录错误:', error);
        alert('登录失败，请检查API服务');
    }
}

// 在页面加载后初始化管理员面板
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('tab-admin') && 
            document.getElementById('tab-admin').classList.contains('active')) {
            initAdminPanel();
        }
    });
} else {
    if (document.getElementById('tab-admin') && 
        document.getElementById('tab-admin').classList.contains('active')) {
        initAdminPanel();
    }
}

// 当切换到管理员标签时初始化
document.addEventListener('click', function(e) {
    if (e.target.closest('.sidebar-item[data-tab="admin"]')) {
        setTimeout(initAdminPanel, 100);
    }
});