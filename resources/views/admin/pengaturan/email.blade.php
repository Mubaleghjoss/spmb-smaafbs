@extends('layouts.admin')

@section('title', 'Pengaturan Email')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Pengaturan Email</h1>
        <a href="{{ route('admin.pengaturan.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    @if(session('sukses'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('sukses') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <form method="POST" action="{{ route('admin.pengaturan.email.simpan') }}">
                @csrf
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Konfigurasi SMTP</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Driver Email</label>
                            <select name="mail_driver" class="form-select">
                                <option value="smtp" {{ $email['mail_driver'] === 'smtp' ? 'selected' : '' }}>SMTP</option>
                                <option value="sendmail" {{ $email['mail_driver'] === 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">SMTP Host</label>
                                <input type="text" name="mail_host" class="form-control" 
                                       value="{{ old('mail_host', $email['mail_host']) }}" placeholder="smtp.gmail.com">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Port</label>
                                <input type="number" name="mail_port" class="form-control" 
                                       value="{{ old('mail_port', $email['mail_port']) }}" placeholder="587">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="mail_username" class="form-control" 
                                       value="{{ old('mail_username', $email['mail_username']) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="mail_password" class="form-control" 
                                       value="{{ old('mail_password', $email['mail_password']) }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Enkripsi</label>
                            <select name="mail_encryption" class="form-select">
                                <option value="tls" {{ $email['mail_encryption'] === 'tls' ? 'selected' : '' }}>TLS</option>
                                <option value="ssl" {{ $email['mail_encryption'] === 'ssl' ? 'selected' : '' }}>SSL</option>
                                <option value="" {{ empty($email['mail_encryption']) ? 'selected' : '' }}>Tidak ada</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Pengirim Default</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Pengirim</label>
                                <input type="email" name="mail_from_address" class="form-control" 
                                       value="{{ old('mail_from_address', $email['mail_from_address']) }}" placeholder="noreply@sekolah.sch.id">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Pengirim</label>
                                <input type="text" name="mail_from_name" class="form-control" 
                                       value="{{ old('mail_from_name', $email['mail_from_name']) }}" placeholder="SPMB SMA Al Furqon">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <form method="POST" action="{{ route('admin.pengaturan.email.test') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-info">
                            <i class="bi bi-send"></i> Test Koneksi
                        </button>
                    </form>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Panduan</h6>
                </div>
                <div class="card-body">
                    <h6>Gmail SMTP</h6>
                    <ul class="small text-muted">
                        <li>Host: smtp.gmail.com</li>
                        <li>Port: 587 (TLS) atau 465 (SSL)</li>
                        <li>Gunakan App Password jika 2FA aktif</li>
                    </ul>

                    <h6>Yahoo SMTP</h6>
                    <ul class="small text-muted">
                        <li>Host: smtp.mail.yahoo.com</li>
                        <li>Port: 587 (TLS)</li>
                    </ul>

                    <h6>Outlook SMTP</h6>
                    <ul class="small text-muted mb-0">
                        <li>Host: smtp.office365.com</li>
                        <li>Port: 587 (TLS)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
