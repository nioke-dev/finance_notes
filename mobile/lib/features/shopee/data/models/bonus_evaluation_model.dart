class BonusEvaluationModel {
  final bool isEligible;
  final int bonusAmount;
  final int completedOrders;
  final double onlineHours;
  final String? reason;

  const BonusEvaluationModel({
    required this.isEligible,
    required this.bonusAmount,
    this.completedOrders = 0,
    this.onlineHours = 0.0,
    this.reason,
  });

  String get message => reason ?? (isEligible ? 'Target bonus tercapai' : 'Target bonus belum tercapai');

  factory BonusEvaluationModel.fromJson(Map<String, dynamic> json) {
    final data = json['data'] is Map<String, dynamic> ? json['data'] : json;

    return BonusEvaluationModel(
      isEligible: data['is_eligible'] ?? data['eligible'] ?? false,
      bonusAmount: (data['bonus_amount'] ?? data['estimated_bonus'] ?? 0) as int,
      completedOrders: (data['completed_orders'] ?? 0) as int,
      onlineHours: (data['online_hours'] != null)
          ? (data['online_hours'] as num).toDouble()
          : 0.0,
      reason: data['reason'] as String?,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'is_eligible': isEligible,
      'bonus_amount': bonusAmount,
      'completed_orders': completedOrders,
      'online_hours': onlineHours,
      'reason': reason,
    };
  }
}
