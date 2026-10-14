import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

class VoiceMemoFab extends StatefulWidget {
  final Future<void> Function() onStartRecord;
  final Future<void> Function() onStopRecord;

  const VoiceMemoFab({
    super.key,
    required this.onStartRecord,
    required this.onStopRecord,
  });

  @override
  State<VoiceMemoFab> createState() => _VoiceMemoFabState();
}

class _VoiceMemoFabState extends State<VoiceMemoFab> with SingleTickerProviderStateMixin {
  bool _isRecording = false;
  int _seconds = 0;
  Timer? _timer;
  late AnimationController _pulseController;
  late Animation<double> _pulseAnimation;

  @override
  void initState() {
    super.initState();
    _pulseController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 900),
    )..repeat(reverse: true);
    _pulseAnimation = Tween<double>(begin: 1.0, end: 1.08).animate(
      CurvedAnimation(parent: _pulseController, curve: Curves.easeInOut),
    );
  }

  void _toggleRecord() async {
    HapticFeedback.heavyImpact();
    if (_isRecording) {
      _timer?.cancel();
      setState(() {
        _isRecording = false;
        _seconds = 0;
      });
      await widget.onStopRecord();
    } else {
      setState(() {
        _isRecording = true;
        _seconds = 0;
      });
      _timer = Timer.periodic(const Duration(seconds: 1), (t) {
        if (!mounted) return;
        setState(() => _seconds++);
        if (_seconds >= 60) {
          _toggleRecord(); // Auto-stop limit at 60s
        }
      });
      await widget.onStartRecord();
    }
  }

  @override
  void dispose() {
    _timer?.cancel();
    _pulseController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final fab = FloatingActionButton.extended(
      backgroundColor: _isRecording ? const Color(0xFFF43F5E) : const Color(0xFF10B981),
      onPressed: _toggleRecord,
      icon: Icon(
        _isRecording ? Icons.stop_rounded : Icons.mic_rounded,
        color: Colors.white,
      ),
      label: Text(
        _isRecording ? 'Rekam (${_seconds}s)' : 'Memo Suara',
        style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.white),
      ),
    );

    if (!_isRecording) return fab;

    return ScaleTransition(
      scale: _pulseAnimation,
      child: fab,
    );
  }
}
