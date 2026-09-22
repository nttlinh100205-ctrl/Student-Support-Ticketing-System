@extends('layouts.app')

@section('title', 'Quản lý loại hỗ trợ')

@section('content')
<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold">
            Quản lý loại hỗ trợ
        </h1>

        <p class="text-slate-500 mt-1">
            Danh mục các loại yêu cầu hỗ trợ sinh viên
        </p>
    </div>

    <p id="message" role="status" class="text-sm"></p>

    <div id="catalogContent" hidden class="space-y-6">

        {{-- ================= FORM THÊM / SỬA ================= --}}
        <form id="supportTypeForm"
              class="bg-white rounded-xl border p-5 space-y-4">

            <h2 id="formTitle" class="font-semibold">
                Thêm loại hỗ trợ
            </h2>

            <input type="hidden" id="supportTypeId">

            {{-- Tên loại hỗ trợ --}}
            <div>
                <label for="supportTypeName"
                       class="block text-sm mb-1">
                    Tên loại hỗ trợ
                </label>

                <input
                    id="supportTypeName"
                    required
                    maxlength="150"
                    class="w-full border rounded-lg p-2"
                    placeholder="Ví dụ: Xác nhận sinh viên">
            </div>

            {{-- Mã loại hỗ trợ --}}
            <div>
                <label for="supportTypeCode"
                       class="block text-sm mb-1">
                    Mã loại hỗ trợ
                </label>

                <input
                    id="supportTypeCode"
                    required
                    maxlength="50"
                    pattern="[A-Za-z0-9_-]+"
                    class="w-full border rounded-lg p-2"
                    placeholder="Ví dụ: XNSV">

                <p class="text-xs text-slate-500 mt-1">
                    Dùng chữ không dấu, số, dấu gạch ngang hoặc gạch dưới.
                </p>
            </div>

            {{-- Phòng phụ trách --}}
            <div>
                <label for="supportTypeDepartment"
                       class="block text-sm mb-1">
                    Phòng phụ trách
                </label>

                <select
                    id="supportTypeDepartment"
                    required
                    class="w-full border rounded-lg p-2">

                    <option value="">
                        -- Chọn phòng ban --
                    </option>

                </select>
            </div>

            {{-- Mô tả --}}
            <div>
                <label for="supportTypeDescription"
                       class="block text-sm mb-1">
                    Mô tả
                </label>

                <textarea
                    id="supportTypeDescription"
                    maxlength="2000"
                    rows="3"
                    class="w-full border rounded-lg p-2"
                    placeholder="Nhập mô tả loại hỗ trợ"></textarea>
            </div>

            {{-- Trạng thái --}}
            <label class="flex items-center gap-2">

                <input
                    type="checkbox"
                    id="supportTypeActive"
                    checked>

                Đang hoạt động

            </label>

            <div class="flex gap-2">

                <button
                    type="submit"
                    id="saveButton"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg">

                    Lưu loại hỗ trợ

                </button>

                <button
                    type="button"
                    id="resetButton"
                    class="px-4 py-2 border rounded-lg">

                    Nhập mới

                </button>

            </div>

        </form>


        {{-- ================= TÌM KIẾM / LỌC ================= --}}
        <div class="bg-white rounded-xl border p-5 space-y-4">

            <form id="searchForm"
                  class="grid grid-cols-1 md:grid-cols-3 gap-3">

                <input
                    id="search"
                    class="border rounded-lg p-2"
                    placeholder="Tìm theo tên hoặc mã">

                <select
                    id="departmentFilter"
                    class="border rounded-lg p-2">

                    <option value="">
                        Tất cả phòng ban
                    </option>

                </select>

                <select
                    id="activeFilter"
                    class="border rounded-lg p-2">

                    <option value="">
                        Tất cả trạng thái
                    </option>

                    <option value="1">
                        Đang hoạt động
                    </option>

                    <option value="0">
                        Ngừng hoạt động
                    </option>

                </select>

                <div>
                    <button
                        type="submit"
                        class="px-4 py-2 bg-slate-800 text-white rounded-lg">

                        Tìm kiếm

                    </button>
                </div>

            </form>


            {{-- ================= BẢNG ================= --}}
            <div class="overflow-x-auto">

                <table class="w-full text-sm">

                    <thead class="bg-slate-50">

                        <tr>

                            <th class="text-left p-3">
                                Mã
                            </th>

                            <th class="text-left p-3">
                                Tên loại hỗ trợ
                            </th>

                            <th class="text-left p-3">
                                Phòng phụ trách
                            </th>

                            <th class="text-left p-3">
                                Trạng thái
                            </th>

                            <th class="text-left p-3">
                                Thao tác
                            </th>

                        </tr>

                    </thead>

                    <tbody id="supportTypeRows">
                    </tbody>

                </table>

            </div>


            {{-- ================= PHÂN TRANG ================= --}}
            <div class="flex items-center justify-between">

                <button
                    id="previousButton"
                    type="button"
                    class="px-3 py-2 border rounded-lg">

                    Trang trước

                </button>

                <span id="pageInfo">
                    Trang 1/1
                </span>

                <button
                    id="nextButton"
                    type="button"
                    class="px-3 py-2 border rounded-lg">

                    Trang sau

                </button>

            </div>

        </div>

    </div>

</div>


<script>
(() => {

    const el = id => document.getElementById(id);

    const apiUrl = '/api/v1/admin/support-types';
    const departmentApiUrl = '/api/v1/admin/departments';

    const token =
        localStorage.getItem('access_token');

    let currentPage = 1;
    let lastPage = 1;

    let searchValue = '';
    let departmentValue = '';
    let activeValue = '';


    // =====================================================
    // THÔNG BÁO
    // =====================================================

    function notify(text, isError = false) {

        el('message').textContent = text;

        el('message').className = isError
            ? 'text-sm text-red-600'
            : 'text-sm text-emerald-700';
    }


    // =====================================================
    // GỌI API
    // =====================================================

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


        let data = {};

        try {
            data = await response.json();
        }
        catch (error) {
            data = {};
        }


        if (response.status === 401) {

            localStorage.removeItem(
                'access_token'
            );

            localStorage.removeItem(
                'current_user'
            );

            window.location.href = '/login';

            throw new Error(
                'Phiên đăng nhập đã hết hạn.'
            );
        }


        if (response.status === 403) {

            el('catalogContent').hidden = true;

            throw new Error(
                'Bạn không có quyền quản lý loại hỗ trợ.'
            );
        }


        if (!response.ok) {

            const errors = data.errors
                ? Object.values(data.errors)
                    .flat()
                    .join(' ')
                : data.message;

            throw new Error(
                errors ||
                'Không thể xử lý yêu cầu.'
            );
        }

        return data;
    }


    // =====================================================
    // LOAD PHÒNG BAN
    // =====================================================

    async function loadDepartments() {

        const result =
            await api(
                `${departmentApiUrl}?is_active=1`
            );

        const departments =
            result.data || [];


        const formSelect =
            el('supportTypeDepartment');

        const filterSelect =
            el('departmentFilter');


        formSelect.innerHTML =
            '<option value="">-- Chọn phòng ban --</option>';

        filterSelect.innerHTML =
            '<option value="">Tất cả phòng ban</option>';


        departments.forEach(department => {

            const option1 =
                document.createElement('option');

            option1.value =
                department.id;

            option1.textContent =
                `${department.code} - ${department.name}`;

            formSelect.appendChild(option1);


            const option2 =
                document.createElement('option');

            option2.value =
                department.id;

            option2.textContent =
                `${department.code} - ${department.name}`;

            filterSelect.appendChild(option2);

        });
    }


    // =====================================================
    // RESET FORM
    // =====================================================

    function resetForm() {

        el('supportTypeForm').reset();

        el('supportTypeId').value = '';

        el('supportTypeActive').checked = true;

        el('formTitle').textContent =
            'Thêm loại hỗ trợ';

        el('saveButton').textContent =
            'Lưu loại hỗ trợ';

        notify('');
    }


    // =====================================================
    // LOAD DANH SÁCH
    // =====================================================

    async function loadSupportTypes(page = 1) {

        el('previousButton').disabled = true;
        el('nextButton').disabled = true;


        const params =
            new URLSearchParams({
                page: String(page)
            });


        if (searchValue !== '') {

            params.set(
                'search',
                searchValue
            );
        }


        if (departmentValue !== '') {

            params.set(
                'department_id',
                departmentValue
            );
        }


        if (activeValue !== '') {

            params.set(
                'is_active',
                activeValue
            );
        }


        const result =
            await api(
                `${apiUrl}?${params}`
            );


        currentPage =
            result.current_page || 1;

        lastPage =
            result.last_page || 1;


        const tbody =
            el('supportTypeRows');

        tbody.replaceChildren();


        const rows =
            result.data || [];


        if (rows.length === 0) {

            const tr =
                document.createElement('tr');

            const td =
                document.createElement('td');

            td.colSpan = 5;

            td.className =
                'p-4 text-center text-slate-500';

            td.textContent =
                'Không có loại hỗ trợ phù hợp.';

            tr.appendChild(td);

            tbody.appendChild(tr);
        }


        rows.forEach(supportType => {

            const row =
                document.createElement('tr');

            row.className =
                'border-t';


            // MÃ
            const codeCell =
                document.createElement('td');

            codeCell.className =
                'p-3';

            codeCell.textContent =
                supportType.code ?? '';

            row.appendChild(codeCell);


            // TÊN
            const nameCell =
                document.createElement('td');

            nameCell.className =
                'p-3';

            nameCell.textContent =
                supportType.name ?? '';

            row.appendChild(nameCell);


            // PHÒNG BAN
            const departmentCell =
                document.createElement('td');

            departmentCell.className =
                'p-3';

            departmentCell.textContent =
                supportType.department
                    ? supportType.department.name
                    : '';

            row.appendChild(
                departmentCell
            );


            // TRẠNG THÁI
            const statusCell =
                document.createElement('td');

            statusCell.className =
                'p-3';

            statusCell.textContent =
                supportType.is_active
                    ? 'Đang hoạt động'
                    : 'Ngừng hoạt động';

            row.appendChild(
                statusCell
            );


            // THAO TÁC
            const actionCell =
                document.createElement('td');

            actionCell.className =
                'p-3';


            const editButton =
                document.createElement('button');

            editButton.type =
                'button';

            editButton.textContent =
                'Sửa';

            editButton.className =
                'text-indigo-600 hover:underline';


            editButton.addEventListener(
                'click',
                () => editSupportType(
                    supportType.id
                )
            );


            actionCell.appendChild(
                editButton
            );

            row.appendChild(
                actionCell
            );


            tbody.appendChild(row);

        });


        el('pageInfo').textContent =
            `Trang ${currentPage}/${lastPage} — ${result.total ?? rows.length} loại hỗ trợ`;


        el('previousButton').disabled =
            currentPage <= 1;

        el('nextButton').disabled =
            currentPage >= lastPage;
    }


    // =====================================================
    // SỬA
    // =====================================================

    async function editSupportType(id) {

        try {

            const result =
                await api(
                    `${apiUrl}/${id}`
                );


            const supportType =
                result.data;


            el('supportTypeId').value =
                supportType.id;

            el('supportTypeName').value =
                supportType.name ?? '';

            el('supportTypeCode').value =
                supportType.code ?? '';

            el('supportTypeDepartment').value =
                supportType.department_id ?? '';

            el('supportTypeDescription').value =
                supportType.description ?? '';

            el('supportTypeActive').checked =
                Boolean(
                    supportType.is_active
                );


            el('formTitle').textContent =
                'Sửa loại hỗ trợ';

            el('saveButton').textContent =
                'Cập nhật loại hỗ trợ';


            el('supportTypeForm')
                .scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });

        }
        catch (error) {

            notify(
                error.message,
                true
            );
        }
    }


    // =====================================================
    // THÊM / CẬP NHẬT
    // =====================================================

    el('supportTypeForm')
        .addEventListener(
            'submit',
            async event => {

                event.preventDefault();


                const id =
                    el('supportTypeId').value;


                const payload = {

                    name:
                        el('supportTypeName')
                            .value
                            .trim(),

                    code:
                        el('supportTypeCode')
                            .value
                            .trim(),

                    department_id:
                        Number(
                            el('supportTypeDepartment')
                                .value
                        ),

                    description:
                        el('supportTypeDescription')
                            .value
                            .trim() || null,

                    is_active:
                        el('supportTypeActive')
                            .checked
                };


                el('saveButton').disabled =
                    true;


                try {

                    let result;


                    if (id) {

                        result =
                            await api(
                                `${apiUrl}/${id}`,
                                {
                                    method: 'PUT',

                                    body:
                                        JSON.stringify(
                                            payload
                                        )
                                }
                            );

                    }
                    else {

                        result =
                            await api(
                                apiUrl,
                                {
                                    method: 'POST',

                                    body:
                                        JSON.stringify(
                                            payload
                                        )
                                }
                            );
                    }


                    notify(
                        result.message ||
                        (
                            id
                                ? 'Cập nhật loại hỗ trợ thành công.'
                                : 'Thêm loại hỗ trợ thành công.'
                        )
                    );


                    resetForm();

                    await loadSupportTypes(
                        currentPage
                    );

                }
                catch (error) {

                    notify(
                        error.message,
                        true
                    );

                }
                finally {

                    el('saveButton').disabled =
                        false;
                }

            }
        );


    // =====================================================
    // NHẬP MỚI
    // =====================================================

    el('resetButton')
        .addEventListener(
            'click',
            resetForm
        );


    // =====================================================
    // TÌM KIẾM / LỌC
    // =====================================================

    el('searchForm')
        .addEventListener(
            'submit',
            event => {

                event.preventDefault();


                searchValue =
                    el('search')
                        .value
                        .trim();


                departmentValue =
                    el('departmentFilter')
                        .value;


                activeValue =
                    el('activeFilter')
                        .value;


                loadSupportTypes(1)
                    .catch(
                        error =>
                            notify(
                                error.message,
                                true
                            )
                    );
            }
        );


    // =====================================================
    // PHÂN TRANG
    // =====================================================

    el('previousButton')
        .addEventListener(
            'click',
            () => {

                if (
                    currentPage > 1
                ) {

                    loadSupportTypes(
                        currentPage - 1
                    );
                }
            }
        );


    el('nextButton')
        .addEventListener(
            'click',
            () => {

                if (
                    currentPage < lastPage
                ) {

                    loadSupportTypes(
                        currentPage + 1
                    );
                }
            }
        );


    // =====================================================
    // KHỞI TẠO
    // =====================================================

    async function init() {

        if (!token) {

            window.location.href =
                '/login';

            return;
        }


        try {

            const result =
                await api(
                    '/api/v1/auth/me'
                );


            if (
                result.user.role !==
                'ADMIN'
            ) {

                notify(
                    'Trang này chỉ dành cho ADMIN.',
                    true
                );

                return;
            }


            el('catalogContent').hidden =
                false;


            await loadDepartments();

            await loadSupportTypes();

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