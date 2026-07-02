<?php

namespace App\Livewire;

use App\Models\Peserta;
use App\Services\SpmbService;
use App\Enums\TahapanSpmb as TahapanSpmbEnum;
use Livewire\Component;

class StatusTahapan extends Component
{
    public int $pesertaId;
    public array $statusTahapan = [];
    public int $tahapSaatIni = 1;

    public function mount(int $pesertaId): void
    {
        $this->pesertaId = $pesertaId;
        $this->refreshStatus();
    }

    public function refreshStatus(): void
    {
        $peserta = Peserta::with('tahapanSpmb')->find($this->pesertaId);
        
        if (!$peserta) {
            return;
        }

        $tahapan = $peserta->tahapanSpmb;
        $this->tahapSaatIni = $tahapan?->tahap_saat_ini ?? 1;

        $this->statusTahapan = [
            1 => [
                'selesai' => $tahapan?->tahap_1_selesai ?? true,
                'label' => TahapanSpmbEnum::BUAT_AKUN->label(),
                'icon' => 'person-plus',
            ],
            2 => [
                'selesai' => $tahapan?->tahap_2_selesai ?? false,
                'label' => TahapanSpmbEnum::ISI_FORMULIR->label(),
                'icon' => 'file-earmark-text',
            ],
            3 => [
                'selesai' => $tahapan?->tahap_3_selesai ?? false,
                'label' => TahapanSpmbEnum::BAYAR_FORMULIR->label(),
                'icon' => 'credit-card',
            ],
        ];
    }

    public function render()
    {
        return view('livewire.status-tahapan');
    }
}
