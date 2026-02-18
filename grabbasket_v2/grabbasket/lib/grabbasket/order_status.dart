import 'package:flutter/material.dart';

/// Central place for translating backend order status codes into
/// customer-friendly labels + UI hints.
///
/// Backend statuses (current):
/// - CREATED
/// - ACCEPTED_BY_SELLER
/// - ASSIGNED_TO_PARTNER
/// - READY_FOR_PICKUP
/// - PICKED_UP
/// - DELIVERED
/// - CANCELLED_BY_CUSTOMER (and other CANCELLED_* variants)
/// - REJECTED_BY_SELLER (and other REJECTED_* variants)
class OrderStatus {
  static const created = 'CREATED';
  static const acceptedBySeller = 'ACCEPTED_BY_SELLER';
  static const assignedToPartner = 'ASSIGNED_TO_PARTNER';
  static const readyForPickup = 'READY_FOR_PICKUP';
  static const pickedUp = 'PICKED_UP';
  static const delivered = 'DELIVERED';

  static bool isCancelled(String code) => code.startsWith('CANCELLED');
  static bool isRejected(String code) => code.startsWith('REJECTED');
  static bool isTerminal(String code) => code == delivered || isCancelled(code) || isRejected(code);

  /// A human-friendly label for a status code.
  static String label(String code) {
    switch (code) {
      case created:
        return 'Placed';
      case acceptedBySeller:
        return 'Accepted';
      case assignedToPartner:
        return 'Partner assigned';
      case readyForPickup:
        return 'Ready for pickup';
      case pickedUp:
        return 'Picked up';
      case delivered:
        return 'Delivered';
      case 'REJECTED_BY_SELLER':
        return 'Rejected';
      default:
        if (isCancelled(code)) return 'Cancelled';
        if (isRejected(code)) return 'Rejected';
        return _titleCase(code);
    }
  }

  /// Steps for a “progress chips” view.
  ///
  /// Note: we intentionally keep the steps in backend order, and show a single
  /// fallback chip if the status isn’t part of the main flow.
  static List<({String code, String label})> steps() => const <({String code, String label})>[
        (code: created, label: 'Placed'),
        (code: acceptedBySeller, label: 'Accepted'),
        (code: assignedToPartner, label: 'Partner assigned'),
        (code: readyForPickup, label: 'Ready'),
        (code: pickedUp, label: 'Picked up'),
        (code: delivered, label: 'Delivered'),
      ];

  static int indexInSteps(String code) {
    final i = steps().indexWhere((s) => s.code == code);
    return i;
  }

  /// Whether customer cancellation should be shown/enabled.
  static bool customerCanCancel(String code) {
    if (code == pickedUp || code == delivered) return false;
    if (isCancelled(code) || isRejected(code)) return false;
    return true;
  }

  /// Whether we should poll for partner location.
  static bool shouldTrackPartner(String code) {
    return {assignedToPartner, readyForPickup, pickedUp}.contains(code);
  }

  /// A rough status color for pills.
  static Color color(String code) {
    if (code == delivered) return Colors.green;
    if (isCancelled(code) || isRejected(code)) return Colors.red;

    switch (code) {
      case created:
        return Colors.blueGrey;
      case acceptedBySeller:
        return Colors.blue;
      case assignedToPartner:
      case readyForPickup:
      case pickedUp:
        return Colors.deepOrange;
      default:
        return Colors.blueGrey;
    }
  }

  static String _titleCase(String input) {
    final parts = input
        .trim()
        .replaceAll('_', ' ')
        .toLowerCase()
        .split(RegExp(r'\s+'))
        .where((p) => p.isNotEmpty)
        .toList();
    return parts.map((w) => w[0].toUpperCase() + w.substring(1)).join(' ');
  }
}
