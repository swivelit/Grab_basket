<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class BannerController extends Controller
{
    /**
     * Display a listing of banners
     */
    public function index()
    {
        if (!session('is_admin')) {
            return redirect()->route('admin.login');
        }

        $banners = Banner::orderBy('display_order')->orderBy('created_at', 'desc')->get();
        
        return view('admin.banners.index', compact('banners'));
    }

    /**
     * Show the form for creating a new banner
     */
    public function create()
    {
        if (!session('is_admin')) {
            return redirect()->route('admin.login');
        }

        return view('admin.banners.create');
    }

    /**
     * Store a newly created banner
     */
    public function store(Request $request)
    {
        if (!session('is_admin')) {
            return redirect()->route('admin.login');
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'link_url' => 'nullable|url',
            'button_text' => 'required|string|max:50',
            'position' => 'required|in:hero,top,middle,bottom',
            'theme' => 'required|in:festive,modern,minimal,gradient',
            'background_color' => 'required|string|max:7',
            'text_color' => 'required|string|max:7',
            'is_active' => 'boolean',
            'display_order' => 'integer|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $data = $request->except('image');
            $data['is_active'] = $request->has('is_active') ? true : false;

            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = 'banner_' . time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/banners'), $imageName);
                $data['image_url'] = 'images/banners/' . $imageName;
            }

            $banner = Banner::create($data);

            Log::info('Banner created', ['banner_id' => $banner->id, 'title' => $banner->title]);

            return redirect()->route('admin.banners.index')
                ->with('success', 'Banner created successfully!');
        } catch (\Exception $e) {
            Log::error('Banner creation failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to create banner: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the form for editing a banner
     */
    public function edit($id)
    {
        if (!session('is_admin')) {
            return redirect()->route('admin.login');
        }

        $banner = Banner::findOrFail($id);
        
        return view('admin.banners.edit', compact('banner'));
    }

    /**
     * Update the specified banner
     */
    public function update(Request $request, $id)
    {
        if (!session('is_admin')) {
            return redirect()->route('admin.login');
        }

        $banner = Banner::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'link_url' => 'nullable|url',
            'button_text' => 'required|string|max:50',
            'position' => 'required|in:hero,top,middle,bottom',
            'theme' => 'required|in:festive,modern,minimal,gradient',
            'background_color' => 'required|string|max:7',
            'text_color' => 'required|string|max:7',
            'is_active' => 'boolean',
            'display_order' => 'integer|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $data = $request->except('image');
            $data['is_active'] = $request->has('is_active') ? true : false;

            // Handle new image upload
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($banner->image_url && file_exists(public_path($banner->image_url))) {
                    unlink(public_path($banner->image_url));
                }

                $image = $request->file('image');
                $imageName = 'banner_' . time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/banners'), $imageName);
                $data['image_url'] = 'images/banners/' . $imageName;
            }

            $banner->update($data);

            Log::info('Banner updated', ['banner_id' => $banner->id, 'title' => $banner->title]);

            return redirect()->route('admin.banners.index')
                ->with('success', 'Banner updated successfully!');
        } catch (\Exception $e) {
            Log::error('Banner update failed', ['error' => $e->getMessage(), 'banner_id' => $id]);
            return back()->with('error', 'Failed to update banner: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified banner
     */
    public function destroy($id)
    {
        if (!session('is_admin')) {
            return redirect()->route('admin.login');
        }

        try {
            $banner = Banner::findOrFail($id);

            // Delete image if exists
            if ($banner->image_url && file_exists(public_path($banner->image_url))) {
                unlink(public_path($banner->image_url));
            }

            $banner->delete();

            Log::info('Banner deleted', ['banner_id' => $id]);

            return redirect()->route('admin.banners.index')
                ->with('success', 'Banner deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Banner deletion failed', ['error' => $e->getMessage(), 'banner_id' => $id]);
            return back()->with('error', 'Failed to delete banner: ' . $e->getMessage());
        }
    }

    /**
     * Toggle banner active status
     */
    public function toggleStatus($id)
    {
        if (!session('is_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $banner = Banner::findOrFail($id);
            $banner->is_active = !$banner->is_active;
            $banner->save();

            Log::info('Banner status toggled', ['banner_id' => $id, 'is_active' => $banner->is_active]);

            return response()->json([
                'success' => true,
                'is_active' => $banner->is_active,
                'message' => 'Banner status updated successfully!'
            ]);
        } catch (\Exception $e) {
            Log::error('Banner status toggle failed', ['error' => $e->getMessage(), 'banner_id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update banner status'
            ], 500);
        }
    }
}
