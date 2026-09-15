# PHPUnit Failure Audit — 2026-09-15

The 12 reproduced failures were audited against the current registration workflow, jalur context, and quota rules.

| # | TEST | EXPECTED | ACTUAL | ROOT CAUSE | CLASSIFICATION | PROPOSED FIX | RISK |
|---|---|---|---|---|---|---|---|
| 1 | `AdminRegistrationCategoryTest::test_admin_dapat_memfilter_peserta_berdasarkan_tahun_ajaran` | Default-year participant visible | Jalur-selection page | Admin participant page now requires an active jalur context; test did not select one | STALE TEST | Set `siswa_baru` jalur context in test setup | Low; test-only context setup |
| 2 | `...::test_admin_dapat_memfilter_peserta_berdasarkan_status_kuota` | Waiting-list participant visible | Jalur-selection page | Same missing jalur context | STALE TEST | Same test fixture correction | Low |
| 3 | `...::test_admin_dapat_memfilter_data_formulir_peserta` | Filtered participant visible | Jalur-selection page | Same missing jalur context | STALE TEST | Same test fixture correction | Low |
| 4 | `...::test_admin_melihat_rekap_data_formulir_peserta` | Form recap visible | Jalur-selection page | Same missing jalur context | STALE TEST | Same test fixture correction | Low |
| 5 | `...::test_update_kuota_tahun_ajaran_merekalkulasi_waiting_list` | 2 in quota, 1 waiting | 0 in quota | Current quota policy requires a form and active form-payment reservation; bare factory participants are not quota-eligible | STALE TEST | Add complete form, gender, and active payment fixtures | Low; preserves quota rules |
| 6 | `RegistrationCategoryTest::test_form_tetap_dibuka_oleh_periode_meski_toggle_lama_ditutup` | `Daftar Sekarang` text | Current public wizard uses `Lanjut`; period still opens correctly | UI workflow changed from the old CTA text; legacy global toggle is intentionally ignored when a wave is open | STALE TEST | Assert current wizard CTA and wave state | Low |
| 7 | `...::test_siswa_baru_selalu_disimpan_sebagai_kelas_10` | Payload with class 11 still registers as class 10 | Validation rejected class 11 before normalization | Controller validated the jalur-specific class rule before applying the existing category normalization | REAL REGRESSION | Normalize `kelas_tujuan` to 10 before validation for `siswa_baru` | Low; enforces existing policy and blocks invalid persistence |
| 8 | `...::test_peserta_di_luar_kuota_otomatis_masuk_waiting_list` | Registration redirects and quota statuses are asserted immediately | Registration correctly created incomplete participants; quota assignment waits for active payment | Test assumed pre-payment quota reservation, contrary to current policy | STALE TEST | Add active form-payment fixtures and call recalculation before status assertions; assert current auto-login dashboard redirect | Low |
| 9 | `...::test_kuota_gender_direkalkulasi_setelah_formulir_menyimpan_jenis_kelamin` | Gender quota recalculates | `belum_lengkap` | Test participants lacked active form-payment reservations required for quota eligibility | STALE TEST | Add active payment fixtures | Low |
| 10 | `...::test_gelombang_dari_tahun_lain_ditolak` | `gelombang_pendaftaran_id` error reached | Earlier gender/pindahan-contact validation errors masked it | Wave-validation test fixture omitted required gender and pindahan school-contact fields | STALE TEST | Complete gender and school operator/contact fixture fields | Low |
| 11 | `...::test_gelombang_kedaluwarsa_tidak_dapat_dipakai` | `gelombang_pendaftaran_id` error reached | Same masking validation errors | Same incomplete pindahan fixture | STALE TEST | Complete gender and school operator/contact fixture fields | Low |
| 12 | `...::test_form_publik_mengikuti_jam_buka_gelombang` | `Belum dibuka` and `Daftar Sekarang` server-rendered text | Current closed/open page uses wave-driven state and `Lanjut` CTA; no old CTA text | Test asserted legacy UI strings rather than current wave wizard markup | STALE TEST | Assert current closed message and current wizard CTA | Low |

## Changes made

- Preserved all pre-existing synthetic-testing and isolated-database changes.
- Updated stale feature fixtures/assertions to match the current jalur, payment-gated quota, auto-login, and public-wizard behavior.
- Fixed the proven class-normalization regression in `PendaftaranController`: siswa baru payloads are normalized to class 10 before validation.

## Verification

- Targeted suite: **18 passed, 84 assertions**.
- Exact full suite command completed green: **138 passed, 32,075 assertions, 0 failures**, exit code 0.
- Resolved target remained the isolated testing database configured by the task; no production/staging database was migrated or modified.
