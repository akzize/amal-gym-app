<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * A list of insured trainees sent to the insurer. Each insurance payment belongs to at most one export.
 */
class InsuranceExport extends Model
{
    protected $guarded = [];

    /**
     * Put every insurance payment not exported yet into a new export, or return null when there is none.
     */
    public static function createFromPending(?int $userId): ?self
    {
        return DB::transaction(function () use ($userId) {
            $export = self::create(['exported_by' => $userId]);

            // A single UPDATE on the not-yet-exported rows: two exports made at the same moment can't share a payment
            $stamped = Payment::query()->awaitingInsuranceExport()->update(['insurance_export_id' => $export->id]);

            if ($stamped === 0) {
                $export->delete();

                return null;
            }

            return $export;
        });
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function exportedBy()
    {
        return $this->belongsTo(User::class, 'exported_by');
    }
}
