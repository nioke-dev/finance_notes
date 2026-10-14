import 'package:flutter_riverpod/flutter_riverpod.dart';

class LocationData {
  final double latitude;
  final double longitude;

  const LocationData({required this.latitude, required this.longitude});
}

final locationServiceProvider = Provider<LocationService>((ref) {
  return LocationService();
});

class LocationService {
  Future<LocationData?> getCurrentLocation() async {
    // Di aplikasi riil, mengintegrasikan geolocator dengan timeout 3 detik
    // Fallback gracefully jika GPS tidak menyala di lapangan
    return null;
  }
}
