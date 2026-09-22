@extends('layouts.app')

@section('title', 'Quản lý cán bộ theo phòng ban')

@section('content')

<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold">
            Quản lý cán bộ theo phòng ban
        </h1>

        <p class="text-slate-500 mt-1">
            Xem, gán và chuyển cán bộ giữa các phòng ban
        </p>
    </div>

    <p id="message" class="text-sm"></p>

    <div id="staffContent" hidden class="space-y-6">

        {{-- Chọn phòng ban --}}
        <div class="bg-white rounded-xl border p-5">

            <label class="block mb-2 font-medium">
                Phòng ban
            </label>

            <select
                id="departmentSelect"
                class="w-full border rounded-lg p-2">

                <option value="">
                    -- Chọn phòng ban --
                </option>

            </select>

        </div>


        {{-- Danh sách cán bộ --}}
        <div class="bg-white rounded-xl border p-5 space-y-4">

            <h2 class="font-semibold">
                Danh sách cán bộ
            </h2>

            <div class="overflow-x-auto">

                <table class="w-full text-sm">

                    <thead class="bg-slate-50">

                        <tr>
                            <th class="text-left p-3">Tên cán bộ</th>
                            <th class="text-left p-3">Email</th>
                            <th class="text-left p-3">Vai trò</th>
                            <th class="text-left p-3">Trạng thái</th>
                            <th class="text-left p-3">Thao tác</th>
                        </tr>

                    </thead>

                    <tbody id="staffRows">
                    </tbody>

                </table>

            </div>

        </div>


        {{-- Gán / chuyển cán bộ --}}
        <div class="bg-white rounded-xl border p-5 space-y-4">

            <h2 class="font-semibold">
                Gán hoặc chuyển cán bộ
            </h2>

            <div>

                <label class="block mb-1">
                    Tài khoản
                </label>

                <select
                    id="userSelect"
                    class="w-full border rounded-lg p-2">

                    <option value="">
                        -- Chọn tài khoản --
                    </option>

                </select>

            </div>


            <div>

                <label class="block mb-1">
                    Vai trò
                </label>

                <select
                    id="roleSelect"
                    class="w-full border rounded-lg p-2">

                    <option value="STAFF">
                        Cán bộ
                    </option>

                    <option value="DEPARTMENT_HEAD">
                        Trưởng phòng
                    </option>

                </select>

            </div>


            <button
                type="button"
                id="assignButton"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg">

                Gán vào phòng ban

            </button>

        </div>

    </div>

</div>


<script>
(() => {

    const el = id =>
        document.getElementById(id);

    const token =
        localStorage.getItem('access_token');


    function notify(text, isError = false) {

        el('message').textContent = text;

        el('message').className =
            isError
                ? 'text-sm text-red-600'
                : 'text-sm text-emerald-700';
    }


    async function api(url, options = {}) {

        const response = await fetch(url, {

            ...options,

            headers: {

                'Accept': 'application/json',
                'Content-Type': 'application/json',

                Authorization:
                    `Bearer ${token}`,

                ...(options.headers || {})
            }

        });


        const data =
            await response.json();


        if (response.status === 401) {

            localStorage.removeItem(
                'access_token'
            );

            window.location.href =
                '/login';

            throw new Error(
                'Phiên đăng nhập đã hết hạn.'
            );
        }


        if (!response.ok) {

            const message =
                data.message ||
                'Không thể xử lý yêu cầu.';

            throw new Error(message);
        }


        return data;
    }


    async function loadDepartments() {

        const result =
            await api(
                '/api/v1/admin/departments'
            );

        const select =
            el('departmentSelect');

        select.innerHTML =
            '<option value="">-- Chọn phòng ban --</option>';


        result.data.forEach(department => {

            const option =
                document.createElement('option');

            option.value =
                department.id;

            option.textContent =
                `${department.code} - ${department.name}`;

            select.appendChild(option);

        });
    }


async function loadUsers() {

    const result = await api(
        '/api/v1/admin/users'
    );

    const select = el('userSelect');

    select.innerHTML =
        '<option value="">-- Chọn tài khoản --</option>';

    // API users trả paginator nằm trong result.data
    const users =
        result.data?.data ?? [];

    users.forEach(user => {

        // Không cho chọn ADMIN
        if (user.role === 'ADMIN') {
            return;
        }

        const option =
            document.createElement('option');

        option.value = user.id;

        let departmentText = '';

        if (user.department) {
            departmentText =
                ` | ${user.department.code ?? ''} - ${user.department.name ?? ''}`;
        }

        option.textContent =
            `${user.name} - ${user.email}${departmentText}`;

        select.appendChild(option);
    });
}


    async function loadStaff() {

        const departmentId =
            el('departmentSelect').value;


        if (!departmentId) {

            el('staffRows')
                .replaceChildren();

            return;
        }


        const result =
            await api(
                `/api/v1/admin/departments/${departmentId}/staff`
            );


        const tbody =
            el('staffRows');

        tbody.replaceChildren();


        const staffList =
            result.data || [];


        if (staffList.length === 0) {

            const row =
                document.createElement('tr');

            const cell =
                document.createElement('td');

            cell.colSpan = 5;

            cell.className =
                'p-4 text-center text-slate-500';

            cell.textContent =
                'Phòng ban chưa có cán bộ.';

            row.appendChild(cell);

            tbody.appendChild(row);

            return;
        }


        staffList.forEach(user => {

            const row =
                document.createElement('tr');

            row.className =
                'border-t';


            const values = [

                user.name,

                user.email,

                user.role ===
                    'DEPARTMENT_HEAD'
                    ? 'Trưởng phòng'
                    : 'Cán bộ',

                user.status ===
                    'ACTIVE'
                    ? 'Hoạt động'
                    : 'Ngừng hoạt động'

            ];


            values.forEach(value => {

                const cell =
                    document.createElement('td');

                cell.className =
                    'p-3';

                cell.textContent =
                    value;

                row.appendChild(cell);

            });


            const actionCell =
                document.createElement('td');

            actionCell.className =
                'p-3';


            const button =
                document.createElement('button');

            button.type =
                'button';

            button.textContent =
                'Chọn';

            button.className =
                'text-indigo-600 hover:underline';


            button.addEventListener(
                'click',
                () => {

                    el('userSelect').value =
                        user.id;

                    el('roleSelect').value =
                        user.role;

                }
            );


            actionCell.appendChild(button);

            row.appendChild(actionCell);

            tbody.appendChild(row);

        });
    }


    el('departmentSelect')
        .addEventListener(
            'change',
            () => {

                loadStaff()
                    .catch(error =>
                        notify(
                            error.message,
                            true
                        )
                    );
            }
        );


    el('assignButton')
        .addEventListener(
            'click',
            async () => {

                const userId =
                    el('userSelect').value;

                const departmentId =
                    el('departmentSelect').value;

                const role =
                    el('roleSelect').value;


                if (!departmentId) {

                    notify(
                        'Vui lòng chọn phòng ban.',
                        true
                    );

                    return;
                }


                if (!userId) {

                    notify(
                        'Vui lòng chọn tài khoản.',
                        true
                    );

                    return;
                }


                try {

                    const result =
                        await api(
                            `/api/v1/admin/users/${userId}/role`,
                            {
                                method: 'PUT',

                                body:
                                    JSON.stringify({
                                        role: role,
                                        department_id:
                                            Number(
                                                departmentId
                                            )
                                    })
                            }
                        );


                    notify(
                        result.message ||
                        'Gán cán bộ thành công.'
                    );


                    await loadStaff();

                    await loadUsers();

                }
                catch (error) {

                    notify(
                        error.message,
                        true
                    );
                }

            }
        );


    async function init() {

        if (!token) {

            window.location.href =
                '/login';

            return;
        }


        try {

            const me =
                await api(
                    '/api/v1/auth/me'
                );


            if (
                me.user.role !== 'ADMIN'
            ) {

                notify(
                    'Trang này chỉ dành cho ADMIN.',
                    true
                );

                return;
            }


            el('staffContent').hidden =
                false;


            await loadDepartments();

            await loadUsers();

        }
        catch (error) {

            notify(
                error.message,
                true
            );
        }

    }


    init();

})();
</script>

@endsection