Lý do chọn bộ số liệu này: một hệ thống tiếp nhận yêu cầu hỗ trợ sinh viên, người xem báo cáo thường là cán bộ/trưởng phòng ban/admin, và họ quan tâm 3 câu hỏi: có bao nhiêu yêu cầu, đang ở đâu (trạng thái nào), và xử lý có nhanh không. Từ đó ra thiết kế:
GET /api/reports/statistics
Query params (filter — tất cả optional):
    department_id     // xem riêng 1 phòng ban
    support_type_id    // xem riêng 1 loại yêu cầu
    from_date, to_date // khoảng thời gian (theo created_at)
Response (đúng khuôn ApiResponse::success()):
json
{
  "success": true,
  "data": {
    "total_requests": 128,
    "by_status": {
      "new": 20, "received": 15, "in_progress": 40,
      "resolved": 45, "cancelled": 8
    },
    "by_department": [
      { "department_id": 3, "department_name": "Phòng CTSV", "total": 60 }
    ],
    "by_support_type": [
      { "support_type_id": 2, "name": "Xác nhận sinh viên", "total": 30 }
    ],
    "avg_processing_hours": 18.4,
    "requests_over_time": [
      { "date": "2026-09-01", "total": 5 },
      { "date": "2026-09-02", "total": 8 }
    ]
  },
  "message": null
}
GET /api/reports/export — cùng filter như trên, trả file CSV danh sách yêu cầu chi tiết (không phải số liệu tổng hợp) để người dùng mở Excel xem/lọc tiếp.
