class ReconciliationResultModel {
  final bool isBalanced;
  final int difference;
  final int physicalTotal;
  final int bookBalance;
  final String status;

  const ReconciliationResultModel({
    required this.isBalanced,
    required this.difference,
    required this.physicalTotal,
    required this.bookBalance,
    required this.status,
  });

  factory ReconciliationResultModel.fromJson(Map<String, dynamic> json) {
    final data = json['data'] is Map<String, dynamic> ? json['data'] : json;

    final diff = (data['discrepancy_amount'] ?? data['difference'] ?? 0) as int;
    final isBal = (data['is_balanced'] ?? (diff == 0)) as bool;

    return ReconciliationResultModel(
      isBalanced: isBal,
      difference: diff,
      physicalTotal: (data['physical_amount'] ?? data['physical_total'] ?? 0) as int,
      bookBalance: (data['book_amount'] ?? data['book_balance'] ?? 0) as int,
      status: (data['status'] ?? (isBal ? 'matched' : 'unbalanced')) as String,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'is_balanced': isBalanced,
      'difference': difference,
      'physical_total': physicalTotal,
      'book_balance': bookBalance,
      'status': status,
    };
  }
}
