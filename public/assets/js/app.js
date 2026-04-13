/**
 * Sub-Store 前端主应用
 */

// 工具函数
function showLoading() {
    document.getElementById('loading').classList.add('active');
}

function hideLoading() {
    document.getElementById('loading').classList.remove('active');
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 3000);
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
    
    // 重置文件路径的禁用状态
    if (modalId === 'file-modal') {
        const pathInput = document.getElementById('file-path');
        if (pathInput) {
            pathInput.disabled = false;
        }
    }
}

function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
}

// Tab 切换
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        // 移除所有 active
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

        // 添加 active
        this.classList.add('active');
        const tabId = this.getAttribute('data-tab');
        document.getElementById(tabId).classList.add('active');

        // 加载对应数据
        loadTabData(tabId);
    });
});

// 加载标签页数据
async function loadTabData(tabId) {
    try {
        switch (tabId) {
            case 'subscriptions':
                await loadSubscriptions();
                break;
            case 'collections':
                await loadCollections();
                break;
            case 'artifacts':
                await loadArtifacts();
                break;
            case 'files':
                await loadFiles();
                break;
            case 'settings':
                await loadSettings();
                break;
        }
    } catch (error) {
        showToast('加载数据失败: ' + error.message, 'error');
    }
}

// ========== 订阅管理 ==========
async function loadSubscriptions() {
    showLoading();
    try {
        const result = await API.subscriptions.getAll();
        const subscriptions = result.data || [];
        renderSubscriptions(subscriptions);
    } finally {
        hideLoading();
    }
}

function renderSubscriptions(subscriptions) {
    const container = document.getElementById('subscriptions-list');

    if (subscriptions.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <div class="empty-state-text">暂无订阅，点击"添加订阅"开始使用</div>
            </div>
        `;
        return;
    }

    container.innerHTML = subscriptions.map(sub => {
        // 计算节点数量
        let nodeCount = 0;
        if (sub.proxies && Array.isArray(sub.proxies)) {
            nodeCount = sub.proxies.length;
        } else if (sub.content && !sub.proxies) {
            nodeCount = '?'; // 有待解析的内容
        }
        
        return `
        <div class="card">
            <div class="card-header">
                <div class="card-title">${escapeHtml(sub.name)}</div>
                <div>
                    ${nodeCount !== '?' && nodeCount > 0 ? `<span class="card-badge" style="background: #67c23a; margin-right: 5px;">${nodeCount} 个节点</span>` : ''}
                    ${sub.error ? `<span class="card-badge" style="background: #f56c6c; margin-right: 5px;">错误</span>` : ''}
                    <span class="card-badge">${sub.source === 'remote' ? '远程' : '本地'}</span>
                </div>
            </div>
            <div class="card-body">
                ${sub.url ? `<p>URL: ${escapeHtml(sub.url)}</p>` : ''}
                ${sub.displayName ? `<p>备注: ${escapeHtml(sub.displayName)}</p>` : ''}
                ${sub.error ? `<p style="color: #f56c6c; font-size: 12px;">错误: ${escapeHtml(sub.error)}</p>` : ''}
                <p style="font-size: 12px; color: #909399; margin-top: 8px;">
                    创建于: ${formatDate(sub.createdAt)}
                </p>
            </div>
            <div class="card-footer">
                <button class="btn btn-sm btn-secondary" onclick="editSubscription('${escapeHtml(sub.name)}')">编辑</button>
                <button class="btn btn-sm btn-success" onclick="downloadSubscription('${escapeHtml(sub.name)}')">下载</button>
                <button class="btn btn-sm btn-danger" onclick="deleteSubscription('${escapeHtml(sub.name)}')">删除</button>
            </div>
        </div>
    `}).join('');
}

function showAddSubscriptionModal() {
    document.getElementById('modal-title').textContent = '添加订阅';
    document.getElementById('subscription-form').reset();
    document.getElementById('subscription-form').onsubmit = handleCreateSubscription;
    openModal('subscription-modal');
}

async function handleCreateSubscription(e) {
    e.preventDefault();
    showLoading();
    try {
        const data = {
            name: document.getElementById('sub-name').value,
            source: document.getElementById('sub-source').value,
            url: document.getElementById('sub-url').value,
            displayName: document.getElementById('sub-display-name').value,
        };
        await API.subscriptions.create(data);
        showToast('订阅创建成功');
        closeModal('subscription-modal');
        await loadSubscriptions();
    } catch (error) {
        showToast('创建失败: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

async function editSubscription(name) {
    showLoading();
    try {
        const result = await API.subscriptions.get(name);
        const sub = result.data;

        document.getElementById('modal-title').textContent = '编辑订阅';
        document.getElementById('sub-name').value = sub.name;
        document.getElementById('sub-source').value = sub.source || 'remote';
        document.getElementById('sub-url').value = sub.url || '';
        document.getElementById('sub-display-name').value = sub.displayName || '';

        document.getElementById('subscription-form').onsubmit = async (e) => {
            e.preventDefault();
            showLoading();
            try {
                const updateData = {
                    name: document.getElementById('sub-name').value,
                    source: document.getElementById('sub-source').value,
                    url: document.getElementById('sub-url').value,
                    displayName: document.getElementById('sub-display-name').value,
                };
                await API.subscriptions.update(name, updateData);
                showToast('订阅更新成功');
                closeModal('subscription-modal');
                await loadSubscriptions();
            } catch (error) {
                showToast('更新失败: ' + error.message, 'error');
            } finally {
                hideLoading();
            }
        };

        openModal('subscription-modal');
    } catch (error) {
        showToast('加载失败: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

async function deleteSubscription(name) {
    if (!confirm(`确定要删除订阅 "${name}" 吗？`)) {
        return;
    }

    showLoading();
    try {
        await API.subscriptions.delete(name);
        showToast('订阅删除成功');
        await loadSubscriptions();
    } catch (error) {
        showToast('删除失败: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

async function downloadSubscription(name) {
    // 构建完整的下载 URL，包含当前路径前缀
    const downloadUrl = `${API.baseURL}/download/${encodeURIComponent(name)}?target=clash`;
    
    // 创建一个隐藏的 a 标签来触发下载
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = `${name}.yaml`;
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showToast(`正在下载订阅: ${name}`, 'success');
}

// ========== 集合管理 ==========
async function loadCollections() {
    showLoading();
    try {
        const result = await API.collections.getAll();
        const collections = result.data || [];
        renderCollections(collections);
    } finally {
        hideLoading();
    }
}

function renderCollections(collections) {
    const container = document.getElementById('collections-list');

    if (collections.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">📦</div>
                <div class="empty-state-text">暂无集合，点击“添加集合”开始使用</div>
            </div>
        `;
        return;
    }

    container.innerHTML = collections.map(col => `
        <div class="card">
            <div class="card-header">
                <div class="card-title">${escapeHtml(col.name)}</div>
            </div>
            <div class="card-body">
                <p>包含 ${col.subscriptions ? col.subscriptions.length : 0} 个订阅</p>
                ${col.displayName ? `<p>备注: ${escapeHtml(col.displayName)}</p>` : ''}
                <p style="font-size: 12px; color: #909399; margin-top: 8px;">
                    创建于: ${formatDate(col.createdAt)}
                </p>
            </div>
            <div class="card-footer">
                <button class="btn btn-sm btn-secondary" onclick="editCollection('${escapeHtml(col.name)}')">编辑</button>
                <button class="btn btn-sm btn-success" onclick="downloadCollection('${escapeHtml(col.name)}')">下载</button>
                <button class="btn btn-sm btn-danger" onclick="deleteCollection('${escapeHtml(col.name)}')">删除</button>
            </div>
        </div>
    `).join('');
}

async function showAddCollectionModal() {
    document.getElementById('collection-modal-title').textContent = '添加集合';
    document.getElementById('collection-form').reset();
    
    // 加载订阅列表供选择
    await loadSubscriptionsForCollection();
    
    document.getElementById('collection-form').onsubmit = handleCreateCollection;
    openModal('collection-modal');
}

async function loadSubscriptionsForCollection() {
    try {
        const result = await API.subscriptions.getAll();
        const subscriptions = result.data || [];
        const container = document.getElementById('col-subscriptions-list');
        
        if (subscriptions.length === 0) {
            container.innerHTML = '<p style="color: #909399; text-align: center; padding: 20px;">暂无订阅，请先添加订阅</p>';
            return;
        }
        
        container.innerHTML = subscriptions.map(sub => `
            <label style="display: block; padding: 8px; cursor: pointer; border-bottom: 1px solid #f0f0f0;">
                <input type="checkbox" value="${escapeHtml(sub.name)}" style="margin-right: 8px;">
                ${escapeHtml(sub.name)}
                ${sub.source === 'remote' ? '<span style="color: #67c23a; font-size: 12px;">(远程)</span>' : '<span style="color: #909399; font-size: 12px;">(本地)</span>'}
            </label>
        `).join('');
    } catch (error) {
        console.error('加载订阅列表失败:', error);
    }
}

async function handleCreateCollection(e) {
    e.preventDefault();
    showLoading();
    try {
        // 获取选中的订阅
        const checkboxes = document.querySelectorAll('#col-subscriptions-list input[type="checkbox"]:checked');
        const subscriptions = Array.from(checkboxes).map(cb => cb.value);
        
        const data = {
            name: document.getElementById('col-name').value,
            subscriptions: subscriptions,
            displayName: document.getElementById('col-display-name').value,
        };
        await API.collections.create(data);
        showToast('集合创建成功');
        closeModal('collection-modal');
        await loadCollections();
    } catch (error) {
        showToast('创建失败: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

async function editCollection(name) {
    showLoading();
    try {
        const result = await API.collections.get(name);
        const col = result.data;

        document.getElementById('collection-modal-title').textContent = '编辑集合';
        document.getElementById('col-name').value = col.name;
        document.getElementById('col-display-name').value = col.displayName || '';
        
        // 加载订阅列表
        await loadSubscriptionsForCollection();
        
        // 设置已选中的订阅
        setTimeout(() => {
            const checkboxes = document.querySelectorAll('#col-subscriptions-list input[type="checkbox"]');
            checkboxes.forEach(cb => {
                if (col.subscriptions && col.subscriptions.includes(cb.value)) {
                    cb.checked = true;
                }
            });
        }, 100);

        document.getElementById('collection-form').onsubmit = async (e) => {
            e.preventDefault();
            showLoading();
            try {
                const checkboxes = document.querySelectorAll('#col-subscriptions-list input[type="checkbox"]:checked');
                const subscriptions = Array.from(checkboxes).map(cb => cb.value);
                
                const updateData = {
                    name: document.getElementById('col-name').value,
                    subscriptions: subscriptions,
                    displayName: document.getElementById('col-display-name').value,
                };
                await API.collections.update(name, updateData);
                showToast('集合更新成功');
                closeModal('collection-modal');
                await loadCollections();
            } catch (error) {
                showToast('更新失败: ' + error.message, 'error');
            } finally {
                hideLoading();
            }
        };

        openModal('collection-modal');
    } catch (error) {
        showToast('加载失败: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

async function deleteCollection(name) {
    if (!confirm(`确定要删除集合 "${name}" 吗？`)) {
        return;
    }

    showLoading();
    try {
        await API.collections.delete(name);
        showToast('集合删除成功');
        await loadCollections();
    } catch (error) {
        showToast('删除失败: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

async function downloadCollection(name) {
    // 构建完整的下载 URL，包含当前路径前缀
    const downloadUrl = `${API.baseURL}/download/collection/${encodeURIComponent(name)}?target=clash`;
    
    // 创建一个隐藏的 a 标签来触发下载
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = `${name}.yaml`;
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showToast(`正在下载组合订阅: ${name}`, 'success');
}

// ========== 产物管理 ==========
async function loadArtifacts() {
    showLoading();
    try {
        const result = await API.artifacts.getAll();
        const artifacts = result.data || [];
        renderArtifacts(artifacts);
    } finally {
        hideLoading();
    }
}

function renderArtifacts(artifacts) {
    const container = document.getElementById('artifacts-list');

    if (artifacts.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">🎯</div>
                <div class="empty-state-text">暂无产物，点击“添加产物”开始使用</div>
            </div>
        `;
        return;
    }

    container.innerHTML = artifacts.map(artifact => `
        <div class="card">
            <div class="card-header">
                <div class="card-title">${escapeHtml(artifact.name)}</div>
                <span class="card-badge">${artifact.type || 'unknown'}</span>
            </div>
            <div class="card-body">
                <p>源: ${escapeHtml(artifact.source || 'N/A')}</p>
                ${artifact.platform ? `<p>目标平台: <span style="color: #409eff; font-weight: bold;">${artifact.platform}</span></p>` : ''}
                ${artifact.description ? `<p>描述: ${escapeHtml(artifact.description)}</p>` : ''}
                <p style="font-size: 12px; color: #909399; margin-top: 8px;">
                    更新于: ${formatDate(artifact.updatedAt)}
                </p>
            </div>
            <div class="card-footer">
                <button class="btn btn-sm btn-secondary" onclick="editArtifact('${escapeHtml(artifact.name)}')">编辑</button>
                <button class="btn btn-sm btn-success" onclick="syncArtifact('${escapeHtml(artifact.name)}')">同步</button>
                <button class="btn btn-sm btn-danger" onclick="deleteArtifact('${escapeHtml(artifact.name)}')">删除</button>
            </div>
        </div>
    `).join('');
}

async function showAddArtifactModal() {
    document.getElementById('artifact-modal-title').textContent = '添加产物';
    document.getElementById('artifact-form').reset();
    document.getElementById('artifact-form').onsubmit = handleCreateArtifact;
    openModal('artifact-modal');
}

async function handleCreateArtifact(e) {
    e.preventDefault();
    showLoading();
    try {
        const data = {
            name: document.getElementById('artifact-name').value,
            type: document.getElementById('artifact-type').value,
            source: document.getElementById('artifact-source').value,
            platform: document.getElementById('artifact-platform').value,
            description: document.getElementById('artifact-description').value,
        };
        await API.artifacts.create(data);
        showToast('产物创建成功');
        closeModal('artifact-modal');
        await loadArtifacts();
    } catch (error) {
        showToast('创建失败: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

async function editArtifact(name) {
    showLoading();
    try {
        const result = await API.artifacts.get(name);
        const artifact = result.data;

        document.getElementById('artifact-modal-title').textContent = '编辑产物';
        document.getElementById('artifact-name').value = artifact.name;
        document.getElementById('artifact-type').value = artifact.type || 'subscription';
        document.getElementById('artifact-source').value = artifact.source || '';
        document.getElementById('artifact-platform').value = artifact.platform || 'clash';
        document.getElementById('artifact-description').value = artifact.description || '';

        document.getElementById('artifact-form').onsubmit = async (e) => {
            e.preventDefault();
            showLoading();
            try {
                const updateData = {
                    name: document.getElementById('artifact-name').value,
                    type: document.getElementById('artifact-type').value,
                    source: document.getElementById('artifact-source').value,
                    platform: document.getElementById('artifact-platform').value,
                    description: document.getElementById('artifact-description').value,
                };
                await API.artifacts.update(name, updateData);
                showToast('产物更新成功');
                closeModal('artifact-modal');
                await loadArtifacts();
            } catch (error) {
                showToast('更新失败: ' + error.message, 'error');
            } finally {
                hideLoading();
            }
        };

        openModal('artifact-modal');
    } catch (error) {
        showToast('加载失败: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

async function syncArtifact(name) {
    showLoading();
    try {
        await API.sync.artifact(name);
        showToast('产物同步成功');
        await loadArtifacts();
    } catch (error) {
        showToast('同步失败: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

async function deleteArtifact(name) {
    if (!confirm(`确定要删除产物 "${name}" 吗？`)) {
        return;
    }

    showLoading();
    try {
        await API.artifacts.delete(name);
        showToast('产物删除成功');
        await loadArtifacts();
    } catch (error) {
        showToast('删除失败: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

// ========== 文件管理 ==========
async function loadFiles() {
    showLoading();
    try {
        const result = await API.files.getAll();
        const files = result.data || [];
        renderFiles(files);
    } finally {
        hideLoading();
    }
}

function renderFiles(files) {
    const container = document.getElementById('files-list');

    if (files.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">📄</div>
                <div class="empty-state-text">暂无文件，点击“添加文件”开始使用</div>
            </div>
        `;
        return;
    }

    container.innerHTML = files.map(file => `
        <div class="card">
            <div class="card-header">
                <div class="card-title">${escapeHtml(file.path)}</div>
            </div>
            <div class="card-body">
                ${file.content ? `<p style="font-size: 12px; color: #606266; max-height: 60px; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(file.content.substring(0, 100))}${file.content.length > 100 ? '...' : ''}</p>` : '<p style="font-size: 12px; color: #909399;">空文件</p>'}
                <p style="font-size: 12px; color: #909399; margin-top: 8px;">
                    更新于: ${formatDate(file.updatedAt)}
                </p>
            </div>
            <div class="card-footer">
                <button class="btn btn-sm btn-secondary" onclick="editFile('${escapeHtml(file.path)}')">编辑</button>
                <button class="btn btn-sm btn-danger" onclick="deleteFile('${escapeHtml(file.path)}')">删除</button>
            </div>
        </div>
    `).join('');
}

async function showAddFileModal() {
    document.getElementById('file-modal-title').textContent = '添加文件';
    document.getElementById('file-form').reset();
    document.getElementById('file-form').onsubmit = handleCreateFile;
    openModal('file-modal');
}

async function handleCreateFile(e) {
    e.preventDefault();
    showLoading();
    try {
        const data = {
            path: document.getElementById('file-path').value,
            content: document.getElementById('file-content').value,
        };
        await API.files.create(data);
        showToast('文件创建成功');
        closeModal('file-modal');
        await loadFiles();
    } catch (error) {
        showToast('创建失败: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

async function editFile(path) {
    showLoading();
    try {
        const result = await API.files.get(path);
        const file = result.data;

        document.getElementById('file-modal-title').textContent = '编辑文件';
        document.getElementById('file-path').value = file.path;
        document.getElementById('file-path').disabled = true; // 路径不可修改
        document.getElementById('file-content').value = file.content || '';

        document.getElementById('file-form').onsubmit = async (e) => {
            e.preventDefault();
            showLoading();
            try {
                const updateData = {
                    content: document.getElementById('file-content').value,
                };
                await API.files.update(path, updateData);
                showToast('文件更新成功');
                closeModal('file-modal');
                await loadFiles();
            } catch (error) {
                showToast('更新失败: ' + error.message, 'error');
            } finally {
                hideLoading();
            }
        };

        openModal('file-modal');
    } catch (error) {
        showToast('加载失败: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

async function deleteFile(path) {
    if (!confirm(`确定要删除文件 "${path}" 吗？`)) {
        return;
    }

    showLoading();
    try {
        await API.files.delete(path);
        showToast('文件删除成功');
        await loadFiles();
    } catch (error) {
        showToast('删除失败: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

// ========== 系统设置 ==========
async function loadSettings() {
    showLoading();
    try {
        const result = await API.settings.get();
        const settings = result.data || {};
        document.getElementById('gist-token').value = settings.gistToken || '';
        document.getElementById('cache-ttl').value = settings.cacheTTL || 3600;
    } finally {
        hideLoading();
    }
}

async function saveSettings() {
    showLoading();
    try {
        const settings = {
            gistToken: document.getElementById('gist-token').value,
            cacheTTL: parseInt(document.getElementById('cache-ttl').value) || 3600,
        };
        await API.settings.update(settings);
        showToast('设置保存成功');
    } catch (error) {
        showToast('保存失败: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

// ========== 工具函数 ==========
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(timestamp) {
    if (!timestamp) return 'N/A';
    const date = new Date(timestamp * 1000);
    return date.toLocaleString('zh-CN');
}

// ========== 初始化 ==========
document.addEventListener('DOMContentLoaded', async () => {
    // 默认加载订阅管理
    await loadSubscriptions();
});
