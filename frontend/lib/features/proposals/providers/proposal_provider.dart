import 'package:flutter/foundation.dart';
import '../models/proposal_model.dart';
import '../services/proposal_service.dart';

/// State management for the Proposals feature.
///
/// Holds three separate lists:
/// - [received]  — pending proposals sent TO the current user
/// - [sent]      — pending proposals sent BY the current user
/// - [accepted]  — accepted/rejected proposals for the current user
///
/// All network calls go through [ProposalService] which returns
/// [ApiResponse] objects, keeping error handling consistent.
class ProposalProvider extends ChangeNotifier {
  final ProposalService _service;

  ProposalProvider({ProposalService? service})
      : _service = service ?? ProposalService();

  // -------------------------------------------------------------------------
  // State
  // -------------------------------------------------------------------------

  bool _isLoading = false;
  String? _error;

  List<ProposalModel> _received = [];
  List<ProposalModel> _sent     = [];
  List<ProposalModel> _accepted = [];

  // -------------------------------------------------------------------------
  // Getters
  // -------------------------------------------------------------------------

  bool get isLoading => _isLoading;
  String? get error  => _error;

  List<ProposalModel> get received => List.unmodifiable(_received);
  List<ProposalModel> get sent     => List.unmodifiable(_sent);
  List<ProposalModel> get accepted => List.unmodifiable(_accepted);

  // -------------------------------------------------------------------------
  // Load all lists
  // -------------------------------------------------------------------------

  Future<void> loadAll(String userId) async {
    _setLoading(true);
    _clearError();

    await Future.wait([
      _loadList(userId, 'received'),
      _loadList(userId, 'sent'),
      _loadList(userId, 'accepted'),
    ]);

    _setLoading(false);
    notifyListeners();
  }

  Future<void> _loadList(String userId, String type) async {
    final response = await _service.fetchProposals(
      userId: userId,
      type: type,
    );

    if (response.isSuccess && response.data != null) {
      switch (type) {
        case 'received':
          _received = response.data!;
          break;
        case 'sent':
          _sent = response.data!;
          break;
        case 'accepted':
          _accepted = response.data!;
          break;
      }
    } else {
      _setError(response.error ?? 'Failed to load $type proposals');
    }
    // notifyListeners is called once in loadAll() after all lists are loaded
  }

  // -------------------------------------------------------------------------
  // Actions
  // -------------------------------------------------------------------------

  Future<bool> sendRequest({
    required String senderId,
    required String receiverId,
    required String requestType,
  }) async {
    final response = await _service.sendRequest(
      senderId:    senderId,
      receiverId:  receiverId,
      requestType: requestType,
    );

    if (!response.isSuccess) {
      _setError(response.error ?? 'Failed to send request');
      notifyListeners();
    }
    return response.isSuccess;
  }

  Future<bool> acceptProposal({
    required String proposalId,
    required String userId,
  }) async {
    final response = await _service.acceptProposal(
      proposalId: proposalId,
      userId:     userId,
    );

    if (response.isSuccess) {
      _received.removeWhere((p) => p.proposalId == proposalId);
      notifyListeners();
    } else {
      _setError(response.error ?? 'Failed to accept proposal');
      notifyListeners();
    }
    return response.isSuccess;
  }

  Future<bool> rejectProposal({
    required String proposalId,
    required String userId,
  }) async {
    final response = await _service.rejectProposal(
      proposalId: proposalId,
      userId:     userId,
    );

    if (response.isSuccess) {
      _received.removeWhere((p) => p.proposalId == proposalId);
      notifyListeners();
    } else {
      _setError(response.error ?? 'Failed to reject proposal');
      notifyListeners();
    }
    return response.isSuccess;
  }

  Future<bool> deleteProposal({
    required String proposalId,
    required String userId,
  }) async {
    final response = await _service.deleteProposal(
      proposalId: proposalId,
      userId:     userId,
    );

    if (response.isSuccess) {
      _sent.removeWhere((p) => p.proposalId == proposalId);
      notifyListeners();
    } else {
      _setError(response.error ?? 'Failed to delete proposal');
      notifyListeners();
    }
    return response.isSuccess;
  }

  // -------------------------------------------------------------------------
  // Helpers
  // -------------------------------------------------------------------------

  void _setLoading(bool value) {
    _isLoading = value;
    // Caller is responsible for calling notifyListeners() after state changes
  }

  void _setError(String message) {
    _error = message;
    // Caller is responsible for calling notifyListeners() after state changes
  }

  void _clearError() {
    _error = null;
  }

  void clearError() => _clearError();
}
