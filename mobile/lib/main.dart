import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'core/theme/app_theme.dart';

import 'features/main_navigation_screen.dart';

void main() {
  runApp(
    const ProviderScope(
      child: DriverFinanceApp(),
    ),
  );
}

class DriverFinanceApp extends StatelessWidget {
  const DriverFinanceApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Driver Finance Malang',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.darkTheme,
      home: const MainNavigationScreen(),
    );
  }
}
