<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::latest()->get();

        return view('admin.banners.index', compact('banners'));
    }

    public function create()
    {
        return view('admin.banners.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required',
        ]);
        // image
        $image = $request->file('image');
        $ext = $image->getClientOriginalExtension();
        $filename = uniqid('banner').'.'.$ext; // Generate a unique filename
        $image->move(public_path('assets/img/banners/'), $filename); // Save the file
        Banner::create([
            'image' => $filename,
        ]);

        return redirect(route('admin.banners.index'))->with('success', 'New Banner Image Added.');
    }

    public function show(Banner $banner)
    {
        return view('admin.banners.show', compact('banner'));
    }

    public function edit(Banner $banner)
    {
        return view('admin.banners.edit', compact('banner'));
    }

    public function update(Request $request, Banner $banner)
    {
        if (! $banner) {
            return redirect()->back()->with('error', 'Banner Not Found');
        }
        $request->validate([
            'image' => 'required',
        ]);
        //remove banner from localstorage
        File::delete(public_path('assets/img/banners/'.$banner->image));

        // image
        $image = $request->file('image');
        $ext = $image->getClientOriginalExtension();
        $filename = uniqid('banner').'.'.$ext; // Generate a unique filename
        $image->move(public_path('assets/img/banners/'), $filename); // Save the file

        $banner->update([
            'image' => $filename,
        ]);

        return redirect(route('admin.banners.index'))->with('success', 'Banner Image Updated.');
    }

    public function destroy(Banner $banner)
    {
        if (! $banner) {
            return redirect()->back()->with('error', 'Banner Not Found');
        }

        File::delete(public_path('assets/img/banners/'.$banner->image));
        $banner->delete();

        return redirect()->back()->with('success', 'Banner Deleted.');
    }
}
