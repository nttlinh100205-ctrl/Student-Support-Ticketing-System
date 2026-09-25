<?php

use App\Http\Controllers\Web\RequestWebController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

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
Route::get('/requests/create', [RequestWebController::class, 'create'])->name('requests.create');
Route::post('/requests', [RequestWebController::class, 'store'])->name('requests.store');
Route::get('/requests/{supportRequest}', [RequestWebController::class, 'show'])->name('requests.show');
Route::get('/requests/{supportRequest}/edit', [RequestWebController::class, 'edit'])->name('requests.edit');
Route::put('/requests/{supportRequest}', [RequestWebController::class, 'update'])->name('requests.update');
Route::put('/requests/{supportRequest}/status', [RequestWebController::class, 'updateStatus'])->name('requests.update-status');
Route::put('/requests/{supportRequest}/assign', [RequestWebController::class, 'assign'])->name('requests.assign');
Route::put('/requests/{supportRequest}/cancel', [RequestWebController::class, 'cancel'])->name('requests.cancel');
Route::delete('/requests/{supportRequest}', [RequestWebController::class, 'destroy'])->name('requests.destroy');

// Comment Thread (Trao đổi)
Route::post('/requests/{supportRequest}/comments', [RequestWebController::class, 'storeComment'])->name('requests.comments.store');
Route::delete('/requests/{supportRequest}/comments/{comment}', [RequestWebController::class, 'destroyComment'])->name('requests.comments.destroy');
