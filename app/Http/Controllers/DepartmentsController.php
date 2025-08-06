<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\departments;

class DepartmentsController extends Controller
{
     // Get all departments (non-archived)
    public function getDepartments()
    {
        $departments = departments::where('is_archive', false)->get();

        return response()->json([
            'isSuccess' => true,
            'department' => $departments
        ]);
    }

    // Add a new department
    public function addDepartment(Request $request)
    {
        $validated = $request->validate([
            'department_name' => 'required|string|max:100',
            'abbreviation' => 'nullable|string|max:20',
            'description' => 'nullable|string',
        ]);

        $department = departments::create($validated);

        return response()->json([
            'isSuccess' => true,
            'message' => 'Department created successfully',
            'department' => $department
        ]);
    }

    // Update a department
    public function updateDepartment(Request $request, $id)
    {
        $department = departments::findOrFail($id);

        $validated = $request->validate([
            'department_name' => 'required|string|max:100',
            'abbreviation' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'is_archive' => 'boolean',
        ]);

        $department->update($validated);

        return response()->json([
            'isSuccess' => true,
            'message' => 'Department updated successfully',
            'department' => $department
        ]);
    }

    // Delete (soft delete by setting is_archive to true)
    public function deleteDepartment($id)
    {
        $department = departments::findOrFail($id);
        $department->is_archive = true;
        $department->save();

        return response()->json([
            'isSuccess' => true,
            'message' => 'Department archived successfully'
        ]);
    }
}

