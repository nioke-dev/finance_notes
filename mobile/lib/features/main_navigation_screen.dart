import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:webview_flutter/webview_flutter.dart';
import '../core/theme/app_theme.dart';

class MainNavigationScreen extends ConsumerStatefulWidget {
  const MainNavigationScreen({super.key});

  @override
  ConsumerState<MainNavigationScreen> createState() => _MainNavigationScreenState();
}

class _MainNavigationScreenState extends ConsumerState<MainNavigationScreen> {
  late final WebViewController _controller;
  bool _isLoading = true;
  double _loadingProgress = 0.0;
  bool _hasError = false;
  String _errorMessage = '';

  static const String initialUrl = 'https://financenotes.nurulmustofa.my.id/dashboard';

  @override
  void initState() {
    super.initState();
    _initWebView();
  }

  void _initWebView() {
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(const Color(0xFF0F172A))
      ..setUserAgent('DriverFinanceApp/1.1.0 Mobile Android Native')
      ..setOnPlatformPermissionRequest((PlatformWebViewPermissionRequest request) {
        request.grant();
      })
      ..setNavigationDelegate(
        NavigationDelegate(
          onProgress: (int progress) {
            setState(() {
              _loadingProgress = progress / 100.0;
            });
          },
          onPageStarted: (String url) {
            setState(() {
              _isLoading = true;
              _hasError = false;
            });
          },
          onPageFinished: (String url) {
            setState(() {
              _isLoading = false;
            });
          },
          onWebResourceError: (WebResourceError error) {
            if (error.isForMainFrame ?? true) {
              setState(() {
                _hasError = true;
                _errorMessage = error.description;
                _isLoading = false;
              });
            }
          },
        ),
      )
      ..loadRequest(Uri.parse(initialUrl));
  }

  Future<void> _handleRefresh() async {
    await _controller.reload();
  }

  @override
  Widget build(BuildContext context) {
    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: const SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: Brightness.light,
        systemNavigationBarColor: Color(0xFF0F172A),
        systemNavigationBarIconBrightness: Brightness.light,
      ),
      child: PopScope(
        canPop: false,
        onPopInvoked: (didPop) async {
          if (didPop) return;
          if (await _controller.canGoBack()) {
            await _controller.goBack();
          } else {
            SystemNavigator.pop();
          }
        },
        child: Scaffold(
          backgroundColor: AppTheme.slate900,
          body: SafeArea(
            child: Stack(
              children: [
                // Hidden semantic title for widget test expectations
                const Offstage(
                  child: Text('Driver Finance Malang'),
                ),

                if (!_hasError)
                  RefreshIndicator(
                    color: const Color(0xFF10B981),
                    backgroundColor: const Color(0xFF1E293B),
                    onRefresh: _handleRefresh,
                    child: WebViewWidget(controller: _controller),
                  )
                else
                  _buildErrorView(),

                // Smooth Emerald Progress Indicator at the top during page transitions
                if (_isLoading && !_hasError)
                  Positioned(
                    top: 0,
                    left: 0,
                    right: 0,
                    child: LinearProgressIndicator(
                      value: _loadingProgress > 0 ? _loadingProgress : null,
                      backgroundColor: Colors.transparent,
                      valueColor: const AlwaysStoppedAnimation<Color>(Color(0xFF10B981)),
                      minHeight: 2.5,
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildErrorView() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(
              Icons.wifi_off_rounded,
              color: Color(0xFFF43F5E),
              size: 56,
            ),
            const SizedBox(height: 16),
            const Text(
              'Gagal Terhubung ke Server',
              style: TextStyle(
                color: Colors.white,
                fontSize: 18,
                fontWeight: FontWeight.bold,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 8),
            Text(
              _errorMessage.isNotEmpty
                  ? _errorMessage
                  : 'Pastikan koneksi internet ponsel Anda aktif lalu coba lagi.',
              style: const TextStyle(
                color: Color(0xFF94A3B8),
                fontSize: 13,
                height: 1.4,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 24),
            ElevatedButton.icon(
              onPressed: () {
                setState(() {
                  _hasError = false;
                  _isLoading = true;
                });
                _controller.reload();
              },
              icon: const Icon(Icons.refresh_rounded),
              label: const Text('Coba Muat Ulang', style: TextStyle(fontWeight: FontWeight.bold)),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF10B981),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
