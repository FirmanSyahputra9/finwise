<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProfilePhotoController
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'photo' => ['required', 'file', 'max:1536'],
        ]);

        $photo = $validated['photo'];
        $image = @getimagesize($photo->getRealPath());
        if (! $image || ($image['mime'] ?? null) !== 'image/webp' || $image[0] !== 1024 || $image[1] !== 1024) {
            throw ValidationException::withMessages(['photo' => __('ui.photo_invalid')]);
        }

        $user = $request->user();
        $filename = Str::uuid().'.webp';
        $oldPath = $user->profile_photo_path;
        $path = 'profile-photos/'.$filename;
        $photo->storeAs('profile-photos', $filename, 'public');
        $user->update(['profile_photo_path' => $path]);

        if ($oldPath && (
            preg_match('#^profile-photos/[^/]+\.webp$#', $oldPath)
            || str_starts_with($oldPath, 'profile-photos/'.$user->id.'/')
        )) {
            Storage::disk('public')->delete($oldPath);
            $legacyFile = public_path($oldPath);
            if (is_file($legacyFile)) {
                @unlink($legacyFile);
            }
        }

        return response()->json([
            'message' => __('ui.photo_saved'),
            'url' => Storage::disk('public')->url($path),
        ]);
    }
}
