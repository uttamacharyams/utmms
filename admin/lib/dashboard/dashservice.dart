import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'dashmodel.dart';
import 'package:adminmrz/config/app_endpoints.dart';

class DashboardService {
  static const String _baseUrl = '${kAdminApiBaseUrl}/api9';

  Future<Map<String, String>> _authHeaders() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token');
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  Future<DashboardResponse> getDashboardData() async {
    try {
      final response = await http.get(
        Uri.parse('$_baseUrl/get_dashboard.php'),
        headers: await _authHeaders(),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        return DashboardResponse.fromJson(data);
      } else {
        throw Exception('Failed to load dashboard data: ${response.statusCode}');
      }
    } catch (e) {
      rethrow;
    }
  }
}