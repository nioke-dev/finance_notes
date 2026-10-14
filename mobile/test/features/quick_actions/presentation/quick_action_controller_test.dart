import 'package:flutter_test/flutter_test.dart';
import 'package:driver_finance_mobile/core/services/location_service.dart';
import 'package:driver_finance_mobile/features/quick_actions/data/quick_action_repository.dart';
import 'package:driver_finance_mobile/features/quick_actions/presentation/controllers/quick_action_controller.dart';
import 'package:driver_finance_mobile/core/network/api_client.dart';

class MockQuickActionRepository extends Fake implements QuickActionRepository {
  bool shouldThrow = false;
  int? lastParkingAmount;

  @override
  Future<Map<String, dynamic>> submitParking({
    required int amount,
    double? latitude,
    double? longitude,
    String? restaurantName,
  }) async {
    if (shouldThrow) throw Exception('Koneksi timeout');
    lastParkingAmount = amount;
    return {'status': 'success'};
  }
}

class MockLocationService extends Fake implements LocationService {
  @override
  Future<LocationData?> getCurrentLocation() async {
    return const LocationData(latitude: -7.9826, longitude: 112.6308);
  }
}

void main() {
  group('QuickActionController Tests', () {
    late MockQuickActionRepository mockRepo;
    late MockLocationService mockLocation;
    late QuickActionController controller;

    setUp(() {
      mockRepo = MockQuickActionRepository();
      mockLocation = MockLocationService();
      controller = QuickActionController(
        repository: mockRepo,
        locationService: mockLocation,
      );
    });

    test('recordParking sets successMessage on successful API call', () async {
      final success = await controller.recordParking(1000);

      expect(success, true);
      expect(controller.state.isLoading, false);
      expect(controller.state.successMessage, 'Parkir Rp 1000 tercatat');
      expect(controller.state.errorMessage, isNull);
      expect(mockRepo.lastParkingAmount, 1000);
    });

    test('recordParking sets errorMessage on failure', () async {
      mockRepo.shouldThrow = true;

      final success = await controller.recordParking(2000);

      expect(success, false);
      expect(controller.state.isLoading, false);
      expect(controller.state.errorMessage, contains('Koneksi timeout'));
      expect(controller.state.successMessage, isNull);
    });
  });
}
