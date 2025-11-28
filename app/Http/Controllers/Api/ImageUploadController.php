<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadController extends Controller
{
    /**
     * Upload gambar
     *
     * Upload gambar untuk menu, user, atau branch. Ukuran maksimal 2MB.
     * File disimpan di storage/app/public/images/{type}/
     *
     * @authenticated
     * @bodyParam image file required File gambar (jpeg, png, jpg, gif, max: 2MB).
     * @bodyParam type string Tipe gambar (menu, user, branch). Default: menu. Example: menu
     *
     * @response 200 {
     *   "success": true,
     *   "filename": "menu_1732778400_xY9z2K4pLm.jpg",
     *   "path": "images/menu/menu_1732778400_xY9z2K4pLm.jpg",
     *   "url": "/storage/images/menu/menu_1732778400_xY9z2K4pLm.jpg"
     * }
     * @response 500 {
     *   "success": false,
     *   "message": "Image upload failed",
     *   "error": "..."
     * }
     */
    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'type' => 'nullable|string|in:menu,user,branch',
        ]);

        try {
            $type = $request->input('type', 'menu');
            $image = $request->file('image');

            // Generate unique filename
            $filename = $type . '_' . time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();

            // Store image in public disk
            $path = $image->storeAs('images/' . $type, $filename, 'public');

            // Generate URL
            $url = Storage::url($path);

            return response()->json([
                'success' => true,
                'filename' => $filename,
                'path' => $path,
                'url' => $url,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Image upload failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hapus gambar
     *
     * Menghapus file gambar dari storage berdasarkan path.
     *
     * @authenticated
     * @bodyParam path string required Path file gambar (relatif dari storage/app/public/). Example: images/menu/menu_1732778400_xY9z2K4pLm.jpg
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Image deleted successfully"
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Image not found"
     * }
     */
    public function delete(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        try {
            if (Storage::disk('public')->exists($request->path)) {
                Storage::disk('public')->delete($request->path);

                return response()->json([
                    'success' => true,
                    'message' => 'Image deleted successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Image not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Image deletion failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
