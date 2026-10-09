<?php

use App\Http\Controllers\Web\AdminChatController;
use App\Http\Controllers\Web\RequestFormController;
use App\Http\Controllers\Web\RequestWebController;
use App\Http\Controllers\Web\WorkspaceController;
use App\Http\Middleware\WorkspaceRole;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

require __DIR__.'/account.php';

Route::middleware('account.auth')->group(function () {
    Route::get('/requests/{supportRequest}/images/{attachment}', [RequestFormController::class, 'attachment'])->name('requests.images');
    Route::get('/support-chat', [AdminChatController::class, 'index'])->name('support-chat.index');
    Route::get('/support-chat/images/{message}', [AdminChatController::class, 'image'])->whereNumber('message')->name('support-chat.image');
    Route::post('/support-chat', [AdminChatController::class, 'store'])->middleware(['throttle:20,1', 'idempotent'])->name('support-chat.store');
});

Route::middleware('account.auth')->group(function () {
    Route::get('/request-forms/{type}', [RequestFormController::class, 'show'])->whereNumber('type');
    Route::get('/requests/{supportRequest}/form-files/{field}', [RequestFormController::class, 'download'])->name('requests.form-file');
    Route::get('/', fn () => redirect()->route('requests.index'));

    /*
    |--------------------------------------------------------------------------
    | Phục vụ file storage qua Route (Fix symlink trên Windows / XAMPP)
    |--------------------------------------------------------------------------
    | Trên Windows + XAMPP, symlink thường không hoạt động vì Apache không
    | follow symbolic links. Route này phục vụ file trực tiếp từ
    | storage/app/public/{path} — người dùng chỉ cần upload, không cần
    | cấu hình gì thêm.
    */
    Route::get('/storage/{path}', function (string $path) {
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            abort(404);
        }

        return response()->file($disk->path($path), [
            'Content-Type' => $disk->mimeType($path),
        ]);
    })->where('path', '.*')->name('storage.serve');

    Route::post('/switch-role', [RequestWebController::class, 'switchRole'])->name('requests.switch-role');

    Route::get('/requests', [RequestWebController::class, 'index'])->name('requests.index');
    Route::get('/requests/export', [RequestWebController::class, 'export'])->name('requests.export');
    Route::get('/requests/create', [RequestWebController::class, 'create'])->name('requests.create');
    Route::post('/requests', [RequestWebController::class, 'store'])->middleware('idempotent')->name('requests.store');
    Route::get('/requests/{supportRequest}/copy', [RequestWebController::class, 'copy'])->name('requests.copy');
    Route::get('/requests/{supportRequest}', [RequestWebController::class, 'show'])->name('requests.show');
    Route::post('/requests/{supportRequest}/rating', [RequestWebController::class, 'rate'])->name('requests.rating.store');
    Route::get('/requests/{supportRequest}/comments/{comment}/attachments/{commentAttachment}/preview', [RequestWebController::class, 'previewCommentAttachment'])
        ->name('requests.comments.attachments.preview');
    Route::get('/requests/{supportRequest}/edit', [RequestWebController::class, 'edit'])->name('requests.edit');
    Route::put('/requests/{supportRequest}', [RequestWebController::class, 'update'])->name('requests.update');
    Route::put('/requests/{supportRequest}/status', [RequestWebController::class, 'updateStatus'])->name('requests.update-status');
    Route::put('/requests/{supportRequest}/assign', [RequestWebController::class, 'assign'])->name('requests.assign');
    Route::put('/requests/{supportRequest}/transfer', [RequestWebController::class, 'transfer'])->name('requests.transfer');
    Route::put('/requests/{supportRequest}/cancel', [RequestWebController::class, 'cancel'])->name('requests.cancel');
    Route::delete('/requests/{supportRequest}', [RequestWebController::class, 'destroy'])->name('requests.destroy');

    // Comment Thread (Trao đổi)
    Route::post('/requests/{supportRequest}/comments', [RequestWebController::class, 'storeComment'])->name('requests.comments.store');
    Route::delete('/requests/{supportRequest}/comments/{comment}', [RequestWebController::class, 'destroyComment'])->name('requests.comments.destroy');

});

Route::middleware('account.auth')->group(function () {
    Route::get('/dashboard', [WorkspaceController::class, 'index'])->name('workspace.dashboard');
    Route::get('/kanban', [WorkspaceController::class, 'kanban'])->middleware(WorkspaceRole::class.':staff,department_head,admin')->name('workspace.kanban');
    Route::get('/team', [WorkspaceController::class, 'team'])->middleware(WorkspaceRole::class.':department_head,admin')->name('workspace.team');
    Route::get('/ratings', [WorkspaceController::class, 'ratings'])->middleware(WorkspaceRole::class.':staff,department_head,admin')->name('workspace.ratings');
    Route::get('/admin/audit', [WorkspaceController::class, 'audit'])->middleware(WorkspaceRole::class.':admin')->name('workspace.audit');
});

Route::middleware(['account.auth', WorkspaceRole::class.':admin'])->group(function () {
    Route::get('/admin/settings', [WorkspaceController::class, 'settings'])->name('workspace.settings');
    Route::put('/admin/settings', [WorkspaceController::class, 'saveSettings'])->name('workspace.settings.save');
});
Route::delete('/requests/{id}/assignment', [WorkspaceController::class, 'unassign'])->middleware(['account.auth', WorkspaceRole::class.':admin,department_head'])->name('workspace.unassign');
