<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CrabSpecies;
use App\Models\RecognitionRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminScanDataController extends Controller
{
    private const STATUSES = ['recognized', 'low_confidence', 'no_detection', 'failed', 'pending'];
    private const CONFIDENCE_LEVELS = ['high', 'moderate', 'low', 'unknown'];

    public function index(Request $request)
    {
        return view('admin.scans.index', [
            'records' => $this->filtered($request)->with(['user', 'species', 'expertSpecies'])->withCount('feedback')->latest()->paginate(12)->withQueryString(),
            'species' => CrabSpecies::where('is_active', true)->orderBy('common_name')->get(),
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
            'statuses' => self::STATUSES,
            'confidenceLevels' => self::CONFIDENCE_LEVELS,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $records = $this->filtered($request)->with(['user', 'species', 'expertSpecies'])->latest()->get();

        return response()->streamDownload(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Scan reference', 'Date', 'User', 'Detected species', 'Expert species', 'Confidence', 'Status', 'Location', 'Model', 'Training candidate', 'Admin notes']);

            foreach ($records as $record) {
                fputcsv($handle, [
                    $record->scan_reference,
                    optional($record->created_at)->toDateTimeString(),
                    $record->user?->email,
                    $record->species?->common_name ?? $record->predicted_class ?? 'Unknown',
                    $record->expertSpecies?->common_name,
                    $record->confidence === null ? '' : number_format($record->confidence * 100, 1).'%',
                    $record->recognition_status,
                    $record->location_label ?: trim(($record->latitude ?? '').' '.($record->longitude ?? '')),
                    trim(($record->model_name ?? '').' '.($record->model_version ?? '')),
                    $record->needs_retraining ? 'yes' : 'no',
                    $record->admin_notes,
                ]);
            }

            fclose($handle);
        }, 'admin-scan-data.csv', ['Content-Type' => 'text/csv']);
    }

    public function edit(RecognitionRecord $scan)
    {
        return view('admin.scans.edit', [
            'scan' => $scan->load(['user', 'species', 'expertSpecies', 'feedback.user']),
            'species' => CrabSpecies::where('is_active', true)->orderBy('common_name')->get(),
            'statuses' => self::STATUSES,
            'confidenceLevels' => self::CONFIDENCE_LEVELS,
        ]);
    }

    public function update(Request $request, RecognitionRecord $scan)
    {
        $data = $request->validate([
            'recognition_status' => ['required', Rule::in(self::STATUSES)],
            'confidence_level' => ['required', Rule::in(self::CONFIDENCE_LEVELS)],
            'expert_species_id' => ['nullable', 'exists:crab_species,id'],
            'location_label' => ['nullable', 'string', 'max:255'],
            'capture_notes' => ['nullable', 'string', 'max:1000'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $old = $scan->toArray();
        $scan->update($data + [
            'needs_retraining' => $request->boolean('needs_retraining'),
        ]);

        $this->audit($request, 'updated', $scan, $old, $scan->fresh()->toArray());

        return redirect()->route('admin.scans.edit', $scan)->with('status', 'Scan data updated.');
    }

    public function destroy(Request $request, RecognitionRecord $scan)
    {
        $old = $scan->toArray();
        $paths = collect([$scan->original_image_path, $scan->annotated_image_path])->filter()->all();

        if ($paths !== []) {
            Storage::delete($paths);
        }

        $scan->delete();
        $this->audit($request, 'deleted', $scan, $old, null);

        return redirect()->route('admin.scans.index')->with('status', 'Scan record deleted.');
    }

    private function filtered(Request $request)
    {
        $query = RecognitionRecord::query();

        if ($request->filled('q')) {
            $query->where(function ($inner) use ($request) {
                $term = '%'.$request->q.'%';
                $inner->where('scan_reference', 'like', $term)
                    ->orWhere('predicted_class', 'like', $term)
                    ->orWhere('location_label', 'like', $term)
                    ->orWhere('model_name', 'like', $term)
                    ->orWhereHas('user', function ($userQuery) use ($term) {
                        $userQuery->where('name', 'like', $term)
                            ->orWhere('email', 'like', $term);
                    });
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('species')) {
            $query->where('crab_species_id', $request->species);
        }

        if ($request->filled('status')) {
            $query->where('recognition_status', $request->status);
        }

        if ($request->filled('confidence')) {
            $query->where('confidence_level', $request->confidence);
        }

        if ($request->filled('training')) {
            $query->where('needs_retraining', $request->training === 'yes');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return $query;
    }

    private function audit(Request $request, string $action, RecognitionRecord $scan, ?array $old, ?array $new): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'scan.'.$action,
            'entity_type' => RecognitionRecord::class,
            'entity_id' => $scan->id,
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
