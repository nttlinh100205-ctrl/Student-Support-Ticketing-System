<?php


return [
    'departments' => [
        1 => 'Phòng Đào tạo',
        2 => 'Phòng Công tác Sinh viên',
        3 => 'Phòng Tài chính – Kế toán',
        4 => 'Thư viện',
        5 => 'Trung tâm Hỗ trợ Sinh viên',
        6 => 'Phòng Cơ sở vật chất',
    ],

   
    'support_types' => [
        1 => ['name' => 'Xác nhận sinh viên', 'department_id' => 1],
        2 => ['name' => 'Xin bảng điểm', 'department_id' => 1],
        3 => ['name' => 'Hỗ trợ học bổng', 'department_id' => 2],
        4 => ['name' => 'Tư vấn tâm lý', 'department_id' => 5],
        5 => ['name' => 'Hỗ trợ học phí / vay vốn', 'department_id' => 3],
        6 => ['name' => 'Mượn tài liệu / phòng học', 'department_id' => 4],
        7 => ['name' => 'Khiếu nại / phản ánh', 'department_id' => 2],
        8 => ['name' => 'Báo hỏng thiết bị / phòng học', 'department_id' => 6],
        9 => ['name' => 'Sửa chữa cơ sở vật chất', 'department_id' => 6],
        10 => ['name' => 'Vệ sinh / an toàn khuôn viên', 'department_id' => 6],
    ],

    'staff_by_department' => [
        1 => [21, 22],
        2 => [21, 22],
        3 => [21, 22],
        4 => [21, 22],
        5 => [21, 22],
        6 => [21, 22],
    ],

    'reply_templates' => [
        'received' => 'Yêu cầu của bạn đã được tiếp nhận. Cán bộ sẽ kiểm tra và phản hồi sớm.',
        'need_info' => 'Bạn vui lòng bổ sung thêm thông tin hoặc tài liệu để chúng tôi tiếp tục xử lý yêu cầu này.',
        'in_progress' => 'Yêu cầu đang được xử lý. Chúng tôi sẽ cập nhật kết quả tại cuộc trao đổi này.',
        'resolved' => 'Yêu cầu đã được xử lý. Bạn vui lòng kiểm tra và phản hồi nếu cần hỗ trợ thêm.',
    ],
];
