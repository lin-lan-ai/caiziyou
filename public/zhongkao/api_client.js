// 中考备战 API 客户端
const ZhongkaoAPI = {
  // 学习计划
  async getPlans(date) {
    const resp = await fetch(`/api/zhongkao/plans?date=${date || ''}`);
    return resp.json();
  },
  
  async addPlan(data) {
    const resp = await fetch('/api/zhongkao/plans', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(data)
    });
    return resp.json();
  },
  
  async togglePlan(id) {
    const resp = await fetch(`/api/zhongkao/plans/${id}/toggle`, {method: 'POST'});
    return resp.json();
  },
  
  async deletePlan(id) {
    const resp = await fetch(`/api/zhongkao/plans/${id}`, {method: 'DELETE'});
    return resp.json();
  },
  
  // 知识点
  async getKnowledge(subject) {
    const resp = await fetch(`/api/zhongkao/knowledge?subject=${subject}`);
    return resp.json();
  },
  
  // 试卷
  async getExams(subject) {
    const resp = await fetch(`/api/zhongkao/exams?subject=${subject || ''}`);
    return resp.json();
  },
  
  async getExam(id) {
    const resp = await fetch(`/api/zhongkao/exams/${id}`);
    return resp.json();
  },
  
  // 答题
  async submitAnswer(data) {
    const resp = await fetch('/api/zhongkao/answer', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(data)
    });
    return resp.json();
  },
  
  // 错题本
  async getMistakes(status) {
    const resp = await fetch(`/api/zhongkao/mistakes?status=${status || ''}`);
    return resp.json();
  },
  
  async updateMistakeStatus(id, status) {
    const resp = await fetch(`/api/zhongkao/mistakes/${id}/status`, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({status})
    });
    return resp.json();
  },
  
  // 统计
  async getStats() {
    const resp = await fetch('/api/zhongkao/stats');
    return resp.json();
  },
  
  // 科目进度
  async getProgress() {
    const resp = await fetch('/api/zhongkao/progress');
    return resp.json();
  }
};
