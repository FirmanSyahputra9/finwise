<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $directory = public_path('profile-photos/'.$user->id);
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            abort(500, 'Unable to create the profile photo directory.');
        }

        $filename = Str::uuid().'.webp';
        $oldPath = $user->profile_photo_path;
        $photo->move($directory, $filename);
        $path = 'profile-photos/'.$user->id.'/'.$filename;
        $user->update(['profile_photo_path' => $path]);

        if ($oldPath && str_starts_with($oldPath, 'profile-photos/'.$user->id.'/')) {
            $oldFile = public_path($oldPath);
            if (is_file($oldFile)) @unlink($oldFile);
        }

        return response()->json([
            'message' => __('ui.photo_saved'),
            'url' => asset($path),
        ]);
    }
}
