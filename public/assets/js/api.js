/**
 * API 请求封装
 */

const API = {
    // 自动检测 baseURL（根据当前路径）
    baseURL: (() => {
        // 如果是从根目录访问，使用空字符串
        // 如果是从子目录访问（如 /sub-store-php/），使用该路径
        const path = window.location.pathname;
        // 移除文件名，只保留目录部分
        return path.substring(0, path.lastIndexOf('/'));
    })(),

    // 通用请求方法
    async request(method, url, data = null) {
        const options = {
            method: method.toUpperCase(),
            headers: {
                'Content-Type': 'application/json',
            },
        };

        if (data && (method === 'POST' || method === 'PUT' || method === 'PATCH')) {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(this.baseURL + url, options);
            
            // 检查 Content-Type 是否为 JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                // 如果不是 JSON 响应，读取文本内容用于错误提示
                const text = await response.text();
                console.error('非 JSON 响应:', text);
                throw new Error(`服务器返回了非 JSON 响应 (HTTP ${response.status})`);
            }
            
            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || result.error || '请求失败');
            }

            return result;
        } catch (error) {
            console.error(`API ${method} ${url} 失败:`, error);
            throw error;
        }
    },

    // GET 请求
    get(url) {
        return this.request('GET', url);
    },

    // POST 请求
    post(url, data) {
        return this.request('POST', url, data);
    },

    // PUT 请求
    put(url, data) {
        return this.request('PUT', url, data);
    },

    // PATCH 请求
    patch(url, data) {
        return this.request('PATCH', url, data);
    },

    // DELETE 请求
    delete(url) {
        return this.request('DELETE', url);
    },

    // 订阅管理
    subscriptions: {
        getAll() {
            return API.get('/api/subs');
        },
        create(data) {
            return API.post('/api/subs', data);
        },
        get(name) {
            return API.get(`/api/sub/${encodeURIComponent(name)}`);
        },
        update(name, data) {
            return API.patch(`/api/sub/${encodeURIComponent(name)}`, data);
        },
        delete(name) {
            return API.delete(`/api/sub/${encodeURIComponent(name)}`);
        },
        getFlow(name) {
            return API.get(`/api/sub/flow/${encodeURIComponent(name)}`);
        },
    },

    // 集合管理
    collections: {
        getAll() {
            return API.get('/api/collections');
        },
        create(data) {
            return API.post('/api/collections', data);
        },
        get(name) {
            return API.get(`/api/collection/${encodeURIComponent(name)}`);
        },
        update(name, data) {
            return API.patch(`/api/collection/${encodeURIComponent(name)}`, data);
        },
        delete(name) {
            return API.delete(`/api/collection/${encodeURIComponent(name)}`);
        },
    },

    // 产物管理
    artifacts: {
        getAll() {
            return API.get('/api/artifacts');
        },
        create(data) {
            return API.post('/api/artifacts', data);
        },
        get(name) {
            return API.get(`/api/artifact/${encodeURIComponent(name)}`);
        },
        update(name, data) {
            return API.patch(`/api/artifact/${encodeURIComponent(name)}`, data);
        },
        delete(name) {
            return API.delete(`/api/artifact/${encodeURIComponent(name)}`);
        },
        restore() {
            return API.get('/api/artifacts/restore');
        },
    },

    // 文件管理
    files: {
        getAll() {
            return API.get('/api/files');
        },
        create(data) {
            return API.post('/api/files', data);
        },
        get(path) {
            return API.get(`/api/file/${encodeURIComponent(path)}`);
        },
        update(path, data) {
            return API.put(`/api/file/${encodeURIComponent(path)}`, data);
        },
        delete(path) {
            return API.delete(`/api/file/${encodeURIComponent(path)}`);
        },
    },

    // 设置管理
    settings: {
        get() {
            return API.get('/api/settings');
        },
        update(data) {
            return API.put('/api/settings', data);
        },
    },

    // Token 管理
    tokens: {
        getAll() {
            return API.get('/api/tokens');
        },
        create(data) {
            return API.post('/api/tokens', data);
        },
        update(token, data) {
            return API.put(`/api/token/${encodeURIComponent(token)}`, data);
        },
        delete(token) {
            return API.delete(`/api/token/${encodeURIComponent(token)}`);
        },
    },

    // 同步
    sync: {
        allArtifacts() {
            return API.post('/api/sync/artifacts');
        },
        artifact(name) {
            return API.post(`/api/sync/artifact/${encodeURIComponent(name)}`);
        },
    },
};
