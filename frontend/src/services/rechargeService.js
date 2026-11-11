import api from './api';

export const rechargeService = {
  async getOperators(type = 'mobile') {
    const res = await api.get(`/recharge/operators.php?type=${type}`);
    return res.data;
  },

  async getPlans(operatorId, planType = null) {
    let url = `/recharge/plans.php?operator_id=${operatorId}`;
    if (planType) url += `&plan_type=${encodeURIComponent(planType)}`;
    const res = await api.get(url);
    return res.data;
  },

  async mobileRecharge(mobile, operatorId, amount, planId = null) {
    const res = await api.post('/recharge/mobile.php', {
      mobile,
      operator_id: operatorId,
      amount,
      plan_id: planId
    });
    return res.data;
  },

  async dthRecharge(subscriberId, operatorId, amount) {
    const res = await api.post('/recharge/dth.php', {
      subscriber_id: subscriberId,
      operator_id: operatorId,
      amount
    });
    return res.data;
  }
};

