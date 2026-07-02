<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PsikotesNilaiJawaban extends Model
{
    use HasFactory;

    protected $table = 'psikotes_nilai_jawaban';

    protected $fillable = [
        'tes_id',
        'kode_jawaban',
        'nilai',
    ];

    public function tes(): BelongsTo
    {
        return $this->belongsTo(Tes::class);
    }

    /**
     * Default nilai jawaban (A=1, B=2, C=3, D=4)
     */
    public static function defaultNilai(): array
    {
        return [
            'A' => 1,
            'B' => 2,
            'C' => 3,
            'D' => 4,
        ];
    }
}
