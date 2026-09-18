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
];
