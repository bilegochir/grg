<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WorkspaceBranchController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->workspace()->canSwitchBranches($user), Response::HTTP_FORBIDDEN);

        $data = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ]);

        $branchId = $data['branch_id'] ?? null;

        if ($branchId !== null) {
            Branch::query()->whereKey($branchId)->where('is_active', true)->firstOrFail();
        }

        $request->session()->put('workspace_branch_id', $branchId);

        return back()->with('success', $branchId ? 'Branch view updated.' : 'Showing all branches.');
    }
}
