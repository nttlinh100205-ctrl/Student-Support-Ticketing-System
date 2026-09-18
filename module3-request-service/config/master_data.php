<?php

/**
 * Master data giả (Module 2) — dùng validate department ↔ support_type.
 
 */
return [
    'departments' => [
        1 => 'Phòng Đào tạo',
        2 => 'Phòng Công tác Sinh viên',
        3 => 'Phòng Tài chính – Kế toán',
        4 => 'Thư viện',
        5 => 'Trung tâm Hỗ trợ Sinh viên',
    ],

   
    'support_types' => [
        1 => ['name' => 'Xác nhận sinh viên', 'department_id' => 1],
        2 => ['name' => 'Xin bảng điểm', 'department_id' => 1],
        3 => ['name' => 'Hỗ trợ học bổng', 'department_id' => 2],
        4 => ['name' => 'Tư vấn tâm lý', 'department_id' => 5],
        5 => ['name' => 'Hỗ trợ học phí / vay vốn', 'department_id' => 3],
        6 => ['name' => 'Mượn tài liệu / phòng học', 'department_id' => 4],
        7 => ['name' => 'Khiếu nại / phản ánh', 'department_id' => 2],
    ],
];
