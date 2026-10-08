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
        1 => [
            'name' => 'Xác nhận sinh viên',
            'department_id' => 1,
            'content_template' => 'Em cần giấy xác nhận đang là sinh viên để [mục đích]. Mã số sinh viên: [mã số]. Vui lòng hướng dẫn thủ tục và thời gian nhận giấy.',
        ],
        2 => [
            'name' => 'Xin bảng điểm',
            'department_id' => 1,
            'content_template' => 'Em cần xin bảng điểm [học kỳ/năm học] để [mục đích]. Mã số sinh viên: [mã số]. Vui lòng hướng dẫn thủ tục và thời gian nhận.',
        ],
        3 => [
            'name' => 'Hỗ trợ học bổng',
            'department_id' => 2,
            'content_template' => 'Em cần tư vấn về học bổng [tên chương trình/kỳ]. Mã số sinh viên: [mã số]. Vui lòng cho em biết điều kiện, hồ sơ cần chuẩn bị và hạn nộp.',
        ],
        4 => [
            'name' => 'Tư vấn tâm lý',
            'department_id' => 5,
            'content_template' => 'Em muốn được tư vấn về [nội dung em muốn chia sẻ]. Thời gian phù hợp để liên hệ: [thời gian]. Mong thông tin được trao đổi riêng tư.',
        ],
        5 => [
            'name' => 'Hỗ trợ học phí / vay vốn',
            'department_id' => 3,
            'content_template' => 'Em cần hỗ trợ về [học phí/vay vốn] trong [học kỳ/năm học]. Mã số sinh viên: [mã số]. Vui lòng hướng dẫn hồ sơ và thời hạn cần lưu ý.',
        ],
        6 => [
            'name' => 'Mượn tài liệu / phòng học',
            'department_id' => 4,
            'content_template' => 'Em muốn đăng ký mượn [tài liệu/phòng học] vào [ngày, giờ]. Mã số sinh viên: [mã số]. Vui lòng kiểm tra tình trạng và hướng dẫn đăng ký.',
        ],
        7 => [
            'name' => 'Khiếu nại / phản ánh',
            'department_id' => 2,
            'content_template' => 'Em xin phản ánh về [sự việc] xảy ra tại [địa điểm] vào [thời gian]. Nội dung cụ thể: [mô tả ngắn]. Mong phòng kiểm tra và hướng dẫn xử lý.',
        ],
        8 => [
            'name' => 'Báo hỏng thiết bị / phòng học',
            'department_id' => 6,
            'content_template' => 'Thiết bị [tên thiết bị] tại [phòng/vị trí] gặp lỗi [mô tả lỗi], phát hiện lúc [thời gian]. Em gửi kèm ảnh để phòng kiểm tra và hỗ trợ sửa chữa.',
        ],
        9 => [
            'name' => 'Sửa chữa cơ sở vật chất',
            'department_id' => 6,
            'content_template' => 'Cơ sở vật chất tại [tòa nhà/phòng/vị trí] cần sửa chữa: [mô tả tình trạng]. Tình trạng được phát hiện lúc [thời gian]. Em gửi kèm ảnh minh họa.',
        ],
        10 => [
            'name' => 'Vệ sinh / an toàn khuôn viên',
            'department_id' => 6,
            'content_template' => 'Em muốn báo về vấn đề [vệ sinh/an toàn] tại [địa điểm], xảy ra lúc [thời gian]. Mô tả ngắn: [chi tiết]. Em gửi kèm ảnh nếu cần thiết.',
        ],
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

    'staff' => [
        21 => [
            'id' => 21,
            'full_name' => 'Nguyễn Văn A',
            'role' => 'staff',
            'department_id' => 3,
            'email' => 'canbo01@university.edu.vn',
        ],
        22 => [
            'id' => 22,
            'full_name' => 'Phạm Minh D',
            'role' => 'staff',
            'department_id' => 3,
            'email' => 'canbo02@university.edu.vn',
        ],
        23 => [
            'id' => 23,
            'full_name' => 'Hoàng Thị E',
            'role' => 'staff',
            'department_id' => 1,
            'email' => 'canbo03@university.edu.vn',
        ],
    ],

    'users' => [
        1 => [
            'id' => 1,
            'role' => 'admin',
            'department_id' => null,
            'full_name' => 'Admin Hệ thống',
            'email' => 'admin@university.edu.vn',
        ],
        12 => [
            'id' => 12,
            'role' => 'student',
            'department_id' => null,
            'full_name' => 'Trần Thị B',
            'email' => 'sv001@university.edu.vn',
        ],
        21 => [
            'id' => 21,
            'role' => 'staff',
            'department_id' => 3,
            'full_name' => 'Nguyễn Văn A',
            'email' => 'canbo01@university.edu.vn',
        ],
        22 => [
            'id' => 22,
            'role' => 'staff',
            'department_id' => 3,
            'full_name' => 'Phạm Minh D',
            'email' => 'canbo02@university.edu.vn',
        ],
        23 => [
            'id' => 23,
            'role' => 'staff',
            'department_id' => 1,
            'full_name' => 'Hoàng Thị E',
            'email' => 'canbo03@university.edu.vn',
        ],
        31 => [
            'id' => 31,
            'role' => 'department_head',
            'department_id' => 3,
            'full_name' => 'Lê Thị C',
            'email' => 'truongphong@university.edu.vn',
        ],
    ],
];
