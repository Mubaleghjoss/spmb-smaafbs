@extends('layouts.admin')

@section('title', 'Data Peserta')

@section('content')
    @include('admin.partials.pilih-jalur', ['konteks' => 'daftar peserta'])
@endsection
