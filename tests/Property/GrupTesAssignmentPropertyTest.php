<?php

namespace Tests\Property;

use App\Models\Grup;
use App\Models\Tes;
use App\Models\Peserta;
use App\Models\TahapanSpmb;
use Eris\Generators;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Property-Based Tests untuk fitur Grup-Tes Assignment
 * Feature: grup-tes-assignment
 */
class GrupTesAssignmentPropertyTest extends PropertyTestCase
{
    use RefreshDatabase;

    /**
     * Feature: grup-tes-assignment, Property 1: Group-Test Assignment Persistence
     * Untuk setiap grup dan tes yang valid, ketika di-assign bersama,
     * query relasi harus mengembalikan assignment tersebut.
     * **Validates: Requirements 1.3**
     */
    public function test_group_test_assignment_persistence(): void
    {
        $this->forAll(
            Generators::pos(),
            Generators::pos()
        )->then(function ($grupCount, $tesCount) {
            // Limit to reasonable numbers
            $grupCount = min($grupCount % 5 + 1, 5);
            $tesCount = min($tesCount % 5 + 1, 5);
            
            // Create groups and tests
            $grups = Grup::factory()->count($grupCount)->create();
            $tests = Tes::factory()->count($tesCount)->create();
            
            // Assign all groups to first test
            $firstTes = $tests->first();
            $grupIds = $grups->pluck('id')->toArray();
            $firstTes->grup()->sync($grupIds);
            
            // Verify assignment persisted
            $assignedGrups = $firstTes->grup()->pluck('grup.id')->toArray();
            
            foreach ($grupIds as $grupId) {
                $this->assertContains($grupId, $assignedGrups, 
                    "Grup {$grupId} should be assigned to tes {$firstTes->id}");
            }
            
            // Verify count matches
            $this->assertCount($grupCount, $assignedGrups);
            
            // Cleanup
            foreach ($tests as $tes) {
                $tes->grup()->detach();
                $tes->delete();
            }
            foreach ($grups as $grup) {
                $grup->delete();
            }
        });
    }

    /**
     * Feature: grup-tes-assignment, Property 2: Group-Test Removal Consistency
     * Untuk setiap relasi grup-tes yang ada, ketika dihapus,
     * relasi tersebut tidak boleh ada lagi di database.
     * **Validates: Requirements 1.4**
     */
    public function test_group_test_removal_consistency(): void
    {
        $this->forAll(
            Generators::pos()
        )->then(function ($count) {
            $count = min($count % 5 + 1, 5);
            
            $grup = Grup::factory()->create();
            $tests = Tes::factory()->count($count)->create();
            
            // Assign all tests to grup
            $tesIds = $tests->pluck('id')->toArray();
            $grup->tes()->sync($tesIds);
            
            // Verify assignment
            $this->assertCount($count, $grup->tes);
            
            // Remove one test
            $removedTesId = $tesIds[0];
            $grup->tes()->detach($removedTesId);
            
            // Refresh and verify removal
            $grup->refresh();
            $remainingTesIds = $grup->tes()->pluck('tes.id')->toArray();
            
            $this->assertNotContains($removedTesId, $remainingTesIds,
                "Tes {$removedTesId} should be removed from grup {$grup->id}");
            $this->assertCount($count - 1, $remainingTesIds);
            
            // Cleanup
            $grup->tes()->detach();
            foreach ($tests as $tes) {
                $tes->delete();
            }
            $grup->delete();
        });
    }

    /**
     * Feature: grup-tes-assignment, Property 6: Group Count Accuracy
     * Untuk setiap tes, jumlah grup yang ditampilkan harus sama dengan
     * jumlah grup yang sebenarnya di relasi grup_tes.
     * **Validates: Requirements 5.1**
     */
    public function test_group_count_accuracy(): void
    {
        $this->forAll(
            Generators::pos()
        )->then(function ($count) {
            $count = min($count % 10 + 1, 10);
            
            $tes = Tes::factory()->create();
            $grups = Grup::factory()->count($count)->create();
            
            // Assign groups
            $grupIds = $grups->pluck('id')->toArray();
            $tes->grup()->sync($grupIds);
            
            // Verify count attribute matches actual count
            $tes->refresh();
            $actualCount = $tes->grup()->count();
            $attributeCount = $tes->jumlah_grup;
            
            $this->assertEquals($actualCount, $attributeCount,
                "Jumlah grup attribute ({$attributeCount}) should match actual count ({$actualCount})");
            $this->assertEquals($count, $attributeCount);
            
            // Cleanup
            $tes->grup()->detach();
            $tes->delete();
            foreach ($grups as $grup) {
                $grup->delete();
            }
        });
    }

    /**
     * Feature: grup-tes-assignment, Property 7: Test Count Accuracy
     * Untuk setiap grup, jumlah tes yang ditampilkan harus sama dengan
     * jumlah tes yang sebenarnya di relasi grup_tes.
     * **Validates: Requirements 5.2**
     */
    public function test_test_count_accuracy(): void
    {
        $this->forAll(
            Generators::pos()
        )->then(function ($count) {
            $count = min($count % 10 + 1, 10);
            
            $grup = Grup::factory()->create();
            $tests = Tes::factory()->count($count)->create();
            
            // Assign tests
            $tesIds = $tests->pluck('id')->toArray();
            $grup->tes()->sync($tesIds);
            
            // Verify count attribute matches actual count
            $grup->refresh();
            $actualCount = $grup->tes()->count();
            $attributeCount = $grup->jumlah_tes;
            
            $this->assertEquals($actualCount, $attributeCount,
                "Jumlah tes attribute ({$attributeCount}) should match actual count ({$actualCount})");
            $this->assertEquals($count, $attributeCount);
            
            // Cleanup
            $grup->tes()->detach();
            foreach ($tests as $tes) {
                $tes->delete();
            }
            $grup->delete();
        });
    }

    /**
     * Feature: grup-tes-assignment, Property 3: Participant Test Visibility
     * Untuk setiap peserta dengan grup, tes yang terlihat harus hanya tes
     * yang di-assign ke salah satu grup mereka, plus tes tanpa grup.
     * **Validates: Requirements 3.1, 3.3**
     */
    public function test_participant_test_visibility(): void
    {
        $this->forAll(
            Generators::pos()
        )->then(function ($seed) {
            // Create peserta with tahapan
            $peserta = Peserta::factory()->create();
            TahapanSpmb::create([
                'peserta_id' => $peserta->id,
                'tahap_saat_ini' => 4,
                'tahap_1_selesai' => true,
                'tahap_2_selesai' => true,
                'tahap_3_selesai' => true,
            ]);
            
            // Create two groups
            $grupPeserta = Grup::factory()->create();
            $grupLain = Grup::factory()->create();
            
            // Assign peserta to first group
            $peserta->grup()->attach($grupPeserta->id);
            
            // Create tests
            $tesDenganGrupPeserta = Tes::factory()->aktif()->create();
            $tesDenganGrupLain = Tes::factory()->aktif()->create();
            $tesTanpaGrup = Tes::factory()->aktif()->create();
            
            // Assign tests to groups
            $tesDenganGrupPeserta->grup()->attach($grupPeserta->id);
            $tesDenganGrupLain->grup()->attach($grupLain->id);
            // tesTanpaGrup has no grup
            
            // Get visible tests using service
            $tesService = app(\App\Services\TesService::class);
            $visibleTests = $tesService->ambilTesTersediaUntukPeserta($peserta);
            $visibleIds = $visibleTests->pluck('id')->toArray();
            
            // Verify: peserta should see tests assigned to their grup + tests without grup
            $this->assertContains($tesDenganGrupPeserta->id, $visibleIds,
                "Peserta should see test assigned to their grup");
            $this->assertContains($tesTanpaGrup->id, $visibleIds,
                "Peserta should see test without grup");
            $this->assertNotContains($tesDenganGrupLain->id, $visibleIds,
                "Peserta should NOT see test assigned to other grup");
            
            // Cleanup
            $peserta->grup()->detach();
            $tesDenganGrupPeserta->grup()->detach();
            $tesDenganGrupLain->grup()->detach();
            $tesDenganGrupPeserta->delete();
            $tesDenganGrupLain->delete();
            $tesTanpaGrup->delete();
            $grupPeserta->delete();
            $grupLain->delete();
            $peserta->tahapanSpmb()->delete();
            $peserta->forceDelete();
        });
    }

    /**
     * Feature: grup-tes-assignment, Property 4: Participant Access Control
     * Untuk setiap peserta dan tes yang tidak di-assign ke grup mereka
     * (dan tes punya grup), akses harus ditolak.
     * **Validates: Requirements 3.2**
     */
    public function test_participant_access_control(): void
    {
        $this->forAll(
            Generators::pos()
        )->then(function ($seed) {
            // Create peserta
            $peserta = Peserta::factory()->create();
            TahapanSpmb::create([
                'peserta_id' => $peserta->id,
                'tahap_saat_ini' => 4,
                'tahap_1_selesai' => true,
            ]);
            
            // Create two groups
            $grupPeserta = Grup::factory()->create();
            $grupLain = Grup::factory()->create();
            
            // Assign peserta to first group only
            $peserta->grup()->attach($grupPeserta->id);
            
            // Create test assigned to other group only
            $tes = Tes::factory()->aktif()->create();
            $tes->grup()->attach($grupLain->id);
            
            // Check access - should be denied
            $tesService = app(\App\Services\TesService::class);
            $hasAccess = $tesService->cekAksesPeserta($tes, $peserta);
            
            $this->assertFalse($hasAccess,
                "Peserta should NOT have access to test assigned to different grup");
            
            // Now test with no grup assigned - should have access
            $tesNoGrup = Tes::factory()->aktif()->create();
            $hasAccessNoGrup = $tesService->cekAksesPeserta($tesNoGrup, $peserta);
            
            $this->assertTrue($hasAccessNoGrup,
                "Peserta should have access to test with no grup assigned");
            
            // Cleanup
            $peserta->grup()->detach();
            $tes->grup()->detach();
            $tes->delete();
            $tesNoGrup->delete();
            $grupPeserta->delete();
            $grupLain->delete();
            $peserta->tahapanSpmb()->delete();
            $peserta->forceDelete();
        });
    }

    /**
     * Feature: grup-tes-assignment, Property 5: Bulk Assignment Consistency
     * Untuk setiap set tes dan grup, bulk assignment harus menghasilkan
     * semua tes yang dipilih memiliki semua grup yang dipilih.
     * **Validates: Requirements 4.2**
     */
    public function test_bulk_assignment_consistency(): void
    {
        $this->forAll(
            Generators::pos(),
            Generators::pos()
        )->then(function ($tesCount, $grupCount) {
            $tesCount = min($tesCount % 5 + 1, 5);
            $grupCount = min($grupCount % 3 + 1, 3);
            
            // Create tests and groups
            $tests = Tes::factory()->count($tesCount)->create();
            $grups = Grup::factory()->count($grupCount)->create();
            
            $grupIds = $grups->pluck('id')->toArray();
            
            // Bulk assign all groups to all tests
            $tesService = app(\App\Services\TesService::class);
            foreach ($tests as $tes) {
                $tesService->assignGrup($tes, $grupIds);
            }
            
            // Verify all tests have all groups
            foreach ($tests as $tes) {
                $tes->refresh();
                $assignedGrupIds = $tes->grup()->pluck('grup.id')->toArray();
                
                foreach ($grupIds as $grupId) {
                    $this->assertContains($grupId, $assignedGrupIds,
                        "Tes {$tes->id} should have grup {$grupId} assigned after bulk assignment");
                }
                
                $this->assertCount($grupCount, $assignedGrupIds);
            }
            
            // Cleanup
            foreach ($tests as $tes) {
                $tes->grup()->detach();
                $tes->delete();
            }
            foreach ($grups as $grup) {
                $grup->delete();
            }
        });
    }
}
