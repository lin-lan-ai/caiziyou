// 工具箱功能 - 使用Python API
function initToolboxExternal() {
    console.log('初始化工具箱（外部API版本）');
    console.log('初始化工具箱');
    
    const API_BASE = 'http://localhost:5000/api/tools';
    
    // JSON工具
    const jsonInput = document.getElementById('jsonInput');
    const jsonOutput = document.getElementById('jsonOutput');
    const formatJsonBtn = document.getElementById('formatJsonBtn');
    const minifyJsonBtn = document.getElementById('minifyJsonBtn');
    
    if (formatJsonBtn) {
        formatJsonBtn.onclick = async function() {
            if (!jsonInput.value.trim()) {
                jsonOutput.textContent = '请输入JSON内容';
                return;
            }
            
            try {
                const response = await fetch(`${API_BASE}/json-format`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        json: jsonInput.value,
                        action: 'format'
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    jsonOutput.textContent = data.result;
                } else {
                    jsonOutput.textContent = '错误: ' + data.error;
                }
            } catch (error) {
                console.error('JSON格式化错误:', error);
                jsonOutput.textContent = '请求失败，请检查API服务';
            }
        };
    }
    
    if (minifyJsonBtn) {
        minifyJsonBtn.onclick = async function() {
            if (!jsonInput.value.trim()) {
                jsonOutput.textContent = '请输入JSON内容';
                return;
            }
            
            try {
                const response = await fetch(`${API_BASE}/json-format`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        json: jsonInput.value,
                        action: 'minify'
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    jsonOutput.textContent = data.result;
                } else {
                    jsonOutput.textContent = '错误: ' + data.error;
                }
            } catch (error) {
                console.error('JSON压缩错误:', error);
                jsonOutput.textContent = '请求失败，请检查API服务';
            }
        };
    }
    
    // Base64工具
    const cryptoInput = document.getElementById('cryptoInput');
    const cryptoOutput = document.getElementById('cryptoOutput');
    const encryptBtn = document.getElementById('encryptBtn');
    const decryptBtn = document.getElementById('decryptBtn');
    
    if (encryptBtn) {
        encryptBtn.onclick = async function() {
            if (!cryptoInput.value.trim()) {
                cryptoOutput.textContent = '请输入文本内容';
                return;
            }
            
            try {
                const response = await fetch(`${API_BASE}/base64`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        text: cryptoInput.value,
                        action: 'encode'
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    cryptoOutput.textContent = data.result;
                } else {
                    cryptoOutput.textContent = '错误: ' + data.error;
                }
            } catch (error) {
                console.error('Base64加密错误:', error);
                cryptoOutput.textContent = '请求失败，请检查API服务';
            }
        };
    }
    
    if (decryptBtn) {
        decryptBtn.onclick = async function() {
            if (!cryptoInput.value.trim()) {
                cryptoOutput.textContent = '请输入Base64编码';
                return;
            }
            
            try {
                const response = await fetch(`${API_BASE}/base64`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        text: cryptoInput.value,
                        action: 'decode'
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    cryptoOutput.textContent = data.result;
                } else {
                    cryptoOutput.textContent = '错误: ' + data.error;
                }
            } catch (error) {
                console.error('Base64解密错误:', error);
                cryptoOutput.textContent = '请求失败，请检查API服务';
            }
        };
    }
    
    // 单位转换工具
    const unitValue = document.getElementById('unitValue');
    const unitOutput = document.getElementById('unitOutput');
    const pxToRemBtn = document.getElementById('pxToRemBtn');
    const remToPxBtn = document.getElementById('remToPxBtn');
    
    if (pxToRemBtn) {
        pxToRemBtn.onclick = async function() {
            const value = parseFloat(unitValue.value) || 0;
            
            try {
                const response = await fetch(`${API_BASE}/unit-convert`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        value: value,
                        from_unit: 'px',
                        to_unit: 'rem'
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    unitOutput.textContent = `${data.from} = ${data.to}`;
                } else {
                    unitOutput.textContent = '错误: ' + data.error;
                }
            } catch (error) {
                console.error('单位转换错误:', error);
                unitOutput.textContent = `${value}px = ${(value/16).toFixed(3)}rem`;
            }
        };
    }
    
    if (remToPxBtn) {
        remToPxBtn.onclick = async function() {
            const value = parseFloat(unitValue.value) || 0;
            
            try {
                const response = await fetch(`${API_BASE}/unit-convert`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        value: value,
                        from_unit: 'rem',
                        to_unit: 'px'
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    unitOutput.textContent = `${data.from} = ${data.to}`;
                } else {
                    unitOutput.textContent = '错误: ' + data.error;
                }
            } catch (error) {
                console.error('单位转换错误:', error);
                unitOutput.textContent = `${value}rem = ${(value*16).toFixed(0)}px`;
            }
        };
    }
    
    // 颜色工具
    const colorPicker = document.getElementById('colorPicker');
    const colorOutput = document.getElementById('colorOutput');
    const copyHexBtn = document.getElementById('copyHexBtn');
    const randomColorBtn = document.getElementById('randomColorBtn');
    
    if (colorPicker) {
        colorPicker.oninput = async function() {
            const color = this.value;
            colorOutput.textContent = `HEX: ${color}`;
            
            // 同时显示RGB
            try {
                const response = await fetch(`${API_BASE}/color-convert`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        color: color,
                        format: 'rgb'
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    colorOutput.textContent = `HEX: ${color} | RGB: ${data.result}`;
                }
            } catch (error) {
                // 忽略API错误，只显示HEX
            }
        };
    }
    
    if (copyHexBtn) {
        copyHexBtn.onclick = function() {
            if (colorPicker) {
                navigator.clipboard.writeText(colorPicker.value)
                    .then(() => {
                        colorOutput.textContent = `已复制: ${colorPicker.value}`;
                        setTimeout(() => {
                            colorOutput.textContent = `HEX: ${colorPicker.value}`;
                        }, 2000);
                    })
                    .catch(() => {
                        colorOutput.textContent = '复制失败';
                    });
            }
        };
    }
    
    if (randomColorBtn) {
        randomColorBtn.onclick = function() {
            const randomColor = '#' + Math.floor(Math.random()*16777215).toString(16).padStart(6, '0');
            if (colorPicker) {
                colorPicker.value = randomColor;
                colorPicker.dispatchEvent(new Event('input'));
            }
        };
    }
    
    console.log('工具箱初始化完成');
}

// 在页面加载后初始化工具箱
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('tab-tools') && 
            document.getElementById('tab-tools').classList.contains('active')) {
            initToolbox();
        }
    });
} else {
    if (document.getElementById('tab-tools') && 
        document.getElementById('tab-tools').classList.contains('active')) {
        initToolbox();
    }
}

// 当切换到工具标签时初始化
document.addEventListener('click', function(e) {
    if (e.target.closest('.sidebar-item[data-tab="tools"]')) {
        setTimeout(initToolbox, 100);
    }
});