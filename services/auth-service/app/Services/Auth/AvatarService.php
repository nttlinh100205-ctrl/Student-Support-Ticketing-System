<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AvatarService
{
    private const DISK = 'public';

    public function upload(User $user, UploadedFile $file): User
    {
        $oldAvatarPath = $user->avatar_path;

        $newAvatarPath = $file->store(
            'avatars',
            self::DISK
        );

        if ($newAvatarPath === false) {
            throw new RuntimeException(
                'Không thể lưu ảnh đại diện.'
            );
        }

        try {
            DB::transaction(function () use (
                $user,
                $newAvatarPath
            ): void {
                $user->forceFill([
                    'avatar_path' => $newAvatarPath,
                ])->save();
            });
        } catch (\Throwable $e) {
            Storage::disk(self::DISK)->delete($newAvatarPath);

            throw $e;
        }

        if (
            $oldAvatarPath !== null &&
            $oldAvatarPath !== '' &&
            $oldAvatarPath !== $newAvatarPath
        ) {
            Storage::disk(self::DISK)->delete($oldAvatarPath);
        }

        return $user->fresh();
    }

    public function delete(User $user): User
    {
        if (
            $user->avatar_path !== null &&
            $user->avatar_path !== ''
        ) {
            Storage::disk(self::DISK)->delete(
                $user->avatar_path
            );
        }

        $user->forceFill([
            'avatar_path' => null,
        ])->save();

        return $user->fresh();
    }
}