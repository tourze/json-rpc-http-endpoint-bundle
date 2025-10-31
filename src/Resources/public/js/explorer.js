/**
 * JsonRPC Explorer JavaScript
 */
class JsonRpcExplorer {
    constructor() {
        this.currentMethod = null;
        this.methods = {};
        this.init();
    }

    init() {
        this.loadToken();
        this.bindEvents();
        this.initializeJsonInput();
        this.initializeSearch();
    }

    bindEvents() {
        // Token input save
        const tokenInput = document.getElementById('bearer-token');
        if (tokenInput) {
            tokenInput.addEventListener('input', () => this.saveToken());
        }

        // Method selection
        document.querySelectorAll('.method-item').forEach(item => {
            item.addEventListener('click', (e) => {
                const methodName = e.currentTarget.dataset.method;
                this.selectMethod(methodName);
            });
        });

        // Test buttons - 使用事件委托避免重复绑定问题
        const self = this; // 保存this引用
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('test-method-btn')) {
                // 防止重复点击
                if (e.target.disabled) {
                    return;
                }
                const methodName = e.target.dataset.method;
                console.log('Test button clicked for method:', methodName);
                
                // 确保方法已被选中（激活对应的方法详情）
                self.selectMethod(methodName);
                
                // 稍等一下再执行测试，确保DOM更新完成
                setTimeout(() => {
                    self.testMethod(methodName);
                }, 10);
            }
        });

        // Clear response buttons - 使用事件委托
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('clear-response-btn')) {
                const methodName = e.target.dataset.method;
                self.clearResponse(methodName);
            }
        });

        // Format JSON buttons - 使用事件委托
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('format-json-btn')) {
                const methodName = e.target.dataset.method;
                self.formatJson(methodName);
            }
        });

        // Copy Markdown buttons - 使用事件委托
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('copy-markdown-btn')) {
                const methodName = e.target.dataset.method;
                self.copyMarkdown(methodName);
            }
        });
    }

    loadToken() {
        const token = localStorage.getItem('jsonrpc_bearer_token');
        if (token) {
            const tokenInput = document.getElementById('bearer-token');
            if (tokenInput) {
                tokenInput.value = token;
            }
        }
    }

    saveToken() {
        const tokenInput = document.getElementById('bearer-token');
        if (tokenInput) {
            localStorage.setItem('jsonrpc_bearer_token', tokenInput.value);
        }
    }

    selectMethod(methodName) {
        console.log('selectMethod called with:', methodName);
        
        // Remove active class from all items
        document.querySelectorAll('.method-item').forEach(item => {
            item.classList.remove('active');
        });

        // Add active class to selected item
        const selectedItem = document.querySelector(`.method-item[data-method="${methodName}"]`);
        if (selectedItem) {
            selectedItem.classList.add('active');
        }

        // Hide all method details
        document.querySelectorAll('.method-detail').forEach(detail => {
            detail.classList.remove('active');
        });

        // Show selected method detail - 尝试多种方式查找
        let methodDetail = document.getElementById(`method-${methodName}`);
        
        // 如果直接ID查找失败，尝试转义特殊字符
        if (!methodDetail) {
            const escapedMethodName = methodName.replace(/[^a-zA-Z0-9_-]/g, '\\$&');
            methodDetail = document.querySelector(`#method-${escapedMethodName}`);
        }
        
        // 如果还是找不到，尝试通过 data 属性查找
        if (!methodDetail) {
            const allDetails = document.querySelectorAll('.method-detail');
            for (const detail of allDetails) {
                const dataScript = detail.querySelector('script[type="application/json"]');
                if (dataScript) {
                    try {
                        const data = JSON.parse(dataScript.textContent);
                        if (data.name === methodName) {
                            methodDetail = detail;
                            break;
                        }
                    } catch (e) {
                        // 忽略解析错误，继续查找
                    }
                }
            }
        }
        
        if (methodDetail) {
            methodDetail.classList.add('active');
            this.currentMethod = methodName;
            this.initializeMethodInput(methodName);
            console.log('Method detail activated for:', methodName);
        } else {
            console.warn('Could not find method detail for:', methodName, 'Available IDs:', 
                Array.from(document.querySelectorAll('.method-detail')).map(d => d.id));
        }
    }

    initializeMethodInput(methodName) {
        const methodDetail = document.querySelector(`.method-detail.active`);
        if (!methodDetail) return;
        
        const textarea = methodDetail.querySelector('.json-input');
        if (textarea && !textarea.value.trim()) {
            // Generate sample JSON based on parameters
            const methodData = this.getMethodData(methodName);
            if (methodData && methodData.parameters) {
                const sampleParams = {};
                Object.keys(methodData.parameters).forEach(paramName => {
                    const param = methodData.parameters[paramName];
                    sampleParams[paramName] = this.getSampleValue(param.type, param.description);
                });
                
                if (Object.keys(sampleParams).length > 0) {
                    textarea.value = JSON.stringify(sampleParams, null, 2);
                }
            }
        }
    }

    getSampleValue(type, description) {
        switch (type) {
            case 'string':
                return '';
            case 'int':
            case 'integer':
                return 0;
            case 'float':
            case 'double':
                return 0.0;
            case 'bool':
            case 'boolean':
                return false;
            case 'array':
                return [];
            case 'object':
                return {};
            default:
                return null;
        }
    }

    getMethodData(methodName) {
        // This should be populated from server-side data
        const methodElement = document.querySelector(`.method-detail.active`);
        if (methodElement) {
            const dataScript = methodElement.querySelector('script[type="application/json"]');
            if (dataScript) {
                try {
                    return JSON.parse(dataScript.textContent);
                } catch (e) {
                    console.error('Failed to parse method data:', e);
                }
            }
        }
        return null;
    }

    initializeJsonInput() {
        document.querySelectorAll('.json-input').forEach(textarea => {
            // Add basic JSON validation
            textarea.addEventListener('input', (e) => {
                this.validateJson(e.target);
            });
        });
    }

    validateJson(textarea) {
        try {
            if (textarea.value.trim()) {
                JSON.parse(textarea.value);
                textarea.style.borderColor = '#28a745';
                textarea.style.backgroundColor = '#f8fff9';
            } else {
                textarea.style.borderColor = '#ddd';
                textarea.style.backgroundColor = '#f8f9fa';
            }
        } catch (e) {
            textarea.style.borderColor = '#dc3545';
            textarea.style.backgroundColor = '#fff8f8';
        }
    }

    formatJson(methodName) {
        const method = methodName || this.currentMethod;
        if (!method) return;

        const methodDetail = document.querySelector(`.method-detail.active`);
        if (!methodDetail) return;
        
        const textarea = methodDetail.querySelector('.json-input');
        if (textarea && textarea.value.trim()) {
            try {
                const parsed = JSON.parse(textarea.value);
                textarea.value = JSON.stringify(parsed, null, 2);
                this.validateJson(textarea);
            } catch (e) {
                this.showError('无效的 JSON 格式');
            }
        }
    }

    async testMethod(methodName) {
        console.log('testMethod called with:', methodName);
        const method = methodName || this.currentMethod;
        if (!method) {
            console.log('No method specified');
            return;
        }

        console.log('Testing method:', method);
        
        // 直接通过ID查找方法详情容器
        let methodDetail = document.getElementById(`method-${method}`);
        if (!methodDetail) {
            console.log('Method detail not found by ID, trying CSS escape');
            const escapedMethod = method.replace(/([^a-zA-Z0-9_-])/g, '\\$1');
            methodDetail = document.querySelector(`#method-${escapedMethod}`);
        }
        
        if (!methodDetail) {
            console.log('Method detail still not found, available method details:', 
                Array.from(document.querySelectorAll('.method-detail')).map(d => d.id));
            return;
        }
        
        console.log('Found method detail:', methodDetail.id);
        
        const textarea = methodDetail.querySelector('.json-input');
        const responseSection = methodDetail.querySelector('.response-section');
        const requestContent = methodDetail.querySelector('.request-content');
        const responseContent = methodDetail.querySelector('.response-content');
        const testBtn = document.querySelector(`.test-method-btn[data-method="${method}"]`);

        console.log('Elements check:', {
            textarea: !!textarea,
            responseSection: !!responseSection,
            requestContent: !!requestContent,
            responseContent: !!responseContent,
            testBtn: !!testBtn
        });

        if (!textarea || !responseSection || !responseContent || !requestContent) {
            console.log('Missing required elements for method:', method);
            return;
        }

        // Parse parameters
        let params = {};
        if (textarea.value.trim()) {
            try {
                params = JSON.parse(textarea.value);
            } catch (e) {
                this.showError('参数格式错误：请输入有效的 JSON');
                return;
            }
        }

        // Prepare request
        const request = {
            jsonrpc: '2.0',
            method: method,
            params: params,
            id: Date.now()
        };

        // Prepare headers
        const headers = {
            'Content-Type': 'application/json'
        };

        const token = document.getElementById('bearer-token')?.value;
        if (token && token.trim()) {
            headers['Authorization'] = `Bearer ${token.trim()}`;
        }

        // Show loading state
        if (testBtn) {
            testBtn.textContent = '测试中...';
            testBtn.disabled = true;
        }
        
        // Show request details
        const requestBody = JSON.stringify(request, null, 2);
        const requestHeaders = Object.entries(headers).map(([key, value]) => `${key}: ${value}`).join('\n');
        const requestDetails = `POST /json-rpc HTTP/1.1
Host: ${window.location.host}
${requestHeaders}
Content-Length: ${new TextEncoder().encode(requestBody).length}

${requestBody}`;
        
        requestContent.textContent = requestDetails;
        responseContent.textContent = '发送请求中...';
        responseContent.className = 'http-content response-content loading';
        responseSection.classList.add('show');

        try {
            const response = await fetch('/json-rpc', {
                method: 'POST',
                headers: headers,
                body: JSON.stringify(request)
            });

            const responseData = await response.text();
            
            // Format response details
            const responseHeaders = Array.from(response.headers.entries())
                .map(([key, value]) => `${key}: ${value}`)
                .join('\n');
            
            // Try to parse as JSON for better formatting
            let formattedBody = responseData;
            try {
                const jsonResponse = JSON.parse(responseData);
                formattedBody = JSON.stringify(jsonResponse, null, 2);
            } catch (e) {
                // Keep original response if not JSON
            }

            const responseDetails = `HTTP/1.1 ${response.status} ${response.statusText}
${responseHeaders}
Content-Length: ${new TextEncoder().encode(responseData).length}

${formattedBody}`;

            responseContent.textContent = responseDetails;
            
            if (response.ok) {
                responseContent.className = 'http-content response-content response-success';
            } else {
                responseContent.className = 'http-content response-content response-error';
            }

        } catch (error) {
            console.error('Test method error:', error);
            responseContent.textContent = `网络错误: ${error.message}`;
            responseContent.className = 'http-content response-content response-error';
        } finally {
            if (testBtn) {
                testBtn.textContent = '测试接口';
                testBtn.disabled = false;
            } else {
                // 如果找不到按钮，尝试重新查找并启用
                console.warn('Test button not found, trying to re-enable all buttons');
                document.querySelectorAll('.test-method-btn').forEach(btn => {
                    if (btn.dataset.method === method) {
                        btn.textContent = '测试接口';
                        btn.disabled = false;
                    }
                });
            }
        }
    }

    clearResponse(methodName) {
        const method = methodName || this.currentMethod;
        if (!method) return;

        const methodDetail = document.querySelector(`.method-detail.active`);
        if (!methodDetail) return;
        
        const responseSection = methodDetail.querySelector('.response-section');
        if (responseSection) {
            responseSection.classList.remove('show');
        }
    }

    copyMarkdown(methodName) {
        const method = methodName || this.currentMethod;
        if (!method) return;

        const methodData = this.getMethodDataByName(method);
        if (!methodData || !methodData.markdown) {
            this.showError('无法获取接口信息');
            return;
        }

        // 使用现代浏览器的 Clipboard API
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(methodData.markdown).then(() => {
                this.showSuccess('接口文档已复制到剪贴板！');
            }).catch((err) => {
                console.error('复制失败:', err);
                this.fallbackCopyText(methodData.markdown);
            });
        } else {
            // 降级方案
            this.fallbackCopyText(methodData.markdown);
        }
    }

    fallbackCopyText(text) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.left = '-999999px';
        textarea.style.top = '-999999px';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();
        
        try {
            const result = document.execCommand('copy');
            if (result) {
                this.showSuccess('接口文档已复制到剪贴板！');
            } else {
                this.showError('复制失败，请手动复制');
            }
        } catch (err) {
            console.error('降级复制方案失败:', err);
            this.showError('复制失败，请手动复制');
        } finally {
            document.body.removeChild(textarea);
        }
    }

    getMethodDataByName(methodName) {
        // 查找指定方法的详情容器
        let methodDetail = document.getElementById(`method-${methodName}`);
        if (!methodDetail) {
            // 尝试转义特殊字符
            const escapedMethodName = methodName.replace(/[^a-zA-Z0-9_-]/g, '\\$&');
            methodDetail = document.querySelector(`#method-${escapedMethodName}`);
        }
        
        if (!methodDetail) {
            // 通过数据脚本查找
            const allDetails = document.querySelectorAll('.method-detail');
            for (const detail of allDetails) {
                const dataScript = detail.querySelector('script[type="application/json"]');
                if (dataScript) {
                    try {
                        const data = JSON.parse(dataScript.textContent);
                        if (data.name === methodName) {
                            methodDetail = detail;
                            break;
                        }
                    } catch (e) {
                        // 忽略解析错误，继续查找
                    }
                }
            }
        }
        
        if (methodDetail) {
            const dataScript = methodDetail.querySelector('script[type="application/json"]');
            if (dataScript) {
                try {
                    return JSON.parse(dataScript.textContent);
                } catch (e) {
                    console.error('Failed to parse method data:', e);
                }
            }
        }
        return null;
    }

    initializeSearch() {
        const searchInput = document.getElementById('method-search');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.filterMethods(e.target.value);
            });
        }
    }

    filterMethods(searchTerm) {
        const term = searchTerm.toLowerCase().trim();
        const methodItems = document.querySelectorAll('.method-item');
        const methodGroups = document.querySelectorAll('.method-group');
        
        methodItems.forEach(item => {
            const searchText = item.dataset.searchText || '';
            if (!term || searchText.includes(term)) {
                item.classList.remove('hidden');
            } else {
                item.classList.add('hidden');
            }
        });
        
        // 隐藏没有可见项目的组
        methodGroups.forEach(group => {
            const visibleItems = group.querySelectorAll('.method-item:not(.hidden)');
            if (visibleItems.length === 0) {
                group.style.display = 'none';
            } else {
                group.style.display = 'block';
            }
        });
    }

    showError(message) {
        this.showMessage(message, 'error');
    }

    showSuccess(message) {
        this.showMessage(message, 'success');
    }

    showMessage(message, type = 'info') {
        // 创建消息元素
        const messageDiv = document.createElement('div');
        messageDiv.className = `message-toast message-${type}`;
        messageDiv.textContent = message;
        
        // 设置样式
        messageDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 6px;
            color: white;
            font-weight: 500;
            z-index: 10000;
            max-width: 300px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: opacity 0.3s ease;
        `;
        
        // 根据类型设置背景色
        switch (type) {
            case 'success':
                messageDiv.style.backgroundColor = '#28a745';
                break;
            case 'error':
                messageDiv.style.backgroundColor = '#dc3545';
                break;
            default:
                messageDiv.style.backgroundColor = '#6c757d';
        }
        
        // 添加到页面
        document.body.appendChild(messageDiv);
        
        // 3秒后淡出并移除
        setTimeout(() => {
            messageDiv.style.opacity = '0';
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    document.body.removeChild(messageDiv);
                }
            }, 300);
        }, 3000);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new JsonRpcExplorer();
});