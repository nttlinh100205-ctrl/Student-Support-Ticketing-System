<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImageStorage
{
    public function isCloud(string $path): bool
    {
        return str_starts_with($path, 'cloudinary:');
    }

    private function cloud(): string
    {
        $name = (string) config('images.cloud_name');
        if (! preg_match('/^[a-zA-Z0-9_-]+$/', $name) || ! config('images.api_key') || ! config('images.api_secret')) {
            throw ValidationException::withMessages(['image' => 'Chưa cấu hình Cloudinary. Vui lòng liên hệ quản trị viên.']);
        }

        return $name;
    }

    public function store(UploadedFile $file, string $folder, string $disk = 'local'): string
    {
        if (! str_starts_with((string) $file->getMimeType(), 'image/') || config('images.driver') === 'local') {
            return $file->store($folder, $disk);
        }
        $cloud = $this->cloud();
        $publicId = 'unisupport/'.trim($folder, '/').'/'.Str::uuid();
        $stream = fopen($file->getRealPath(), 'rb');
        try {
            $response = Http::withBasicAuth(config('images.api_key'), config('images.api_secret'))->connectTimeout(5)->timeout(45)
                ->attach('file', $stream, 'image.'.$file->guessExtension())
                ->post('https://api.cloudinary.com/v1_1/'.$cloud.'/image/upload', ['public_id' => $publicId, 'type' => 'authenticated']);
        } catch (ConnectionException $exception) {
            throw ValidationException::withMessages(['image' => 'Không kết nối được Cloudinary. Vui lòng thử lại.']);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
        $format = $response->json('format');
        if (! $response->successful() || $response->json('public_id') !== $publicId || ! in_array($format, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'], true)) {
            throw ValidationException::withMessages(['image' => 'Cloudinary chưa lưu được ảnh. Vui lòng thử lại hoặc kiểm tra cấu hình.']);
        }

        return 'cloudinary:'.$publicId.'.'.$format;
    }

    private function asset(string $path): string
    {
        $asset = substr($path, strlen('cloudinary:'));
        abort_unless(preg_match('~^unisupport/[a-zA-Z0-9/_-]+\.(jpg|jpeg|png|webp|gif|avif)$~', $asset), 404);

        return $asset;
    }

    public function response(string $path, string $name, string $disk = 'local', bool $download = false)
    {
        if (! $this->isCloud($path)) {
            abort_unless(Storage::disk($disk)->exists($path), 404);

            return $download ? Storage::disk($disk)->download($path, basename($name)) : Storage::disk($disk)->response($path, basename($name), ['X-Content-Type-Options' => 'nosniff']);
        }
        $cloud = $this->cloud();
        $asset = $this->asset($path);
        $signature = substr(strtr(base64_encode(sha1($asset.config('images.api_secret'), true)), '+/', '-_'), 0, 8);
        // URL ký chỉ dùng ở backend; trình duyệt nhận ảnh qua route đã kiểm tra quyền.
        try {
            $image = Http::connectTimeout(5)->timeout(30)->get('https://res.cloudinary.com/'.$cloud.'/image/authenticated/s--'.$signature.'--/'.$asset);
        } catch (ConnectionException $exception) {
            abort(503, 'Không tải được ảnh từ Cloudinary.');
        }
        abort_unless($image->successful(), $image->status() === 404 ? 404 : 503);
        $mime = explode(';', (string) $image->header('Content-Type'))[0];
        abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'], true), 502);

        return response($image->body(), 200, [
            'Content-Type' => $mime, 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => ($download ? 'attachment' : 'inline').'; filename="image.'.pathinfo($asset, PATHINFO_EXTENSION).'"',
        ]);
    }

    public function delete(string $path, string $disk = 'local'): void
    {
        if (! $this->isCloud($path)) {
            Storage::disk($disk)->delete($path);

            return;
        }
        $asset = $this->asset($path);
        try {
            $response = Http::withBasicAuth(config('images.api_key'), config('images.api_secret'))->connectTimeout(5)->timeout(20)->asForm()
                ->post('https://api.cloudinary.com/v1_1/'.$this->cloud().'/image/destroy', ['public_id' => substr($asset, 0, strrpos($asset, '.')), 'type' => 'authenticated', 'invalidate' => true]);
        } catch (ConnectionException $exception) {
            abort(503, 'Chưa xóa được ảnh trên Cloudinary.');
        }
        abort_unless($response->successful(), 503, 'Chưa xóa được ảnh trên Cloudinary.');
    }
}
