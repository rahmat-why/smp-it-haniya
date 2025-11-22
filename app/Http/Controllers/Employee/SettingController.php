<?php

namespace App\Http\Controllers\Employee;

use App\Models\MstHeaderSetting;
use App\Models\MstDetailSetting;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    // Check authentication before accessing
    public function __construct()
    {
        if (session('user_type') !== 'Employee') {
            return redirect('/employee/login');
        }
    }

    // ============ HEADER SETTINGS ============

    /**
     * Display all header settings with their details
     * Uses raw SELECT for better performance on complex queries
     */
    public function indexHeader()
    {
        $headers = DB::select('
            SELECT h.*, 
                   COUNT(d.detail_id) as detail_count
            FROM mst_header_settings h
            LEFT JOIN mst_detail_settings d ON h.header_id = d.header_id
            GROUP BY h.header_id, h.title, h.created_at, h.updated_at, h.created_by, h.updated_by
            ORDER BY h.header_id DESC
        ');

        return view('employee.settings.index-header', compact('headers'));
    }

    /**
     * Show form for creating new header setting
     */
    public function createHeader()
    {
        return view('employee.settings.create-header');
    }

    /**
     * Store new header setting in database
     * Uses Eloquent for safe data insertion
     */
    public function storeHeader(Request $request)
    {
        $validated = $request->validate([
            'header_id' => 'required|string|max:50|unique:mst_header_settings',
            'title' => 'required|string|max:255',
        ]);

        $validated['created_by'] = session('employee_id');
        $validated['updated_by'] = session('employee_id');
        $validated['created_at'] = now();
        $validated['updated_at'] = now();

        MstHeaderSetting::create($validated);

        return redirect()->route('employee.settings.index-header')
                       ->with('success', 'Header setting created successfully!');
    }

    /**
     * Show form for editing header setting
     * Uses raw SELECT for retrieval
     */
    public function editHeader($id)
    {
        $header = DB::select(
            'SELECT * FROM mst_header_settings WHERE header_id = ?',
            [$id]
        );

        if (empty($header)) {
            return redirect()->route('employee.settings.index-header')
                           ->with('error', 'Header setting not found!');
        }

        return view('employee.settings.edit-header', ['header' => $header[0]]);
    }

    /**
     * Update header setting in database
     * Uses Eloquent for safe data update
     */
    public function updateHeader(Request $request, $id)
    {
        $header = MstHeaderSetting::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $validated['updated_by'] = session('employee_id');
        $validated['updated_at'] = now();

        $header->update($validated);

        return redirect()->route('employee.settings.index-header')
                       ->with('success', 'Header setting updated successfully!');
    }

    /**
     * Delete header setting from database
     * Also deletes all related detail settings
     */
    public function destroyHeader($id)
    {
        $header = MstHeaderSetting::findOrFail($id);

        // Delete all detail settings first
        MstDetailSetting::where('header_id', $id)->delete();

        // Delete header
        $header->delete();

        return redirect()->route('employee.settings.index-header')
                       ->with('success', 'Header setting and all details deleted successfully!');
    }

    // ============ DETAIL SETTINGS ============

    /**
     * Display details for a specific header
     * Uses raw SELECT for better performance
     */
    public function indexDetail($headerId)
    {
        // Check if header exists
        $header = DB::select(
            'SELECT * FROM mst_header_settings WHERE header_id = ?',
            [$headerId]
        );

        if (empty($header)) {
            return redirect()->route('employee.settings.index-header')
                           ->with('error', 'Header setting not found!');
        }

        $details = DB::select(
            'SELECT * FROM mst_detail_settings WHERE header_id = ? ORDER BY detail_id DESC',
            [$headerId]
        );

        return view('employee.settings.index-detail', [
            'header' => $header[0],
            'details' => $details
        ]);
    }

    /**
     * Show form for creating detail setting
     */
    public function createDetail($headerId)
    {
        // Check if header exists
        $header = DB::select(
            'SELECT * FROM mst_header_settings WHERE header_id = ?',
            [$headerId]
        );

        if (empty($header)) {
            return redirect()->route('employee.settings.index-header')
                           ->with('error', 'Header setting not found!');
        }

        return view('employee.settings.create-detail', [
            'header' => $header[0]
        ]);
    }

    /**
     * Store new detail setting in database
     */
    public function storeDetail(Request $request, $headerId)
    {
        $validated = $request->validate([
            'detail_id' => 'required|string|max:50|unique:mst_detail_settings',
            'item_code' => 'required|string|max:50',
            'item_name' => 'required|string|max:255',
            'item_desc' => 'nullable|string|max:1000',
            'status' => 'required|in:Active,Inactive',
            'item_type' => 'required|string|max:50',
        ]);

        $validated['header_id'] = $headerId;
        $validated['created_by'] = session('employee_id');
        $validated['updated_by'] = session('employee_id');
        $validated['created_at'] = now();
        $validated['updated_at'] = now();

        MstDetailSetting::create($validated);

        return redirect()->route('employee.settings.detail', $headerId)
                       ->with('success', 'Detail setting created successfully!');
    }

    /**
     * Show form for editing detail setting
     */
    public function editDetail($headerId, $detailId)
    {
        $header = DB::select(
            'SELECT * FROM mst_header_settings WHERE header_id = ?',
            [$headerId]
        );

        if (empty($header)) {
            return redirect()->route('employee.settings.index-header')
                           ->with('error', 'Header setting not found!');
        }

        $detail = DB::select(
            'SELECT * FROM mst_detail_settings WHERE detail_id = ? AND header_id = ?',
            [$detailId, $headerId]
        );

        if (empty($detail)) {
            return redirect()->route('employee.settings.detail', $headerId)
                           ->with('error', 'Detail setting not found!');
        }

        return view('employee.settings.edit-detail', [
            'header' => $header[0],
            'detail' => $detail[0]
        ]);
    }

    /**
     * Update detail setting in database
     */
    public function updateDetail(Request $request, $headerId, $detailId)
    {
        $detail = MstDetailSetting::findOrFail($detailId);

        $validated = $request->validate([
            'item_code' => 'required|string|max:50',
            'item_name' => 'required|string|max:255',
            'item_desc' => 'nullable|string|max:1000',
            'status' => 'required|in:Active,Inactive',
            'item_type' => 'required|string|max:50',
        ]);

        $validated['updated_by'] = session('employee_id');
        $validated['updated_at'] = now();

        $detail->update($validated);

        return redirect()->route('employee.settings.detail', $headerId)
                       ->with('success', 'Detail setting updated successfully!');
    }

    /**
     * Delete detail setting from database
     */
    public function destroyDetail($headerId, $detailId)
    {
        $detail = MstDetailSetting::findOrFail($detailId);
        $detail->delete();

        return redirect()->route('employee.settings.detail', $headerId)
                       ->with('success', 'Detail setting deleted successfully!');
    }
}
